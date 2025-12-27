<?php

namespace ScrapyardIO\Sensors\Accelerometers\ADXL345\Concerns;

use ScrapyardIO\Transports\I2CTransport;

trait ADXL345I2CChip
{
    protected ?I2CTransport $adxl345_i2c = null;
    protected int $adxl345_i2c_bus = 1;
    protected int $adxl345_i2c_address = 0;
    protected int $max_packet_size = 0;

    protected function i2c_adxl345_bus(?int $bus = null): int
    {
        if($bus)
        {
            $this->adxl345_i2c_bus = $bus;
        }
        return $this->adxl345_i2c_bus;
    }

    protected function i2c_adxl345_address(?int $address = null): int
    {
        if($address)
        {
            $this->adxl345_i2c_address = $address;
        }
        return $this->adxl345_i2c_address;
    }

    protected function adxl345_i2c(): ?I2CTransport
    {
        if(empty($this->adxl345_i2c))
        {
            $this->adxl345_i2c = new I2CTransport(
                $this->i2c_adxl345_address(),
                $this->i2c_adxl345_bus()
            );
        }

        return $this->adxl345_i2c;
    }

    public function readData(int $command, int $num_bytes_to_read): array
    {
        $this->sendCommand([$command]);
        return $this->adxl345_i2c()->read($num_bytes_to_read);
    }

    public function sendCommand(array $bytes): void
    {
        $this->adxl345_i2c()->notify($bytes);
    }
}
