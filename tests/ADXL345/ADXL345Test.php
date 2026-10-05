<?php

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345DataFormat;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345PowerControl;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345Range;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\CelestialBody;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeI2CTransport;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeInterruptPin;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xI2CTransport;
use GeneralPurposeIO\Contracts\IntegratedCircuits\Sensor;

const DEVID = 0x00;
const POWER_CTL = 0x2D;
const INT_ENABLE = 0x2E;
const DATA_FORMAT = 0x31;
const DATAX0 = 0x32;

/** @return array{0: ADXL345, 1: FakeI2CTransport} an unbooted chip over a scripted bus */
function adxl(array $replies = []): array
{
    $bus = new FakeI2CTransport;
    $bus->replies = $replies;

    return [new ADXL345(new ADXL34xI2CTransport($bus)), $bus];
}

/** @return array{0: ADXL345, 1: FakeI2CTransport} a booted chip, boot replies consumed */
function bootedAdxl(array $replies = []): array
{
    [$chip, $bus] = adxl([...bootI2CReplies(), ...$replies]);
    $chip->boot();

    return [$chip, $bus];
}

it('is a bootable sensor that exposes its transport', function (): void {
    [$chip] = adxl();

    expect($chip)->toBeInstanceOf(Sensor::class)
        ->and($chip->hasBooted())->toBeFalse()
        ->and($chip->transport())->toBeInstanceOf(ADXL34xI2CTransport::class);
});

it('boots by confirming 0xE5, entering measurement mode, waiting for the first sample, and applying the interrupt mask', function (): void {
    [$chip, $bus] = adxl(bootI2CReplies());

    $chip->boot();

    expect($chip->hasBooted())->toBeTrue()
        ->and($bus->write_reads)->toBe([[[DEVID], 1], [[DATAX0], 6], [[0x2C], 1], [[0x30], 1]])
        ->and($bus->writes)->toBe([[POWER_CTL, 0b0000_1000], [INT_ENABLE, 0x00]]);
});

it('keeps reading INT_SOURCE through boot until DATA_READY reports a fresh sample', function (): void {
    [$chip, $bus] = adxl([[0xE5], [0, 0, 0, 0, 0, 0], [0x0A], [0x00], [0x02], [0x80]]);

    $chip->boot();

    expect(array_map(fn (array $wr): int => $wr[0][0], $bus->write_reads))->toBe([0x00, 0x32, 0x2C, 0x30, 0x30, 0x30]);
});

it('gives up on boot after two sample periods without DATA_READY', function (): void {
    [$chip] = adxl([[0xE5], [0, 0, 0, 0, 0, 0], [0x0F], ...array_fill(0, 10_000, [0x00])]);

    expect(fn () => $chip->boot())->toThrow(ADXL34xException::class, 'no sample within 2 ms of entering measurement mode at 3200');
});

it('refuses a chip that does not answer 0xE5', function (): void {
    [$chip] = adxl([[0x00]]);

    expect(fn () => $chip->boot())->toThrow(ADXL34xException::class, 'expected 229, got 0');
});

it('reads all three axes in one six-byte transaction, little-endian signed', function (): void {
    [$chip, $bus] = bootedAdxl([[0x10, 0x00, 0xF0, 0xFF, 0x00, 0x01]]);

    $raw = $chip->raw();

    expect($raw)->toBe(['x' => 16, 'y' => -16, 'z' => 256])
        ->and(array_slice($bus->write_reads, BOOT_READS))->toBe([[[DATAX0], 6]]);
});

it('reads the DATA_FORMAT register as a breakout, including range and resolution', function (): void {
    [$chip] = bootedAdxl([[0b0000_1010]]);

    $format = $chip->getDataFormat();

    expect($format)->toBeInstanceOf(ADXL345DataFormat::class)
        ->and($format->full_resolution)->toBeTrue()
        ->and($format->range)->toBe(ADXL345Range::G8)
        ->and($format->self_test)->toBeFalse()
        ->and($format->toByte())->toBe(0b0000_1010);
});

