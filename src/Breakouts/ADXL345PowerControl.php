<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL345\Breakouts;

use BareMetal\Circuits\DataRegister;
use DeptOfScrapyardRobotics\Sensors\ADXL345\Enums\ADXL345SleepSamplingRate;

readonly class ADXL345PowerControl extends DataRegister
{
    public function __construct(
        public bool $link = false,
        public bool $measurement_mode = false,
        public bool $sleep_mode = true,
        public ADXL345SleepSamplingRate $wakeup = ADXL345SleepSamplingRate::SLEEP_8HZ,
    ) {}

    public function toBits(): string
    {
        $bits765 = '000';
        $bit4 = $this->link ? '1' : '0';
        $bit3 = $this->measurement_mode ? '1' : '0';
        $bit2 = $this->sleep_mode ? '1' : '0';
        $bits10 = $this->wakeup->toBits();

        return "{$bits765}{$bit4}{$bit3}{$bit2}{$bits10}";
    }

    public static function fromByte(int $byte): static
    {
        $bits = byte2bits($byte);

        $bits10 = bindec("{$bits[1]}{$bits[0]}");

        return new static(
            $bits[4],
            $bits[3],
            $bits[2],
            ADXL345SleepSamplingRate::from($bits10)
        );
    }

    public static function none(): static
    {
        return new static(
            false,
            false,
            false,
            ADXL345SleepSamplingRate::SLEEP_8HZ
        );
    }
}
