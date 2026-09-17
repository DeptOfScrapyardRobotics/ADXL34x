<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345InterruptFunction;

/**
 * One interrupt function that fired. $pin is the INT line INT_MAP routes it
 * to (1 or 2). $timestamp_ns is the wired line's edge time, or hrtime(true)
 * at the INT_SOURCE read when the line is polled.
 */
final readonly class ADXL345InterruptEvent
{
    public function __construct(
        public ADXL345InterruptFunction $function,
        public int $pin,
        public int $timestamp_ns,
    ) {}
}
