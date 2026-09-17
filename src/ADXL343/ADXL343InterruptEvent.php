<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums\ADXL343InterruptFunction;

/**
 * One interrupt function that fired. $pin is the INT line INT_MAP routes it
 * to (1 or 2). $timestamp_ns is the wired line's edge time, or hrtime(true)
 * at the INT_SOURCE read when the line is polled.
 */
final readonly class ADXL343InterruptEvent
{
    public function __construct(
        public ADXL343InterruptFunction $function,
        public int $pin,
        public int $timestamp_ns,
    ) {}
}
