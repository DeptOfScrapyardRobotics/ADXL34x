<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums;

enum ADXL34xCatalogIc: string
{
    case ADXL343 = 'adxl343';
    case ADXL345 = 'adxl345';

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases(),
        );
    }
}
