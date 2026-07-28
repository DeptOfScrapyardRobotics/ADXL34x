<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Concerns;

use Fabricate\NutsAndBolts\Concerns\Splices16Bits;
use Fabricate\Contracts\Sensors\Enums\CelestialBody;
use Fabricate\Contracts\NutsAndBolts\BootScaffolding;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345OpCode;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345PowerControl;

trait ADXL345InternalAPI
{
    use BootScaffolding, Splices16Bits;

    protected int $hardwired_device_id = 0xE5;

    protected function sendCommand(ADXL345OpCode $register, array $command_data = []): int
    {
        return $this->transport->write($register->value, $command_data);
    }

    protected function readData(ADXL345OpCode $register, int $length): array
    {
        return $this->transport->read($register->value, $length);
    }

    /**
     * @throws ADXL34xException
     */
    protected function _boot(): void
    {
        $this->confirmDeviceId();
        $this->setupPowerControl();
        $this->setInterruptPinFunctions();
    }

    /**
     * @throws ADXL34xException
     */
    protected function confirmDeviceId(): void
    {
        if ($this->device_id != $this->hardwired_device_id) {
            throw ADXL34xException::invalidChipId($this->device_id, $this->hardwired_device_id);
        }
    }

    protected function setupPowerControl(): void
    {
        $this->power_control = new ADXL345PowerControl(measurement_mode: true, sleep_mode: false);
    }

    protected function setInterruptPinFunctions(): void
    {
        $this->active_interrupts = $this->_starting_int_fns;
    }

    protected function calcADXL(int $value): float
    {
        return $value * $this->getRange()->scale() * CelestialBody::TERRA->gravity();
    }
}