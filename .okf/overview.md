---
type: Package
title: dept-of-scrapyard-robotics/adxl34x
description: ADXL343 and ADXL345 accelerometer drivers for scrapyard-io/framework 0.8 — identity, requires, classes, boot, errors.
resource: composer.json
tags: [adxl343, adxl345, accelerometer, i2c, spi, package]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
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
| Composer | `dept-of-scrapyard-robotics/adxl34x` **0.8.0** |
| PHP | `^8.4\|^8.5\|^8.6` |
| Namespace | `DeptOfScrapyardRobotics\Sensors\ADXL34x\` → `src/` |
| Provider | `Providers\ADXL34xServiceProvider` (`extra.venusian.providers`) |

# Requires

Split components only.[^composer]

| Package | Why |
|---|---|
| `gpio/contracts` | transport, sensor, digital-in contracts |
| `gpio/integrated-circuits` | `Bootable`, `DataRegister` |
| `gpio/nuts-and-bolts` | `byte2bits()` in breakouts |
| `venusian-voyager/nuts-and-bolts` | `ServiceProvider` |

Suggests: `gpio/i2c`, `gpio/spi`, `gpio/digital`, `microscrap/scrapyard-usb` (ext-ftdi), `microscrap/scrapyard-linux` (ext-posi).

# Classes

| Class | Role |
|---|---|
| `ADXL345\ADXL345`, `ADXL343\ADXL343` | chip; extends `Bootable`, implements `Sensor` |
| `Transports\ADXL34xI2CTransport` | wraps `I2CTransport`; register byte then payload |
| `Transports\ADXL34xSPITransport` | wraps `SPITransport`; 0x80 read bit, 0x40 multi-byte bit |
| `ADXL345\Breakouts\{PowerControl, DataFormat, InterruptFunctions}` | readonly register breakouts |
| `ADXL345\Enums\{Range, DataRate, SleepSamplingRate, OpCode}` | typed register values |
| `Enums\ADXL34xI2CAddress` | `SDO_GROUNDED` 0x53, `SDO_ENERGIZED` 0x1D |
| `Enums\AxisOrientation` | vertical axis for `calibrate()` |
| `Enums\CelestialBody` | gravity factor for `acceleration()` |

ADXL343 tree = ADXL345 tree, names swapped. Same register map, same device id.

# Construct + boot

`new ADXL345(ADXL34xDataTransport $transport, ADXL345InterruptFunctions $_starting_int_fns = new ..., bool $boot_now = false)`[^adxl345]

`boot()` runs once:[^bootstrap]

1. read DEVID, throw unless `0xE5`
2. POWER_CTL ← measurement on, sleep off, link off, wakeup 8 Hz
3. INT_ENABLE ← constructor mask (default none)

`close()` → transport releases INT pins; bus connection stays with its driver.

# Errors

`ADXL34xException` extends `CircuitException` extends `GPIOLevelException`.[^exception]

| Factory | When |
|---|---|
| `invalidChipId` | DEVID ≠ 0xE5 at boot |
| `readFailed` | bus refused read |
| `shortRead` | fewer bytes than asked |
| `invalidProperty` | unknown magic property |

# Related

* [connecting](/connecting.md) · [reading](/reading.md) · [chip-settings](/chip-settings.md) · [configuration](/configuration.md)

[^composer]: Package manifest
[^adxl345]: ADXL345
[^bootstrap]: ADXL345Bootsrap
[^exception]: ADXL34xException
