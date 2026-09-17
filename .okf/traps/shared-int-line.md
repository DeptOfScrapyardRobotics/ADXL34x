---
type: Trap
title: One INT line, one consumer
description: The dispatcher drains the wired INT line's edge buffer; also watching that line on the gpio dock splits the edges between them.
tags: [trap, interrupts, io-pools, edges]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: interrupts
    resource: src/ADXL345/ADXL345Interrupts.php
    title: ADXL345Interrupts::poll()
---

# Trap

`poll()` calls `pollEdges()` on the wired line → consumes buffered edges.[^interrupts] `GPIO::watch($int1)` does same each tick. Both on one line → each sees some edges, misses others.

# Use instead

`$adxl->interrupts()->every(IOPool::gpio())` for dock-driven interrupts; no `GPIO::watch` on that line.

[^interrupts]: ADXL345Interrupts::poll()
