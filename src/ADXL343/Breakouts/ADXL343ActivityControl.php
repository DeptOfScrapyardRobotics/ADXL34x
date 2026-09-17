<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Breakouts;

use GeneralPurposeIO\IntegratedCircuits\DataRegister;

/**
 * ACT_INACT_CTL (0x27). Bit 7 activity ac/dc, 6:4 activity X/Y/Z enable,
 * bit 3 inactivity ac/dc, 2:0 inactivity X/Y/Z enable. ac = compare against
 * the reading when detection started; dc = compare against zero.
 */
readonly class ADXL343ActivityControl extends DataRegister
{
    public function __construct(
        public bool $activity_ac = false,
        public bool $activity_x = false,
        public bool $activity_y = false,
        public bool $activity_z = false,
        public bool $inactivity_ac = false,
        public bool $inactivity_x = false,
        public bool $inactivity_y = false,
        public bool $inactivity_z = false,
    ) {}

    public function toBits(): string
    {
        return implode('', array_map(fn (bool $bit): string => $bit ? '1' : '0', [
            $this->activity_ac,
            $this->activity_x,
            $this->activity_y,
            $this->activity_z,
            $this->inactivity_ac,
            $this->inactivity_x,
            $this->inactivity_y,
            $this->inactivity_z,
        ]));
    }

    public static function fromByte(int $byte): static
    {
        $bits = byte2bits($byte);

        return new static(
            (bool) $bits[7],
            (bool) $bits[6],
            (bool) $bits[5],
            (bool) $bits[4],
            (bool) $bits[3],
            (bool) $bits[2],
            (bool) $bits[1],
            (bool) $bits[0],
        );
    }

    public static function none(): static
    {
        return new static;
    }
}
