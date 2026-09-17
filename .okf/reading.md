---
type: Guide
title: Reading acceleration
description: acceleration(), raw(), scale() and the axis readers — units, transactions per call, and how scale follows the chip's mode.
tags: [acceleration, raw, scale, units]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: api
    resource: src/ADXL345/Concerns/ADXL345API.php
    title: ADXL345API
  - id: bootstrap
    resource: src/ADXL345/Concerns/ADXL345Bootsrap.php
    title: ADXL345Bootsrap
  - id: range
    resource: src/ADXL345/Enums/ADXL345Range.php
    title: ADXL345Range
---

# Calls

| Call | Returns | Bus transactions |
|---|---|---|
| `acceleration(CelestialBody $body = TERRA)` | `array{x,y,z: float}` m/s² | DATA_FORMAT + 6-byte DATAX0 |
| `raw()` | `array{x,y,z: int}` signed counts | 6-byte DATAX0 |
| `scale()` | float g per count | DATA_FORMAT |
| `x()` / `y()` / `z()` (+ props) | float m/s² | full `acceleration()` each |
| `getRawX()` / `Y` / `Z` | int | full `raw()` each |

Axes: little-endian signed 16-bit from DATAX0..DATAZ1.[^api]

# Scale

`scale()` reads DATA_FORMAT every call:[^api]

- FULL_RES set → `ADXL345Range::G2->scale()` = 0.0039 g/count, any range
- FULL_RES clear → range step: 0.0039 / 0.0078 / 0.0156 / 0.0313[^range]

m/s² = counts × scale × `CelestialBody::TERRA->gravity()` (9.8067).[^bootstrap]

# Live reference

FT232H, board flat, calibrated, ±2 g 10-bit: raw z 258 → 1.006 g → 9.944 m/s².

# Related

* [chip-settings](/chip-settings.md) · [traps/sample-per-call](/traps/sample-per-call.md) · [traps/celestial-body-scale](/traps/celestial-body-scale.md)

[^api]: ADXL345API
[^bootstrap]: ADXL345Bootsrap
[^range]: ADXL345Range
