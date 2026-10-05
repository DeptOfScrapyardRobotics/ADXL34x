<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\Providers;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\ADXL343;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use Voyager\NutsAndBolts\ServiceProvider;

/**
 * Each IC's config lives under the circuits tree: config('circuits.adxl345'),
 * published to config/circuits/adxl345.php, which the config loader keys the
 * same way.
 */
class ADXL34xServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/adxl343.php', 'circuits.adxl343');
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/adxl345.php', 'circuits.adxl345');
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 2).'/config/adxl343.php' => $this->app->configPath('circuits/adxl343.php'),
            dirname(__DIR__, 2).'/config/adxl345.php' => $this->app->configPath('circuits/adxl345.php'),
        ], 'adxl34x-config');

        // With the GPIO catalog installed, each chip is conjurable by slug: app('circuit')->conjure('adxl345').
        if ($this->app->isBound('circuit')) {
            $catalog = $this->app->make('circuit');
            $catalog->addCircuit('adxl343', ADXL343::class);
            $catalog->addCircuit('adxl345', ADXL345::class);
        }
    }
}
