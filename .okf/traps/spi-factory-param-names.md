---
type: Trap
title: SPI factory param names
description: ADXL34x SPI factory and Circuit profiles require spi_device / spi_adapter — not bare device / adapter.
tags: [traps, spi, profiles, factories]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T00:35:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: adxl343
    resource: src/ADXL343/ADXL343.php
    title: ADXL343::spi signature
  - id: adxl345
    resource: src/ADXL345/ADXL345.php
    title: ADXL345::spi signature
  - id: transport-enum
    resource: scrapyard-io/gpio-framework CircuitTransport SPI adapter/device prefixes
    title: CircuitTransport SPI prefixes (gpio-framework)
---

# Trap

`#[Pinout]` SPI roles are labeled `driver` / `device` / `chip_select`, but the **factory named args** (and therefore `circuits.php` `params` + fluent parity) are prefixed:

| Pinout role | Factory / profile param |
|-------------|-------------------------|
| `driver` | **`spi_adapter`** |
| `device` | **`spi_device`** |
| `chip_select` | `chip_select` |

Do **not** write SPI profiles with bare `device` / `adapter` — `Circuit::profile()` / `PendingCircuit` invoke `::spi(...)` via reflection named args and will miss the SPI-prefixed parameters.[^adxl343][^adxl345]

# Correct shape

```php
ADXL345::spi(
    spi_device: 'ft232h',
    chip_select: 0,
    spi_adapter: 'usb',
);

// circuits.php params (SPI protocol)
'params' => [
    'spi_device' => 'ft232h',
    'chip_select' => 0,
    'spi_adapter' => 'usb',
    'boot_now' => true,
],
```

I2C stays unprefixed: `device`, `adapter`, `slave`.[^adxl343]

# Related

* [ADXL34x ICs](../core/adxl34x.md)
* [Circuits integration](../core/circuits.md)

[^adxl343]: ADXL343::spi signature
[^adxl345]: ADXL345::spi signature
[^transport-enum]: CircuitTransport SPI prefixes (sibling package)
