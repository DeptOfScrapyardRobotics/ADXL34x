---
type: Guide
title: Interrupts
description: Subscribe to the chip's interrupt functions through $adxl->interrupts() — edge-driven on wired INT lines, INT_SOURCE polling on unwired ones, poll(), blocking wait() or an event-loop timer interchangeably.
tags: [interrupts, int1, int2, data-ready, tap, activity, free-fall, event-loop]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-04T17:49:45Z }
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
  - id: digital-input
    resource: vendor/gpio/digital/DigitalInputTransport.php
    title: gpio/digital DigitalInputTransport — one unread-edge queue per pin
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

Returns events dispatched. Edge timestamp: kernel CLOCK_MONOTONIC on Linux, MPSSE sample time on FT232H (10 ms sampling → event gaps jitter ± one sample).

# Cache

API trait keeps `_interrupt_enable`, `_interrupt_map`, `_interrupt_active_low`. Setters + getters of INT_ENABLE, INT_MAP, DATA_FORMAT refresh them. First use reads each once; INT_MAP only once something enabled; DATA_FORMAT only once a routed line wired.[^api] Changes through chip properties apply next check.

# wait(timeout_ms, poll_interval_us = 1_000)

Loop: `poll()`; events → return. Exactly one routed line and it wired → `listen(remaining ms)` on it; edge → INT_SOURCE → dispatch. Else `usleep(min(interval, remaining))`. Deadline → `[]`. With a loop bound to the pin's driver, the pin's `listen()` suspends a fiber or borrows the loop (framework behaviour).

# every(Loop $loop, float $interval_s = 0.01): Timer

`$loop->every($interval_s, fn () => $this->poll(), $this->name)`. Default name `adxl345.interrupts` / `adxl343.interrupts`; `interrupts('left.adxl345')` renames, so two chips keep two timers. Same name again replaces the timer (loop registry). `stop(Loop)` → `$loop->forget($name)`. Handlers are the delivery; the timer drops poll()'s return value.[^interrupts]

App loop: `app('event-loop')`. Wired line → a tick reads nothing unless the line edged.

poll / wait / every share handlers + dispatcher → interleave freely.[^tests]

# Clearing

INT_SOURCE read clears single tap, double tap, activity, inactivity, free fall. DATA_READY, watermark, overrun clear only on data read.

- polled line, handler not reading data → `data_ready` event every check
- wired line, data unread → line stays asserted → no new edge → no further events

DATA_READY handler reads data (`acceleration()` / `raw()`). One INT_SOURCE read serves both lines, so the dispatcher emits every enabled fired function, including ones routed to a wired line not yet edged. Latched functions: once. Level functions: may come again on that line's edge if data still unread.

# Shared INT line

gpio/digital 0.10 keeps one unread-edge queue per pin; `pollEdges()` / `listen()` take from it, `watch()` mails a copy and leaves it queued.[^digital-input] App can `watch()` a wired INT line without starving the dispatcher. (0.8 dock: both drained the same edges.)

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

12.5 Hz, DATA_READY + handler reading data:

| Bench | Routing | wait() gaps | every() 2 s |
|---|---|---|---|
| Pi 5, ADXL343 I2C, INT1 → GPIO24 | INT1: 7 edges / 600 ms; INT2: 0 on GPIO24 | 78.6 ms steady | 25 events |
| FT232H, ADXL345 SPI, INT1 → D5, INT2 → D6 | each line edges only its own pin | 68–83 ms (sampled) | 26 events |

# Related

* [chip-settings](/chip-settings.md) · [connecting](/connecting.md) · [hardware smoke](/runbooks/hardware-smoke.md)

[^interrupts]: ADXL345Interrupts
[^event]: ADXL345InterruptEvent
[^function]: ADXL345InterruptFunction
[^api]: ADXL345API — routing cache, INT_MAP, INT_SOURCE, event registers
[^transport]: ADXL34xDataTransport::interruptPin()
[^tests]: interrupt tests
[^digital-input]: gpio/digital DigitalInputTransport — one unread-edge queue per pin
