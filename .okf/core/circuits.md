---
type: Module
title: Circuits integration
description: Catalog registration, adxl34x:make-profile, profiles, and adxl34x-smoke sketch.
resource: src/ADXL34xServiceProvider.php
tags: [circuits, catalog, profile, smoke, workshop]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T00:35:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: provider
    resource: src/ADXL34xServiceProvider.php
    title: ADXL34xServiceProvider
  - id: catalog
    resource: src/Enums/ADXL34xCatalogIc.php
    title: ADXL34xCatalogIc
  - id: console-enum
    resource: src/Enums/ADXL34xConsoleCommand.php
    title: ADXL34xConsoleCommand
  - id: make-profile
    resource: src/Console/ADXL34xMakeProfileCommand.php
    title: adxl34x:make-profile
  - id: smoke
    resource: src/Sketches/ADXL34xSmoke.php
    title: adxl34x-smoke
---

# Role

This package **owns the ADXL343 / ADXL345 chip drivers** and registers them with gpio-framework Circuits. Registry / fluent / profile **semantics** live in `scrapyard-io/gpio-framework` — open that package’s `.okf` for `CircuitRegistry`, `PendingCircuit`, and `circuit:make-profile` behavior.

# Catalog

On `boot()`:[^provider]

```php
Circuit::addCircuit(ADXL34xCatalogIc::ADXL343->value, ADXL343::class); // 'adxl343'
Circuit::addCircuit(ADXL34xCatalogIc::ADXL345->value, ADXL345::class); // 'adxl345'

$maker = ADXL34xConsoleCommand::MAKE_PROFILE->value; // 'adxl34x:make-profile'
foreach (ADXL34xCatalogIc::cases() as $ic) {
    Circuit::registerProfileCommand($ic->value, $maker);
}
```

Slug enum: `ADXL34xCatalogIc::{ADXL343,ADXL345}` → `adxl343` / `adxl345`.[^catalog][^console-enum]

# Profiles

Publish gpio Circuits config first (from gpio-framework), then scaffold:

```bash
workshop vendor:publish --tag=gpio-circuits-config
workshop circuit:make-profile          # picks any installed IC; ADXL34x delegates here
workshop adxl34x:make-profile          # ADXL343 / ADXL345 only
```

`adxl34x:make-profile` uses `ScaffoldsCircuitProfiles` + `CircuitAttributeInspector` — prompts from `#[IntegratedCircuit]` / `#[Pinout]`, writes `config/circuits.php` with `boot_now => true`.[^make-profile]

SPI profile params must use `spi_device` / `spi_adapter` (and `chip_select`) to match `::spi(...)` — see [SPI factory param names](../traps/spi-factory-param-names.md).

```php
Circuit::profile('imu_board'); // recipe ic => adxl343|adxl345
```

# Smoke sketch

Sketch slug: `adxl34x-smoke` (`#[SketchAttribute('adxl34x-smoke')]`), registered when `SketchRegistry` is bound.[^provider][^smoke]

```bash
php workshop runner adxl34x-smoke
php workshop runner adxl34x-smoke --profile=imu_board
```

Requires at least one profile whose `ic` is `adxl343` or `adxl345`. Provisions **only** via `Circuit::profile()` — prints `X/Y/Z` g (~250 ms sample cadence) until Ctrl-C; closes the sensor on shutdown.[^smoke]

# Related

* [ADXL34x ICs](adxl34x.md)
* [Package (0.7)](../orientation/package.md)
* [SPI factory param names](../traps/spi-factory-param-names.md)

[^provider]: ADXL34xServiceProvider
[^catalog]: ADXL34xCatalogIc
[^console-enum]: ADXL34xConsoleCommand
[^make-profile]: adxl34x:make-profile
[^smoke]: adxl34x-smoke
