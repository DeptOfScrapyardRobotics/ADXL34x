# adxl34x

Drive ADXL343 and ADXL345 three-axis accelerometers from PHP over I2C or SPI, using the ScrapyardIO GPIO framework.

`dept-of-scrapyard-robotics/adxl34x` wraps each chip's register map in a typed class. You connect a bus through `scrapyard-io/framework`, hand the connection to the chip, and read acceleration in m/s² or raw counts. Range, resolution, data rate, power, offsets and interrupts are all typed settings, and sampling can run on the framework's IOPool dock.

## Requirements

- PHP 8.4 or newer
- A Venusian application with `scrapyard-io/framework` 0.8
- An adapter for your hardware:
  - `microscrap/scrapyard-usb` for FTDI MPSSE boards such as the FT232H (needs `ext-ftdi`)
  - `microscrap/scrapyard-linux` for native `i2c-dev`, `spidev` and `libgpiod` (needs `ext-posi`)

## Installation

```bash
composer require dept-of-scrapyard-robotics/adxl34x
```

The service provider is discovered automatically. It merges the package's wiring config under `circuits.adxl343` and `circuits.adxl345`. To publish that config into your app, run:

```bash
php computer vendor:publish --tag=adxl34x-config
```

That writes `config/circuits/adxl343.php` and `config/circuits/adxl345.php`.

## Quick start

An ADXL345 at `0x53` on an FT232H:

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\ADXL34xI2CAddress;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xI2CTransport;
use GeneralPurposeIO\I2C\I2C;

$slave = I2C::driver('usb')
    ->connectTo('ft232h')
    ->register()
    ->device('ft232h', ADXL34xI2CAddress::SDO_GROUNDED->value);

$adxl = new ADXL345(new ADXL34xI2CTransport($slave), boot_now: true);

$adxl->acceleration();
// ['x' => 0.038, 'y' => 0.0, 'z' => 9.944]   m/s², board lying flat
```

Booting checks the device id (`0xE5`), switches the chip into measurement mode, and applies the interrupt mask you passed to the constructor, which is none by default.

## Connecting

The chip takes an `ADXL34xI2CTransport` or an `ADXL34xSPITransport`. Each wraps a connection from the framework's protocol managers.

### I2C

The address depends on the SDO/ALT pin:

| `ADXL34xI2CAddress` | Address | SDO |
|---|---|---|
| `SDO_GROUNDED` | `0x53` | tied low |
| `SDO_ENERGIZED` | `0x1D` | tied high |

```php
use GeneralPurposeIO\I2C\I2C;

// FTDI MPSSE
$slave = I2C::driver('usb')->connectTo('ft232h')->register()->device('ft232h', 0x53);

// Linux i2c-dev, bus 1
$slave = I2C::driver('native')->connectTo(1)->register()->device(1, 0x53);

$adxl = new ADXL345(new ADXL34xI2CTransport($slave), boot_now: true);
```

### SPI

The ADXL34x uses 4-wire SPI in mode 3, at up to 5 MHz.

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xSPITransport;
use GeneralPurposeIO\SPI\SPI;

// Linux spidev0.0
$spi = SPI::driver('native')
    ->connectTo(0)
    ->mode(3)
    ->speed(2_000_000)
    ->register()
    ->device(0, 0);

$adxl = new ADXL345(new ADXL34xSPITransport($spi), boot_now: true);
```

### From the published config

The config file holds your wiring. The package merges it but does not open connections from it, so read it where you build the chip:

```php
use GeneralPurposeIO\I2C\I2C;

$name = config('circuits.adxl345.default_config');      // 'i2c'
$wiring = config("circuits.adxl345.configs.{$name}");

$slave = I2C::driver($wiring['driver'])
    ->connectTo($wiring['device'])
    ->register()
    ->device($wiring['device'], $wiring['slave']);
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
$z_in_g = $raw['z'] * $adxl->scale();   // ≈ 1.0 lying flat
```

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
$adxl->sleep_rate = ADXL345SleepSamplingRate::SLEEP_8HZ;
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

The chip raises eight interrupt functions: `data_ready`, `single_tap`, `double_tap`, `activity`, `inactivity`, `free_fall`, `watermark` and `overrun`. You choose which ones fire with an `ADXL345InterruptFunctions` breakout, and subscribe handlers through `$adxl->interrupts()`.

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345InterruptEvent;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345InterruptFunctions;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345InterruptFunction;

$adxl = new ADXL345(
    new ADXL34xI2CTransport($slave),
    new ADXL345InterruptFunctions(data_ready: true),
    boot_now: true,
);

$adxl->interrupts()->on(ADXL345InterruptFunction::DATA_READY, function (ADXL345InterruptEvent $event) use ($adxl): void {
    $sample = $adxl->acceleration();   // reading the data clears DATA_READY
});
```

Each event carries its `function`, the INT line it fired on (`pin`, 1 or 2), and `timestamp_ns`. Use `off($function)` to drop a function's handlers, or `off($function, $handler)` to drop one.

### Wired lines and polling

Pass the INT lines you've wired to the transport. For a wired line, the driver reads the chip's interrupt source only after the line edges. For a line you haven't wired, it reads the interrupt source on every check instead. The two mix freely, so you can wire INT1 and leave INT2 polled.

```php
use GeneralPurposeIO\Digital\DigitalIO;

