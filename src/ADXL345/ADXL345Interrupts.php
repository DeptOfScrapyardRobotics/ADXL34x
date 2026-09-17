<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345;

use Closure;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345InterruptFunctions;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345InterruptMap;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345InterruptFunction;
use GeneralPurposeIO\Contracts\Core\GPIOResourceDriver;
use GeneralPurposeIO\Contracts\Core\Recurrence;
use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;

/**
 * Delivers the chip's interrupt functions to handlers.
 *
 * What fires is the chip's enable mask (active_interrupts); which line it
 * fires on is INT_MAP (interrupt_map). A line the transport holds an input
 * for is edge-driven: INT_SOURCE is read only after that line edges. A line
 * it does not hold is polled: INT_SOURCE is read on every poll. One
 * INT_SOURCE read dispatches every enabled function it reports.
 *
 * poll() never waits. wait() blocks. every() puts poll() on the gpio dock.
 * All three share this dispatcher, so they interleave freely.
 */
final class ADXL345Interrupts
{
    /** @var array<int, list<Closure>> keyed by ADXL345InterruptFunction value */
    protected array $handlers = [];

    public function __construct(
        protected readonly ADXL345 $chip,
        public string $name = 'adxl345.interrupts',
    ) {}

    public function on(ADXL345InterruptFunction $function, Closure $handler): static
    {
        $this->handlers[$function->value][] = $handler;

        return $this;
    }

    /** Drop one handler, or every handler for the function when none is given. */
    public function off(ADXL345InterruptFunction $function, ?Closure $handler = null): static
    {
        if (is_null($handler)) {
            unset($this->handlers[$function->value]);

            return $this;
        }

        $this->handlers[$function->value] = array_values(array_filter(
            $this->handlers[$function->value] ?? [],
            fn (Closure $registered): bool => $registered !== $handler,
        ));

        return $this;
    }

    /** @return list<Closure> */
    public function handlers(ADXL345InterruptFunction $function): array
    {
        return $this->handlers[$function->value] ?? [];
    }

    /**
     * Check once, dispatch what fired, never wait.
     *
     * @return list<ADXL345InterruptEvent>
     */
    public function poll(): array
    {
        [$enabled, $map] = $this->chip->interruptRouting();
        $lines = $this->routedLines($enabled, $map);

        if ($lines === []) {
            return [];
        }

        $edges = [];
        $unwired = false;

        foreach ($lines as $pin => $input) {
            if (is_null($input)) {
                $unwired = true;

                continue;
            }

            $active_low = $this->chip->interruptsActiveLow();
            $seen = $input->pollEdges(! $active_low, $active_low);

            if ($seen !== []) {
                $edges[$pin] = (int) $seen[0]->timestamp;
            }
        }

        if (! $unwired && $edges === []) {
            return [];
        }

        return $this->dispatch($enabled, $map, $edges);
    }

    /**
     * Block until something fires or the timeout passes.
     *
     * With exactly one routed line and that line wired, blocks in the line's
     * listen(). Otherwise polls every $poll_interval_us.
     *
     * @return list<ADXL345InterruptEvent>
     */
    public function wait(int $timeout_ms, int $poll_interval_us = 1_000): array
    {
        $deadline = hrtime(true) + ($timeout_ms * 1_000_000);

        while (true) {
            $events = $this->poll();

            if ($events !== []) {
                return $events;
            }

            $remaining_ns = $deadline - hrtime(true);

            if ($remaining_ns <= 0) {
                return [];
            }

            [$enabled, $map] = $this->chip->interruptRouting();
            $lines = $this->routedLines($enabled, $map);
            $pin = array_key_first($lines);

            if (count($lines) === 1 && ! is_null($lines[$pin])) {
                $active_low = $this->chip->interruptsActiveLow();
                $edge = $lines[$pin]->listen((int) ceil($remaining_ns / 1_000_000), ! $active_low, $active_low);

                if (! is_null($edge)) {
                    $events = $this->dispatch($enabled, $map, [$pin => (int) $edge->timestamp]);

                    if ($events !== []) {
                        return $events;
                    }
                }

                continue;
            }

            usleep((int) min($poll_interval_us, ceil($remaining_ns / 1_000)));
        }
    }

    /** Put poll() on the gpio dock; each run's completion carries that run's events. */
    public function every(GPIOResourceDriver $gpio, int $ticks = 1): Recurrence
    {
        return $gpio->every($this->name, fn (): array => $this->poll(), $ticks);
    }

    /**
     * The lines at least one enabled function is routed to, each with its
     * wired input or null.
     *
     * @return array<int, ?DigitalInTransport>
     */
    protected function routedLines(ADXL345InterruptFunctions $enabled, ADXL345InterruptMap $map): array
    {
        $lines = [];

        foreach ($enabled->functions() as $function) {
            $pin = $map->pinFor($function);
            $lines[$pin] ??= $this->chip->transport()->interruptPin($pin);
        }

        ksort($lines);

        return $lines;
    }

    /**
     * One INT_SOURCE read; every enabled function it reports becomes an event.
     *
     * @param  array<int, int>  $edges  line → edge timestamp for lines that edged this pass
     * @return list<ADXL345InterruptEvent>
     */
    protected function dispatch(ADXL345InterruptFunctions $enabled, ADXL345InterruptMap $map, array $edges): array
    {
        $source = $this->chip->getInterruptSource();
        $now = hrtime(true);
        $events = [];

        foreach ($source->functions() as $function) {
            if (! $enabled->has($function)) {
                continue;
            }

            $pin = $map->pinFor($function);
            $event = new ADXL345InterruptEvent($function, $pin, $edges[$pin] ?? $now);
            $events[] = $event;

            foreach ($this->handlers($function) as $handler) {
                $handler($event);
            }
        }

        return $events;
    }
}
