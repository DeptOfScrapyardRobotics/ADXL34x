<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums;

enum ADXL34xI2CAddress: int
{
    case SDO_GROUNDED = 0x53;
    case SDO_ENERGIZED = 0x1D;
}
