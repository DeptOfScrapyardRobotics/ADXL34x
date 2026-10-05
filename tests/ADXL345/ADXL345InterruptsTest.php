<?php

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345InterruptEvent;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345Interrupts;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345ActivityControl;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345InterruptFunctions;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345InterruptMap;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345TapAxes;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345InterruptFunction;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345OpCode;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeI2CTransport;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeInterruptPin;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xI2CTransport;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use Voyager\Contracts\IOPools\LoopResources\Timer;

/*
| Replies are consumed in the order the driver reads after boot:
|   INT_MAP (0x2F)      once, the first time any function is enabled
|   DATA_FORMAT (0x31)  once, the first time a routed line is wired (INT_INVERT)
|   INT_SOURCE (0x30)   when a wired line edged, or every poll for an unwired one
| The enable mask is never read back: boot wrote it, the driver remembers it.
*/

/** @return array{0: ADXL345, 1: FakeI2CTransport} booted with $enabled, boot replies consumed */
function interruptChip(ADXL345InterruptFunctions $enabled, ?FakeInterruptPin $int1 = null, ?FakeInterruptPin $int2 = null, array $replies = []): array
{
    $bus = new FakeI2CTransport;
    $bus->replies = [...bootI2CReplies(), ...$replies];
    $chip = new ADXL345(new ADXL34xI2CTransport($bus, $int1, $int2), $enabled, boot_now: true);

    return [$chip, $bus];
}

/** @return list<int> the register of every read after the DEVID check */
function registersReadAfterBoot(FakeI2CTransport $bus): array
{
    return array_map(fn (array $wr): int => $wr[0][0], array_slice($bus->write_reads, BOOT_READS));
}

function risingEdgeAt(int $timestamp_ns): DigitalEdgeEvent
{
    return new DigitalEdgeEvent('bench', 17, SignalEdge::RISING, $timestamp_ns, 1);
}

/** @param list<ADXL345InterruptEvent> $events */
function firedAs(array $events): array
{
    return array_map(fn (ADXL345InterruptEvent $e): array => [$e->function, $e->pin], $events);
}

// --- subscriptions -----------------------------------------------------------

it('hands out one interrupts object per chip and keeps handlers per function', function (): void {
    [$chip] = interruptChip(ADXL345InterruptFunctions::none());
    $a = fn () => null;
    $b = fn () => null;

    $interrupts = $chip->interrupts()
        ->on(ADXL345InterruptFunction::DATA_READY, $a)
        ->on(ADXL345InterruptFunction::DATA_READY, $b)
        ->on(ADXL345InterruptFunction::SINGLE_TAP, $a);

    expect($interrupts)->toBeInstanceOf(ADXL345Interrupts::class)
        ->and($chip->interrupts())->toBe($interrupts)
        ->and($interrupts->name)->toBe('adxl345.interrupts')
        ->and($interrupts->handlers(ADXL345InterruptFunction::DATA_READY))->toBe([$a, $b])
        ->and($interrupts->off(ADXL345InterruptFunction::DATA_READY, $a)->handlers(ADXL345InterruptFunction::DATA_READY))->toBe([$b])
        ->and($interrupts->off(ADXL345InterruptFunction::SINGLE_TAP)->handlers(ADXL345InterruptFunction::SINGLE_TAP))->toBe([]);
});

it('reads no register at all while nothing is enabled', function (): void {
    $int1 = new FakeInterruptPin(17);
    $int1->pending = [risingEdgeAt(1)];
    [$chip, $bus] = interruptChip(ADXL345InterruptFunctions::none(), $int1);

    expect($chip->interrupts()->poll())->toBe([])
        ->and($int1->polls)->toBe([])
        ->and(registersReadAfterBoot($bus))->toBe([]);
});

// --- wired line: edge-driven ---------------------------------------------------

