<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\ADXL343;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Console\ADXL34xMakeProfileCommand;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\ADXL34xCatalogIc;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\ADXL34xConsoleCommand;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Sketches\ADXL34xSmoke;
use Fabricate\Contracts\Sketches\SketchRegistry;
use Fabricate\NutsAndBolts\ServiceProvider;
use GeneralPurposeIO\Core\MagicAliases\Circuit;

class ADXL34xServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(ADXL34xMakeProfileCommand::class);
        $this->commands([
            ADXL34xMakeProfileCommand::class,
        ]);
    }

    public function boot(): void
    {
        Circuit::addCircuit(ADXL34xCatalogIc::ADXL343->value, ADXL343::class);
        Circuit::addCircuit(ADXL34xCatalogIc::ADXL345->value, ADXL345::class);

        $maker = ADXL34xConsoleCommand::MAKE_PROFILE->value;
        foreach (ADXL34xCatalogIc::cases() as $ic) {
            Circuit::registerProfileCommand($ic->value, $maker);
        }

        $this->registerSketch();
    }

    protected function registerSketch(): void
    {
        if (! $this->container->bound(SketchRegistry::class)) {
            return;
        }

        /** @var SketchRegistry $registry */
        $registry = $this->container->make(SketchRegistry::class);

        if (! $registry->has('adxl34x-smoke')) {
            $registry->registerConvention('adxl34x-smoke', ADXL34xSmoke::class);
        }
    }
}
