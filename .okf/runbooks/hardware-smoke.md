---
type: Runbook
title: Hardware smoke
description: Proving a change on the two benches — Pi 5 ADXL343 over I2C, FT232H ADXL345 over SPI — with scratch scripts booted through the real providers.
tags: [hardware, smoke, raspberry-pi, ft232h, i2c, spi]
status: draft
generated: { by: claude-opus/5.5, at: 2026-10-04T17:49:45Z }
sources:
  - id: factories
    resource: src/Concerns/ConjuresADXL34x.php
    title: ConjuresADXL34x
  - id: interrupts
    resource: src/ADXL345/ADXL345Interrupts.php
    title: ADXL345Interrupts
---

# Rule

Pest suite stays hardware-free. Chips are proven by scratch scripts outside the repo, never committed.

# Benches

| Bench | Chip | Bus | INT |
|---|---|---|---|
| Raspberry Pi 5 (`fnk`), driver `native` | ADXL343 | I2C bus 1, 0x53 | INT1 → GPIO24 (gpiochip 0); INT2 not wired |
| Mac + FT232H (0403:6014), driver `usb` | ADXL345 | SPI, `chip_select` 0 = D4 (GPIO0) | INT1 → D5 (pin 1), INT2 → D6 (pin 2) |

FT232H breakout's I2C switch ties D1 to D2: off for SPI.

# Scratch project

Composer project outside the repo: this package by path repo, the adapter (`microscrap/scrapyard-linux` or `microscrap/scrapyard-usb`), `gpio/digital`, `gpio/i2c`, `gpio/spi`, `venusian-voyager/io-pools`, `venusian-voyager/config`. A package or extension version not yet on Packagist → path repo / `--ignore-platform-req` in the scratch project only. Extension 0.10 not installed system-wide → build it in scratch, run `php -n -d extension=<scratch>/modules/<ext>.so`.

Boot like an app: a stub `FrameworkCore` container with `config`, `registerInstance(Loop::class, $loop)`, then `register()` + `boot()` of `I2CServiceProvider`, `SPIServiceProvider`, `DigitalIOServiceProvider`, `UARTServiceProvider`, `PWMServiceProvider`, `IntegratedCircuitsServiceProvider`, the adapter's provider, `ADXL34xServiceProvider`. Define `config()` over the container's repository if the scratch project has no Venusian core (`CircuitRegistry::conjure()` calls it). Then `app('circuit')->conjure('adxl343' | 'adxl345')`.

Pi copy: `COPYFILE_DISABLE=1 tar --no-mac-metadata --no-xattrs --exclude vendor --exclude .git -czf - … | fnk 'tar -xzf - -C ~/adxl-smoke'`; remove `~/adxl-smoke` after.

# Checks

1. `conjure()` → `hasBooted()`, `device_id` 0xE5, `acceleration()` non-zero right after boot (first-sample wait).
2. `data_rate = HZ12p5`; route DATA_READY to INT1, then INT2 (`interrupt_map`); count rising edges on each wired pin while reading `raw()` every 10 ms. Each line edges only its own pin.
3. `interrupts()->wait(1000)` × 5, reading `raw()` after each: event on the routed line, gaps ≈ 80 ms.
4. `interrupts()->every($loop, 0.005)` + DATA_READY handler reading `raw()`, `$loop->at(2.0, stop)`, `run()`: ≈ 25 events.
5. `active_interrupts = none()`, `close()`.

Expected numbers: [interrupts live reference](/interrupts.md#live-reference).

# Related

* [interrupts](/interrupts.md) · [connecting](/connecting.md)
