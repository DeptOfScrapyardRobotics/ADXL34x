# dept-of-scrapyard-robotics/adxl34x Update Log

## 2026-09-16
* **Update**: [interrupts](/interrupts.md) rewritten for the `interrupts()` dispatcher (wired edges or INT_SOURCE polling, poll / wait / every, routing cache, INT_MAP, event registers); [chip-settings](/chip-settings.md) property table + `withIntInvert()`; [dock-sampling](/dock-sampling.md) drops `GPIO::watch` on INT lines.
* **Removal**: traps/latched-interrupts (driver now reads INT_SOURCE).
* **Creation**: [traps/level-interrupts-repeat](/traps/level-interrupts-repeat.md), [traps/shared-int-line](/traps/shared-int-line.md).
* **Creation**: bundle seeded for 0.8.0 — [overview](/overview.md), [connecting](/connecting.md), [reading](/reading.md), [chip-settings](/chip-settings.md), [interrupts](/interrupts.md), [dock-sampling](/dock-sampling.md), [configuration](/configuration.md), four [traps](/traps/index.md).
