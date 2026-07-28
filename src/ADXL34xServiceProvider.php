<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\ADXL343;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use Fabricate\NutsAndBolts\MagicAliases\Circuit;
use Fabricate\NutsAndBolts\ServiceProvider;

class ADXL34xServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        Circuit::addCircuit('adxl343', ADXL343::class);
        Circuit::addCircuit('adxl345', ADXL345::class);
    }
}