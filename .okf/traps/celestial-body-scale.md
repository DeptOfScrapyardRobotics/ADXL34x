---
type: Trap
title: acceleration() is m/s² only with TERRA
description: acceleration(CelestialBody) multiplies g-counts by the chosen body's surface gravity; the sensor measures in standard g, so only TERRA yields m/s².
tags: [trap, units, acceleration]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: bootstrap
    resource: src/ADXL345/Concerns/ADXL345Bootsrap.php
    title: ADXL345Bootsrap
  - id: body
    resource: src/Enums/CelestialBody.php
    title: CelestialBody
---

# Trap

Result = counts × scale × `$body->gravity()`.[^bootstrap] Counts × scale already = acceleration in standard g (9.8067 m/s²). `TERRA` gravity 9.8067 → m/s².[^body] Any other body → value in units of that body's surface g × its gravity, not m/s².

# Use instead

Default `acceleration()` for m/s². `raw()` × `scale()` for g.

[^bootstrap]: ADXL345Bootsrap
[^body]: CelestialBody
