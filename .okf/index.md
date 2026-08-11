---
okf_version: "0.2"
---

# dept-of-scrapyard-robotics/adxl34x Knowledge Bundle

Package knowledge for `dept-of-scrapyard-robotics/adxl34x` (ADXL343 / ADXL345 accelerometer drivers, v0.7.x).
Read this index first; open only the concepts needed for the task.

**Trust rule:** Prefer `status: stable`. Treat `deprecated` as historical only. New agent-written concepts stay `status: draft` until a human verifies them.
**Placement:** Package-root `.okf/` only — never under `src/`.
**Links:** Concept cross-links use paths relative to each file.
**Scope:** This package’s IC surface, Circuits catalog registration, profiles, and smoke sketch. Registry semantics live in `scrapyard-io/gpio-framework` — do not duplicate that bundle here.
**Dist note:** `.okf/` and root `AGENTS.md` are `export-ignore` in `.gitattributes`.

# Orientation

* [Package (0.7)](orientation/package.md) - Composer identity, namespace, provider, dependencies.

# Core

* [ADXL34x ICs](core/adxl34x.md) - SensorIC classes, attributes, I2C/SPI factories, breakouts, local enums.
* [Circuits integration](core/circuits.md) - Catalog slugs, make-profile, profiles, smoke sketch.

# Traps

* [Fabricate leftovers](traps/fabricate-leftovers.md) - Use GeneralPurposeIO Circuits; Nab `Splices16Bits` is OK; no Fabricate sensor contracts.
* [SPI factory param names](traps/spi-factory-param-names.md) - SPI uses `spi_device` / `spi_adapter`, not bare `device` / `adapter`.

# Log

* [Directory update log](log.md)
