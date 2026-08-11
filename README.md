# dept-of-scrapyard-robotics/adxl34x (0.7)

I2C/SPI drivers for ADXL343 / ADXL345. Extends `GeneralPurposeIO\Circuits\SensorIC`.

## Register

Provider registers catalog slugs `adxl343`, `adxl345` and wires `adxl34x:make-profile` into `circuit:make-profile`.

## Profiles

```bash
workshop vendor:publish --tag=gpio-circuits-config
workshop circuit:make-profile          # picks any installed IC; ADXL34x delegates here
workshop adxl34x:make-profile          # ADXL343 / ADXL345 only
```

The command asks I2C or SPI adapter/device (plus slave or chip select) from `#[Pinout]`, and always sets `boot_now => true`.

SPI profile/factory params use `spi_device` / `spi_adapter` (not bare `device` / `adapter`).

```php
Circuit::profile('imu_board');
```

## Smoke sketch

Requires at least one ADXL34x profile in `config/circuits.php`:

```bash
php workshop runner adxl34x-smoke
php workshop runner adxl34x-smoke --profile=imu_board
```

Provisions only via `Circuit::profile()` — prints X/Y/Z g until you Ctrl-C.
