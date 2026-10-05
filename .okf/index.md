---
okf_version: "0.2"
---

# dept-of-scrapyard-robotics/adxl34x

ADXL343 + ADXL345 three-axis accelerometer drivers for `scrapyard-io/framework` 0.10. I2C or SPI, conjured from config, typed register settings, m/s² or raw counts, interrupts by poll, wait or event-loop timer.

Read this index first, open only concepts the task needs. Every concept `status: draft` until a human verifies it.

# Concepts

* [Package](overview.md) - ADXL343 and ADXL345 accelerometer drivers for scrapyard-io/framework 0.10 — identity, requires, classes, boot, errors.
* [Connecting](connecting.md) - conjure() and the i2c() / spi() factories, sharing a bus, SPI mode and clock, building the transport by hand, INT pins.
* [Reading](reading.md) - acceleration(), raw(), scale(), axis readers, samples per call, CelestialBody scaling.
* [Chip settings](chip-settings.md) - Range, resolution, data rate, power, offsets and calibration on the ADXL34x, with the full magic-property table.
* [Interrupts](interrupts.md) - Subscribe to the chip's interrupt functions through $adxl->interrupts() — edge-driven on wired INT lines, INT_SOURCE polling on unwired ones, poll(), blocking wait() or an event-loop timer interchangeably.
* [Configuration](configuration.md) - circuits.adxl343 / circuits.adxl345 keys, how conjure() reads them, publish tag.

# Runbooks

* [Hardware smoke](runbooks/hardware-smoke.md) - Proving a change on the two benches — Pi 5 ADXL343 over I2C, FT232H ADXL345 over SPI — with scratch scripts booted through the real providers.

# Log

* [log.md](log.md)
