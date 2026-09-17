<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Concerns;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345PowerControl;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\CelestialBody;

trait ADXL345Bootsrap
{
    use ADXL345API;

    /**
     * @throws ADXL34xException
     */
    public function __get(string $name): mixed
    {
        return match($name) {
            'device_id' => $this->getDeviceId(),
            'power_control' => $this->getPowerControl(),
            'link_mode' => $this->getLinkMode(),
            'measurement_mode' => $this->getMeasurementMode(),
            'sleep_mode' => $this->getSleepMode(),
            'sleep_rate' => $this->getWakeup(),
            'active_interrupts' => $this->getEnabledInterrupts(),
            'fsr' => $this->getRange(),
            'data_format' => $this->getDataFormat(),
            'full_resolution' => $this->getResolution(),
            'raw' => $this->raw(),
            'acceleration' => $this->acceleration(),
            'x' => $this->x(),
            'y' => $this->y(),
            'z' => $this->z(),
            'data_rate' => $this->getDataRate(),
            'interrupt_map' => $this->getInterruptMap(),
            'interrupt_source' => $this->getInterruptSource(),
            'tap_threshold' => $this->getTapThreshold(),
            'tap_duration' => $this->getTapDuration(),
            'tap_latency' => $this->getTapLatency(),
            'tap_window' => $this->getTapWindow(),
            'tap_axes' => $this->getTapAxes(),
            'activity_threshold' => $this->getActivityThreshold(),
            'inactivity_threshold' => $this->getInactivityThreshold(),
            'inactivity_time' => $this->getInactivityTime(),
            'activity_control' => $this->getActivityControl(),
            'free_fall_threshold' => $this->getFreeFallThreshold(),
            'free_fall_time' => $this->getFreeFallTime(),
            default => throw ADXL34xException::invalidProperty($name, static::class)
        };
    }

    /**
     * @throws ADXL34xException
     */
    public function __set(string $name, mixed $value): void
    {
        match($name) {
            'power_control' => $this->setPowerControl($value),
            'link_mode' => $this->setLinkMode($value),
            'measurement_mode' => $this->setMeasurementMode($value),
            'sleep_mode' => $this->setSleepMode($value),
            'sleep_rate' => $this->setWakeup($value),
            'active_interrupts' => $this->setEnabledInterrupts($value),
            'data_rate' => $this->setDataRate($value),
            'fsr' => $this->setRange($value),
            'data_format' => $this->setDataFormat($value),
            'full_resolution' => $this->setResolution($value),
            'interrupt_map' => $this->setInterruptMap($value),
            'tap_threshold' => $this->setTapThreshold($value),
            'tap_duration' => $this->setTapDuration($value),
            'tap_latency' => $this->setTapLatency($value),
            'tap_window' => $this->setTapWindow($value),
            'tap_axes' => $this->setTapAxes($value),
            'activity_threshold' => $this->setActivityThreshold($value),
            'inactivity_threshold' => $this->setInactivityThreshold($value),
            'inactivity_time' => $this->setInactivityTime($value),
            'activity_control' => $this->setActivityControl($value),
            'free_fall_threshold' => $this->setFreeFallThreshold($value),
            'free_fall_time' => $this->setFreeFallTime($value),
            default => throw ADXL34xException::invalidProperty($name, static::class)
        };
    }



    protected function _boot(): void
    {
        $this->confirmDeviceId();
        $this->setupPowerControl();
        $this->setInterruptPinFunctions();
    }

    protected function confirmDeviceId(): void
    {
        $device_id = $this->getDeviceId();

        if ($device_id !== $this->hardwired_device_id) {
            throw ADXL34xException::invalidChipId($device_id, $this->hardwired_device_id);
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

    /** Raw counts → m/s² at the scale the chip is configured for right now. */
    protected function calcADXL(int $value, CelestialBody $body = CelestialBody::TERRA): float
    {
        return $value * $this->scale() * $body->gravity();
    }
}