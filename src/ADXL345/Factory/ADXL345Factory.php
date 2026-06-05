<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Factory;

use BareMetal\CircuitFactory;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Adapters\ADL345I2CAdapter;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Adapters\ADL345SPIAdapter;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\DataObjects\ADXL345InterruptFunctions;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Exceptions\ADXL345Exception;
use Exception;
use Waveforms\Carriers\GPIO\Factory\GPIOConnectionBuilder;
use Waveforms\Carriers\I2C\Factory\I2CConnectionBuilder;
use Waveforms\Carriers\I2C\I2CDevice;
use Waveforms\Carriers\SPI\Enums\SPIMode;
use Waveforms\Carriers\SPI\Factory\SPIConnectionBuilder;

class ADXL345Factory extends CircuitFactory
{
    public string $consumer = 'adxl345';

    protected ?GPIOConnectionBuilder $gpio_connection = null;

    public null|I2CConnectionBuilder|SPIConnectionBuilder $connection = null;

    public ADXL345InterruptFunctions $interrupts;

    public function __construct(
        public I2CConnectionBuilder $i2c_connection,
        public SPIConnectionBuilder $spi_connection,
    ) {
        $this->interrupts = ADXL345InterruptFunctions::none();
    }

    public function i2c(string|int $chip_device, int $slave_address): static
    {
        $this->connection = $this->i2c_connection->firstly($chip_device)
            ->slaveAddress($slave_address);

        return $this;
    }

    public function spi(string|int $master, int $chip_select): static
    {
        $this->connection = $this->spi_connection->firstly($master)
            ->chip($chip_select)
            ->speed(1000000)
            ->mode(SPIMode::MODE_3);

        return $this;
    }

    public function int1(int $pin): static
    {
        return $this;
    }

    public function int2(int $pin): static
    {
        return $this;
    }

    public function consumer(string $consumer): static
    {
        $this->consumer = $consumer;

        return $this;
    }

    public function interrupts(ADXL345InterruptFunctions $interrupts): static
    {
        $this->interrupts = $interrupts;

        return $this;
    }

    /**
     * @throws Exception
     * @throws ADXL345Exception
     */
    public function create(): ADXL345
    {
        $carrier = $this->connection?->boot();
        if (is_null($carrier)) {
            throw new Exception('A connection was not registered.');
        }

        if ($carrier instanceof I2CDevice) {
            $carrier = new ADL345I2CAdapter($carrier);
        } else {
            $carrier = new ADL345SPIAdapter($carrier);
        }

        $gpio = $this->gpio_connection?->consumer($this->consumer)->boot();

        return new ADXL345(
            $carrier, $gpio,
            $this->interrupts,
        );
    }
}
