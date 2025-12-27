<?php

namespace ScrapyardIO\Sensors\Accelerometers\ADXL345\Concerns;

use ScrapyardIO\Transports\SPITransport;

trait ADXL345SPIChip
{
    protected ?SPITransport $adxl345_spi = null;
    protected int $adxl345_spi_bus = 1;
    protected int $spi_adxl345_chip_select = 0;
    protected int $max_packet_size = 0;

    protected function spi_adxl345_bus(?int $bus = null): int
    {
        if(!is_null($bus))
        {
            $this->adxl345_spi_bus = $bus;
        }
        return $this->adxl345_spi_bus;
    }

    protected function spi_adxl345_chip_select(?int $cs = null): int
    {
        if($cs)
        {
            $this->spi_adxl345_chip_select = $cs;
        }
        return $this->spi_adxl345_chip_select;
    }

    protected function adxl345_spi(): ?SPITransport
    {
        if(empty($this->adxl345_spi))
        {
            $this->adxl345_spi = new SPITransport(
                $this->spi_adxl345_bus(),
                $this->spi_adxl345_chip_select(),
                3,
                1000000,
                0
            );
        }

        return $this->adxl345_spi;
    }

    public function readData(int $command, int $num_bytes_to_read, bool $set_read_bit = false): array
    {
        //$this->sendCommand([$command]);
        return $this->adxl345_spi()->read($command, $num_bytes_to_read, $set_read_bit);
    }

    public function sendCommand(array $bytes): void
    {
        $this->adxl345_spi()->notify($bytes);
    }
}
