<?php

namespace ScrapyardIO\Sensors\Accelerometers\ADXL345\Adapters;

use ScrapyardIO\Sensors\Accelerometers\ADXL345\Exceptions\ADXL345Exception;
use ScrapyardIO\Sensors\Enums\SensorType;
use ScrapyardIO\Support\Attributes\Sensor;
use ScrapyardIO\Sensors\Accelerometers\ADXL345\Enums\ADXL345Command;
use ScrapyardIO\Sensors\Accelerometers\Adapters\AccelerometerAdapter;
use ScrapyardIO\Sensors\Accelerometers\ADXL345\Concerns\ADXL345SPIChip;
use ScrapyardIO\Sensors\Accelerometers\ADXL345\Enums\ADXL345I2CAddress;
use ScrapyardIO\Sensors\Accelerometers\ADXL345\Concerns\ADXL345BootSequence;

#[Sensor('ADXL345', ADXL345I2CAddress::SDO_GROUNDED->value + 1, SensorType::ACCELEROMETER)]
class ADXL345SPIAdapter extends AccelerometerAdapter
{
    use ADXL345SPIChip;
    use ADXL345BootSequence;

    public function bus(int $bus):static
    {
        $this->spi_adxl345_bus($bus);
        return $this;
    }

    public function chipSelect(int $cs):static
    {
        $this->spi_adxl345_chip_select($cs);
        return $this;
    }

    public function rawX(): int
    {
        [$echo, $low, $high] = $this->readData(ADXL345Command::X_AXIS_LOW->value | 0x40, 2, true);
        usleep(9000);
        return $this->convertTo16Bit($low, $high);
    }

    public function rawY(): int
    {
        [$echo, $low, $high] = $this->readData(ADXL345Command::Y_AXIS_LOW->value | 0x40, 2, true);
        usleep(9000);
        return $this->convertTo16Bit($low, $high);
    }

    public function rawZ(): int
    {
        [$echo,$low, $high] = $this->readData(ADXL345Command::Z_AXIS_LOW->value | 0x40, 2, true);
        usleep(9000);
        return $this->convertTo16Bit($low, $high);
    }

    public function rawXYZ(): array
    {
        [$echo, $x_low, $x_high, $y_low, $y_high, $z_low, $z_high] =
            $this->readData(ADXL345Command::X_AXIS_LOW->value | 0x40, 6, true);

        usleep(9000);
        return [
            'x' => $this->convertTo16Bit($x_low, $x_high),
            'y' => $this->convertTo16Bit($y_low, $y_high),
            'z' => $this->convertTo16Bit($z_low, $z_high),
        ];
    }

    public function boot(): static
    {
        $this->adxl345_spi();

        $this->readDeviceId();
        usleep(9000);
        $this->setDataFormat();
        usleep(9000);
        $this->setPowerControl();
        usleep(9000);

        return $this;
    }

    public function readDeviceId(): void
    {
        [$echo, $device_id] = $this->readData(ADXL345Command::DEVICE_ID_REGISTER->value, 1, true);
        if($device_id != 0xE5) throw ADXL345Exception::invalidDeviceID($device_id, 0xE5);
    }
}
