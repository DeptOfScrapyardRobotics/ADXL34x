<?php

/*
| Proven against recording fake transports: every register byte the chip would
| see, every byte it would answer. Nothing here touches a bus. The live checks
| are an ADXL343 on a Raspberry Pi's I2C bus and an ADXL345 on an FT232H's SPI.
*/

use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\ConfigPathVessel;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeDigitalIOConnectionDriver;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeI2CConnectionDriver;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeSPIConnectionDriver;
use GeneralPurposeIO\Digital\DigitalOConnectionManager;
use GeneralPurposeIO\I2C\I2CConnectionManager;
use GeneralPurposeIO\IntegratedCircuits\CircuitRegistry;
use GeneralPurposeIO\SPI\SPIConnectionManager;
use Voyager\Config\Repository;
use Voyager\IOPools\EventLoop;
use Voyager\IOPools\LoopWaiter;
use Voyager\IOPools\PromiseEngines\GuzzlePromiseEngine;
use Voyager\IOPools\ResourceRegistry;
use Voyager\IOPools\Waiter\StreamSelectWaiterBackend;
use Voyager\Vessel\ControlPanel;

/*
| A Venusian app's core defines config() over the container's config repository;
| CircuitRegistry::conjure() calls it. A package suite has no core, so this
| stands in for it the same way.
*/
if (! function_exists('config')) {
    function config(array|string|null $key = null, mixed $default = null): mixed
    {
        $config = ControlPanel::getInstance()->make('config');

        return match (true) {
            is_null($key) => $config,
            is_array($key) => $config->set($key),
            default => $config->get($key, $default),
        };
    }
}

/*
| What boot reads, in order: DEVID, the six data bytes (clearing a leftover
| DATA_READY), BW_RATE, then INT_SOURCE until DATA_READY. The scripted answers
| say 100 Hz and a fresh sample at the first INT_SOURCE read.
*/
const BOOT_READS = 4;

/** @return list<list<int>> */
function bootI2CReplies(): array
{
    return [[0xE5], [0, 0, 0, 0, 0, 0], [0x0A], [0x80]];
}

/** @return list<list<int>> the same answers as MISO frames: a dummy byte under the address byte, then the data */
function bootSPIReplies(): array
{
    return array_map(fn (array $reply): array => [0xFF, ...$reply], bootI2CReplies());
}

/** A loop on the select backend, the way IOPools builds one. */
function testLoop(int $pace_ms = 16): EventLoop
{
    $registry = new ResourceRegistry;

    return new EventLoop($registry, new LoopWaiter($registry, new StreamSelectWaiterBackend, $pace_ms * 1_000_000), new GuzzlePromiseEngine);
}

/**
 * The shared container as an app sets it up: config, the circuit catalog, and the three protocol managers, each
 * with a 'fake' driver.
 *
 * @return array{i2c: FakeI2CConnectionDriver, spi: FakeSPIConnectionDriver, digital: FakeDigitalIOConnectionDriver, app: ConfigPathVessel}
 */
function fakeBench(array $config = []): array
{
    $app = new ConfigPathVessel;
    $app->registerInstance('config', new Repository($config));
    $app->registerInstance('circuit', new CircuitRegistry);
    ControlPanel::setInstance($app);

    $bench = [
        'i2c' => new FakeI2CConnectionDriver,
        'spi' => new FakeSPIConnectionDriver,
        'digital' => new FakeDigitalIOConnectionDriver,
        'app' => $app,
    ];

    $app->registerInstance('gpio.i2c', (new I2CConnectionManager($app))->extend('fake', fn () => $bench['i2c']));
    $app->registerInstance('gpio.spi', (new SPIConnectionManager($app))->extend('fake', fn () => $bench['spi']));
    $app->registerInstance('gpio.digital', (new DigitalOConnectionManager($app))->extend('fake', fn () => $bench['digital']));

    return $bench;
}

pest()->afterEach(function (): void {
    ControlPanel::setInstance(null);
})->in(__DIR__);
