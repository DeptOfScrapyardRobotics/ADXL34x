# Log

## 2026-10-04

* **Update**: 0.10 port. [overview](/overview.md): requires the 0.10 split components plus `venusian-voyager/contracts` and `vessel`; boot waits for the first fresh sample; new exceptions.
* **Creation**: [connecting](/connecting.md) rewritten for `conjure()` and the `i2c()` / `spi()` factories (SPI mode 3, ≤ 5 MHz, shared buses, INT pins after the bus).
* **Update**: [interrupts](/interrupts.md): `every(Loop, interval)` loop timer and `stop()` replace the dock recurrence; shared-INT-line note resolved by gpio/digital's per-pin queue; live reference from both benches.
* **Update**: [chip-settings](/chip-settings.md): `SLEEP_3HZ` renamed `SLEEP_1HZ`. [configuration](/configuration.md): `spi.speed`, `protocol`, conjure semantics.
* **Deprecation**: `dock-sampling` removed: 0.10 has no dock. The 0.8 bundle's separate warning notes folded into [reading](/reading.md), [chip-settings](/chip-settings.md) and [interrupts](/interrupts.md).
* **Creation**: [hardware smoke](/runbooks/hardware-smoke.md) runbook.
