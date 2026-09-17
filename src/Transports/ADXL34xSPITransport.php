<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use GeneralPurposeIO\Contracts\SPI\SPITransport;
use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;

class ADXL34xSPITransport extends ADXL34xDataTransport
{
    public function __construct(
        protected SPITransport $transport,
        ?DigitalInTransport $int1 = null,
        ?DigitalInTransport $int2 = null,
    ) {
        parent::__construct($int1, $int2);
    }

    protected function getData(int $register, int $length): array
    {
        $addr_byte = 0x80 | ($register & 0x3F);
        if ($length > 1) {
            $addr_byte |= 0x40;
        }

        $tx = array_merge([$addr_byte], array_fill(0, $length, 0x00));
        $rx = $this->transport->transfer($tx);

        if ($rx === false) {
            throw ADXL34xException::readFailed($register, $length);
        }

        $bytes = array_slice(array_values($rx), 1, $length);

        if (count($bytes) < $length) {
            throw ADXL34xException::shortRead($register, $length, count($bytes));
        }

        return $bytes;
    }

    protected function sendData(int $register, array $data): int
    {
        $addr_byte = $register & 0x3F;
        if (count($data) > 1) {
            $addr_byte |= 0x40;
        }
        $payload = [$addr_byte, ...$data];

        return $this->transport->write($payload);
    }

    /** Nothing to release: the chip-select transport is a view on a connection the SPI driver owns. */
    protected function closeMain(): void
    {
        //
    }
}