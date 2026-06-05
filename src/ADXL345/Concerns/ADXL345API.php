<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Concerns;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\DataObjects\ADXL345InterruptFunctions;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\DataObjects\ADXL345PowerControl;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345DataRate;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345OpCode;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345Range;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345ReadRegister;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL345\Enums\ADXL345SleepSamplingRate;

trait ADXL345API
{
    use ADXL345InternalAPI;

    protected float $mg2g_multiplier = 0.004;  // 4mg per lsb

    protected float $std_gravity = 9.80665;  // earth standard gravity

    protected array $enabled_interrupts = [];

    protected array $event_status = [];

    public function getDeviceId(): int
    {
        return $this->read(ADXL345ReadRegister::DEVICE_ID_REGISTER, 1)[0] ?? -1;
    }

    public function getPowerControl(): ADXL345PowerControl
    {
        $byte = $this->read(ADXL345ReadRegister::POWER_CONTROL_REGISTER, 1)[0] ?? -1;

        return ADXL345PowerControl::fromByte($byte);
    }

    public function setPowerControl(ADXL345PowerControl $power_control): void
    {
        $this->write(ADXL345OpCode::POWER_CONTROL_REGISTER, [$power_control->toByte()]);
    }

    public function getLinkMode(): bool
    {
        return $this->getPowerControl()->link;
    }

    public function setLinkMode(bool $link_mode): void
    {
        $pwr_control = $this->getPowerControl();
        $new_control = new $pwr_control(
            $link_mode,
            $pwr_control->measurement_mode,
            $pwr_control->sleep_mode,
            $pwr_control->wakeup,
        );
        $this->power_control = $new_control;
    }

    public function getMeasurementMode(): bool
    {
        return $this->getPowerControl()->measurement_mode;
    }

    public function setMeasurementMode(bool $measurement_mode): void
    {
        $pwr_control = $this->getPowerControl();
        $new_control = new $pwr_control(
            $pwr_control->link,
            $measurement_mode,
            $pwr_control->sleep_mode,
            $pwr_control->wakeup,
        );
        $this->power_control = $new_control;
    }

    public function getSleepMode(): bool
    {
        return $this->getPowerControl()->sleep_mode;
    }

    public function setSleepMode(bool $sleep_mode): void
    {
        $pwr_control = $this->getPowerControl();
        $new_control = new $pwr_control(
            $pwr_control->link,
            $pwr_control->measurement_mode,
            $sleep_mode,
            $pwr_control->wakeup,
        );
        $this->power_control = $new_control;
    }

    public function getWakeup(): ADXL345SleepSamplingRate
    {
        return $this->getPowerControl()->wakeup;
    }

    public function setWakeup(ADXL345SleepSamplingRate $wakeup): void
    {
        $pwr_control = $this->getPowerControl();
        $new_control = new $pwr_control(
            $pwr_control->link,
            $pwr_control->measurement_mode,
            $pwr_control->sleep_mode,
            $wakeup
        );
        $this->power_control = $new_control;
    }

    public function getEnabledInterrupts(): ADXL345InterruptFunctions
    {
        $byte = $this->read(ADXL345ReadRegister::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? -1;

        return ADXL345InterruptFunctions::fromByte($byte);
    }

    public function setEnabledInterrupts(ADXL345InterruptFunctions $interrupts): void
    {
        $this->write(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$interrupts->toByte()]);
    }

    public function getAcceleration(): array
    {
        $data = $this->read(ADXL345ReadRegister::DATA_FROM_X0_REGISTER, 6);

        $results = [
            'x' => $this->s16le($data[0] ?? 0, $data[1] ?? 0),
            'y' => $this->s16le($data[2] ?? 0, $data[3] ?? 0),
            'z' => $this->s16le($data[4] ?? 0, $data[5] ?? 0),
        ];

        return [
            'x' => $results['x'] * $this->mg2g_multiplier * $this->std_gravity,
            'y' => $results['y'] * $this->mg2g_multiplier * $this->std_gravity,
            'z' => $results['z'] * $this->mg2g_multiplier * $this->std_gravity,
        ];
    }

    public function getRawX(): int
    {
        $data = $this->read(ADXL345ReadRegister::DATA_FROM_X0_REGISTER, 6);

        return $this->s16le($data[0] ?? 0, $data[1] ?? 0);
    }

    public function getRawY(): int
    {
        $data = $this->read(ADXL345ReadRegister::DATA_FROM_X0_REGISTER, 6);

        return $this->s16le($data[2] ?? 0, $data[3] ?? 0);
    }

    public function getRawZ(): int
    {
        $data = $this->read(ADXL345ReadRegister::DATA_FROM_X0_REGISTER, 6);

        return $this->s16le($data[4] ?? 0, $data[5] ?? 0);
    }

    public function getEvents(): array
    {
        $interrupt_source_register = $this->read(ADXL345ReadRegister::INTERRUPT_SOURCE_REGISTER, 1)[0] ?? 0;
        $this->event_status = [];

        foreach ($this->enabled_interrupts as $event_type => $value) {
            if ($event_type === 'motion') {
                $this->event_status[$event_type] = ($interrupt_source_register & 0b00010000) > 0;
            }
            if ($event_type === 'tap') {
                if ($value === 1) {
                    $this->event_status[$event_type] = ($interrupt_source_register & 0b01000000) > 0;
                } else {
                    $this->event_status[$event_type] = ($interrupt_source_register & 0b00100000) > 0;
                }
            }
            if ($event_type === 'freefall') {
                $this->event_status[$event_type] = ($interrupt_source_register & 0b00000100) > 0;
            }
        }

        return $this->event_status;
    }

