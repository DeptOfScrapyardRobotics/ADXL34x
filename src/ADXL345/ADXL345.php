<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345;

use BareMetal\IntegratedCircuit;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Adapters\ADXL345DataCarrier;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Concerns\ADXL345API;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\DataObjects\ADXL345InterruptFunctions;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\DataObjects\ADXL345PowerControl;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345SleepSamplingRate;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Exceptions\ADXL345Exception;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Factory\ADXL345Factory;
use Exception;
use RealityInterface\Sensors\Attributes\MeasuresAcceleration;
use RealityInterface\Sensors\Contracts\Applied\Accelerometry\GenericAccelerometer;
use RealityInterface\Sensors\Enums\SensorType;
use Waveforms\Carriers\GPIO\GPIOBus;
use Waveforms\Carriers\I2C\I2C;
use Waveforms\Carriers\SPI\SPI;

/**
 * @property-read int $device_id
 * @property ADXL345PowerControl $power_control
 * @property bool $link_mode
 * @property bool $measurement_mode,
 * @property bool $sleep_mode,
 * @property ADXL345SleepSamplingRate $sleep_rate
 * @property ADXL345InterruptFunctions $active_interrupts
 * @property-read array $acceleration
 * @property-read int $raw_x
 * @property-read int $raw_y
 * @property-read int $raw_z
 * @property-read array $events
 * @property-write bool $enable_motion_detection
 * @property-write bool $enable_freefall_detection
 * @property-write bool $enable_tap_detection
 */
#[MeasuresAcceleration(SensorType::ACCELEROMETER)]
class ADXL345 extends IntegratedCircuit implements GenericAccelerometer
{
    use ADXL345API;

    protected bool $booted = false;

    protected int $hardwired_device_id = 0xE5;

    /**
     * @throws ADXL345Exception
     */
    public function __construct(
        protected readonly ADXL345DataCarrier $carrier,
        protected readonly ?GPIOBus $gpio,
        ADXL345InterruptFunctions $interrupts
    ) {
        $this->boot($interrupts);
    }

    /**
     * @throws ADXL345Exception
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'device_id' => $this->getDeviceId(),
            'power_control' => $this->getPowerControl(),
            'link_mode' => $this->getLinkMode(),
            'measurement_mode' => $this->getMeasurementMode(),
            'sleep_mode' => $this->getSleepMode(),
            'sleep_rate' => $this->getWakeup(),
            'active_interrupts' => $this->getEnabledInterrupts(),
            'acceleration' => $this->getAcceleration(),
            'raw_x' => $this->getRawX(),
            'raw_y' => $this->getRawY(),
            'raw_z' => $this->getRawZ(),
            'events' => $this->getEvents(),
            'data_rate' => $this->getDataRate(),
            default => throw ADXL345Exception::invalidProperty($name)
        };
    }

    /**
     * @throws ADXL345Exception
     */
    public function __set(string $name, mixed $value): void
    {
        match ($name) {
            'power_control' => $this->setPowerControl($value),
            'link_mode' => $this->setLinkMode($value),
            'measurement_mode' => $this->setMeasurementMode($value),
            'sleep_mode' => $this->setSleepMode($value),
            'sleep_rate' => $this->setWakeup($value),
            'active_interrupts' => $this->setEnabledInterrupts($value),
            'enable_motion_detection' => $this->setMotionDetection($value),
            'enable_freefall_detection' => $this->setFreefallDetection($value),
            'enable_tap_detection' => $this->setTapDetection($value),
            'data_rate' => $this->setDataRate($value),
            default => throw ADXL345Exception::invalidProperty($name)
        };
    }

    /**
     * @throws ADXL345Exception
     */
    protected function boot(ADXL345InterruptFunctions $interrupts): void
    {
        if (! $this->booted) {

            if ($this->device_id != $this->hardwired_device_id) {
                throw ADXL345Exception::invalidChipId($this->device_id);
            }

            $this->power_control = new ADXL345PowerControl(measurement_mode: true, sleep_mode: false);
            $this->active_interrupts = $interrupts;

            $this->booted = true;
        }
    }

    /**
     * @throws Exception
     */
    public static function connection(string $driver): ADXL345Factory
    {
        return new ADXL345Factory(
            I2C::connection($driver),
            SPI::connection($driver)
        );
    }
}
