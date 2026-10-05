<?php

use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\ADXL34xI2CAddress;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Providers\ADXL34xServiceProvider;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\ConfigPathVessel;
use Voyager\Config\Repository;
use Voyager\NutsAndBolts\ServiceProvider;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\ADXL343;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use GeneralPurposeIO\IntegratedCircuits\CircuitRegistry;

it('registers both IC configs under circuits, keeping anything the app already set', function (): void {
    $vessel = new ConfigPathVessel;
    $vessel->registerInstance('config', new Repository(['circuits' => ['adxl345' => ['default_config' => 'spi']]]));

    (new ADXL34xServiceProvider($vessel))->register();

    $config = $vessel->make('config');

    expect($config->get('circuits.adxl343.default_config'))->toBe('i2c')
        ->and($config->get('circuits.adxl343.configs.i2c.slave'))->toBe(ADXL34xI2CAddress::SDO_GROUNDED->value)
        ->and($config->get('circuits.adxl345.default_config'))->toBe('spi')
        ->and($config->get('circuits.adxl345.configs.spi.chip_select'))->toBe(0)
        ->and($config->has('adxl343'))->toBeFalse()
        ->and($config->has('adxl345'))->toBeFalse();
});

it('leaves other circuits config beside its own keys untouched', function (): void {
    $vessel = new ConfigPathVessel;
    $vessel->registerInstance('config', new Repository(['circuits' => ['front_panel' => ['ic' => 'st7789']]]));

    (new ADXL34xServiceProvider($vessel))->register();

    $config = $vessel->make('config');

    expect($config->get('circuits.front_panel'))->toBe(['ic' => 'st7789'])
        ->and($config->get('circuits.adxl345.default_config'))->toBe('i2c');
});

it('publishes both config files into config/circuits under the adxl34x-config tag', function (): void {
    $app = new ConfigPathVessel('/app/config');
    $app->registerInstance('config', new Repository);

    $provider = new ADXL34xServiceProvider($app);
    $provider->register();
    $provider->boot();

    $root = dirname(__DIR__, 2);

    expect(ServiceProvider::pathsToPublish(ADXL34xServiceProvider::class, 'adxl34x-config'))->toBe([
        "{$root}/config/adxl343.php" => '/app/config/circuits/adxl343.php',
        "{$root}/config/adxl345.php" => '/app/config/circuits/adxl345.php',
    ]);
});

it('catalogs both chips when the circuit catalog is bound, and leaves an app without one alone', function (): void {
    $app = new ConfigPathVessel('/app/config');
    $app->registerInstance('config', new Repository);
    $app->registerInstance('circuit', $catalog = new CircuitRegistry);

    (new ADXL34xServiceProvider($app))->boot();

    $bare = new ConfigPathVessel('/app/config');
    $bare->registerInstance('config', new Repository);

    expect($catalog->listCircuits())->toBe(['adxl343' => ADXL343::class, 'adxl345' => ADXL345::class])
        ->and(fn () => (new ADXL34xServiceProvider($bare))->boot())->not->toThrow(Throwable::class);
});
