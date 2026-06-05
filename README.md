Introduction
============

PHP Package for the ADXL34x-family of accelerometer sensors.

Compatible I2C Interfaces
===============
The ADXL34x sensors communicates with your device over I2C, the InterIntegrated Circuit Protocol.

You can interface with sensors like ADXL345 with this package the following ways:
* A Linux Single-Board Computer's exposed GPIO pins using the dedicated I2C SDA/SCL pins
* An MPSSE-enabled USB-to-Serial device such as an FT232H generally using D0 and SCL and D1 for SDA connected to nearly any Linux or MacOS USB port.

Compatible SPI Interfaces
===============
The ADXL34x sensor also supports SPI for direct register communication.

You can interface with sensors like ADXL345 over SPI with this package the following ways:
* A Linux Single-Board Computer's exposed GPIO pins using the dedicated SPI MOSI/MISO/SCK and CS pins
* An MPSSE-enabled USB-to-Serial device such as an FT232H generally using D0 and SCK, D1 for MOSI, D2 for MISO and D3 for CS connected to nearly any Linux or MacOS USB port.

Dependencies
=============
This package makes use of modules within:
* [The ScrapyardIO Framework](https://github.com/ScrapyardIO/framework)

This package also requires one of the following extensions in order to interface with I2C/SPI
* [POSI Extension v^0.4.0 or newer](https://github.com/php-io-extensions/posi)
* [FTDI Extension v^0.4.0 or newer](https://github.com/php-io-extensions/ftdi)

In addition, an extension wrapper package is needed

For ext-posi
* [Microscrap POSIX Package v0.4.0 or newer](https://github.com/microscrap/posix)
* [Microscrap Native I2C Package v0.4.0 or newer](https://github.com/microscrap/i2c)
* [Microscrap Native SPI Package v0.4.0 or newer](https://github.com/microscrap/spi)

For ext-ftdi
* [Microscrap FTDI Package v0.4.0 or newer](https://github.com/microscrap/ftdi)
* [Microscrap MPSSE Package v0.4.0 or newer](https://github.com/microscrap/mpsse)

Installing from Composer
====================
Inside the root of your PHP Project, simply require the BMP package from composer
```shell
composer require dept-of-scrapyard-robotics/adxl34x
```
Framework Configuration
====================

If you would like to use the ScrapyardIO Framework to bootstrap your sensor without
wasting lines configuring your sensor right in the script you can add your desired
configuration to scrapyard-io.php, such as in this example:

### I2C
```php

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345I2CAddress

return [
    'boards' => [
        // For Native Configurations 
        'bmp280-native' => [
            'class_name' => ADXL345::class,
            'connection' => ['driver' => 'native'],
            'startup' => [
                'i2c' => [
                    'chip_device' => 1,
                    'slave_address' => ADXL345I2CAddress::SDO_GROUNDED->value,
                ],
            ],
        ],
        // For USB Configurations
        'bmp280-usb' => [
            'class_name' => ADXL345::class,
            'connection' => ['driver' => 'usb'],
            'startup' => [
                'i2c' => [
                    'chip_device' => 'ft232h',
                    'slave_address' => ADXL345I2CAddress::SDO_GROUNDED->value,
                ],
            ],
        ],        
    ]
];

```

### SPI
```php

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;

return [
    'boards' => [
        // For Native Configurations 
        'bmp280-native' => [
            'class_name' => ADXL345::class,
            'connection' => ['driver' => 'native'],
            'startup' => [
                'spi' => [
                    'master' => 0,
                    'chip_select' => 0,
                ],
            ],
        ],
        // For USB Configurations
        'bmp280-native' => [
            'class_name' => ADXL345::class,
            'connection' => ['driver' => 'usb'],
            'startup' => [
                'spi' => [
                    'master' => 'ft232h',
                    'chip_select' => 0,
                ],
            ],
        ],        
    ]
];
```

Basic Usage
============

### Native (POSIX) I2C driver. (Single Board Computers)
```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345I2CAddress

$native_i2c_sensor = ADXL345::connection('native')
    ->i2c(1, ADXL345I2CAddress::SDO_GROUNDED->value)
    ->create()
    
[$x, $y, $z] = $native_i2c_sensor->acceleration;

```

### Native (POSIX) SPI driver. (Single Board Computers)
```php

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;

$native_spi_sensor = ADXL345::connection('native')
    ->spi(0, 0)
    ->create()
    
[$x, $y, $z] = $native_spi_sensor->acceleration;

```

### USB (MPSSE) driver using I2C. (Linux and MacOS)
```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345I2CAddress;

$usb_i2c_sensor = ADXL345::connection('usb')
    ->i2c('ft232h', ADXL345I2CAddress::SDO_GROUNDED->value)
    ->create()
    
[$x, $y, $z] = $usb_i2c_sensor->acceleration;

```

### USB (MPSSE) driver using SPI. (Linux and MacOS)
```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;

$usb_i2c_sensor = ADXL345::connection('usb')
    ->spi('ft232h', 0)
    ->create()
    
[$x, $y, $z] = $usb_i2c_sensor->acceleration;
```
## Alternative Usage

### Using Through the Sensor Library (as an Accelerometer)
```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use RealityInterface\Sensors\Applied\Accelerometry\Accelerometer;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345I2CAddress;

$adxl345 = ADXL345::connection('native')
    ->i2c(1, ADXL345I2CAddress::SDO_GROUNDED->value)
    ->create();
    
$sensor = Accelerometer::as($adxl345);

$data = $sensor->getAcceleration();

```

### Using Through the Sensor Framework (with an autoloaded config) (as an Accelerometer)
```php

use RealityInterface\Sensors\Applied\Accelerometry\Accelerometer;

$sensor = Accelerometer::using('adxl345');

$data = $sensor->getAcceleration();

```

## Advanced Usage

You can tune measurement behavior at runtime using the ADXL345's configuration properties:

* `data_rate` controls output data rate (and therefore power consumption).
* `range` controls the full-scale measurement range.
* `sleep_mode` puts the device into low-power sleep between measurements.
* `sleep_rate` controls how often the device wakes to sample in sleep mode.
* `link_mode` serialises activity and inactivity events so only one fires at a time.
* `active_interrupts` controls which interrupt sources are forwarded to INT1.

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\DataObjects\ADXL345InterruptFunctions;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345DataRate;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345I2CAddress;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345Range;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345SleepSamplingRate;

$sensor = ADXL345::connection('native')
    ->i2c(1, ADXL345I2CAddress::SDO_GROUNDED->value)
    ->create();

// Set output data rate to 200 Hz
$sensor->data_rate = ADXL345DataRate::HZ200;

// Set full-scale range to ±8 g
$sensor->range = ADXL345Range::G8;

// Enable motion detection interrupt
$sensor->enable_motion_detection = true;

// Enable freefall detection interrupt
$sensor->enable_freefall_detection = true;

// Put device to sleep, waking at 4 Hz to sample
$sensor->sleep_mode = true;
$sensor->sleep_rate = ADXL345SleepSamplingRate::SLEEP_4HZ;
```

### Event Detection

Reading `$sensor->events` returns a map of which enabled interrupt sources fired since the last read.

```php
$sensor->enable_tap_detection = true;

$events = $sensor->events;
// ['tap' => true]  — when a tap was detected

$events = $sensor->events;
// ['tap' => false] — no tap since last check
```

## Calibration

The ADXL345 exposes per-axis offset registers (`OFSX`, `OFSY`, `OFSZ`) that subtract a fixed value from every raw output before it reaches the data registers. This lets you null out any DC bias introduced by board mounting angle or PCB flex.

### Reading current offsets

```php
[$x_offset, $y_offset, $z_offset] = $sensor->offset;
```

Each value is a signed 8-bit integer scaled at 15.6 mg/LSB.

### Writing offsets

```php
// Zero all offsets
$sensor->offset = [0, 0, 0];

// Apply a measured correction — e.g. z reads +0.05 g too high at rest
// 0.05 g / 0.0156 g per LSB ≈ 3 LSB correction
$sensor->offset = [0, 0, -3];
```

### Self-calibration procedure

1. Place the sensor on a flat, level surface.
2. Read several acceleration samples and average them.
3. The expected resting values are `x=0 g`, `y=0 g`, `z=+1 g`.
4. Convert the error in g to LSBs (`error_g / 0.0156`) and negate it.
5. Write those values back via `$sensor->offset`.

```php
$samples = 50;
$sum = ['x' => 0.0, 'y' => 0.0, 'z' => 0.0];

for ($i = 0; $i < $samples; $i++) {
    [$x, $y, $z] = $sensor->acceleration;
    $sum['x'] += $x;
    $sum['y'] += $y;
    $sum['z'] += $z;
    usleep(10000);
}

$gravity = 9.80665;
$avg_x = $sum['x'] / $samples / $gravity;  // in g
$avg_y = $sum['y'] / $samples / $gravity;
$avg_z = $sum['z'] / $samples / $gravity;

$lsb_per_g = 1 / 0.0156;
$sensor->offset = [
    (int) round(-$avg_x * $lsb_per_g),
    (int) round(-$avg_y * $lsb_per_g),
    (int) round(-(($avg_z - 1.0) * $lsb_per_g)),
];
```

## Sensor API

The getters and setters in this API interface with the device directly (register reads/writes),
so you can use property access while still working against the sensor itself.

Readable Properties (Getters)
-----------------------------

* `$sensor->device_id`
  Reads and returns the ADXL345 device ID. Should return `0xE5`.

* `$sensor->acceleration`
  Reads all three axes and returns a keyed array `['x' => float, 'y' => float, 'z' => float]`
  in m/s².

* `$sensor->raw_x`
  Reads and returns the raw signed 16-bit integer for the X axis.

* `$sensor->raw_y`
  Reads and returns the raw signed 16-bit integer for the Y axis.

* `$sensor->raw_z`
  Reads and returns the raw signed 16-bit integer for the Z axis.

* `$sensor->events`
  Reads the interrupt source register and returns a map of which enabled interrupt sources
  (`motion`, `freefall`, `tap`) have fired since the last read.
  Example: `['motion' => true, 'freefall' => false]`

* `$sensor->power_control`
  Reads and returns the full `ADXL345PowerControl` register data object.

* `$sensor->link_mode`
  Returns `true` if link mode is enabled (activity/inactivity events are serialised).

* `$sensor->measurement_mode`
  Returns `true` if the device is in active measurement mode.

* `$sensor->sleep_mode`
  Returns `true` if the device is currently in sleep mode.

* `$sensor->sleep_rate`
  Returns the current `ADXL345SleepSamplingRate` wakeup rate used in sleep mode.
  Possible values: `SLEEP_8HZ`, `SLEEP_4HZ`, `SLEEP_2HZ`, `SLEEP_3HZ`.

* `$sensor->active_interrupts`
  Reads and returns the `ADXL345InterruptFunctions` data object representing which interrupt
  sources are currently enabled in the INT_ENABLE register.

* `$sensor->data_rate`
  Returns the current `ADXL345DataRate` output data rate.
  Possible values: `HZ3200`, `HZ1600`, `HZ800`, `HZ400`, `HZ200`, `HZ100` (default), `HZ50`,
  `HZ25`, `HZ12p5`, `HZ6p25`, `HZ3p13`, `HZ1p56`, `HZ0p78`, `HZ0p39`, `HZ0p20`, `HZ0p10`.

* `$sensor->offset`
  Returns a `[x, y, z]` array of the current signed 8-bit offset correction values (15.6 mg/LSB).

Writable Properties (Setters)
-----------------------------

* `$sensor->power_control = new ADXL345PowerControl(...);`
  Writes the full power control register from an `ADXL345PowerControl` data object.

* `$sensor->link_mode = true;`
  Enables or disables link mode. When enabled, activity and inactivity events are serialised
  so only one fires at a time.

* `$sensor->measurement_mode = true;`
  Enables or disables measurement mode. Set to `false` to put the device in standby.

* `$sensor->sleep_mode = true;`
  Enables or disables sleep mode. In sleep mode the device wakes periodically at `sleep_rate`
  to take a sample, then returns to low-power sleep.

* `$sensor->sleep_rate = ADXL345SleepSamplingRate::SLEEP_4HZ;`
  Sets the wakeup sampling rate used while in sleep mode.

* `$sensor->active_interrupts = new ADXL345InterruptFunctions(...);`
  Writes the INT_ENABLE register from an `ADXL345InterruptFunctions` data object, controlling
  exactly which interrupt sources are forwarded to INT1.

* `$sensor->enable_motion_detection = true;`
  Convenience setter. When `true`, configures the activity threshold and enables the activity
  interrupt. When `false`, disables it.

* `$sensor->enable_freefall_detection = true;`
  Convenience setter. When `true`, configures the freefall threshold and time window and enables
  the freefall interrupt. When `false`, disables it.

* `$sensor->enable_tap_detection = true;`
  Convenience setter. When `true`, configures tap axes, threshold, and duration and enables the
  single-tap interrupt. When `false`, disables both single and double-tap interrupts.

* `$sensor->data_rate = ADXL345DataRate::HZ200;`
  Sets the output data rate. Lower rates reduce power consumption.

* `$sensor->offset = [0, 0, -3];`
  Writes signed per-axis offset corrections as a `[x, y, z]` array (15.6 mg/LSB each).
  Used to null out DC bias from mounting angle or PCB flex.
