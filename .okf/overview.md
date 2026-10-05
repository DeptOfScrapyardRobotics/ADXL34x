---
type: Package
title: dept-of-scrapyard-robotics/adxl34x
description: ADXL343 and ADXL345 accelerometer drivers for scrapyard-io/framework 0.10 — identity, requires, classes, boot, errors.
resource: composer.json
tags: [adxl343, adxl345, accelerometer, i2c, spi, package]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-04T17:49:45Z }
sources:
  - id: composer
    resource: composer.json
    title: Package manifest
  - id: adxl345
    resource: src/ADXL345/ADXL345.php
    title: ADXL345
  - id: bootstrap
    resource: src/ADXL345/Concerns/ADXL345Bootsrap.php
    title: ADXL345Bootsrap
  - id: exception
    resource: src/ADXL34xException.php
    title: ADXL34xException
  - id: provider
    resource: src/Providers/ADXL34xServiceProvider.php
    title: ADXL34xServiceProvider
---

# Identity

| Field | Value |
|---|---|
| Composer | `dept-of-scrapyard-robotics/adxl34x` **0.10.0**, branch alias `dev-main` → `0.10.x-dev` |
| PHP | `^8.4\|^8.5\|^8.6` |
| Namespace | `DeptOfScrapyardRobotics\Sensors\ADXL34x\` → `src/` |
| Provider | `Providers\ADXL34xServiceProvider` (`extra.venusian.providers`) |

# Requires

Split components only.[^composer]

| Package | Why |
|---|---|
| `gpio/contracts` | transport, sensor, digital-in contracts, `SPIMode`, `Splices16Bits` |
| `gpio/integrated-circuits` | `Bootable`, `DataRegister` |
| `gpio/nuts-and-bolts` | `byte2bits()` in breakouts |
| `venusian-voyager/contracts` | `Loop`, `Timer` for `interrupts()->every()` |
| `venusian-voyager/nuts-and-bolts` | `ServiceProvider` |
| `venusian-voyager/vessel` | `ControlPanel::getInstance()` in the factories |

Suggests: `gpio/i2c`, `gpio/spi`, `gpio/digital`, `venusian-voyager/io-pools`, `microscrap/scrapyard-linux` (ext-posi), `microscrap/scrapyard-usb` (ext-ftdi).

# Classes

| Class | Role |
|---|---|
| `ADXL345\ADXL345`, `ADXL343\ADXL343` | chip; extends `Bootable`, implements `Sensor`; uses `ConjuresADXL34x` |
| `Concerns\ConjuresADXL34x` | `i2c()` / `spi()` factories. See [connecting](/connecting.md) |
| `Transports\ADXL34xI2CTransport` | wraps `I2CTransport`; register byte then payload |
| `Transports\ADXL34xSPITransport` | wraps `SPITransport`; 0x80 read bit, 0x40 multi-byte bit |
| `ADXL345\ADXL345Interrupts`, `ADXL345InterruptEvent` | dispatcher and event. See [interrupts](/interrupts.md) |
| `ADXL345\Breakouts\*` | readonly register breakouts: power, data format, interrupt functions/map, tap axes, activity control |
| `ADXL345\Enums\*` | `Range`, `DataRate`, `SleepSamplingRate`, `OpCode`, `InterruptFunction` |
| `Enums\ADXL34xI2CAddress` | `SDO_GROUNDED` 0x53, `SDO_ENERGIZED` 0x1D |
| `Enums\ADXL34xSPIClock` | `MAX_HZ` 5 MHz |
| `Enums\AxisOrientation` | vertical axis for `calibrate()` |
| `Enums\CelestialBody` | gravity factor for `acceleration()` |

ADXL343 tree = ADXL345 tree, names swapped. Same register map, same device id.

# Construct + boot

`new ADXL345(ADXL34xDataTransport $transport, ADXL345InterruptFunctions $_starting_int_fns = new ..., bool $boot_now = false)`. Factories pass `boot_now: true` unless told otherwise.[^adxl345]

`boot()` runs once:[^bootstrap]

1. read DEVID, throw `invalidChipId` unless `0xE5`
2. POWER_CTL ← measurement on, sleep off, link off, wakeup 8 Hz
3. read DATAX0..Z1 once (clears leftover DATA_READY), read BW_RATE, read INT_SOURCE until DATA_READY; budget 2 / ODR + 2 ms, else `noFirstSample`
4. INT_ENABLE ← constructor mask (default none)

Step 3: data registers hold zeros after power-up, or the last sample before standby, until the first conversion lands 1/ODR + 1.1 ms after MEASURE. DATA_READY sets whatever INT_ENABLE says. Without the wait the first read after boot was stale; proven on the Pi ADXL343 (zeros right after `conjure()`).

`close()` → transport releases INT pins; bus connection stays with its driver.

# Errors

`ADXL34xException` extends `CircuitException` extends `GPIOLevelException`.[^exception]

| Factory | When |
|---|---|
| `invalidChipId` | DEVID ≠ 0xE5 at boot |
| `noFirstSample` | no DATA_READY within budget at boot |
| `readFailed` | bus refused read |
| `shortRead` | fewer bytes than asked |
| `invalidProperty` | unknown magic property |
| `registerOutOfRange` | one-byte property outside 0–255 |
| `invalidInterruptPin` | `interruptPin()` not 1 or 2 |
| `notConnected` | factory could not connect bus or INT pin device |
| `spiClockOutOfRange` | `spi()` speed outside 1 Hz – 5 MHz |
| `wrongSpiMode` | shared SPI bus opened in a mode other than 3 |

# Related

* [connecting](/connecting.md) · [reading](/reading.md) · [chip-settings](/chip-settings.md) · [interrupts](/interrupts.md) · [configuration](/configuration.md)

[^composer]: Package manifest
[^adxl345]: ADXL345
[^bootstrap]: ADXL345Bootsrap
[^exception]: ADXL34xException
