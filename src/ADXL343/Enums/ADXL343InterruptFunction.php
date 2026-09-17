<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums;

/** One interrupt function, backed by its bit in INT_ENABLE, INT_MAP and INT_SOURCE. */
enum ADXL343InterruptFunction: int
{
    case DATA_READY = 7;
    case SINGLE_TAP = 6;
    case DOUBLE_TAP = 5;
    case ACTIVITY = 4;
    case INACTIVITY = 3;
    case FREE_FALL = 2;
    case WATERMARK = 1;
    case OVERRUN = 0;

    /** The breakout property that carries this function's bit. */
    public function property(): string
    {
        return match ($this) {
            self::DATA_READY => 'data_ready',
            self::SINGLE_TAP => 'single_tap',
            self::DOUBLE_TAP => 'double_tap',
            self::ACTIVITY => 'activity',
            self::INACTIVITY => 'inactivity',
            self::FREE_FALL => 'free_fall',
            self::WATERMARK => 'watermark',
            self::OVERRUN => 'overrun',
        };
    }
}
