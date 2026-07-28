<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x;

use Fabricate\NutsAndBolts\Concerns\Splices16Bits;
use GeneralPurposeIO\I2C\I2CSlave;
use GeneralPurposeIO\SPI\SPIDevice;

class ADXL34xCarrierTransport
{
    use ADXL34xIO, Splices16Bits;

    public readonly string $active_transport;

    /**
     * @throws ADXL34xException
     */
    public function __construct(
        protected ?I2CSlave $i2c = null,
        protected ?SPIDevice $spi = null,
    ) {
        $this->active_transport = $this->detectTransport();
    }

    public function write(int $register, array $data): int
    {
        $method = "{$this->active_transport}Write";
        return $this->{$method}($register, $data);
    }

    public function read(int $register, int $length): array
    {
        $method = "{$this->active_transport}Read";
        return $this->{$method}($register, $length);
    }

    /**
     * @throws ADXL34xException
     */
    protected function detectTransport(): string
    {
        if(!is_null($this->i2c)) {
            return 'i2c';
        }
        elseif(!is_null($this->spi)) {
            return 'spi';
        }

        throw ADXL34xException::transportMissingProtocol();
    }

    public function close(): void
    {
        $this->i2c?->close();
        $this->spi?->close();
    }
}