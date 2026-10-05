# Agent guidelines — dept-of-scrapyard-robotics/adxl34x

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from the Composer dist via `.gitattributes` `export-ignore`). Before changing code or advising on this package: read [`.okf/index.md`](.okf/index.md) first, open only the concepts the task needs, prefer `status: stable` over `draft`. When you learn something durable, update the affected concept(s), bump `generated.at`, and append [`.okf/log.md`](.okf/log.md); new or changed concepts stay `status: draft` until a human verifies them. The bundle documents the package, never a session.

Do **not** create `.okf` folders under `src/*` — knowledge for this package lives at the package root only. Catalog, transport, loop and adapter semantics belong to `scrapyard-io/framework`'s and Venusian's bundles; point there, do not restate them here.

## Where this package sits

`ext-posi` / `ext-ftdi` → `microscrap/*` → `scrapyard-io/framework` (protocol managers, transports, the circuit catalog) → **`dept-of-scrapyard-robotics/adxl34x`** (chip driver) → apps and Surface.

## Package rules (quick) — 0.10.x

- Composer: `dept-of-scrapyard-robotics/adxl34x` **0.10.0**. PHP `^8.4|^8.5|^8.6`. Namespace `DeptOfScrapyardRobotics\Sensors\ADXL34x\` → `src/`.
- **Requires split components only**: `gpio/contracts`, `gpio/integrated-circuits`, `gpio/nuts-and-bolts`, `venusian-voyager/contracts`, `venusian-voyager/nuts-and-bolts`, `venusian-voyager/vessel`. Never `scrapyard-io/framework` or `venusian/framework`. Protocol components, io-pools and adapters are `suggest`; requires follow imports (and helper functions such as `byte2bits()`).
- **Two chips, one register map.** `ADXL345/` and `ADXL343/` are the same tree with names swapped; a change to one is a change to both, tests included. Device id `0xE5` for both.
- **Chip = `Bootable` + `Sensor`.** Boot confirms DEVID, enters measurement mode, waits for DATA_READY (first fresh sample), applies the constructor's interrupt mask. `close()` releases INT pins only; bus connections belong to their driver.
- **Factories are the config shape.** `ConjuresADXL34x` gives both chips `i2c()` and `spi()`, whose parameters are exactly a `circuits.<chip>.configs.*` entry's keys; the provider catalogs `adxl343` and `adxl345`, so `app('circuit')->conjure()` builds a booted chip. A new config key = a new factory parameter, and the reverse. Bus first, INT pins after.
- **SPI is mode 3, ≤ 5 MHz.** `spi()` opens with `SPIMode::MODE_3`, clocks its chip select through the transport's `speed()`, refuses a shared bus in another mode.
- **Interrupts** go through `$adxl->interrupts()`: wired INT line → edge-driven, unwired → INT_SOURCE polled; `poll()` / `wait()` / `every(Loop)` share one dispatcher. Routing (enable mask, INT_MAP, INT_INVERT) is cached and refreshed by its setters and getters — never write those registers around them. See `.okf/interrupts.md`.
- **Transports** wrap a framework `I2CTransport` / `SPITransport` behind `ReadWriter`. A refused or short read throws `ADXL34xException`, never returns `false`.
- **Register breakouts** are `readonly` `DataRegister`s (`toBits` / `fromByte` / `none`); read-modify-write through `with*()` copies so sibling bits survive.
- **Scale follows the chip**: `scale()` reads DATA_FORMAT; full resolution = 3.9 mg/LSB at every range, 10-bit = the range's step. Nothing caches it.
- **Reach the framework through the container.** 0.10 has no protocol aliases or facades. Factories resolve `gpio.i2c` / `gpio.spi` / `gpio.digital` from `ControlPanel::getInstance()`; apps call `app('circuit')->conjure()`.
- **Config** merges under `circuits.adxl343` / `circuits.adxl345`; publish tag `adxl34x-config` → `config/circuits/*.php`. Package defaults are `driver => 'none'`.
- **Exceptions** descend from `GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException` → `GPIOLevelException`.
- Enums int-backed, FULLY UPPERCASE cases. No class constants (limits live in enums such as `ADXL34xSPIClock`). `is_null($x)` over `$x === null`.

## Verification

```bash
vendor/bin/pest            # fake buses and pins, a real EventLoop; no hardware
```

Run it under NTS and ZTS PHP before every commit. Suites stay hardware-free: chips are for scratch smoke scripts, never committed and never in `tests/`.

Hardware truth: an ADXL343 at `0x53` on a Raspberry Pi 5's I2C bus 1 with INT1 on GPIO24 (gpiochip 0), and an ADXL345 on an FT232H over SPI with chip select on GPIO0 (D4), INT1 on GPIO1 (D5), INT2 on GPIO2 (D6). A skipped or faked read is not evidence for a register the chip has never answered.
