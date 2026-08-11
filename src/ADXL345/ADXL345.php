<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345InterruptFunctions;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Concerns\ADXL345API;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xCarrierTransport;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\ADXL34xI2CAddress;
use Exception;
use GeneralPurposeIO\Circuits\Types\SensorIC;
use GeneralPurposeIO\Contracts\Circuits\Attributes\IntegratedCircuit;
use GeneralPurposeIO\Contracts\Circuits\Attributes\Pinout;
use GeneralPurposeIO\Contracts\Circuits\BootSequence;
use GeneralPurposeIO\I2C\I2C;
use GeneralPurposeIO\I2C\I2CSlave;
use GeneralPurposeIO\SPI\SPI;
use GeneralPurposeIO\SPI\SPIDevice;
use Waveforms\Contracts\Motion\MeasuresAcceleration;

#[IntegratedCircuit('I2C', 'SPI')]
#[Pinout(['I2C' => ['driver', 'device', 'slave']], ['SPI' => ['driver', 'device', 'chip_select']])]
class ADXL345 extends SensorIC implements BootSequence, MeasuresAcceleration
{
    use ADXL345API;

    /**
     * @throws Exception
     */
    public function __construct(
        protected readonly ADXL34xCarrierTransport $transport,
        private readonly ADXL345InterruptFunctions $_starting_int_fns = new ADXL345InterruptFunctions(),
        bool $boot_now = false,
    ) {
        if($boot_now) {
            $this->boot();
        }
    }

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
            'x' => $this->x(),
            'y' => $this->y(),
            'z' => $this->z(),
            'data_rate' => $this->getDataRate(),
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
            default => throw ADXL34xException::invalidProperty($name, static::class)
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

    public function close(): void
    {
        $this->transport->close();
    }

    /**
     * Creates an ADXL345 instance with a standalone i2c connection
     * @param string|int $device
     * @param string|null $adapter
     * @param int $slave
     * @param bool $boot_now
     * @param ADXL345InterruptFunctions $int_fns
     * @return static
     * @throws ADXL34xException
     */
    public static function i2c(
        string|int $device,
        ?string $adapter = null,
        int $slave = ADXL34xI2CAddress::SDO_GROUNDED->value,
        ADXL345InterruptFunctions $int_fns = new ADXL345InterruptFunctions,
        bool $boot_now = true,
    ): static
    {
        $i2c = I2C::adapter($adapter)
            ->device($device)
            ->bus()
            ->slave($slave);

        return static::fromI2CBus($i2c, $int_fns, $boot_now);
    }

    /**
     * Creates an ADXL345 instance from a bootstrapped I2CSlave instance
     * @param I2CSlave $i2c
     * @param bool $boot_now
     * @param ADXL345InterruptFunctions $int_fns
     * @return static
     * @throws ADXL34xException
     * @throws Exception
     */
    public static function fromI2CBus(
        I2CSlave $i2c,
        ADXL345InterruptFunctions $int_fns = new ADXL345InterruptFunctions,
        bool $boot_now = true,
    ): static
    {
        $transport = new ADXL34xCarrierTransport(i2c: $i2c);
        return new static($transport, $int_fns, $boot_now);
    }

    /**
     * @throws ADXL34xException
     */
    public static function spi(
        string|int $spi_device,
        string|int $chip_select,
        ?string $spi_adapter = null,
        ADXL345InterruptFunctions $int_fns = new ADXL345InterruptFunctions,
        bool $boot_now = true
    ): static
    {
        $spi = SPI::adapter($spi_adapter)->device($spi_device)
            ->mode(3)->speed(1000000)->bus()
            ->select($chip_select);

        return static::fromSPIBus($spi, $int_fns, $boot_now);
    }

    /**
     * @throws ADXL34xException
     * @throws Exception
     */
    public static function fromSPIBus(
        SPIDevice $spi,
        ADXL345InterruptFunctions $int_fns = new ADXL345InterruptFunctions,
        bool $boot_now = true,
    ): static
    {
        $transport = new ADXL34xCarrierTransport(spi: $spi);
        return new static($transport,
            $int_fns,
            $boot_now);
    }
}
