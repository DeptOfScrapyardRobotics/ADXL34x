<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL345;

use GPIO\Contracts\I2C\I2CAPI;
use GPIO\Contracts\SPI\SPIAPI;
use ScrapyardIO\NutsAndBolts\Concerns\Splices16Bits;

class ADXL345SignalTransport
{
    use Splices16Bits, ADXL345IO;

    public readonly string $active_transport;

    /**
     * @throws ADXL345Exception
     */
    public function __construct(
        protected ?I2CAPI $i2c = null,
        protected ?SPIAPI $spi = null,
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
     * @throws ADXL345Exception
     */
    protected function detectTransport(): string
    {
        if(!is_null($this->i2c)) {
            return 'i2c';
        }
        elseif(!is_null($this->spi)) {
            return 'spi';
        }

        throw ADXL345Exception::transportMissingProtocol();
    }
}
