<?php

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\ADXL343;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeInterruptPin;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeSPITransport;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xSPITransport;

/*
| The ADXL34x SPI frame: the first byte is R/W (bit 7), MB for a multi-byte
| burst (bit 6), then the register (bits 5:0). A read clocks out one dummy byte
| per byte wanted; MISO carries the answer after the address byte.
*/

/** @return array{0: ADXL34xSPITransport, 1: FakeSPITransport} */
function spiFrame(array $replies = []): array
{
    $bus = new FakeSPITransport(0);
    $bus->replies = $replies;

    return [new ADXL34xSPITransport($bus), $bus];
}

it('reads one register with R/W set and MB clear, answering what follows the address byte', function (): void {
    [$transport, $bus] = spiFrame([[0xFF, 0xE5]]);

    expect($transport->read(0x00, 1))->toBe([0xE5])
        ->and($bus->transfers)->toBe([[0x80, 0x00]]);
});

it('bursts several registers with R/W and MB set', function (): void {
    [$transport, $bus] = spiFrame([[0xFF, 1, 2, 3, 4, 5, 6]]);

    expect($transport->read(0x32, 6))->toBe([1, 2, 3, 4, 5, 6])
        ->and($bus->transfers)->toBe([[0xF2, 0, 0, 0, 0, 0, 0]]);
});

it('writes one register with R/W clear, and several with MB set', function (): void {
    [$transport, $bus] = spiFrame();

    $transport->write(0x2D, [0x08]);
    $transport->write(0x1E, [1, 2, 3]);

    expect($bus->writes)->toBe([[0x2D, 0x08], [0x5E, 1, 2, 3]]);
});

it('keeps the register to six bits so it never sets R/W or MB by accident', function (): void {
    [$transport, $bus] = spiFrame([[0xFF, 0x00]]);

    $transport->read(0xFF, 1);
    $transport->write(0xFF, [0x00]);

    expect($bus->transfers)->toBe([[0xBF, 0x00]])
        ->and($bus->writes)->toBe([[0x3F, 0x00]]);
});

it('turns a refused transfer and a short answer into exceptions', function (): void {
    [$refused] = spiFrame([false]);
    [$short] = spiFrame([[0xFF, 1, 2]]);

    expect(fn () => $refused->read(0x32, 6))->toThrow(ADXL34xException::class, 'refused a 6 byte read')
        ->and(fn () => $short->read(0x32, 6))->toThrow(ADXL34xException::class, 'wanted 6 bytes, got 2');
});

it('releases its INT lines on close and leaves the chip select to the SPI driver', function (): void {
    $int1 = new FakeInterruptPin(1);
    $bus = new FakeSPITransport(0);
    $transport = new ADXL34xSPITransport($bus, $int1);

    $transport->close();

    expect($int1->closed)->toBeTrue()
        ->and($bus->released)->toBeFalse();
});

it('boots either chip over SPI and reads all three axes in one burst', function (string $class): void {
    $bus = new FakeSPITransport(0);
    $bus->replies = [...bootSPIReplies(), [0xFF, 0x10, 0x00, 0xF0, 0xFF, 0x00, 0x01]];
    $chip = new $class(new ADXL34xSPITransport($bus), boot_now: true);

    expect($chip->raw())->toBe(['x' => 16, 'y' => -16, 'z' => 256])
        ->and($bus->writes)->toBe([[0x2D, 0b0000_1000], [0x2E, 0x00]])
        ->and(array_slice($bus->transfers, BOOT_READS))->toBe([[0xF2, 0, 0, 0, 0, 0, 0]]);
})->with([
    'adxl343' => [ADXL343::class],
    'adxl345' => [ADXL345::class],
]);
