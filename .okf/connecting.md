---
type: Guide
title: Connecting
description: conjure() and the i2c() / spi() factories, sharing a bus, SPI mode and clock, building the transport by hand, INT pins.
tags: [i2c, spi, conjure, factories, int1, int2]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-04T17:49:45Z }
sources:
  - id: factories
    resource: src/Concerns/ConjuresADXL34x.php
    title: ConjuresADXL34x
  - id: i2c-transport
    resource: src/Transports/ADXL34xI2CTransport.php
    title: ADXL34xI2CTransport
  - id: spi-transport
    resource: src/Transports/ADXL34xSPITransport.php
    title: ADXL34xSPITransport
  - id: provider
    resource: src/Providers/ADXL34xServiceProvider.php
    title: ADXL34xServiceProvider
  - id: tests
    resource: tests/Factories/ConjureTest.php
    title: factory and conjure tests
---

# conjure()

Provider `boot()` → `addCircuit('adxl343', ADXL343::class)`, `addCircuit('adxl345', ADXL345::class)` when `circuit` bound.[^provider] `app('circuit')->conjure('adxl345')` reads `circuits.adxl345`, takes `default_config` (or named), calls static factory named by entry key or its `protocol` with the entry's keys as named args. See [configuration](/configuration.md).

# Factories

`ConjuresADXL34x`, used by both chips:[^factories]

| Factory | Params |
|---|---|
| `i2c()` | `string $driver`, `string\|int $device`, `int $slave = 0x53`, `array $int1 = []`, `array $int2 = []`, `bool $boot_now = true` |
| `spi()` | `string $driver`, `string\|int $device`, `int $chip_select = 0`, `int $speed = 5_000_000`, `array $int1 = []`, `array $int2 = []`, `bool $boot_now = true` |

Order: bus, then INT lines, then `new static(transport, boot_now)`.

- Managers from container: `ControlPanel::getInstance()->make('gpio.i2c' | 'gpio.spi' | 'gpio.digital')->driver($driver)`. No protocol aliases in 0.10.
- Bus: `device($device, …)` first; null → `connectTo($device)->register()->device(…)`. Bus another chip or the app connected = shared as is.
- INT line array `{enabled, driver, device, pin}`: absent or `enabled` false → null. Else `input()` on that DigitalIO driver, connecting device if needed. Bus before pins: FT232H pins then ride the SPI/I2C engine's context instead of DigitalIO opening the chip in GPIO mode.
- Any connect returning null → `notConnected(protocol, driver, device)`.

# I2C

Address by SDO: `SDO_GROUNDED` 0x53, `SDO_ENERGIZED` 0x1D. Read = `writeRead([register], n)`. Write = `write([register, ...bytes])`. False or short reply → `ADXL34xException`.[^i2c-transport]

# SPI

4-wire, mode 3 (CPOL 1, CPHA 1) only, ≤ 5 MHz.

- New bus: `connectTo()->mode(SPIMode::MODE_3)->speed($speed)->register()`.
- Shared bus: `settingsOf($device)->mode` ≠ `MODE_3` → `wrongSpiMode`. Hand-registered bus (no settings) accepted.
- Always `$spi->speed($speed)` on the slave: own clock whatever the bus default.
- `speed` outside 1 – `ADXL34xSPIClock::MAX_HZ` → `spiClockOutOfRange` before any bus call.

Frame: first byte = R/W bit 7 | MB bit 6 | register 5:0. Read: `transfer([0x80|reg (|0x40 if n>1), 0×n])`, answer after first byte. Write: `write([reg (|0x40 if >1 byte), ...bytes])`. Register masked to 6 bits.[^spi-transport]

FT232H (`microscrap/scrapyard-usb`): `chip_select` = DigitalIO pin, 0–3 = D4–D7, 4–11 = C0–C7; D3 (engine CS) parked high. So CS on GPIO0 = `chip_select: 0`.

# By hand

```php
$slave = app('gpio.i2c')->driver('native')->connectTo(1)->register()->device(1, 0x53);
$adxl = new ADXL345(new ADXL34xI2CTransport($slave, int1: $pin), boot_now: true);
```

`connectTo()` throws on a second call for the same device; after the first, `driver(...)->device(...)` reuses it.

# Related

* [configuration](/configuration.md) · [interrupts](/interrupts.md) · [overview](/overview.md)

[^factories]: ConjuresADXL34x
[^i2c-transport]: ADXL34xI2CTransport
[^spi-transport]: ADXL34xSPITransport
[^provider]: ADXL34xServiceProvider
[^tests]: factory and conjure tests
