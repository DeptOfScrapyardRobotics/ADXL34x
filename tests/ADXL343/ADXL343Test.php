<?php

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\ADXL343;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Breakouts\ADXL343DataFormat;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Breakouts\ADXL343PowerControl;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums\ADXL343OpCode;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums\ADXL343Range;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\CelestialBody;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeI2CTransport;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeInterruptPin;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xI2CTransport;
use GeneralPurposeIO\Contracts\IntegratedCircuits\Sensor;


/** @return array{0: ADXL343, 1: FakeI2CTransport} an unbooted chip over a scripted bus */
function adxl343(array $replies = []): array
{
    $bus = new FakeI2CTransport;
    $bus->replies = $replies;

    return [new ADXL343(new ADXL34xI2CTransport($bus)), $bus];
}

/** @return array{0: ADXL343, 1: FakeI2CTransport} a booted chip, boot replies consumed */
function bootedAdxl343(array $replies = []): array
{
    [$chip, $bus] = adxl343([...bootI2CReplies(), ...$replies]);
    $chip->boot();

    return [$chip, $bus];
}

it('is a bootable sensor that exposes its transport', function (): void {
    [$chip] = adxl343();

    expect($chip)->toBeInstanceOf(Sensor::class)
        ->and($chip->hasBooted())->toBeFalse()
        ->and($chip->transport())->toBeInstanceOf(ADXL34xI2CTransport::class);
});

it('boots by confirming 0xE5, entering measurement mode, waiting for the first sample, and applying the interrupt mask', function (): void {
    [$chip, $bus] = adxl343(bootI2CReplies());

    $chip->boot();

    expect($chip->hasBooted())->toBeTrue()
        ->and($bus->write_reads)->toBe([[[ADXL343OpCode::DEVICE_ID_REGISTER->value], 1], [[ADXL343OpCode::DATA_FROM_X0_REGISTER->value], 6], [[ADXL343OpCode::BW_RATE_REGISTER->value], 1], [[ADXL343OpCode::INTERRUPT_SOURCE_REGISTER->value], 1]])
        ->and($bus->writes)->toBe([[ADXL343OpCode::POWER_CONTROL_REGISTER->value, 0b0000_1000], [ADXL343OpCode::INTERRUPTS_ENABLED_REGISTER->value, 0x00]]);
});

it('keeps reading INT_SOURCE through boot until DATA_READY reports a fresh sample', function (): void {
    [$chip, $bus] = adxl343([[0xE5], [0, 0, 0, 0, 0, 0], [0x0A], [0x00], [0x02], [0x80]]);

    $chip->boot();

    expect(array_map(fn (array $wr): int => $wr[0][0], $bus->write_reads))->toBe([0x00, 0x32, 0x2C, 0x30, 0x30, 0x30]);
});

it('gives up on boot after two sample periods without DATA_READY', function (): void {
    [$chip] = adxl343([[0xE5], [0, 0, 0, 0, 0, 0], [0x0F], ...array_fill(0, 10_000, [0x00])]);

    expect(fn () => $chip->boot())->toThrow(ADXL34xException::class, 'no sample within 2 ms of entering measurement mode at 3200');
});

it('refuses a chip that does not answer 0xE5', function (): void {
    [$chip] = adxl343([[0x00]]);

    expect(fn () => $chip->boot())->toThrow(ADXL34xException::class, 'expected 229, got 0');
});

it('reads all three axes in one six-byte transaction, little-endian signed', function (): void {
    [$chip, $bus] = bootedAdxl343([[0x10, 0x00, 0xF0, 0xFF, 0x00, 0x01]]);

    $raw = $chip->raw();

    expect($raw)->toBe(['x' => 16, 'y' => -16, 'z' => 256])
        ->and(array_slice($bus->write_reads, BOOT_READS))->toBe([[[ADXL343OpCode::DATA_FROM_X0_REGISTER->value], 6]]);
});

it('reads the ADXL343OpCode::DATA_FORMAT_REGISTER->value register as a breakout, including range and resolution', function (): void {
    [$chip] = bootedAdxl343([[0b0000_1010]]);

    $format = $chip->getDataFormat();

    expect($format)->toBeInstanceOf(ADXL343DataFormat::class)
        ->and($format->full_resolution)->toBeTrue()
        ->and($format->range)->toBe(ADXL343Range::G8)
        ->and($format->self_test)->toBeFalse()
        ->and($format->toByte())->toBe(0b0000_1010);
});

