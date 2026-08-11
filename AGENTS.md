# Agent guidelines — dept-of-scrapyard-robotics/adxl34x

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from Composer dist via `.gitattributes` `export-ignore`).

Before changing this package or advising on ADXL34x architecture:

1. Read [`.okf/index.md`](.okf/index.md) first (progressive disclosure).
2. Open only the linked concepts needed for the task.
3. Prefer `status: stable` concepts; treat `deprecated` as historical only. New/changed concepts stay `status: draft` until a human verifies them.
4. When you learn something durable about **this package**, update the affected `.okf` concept(s) and append `.okf/log.md`.
5. Keep the `.okf` bundle at the **package root** only — do not nest extra `.okf` folders under `src/`.
6. Circuits registry semantics belong in `scrapyard-io/gpio-framework`’s `.okf`.

## Package rules (quick) — 0.7.x

- Composer: `dept-of-scrapyard-robotics/adxl34x` **0.7.0**. Namespace `DeptOfScrapyardRobotics\Sensors\ADXL34x\`.
- Provider: `ADXL34xServiceProvider` at package root. Catalog slugs `adxl343`, `adxl345`. Command `adxl34x:make-profile` (delegated from `circuit:make-profile`). Sketch `adxl34x-smoke`.
- ICs extend `GeneralPurposeIO\Circuits\SensorIC`, implement `BootSequence`; factories `i2c(...)` / `spi(...)`.
- SPI factory/profile params: `spi_device`, `chip_select`, `spi_adapter` — not bare `device`/`adapter`.
- Breakouts use `GeneralPurposeIO\Circuits\DataRegister`; boot uses `BootScaffolding`. Nab `Splices16Bits` is OK. Local `AxisOrientation` / `CelestialBody` — no Fabricate sensor contracts.