it('changes the range without disturbing the other DATA_FORMAT bits', function (): void {
    [$chip, $bus] = bootedAdxl([[0b0010_1000]]);

    $chip->setRange(ADXL345Range::G16);

    expect(end($bus->writes))->toBe([DATA_FORMAT, 0b0010_1011]);
});

it('switches resolution without disturbing the range', function (): void {
    [$chip, $bus] = bootedAdxl([[0b0000_0010], [0b0000_1010]]);

    $chip->setResolution(true);
    $chip->setResolution(false);

    expect(array_slice($bus->writes, 2))->toBe([[DATA_FORMAT, 0b0000_1010], [DATA_FORMAT, 0b0000_0010]]);
});

it('scales at the range step in 10-bit mode and at 3.9 mg per LSB in full-resolution mode', function (): void {
    [$ten_bit_16g] = bootedAdxl([[0b0000_0011]]);
    [$full_res_16g] = bootedAdxl([[0b0000_1011]]);
    [$ten_bit_2g] = bootedAdxl([[0b0000_0000]]);

    expect($ten_bit_16g->scale())->toBe(ADXL345Range::G16->scale())
        ->and($full_res_16g->scale())->toBe(ADXL345Range::G2->scale())
        ->and($ten_bit_2g->scale())->toBe(ADXL345Range::G2->scale());
});

it('converts an axis from raw counts through the effective scale to metres per second squared', function (): void {
    [$chip] = bootedAdxl([[0b0000_0000], [0x00, 0x01, 0x00, 0x00, 0x00, 0x00]]);

    expect($chip->x())->toEqualWithDelta(256 * ADXL345Range::G2->scale() * CelestialBody::TERRA->gravity(), 1e-9);
});

it('reads all three accelerations from one transaction', function (): void {
    [$chip, $bus] = bootedAdxl([[0b0000_0011], [0x00, 0x01, 0x00, 0x02, 0x00, 0x03]]);

    $g = CelestialBody::TERRA->gravity();
    $scale = ADXL345Range::G16->scale();

    $acceleration = $chip->acceleration();

    expect($acceleration['x'])->toEqualWithDelta(256 * $scale * $g, 1e-9)
        ->and($acceleration['y'])->toEqualWithDelta(512 * $scale * $g, 1e-9)
        ->and($acceleration['z'])->toEqualWithDelta(768 * $scale * $g, 1e-9)
        ->and(array_slice($bus->write_reads, BOOT_READS))->toBe([[[DATA_FORMAT], 1], [[DATAX0], 6]]);
});

it('exposes the chip through properties, and refuses ones it does not have', function (): void {
    [$chip] = bootedAdxl([[0b0001_0000], [0b0000_1000]]);

    $power = $chip->power_control;

    expect($power)->toBeInstanceOf(ADXL345PowerControl::class)
        ->and($power->link)->toBeTrue()
        ->and($chip->full_resolution)->toBeTrue()
        ->and(fn () => $chip->nope)->toThrow(ADXL34xException::class, "Invalid property 'nope'");
});

it('turns a refused bus read into an exception instead of a type error', function (): void {
    [$chip] = bootedAdxl([false]);

    expect(fn () => $chip->raw())->toThrow(ADXL34xException::class, 'register 0x32');
});

it('turns a short bus read into an exception', function (): void {
    [$chip] = bootedAdxl([[0x00, 0x01]]);

    expect(fn () => $chip->raw())->toThrow(ADXL34xException::class, 'wanted 6 bytes, got 2');
});

it('releases its interrupt pins on close and leaves the bus connection to its driver', function (): void {
    $bus = new FakeI2CTransport;
    $bus->replies = bootI2CReplies();
    $int1 = new FakeInterruptPin(4);
    $chip = new ADXL345(new ADXL34xI2CTransport($bus, $int1), boot_now: true);

    $chip->close();

    expect($int1->closed)->toBeTrue()
        ->and($bus->closed)->toBeFalse();
});
