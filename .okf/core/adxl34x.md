---
type: Module
title: ADXL34x ICs
description: ADXL343 / ADXL345 SensorIC twins — attributes, I2C/SPI factories, breakouts, local enums.
resource: src/ADXL343/ADXL343.php
tags: [core, ic, sensor, i2c, spi, accelerometer]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T00:35:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: adxl343
    resource: src/ADXL343/ADXL343.php
    title: ADXL343 class
  - id: adxl345
    resource: src/ADXL345/ADXL345.php
    title: ADXL345 class
  - id: transport
    resource: src/ADXL34xCarrierTransport.php
    title: ADXL34xCarrierTransport
  - id: io
    resource: src/ADXL34xIO.php
    title: ADXL34xIO trait
  - id: internal-343
    resource: src/ADXL343/Concerns/ADXL343InternalAPI.php
    title: ADXL343InternalAPI (BootScaffolding)
  - id: api-343
    resource: src/ADXL343/Concerns/ADXL343API.php
    title: ADXL343API
  - id: power-343
    resource: src/ADXL343/Breakouts/ADXL343PowerControl.php
    title: ADXL343PowerControl DataRegister
  - id: axis
    resource: src/Enums/AxisOrientation.php
    title: AxisOrientation enum
  - id: celestial
    resource: src/Enums/CelestialBody.php
    title: CelestialBody enum
  - id: i2c-addr
    resource: src/Enums/ADXL34xI2CAddress.php
    title: ADXL34xI2CAddress enum
---

# Role

Analog Devices ADXL343 / ADXL345 3-axis accelerometer drivers. Both extend `GeneralPurposeIO\Circuits\SensorIC` and implement `BootSequence`. Parallel class trees under `ADXL343/` and `ADXL345/` share the same transport and nearly identical public API.[^adxl343][^adxl345]

# Attributes

Both ICs:

```php
#[IntegratedCircuit('I2C', 'SPI')]
#[Pinout(
    ['I2C' => ['driver', 'device', 'slave']],
    ['SPI' => ['driver', 'device', 'chip_select']],
)]
```

Pinout **roles** (`driver` / `device`) are channel labels for profile tooling. SPI factory / profile **params** use the `spi_*` prefixes — see [SPI factory param names](../traps/spi-factory-param-names.md).[^adxl343]

# Factories

| Factory | Primary args | Notes |
|---------|--------------|-------|
| `::i2c($device, $adapter, $slave, …)` | `device`, `adapter`, `slave` (default `ADXL34xI2CAddress::SDO_GROUNDED` = `0x53`) | Builds via `I2C::adapter` → `fromI2CBus` |
| `::spi($spi_device, $chip_select, $spi_adapter, …)` | **`spi_device`**, `chip_select`, **`spi_adapter`** | Mode 3 / 1 MHz; not bare `device`/`adapter` |
| `fromI2CBus` / `fromSPIBus` | Already-open `I2CSlave` / `SPIDevice` | Lower-level entry |

Default `boot_now` is `true` on factories; constructor default is `false` (boot only when requested).[^adxl343][^adxl345]

# Transport and boot

- Wire path: `ADXL34xCarrierTransport` + `ADXL34xIO` (I2C **or** SPI). Uses Nab `Fabricate\NutsAndBolts\Concerns\Splices16Bits` for register address/data helpers — that Nab concern is intentional and OK.[^transport][^io]
- Boot uses `GeneralPurposeIO\Contracts\Circuits\BootScaffolding` (`_boot()` confirms device id `0xE5`, enables measurement-mode power control, applies starting interrupt functions).[^internal-343]

# Breakouts (DataRegister)

Register-style breakouts extend `GeneralPurposeIO\Circuits\DataRegister`:

| Breakout | Purpose |
|----------|---------|
| `*PowerControl` | link / measurement / sleep / wakeup rate |
| `*InterruptFunctions` | data_ready, taps, activity, free_fall, watermark, overrun |

Per-IC copies under `ADXL343/Breakouts/` and `ADXL345/Breakouts/`.[^power-343]

# Public surface

Magic properties / API traits expose `device_id`, power/sleep controls, `fsr` (range), `data_rate`, `active_interrupts`, and `x` / `y` / `z` (g via range scale × `CelestialBody::TERRA` gravity). `calibrate(AxisOrientation $vertical_axis = …)` lives on the API traits.[^api-343][^axis][^celestial]

# Local enums (package-owned)

| Enum | Role |
|------|------|
| `AxisOrientation` | Calibration vertical axis (X/Y/Z ± inverted) |
| `CelestialBody` | Surface gravity constants (g scaling uses `TERRA`) |
| `ADXL34xI2CAddress` | `0x53` / `0x1D` from SDO |
| Per-IC `*Range`, `*DataRate`, `*OpCode`, `*SleepSamplingRate` | Chip register enums |

No Fabricate sensor contracts — orientation/gravity helpers stay local to this package.[^axis][^celestial]

# Related

* [Circuits integration](circuits.md)
* [Package (0.7)](../orientation/package.md)
* [SPI factory param names](../traps/spi-factory-param-names.md)
* [Fabricate leftovers](../traps/fabricate-leftovers.md)

[^adxl343]: ADXL343 class
[^adxl345]: ADXL345 class
[^transport]: ADXL34xCarrierTransport
[^io]: ADXL34xIO trait
[^internal-343]: ADXL343InternalAPI (BootScaffolding)
[^api-343]: ADXL343API
[^power-343]: ADXL343PowerControl DataRegister
[^axis]: AxisOrientation enum
[^celestial]: CelestialBody enum
[^i2c-addr]: ADXL34xI2CAddress enum
