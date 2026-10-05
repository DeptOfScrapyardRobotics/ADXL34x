<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support;

use GeneralPurposeIO\I2C\I2CConnectionFactory;

final class FakeI2CConnectionFactory extends I2CConnectionFactory
{
    protected function device(): string|int
    {
        return $this->device;
    }

    protected function getHandle(): string
    {
        return "i2c:{$this->device}";
    }
}
