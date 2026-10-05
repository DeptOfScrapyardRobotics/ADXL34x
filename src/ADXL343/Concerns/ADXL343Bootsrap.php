<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Concerns;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Breakouts\ADXL343PowerControl;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums\ADXL343OpCode;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\CelestialBody;

trait ADXL343Bootsrap
{
    use ADXL343API;

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
        $this->awaitFirstSample();
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
        $this->power_control = new ADXL343PowerControl(measurement_mode: true, sleep_mode: false);
    }

    /**
     * Until the first conversion in measurement mode lands, 1/ODR + 1.1 ms after the MEASURE bit, the data
     * registers hold zeros after power-up or the last sample taken before standby. Boot returns once DATA_READY
     * reports a fresh one, so the first read after boot is a real sample. DATA_READY is set whatever INT_ENABLE
     * says, and reading the data clears it, so a sample left over from before standby is cleared first.
     */
    protected function awaitFirstSample(): void
    {
        $this->readData(ADXL343OpCode::DATA_FROM_X0_REGISTER, 6);
        $rate = $this->getDataRate();
        $budget_ns = (int) ceil(2e9 / $rate->hz()) + 2_000_000;
        $deadline = hrtime(true) + $budget_ns;

        while (! $this->getInterruptSource()->data_ready) {
            if (hrtime(true) >= $deadline) {
                throw ADXL34xException::noFirstSample($rate->hz(), intdiv($budget_ns, 1_000_000));
            }

            usleep(500);
        }
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