    public function setMotionDetection(bool $flag): void
    {
        $flag ? $this->enableMotionDetection() : $this->disableMotionDetection();
    }

    protected function enableMotionDetection(): void
    {
        $active_interrupts = $this->read(ADXL345ReadRegister::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? 0;
        $this->write(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [0x0]);
        $this->write(ADXL345OpCode::ACTIVITY_AND_INACTIVITY_CONTROL_REGISTER, [0b01110000]);
        $this->write(ADXL345OpCode::THRESHOLD_ACTIVITY_REGISTER, [18]);
        $active_interrupts |= 0b00010000;
        $this->write(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$active_interrupts]);
        $this->enabled_interrupts['motion'] = true;
    }

    protected function disableMotionDetection(): void
    {
        $active_interrupts = $this->read(ADXL345ReadRegister::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? 0;
        $active_interrupts &= ~0b00010000;
        $this->write(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$active_interrupts]);
        unset($this->enabled_interrupts['motion']);
    }

    public function setFreefallDetection(bool $flag): void
    {
        $flag ? $this->enableFreefallDetection() : $this->disableFreefallDetection();
    }

    protected function enableFreefallDetection(): void
    {
        $active_interrupts = $this->read(ADXL345ReadRegister::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? 0;
        $this->write(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [0x0]);
        $this->write(ADXL345OpCode::THRESHOLD_FREE_FALL_REGISTER, [10]);
        $this->write(ADXL345OpCode::TIME_FREE_FALL_REGISTER, [25]);
        $active_interrupts |= 0b00000100;
        $this->write(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$active_interrupts]);
        $this->enabled_interrupts['freefall'] = true;
    }

    protected function disableFreefallDetection(): void
    {
        $active_interrupts = $this->read(ADXL345ReadRegister::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? 0;
        $active_interrupts &= ~0b00000100;
        $this->write(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$active_interrupts]);
        unset($this->enabled_interrupts['freefall']);
    }

    public function setTapDetection(bool $flag): void
    {
        $flag ? $this->enableTapDetection() : $this->disableTapDetection();
    }

    protected function enableTapDetection(): void
    {
        $active_interrupts = $this->read(ADXL345ReadRegister::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? 0;
        $this->write(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [0x0]);
        $this->write(ADXL345OpCode::TAP_AXES_REGISTER, [0b00000111]);
        $this->write(ADXL345OpCode::THRESHOLD_TAP_REGISTER, [20]);
        $this->write(ADXL345OpCode::DURATION_REGISTER, [50]);
        $active_interrupts |= 0b01000000;
        $this->write(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$active_interrupts]);
        $this->enabled_interrupts['tap'] = 1;
    }

    protected function disableTapDetection(): void
    {
        $active_interrupts = $this->read(ADXL345ReadRegister::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? 0;
        $active_interrupts &= ~0b01000000;
        $active_interrupts &= ~0b00100000;
        $this->write(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$active_interrupts]);
        unset($this->enabled_interrupts['tap']);
    }

    public function getDataRate(): ADXL345DataRate
    {
        $register = $this->read(ADXL345ReadRegister::BW_RATE_REGISTER, 1)[0] ?? -1;
        $corrected = $register & 0x0F;

        return ADXL345DataRate::from($corrected);
    }

    public function setDataRate(ADXL345DataRate $rate): void
    {
        $this->write(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$rate->value]);
    }

    public function getRange(): ADXL345Range
    {
        $register = $this->read(ADXL345ReadRegister::DATA_FORMAT_REGISTER, 1)[0] ?? -1;
        $corrected = $register & 0x03;

        return ADXL345Range::from($corrected);
    }

    public function setRange(ADXL345Range $range): void
    {
        $format_register = $this->getRange()->value;
        $format_register &= ~0x0F;
        $format_register |= $range->value;
        $format_register |= 0x08;
        $this->write(ADXL345OpCode::DATA_FORMAT_REGISTER, [$format_register]);
    }

    public function getOffset(): array
    {
        [$x_offset, $y_offset, $z_offset] = $this->read(ADXL345ReadRegister::DATA_FROM_X_OFFSET_REGISTER, 3);

        $x_offset = ($x_offset & 0x80) ? $x_offset - 0x100 : $x_offset;
        $y_offset = ($y_offset & 0x80) ? $y_offset - 0x100 : $y_offset;
        $z_offset = ($z_offset & 0x80) ? $z_offset - 0x100 : $z_offset;

        return [$x_offset, $y_offset, $z_offset];
    }

    public function setOffset(array $values): void
    {
        [$x_offset, $y_offset, $z_offset] = array_values($values);
        $this->write(ADXL345OpCode::X_OFFSET_REGISTER, [$x_offset]);
        $this->write(ADXL345OpCode::Y_OFFSET_REGISTER, [$y_offset]);
        $this->write(ADXL345OpCode::Z_OFFSET_REGISTER, [$z_offset]);
    }
}