it('reads INT_SOURCE only after the wired line edged, and dispatches with the edge timestamp', function (): void {
    $int1 = new FakeInterruptPin(17);
    $int1->pending = [risingEdgeAt(5_000)];
    [$chip, $bus] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), $int1, replies: [[0x00], [0x00], [0x80]]);
    $seen = [];
    $chip->interrupts()->on(ADXL345InterruptFunction::DATA_READY, function (ADXL345InterruptEvent $e) use (&$seen): void { $seen[] = $e; });

    $events = $chip->interrupts()->poll();

    expect($int1->polls)->toBe([[true, false]])
        ->and(registersReadAfterBoot($bus))->toBe([0x2F, 0x31, 0x30])
        ->and(firedAs($events))->toBe([[ADXL345InterruptFunction::DATA_READY, 1]])
        ->and($events[0]->timestamp_ns)->toBe(5_000)
        ->and($seen)->toBe($events);
});

it('costs no bus read when the wired line did not edge, once routing is known', function (): void {
    $int1 = new FakeInterruptPin(17);
    [$chip, $bus] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), $int1, replies: [[0x00], [0x00]]);

    $chip->interrupts()->poll();
    $chip->interrupts()->poll();
    $chip->interrupts()->poll();

    expect(registersReadAfterBoot($bus))->toBe([0x2F, 0x31])
        ->and($int1->polls)->toHaveCount(3);
});

it('watches falling edges when INT_INVERT makes the lines active low', function (): void {
    $int1 = new FakeInterruptPin(17);
    [$chip] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), $int1, replies: [[0x00], [0b0010_0000]]);

    $chip->interrupts()->poll();

    expect($int1->polls)->toBe([[false, true]]);
});

it('follows a polarity change made through the chip', function (): void {
    $int1 = new FakeInterruptPin(17);
    [$chip] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), $int1, replies: [[0x00], [0x00], [0x00]]);

    $chip->interrupts()->poll();
    $chip->data_format = $chip->data_format->withIntInvert(true);
    $chip->interrupts()->poll();

    expect($int1->polls)->toBe([[true, false], [false, true]]);
});

// --- unwired line: polling fallback -------------------------------------------

it('polls INT_SOURCE on every call when the routed line is not wired', function (): void {
    [$chip, $bus] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), replies: [[0x00], [0x80], [0x00]]);

    $first = $chip->interrupts()->poll();
    $second = $chip->interrupts()->poll();

    expect(firedAs($first))->toBe([[ADXL345InterruptFunction::DATA_READY, 1]])
        ->and($first[0]->timestamp_ns)->toBeGreaterThan(0)
        ->and($second)->toBe([])
        ->and(registersReadAfterBoot($bus))->toBe([0x2F, 0x30, 0x30]);
});

it('ignores a function that fired but is not enabled', function (): void {
    [$chip] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), replies: [[0x00], [0xC0]]);
    $tapped = false;
    $chip->interrupts()->on(ADXL345InterruptFunction::SINGLE_TAP, function () use (&$tapped): void { $tapped = true; });

    $events = $chip->interrupts()->poll();

    expect(firedAs($events))->toBe([[ADXL345InterruptFunction::DATA_READY, 1]])
        ->and($tapped)->toBeFalse();
});

// --- mixed ------------------------------------------------------------------------

it('polls the unwired line and dispatches every enabled function that one INT_SOURCE read reports', function (): void {
    $int1 = new FakeInterruptPin(17);                                   // data ready → INT1, wired, no edge
    [$chip, $bus] = interruptChip(
        new ADXL345InterruptFunctions(data_ready: true, single_tap: true),
        $int1,
        replies: [[0b0100_0000], [0x00], [0xC0]],                        // single tap → INT2, unwired
    );

    $events = $chip->interrupts()->poll();

    expect(firedAs($events))->toBe([
        [ADXL345InterruptFunction::DATA_READY, 1],
        [ADXL345InterruptFunction::SINGLE_TAP, 2],
    ])->and(registersReadAfterBoot($bus))->toBe([0x2F, 0x31, 0x30]);
});

