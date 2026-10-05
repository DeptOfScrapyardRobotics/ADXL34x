<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support;

use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Digital\DigitalInputTransport;

/** An INT line with scripted edges: pollEdges() drains `pending`, listen() hands over `listen_edges` one at a time. */
final class FakeInterruptPin extends DigitalInputTransport
{
    public bool $closed = false;

    public bool $level = false;

    /** @var list<DigitalEdgeEvent> */
    public array $pending = [];

    /** @var list<array{bool, bool}> */
    public array $polls = [];

    /** @var list<DigitalEdgeEvent> edges only listen() hands over */
    public array $listen_edges = [];

    /** @var list<array{int, bool, bool}> timeout and edge flags handed to listen() */
    public array $listens = [];

    public function read(): bool
    {
        return $this->level;
    }

    public function pollEdges(bool $rising_events, bool $falling_events): array
    {
        $this->polls[] = [$rising_events, $falling_events];
        $out = $this->pending;
        $this->pending = [];

        return $out;
    }

    public function listen(int $timeout, bool $rising_events, bool $falling_events): ?DigitalEdgeEvent
    {
        $this->listens[] = [$timeout, $rising_events, $falling_events];

        return array_shift($this->listen_edges);
    }

    public function close(): void
    {
        $this->closed = true;
    }

    protected function drainEdges(): array
    {
        return [];
    }

    protected function awaitEdges(int $timeout_ms): void {}

    protected function edgeStreams(): array
    {
        return [];
    }

    protected function samplingInterval(): ?float
    {
        return null;
    }

    protected function release(): void
    {
        $this->closed = true;
    }
}
