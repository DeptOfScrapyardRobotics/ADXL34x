<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL345\Enums;

enum ADXL345I2CAddress: int
{
    case SDO_GROUNDED = 0x53;
    case SDO_ENERGIZED = 0x1D;
}
