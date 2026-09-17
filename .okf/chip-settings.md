---
type: Reference
title: Chip settings
description: Range, resolution, data rate, power, offsets and calibration on the ADXL34x, with the full magic-property table.
tags: [range, resolution, data-rate, power, offsets, calibrate, properties]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: api
    resource: src/ADXL345/Concerns/ADXL345API.php
    title: ADXL345API
  - id: bootstrap
    resource: src/ADXL345/Concerns/ADXL345Bootsrap.php
    title: ADXL345Bootsrap
  - id: data-format
    resource: src/ADXL345/Breakouts/ADXL345DataFormat.php
    title: ADXL345DataFormat
  - id: power
    resource: src/ADXL345/Breakouts/ADXL345PowerControl.php
    title: ADXL345PowerControl
  - id: data-rate
    resource: src/ADXL345/Enums/ADXL345DataRate.php
    title: ADXL345DataRate
---

# DATA_FORMAT (0x31)

`ADXL345DataFormat`: `self_test`, `spi_3wire`, `int_invert`, `full_resolution`, `justify_left`, `range`. `withRange()` / `withResolution()` / `withIntInvert()` return copies. INT_INVERT → interrupt lines active low; dispatcher follows.[^data-format]

`setRange()` / `setResolution()` = read register, swap one field, write whole byte. Other bits kept.[^api]

# BW_RATE (0x2C)

`ADXL345DataRate` HZ0p10 … HZ3200, `hz()` → float. `setDataRate()` writes rate value only → LOW_POWER bit cleared. `getDataRate()` masks low nibble.[^data-rate][^api]

# POWER_CTL (0x2D)

`ADXL345PowerControl(link, measurement_mode, sleep_mode, wakeup)`. Ctor default `sleep_mode: true`; `none()` = all false, 8 Hz. Single-field setters (`setLinkMode`, `setMeasurementMode`, `setSleepMode`, `setWakeup`) read-modify-write.[^power][^api]

# Offsets (0x1E–0x20)

Signed byte per axis, 15.6 mg/step, −128..127. `getOffset()` → `[x, y, z]` sign-extended. `setOffset([x, y, z])` = three single-byte writes; negatives wrap to two's complement.[^api]

# calibrate()

`calibrate(AxisOrientation $vertical_axis = Z, int $samples = 32, int $delay_us = 20_000): array`[^api]

1. average `samples` `raw()` reads, `usleep(delay_us)` between → blocks ≈ samples × delay
2. measured g = mean × `scale()`; expected ±1 g on vertical axis, 0 elsewhere
3. new offset = current + round((expected − measured) / 0.0156), clamped
4. write, return `[x, y, z]`

Cumulative with existing offsets. Offset only, not sensitivity.

# Properties

Via `__get` / `__set`; unknown name → `invalidProperty`.[^bootstrap]

| Property | R | W | Method pair |
|---|---|---|---|
| `device_id` | ✓ | | `getDeviceId` |
| `acceleration`, `raw`, `x`, `y`, `z` | ✓ | | `acceleration`, `raw`, `x`… |
| `fsr` | ✓ | ✓ | `getRange` / `setRange` |
| `full_resolution` | ✓ | ✓ | `getResolution` / `setResolution` |
| `data_format` | ✓ | ✓ | `getDataFormat` / `setDataFormat` |
| `data_rate` | ✓ | ✓ | `getDataRate` / `setDataRate` |
| `power_control` | ✓ | ✓ | `getPowerControl` / `setPowerControl` |
| `measurement_mode` | ✓ | ✓ | `getMeasurementMode` / `setMeasurementMode` |
| `sleep_mode` | ✓ | ✓ | `getSleepMode` / `setSleepMode` |
| `sleep_rate` | ✓ | ✓ | `getWakeup` / `setWakeup` |
| `link_mode` | ✓ | ✓ | `getLinkMode` / `setLinkMode` |
| `active_interrupts` | ✓ | ✓ | `getEnabledInterrupts` / `setEnabledInterrupts` |
| `interrupt_map` | ✓ | ✓ | `getInterruptMap` / `setInterruptMap` |
| `interrupt_source` | ✓ | | `getInterruptSource` |
| `tap_threshold` / `tap_duration` / `tap_latency` / `tap_window` | ✓ | ✓ | `getTap*` / `setTap*` |
| `tap_axes` | ✓ | ✓ | `getTapAxes` / `setTapAxes` |
| `activity_threshold` / `inactivity_threshold` / `inactivity_time` | ✓ | ✓ | `get*` / `set*` |
| `activity_control` | ✓ | ✓ | `getActivityControl` / `setActivityControl` |
| `free_fall_threshold` / `free_fall_time` | ✓ | ✓ | `getFreeFall*` / `setFreeFall*` |

Event-register units: [interrupts](/interrupts.md#event-registers).

# Related

* [reading](/reading.md) · [interrupts](/interrupts.md) · [traps/sleep-3hz-is-1hz](/traps/sleep-3hz-is-1hz.md)

[^api]: ADXL345API
[^bootstrap]: ADXL345Bootsrap
[^data-format]: ADXL345DataFormat
[^power]: ADXL345PowerControl
[^data-rate]: ADXL345DataRate
