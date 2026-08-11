<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x;

use GeneralPurposeIO\Contracts\Circuits\CircuitException;

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
}