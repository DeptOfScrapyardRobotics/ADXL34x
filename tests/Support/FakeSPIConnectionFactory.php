<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support;

use GeneralPurposeIO\SPI\SPIConnectionFactory;

final class FakeSPIConnectionFactory extends SPIConnectionFactory
{
    public function chipSelect(int $chip_select): static
    {
        return $this;
    }

    protected function device(): string|int
    {
        return $this->device;
    }

    public function getHandle(): string
    {
        return "spi:{$this->device}";
    }
}
