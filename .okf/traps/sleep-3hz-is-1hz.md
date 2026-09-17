---
type: Trap
title: SLEEP_3HZ selects 1 Hz
description: ADXL345SleepSamplingRate::SLEEP_3HZ writes wakeup bits 0b11, which the chip reads as 1 Hz sampling in sleep.
tags: [trap, power, sleep, enum]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: enum
    resource: src/ADXL345/Enums/ADXL345SleepSamplingRate.php
    title: ADXL345SleepSamplingRate
  - id: datasheet
    resource: https://www.analog.com/media/en/technical-documentation/data-sheets/adxl345.pdf
    title: ADXL345 datasheet, POWER_CTL Wakeup bits
---

# Trap

Wakeup bits D1:D0 → 00 = 8 Hz, 01 = 4 Hz, 10 = 2 Hz, 11 = 1 Hz.[^datasheet] Enum case for 0b11 named `SLEEP_3HZ`.[^enum] Choosing it gives 1 Hz sleep sampling. Same in `ADXL343SleepSamplingRate`.

[^enum]: ADXL345SleepSamplingRate
[^datasheet]: ADXL345 datasheet, POWER_CTL Wakeup bits
