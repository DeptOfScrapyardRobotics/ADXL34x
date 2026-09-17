---
type: Trap
title: Level interrupts repeat until data is read
description: DATA_READY, WATERMARK and OVERRUN clear only on a data read, so an unread sample re-reports on every polled check and blocks new edges on a wired line.
tags: [trap, interrupts, data-ready]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: interrupts
    resource: src/ADXL345/ADXL345Interrupts.php
    title: ADXL345Interrupts::dispatch()
  - id: datasheet
    resource: https://www.analog.com/media/en/technical-documentation/data-sheets/adxl345.pdf
    title: ADXL345 datasheet, INT_SOURCE
---

# Trap

INT_SOURCE read clears single tap, double tap, activity, inactivity, free fall. Data ready, watermark, overrun clear only on data read.[^datasheet]

- polled line: handler not reading data → `data_ready` event every check
- wired line: line stays asserted → no new edge → no further events

One INT_SOURCE read serves both lines, so dispatcher emits every enabled fired function, incl. ones routed to wired line not yet edged.[^interrupts] Latched functions: once. Level functions: may also come again on that line's edge if data still unread.

# Use instead

DATA_READY handler reads data (`acceleration()` / `raw()`).

[^interrupts]: ADXL345Interrupts::dispatch()
[^datasheet]: ADXL345 datasheet, INT_SOURCE
