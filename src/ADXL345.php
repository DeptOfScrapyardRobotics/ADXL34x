<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL345;

use BareMetal\Contracts\Circuits\BootSequence;
use BareMetal\Contracts\Sensors\Accelerometry\AccelerationMeasurable;
use BareMetal\Sensors\Sensor;
use DeptOfScrapyardRobotics\Sensors\ADXL345\Breakouts\ADXL345InterruptFunctions;
use GPIO\Contracts\I2C\I2CAPI;
use GPIO\Contracts\SPI\SPIAPI;
use ScrapyardIO\NutsAndBolts\ScrapyardIOException;

class ADXL345 extends Sensor implements BootSequence, AccelerationMeasurable
{
    use ADXL345API;

    /**
     * @throws ScrapyardIOException
     */
    public function __construct(
        protected readonly ADXL345SignalTransport $transport,
        private readonly ADXL345InterruptFunctions $_starting_int_fns = new ADXL345InterruptFunctions(),
        bool $boot_now = false,
    ) {
        if($boot_now) {
            $this->boot();
        }
    }

    /**
     * @throws ADXL345Exception
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
            'x' => $this->x(),
            'y' => $this->y(),
            'z' => $this->z(),
            'data_rate' => $this->getDataRate(),
            default => throw ADXL345Exception::invalidProperty($name, static::class)
        };
    }

    /**
     * @throws ADXL345Exception
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
            default => throw ADXL345Exception::invalidProperty($name, static::class)
        };
    }

    public function x(): float
    {
        return $this->calcADXL(
            $this->getRawX()
        );
    }

    public function y(): float
    {
        return $this->calcADXL(
            $this->getRawY()
        );
    }

    public function z(): float
    {
        return $this->calcADXL(
            $this->getRawZ()
        );
    }


    /**
     * @throws ScrapyardIOException
     */
    public static function i2c(
        I2CAPI $i2c,
        //?DigitalInputInterface $int1 = null,
        //?DigitalInputInterface $int2 = null,
        ADXL345InterruptFunctions $int_fns = new ADXL345InterruptFunctions(),
        bool $boot_now = false,
    ): static
    {
        $transport = new ADXL345SignalTransport(i2c:$i2c);
        return new self($transport,
            //$int1, $int2,
            $int_fns,
            $boot_now);
    }

    /**
     * @throws ScrapyardIOException
     */
    public static function spi(
        SPIAPI $spi,
        //?DigitalInputInterface $int1 = null,
        //?DigitalInputInterface $int2 = null,
        ADXL345InterruptFunctions $int_fns = new ADXL345InterruptFunctions(),
        bool $boot_now = false,
    ): static
    {
        $transport = new ADXL345SignalTransport(spi:$spi);
        return new self($transport,
            //$int1, $int2,
            $int_fns,
            $boot_now);
    }
}
