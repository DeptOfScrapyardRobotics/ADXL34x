<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support;

use GeneralPurposeIO\I2C\I2CConnectionDriver;
use GeneralPurposeIO\I2C\I2CConnectionFactory;

/** Hands out FakeI2CTransports that answer from one shared script. */
final class FakeI2CConnectionDriver extends I2CConnectionDriver
{
    /** @var list<list<int>|false> replies every slave handed out from now on starts with */
    public array $replies = [];

    /** @var list<string|int> every bus connectTo() opened */
    public array $opened = [];

    protected function newConnection(int|string $device): I2CConnectionFactory
    {
        $this->opened[] = $device;

        return new FakeI2CConnectionFactory($device, $this);
    }

    protected function getTransport(string|int $device, int $slave_address): FakeI2CTransport
    {
        $transport = new FakeI2CTransport($slave_address);
        $transport->replies = $this->replies;

        return $transport;
    }

    protected function closeConnection(mixed $handle): void {}
}
