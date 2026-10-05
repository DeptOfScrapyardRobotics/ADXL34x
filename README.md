# adxl34x

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dept-of-scrapyard-robotics/adxl34x.svg)](https://packagist.org/packages/dept-of-scrapyard-robotics/adxl34x)
[![License](https://img.shields.io/packagist/l/dept-of-scrapyard-robotics/adxl34x.svg)](LICENSE)

Drive ADXL343 and ADXL345 three-axis accelerometers from PHP over I2C or SPI, using the ScrapyardIO GPIO framework.

`dept-of-scrapyard-robotics/adxl34x` wraps each chip's register map in a typed class. Describe the wiring in a config file, ask the circuit catalog for the chip, and read acceleration in m/s² or raw counts. Range, resolution, data rate, power, offsets and the eight interrupt functions are all typed settings, and interrupts can be checked once, waited for, or run on the event loop.

```
ext-posi / ext-ftdi            1:1 system and libftdi calls
  → microscrap/*               libgpiod, i2c-dev, spidev, termios, libmpsse in PHP
    → microscrap/scrapyard-*   adapters: the `native` and `usb` drivers
      → scrapyard-io/framework protocol managers, transports, the circuit catalog
        → dept-of-scrapyard-robotics/adxl34x   ← this package
```

## Requirements

- PHP 8.4 or newer
- A Venusian 0.10 application with the `scrapyard-io/framework` 0.10 components (`gpio/i2c`, `gpio/spi`, `gpio/digital`, `gpio/integrated-circuits`)
- An adapter for your hardware:
  - `microscrap/scrapyard-linux` (driver `native`) for a Raspberry Pi or other Linux board: `i2c-dev`, `spidev` and `libgpiod`, needs `ext-posi`
  - `microscrap/scrapyard-usb` (driver `usb`) for FTDI MPSSE boards such as the FT232H, needs `ext-ftdi`
- `venusian-voyager/io-pools` 0.10 if you run interrupts on the event loop

## Installation

```bash
composer require dept-of-scrapyard-robotics/adxl34x
```

The service provider is discovered automatically. It merges the package's wiring config under `circuits.adxl343` and `circuits.adxl345`, and registers both chips with the circuit catalog. To publish the config into your app, run:

```bash
php computer vendor:publish --tag=adxl34x-config
```

That writes `config/circuits/adxl343.php` and `config/circuits/adxl345.php`, each with `driver => 'none'` until you fill in your bench.

## Quick start

An ADXL343 on a Raspberry Pi's I2C bus 1 at `0x53`, its INT1 pin on GPIO24:

```php
// config/circuits/adxl343.php
return [
    'default_config' => 'i2c',
    'configs' => [
        'i2c' => [
            'driver' => 'native',
            'device' => 1,
            'slave' => 0x53,
            'int1' => ['enabled' => true, 'driver' => 'native', 'device' => 0, 'pin' => 24],
            'int2' => ['enabled' => false, 'driver' => 'native', 'device' => 0, 'pin' => 0],
        ],
    ],
];
```

```php
$adxl = app('circuit')->conjure('adxl343');

$adxl->acceleration();   // ['x' => float, 'y' => float, 'z' => float] in m/s², one sample
```

An ADXL345 on an FT232H over SPI, chip select on GPIO0 (D4), INT1 on GPIO1 (D5) and INT2 on GPIO2 (D6):

```php
// config/circuits/adxl345.php
return [
    'default_config' => 'spi',
    'configs' => [
        'spi' => [
            'driver' => 'usb',
            'device' => 'ft232h',
            'chip_select' => 0,
            'speed' => 5_000_000,
            'int1' => ['enabled' => true, 'driver' => 'usb', 'device' => 'ft232h', 'pin' => 1],
            'int2' => ['enabled' => true, 'driver' => 'usb', 'device' => 'ft232h', 'pin' => 2],
        ],
    ],
];
```

```php
$adxl = app('circuit')->conjure('adxl345');
```

`conjure()` connects the bus and the INT pins if your app hasn't already, then boots the chip. Booting checks the device id (`0xE5`), switches the chip into measurement mode, waits for its first sample so the first read is never stale, and applies the interrupt mask, which is none by default.

## Connecting

`conjure()` calls the chip's own factory with the config entry's keys, so you can call the factories directly with the same arguments:

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\ADXL343;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;

$adxl = ADXL343::i2c('native', 1, slave: 0x53,
    int1: ['enabled' => true, 'driver' => 'native', 'device' => 0, 'pin' => 24],
);

$adxl = ADXL345::spi('usb', 'ft232h', chip_select: 0, speed: 2_000_000);
```

| Factory | Parameters |
|---|---|
| `i2c()` | `driver`, `device`, `slave = 0x53`, `int1 = []`, `int2 = []`, `boot_now = true` |
| `spi()` | `driver`, `device`, `chip_select = 0`, `speed = 5_000_000`, `int1 = []`, `int2 = []`, `boot_now = true` |

A bus or pin device that isn't connected yet is connected by the factory. One your app already connected is shared as it is, so several chips can sit on one bus. The bus is connected before the INT pins, so pins on an FT232H ride the same USB context as its SPI or I2C engine.

### I2C

The address depends on the SDO/ALT pin:

| `ADXL34xI2CAddress` | Address | SDO |
|---|---|---|
| `SDO_GROUNDED` | `0x53` | tied low |
| `SDO_ENERGIZED` | `0x1D` | tied high |

### SPI

The ADXL34x talks 4-wire SPI in mode 3 only, at up to 5 MHz. `spi()` opens a new bus in mode 3 and clocks the chip's own chip select at `speed`, whatever the bus's default clock is. It refuses a bus your app opened in another mode, and a `speed` above 5 MHz, with an `ADXL34xException`.

### Building the transport yourself

The chip takes an `ADXL34xI2CTransport` or an `ADXL34xSPITransport`, each wrapping a framework transport plus the optional INT lines:

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xI2CTransport;

$i2c = app('gpio.i2c')->driver('native');
$slave = $i2c->connectTo(1)->register()->device(1, 0x53);

$adxl = new ADXL345(new ADXL34xI2CTransport($slave), boot_now: true);
```

## Reading acceleration

```php
$adxl->acceleration();   // ['x' => float, 'y' => float, 'z' => float] in m/s², one sample
$adxl->raw();            // ['x' => int, 'y' => int, 'z' => int] signed counts, one sample
$adxl->scale();          // g per count at the current settings

$adxl->x();              // or $adxl->x, likewise y and z
```

`acceleration()` and `raw()` read all three axes from one six-byte transaction. `x()`, `y()` and `z()` each take their own sample, so three calls give you three different moments. Use `acceleration()` when the axes need to agree.

To convert counts yourself, multiply by the scale to get g:

```php
$raw = $adxl->raw();
$z_in_g = $raw['z'] * $adxl->scale();   // ≈ 1.0 lying flat, once calibrated
```

`acceleration()` takes an optional `CelestialBody`. Counts times scale is acceleration in standard g; the default, `TERRA`, turns that into m/s². Another body multiplies by that body's surface gravity instead, so only `TERRA` gives the measured acceleration in m/s².

## Range, resolution and data rate

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345DataRate;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345Range;

$adxl->fsr = ADXL345Range::G16;             // ±2, ±4, ±8 or ±16 g
$adxl->full_resolution = true;              // 3.9 mg per count at every range
$adxl->data_rate = ADXL345DataRate::HZ200;  // 0.10 Hz to 3200 Hz
```

The range and resolution settings share one register. Each setter changes only its own bits.

| Mode | Scale |
|---|---|
| Full resolution | 3.9 mg per count at every range |
| 10-bit, ±2 g | 3.9 mg per count |
| 10-bit, ±4 g | 7.8 mg per count |
| 10-bit, ±8 g | 15.6 mg per count |
| 10-bit, ±16 g | 31.3 mg per count |

`scale()`, `acceleration()` and the axis readers always use the chip's current mode, so switching mode never needs a code change elsewhere.

Setting a data rate writes the whole rate register, which leaves low-power mode off.

## Power

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345SleepSamplingRate;

$adxl->measurement_mode = false;   // standby
$adxl->measurement_mode = true;    // measuring again

$adxl->sleep_mode = true;
$adxl->sleep_rate = ADXL345SleepSamplingRate::SLEEP_8HZ;   // 8, 4, 2 or 1 Hz while asleep
$adxl->link_mode = true;
```

Each of these reads the power register and rewrites it with one field changed. To set every field at once, write a breakout:

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345PowerControl;

$adxl->power_control = new ADXL345PowerControl(measurement_mode: true, sleep_mode: false);
```

## Offsets and calibration

The offset registers hold one signed byte per axis, at 15.6 mg per step, from −128 to 127. The chip adds them to every sample.

```php
[$x, $y, $z] = $adxl->getOffset();
$adxl->setOffset([-6, -5, 6]);
```

`calibrate()` averages samples while the board sits still, then writes the offsets that make the resting reading 0 g on two axes and 1 g on the vertical one. It returns the offsets it wrote.

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\AxisOrientation;

$offsets = $adxl->calibrate(AxisOrientation::Z);   // Z pointing up
$offsets = $adxl->calibrate(AxisOrientation::X_INVERTED, samples: 64, delay_us: 10_000);
```

Calibration blocks for about `samples × delay_us`, which is about 0.64 s with the defaults. It adds to whatever offsets are already set, so running it again refines them. It corrects offset only, not sensitivity.

## Interrupts

The chip raises eight interrupt functions: `data_ready`, `single_tap`, `double_tap`, `activity`, `inactivity`, `free_fall`, `watermark` and `overrun`. You choose which ones fire with an `ADXL345InterruptFunctions` breakout, which line each fires on with an `ADXL345InterruptMap`, and subscribe handlers through `$adxl->interrupts()`.

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345InterruptEvent;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345InterruptFunctions;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345InterruptFunction;

$adxl->active_interrupts = new ADXL345InterruptFunctions(data_ready: true);

$adxl->interrupts()->on(ADXL345InterruptFunction::DATA_READY, function (ADXL345InterruptEvent $event) use ($adxl): void {
    $sample = $adxl->acceleration();   // reading the data clears DATA_READY
});
```

Each event carries its `function`, the INT line it fired on (`pin`, 1 or 2), and `timestamp_ns`. Use `off($function)` to drop a function's handlers, or `off($function, $handler)` to drop one.

### Wired lines and polling

For a line whose `int1` or `int2` config is enabled, the driver reads the chip's interrupt source only after the line edges, and stamps the event with the edge's time: the kernel's timestamp on Linux, the sample time on an FT232H. For a line you haven't wired, it reads the interrupt source on every check and stamps the event when it reads it. The two mix freely, so you can wire INT1 and leave INT2 polled.

A wired line with no edge costs no bus traffic. When INT_INVERT is set in `data_format`, the driver watches falling edges instead of rising ones.

Every edge on a pin lands in one queue, whoever asks for it, so `watch()`ing a wired INT line for your own use doesn't take edges away from the chip's dispatcher.

### Checking, waiting, or running on the loop

Three ways to check, all sharing one dispatcher. Use whichever fits the moment, and switch between them whenever you like.

```php
$events = $adxl->interrupts()->poll();      // check once, never wait
$events = $adxl->interrupts()->wait(500);   // block up to 500 ms until something fires

$loop = app('event-loop');
$adxl->interrupts()->every($loop, 0.01);    // check every 10 ms on the event loop
$adxl->interrupts()->stop($loop);
```

`poll()` and `wait()` return the events they dispatched; `every()` hands each check's events to your handlers. If exactly one line is in use and it is wired, `wait()` blocks on that line. Otherwise it polls every millisecond, or every `poll_interval_us` if you pass one.

`every()` registers a loop timer named `adxl345.interrupts` (`adxl343.interrupts` for the ADXL343) and returns it. Calling it again under the same name replaces the timer. To give each chip its own timer when several share a loop, name them: `$adxl->interrupts('left.adxl345')`.

### Changing them during operation

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345InterruptMap;

$adxl->active_interrupts = new ADXL345InterruptFunctions(data_ready: true, single_tap: true);
$adxl->interrupt_map = new ADXL345InterruptMap(single_tap: true);   // single tap on INT2, the rest on INT1
$adxl->active_interrupts = ADXL345InterruptFunctions::none();
```

The dispatcher follows these changes on its next check without reading them back. `$adxl->interrupt_source` reads what has fired.

### Tap, activity and free fall

These functions fire only once their thresholds and timings are set. All of them are one-byte properties, 0 to 255.

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345ActivityControl;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345TapAxes;

$adxl->tap_threshold = 0x30;                           // 62.5 mg per count
$adxl->tap_duration = 0x10;                            // 625 µs per count
$adxl->tap_latency = 0x50;                             // 1.25 ms per count, double tap
$adxl->tap_window = 0xF0;                              // 1.25 ms per count, double tap
$adxl->tap_axes = new ADXL345TapAxes(z: true);

$adxl->activity_threshold = 0x20;                      // 62.5 mg per count
$adxl->inactivity_threshold = 0x03;                    // 62.5 mg per count
$adxl->inactivity_time = 0x02;                         // 1 s per count
$adxl->activity_control = new ADXL345ActivityControl(activity_ac: true, activity_x: true, activity_y: true, activity_z: true);

$adxl->free_fall_threshold = 0x07;                     // 62.5 mg per count
$adxl->free_fall_time = 0x14;                          // 5 ms per count
```

### How the chip clears them

Reading the interrupt source clears `single_tap`, `double_tap`, `activity`, `inactivity` and `free_fall`. `data_ready`, `watermark` and `overrun` clear only when you read data. Until your handler reads the data, a polled line reports `data_ready` on every check, and a wired line stays asserted with no new edge.

One interrupt source read covers both lines. The driver therefore dispatches every enabled function it reports at once, including functions routed to a wired line that hasn't edged yet.

## Properties

Every property reads from or writes to the chip.

| Property | Read | Write | Type |
|---|---|---|---|
| `device_id` | ✓ | | `int` |
| `acceleration` | ✓ | | `array{x: float, y: float, z: float}` |
| `raw` | ✓ | | `array{x: int, y: int, z: int}` |
| `x`, `y`, `z` | ✓ | | `float` |
| `fsr` | ✓ | ✓ | `ADXL345Range` |
| `full_resolution` | ✓ | ✓ | `bool` |
| `data_format` | ✓ | ✓ | `ADXL345DataFormat` |
| `data_rate` | ✓ | ✓ | `ADXL345DataRate` |
| `power_control` | ✓ | ✓ | `ADXL345PowerControl` |
| `measurement_mode` | ✓ | ✓ | `bool` |
| `sleep_mode` | ✓ | ✓ | `bool` |
| `sleep_rate` | ✓ | ✓ | `ADXL345SleepSamplingRate` |
| `link_mode` | ✓ | ✓ | `bool` |
| `active_interrupts` | ✓ | ✓ | `ADXL345InterruptFunctions` |
| `interrupt_map` | ✓ | ✓ | `ADXL345InterruptMap` |
| `interrupt_source` | ✓ | | `ADXL345InterruptFunctions` |
| `tap_threshold`, `tap_duration`, `tap_latency`, `tap_window` | ✓ | ✓ | `int` |
| `tap_axes` | ✓ | ✓ | `ADXL345TapAxes` |
| `activity_threshold`, `inactivity_threshold`, `inactivity_time` | ✓ | ✓ | `int` |
| `activity_control` | ✓ | ✓ | `ADXL345ActivityControl` |
| `free_fall_threshold`, `free_fall_time` | ✓ | ✓ | `int` |

Each property has a matching method, such as `getRange()` and `setRange()`, or `getDataFormat()` and `setDataFormat()`.

## Errors

Failures throw `DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException`, which extends the framework's `CircuitException`:

- The chip does not answer `0xE5` at boot.
- The chip takes no sample within two sample periods of entering measurement mode.
- The bus refuses a read, or a read comes back short.
- A factory cannot connect its bus or an INT pin, is given an SPI clock outside 1 Hz to 5 MHz, or finds the SPI bus open in a mode other than 3.
- Your code reads or writes a property that doesn't exist.
- A one-byte register property is set outside 0 to 255.

```php
try {
    $adxl = app('circuit')->conjure('adxl345');
} catch (ADXL34xException $e) {
    // "Invalid ADXL34x Device Chip ID — expected 229, got 0"
}
```

## Closing

```php
$adxl->close();
```

`close()` releases the interrupt pins. The bus connection belongs to the protocol driver and stays open for other devices on it.

## ADXL343

The ADXL343 shares the ADXL345's register map and device id, and the package gives it the same API under its own names:

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums\ADXL343Range;

$adxl = app('circuit')->conjure('adxl343');
$adxl->fsr = ADXL343Range::G8;
```

Its enums and breakouts are `ADXL343Range`, `ADXL343DataRate`, `ADXL343PowerControl`, `ADXL343DataFormat` and so on. Its interrupt dispatcher is `ADXL343Interrupts`, with its loop timer named `adxl343.interrupts`. Its config lives under `circuits.adxl343`.

## Configuration

`config/circuits/adxl345.php` has the following shape, and `adxl343.php` has the same one. Each entry under `configs` holds the arguments of the factory it is named after; set `protocol` to name an entry something else, such as `left` and `right` beside `spi`.

| Key | Default | Meaning |
|---|---|---|
| `default_config` | `'i2c'` | which entry under `configs` `conjure()` uses |
| `configs.i2c.driver` | `'none'` | I2C adapter: `native` or `usb` |
| `configs.i2c.device` | `''` | bus number, or `ft232h` |
| `configs.i2c.slave` | `0x53` | chip address |
| `configs.spi.driver` | `'none'` | SPI adapter |
| `configs.spi.device` | `''` | SPI bus |
| `configs.spi.chip_select` | `0` | chip select |
| `configs.spi.speed` | `5_000_000` | this chip select's clock in Hz, up to 5 MHz |
| `configs.*.int1` / `int2` | disabled | `enabled`, `driver`, `device`, `pin` for each interrupt line |

## Upgrading from 0.8

| 0.8 | 0.10 |
|---|---|
| `scrapyard-io/framework` 0.8 components | the 0.10 components |
| `I2C::driver(...)`, `SPI::driver(...)`, `DigitalIO::driver(...)` | `app('circuit')->conjure()`, the `i2c()` / `spi()` factories, or `app('gpio.i2c')->driver(...)` |
| the package merged config but never read it | `conjure()` builds the chip from it |
| `interrupts()->every(IOPool::gpio(), ticks: 2)` → `Recurrence` | `interrupts()->every($loop, 0.01)` → loop `Timer`; `interrupts()->stop($loop)` |
| `GPIO::every()` / `GPIO::defer()` dock sampling | the event loop: `$loop->every()`, or the I2C/SPI transport's `via()` |
| the first read after boot could return zeros or a stale sample | boot waits for a fresh sample |
| `ADXL345SleepSamplingRate::SLEEP_3HZ` (which set 1 Hz) | `SLEEP_1HZ` |

## Testing

```bash
composer install
vendor/bin/pest
```

The suite runs against recording fake buses and pins, and a real event loop, so it needs no hardware. Both chips were also exercised on hardware for this release: an ADXL343 on a Raspberry Pi 5's I2C bus with INT1 on GPIO24, and an ADXL345 on an FT232H's SPI with both INT lines wired.

## Security

The driver reads and writes registers on hardware the PHP process can open. See [SECURITY.md](SECURITY.md) for the support policy and how to report a vulnerability.

## License

MIT. See [LICENSE](LICENSE).
