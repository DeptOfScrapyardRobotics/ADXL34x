<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Concerns;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345OpCode;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345ReadRegister;

trait ADXL345InternalAPI
{
    protected function s16le(int $lsb, int $msb): int
    {
        $value = (($msb & 0xFF) << 8) | ($lsb & 0xFF);

        return ($value & 0x8000) ? $value - 0x10000 : $value;
    }

    protected function write(ADXL345OpCode $register_hex, array $command_data = []): ?int
    {
        return $this->carrier->write($register_hex->value, $command_data);
    }

    protected function read(ADXL345ReadRegister $register_hex, int $length): array
    {
        return $this->carrier->read($register_hex->value, $length);
    }
}
