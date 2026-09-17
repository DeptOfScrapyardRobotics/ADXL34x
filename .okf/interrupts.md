---
type: Guide
title: Interrupts
description: Subscribe to the chip's interrupt functions through $adxl->interrupts() — edge-driven on wired INT lines, INT_SOURCE polling on unwired ones, blocking wait() or dock every() interchangeably.
tags: [interrupts, int1, int2, data-ready, tap, activity, free-fall, io-pools]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
revised: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z", note: "dispatcher, INT_MAP, INT_SOURCE, event registers" }
sources:
  - id: interrupts
    resource: src/ADXL345/ADXL345Interrupts.php
    title: ADXL345Interrupts
  - id: event
    resource: src/ADXL345/ADXL345InterruptEvent.php
    title: ADXL345InterruptEvent
  - id: function
    resource: src/ADXL345/Enums/ADXL345InterruptFunction.php
    title: ADXL345InterruptFunction
  - id: api
    resource: src/ADXL345/Concerns/ADXL345API.php
    title: ADXL345API — routing cache, INT_MAP, INT_SOURCE, event registers
  - id: transport
    resource: src/Transports/ADXL34xDataTransport.php
    title: ADXL34xDataTransport::interruptPin()
  - id: tests
    resource: tests/ADXL345/ADXL345InterruptsTest.php
    title: interrupt tests
---

# Pieces

| Piece | Role |
|---|---|
| `ADXL345InterruptFunction` | enum, one case per bit: `DATA_READY` 7 … `OVERRUN` 0; `property()` → breakout field[^function] |
| `ADXL345InterruptFunctions` | INT_ENABLE (0x2E) and INT_SOURCE (0x30) shape; `has()`, `functions()` bit 7 first |
| `ADXL345InterruptMap` | INT_MAP (0x2F); set bit → INT2, clear → INT1; `pinFor()` → 1 \| 2 |
| `ADXL345InterruptEvent` | readonly `function`, `pin`, `timestamp_ns`[^event] |
| `ADXL345Interrupts` | dispatcher; one per chip via `$adxl->interrupts(?string $name)`[^interrupts] |
| `ADXL34xDataTransport::interruptPin(1\|2)` | wired `DigitalInTransport` or null; 3+ throws[^transport] |

# Subscribe

`on(fn, Closure)` stacks handlers; `off(fn)` drops all, `off(fn, handler)` drops one; `handlers(fn)`. What fires = enable mask (`active_interrupts`); which line = `interrupt_map`.

# One check (poll)

1. routing = enable mask + map, cached. Enable empty → return, zero reads.
2. routed lines = pins of enabled functions.
3. each wired routed line → `pollEdges(!active_low, active_low)`; edge → remember its timestamp.
4. no unwired routed line and no edge → return, zero reads.
5. else one INT_SOURCE read → every fired **enabled** function → event (pin from map, timestamp = that line's edge or `hrtime`) → handlers inline.[^interrupts]

Returns events dispatched.

# Cache

API trait keeps `_interrupt_enable`, `_interrupt_map`, `_interrupt_active_low`. Setters + getters of INT_ENABLE, INT_MAP, DATA_FORMAT refresh them. First use reads each once; INT_MAP only once something enabled; DATA_FORMAT only once a routed line wired.[^api] Changes through chip properties apply next check.

# wait(timeout_ms, poll_interval_us = 1_000)

Loop: `poll()`; events → return. Exactly one routed line and it wired → `listen(remaining ms)` on it; edge → INT_SOURCE → dispatch. Else `usleep(min(interval, remaining))`. Deadline → `[]`.

# every(GPIOResourceDriver $gpio, int $ticks = 1)

`$gpio->every($this->name, fn () => $this->poll(), $ticks)` → `Recurrence`. Default name `adxl345.interrupts` (`adxl343.interrupts`). Completion result = events.

poll / wait / every share handlers + dispatcher → interleave freely.[^tests]

# Event registers

| Property | Register | Unit |
|---|---|---|
| `tap_threshold` | 0x1D | 62.5 mg |
| `tap_duration` | 0x21 | 625 µs |
| `tap_latency` | 0x22 | 1.25 ms; 0 disables double tap |
| `tap_window` | 0x23 | 1.25 ms; 0 disables double tap |
| `activity_threshold` | 0x24 | 62.5 mg |
| `inactivity_threshold` | 0x25 | 62.5 mg |
| `inactivity_time` | 0x26 | 1 s |
| `free_fall_threshold` | 0x28 | 62.5 mg; 0x05–0x09 recommended |
| `free_fall_time` | 0x29 | 5 ms; 0x14–0x46 recommended |
| `activity_control` | 0x27 | `ADXL345ActivityControl`: act ac, act X/Y/Z, inact ac, inact X/Y/Z |
| `tap_axes` | 0x2A | `ADXL345TapAxes`: suppress, X, Y, Z |

Int properties: 0–255 else `registerOutOfRange`.[^api] Tap / activity / free fall never fire at power-on zeros.

# Live reference

FT232H, INT1 unwired, 12.5 Hz, DATA_READY + handler reading data: `wait()` returned once per sample, 75–81 ms apart; dock `every()` 5 samples / 400 ms, interleaved with `wait()`; disabling mid-run silenced handler.

# Related

* [traps/level-interrupts-repeat](/traps/level-interrupts-repeat.md) · [traps/shared-int-line](/traps/shared-int-line.md) · [chip-settings](/chip-settings.md) · [dock-sampling](/dock-sampling.md)

[^interrupts]: ADXL345Interrupts
[^event]: ADXL345InterruptEvent
[^function]: ADXL345InterruptFunction
[^api]: ADXL345API — routing cache, INT_MAP, INT_SOURCE, event registers
[^transport]: ADXL34xDataTransport::interruptPin()
[^tests]: interrupt tests