$int1 = DigitalIO::driver('native')->connectTo(0)->register()->input(0, 17);

$adxl = new ADXL345(
    new ADXL34xI2CTransport($slave, int1: $int1),
    new ADXL345InterruptFunctions(data_ready: true),
    boot_now: true,
);
```

A wired line with no edge costs no bus traffic. Edge timestamps come from the line; polled events are stamped when the interrupt source is read. When INT_INVERT is set in `data_format`, the driver watches falling edges instead of rising ones.

### Blocking or ticking

Three ways to check, all sharing one dispatcher. Use whichever fits the moment, and switch between them whenever you like.

```php
use Voyager\IOPools\MagicAliases\IOPool;

$events = $adxl->interrupts()->poll();      // check once, never wait
$events = $adxl->interrupts()->wait(500);   // block up to 500 ms until something fires

$recurrence = $adxl->interrupts()->every(IOPool::gpio());   // check on every dock tick
$recurrence->stop();
```

Each call returns the events it dispatched. If exactly one line is in use and it is wired, `wait()` blocks on that line. Otherwise it polls every millisecond, or every `poll_interval_us` if you pass one. `every()` takes an optional `ticks` cadence, and its recurrence is named `adxl345.interrupts`. To give each chip its own recurrence when several share a dock, name them: `$adxl->interrupts('left.adxl345')`.

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

Reading the interrupt source clears `single_tap`, `double_tap`, `activity`, `inactivity` and `free_fall`. `data_ready`, `watermark` and `overrun` clear only when you read data. Until your handler reads the data, a polled line reports `data_ready` on every check, and a wired line stays high with no new edge.

One interrupt source read covers both lines. The driver therefore dispatches every enabled function it reports at once, including functions routed to a wired line that hasn't edged yet.

## Sampling on the IOPool dock

The framework's `gpio` dock resource can sample on a cadence or run a read once, without blocking your sketch's loop. Each closure runs inside a dock tick and takes as long as its bus transaction. For interrupts, use `$adxl->interrupts()->every()`, described under [Blocking or ticking](#blocking-or-ticking).

```php
use GeneralPurposeIO\Contracts\Core\Mail\TransferCompletion;
use GeneralPurposeIO\Core\MagicAliases\GPIO;

// every second tick
$sampling = GPIO::every('adxl345.sample', fn (): array => $adxl->acceleration(), ticks: 2)
    ->onEach(fn (TransferCompletion $sample) => $this->track($sample->result));

// once, on the next tick
GPIO::defer('adxl345.id', fn (): int => $adxl->device_id)
    ->onSuccess(fn (TransferCompletion $done) => $this->log($done->result));

$sampling->stop();
```

A Surface sketch's `tick()` already pumps the dock. Elsewhere, call `IOPool::pump()` (`Voyager\IOPools\MagicAliases\IOPool`) in your loop. Each run of `every()` also lands in the dock's mail, here as `gpio.transfer.adxl345.sample`.

Don't also pass an INT line the chip uses to `GPIO::watch()`. The dock and the chip would drain the same edges.

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

Failures throw `DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException`, which extends the framework's `GPIOLevelException`:

- The chip does not answer `0xE5` at boot.
- The bus refuses a read.
- A read comes back short.
- Your code reads or writes a property that doesn't exist.
- A one-byte register property is set outside 0 to 255.

```php
try {
    $adxl = new ADXL345(new ADXL34xI2CTransport($slave), boot_now: true);
} catch (ADXL34xException $e) {
    // "Invalid ADXL34x Device Chip ID — expected 229, got 0"
}
```

## Closing

```php
$adxl->close();
```

`close()` releases the interrupt pins you passed. The bus connection belongs to the protocol driver and stays open for other devices on it.

## ADXL343

The ADXL343 shares the ADXL345's register map and device id, and the package gives it the same API under its own names:

```php
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\ADXL343;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums\ADXL343Range;

$adxl = new ADXL343(new ADXL34xI2CTransport($slave), boot_now: true);
$adxl->fsr = ADXL343Range::G8;
```

Its enums and breakouts are `ADXL343Range`, `ADXL343DataRate`, `ADXL343PowerControl`, `ADXL343DataFormat` and so on. Its interrupt dispatcher is `ADXL343Interrupts`, and its dock recurrence is named `adxl343.interrupts`. Its config lives under `circuits.adxl343`.

## Configuration

`config/circuits/adxl345.php` has the following shape. `adxl343.php` has the same shape.

| Key | Default | Meaning |
|---|---|---|
| `default_config` | `'i2c'` | which entry under `configs` to use |
| `configs.i2c.driver` | `'none'` | I2C adapter: `usb` or `native` |
| `configs.i2c.device` | `''` | adapter device: `ft232h`, or a bus number |
| `configs.i2c.slave` | `0x53` | chip address |
| `configs.spi.driver` | `'none'` | SPI adapter |
| `configs.spi.device` | `''` | SPI master |
| `configs.spi.chip_select` | `0` | chip select |
| `configs.*.int1` / `int2` | disabled | `enabled`, `driver`, `device`, `pin` for each interrupt line |

## Testing

```bash
composer install
vendor/bin/pest
```

The suite runs against a recording fake bus, so it needs no hardware.

## License

MIT. See [LICENSE](LICENSE).
