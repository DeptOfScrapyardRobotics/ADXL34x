---
type: Reference
title: Configuration
description: circuits.adxl343 / circuits.adxl345 keys, how conjure() reads them, publish tag.
resource: config/adxl345.php
tags: [config, circuits, conjure, publish]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-04T17:49:45Z }
sources:
  - id: provider
    resource: src/Providers/ADXL34xServiceProvider.php
    title: ADXL34xServiceProvider
  - id: config
    resource: config/adxl345.php
    title: config/adxl345.php
---

# Merge + publish

`register()`: `config/adxl343.php` → `circuits.adxl343`, `config/adxl345.php` → `circuits.adxl345`. `array_merge(package, app)` at that key: app's `configs` replaces the package's whole.[^provider]

`boot()`: tag `adxl34x-config` → `config/circuits/adxl343.php`, `config/circuits/adxl345.php`; catalog `adxl343` / `adxl345` when `circuit` bound.

```bash
php computer vendor:publish --tag=adxl34x-config
```

# Schema

Both files same shape.[^config] Each `configs` entry = arguments of the factory it names. Entry key = factory (`i2c`, `spi`) unless entry sets `protocol`, so an app keeps `left` / `right` beside `spi`. Unknown keys dropped by the catalog; a factory param without default missing from the entry → `CircuitException`.

| Key | Type | Default |
|---|---|---|
| `default_config` | string | `'i2c'` |
| `configs.i2c.driver` | string | `'none'` |
| `configs.i2c.device` | string\|int | `''` |
| `configs.i2c.slave` | int | `0x53` |
| `configs.spi.driver` | string | `'none'` |
| `configs.spi.device` | string\|int | `''` |
| `configs.spi.chip_select` | int | `0` |
| `configs.spi.speed` | int Hz | `5_000_000` |
| `configs.{i2c,spi}.int1` / `int2` | `{enabled: bool, driver, device, pin: int}` | disabled, pins 0 / 1 |
| `configs.*.boot_now` | bool | factory default `true` |

Driver `none` (package default) → the framework's none driver throws on connect: app must fill in its bench. See [connecting](/connecting.md).

# Related

* [connecting](/connecting.md) · [overview](/overview.md)

[^provider]: ADXL34xServiceProvider
[^config]: config/adxl345.php
