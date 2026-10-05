<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums;

/** SPI clock limits from the datasheet, in Hz. */
enum ADXL34xSPIClock: int
{
    /** The fastest clock the ADXL34x takes. */
    case MAX_HZ = 5_000_000;
}
