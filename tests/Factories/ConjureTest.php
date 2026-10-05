<?php

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\ADXL343;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\ADXL345;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Providers\ADXL34xServiceProvider;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeI2CTransport;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeInterruptPin;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Tests\Support\FakeSPITransport;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xI2CTransport;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xSPITransport;

dataset('chips', [
    'adxl343' => [ADXL343::class],
    'adxl345' => [ADXL345::class],
]);

/** The FakeI2CTransport or FakeSPITransport under a chip's transport. */
function busUnder(ADXL343|ADXL345 $chip): FakeI2CTransport|FakeSPITransport
{
    return (new ReflectionProperty($chip->transport(), 'transport'))->getValue($chip->transport());
}

// --- i2c() -----------------------------------------------------------------------------

it('i2c() connects the bus, takes the slave, and hands back a booted chip with no INT lines', function (string $class): void {
    $bench = fakeBench();
    $bench['i2c']->replies = bootI2CReplies();

    $chip = $class::i2c('fake', 1, slave: 0x1D);

    expect($chip)->toBeInstanceOf($class)
        ->and($chip->hasBooted())->toBeTrue()
        ->and($chip->transport())->toBeInstanceOf(ADXL34xI2CTransport::class)
        ->and(busUnder($chip)->address)->toBe(0x1D)
        ->and($bench['i2c']->opened)->toBe([1])
        ->and($chip->transport()->interruptPin(1))->toBeNull()
        ->and($chip->transport()->interruptPin(2))->toBeNull();
})->with('chips');

it('i2c() shares a bus the app already connected instead of opening it again', function (string $class): void {
    $bench = fakeBench();
    $bench['i2c']->connectTo(1)->register();
    $bench['i2c']->replies = bootI2CReplies();

    $class::i2c('fake', 1);

    expect($bench['i2c']->opened)->toBe([1]);
})->with('chips');

it('i2c() wires each enabled INT line through DigitalIO and leaves a disabled one out', function (string $class): void {
    $bench = fakeBench();
    $bench['i2c']->replies = bootI2CReplies();

    $chip = $class::i2c('fake', 1,
        int1: ['enabled' => true, 'driver' => 'fake', 'device' => 0, 'pin' => 24],
        int2: ['enabled' => false, 'driver' => 'fake', 'device' => 0, 'pin' => 25],
    );

    expect($chip->transport()->interruptPin(1))->toBeInstanceOf(FakeInterruptPin::class)
        ->and($chip->transport()->interruptPin(1)->pin)->toBe(24)
        ->and($chip->transport()->interruptPin(2))->toBeNull()
        ->and($bench['digital']->opened)->toBe([0]);
})->with('chips');

it('i2c() leaves the chip unbooted when told', function (string $class): void {
    $bench = fakeBench();

    $chip = $class::i2c('fake', 1, boot_now: false);

    expect($chip->hasBooted())->toBeFalse()
        ->and(busUnder($chip)->write_reads)->toBe([]);
})->with('chips');

// --- spi() -------------------------------------------------------------------------------

it('spi() opens the bus in mode 3 and clocks the chip select at the speed asked for', function (string $class): void {
    $bench = fakeBench();
    $bench['spi']->replies = bootSPIReplies();

    $chip = $class::spi('fake', 'ft232h', chip_select: 0, speed: 2_000_000,
        int1: ['enabled' => true, 'driver' => 'fake', 'device' => 'ft232h', 'pin' => 1],
        int2: ['enabled' => true, 'driver' => 'fake', 'device' => 'ft232h', 'pin' => 2],
    );

    expect($chip->hasBooted())->toBeTrue()
        ->and($chip->transport())->toBeInstanceOf(ADXL34xSPITransport::class)
        ->and(busUnder($chip)->chipSelect())->toBe(0)
        ->and(busUnder($chip)->clock())->toBe(2_000_000)
        ->and($bench['spi']->settingsOf('ft232h')->mode->value)->toBe(3)
        ->and($bench['spi']->settingsOf('ft232h')->speed)->toBe(2_000_000)
        ->and($chip->transport()->interruptPin(1)->pin)->toBe(1)
        ->and($chip->transport()->interruptPin(2)->pin)->toBe(2);
})->with('chips');

