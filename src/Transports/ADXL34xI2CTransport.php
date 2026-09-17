<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use GeneralPurposeIO\Contracts\I2C\I2CTransport;
use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;

class ADXL34xI2CTransport extends ADXL34xDataTransport
{
    public function __construct(
        protected I2CTransport $transport,
        ?DigitalInTransport $int1 = null,
        ?DigitalInTransport $int2 = null,
    ) {
        parent::__construct($int1, $int2);
    }

    protected function getData(int $register, int $length): array
    {
        $bytes = $this->transport->writeRead([$this->getLowByte($register)], $length);

        if ($bytes === false) {
            throw ADXL34xException::readFailed($register, $length);
        }

        if (count($bytes) < $length) {
            throw ADXL34xException::shortRead($register, $length, count($bytes));
        }

        return array_values($bytes);
    }

    protected function sendData(int $register, array $data): int
    {
        $payload = [$this->getLowByte($register), ...$data];

        return $this->transport->write($payload);
    }

    /** Nothing to release: the slave transport is a view on a connection the I2C driver owns. */
    protected function closeMain(): void
    {
        //
    }
}