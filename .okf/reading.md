---
type: Reference
title: Reading
description: acceleration(), raw(), scale(), axis readers, samples per call, CelestialBody scaling.
tags: [acceleration, raw, scale, axes]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-04T17:49:45Z }
sources:
  - id: api
    resource: src/ADXL345/Concerns/ADXL345API.php
    title: ADXL345API
  - id: range
    resource: src/ADXL345/Enums/ADXL345Range.php
    title: ADXL345Range
  - id: body
    resource: src/Enums/CelestialBody.php
    title: CelestialBody
  - id: chip
    resource: src/ADXL345/ADXL345.php
    title: ADXL345
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

One sample per call: `$adxl->x; $adxl->y; $adxl->z;` = 3 samples, 6 transactions, axes from different moments. Same for `getRawX/Y/Z`. Axes that must agree → `acceleration()` or `raw()`.[^chip]

# Scale

`scale()` reads DATA_FORMAT every call:[^api]

- FULL_RES set → `ADXL345Range::G2->scale()` = 0.0039 g/count, any range
- FULL_RES clear → range step: 0.0039 / 0.0078 / 0.0156 / 0.0313[^range]

m/s² = counts × scale × `$body->gravity()`. Counts × scale = standard g; `TERRA` (9.8067) → m/s². Another body multiplies by its own surface gravity: not the measured acceleration in m/s². For g: `raw()` × `scale()`.[^body]

# Related

* [chip-settings](/chip-settings.md) · [overview](/overview.md)

[^api]: ADXL345API
[^range]: ADXL345Range
[^body]: CelestialBody
[^chip]: ADXL345
