<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Adapters;

use Waveforms\Carriers\SPI\SPIDevice;

class ADL345SPIAdapter extends ADXL345DataCarrier
{
    public function __construct(
        SPIDevice $carrier
    ) {
        parent::__construct($carrier);
    }

    public function read(int $register_hex, int $length): array
    {
        // Bit7 = 1 (read), Bit6 = MB for burst reads
        $addr_byte = 0x80 | ($register_hex & 0x3F);
        if ($length > 1) {
            $addr_byte |= 0x40;
        }

        /** @var SPIDevice $carrier */
        $carrier = &$this->carrier;
        $tx = array_merge([$addr_byte], array_fill(0, $length, 0x00));
        $rx = $carrier->transfer($tx);

        return array_slice($rx, 1, $length);
    }

    public function write(int $register_hex, array $command_data = []): int
    {
        $addr_byte = $register_hex & 0x3F;
        if (count($command_data) > 1) {
            $addr_byte |= 0x40;
        }
        $payload = [$addr_byte, ...$command_data];

        return $this->carrier->write($payload);
    }
}
