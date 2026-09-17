<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Breakouts;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums\ADXL343InterruptFunction;
use GeneralPurposeIO\IntegratedCircuits\DataRegister;

/** INT_MAP (0x2F). A set bit routes that function to INT2; a clear bit routes it to INT1. */
readonly class ADXL343InterruptMap extends DataRegister
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

    /** 1 for INT1, 2 for INT2. */
    public function pinFor(ADXL343InterruptFunction $function): int
    {
        return $this->{$function->property()} ? 2 : 1;
    }

    public function toBits(): string
    {
        return implode('', array_map(
            fn (ADXL343InterruptFunction $function): string => $this->{$function->property()} ? '1' : '0',
            ADXL343InterruptFunction::cases(),
        ));
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

    /** Every function on INT1: the power-on value. */
    public static function none(): static
    {
        return new static;
    }
}
