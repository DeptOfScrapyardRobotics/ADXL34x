<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums;

/** POWER_CTL wakeup bits D1:D0: how often the chip samples while asleep, per the datasheet. */
enum ADXL345SleepSamplingRate: int
{
    case SLEEP_8HZ = 0b00;
    case SLEEP_4HZ = 0b01;
    case SLEEP_2HZ = 0b10;
    case SLEEP_1HZ = 0b11;

    public function toBits(): string
    {
        return match ($this) {
            ADXL345SleepSamplingRate::SLEEP_8HZ => '00',
            ADXL345SleepSamplingRate::SLEEP_4HZ => '01',
            ADXL345SleepSamplingRate::SLEEP_2HZ => '10',
            ADXL345SleepSamplingRate::SLEEP_1HZ => '11',
        };
    }
}