it('uses each wired line\'s own edge timestamp in a mixed poll', function (): void {
    $int1 = new FakeInterruptPin(17);
    $int2 = new FakeInterruptPin(27);
    $int1->pending = [risingEdgeAt(111)];
    $int2->pending = [risingEdgeAt(222)];
    [$chip] = interruptChip(new ADXL345InterruptFunctions(data_ready: true, free_fall: true), $int1, $int2, replies: [[0b0000_0100], [0x00], [0x84]]);

    $events = $chip->interrupts()->poll();

    expect(array_map(fn (ADXL345InterruptEvent $e): array => [$e->pin, $e->timestamp_ns], $events))->toBe([[1, 111], [2, 222]]);
});

// --- routing changes during operation -----------------------------------------

it('picks up an enable mask changed through the chip without reading it back', function (): void {
    [$chip, $bus] = interruptChip(ADXL345InterruptFunctions::none(), replies: [[0x00], [0x80]]);

    expect($chip->interrupts()->poll())->toBe([]);

    $chip->active_interrupts = new ADXL345InterruptFunctions(data_ready: true);

    expect(firedAs($chip->interrupts()->poll()))->toBe([[ADXL345InterruptFunction::DATA_READY, 1]])
        ->and(registersReadAfterBoot($bus))->toBe([0x2F, 0x30]);
});

it('picks up a pin map changed through the chip', function (): void {
    $int2 = new FakeInterruptPin(27);
    [$chip, $bus] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), null, $int2, replies: [[0x00], [0x80], [0x00]]);

    expect(firedAs($chip->interrupts()->poll()))->toBe([[ADXL345InterruptFunction::DATA_READY, 1]]);

    $chip->interrupt_map = new ADXL345InterruptMap(data_ready: true);
    $chip->interrupts()->poll();

    expect($int2->polls)->toBe([[true, false]])
        ->and(registersReadAfterBoot($bus))->toBe([0x2F, 0x30, 0x31]);
});

// --- blocking ------------------------------------------------------------------------

it('wait() blocks in listen() on a sole wired line and dispatches the edge it returns', function (): void {
    $int1 = new FakeInterruptPin(17);
    $int1->listen_edges = [risingEdgeAt(42)];
    [$chip, $bus] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), $int1, replies: [[0x00], [0x00], [0x80]]);

    $events = $chip->interrupts()->wait(100);

    expect(firedAs($events))->toBe([[ADXL345InterruptFunction::DATA_READY, 1]])
        ->and($events[0]->timestamp_ns)->toBe(42)
        ->and($int1->listens)->toHaveCount(1)
        ->and($int1->listens[0][0])->toBeLessThanOrEqual(100)
        ->and(array_slice($int1->listens[0], 1))->toBe([true, false])
        ->and(registersReadAfterBoot($bus))->toBe([0x2F, 0x31, 0x30]);
});

it('wait() returns an edge already buffered without calling listen()', function (): void {
    $int1 = new FakeInterruptPin(17);
    $int1->pending = [risingEdgeAt(7)];
    [$chip] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), $int1, replies: [[0x00], [0x00], [0x80]]);

    $events = $chip->interrupts()->wait(100);

    expect($events[0]->timestamp_ns)->toBe(7)
        ->and($int1->listens)->toBe([]);
});

it('wait() on a wired line returns empty once the deadline passes', function (): void {
    $int1 = new FakeInterruptPin(17);
    [$chip] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), $int1, replies: [[0x00], [0x00]]);

    $started = hrtime(true);
    $events = $chip->interrupts()->wait(20);

    expect($events)->toBe([])
        ->and((hrtime(true) - $started) / 1_000_000)->toBeGreaterThanOrEqual(20)
        ->and($int1->listens)->not->toBe([]);
});

it('wait() polls INT_SOURCE until something fires when the line is not wired', function (): void {
    [$chip, $bus] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), replies: [[0x00], [0x00], [0x00], [0x80]]);

    $events = $chip->interrupts()->wait(200);

    expect(firedAs($events))->toBe([[ADXL345InterruptFunction::DATA_READY, 1]])
        ->and(registersReadAfterBoot($bus))->toBe([0x2F, 0x30, 0x30, 0x30]);
});

