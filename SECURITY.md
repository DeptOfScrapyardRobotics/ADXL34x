# Security Policy

## Supported versions

adxl34x is pre-1.0. No 0.x release receives security fixes or advisories; fixes land in the
next release line. Security support starts with 1.0.

| Version | Security fixes |
|---------|----------------|
| < 1.0   | No             |

## Reporting a vulnerability

Please don't open a public issue for a security problem.

Report it privately through GitHub: the **Report a vulnerability** button on this repository's
**Security** tab. If that isn't available, email **info@projectsaturnstudios.com**.

Include what you found, the affected version, the adapter and hardware in use, and steps to
reproduce. Reports are read and weighed for the release line in development; before 1.0 there is
no response-time commitment.

## Security model

adxl34x is plain PHP. It reads and writes the ADXL343 / ADXL345 registers through the bus and pin
transports `scrapyard-io/framework` hands it, and holds no native code of its own. What a PHP
process may touch is decided below it: the adapter (`microscrap/scrapyard-linux` over ext-posi,
`microscrap/scrapyard-usb` over ext-ftdi) and the operating system's permissions on the I2C, SPI,
GPIO or USB device. Grant those through device groups or udev rules scoped to the hardware, not by
running PHP as root.

- **Configuration is trusted input.** `conjure()` connects whatever bus, chip select and pins the
  `circuits.adxl343` / `circuits.adxl345` config names. Keep that config under the app's control.
- **Register writes are checked.** One-byte register properties refuse values outside 0 to 255,
  the SPI factory refuses clocks above the chip's 5 MHz, and a refused or short bus read throws
  instead of returning partial data.

A report is in scope when this package writes a register or a bus frame it was not asked to, or
lets a well-formed call corrupt the chip's configuration. Weaknesses in an adapter or extension
belong to that package's own policy.
