<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL345;

use BareMetal\Contracts\Sensors\SensorException;

class ADXL345Exception extends SensorException
{
    public static function transportMissingProtocol(): static
    {
        return new static("ADXL345 requires an SPI or an I2C capable connection.");
    }

    public static function invalidChipId(int $chip_id): static
    {
        return new static(sprintf('Invalid ADXL345 Chip ID — expected 0xE5, got 0x%02X', $chip_id));
    }
}