it('wait() without a wired line returns empty once the deadline passes', function (): void {
    [$chip] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), replies: [[0x00], ...array_fill(0, 500, [0x00])]);

    $started = hrtime(true);
    $events = $chip->interrupts()->wait(20);

    expect($events)->toBe([])
        ->and((hrtime(true) - $started) / 1_000_000)->toBeGreaterThanOrEqual(20);
});

it('wait() polls rather than blocking on one line when both lines are wired', function (): void {
    $int1 = new FakeInterruptPin(17);
    $int2 = new FakeInterruptPin(27);
    [$chip] = interruptChip(new ADXL345InterruptFunctions(data_ready: true, free_fall: true), $int1, $int2, replies: [[0b0000_0100], [0x00]]);

    $chip->interrupts()->wait(10);

    expect($int1->listens)->toBe([])
        ->and($int2->listens)->toBe([])
        ->and(count($int1->polls))->toBeGreaterThan(1);
});

// --- event loop -------------------------------------------------------------------------

it('every() runs poll() on a loop timer named after the dispatcher, handlers receiving what it finds', function (): void {
    [$chip] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), replies: [[0x00], [0x80]]);
    $loop = testLoop();
    $seen = [];
    $chip->interrupts()->on(ADXL345InterruptFunction::DATA_READY, function (ADXL345InterruptEvent $e) use (&$seen, $loop): void {
        $seen[] = $e;
        $loop->stop();
    });

    $timer = $chip->interrupts()->every($loop, 0.001);
    $loop->run();

    expect($timer)->toBeInstanceOf(Timer::class)
        ->and($timer->interval())->toBe(1_000_000)
        ->and(firedAs($seen))->toBe([[ADXL345InterruptFunction::DATA_READY, 1]]);
});

it('names the timer per chip when told, so two chips share one loop', function (): void {
    [$left] = interruptChip(ADXL345InterruptFunctions::none());
    [$right] = interruptChip(ADXL345InterruptFunctions::none());
    $loop = testLoop();

    $left->interrupts('left.adxl345')->every($loop);
    $right->interrupts('right.adxl345')->every($loop);

    expect($loop->registry->soonestDue())->not->toBeNull()
        ->and($left->interrupts()->name)->toBe('left.adxl345');

    $left->interrupts()->stop($loop);
    $right->interrupts()->stop($loop);

    expect($loop->registry->hasWork())->toBeFalse();
});

it('blocking and loop polling share one dispatcher and interleave freely', function (): void {
    [$chip] = interruptChip(new ADXL345InterruptFunctions(data_ready: true), replies: [[0x00], [0x80], [0x80], [0x80]]);
    $loop = testLoop();
    $count = 0;
    $chip->interrupts()->on(ADXL345InterruptFunction::DATA_READY, function () use (&$count, $loop): void {
        if (++$count % 2 === 1) {
            $loop->stop();
        }
    });
    $chip->interrupts()->every($loop, 0.001);

    $loop->run();
    $chip->interrupts()->wait(50);
    $loop->run();

    expect($count)->toBe(3);
});

// --- registers -------------------------------------------------------------------

it('round-trips INT_MAP as a breakout and answers which line a function lands on', function (): void {
    [$chip, $bus] = interruptChip(ADXL345InterruptFunctions::none(), replies: [[0b0100_0000]]);

    $map = $chip->interrupt_map;

    expect($map)->toBeInstanceOf(ADXL345InterruptMap::class)
        ->and($map->single_tap)->toBeTrue()
        ->and($map->pinFor(ADXL345InterruptFunction::SINGLE_TAP))->toBe(2)
        ->and($map->pinFor(ADXL345InterruptFunction::DATA_READY))->toBe(1)
        ->and($map->toByte())->toBe(0b0100_0000);

    $chip->interrupt_map = new ADXL345InterruptMap(data_ready: true);

    expect(end($bus->writes))->toBe([ADXL345OpCode::INTERRUPT_MAP_REGISTER->value, 0b1000_0000]);
});

