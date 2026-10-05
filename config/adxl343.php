<?php

use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\ADXL34xI2CAddress;

return [
    'default_config' => 'i2c',
    'configs' => [
        'i2c' => [
            'driver' => 'none',
            'device' => '',
            'slave' => ADXL34xI2CAddress::SDO_GROUNDED->value,
            'int1' => [
                'enabled' => false,
                'driver' => 'none',
                'device' => '',
                'pin' => 0,
            ],
            'int2' => [
                'enabled' => false,
                'driver' => 'none',
                'device' => '',
                'pin' => 1,
            ],
        ],
        'spi' => [
            'driver' => 'none',
            'device' => '',
            'chip_select' => 0,
            'speed' => 5_000_000,
            'int1' => [
                'enabled' => false,
                'driver' => 'none',
                'device' => '',
                'pin' => 0,
            ],
            'int2' => [
                'enabled' => false,
                'driver' => 'none',
                'device' => '',
                'pin' => 1,
            ],
        ],
    ]
];