it('spi() runs at 5 MHz unless told otherwise', function (string $class): void {
    $bench = fakeBench();
    $bench['spi']->replies = bootSPIReplies();

    $chip = $class::spi('fake', 'ft232h');

    expect(busUnder($chip)->clock())->toBe(5_000_000)
        ->and($bench['spi']->settingsOf('ft232h')->speed)->toBe(5_000_000);
})->with('chips');

it('spi() shares a mode 3 bus the app opened, clocking its own chip select', function (string $class): void {
    $bench = fakeBench();
    $bench['spi']->connectTo('ft232h')->mode(3)->speed(10_000_000)->register();
    $bench['spi']->replies = bootSPIReplies();

    $chip = $class::spi('fake', 'ft232h', chip_select: 4, speed: 1_000_000);

    expect($bench['spi']->opened)->toBe(['ft232h'])
        ->and(busUnder($chip)->chipSelect())->toBe(4)
        ->and(busUnder($chip)->clock())->toBe(1_000_000);
})->with('chips');

it('spi() refuses a bus the app opened in another mode', function (string $class): void {
    $bench = fakeBench();
    $bench['spi']->connectTo('ft232h')->mode(0)->register();

    expect(fn () => $class::spi('fake', 'ft232h'))->toThrow(ADXL34xException::class, 'needs SPI mode 3, but bus ft232h was opened in mode 0');
})->with('chips');

it('spi() refuses a clock the chip cannot run at, before touching the bus', function (string $class): void {
    $bench = fakeBench();

    expect(fn () => $class::spi('fake', 'ft232h', speed: 5_000_001))->toThrow(ADXL34xException::class, '5000001 Hz')
        ->and(fn () => $class::spi('fake', 'ft232h', speed: 0))->toThrow(ADXL34xException::class, '0 Hz')
        ->and($bench['spi']->opened)->toBe([]);
})->with('chips');

// --- the catalog ---------------------------------------------------------------------------

it('conjures a wired, booted chip from the app config the provider merged', function (string $class): void {
    $slug = strtolower(class_basename_of($class));
    $bench = fakeBench(['circuits' => [$slug => [
        'default_config' => 'bench',
        'configs' => ['bench' => [
            'protocol' => 'spi',
            'driver' => 'fake',
            'device' => 'ft232h',
            'chip_select' => 0,
            'speed' => 4_000_000,
            'int1' => ['enabled' => true, 'driver' => 'fake', 'device' => 'ft232h', 'pin' => 1],
            'int2' => ['enabled' => false, 'driver' => 'fake', 'device' => 'ft232h', 'pin' => 2],
        ]],
    ]]]);
    $bench['spi']->replies = bootSPIReplies();
    $provider = new ADXL34xServiceProvider($bench['app']);
    $provider->register();
    $provider->boot();

    $chip = $bench['app']->make('circuit')->conjure($slug);

    expect($chip)->toBeInstanceOf($class)
        ->and($chip->hasBooted())->toBeTrue()
        ->and(busUnder($chip)->clock())->toBe(4_000_000)
        ->and($chip->transport()->interruptPin(1)->pin)->toBe(1)
        ->and($chip->transport()->interruptPin(2))->toBeNull();
})->with('chips');

it('conjures the package default config as an I2C chip on a driver the app names', function (string $class): void {
    $slug = strtolower(class_basename_of($class));
    $bench = fakeBench();
    $bench['i2c']->replies = bootI2CReplies();
    $provider = new ADXL34xServiceProvider($bench['app']);
    $provider->register();
    $provider->boot();
    $bench['app']->make('config')->set("circuits.{$slug}.configs.i2c.driver", 'fake');
    $bench['app']->make('config')->set("circuits.{$slug}.configs.i2c.device", 1);

    $chip = $bench['app']->make('circuit')->conjure($slug);

    expect($chip->transport())->toBeInstanceOf(ADXL34xI2CTransport::class)
        ->and(busUnder($chip)->address)->toBe(0x53);
})->with('chips');

function class_basename_of(string $class): string
{
    return substr($class, strrpos($class, '\\') + 1);
}
