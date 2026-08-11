---
type: Trap
title: Fabricate leftovers
description: ADXL34x 0.7 uses GeneralPurposeIO Circuits and local enums — not Fabricate Circuits/sensor contracts.
tags: [traps, fabricate, circuits, sensors]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T00:35:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: adxl343
    resource: src/ADXL343/ADXL343.php
    title: ADXL343 imports
  - id: power
    resource: src/ADXL343/Breakouts/ADXL343PowerControl.php
    title: DataRegister breakout
  - id: internal
    resource: src/ADXL343/Concerns/ADXL343InternalAPI.php
    title: BootScaffolding + Splices16Bits
  - id: axis
    resource: src/Enums/AxisOrientation.php
    title: Local AxisOrientation
---

# Trap

Do **not** import or revive:

- `Fabricate\Contracts\Circuits\*`
- `Fabricate\Circuits\DataRegister` / Fabricate boot scaffolding
- Fabricate sensor contracts for axis / gravity / orientation (this package keeps local enums)

# Use instead

| Concern | Correct FQCN |
|---------|----------------|
| Taxonomy base | `GeneralPurposeIO\Circuits\SensorIC` |
| Attributes / BootSequence | `GeneralPurposeIO\Contracts\Circuits\Attributes\*`, `BootSequence`, `BootScaffolding` |
| Bit helpers | `GeneralPurposeIO\Circuits\DataRegister` |
| Circuit alias | `GeneralPurposeIO\Core\MagicAliases\Circuit` |
| 16-bit splice helpers | `Fabricate\NutsAndBolts\Concerns\Splices16Bits` (**OK** — Nab moon, not Circuits leftover) |
| Axis / gravity | `DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\{AxisOrientation,CelestialBody}` |

`ADXL343` / `ADXL345` already wire GeneralPurposeIO Circuits types; InternalAPI + carrier transport intentionally use Nab `Splices16Bits`.[^adxl343][^power][^internal][^axis]

# Related

* [ADXL34x ICs](../core/adxl34x.md)
* [Circuits integration](../core/circuits.md)

[^adxl343]: ADXL343 imports
[^power]: DataRegister breakout
[^internal]: BootScaffolding + Splices16Bits
[^axis]: Local AxisOrientation
