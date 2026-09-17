<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support;

use GeneralPurposeIO\I2C\I2CTransport;

/** Records every write; answers writeRead from a queue of scripted replies. */
final class FakeI2CTransport extends I2CTransport
{
    /** @var list<list<int>> */
    public array $writes = [];

    /** @var list<array{0: list<int>, 1: int}> */
    public array $write_reads = [];

    /** @var list<list<int>|false> */
    public array $replies = [];

    public bool $closed = false;

    public function __construct(int $address = 0x53)
    {
        parent::__construct($address);
    }

    public function handle(): string
    {
        return 'fake';
    }

    public function probe(): bool
    {
        return true;
    }

    public function read(int $len): array|false
    {
        return array_shift($this->replies) ?? false;
    }

    public function write(array|string $data): int
    {
        $bytes = is_array($data) ? array_values($data) : array_values(unpack('C*', $data));
        $this->writes[] = $bytes;

        return count($bytes);
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        $bytes = is_array($bytes_to_write) ? array_values($bytes_to_write) : array_values(unpack('C*', $bytes_to_write));
        $this->write_reads[] = [$bytes, $bytes_to_read];

        return array_shift($this->replies) ?? false;
    }

    public function bulkWrite(array|string $messages): array|false
    {
        return false;
    }

    public function close(): void
    {
        $this->closed = true;
    }
}
