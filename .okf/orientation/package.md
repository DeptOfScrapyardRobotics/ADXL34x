---
type: Module
title: Package (0.7)
description: dept-of-scrapyard-robotics/adxl34x Composer identity, namespace, and discovery.
resource: composer.json
tags: [orientation, package, 0.7, adxl34x]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T00:35:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: Package composer.json
  - id: provider
    resource: src/ADXL34xServiceProvider.php
    title: ADXL34xServiceProvider
  - id: gitattributes
    resource: .gitattributes
    title: Dist export-ignore
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `dept-of-scrapyard-robotics/adxl34x` **0.7.0** |
| PHP | `^8.4\|^8.5\|^8.6` |
| Namespace | `DeptOfScrapyardRobotics\Sensors\ADXL34x\` → `src/` |
| Provider | `DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xServiceProvider` (package root, not `Providers/`) |
| Catalog slugs | `adxl343`, `adxl345` |

# Requires

| Package | Constraint |
|---------|------------|
| `scrapyard-io/gpio-framework` | `^0.7.0` |

Suggested (optional): `microscrap/i2c`, `microscrap/spi`, `microscrap/mpsse` at `^0.7.0`.[^composer]

# Discovery

`extra.scrapyard-io.providers` lists `ADXL34xServiceProvider`. That provider registers both catalog ICs, wires `adxl34x:make-profile` into `circuit:make-profile`, and registers the `adxl34x-smoke` sketch.[^provider]

# Dist

`.okf/` and `AGENTS.md` are `export-ignore` — Composer dist tarballs omit them.[^gitattributes]

# Related

* [ADXL34x ICs](../core/adxl34x.md)
* [Circuits integration](../core/circuits.md)

[^composer]: Package composer.json
[^provider]: ADXL34xServiceProvider
[^gitattributes]: Dist export-ignore
