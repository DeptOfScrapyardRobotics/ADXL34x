<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\DataObjects;

use BareMetal\DataObjects\DataRegister;

readonly class ADXL345InterruptFunctions extends DataRegister
{
    public function __construct(
        public bool $data_ready = false,
        public bool $single_tap = false,
        public bool $double_tap = false,
        public bool $activity = false,
        public bool $inactivity = false,
        public bool $free_fall = false,
        public bool $watermark = false,
        public bool $overrun = false,
    ) {}

    public function toBits(): string
    {
        $bit7 = $this->data_ready ? '1' : '0';
        $bit6 = $this->single_tap ? '1' : '0';
        $bit5 = $this->double_tap ? '1' : '0';
        $bit4 = $this->activity ? '1' : '0';
        $bit3 = $this->inactivity ? '1' : '0';
        $bit2 = $this->free_fall ? '1' : '0';
        $bit1 = $this->watermark ? '1' : '0';
        $bit0 = $this->overrun ? '1' : '0';

        return "{$bit7}{$bit6}{$bit5}{$bit4}{$bit3}{$bit2}{$bit1}{$bit0}";
    }

    public static function fromByte(int $byte): static
    {
        $bits = byte2bits($byte);

        return new static(
            $bits[7],
            $bits[6],
            $bits[5],
            $bits[4],
            $bits[3],
            $bits[2],
            $bits[1],
            $bits[0],
        );
    }

    public static function none(): static
    {
        return new static(
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
        );
    }
}
