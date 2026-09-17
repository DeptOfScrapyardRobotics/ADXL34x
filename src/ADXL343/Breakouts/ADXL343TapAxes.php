<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Breakouts;

use GeneralPurposeIO\IntegratedCircuits\DataRegister;

/** TAP_AXES (0x2A). Bit 3 Suppress (double tap), bits 2:0 TAP_X / TAP_Y / TAP_Z enable. */
readonly class ADXL343TapAxes extends DataRegister
{
    public function __construct(
        public bool $suppress = false,
        public bool $x = false,
        public bool $y = false,
        public bool $z = false,
    ) {}

    public function toBits(): string
    {
        $bit3 = $this->suppress ? '1' : '0';
        $bit2 = $this->x ? '1' : '0';
        $bit1 = $this->y ? '1' : '0';
        $bit0 = $this->z ? '1' : '0';

        return "0000{$bit3}{$bit2}{$bit1}{$bit0}";
    }

    public static function fromByte(int $byte): static
    {
        $bits = byte2bits($byte);

        return new static((bool) $bits[3], (bool) $bits[2], (bool) $bits[1], (bool) $bits[0]);
    }

    public static function none(): static
    {
        return new static;
    }
}
