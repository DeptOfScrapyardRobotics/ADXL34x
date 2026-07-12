<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL345\Enums;

/**
 * Measurement range — stored in DATA_FORMAT register bits [1:0].
 *
 * The scale factor (g per LSB) applies in fixed-resolution mode (10-bit).
 * In full-resolution mode the scale is always 3.9 mg/LSB regardless of range.
 */
enum ADXL345Range: int
{
    /** ±2 g  — 3.9 mg/LSB */
    case G2 = 0x00;

    /** ±4 g  — 7.8 mg/LSB */
    case G4 = 0x01;

    /** ±8 g  — 15.6 mg/LSB */
    case G8 = 0x02;

    /** ±16 g — 31.2 mg/LSB */
    case G16 = 0x03;

    /**
     * Scale factor in g/LSB for fixed-resolution (10-bit) mode.
     *
     * In full-resolution mode the sensor always returns 3.9 mg/LSB;
     * the main class uses this method only when FULL_RES = 0.
     */
    public function scale(): float
    {
        return match ($this) {
            self::G2 => 0.0039,
            self::G4 => 0.0078,
            self::G8 => 0.0156,
            self::G16 => 0.0313,
        };
    }

    /** Human-readable range label. */
    public function label(): string
    {
        return match ($this) {
            self::G2 => '±2g',
            self::G4 => '±4g',
            self::G8 => '±8g',
            self::G16 => '±16g',
        };
    }
}
