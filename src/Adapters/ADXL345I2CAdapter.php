<?php

namespace ScrapyardIO\Sensors\Accelerometers\ADXL345\Adapters;

use ScrapyardIO\Sensors\Enums\SensorType;
use ScrapyardIO\Support\Attributes\Sensor;
use ScrapyardIO\Sensors\Accelerometers\ADXL345\Enums\ADXL345Command;
use ScrapyardIO\Sensors\Accelerometers\Adapters\AccelerometerAdapter;
use ScrapyardIO\Sensors\Accelerometers\ADXL345\Concerns\ADXL345I2CChip;
use ScrapyardIO\Sensors\Accelerometers\ADXL345\Enums\ADXL345I2CAddress;
use ScrapyardIO\Sensors\Accelerometers\ADXL345\Concerns\ADXL345BootSequence;
use ScrapyardIO\Sensors\Accelerometers\ADXL345\Exceptions\ADXL345Exception;

#[Sensor('ADXL345', ADXL345I2CAddress::SDO_GROUNDED->value, SensorType::ACCELEROMETER)]
class ADXL345I2CAdapter extends AccelerometerAdapter
{
    use ADXL345I2CChip;
    use ADXL345BootSequence;

    public function bus(int $bus):static
    {
        $this->i2c_adxl345_bus($bus);
        return $this;
    }

    public function address(ADXL345I2CAddress $address):static
    {
        $this->i2c_adxl345_address($address->value);
        return $this;
    }

    public function rawX(): int
    {
        [$low, $high] = $this->readData(ADXL345Command::X_AXIS_LOW->value, 2);
        usleep(9000);
        return $this->convertTo16Bit($low, $high);
    }

    public function rawY(): int
    {
        [$low, $high] = $this->readData(ADXL345Command::Y_AXIS_LOW->value, 2);
        usleep(9000);
        return $this->convertTo16Bit($low, $high);
    }

    public function rawZ(): int
    {
        [$low, $high] = $this->readData(ADXL345Command::Z_AXIS_LOW->value, 2);
        usleep(9000);
        return $this->convertTo16Bit($low, $high);
    }

    public function rawXYZ(): array
    {
        [$x_low, $x_high, $y_low, $y_high, $z_low, $z_high] =
            $this->readData(ADXL345Command::X_AXIS_LOW->value, 6);

        usleep(9000);
        return [
            'x' => $this->convertTo16Bit($x_low, $x_high),
            'y' => $this->convertTo16Bit($y_low, $y_high),
            'z' => $this->convertTo16Bit($z_low, $z_high),
        ];
    }

    /**
     * @return $this
     * @throws ADXL345Exception
     */
    public function boot(): static
    {
        $this->adxl345_i2c();

        $this->readDeviceId();
        usleep(9000);
        $this->setDataFormat();
        usleep(9000);
        $this->setPowerControl();
        usleep(9000);

        return $this;
    }
}
