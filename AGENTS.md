# Agent guidelines — dept-of-scrapyard-robotics/adxl34x

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from the Composer dist via `.gitattributes` `export-ignore`). Before changing code or advising on this package: read [`.okf/index.md`](.okf/index.md) first, open only the concepts the task needs, prefer `status: stable` over `draft`. When you learn something durable, update the affected concept(s) and append [`.okf/log.md`](.okf/log.md); new or changed concepts stay `status: draft` until a human verifies them.

Do **not** create `.okf` folders under `src/*` — knowledge for this package lives at the package root only. Registry, transport and dock semantics belong to `scrapyard-io/framework`'s bundle; point there, do not restate them here.

## Where this package sits

`ext-posi` / `ext-ftdi` → `microscrap/*` → `scrapyard-io/framework` (protocol managers, transports, the `gpio` dock resource) → **`dept-of-scrapyard-robotics/adxl34x`** (chip driver) → apps and Surface.

## Package rules (quick) — 0.8.x

- Composer: `dept-of-scrapyard-robotics/adxl34x` **0.8.0**. PHP `^8.4|^8.5|^8.6`. Namespace `DeptOfScrapyardRobotics\Sensors\ADXL34x\` → `src/`.
- **Requires split components only**: `gpio/contracts`, `gpio/integrated-circuits`, `gpio/nuts-and-bolts`, `venusian-voyager/nuts-and-bolts`. Never `scrapyard-io/framework` or `venusian/framework`. Protocol components and adapters are `suggest`.
- **Two chips, one register map.** `ADXL345/` and `ADXL343/` are the same tree with names swapped; a change to one is a change to both. Device id `0xE5` for both.
- **Chip = `Bootable` + `Sensor`.** Boot confirms DEVID, enters measurement mode, applies the constructor's interrupt mask. `close()` releases INT pins only; bus connections belong to their driver.
- **Interrupts** go through `$adxl->interrupts()` (`ADXL345Interrupts`): wired INT line → edge-driven, unwired → INT_SOURCE polled; `poll()` / `wait()` / `every()` share one dispatcher. Routing (enable mask, INT_MAP, INT_INVERT) is cached and refreshed by its setters and getters — never write those registers around them. See `.okf/interrupts.md`.
- **Transports** wrap a framework `I2CTransport` / `SPITransport` behind `ReadWriter`. A refused or short read throws `ADXL34xException`, never returns `false`.
- **Register breakouts** are `readonly` `DataRegister`s (`toBits` / `fromByte` / `none`); read-modify-write through `with*()` copies so sibling bits survive.
- **Scale follows the chip**: `scale()` reads DATA_FORMAT; full resolution = 3.9 mg/LSB at every range, 10-bit = the range's step. Nothing caches it.
- **Reach the framework through MagicAliases** (`I2C::`, `SPI::`, `DigitalIO::`, `GPIO::`, `IOPool::`), never `app('gpio.*')`.
- **Config** merges under `circuits.adxl343` / `circuits.adxl345`; publish tag `adxl34x-config` → `config/circuits/*.php`. The package reads none of it.
- **Exceptions** descend from `GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException` → `GPIOLevelException`.
- Enums int-backed, FULLY UPPERCASE cases. No class constants. `is_null($x)` over `$x === null`.

## Verification

```bash
vendor/bin/pest            # scripted fake bus; no hardware
```

Hardware truth is an ADXL345 at `0x53` on an FT232H (`I2C::driver('usb')`). A skipped or faked read is not evidence for a register the chip has never answered.
