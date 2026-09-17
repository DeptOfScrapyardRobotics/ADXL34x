---
type: Guide
title: Connecting an ADXL34x
description: Build the chip over I2C or SPI from the framework's protocol managers, with usb or native adapters, or from the published wiring config.
tags: [i2c, spi, transport, wiring, mpsse, spidev]
status: draft
generated: { by: claude-opus-5/claude-code, at: "2026-09-16T00:00:00Z" }
sources:
  - id: i2c-transport
    resource: src/Transports/ADXL34xI2CTransport.php
    title: ADXL34xI2CTransport
  - id: spi-transport
    resource: src/Transports/ADXL34xSPITransport.php
    title: ADXL34xSPITransport
  - id: address
    resource: src/Enums/ADXL34xI2CAddress.php
    title: ADXL34xI2CAddress
---

# Shape

MagicAlias (`I2C::` / `SPI::` / `DigitalIO::`) → `driver()` → `connectTo()` → `register()` → `device()` → wrap in ADXL transport → chip.

# I2C

Address by SDO pin: `SDO_GROUNDED` 0x53, `SDO_ENERGIZED` 0x1D.[^address]

```php
$slave = I2C::driver('usb')->connectTo('ft232h')->register()->device('ft232h', 0x53);
$slave = I2C::driver('native')->connectTo(1)->register()->device(1, 0x53);

$adxl = new ADXL345(new ADXL34xI2CTransport($slave), boot_now: true);
```

Read = `writeRead([register], n)`. Write = `write([register, ...bytes])`. False or short reply → `ADXL34xException`.[^i2c-transport]

# SPI

4-wire, mode 3, ≤ 5 MHz.

```php
$spi = SPI::driver('native')->connectTo(0)->mode(3)->speed(2_000_000)->register()->device(0, 0);
$adxl = new ADXL345(new ADXL34xSPITransport($spi), boot_now: true);
```

Read address byte = `0x80 | register`, plus `0x40` when > 1 byte; full-duplex `transfer()`, reply after first byte. Write sets `0x40` when > 1 data byte.[^spi-transport]

# From config

Package merges wiring, never opens connections from it. App reads it:

```php
$wiring = config('circuits.adxl345.configs.'.config('circuits.adxl345.default_config'));
$slave = I2C::driver($wiring['driver'])->connectTo($wiring['device'])->register()->device($wiring['device'], $wiring['slave']);
```

`connectTo()` throws on second call for same device; connect once, reuse driver: `I2C::driver('usb')->device('ft232h', 0x53)`.

# INT pins

Transport ctor takes `?DigitalInTransport $int1, $int2`. See [interrupts](/interrupts.md).

# Related

* [configuration](/configuration.md) · [overview](/overview.md)

[^i2c-transport]: ADXL34xI2CTransport
[^spi-transport]: ADXL34xSPITransport
[^address]: ADXL34xI2CAddress
