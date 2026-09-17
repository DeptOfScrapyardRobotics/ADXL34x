<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use GeneralPurposeIO\Contracts\IntegratedCircuits\ReadWriter;
use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;
use GeneralPurposeIO\Contracts\NutsAndBolts\Splices16Bits;

abstract class ADXL34xDataTransport implements ReadWriter
{
    use Splices16Bits;

    public function __construct(
        protected ?DigitalInTransport $int1 = null,
        protected ?DigitalInTransport $int2 = null,
    ) {}

    abstract protected function closeMain(): void;
    abstract protected function getData(int $register, int $length): array;
    abstract protected function sendData(int $register, array $data): int;

    /** The input transport wired to INT1 or INT2, or null when that line is not wired. */
    public function interruptPin(int $pin): ?DigitalInTransport
    {
        return match ($pin) {
            1 => $this->int1,
            2 => $this->int2,
            default => throw ADXL34xException::invalidInterruptPin($pin),
        };
    }

    public function read(int $register, int $length): array
    {
        return $this->getData($register, $length);
    }

    public function write(int $register, array $data): int
    {
        return $this->sendData($register, $data);
    }

    /** Release the interrupt pins, then whatever the carrier holds. The bus connection itself belongs to its driver and stays open. */
    public function close(): void
    {
        $this->int1?->close();
        $this->int2?->close();
        $this->closeMain();
    }
}