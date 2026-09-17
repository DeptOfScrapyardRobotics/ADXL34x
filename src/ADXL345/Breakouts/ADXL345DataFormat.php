<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345Range;
use GeneralPurposeIO\IntegratedCircuits\DataRegister;

/**
 * DATA_FORMAT (0x31). Bit 7 SELF_TEST, 6 SPI (3-wire), 5 INT_INVERT,
 * 4 reserved, 3 FULL_RES, 2 Justify (left, MSB), 1:0 Range.
 *
 * In full-resolution mode the LSB stays 3.9 mg whatever the range; in
 * 10-bit mode the LSB grows with the range. scale() on the chip follows.
 */
readonly class ADXL345DataFormat extends DataRegister
{
    public function __construct(
        public bool $self_test = false,
        public bool $spi_3wire = false,
        public bool $int_invert = false,
        public bool $full_resolution = false,
        public bool $justify_left = false,
        public ADXL345Range $range = ADXL345Range::G2,
    ) {}

    public function toBits(): string
    {
        $bit7 = $this->self_test ? '1' : '0';
        $bit6 = $this->spi_3wire ? '1' : '0';
        $bit5 = $this->int_invert ? '1' : '0';
        $bit4 = '0';
        $bit3 = $this->full_resolution ? '1' : '0';
        $bit2 = $this->justify_left ? '1' : '0';
        $bits10 = str_pad(decbin($this->range->value), 2, '0', STR_PAD_LEFT);

        return "{$bit7}{$bit6}{$bit5}{$bit4}{$bit3}{$bit2}{$bits10}";
    }

    public static function fromByte(int $byte): static
    {
        $bits = byte2bits($byte);

        return new static(
            (bool) $bits[7],
            (bool) $bits[6],
            (bool) $bits[5],
            (bool) $bits[3],
            (bool) $bits[2],
            ADXL345Range::from(bindec("{$bits[1]}{$bits[0]}")),
        );
    }

    public static function none(): static
    {
        return new static;
    }

    public function withRange(ADXL345Range $range): static
    {
        return new static($this->self_test, $this->spi_3wire, $this->int_invert, $this->full_resolution, $this->justify_left, $range);
    }

    public function withIntInvert(bool $active_low): static
    {
        return new static($this->self_test, $this->spi_3wire, $active_low, $this->full_resolution, $this->justify_left, $this->range);
    }

    public function withResolution(bool $full): static
    {
        return new static($this->self_test, $this->spi_3wire, $this->int_invert, $full, $this->justify_left, $this->range);
    }
}
