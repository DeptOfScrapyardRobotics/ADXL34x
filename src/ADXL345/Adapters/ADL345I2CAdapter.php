<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Adapters;

use Waveforms\Carriers\I2C\I2CDevice;

class ADL345I2CAdapter extends ADXL345DataCarrier
{
    public function __construct(
        I2CDevice $carrier
    ) {
        parent::__construct($carrier);
    }

    public function read(int $register_hex, int $length): array
    {
        /** @var I2CDevice $carrier */
        $carrier = &$this->carrier;

        return $carrier->readWrite([$register_hex & 0xFF], $length);
    }

    public function write(int $register_hex, array $command_data = []): int
    {
        $payload = [$register_hex & 0xFF, ...$command_data];

        return $this->carrier->write($payload);
    }
}
