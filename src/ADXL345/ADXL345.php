<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\CelestialBody;
use GeneralPurposeIO\Contracts\IntegratedCircuits\Sensor;
use GeneralPurposeIO\IntegratedCircuits\Bootable;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xDataTransport;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Concerns\ADXL345Bootsrap;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Breakouts\ADXL345InterruptFunctions;

class ADXL345 extends Bootable implements Sensor
{
    use ADXL345Bootsrap;

    protected int $hardwired_device_id = 0xE5;

    protected ?ADXL345Interrupts $interrupts = null;

    public function __construct(
        protected readonly ADXL34xDataTransport $transport,
        protected ADXL345InterruptFunctions $_starting_int_fns = new ADXL345InterruptFunctions(),
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

    /** The chip's interrupt dispatcher. Pass a name to key its dock recurrence when several chips share one dock. */
    public function interrupts(?string $name = null): ADXL345Interrupts
    {
        $this->interrupts ??= new ADXL345Interrupts($this);

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