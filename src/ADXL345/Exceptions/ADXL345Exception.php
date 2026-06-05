<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Exceptions;

use Exception;

class ADXL345Exception extends Exception
{
    public static function invalidProperty(string $name): static
    {
        return new static("Invalid property $name");
    }

    public static function invalidChipId(int $chip_id): static
    {
        return new static(sprintf('Invalid ADXL345 Chip ID — expected 0xE5, got 0x%02X', $chip_id));
    }
}
