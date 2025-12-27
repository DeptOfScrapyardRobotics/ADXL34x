<?php

namespace ScrapyardIO\Sensors\Accelerometers\ADXL345\Concerns;

use ScrapyardIO\Sensors\Accelerometers\ADXL345\Enums\ADXL345Command;
use ScrapyardIO\Sensors\Accelerometers\ADXL345\Exceptions\ADXL345Exception;
use ScrapyardIO\Support\DataManipulation\ByteRegister;

trait ADXL345BootSequence
{
    // Power Control Register
    protected int $wakeup_rate = 8;
    protected bool $measure_mode_standby = true;
    protected bool $auto_sleep_enabled = true;
    protected bool $link_mode_dependant = false;

    // Data Format
    protected int $full_scale_range = 2;
    protected bool $left_justified = true;
    protected int $resolution_bits = 13;
    protected bool $high_interrupt_invert = true;
    protected int $spi_wires = 4;
    protected bool $self_test_enabled = false;

    /**
     * @return void
     * @throws ADXL345Exception
     */
    public function readDeviceId(): void
    {
        [$device_id] = $this->readData(ADXL345Command::DEVICE_ID_REGISTER->value, 1);
        if($device_id != 0xE5) throw ADXL345Exception::invalidDeviceID($device_id, 0xE5);
    }

    public function setDataFormat(): void
    {
        $this->sendCommand([ADXL345Command::DATA_FORMAT_REGISTER->value, $this->dataFormat()]);
    }

    public function setPowerControl(): void
    {
        $this->sendCommand([ADXL345Command::POWER_CTRL_REGISTER->value, $this->powerControl()]);
    }

    public function dataFormat(): int
    {
        $register = (new ByteRegister(0))
            ->update(7, $this->self_test_enabled)
            ->update(6, $this->spi_wires != 4)
            ->update(5, !$this->high_interrupt_invert)
            ->update(4, 0)
            ->update(3, $this->resolution_bits == 13)
            ->update(2, $this->left_justified);
        $register = match ($this->full_scale_range) {
            4 => $register->update(1, 0)
                ->update(0, 1),
            8 => $register->update(1, 1)
                ->update(0, 0),
            16 => $register->update(1, 1)
                ->update(0, 1),
            default => $register->update(1, 0)
                ->update(0, 0),
        };

        return $register->byte;
    }

    protected function powerControl() : int
    {
        $register = (new ByteRegister(0))
            ->update(7, 0)
            ->update(6, 0)
            ->update(5, 0)
            ->update(4, $this->link_mode_dependant)
            ->update(3, $this->auto_sleep_enabled)
            ->update(2, !$this->measure_mode_standby);

        $register = match ($this->wakeup_rate) {
            8 => $register->update(1, 0)
                ->update(0, 0),
            4 => $register->update(1, 0)
                ->update(0, 1),
            2 => $register->update(1, 1)
                ->update(0, 0),
            default => $register->update(1, 1)
                ->update(0, 1),
        };

        return $register->byte;
    }

    protected function convertTo16Bit(int $low, int $high): int
    {
        // ADXL345 outputs data as signed 16-bit little-endian
        $result = unpack('s', pack('v', ($high << 8) | $low))[1];

        // When left-justified (JUSTIFY bit = 1), data needs to be shifted right
        if($this->left_justified)
        {
            if($this->resolution_bits == 13)
            {
                // 13-bit full-res mode: shift right 3 bits (16 - 13 = 3)
                $result >>= 3;
            }
            elseif($this->resolution_bits == 10)
            {
                // 10-bit mode: shift right 6 bits (16 - 10 = 6)
                $result >>= 6;
            }
        }
        // When right-justified (JUSTIFY bit = 0):
        // For 10-bit mode, data is already correct, no shift needed
        // For 13-bit mode... there's still an issue here

        return $result;
    }

    public function resoBits(): int
    {
        return $this->resolution_bits;
    }

    public function getFSR(): int
    {
        return $this->full_scale_range;
    }
}
