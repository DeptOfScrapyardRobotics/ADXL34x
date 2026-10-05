<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support;

use Closure;
use GeneralPurposeIO\SPI\SPITransport;

/** Records every write and transfer; answers transfers from a queue of scripted replies, MISO bytes in full. */
final class FakeSPITransport extends SPITransport
{
    /** @var list<list<int>> */
    public array $writes = [];

    /** @var list<list<int>> */
    public array $transfers = [];

    /** @var list<list<int>|false> */
    public array $replies = [];

    public bool $released = false;

    public function handle(): string
    {
        return 'fake';
    }

    public function read(int $len): array|false
    {
        return array_fill(0, $len, 0);
    }

    public function write(array|string $data): int
    {
        $bytes = is_array($data) ? array_values($data) : bytes2array($data);
        $this->writes[] = $bytes;

        return count($bytes);
    }

    public function transfer(array|string $data): array|false
    {
        $bytes = is_array($data) ? array_values($data) : bytes2array($data);
        $this->transfers[] = $bytes;

        return array_shift($this->replies) ?? false;
    }

    public function writeRead(array|string $bytes_to_write, int $bytes_to_read): array|false
    {
        return array_fill(0, $bytes_to_read, 0);
    }

    public function speed(int $hz): static
    {
        $this->hz = $hz;

        return $this;
    }

    protected function beginSelection(): void {}

    protected function endSelection(): void {}

    protected function release(): void
    {
        $this->released = true;
    }
}
