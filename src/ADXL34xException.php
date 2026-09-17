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
}
