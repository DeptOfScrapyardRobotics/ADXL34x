<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support;

use GeneralPurposeIO\SPI\SPIConnectionDriver;
use GeneralPurposeIO\SPI\SPIConnectionFactory;

/** Hands out FakeSPITransports that answer from one shared script. */
final class FakeSPIConnectionDriver extends SPIConnectionDriver
{
    /** @var list<list<int>|false> replies every slave handed out from now on starts with */
    public array $replies = [];

    /** @var list<string|int> every bus connectTo() opened */
    public array $opened = [];

    protected function newConnection(int|string $device): SPIConnectionFactory
    {
        $this->opened[] = $device;

        return new FakeSPIConnectionFactory($device, $this);
    }

    protected function getTransport(int|string $device, int $chip_select): FakeSPITransport
    {
        $transport = new FakeSPITransport($chip_select);
        $transport->replies = $this->replies;

        return $transport;
    }

    protected function closeConnection(mixed $handle): void {}
}
