<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums;

enum ADXL343SleepSamplingRate: int
{
    case SLEEP_8HZ = 0b00;
    case SLEEP_4HZ = 0b01;
    case SLEEP_2HZ = 0b10;
    case SLEEP_3HZ = 0b11;

    public function toBits(): string
    {
        return match ($this) {
            ADXL343SleepSamplingRate::SLEEP_8HZ => '00',
            ADXL343SleepSamplingRate::SLEEP_4HZ => '01',
            ADXL343SleepSamplingRate::SLEEP_2HZ => '10',
            ADXL343SleepSamplingRate::SLEEP_3HZ => '11',
        };
    }
}
