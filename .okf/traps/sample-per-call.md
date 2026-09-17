---
type: Trap
title: Axis readers each take a sample
description: x(), y(), z() and their properties each run a full acceleration() read, so three calls give three different instants and six bus transactions.
tags: [trap, reading, transactions]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: bootstrap
    resource: src/ADXL345/Concerns/ADXL345Bootsrap.php
    title: ADXL345Bootsrap
---

# Trap

`$adxl->x; $adxl->y; $adxl->z;` = 3 samples, 6 transactions (DATA_FORMAT + DATAX0 each). Axes from different moments.[^bootstrap] Same for `getRawX/Y/Z` (3 × `raw()`).

# Use instead

`acceleration()` or `raw()` → all three axes, one burst read.

[^bootstrap]: ADXL345Bootsrap