it('changes the range without disturbing the other ADXL343OpCode::DATA_FORMAT_REGISTER->value bits', function (): void {
    [$chip, $bus] = bootedAdxl343([[0b0010_1000]]);

    $chip->setRange(ADXL343Range::G16);

    expect(end($bus->writes))->toBe([ADXL343OpCode::DATA_FORMAT_REGISTER->value, 0b0010_1011]);
});

it('switches resolution without disturbing the range', function (): void {
    [$chip, $bus] = bootedAdxl343([[0b0000_0010], [0b0000_1010]]);

    $chip->setResolution(true);
    $chip->setResolution(false);

    expect(array_slice($bus->writes, 2))->toBe([[ADXL343OpCode::DATA_FORMAT_REGISTER->value, 0b0000_1010], [ADXL343OpCode::DATA_FORMAT_REGISTER->value, 0b0000_0010]]);
});

it('scales at the range step in 10-bit mode and at 3.9 mg per LSB in full-resolution mode', function (): void {
    [$ten_bit_16g] = bootedAdxl343([[0b0000_0011]]);
    [$full_res_16g] = bootedAdxl343([[0b0000_1011]]);
    [$ten_bit_2g] = bootedAdxl343([[0b0000_0000]]);

    expect($ten_bit_16g->scale())->toBe(ADXL343Range::G16->scale())
        ->and($full_res_16g->scale())->toBe(ADXL343Range::G2->scale())
        ->and($ten_bit_2g->scale())->toBe(ADXL343Range::G2->scale());
});

it('converts an axis from raw counts through the effective scale to metres per second squared', function (): void {
    [$chip] = bootedAdxl343([[0b0000_0000], [0x00, 0x01, 0x00, 0x00, 0x00, 0x00]]);

    expect($chip->x())->toEqualWithDelta(256 * ADXL343Range::G2->scale() * CelestialBody::TERRA->gravity(), 1e-9);
});

it('reads all three accelerations from one transaction', function (): void {
    [$chip, $bus] = bootedAdxl343([[0b0000_0011], [0x00, 0x01, 0x00, 0x02, 0x00, 0x03]]);

    $g = CelestialBody::TERRA->gravity();
    $scale = ADXL343Range::G16->scale();

    $acceleration = $chip->acceleration();

    expect($acceleration['x'])->toEqualWithDelta(256 * $scale * $g, 1e-9)
        ->and($acceleration['y'])->toEqualWithDelta(512 * $scale * $g, 1e-9)
        ->and($acceleration['z'])->toEqualWithDelta(768 * $scale * $g, 1e-9)
        ->and(array_slice($bus->write_reads, BOOT_READS))->toBe([[[ADXL343OpCode::DATA_FORMAT_REGISTER->value], 1], [[ADXL343OpCode::DATA_FROM_X0_REGISTER->value], 6]]);
});

it('exposes the chip through properties, and refuses ones it does not have', function (): void {
    [$chip] = bootedAdxl343([[0b0001_0000], [0b0000_1000]]);

    $power = $chip->power_control;

    expect($power)->toBeInstanceOf(ADXL343PowerControl::class)
        ->and($power->link)->toBeTrue()
        ->and($chip->full_resolution)->toBeTrue()
        ->and(fn () => $chip->nope)->toThrow(ADXL34xException::class, "Invalid property 'nope'");
});

it('turns a refused bus read into an exception instead of a type error', function (): void {
    [$chip] = bootedAdxl343([false]);

    expect(fn () => $chip->raw())->toThrow(ADXL34xException::class, 'register 0x32');
});

it('turns a short bus read into an exception', function (): void {
    [$chip] = bootedAdxl343([[0x00, 0x01]]);

    expect(fn () => $chip->raw())->toThrow(ADXL34xException::class, 'wanted 6 bytes, got 2');
});

it('releases its interrupt pins on close and leaves the bus connection to its driver', function (): void {
    $bus = new FakeI2CTransport;
    $bus->replies = bootI2CReplies();
    $int1 = new FakeInterruptPin(4);
    $chip = new ADXL343(new ADXL34xI2CTransport($bus, $int1), boot_now: true);

    $chip->close();

    expect($int1->closed)->toBeTrue()
        ->and($bus->closed)->toBeFalse();
});

it('names the sleep sampling rates as the datasheet sets them: 0b11 is 1 Hz', function (): void {
    expect(DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums\ADXL343SleepSamplingRate::from(0b11))
        ->toBe(DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums\ADXL343SleepSamplingRate::SLEEP_1HZ)
        ->and(DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345SleepSamplingRate::SLEEP_1HZ->value)->toBe(0b11);
});