it('reads INT_SOURCE into the interrupt functions breakout', function (): void {
    [$chip, $bus] = interruptChip(ADXL345InterruptFunctions::none(), replies: [[0b1000_0100]]);

    $source = $chip->interrupt_source;

    expect($source)->toBeInstanceOf(ADXL345InterruptFunctions::class)
        ->and($source->functions())->toBe([ADXL345InterruptFunction::DATA_READY, ADXL345InterruptFunction::FREE_FALL])
        ->and($source->has(ADXL345InterruptFunction::FREE_FALL))->toBeTrue()
        ->and($source->has(ADXL345InterruptFunction::OVERRUN))->toBeFalse()
        ->and(registersReadAfterBoot($bus))->toBe([0x30]);
});

it('writes the tap, activity, inactivity and free-fall registers through typed properties', function (): void {
    [$chip, $bus] = interruptChip(ADXL345InterruptFunctions::none());
    $before = count($bus->writes);

    $chip->tap_threshold = 0x30;
    $chip->tap_duration = 0x10;
    $chip->tap_latency = 0x50;
    $chip->tap_window = 0xF0;
    $chip->tap_axes = new ADXL345TapAxes(z: true);
    $chip->activity_threshold = 0x20;
    $chip->inactivity_threshold = 0x03;
    $chip->inactivity_time = 0x02;
    $chip->activity_control = new ADXL345ActivityControl(activity_ac: true, activity_x: true, activity_y: true, activity_z: true);
    $chip->free_fall_threshold = 0x07;
    $chip->free_fall_time = 0x14;

    expect(array_slice($bus->writes, $before))->toBe([
        [0x1D, 0x30], [0x21, 0x10], [0x22, 0x50], [0x23, 0xF0], [0x2A, 0b0000_0001],
        [0x24, 0x20], [0x25, 0x03], [0x26, 0x02], [0x27, 0b1111_0000],
        [0x28, 0x07], [0x29, 0x14],
    ]);
});

it('reads those registers back as ints and breakouts', function (): void {
    [$chip] = interruptChip(ADXL345InterruptFunctions::none(), replies: [[0x30], [0b0000_1101], [0b0000_1111]]);

    $threshold = $chip->tap_threshold;
    $axes = $chip->tap_axes;
    $control = $chip->activity_control;

    expect($threshold)->toBe(0x30)
        ->and($axes)->toBeInstanceOf(ADXL345TapAxes::class)
        ->and([$axes->suppress, $axes->x, $axes->y, $axes->z])->toBe([true, true, false, true])
        ->and($axes->toByte())->toBe(0b0000_1101)
        ->and($control)->toBeInstanceOf(ADXL345ActivityControl::class)
        ->and($control->inactivity_ac)->toBeTrue()
        ->and($control->activity_x)->toBeFalse()
        ->and($control->toByte())->toBe(0b0000_1111);
});

it('rejects a byte register value outside 0 to 255, naming the property', function (): void {
    [$chip] = interruptChip(ADXL345InterruptFunctions::none());

    expect(fn () => $chip->tap_threshold = 256)->toThrow(ADXL34xException::class, 'tap_threshold')
        ->and(fn () => $chip->free_fall_time = -1)->toThrow(ADXL34xException::class, 'free_fall_time');
});

it('the transport answers which INT lines it holds', function (): void {
    $int2 = new FakeInterruptPin(5);
    [$chip] = interruptChip(ADXL345InterruptFunctions::none(), null, $int2);

    expect($chip->transport()->interruptPin(1))->toBeNull()
        ->and($chip->transport()->interruptPin(2))->toBe($int2)
        ->and(fn () => $chip->transport()->interruptPin(3))->toThrow(ADXL34xException::class, 'INT3');
});
