---
type: Configuration
title: Wiring config
description: circuits.adxl343 and circuits.adxl345 config keys, how the provider merges them, and the adxl34x-config publish tag.
resource: config/adxl345.php
tags: [config, circuits, publish, provider]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: provider
    resource: src/Providers/ADXL34xServiceProvider.php
    title: ADXL34xServiceProvider
  - id: config
    resource: config/adxl345.php
    title: adxl345 config
---

# Merge + publish

`register()`: `config/adxl343.php` → `circuits.adxl343`, `config/adxl345.php` → `circuits.adxl345`. App values win.[^provider]

`boot()`: tag `adxl34x-config` → `config/circuits/adxl343.php`, `config/circuits/adxl345.php`. Venusian loader keys nested dirs as dotted keys → same keys as merge; `config/circuits.php` loads first, nested files layer in.

```bash
php computer vendor:publish --tag=adxl34x-config
```

# Schema

Both files same shape.[^config]

| Key | Type | Default |
|---|---|---|
| `default_config` | string | `'i2c'` |
| `configs.i2c.driver` | string | `'none'` |
| `configs.i2c.device` | string\|int | `''` |
| `configs.i2c.slave` | int | `0x53` |
| `configs.spi.driver` | string | `'none'` |
| `configs.spi.device` | string\|int | `''` |
| `configs.spi.chip_select` | int | `0` |
| `configs.{i2c,spi}.int1` / `int2` | `{enabled: bool, driver, device, pin: int}` | disabled, pins 0 / 1 |

Package reads none of it; app wires chip from it — see [connecting](/connecting.md).

# Related

* [overview](/overview.md)

[^provider]: ADXL34xServiceProvider
[^config]: adxl345 config
