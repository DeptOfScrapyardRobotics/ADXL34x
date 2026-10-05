<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\Concerns\ConjuresADXL34x;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\CelestialBody;
use GeneralPurposeIO\Contracts\IntegratedCircuits\Sensor;
use GeneralPurposeIO\IntegratedCircuits\Bootable;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xDataTransport;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Concerns\ADXL343Bootsrap;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Breakouts\ADXL343InterruptFunctions;

class ADXL343 extends Bootable implements Sensor
{
    use ADXL343Bootsrap;
    use ConjuresADXL34x;

    protected int $hardwired_device_id = 0xE5;

    protected ?ADXL343Interrupts $interrupts = null;

    public function __construct(
        protected readonly ADXL34xDataTransport $transport,
        protected ADXL343InterruptFunctions $_starting_int_fns = new ADXL343InterruptFunctions(),
        bool $boot_now = false
    ) {

        parent::__construct($boot_now);
    }

    public function transport(): ADXL34xDataTransport
    {
        return $this->transport;
    }

    public function x(): float
    {
        return $this->acceleration()['x'];
    }

    public function y(): float
    {
        return $this->acceleration()['y'];
    }

    public function z(): float
    {
        return $this->acceleration()['z'];
    }

    /**
     * All three axes in m/s² from one scale read and one burst read.
     *
     * @return array{x: float, y: float, z: float}
     */
    public function acceleration(CelestialBody $body = CelestialBody::TERRA): array
    {
        $factor = $this->scale() * $body->gravity();
        $raw = $this->raw();

        return [
            'x' => $raw['x'] * $factor,
            'y' => $raw['y'] * $factor,
            'z' => $raw['z'] * $factor,
        ];
    }

    /** The chip's interrupt dispatcher. Pass a name to key its loop timer when several chips share one loop. */
    public function interrupts(?string $name = null): ADXL343Interrupts
    {
        $this->interrupts ??= new ADXL343Interrupts($this);

        if (! is_null($name)) {
            $this->interrupts->name = $name;
        }

        return $this->interrupts;
    }

    public function close(): void
    {
        $this->transport->close();
    }
}