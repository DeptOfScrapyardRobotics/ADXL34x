<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL345;

trait ADXL345IO
{
    /**
     * @throws ADXL345Exception
     */
    protected function spiRead(int $register, int $length): array
    {
        if(!is_null($this->spi)) {
            $addr_byte = 0x80 | ($register & 0x3F);
            if ($length > 1) {
                $addr_byte |= 0x40;
            }

            $tx = array_merge([$addr_byte], array_fill(0, $length, 0x00));
            $rx = $this->spi->transfer($tx);

            return array_slice($rx, 1, $length);
        }

        throw ADXL345Exception::transportMissingProtocol();
    }

    /**
     * @throws ADXL345Exception
     */
    protected function spiWrite(int $register, array $data = []): int
    {
        if(!is_null($this->spi)) {

            $addr_byte = $register & 0x3F;
            if (count($data) > 1) {
                $addr_byte |= 0x40;
            }
            $payload = [$addr_byte, ...$data];

            return $this->spi->write($payload);
        }

        throw ADXL345Exception::transportMissingProtocol();
    }

    /**
     * @throws ADXL345Exception
     */
    protected function i2cRead(int $register, int $length): array
    {
        if(!is_null($this->i2c)) {
            return $this->i2c->writeRead([$this->getLowByte($register)], $length);
        }

        throw ADXL345Exception::transportMissingProtocol();
    }

    /**
     * @throws ADXL345Exception
     */
    protected function i2cWrite(int $register, array $data = []): int
    {
        if(!is_null($this->i2c)) {
            $payload = [$this->getLowByte($register), ...$data];

            return $this->i2c->write($payload);
        }

        throw ADXL345Exception::transportMissingProtocol();
    }
}
