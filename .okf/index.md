---
okf_version: "0.2"
---

# dept-of-scrapyard-robotics/adxl34x — knowledge bundle

ADXL343 + ADXL345 three-axis accelerometer drivers for `scrapyard-io/framework` 0.8. I2C or SPI, typed register settings, m/s² or raw counts, IOPool dock sampling.

Read this index first, open only concepts task needs. Every concept `status: draft` until human verifies.

# Concepts

* [overview.md](/overview.md) - package identity, requires, classes, boot, errors
* [connecting.md](/connecting.md) - I2C and SPI transports over usb / native adapters, addresses, wiring from config
* [reading.md](/reading.md) - acceleration(), raw(), scale(), axis readers
* [chip-settings.md](/chip-settings.md) - range, resolution, data rate, power, offsets, calibrate, property table
* [interrupts.md](/interrupts.md) - interrupts() dispatcher: wired edges or INT_SOURCE polling, poll / wait / every, INT_MAP, event registers
* [dock-sampling.md](/dock-sampling.md) - every / defer on the gpio dock resource
* [configuration.md](/configuration.md) - circuits.adxl343 / circuits.adxl345 keys, publish tag

# Traps

* [traps/](/traps/index.md) - sample per call, SLEEP_3HZ is 1 Hz, level interrupts repeat, shared INT line, CelestialBody scaling

# Log

* [log.md](/log.md)
