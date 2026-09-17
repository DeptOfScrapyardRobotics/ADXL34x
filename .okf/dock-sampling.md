---
type: Guide
title: Sampling on the gpio dock
description: Use the framework's gpio dock resource to sample an ADXL34x on a cadence or defer a read without blocking the loop; interrupts ride it through interrupts()->every().
tags: [io-pools, dock, every, defer, watch]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: resource
    resource: scrapyard-io/framework:src/GeneralPurposeIO/Core/IOPools/GPIOResourceDriver.php
    title: GPIOResourceDriver
  - id: alias
    resource: scrapyard-io/framework:src/GeneralPurposeIO/Core/MagicAliases/GPIO.php
    title: GPIO MagicAlias
---

# Verbs used

Closures run inside dock tick, cost = their bus time.[^resource]

```php
use GeneralPurposeIO\Contracts\Core\Mail\TransferCompletion;
use GeneralPurposeIO\Core\MagicAliases\GPIO;

$sampling = GPIO::every('adxl345.sample', fn (): array => $adxl->acceleration(), ticks: 2)
    ->onEach(fn (TransferCompletion $s) => /* $s->result = ['x','y','z'] */ null);
$sampling->stop();

GPIO::defer('adxl345.id', fn (): int => $adxl->device_id)
    ->onSuccess(fn (TransferCompletion $c) => /* $c->result */ null);

$adxl->interrupts()->every(IOPool::gpio());   // interrupt dispatcher on the dock
```

`GPIO` (`GeneralPurposeIO\Core\MagicAliases\GPIO`) = container `gpio` = `IOPool::gpio()` (`Voyager\IOPools\MagicAliases\IOPool`).[^alias]

# Mail

| Source | Name |
|---|---|
| `every` run | `gpio.transfer.adxl345.sample` |
| `defer` | `gpio.transfer.adxl345.id` |
| `interrupts()->every()` run | `gpio.transfer.adxl345.interrupts`, result = events |
| throwing `every` run | `gpio.fault.every.adxl345.sample`, recurrence continues |

# Pumping

Surface sketch `tick()` pumps. Elsewhere `IOPool::pump()` per loop turn.

Live: cadence 2 over 6 pumps → 3 samples, mail `gpio.transfer.adxl345.sample`.

# Related

* [interrupts](/interrupts.md) · [reading](/reading.md) · [traps/shared-int-line](/traps/shared-int-line.md)

[^resource]: GPIOResourceDriver
[^alias]: GPIO MagicAlias
