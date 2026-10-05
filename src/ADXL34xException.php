<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x;

use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException;

class ADXL34xException extends CircuitException
{
    public static function transportMissingProtocol(): static
    {
        return new static("ADXL34x devices require an SPI or an I2C capable connection.");
    }

    public static function invalidChipId(int $chip_id, int $expected_id): static
    {
        return new static("Invalid ADXL34x Device Chip ID — expected {$expected_id}, got {$chip_id}");
    }

    public static function invalidProperty(string $name, string $class): static
    {
        return new static("Invalid property '{$name}' on {$class}.");
    }

    public static function readFailed(int $register, int $length): static
    {
        return new static(sprintf('ADXL34x register 0x%02X: the bus refused a %d byte read.', $register, $length));
    }

    public static function shortRead(int $register, int $wanted, int $got): static
    {
        return new static(sprintf('ADXL34x register 0x%02X: wanted %d bytes, got %d.', $register, $wanted, $got));
    }

    public static function registerOutOfRange(string $name, int $value): static
    {
        return new static("ADXL34x {$name} takes 0 to 255; got {$value}.");
    }

    public static function invalidInterruptPin(int $pin): static
    {
        return new static("ADXL34x has INT1 and INT2; there is no INT{$pin}.");
    }

    public static function noFirstSample(float $rate_hz, int $waited_ms): static
    {
        return new static("ADXL34x took no sample within {$waited_ms} ms of entering measurement mode at {$rate_hz} Hz.");
    }

    public static function notConnected(string $protocol, string $driver, string|int $device): static
    {
        return new static("ADXL34x: the {$driver} {$protocol} driver could not connect device {$device}.");
    }

    public static function spiClockOutOfRange(int $hz): static
    {
        return new static("ADXL34x SPI runs at 1 Hz to 5 MHz; got {$hz} Hz.");
    }

    public static function wrongSpiMode(string|int $device, int $mode): static
    {
        return new static("ADXL34x needs SPI mode 3, but bus {$device} was opened in mode {$mode}.");
    }
}
