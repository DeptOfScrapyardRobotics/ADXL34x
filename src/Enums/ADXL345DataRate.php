<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL345\Enums;

/**
 * Output data rate — stored in BW_RATE register bits [3:0].
 *
 * Values below HZ6p25 enable low-power mode automatically in the chip;
 * these are still valid but draw more standby current in some configurations.
 */
enum ADXL345DataRate: int
{
    case HZ3200 = 0x0F;
    case HZ1600 = 0x0E;
    case HZ800 = 0x0D;
    case HZ400 = 0x0C;
    case HZ200 = 0x0B;
    /** Default power-on data rate */
    case HZ100 = 0x0A;
    case HZ50 = 0x09;
    case HZ25 = 0x08;
    case HZ12p5 = 0x07;
    case HZ6p25 = 0x06;
    case HZ3p13 = 0x05;
    case HZ1p56 = 0x04;
    case HZ0p78 = 0x03;
    case HZ0p39 = 0x02;
    case HZ0p20 = 0x01;
    case HZ0p10 = 0x00;

    /** Nominal output data rate in HZ. */
    public function hz(): float
    {
        return match ($this) {
            self::HZ3200 => 3200.0,
            self::HZ1600 => 1600.0,
            self::HZ800 => 800.0,
            self::HZ400 => 400.0,
            self::HZ200 => 200.0,
            self::HZ100 => 100.0,
            self::HZ50 => 50.0,
            self::HZ25 => 25.0,
            self::HZ12p5 => 12.5,
            self::HZ6p25 => 6.25,
            self::HZ3p13 => 3.13,
            self::HZ1p56 => 1.56,
            self::HZ0p78 => 0.78,
            self::HZ0p39 => 0.39,
            self::HZ0p20 => 0.20,
            self::HZ0p10 => 0.10,
        };
    }
}
