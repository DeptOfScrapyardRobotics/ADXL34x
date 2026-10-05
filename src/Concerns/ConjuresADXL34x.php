<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Concerns;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\ADXL34xI2CAddress;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\ADXL34xSPIClock;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xI2CTransport;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xSPITransport;
use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;
use GeneralPurposeIO\Contracts\SPI\SPIMode;
use Voyager\Vessel\ControlPanel;

/**
 * The i2c() and spi() protocol factories the circuit catalog calls. Their parameters are the keys of a
 * config/circuits/<chip>.php entry, so app('circuit')->conjure('adxl345') builds a wired, booted chip from the
 * app's config alone. A bus or pin device that is not connected yet is connected here; one the app already
 * connected is shared as it is.
 */
trait ConjuresADXL34x
{
    /**
     * @param  array{enabled?: bool, driver?: string, device?: string|int, pin?: int}  $int1
     * @param  array{enabled?: bool, driver?: string, device?: string|int, pin?: int}  $int2
     */
    public static function i2c(
        string $driver,
        string|int $device,
        int $slave = ADXL34xI2CAddress::SDO_GROUNDED->value,
        array $int1 = [],
        array $int2 = [],
        bool $boot_now = true,
    ): static {
        $bus = static::gpio('gpio.i2c')->driver($driver);
        $i2c = $bus->device($device, $slave) ?? $bus->connectTo($device)->register()->device($device, $slave);

        if (is_null($i2c)) {
            throw ADXL34xException::notConnected('I2C', $driver, $device);
        }

        return new static(new ADXL34xI2CTransport($i2c, static::line($int1), static::line($int2)), boot_now: $boot_now);
    }

    /**
     * Opens the bus in mode 3 (CPOL 1, CPHA 1, the only mode the chip answers in) when it is not connected yet, and clocks this chip select at $speed whatever the
     * bus runs at. A bus the app opened in another mode is refused: the chip would answer garbage.
     *
     * @param  array{enabled?: bool, driver?: string, device?: string|int, pin?: int}  $int1
     * @param  array{enabled?: bool, driver?: string, device?: string|int, pin?: int}  $int2
     */
    public static function spi(
        string $driver,
        string|int $device,
        int $chip_select = 0,
        int $speed = ADXL34xSPIClock::MAX_HZ->value,
        array $int1 = [],
        array $int2 = [],
        bool $boot_now = true,
    ): static {
        if ($speed < 1 || $speed > ADXL34xSPIClock::MAX_HZ->value) {
            throw ADXL34xException::spiClockOutOfRange($speed);
        }

        $bus = static::gpio('gpio.spi')->driver($driver);
        $spi = $bus->device($device, $chip_select)
            ?? $bus->connectTo($device)->mode(SPIMode::MODE_3)->speed($speed)->register()->device($device, $chip_select);

        if (is_null($spi)) {
            throw ADXL34xException::notConnected('SPI', $driver, $device);
        }

        $mode = $bus->settingsOf($device)?->mode;

        if (! is_null($mode) && $mode !== SPIMode::MODE_3) {
            throw ADXL34xException::wrongSpiMode($device, $mode->value);
        }

        $spi->speed($speed);

        return new static(new ADXL34xSPITransport($spi, static::line($int1), static::line($int2)), boot_now: $boot_now);
    }

    /**
     * One INT line, or null when its config is not enabled. The bus is connected first, so a pin on an FT232H
     * rides the bus's own context.
     *
     * @param  array{enabled?: bool, driver?: string, device?: string|int, pin?: int}  $line
     */
    protected static function line(array $line): ?DigitalInTransport
    {
        if (! ($line['enabled'] ?? false)) {
            return null;
        }

        $pins = static::gpio('gpio.digital')->driver($line['driver']);
        $pin = $pins->input($line['device'], $line['pin'])
            ?? $pins->connectTo($line['device'])->register()->input($line['device'], $line['pin']);

        return $pin ?? throw ADXL34xException::notConnected('DigitalIO', $line['driver'], $line['device']);
    }

    /** A protocol manager from the app's container: gpio.i2c, gpio.spi or gpio.digital. */
    protected static function gpio(string $manager): mixed
    {
        return ControlPanel::getInstance()->make($manager);
    }
}
