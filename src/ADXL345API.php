<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL345;

use DeptOfScrapyardRobotics\Sensors\ADXL345\Breakouts\ADXL345InterruptFunctions;
use DeptOfScrapyardRobotics\Sensors\ADXL345\Enums\ADXL345DataRate;
use DeptOfScrapyardRobotics\Sensors\ADXL345\Enums\ADXL345OpCode;
use DeptOfScrapyardRobotics\Sensors\ADXL345\Breakouts\ADXL345PowerControl;
use DeptOfScrapyardRobotics\Sensors\ADXL345\Enums\ADXL345Range;
use DeptOfScrapyardRobotics\Sensors\ADXL345\Enums\ADXL345SleepSamplingRate;
use BareMetal\Contracts\Sensors\Accelerometry\AxisOrientation;

trait ADXL345API
{
    use ADXL345InternalAPI;

    protected array $event_status = [];

    protected array $enabled_interrupts = [];

    public function getDeviceId(): int
    {
        [$id] = $this->readData(ADXL345OpCode::DEVICE_ID_REGISTER, 1);

        return $id;
    }

    public function getPowerControl(): ADXL345PowerControl
    {
        $byte = $this->readData(ADXL345OpCode::POWER_CONTROL_REGISTER, 1)[0] ?? -1;

        return ADXL345PowerControl::fromByte($byte);
    }

    public function getLinkMode(): bool
    {
        return $this->getPowerControl()->link;
    }

    public function getMeasurementMode(): bool
    {
        return $this->getPowerControl()->measurement_mode;
    }

    public function getSleepMode(): bool
    {
        return $this->getPowerControl()->sleep_mode;
    }

    public function getWakeup(): ADXL345SleepSamplingRate
    {
        return $this->getPowerControl()->wakeup;
    }

    public function getEnabledInterrupts(): ADXL345InterruptFunctions
    {
        $byte = $this->readData(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? -1;

        return ADXL345InterruptFunctions::fromByte($byte);
    }

    public function getDataRate(): ADXL345DataRate
    {
        $register = $this->readData(ADXL345OpCode::BW_RATE_REGISTER, 1)[0] ?? -1;
        $corrected = $register & 0x0F;

        return ADXL345DataRate::from($corrected);
    }

    public function getRawX(): float
    {
        $data = $this->readData(ADXL345OpCode::DATA_FROM_X0_REGISTER, 6);

        return $this->s16le($data[0] ?? 0, $data[1] ?? 0);
    }

    public function getRawY(): float
    {
        $data = $this->readData(ADXL345OpCode::DATA_FROM_X0_REGISTER, 6);

        return $this->s16le($data[2] ?? 0, $data[3] ?? 0);
    }

    public function getRawZ(): float
    {
        $data = $this->readData(ADXL345OpCode::DATA_FROM_X0_REGISTER, 6);

        return $this->s16le($data[4] ?? 0, $data[5] ?? 0);
    }

    public function getRange(): ADXL345Range
    {
        $register = $this->readData(ADXL345OpCode::DATA_FORMAT_REGISTER, 1)[0] ?? -1;
        $corrected = $register & 0x03;

        return ADXL345Range::from($corrected);
    }

    public function getOffset(): array
    {
        [$x_offset, $y_offset, $z_offset] = $this->readData(ADXL345OpCode::X_OFFSET_REGISTER, 3);

        $x_offset = ($x_offset & 0x80) ? $x_offset - 0x100 : $x_offset;
        $y_offset = ($y_offset & 0x80) ? $y_offset - 0x100 : $y_offset;
        $z_offset = ($z_offset & 0x80) ? $z_offset - 0x100 : $z_offset;

        return [$x_offset, $y_offset, $z_offset];
    }

    public function setPowerControl(ADXL345PowerControl $power_control): void
    {
        $this->sendCommand(ADXL345OpCode::POWER_CONTROL_REGISTER, [$power_control->toByte()]);
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

    public function setEnabledInterrupts(ADXL345InterruptFunctions $interrupts): void
    {
        $this->sendCommand(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$interrupts->toByte()]);
    }

    public function setDataRate(ADXL345DataRate $rate): void
    {
        $this->sendCommand(ADXL345OpCode::BW_RATE_REGISTER, [$rate->value]);
    }

    public function setRange(ADXL345Range $range): void
    {
        $format_register = $this->getRange()->value;
        $format_register &= ~0x0F;
        $format_register |= $range->value;
        $format_register |= 0x08;
        $this->sendCommand(ADXL345OpCode::DATA_FORMAT_REGISTER, [$format_register]);
    }

    public function setOffset(array $values): void
    {
        [$x_offset, $y_offset, $z_offset] = array_values($values);
        $this->sendCommand(ADXL345OpCode::X_OFFSET_REGISTER, [$x_offset]);
        $this->sendCommand(ADXL345OpCode::Y_OFFSET_REGISTER, [$y_offset]);
        $this->sendCommand(ADXL345OpCode::Z_OFFSET_REGISTER, [$z_offset]);
    }

    /**
     * One-shot hardware calibration: averages $samples readings while the
     * sensor sits still in a known orientation, compares against what it
     * *should* read (0g on the two flat axes, +/-1g on $vertical_axis),
     * and nudges OFSX/OFSY/OFSZ so the chip compensates on every future
     * read without any further software math.
     *
     * OFSX/OFSY/OFSZ are always 15.6 mg/LSB per the datasheet, regardless
     * of the currently selected ADXL345Range - only DATA_FROM_X/Y/Z0 output
     * scaling depends on range/full-res.
     *
     * @return array{0:int,1:int,2:int} the new offset register values written
     */
    public function calibrate(AxisOrientation $vertical_axis = AxisOrientation::Z, int $samples = 32, int $delay_us = 20_000): array
    {
        $scale = $this->getRange()->scale();
        $offset_scale = 0.0156;

        $totals = ['x' => 0.0, 'y' => 0.0, 'z' => 0.0];
        for ($i = 0; $i < $samples; $i++) {
            $totals['x'] += $this->getRawX();
            $totals['y'] += $this->getRawY();
            $totals['z'] += $this->getRawZ();
            usleep($delay_us);
        }

        $measured_g = [
            'x' => ($totals['x'] / $samples) * $scale,
            'y' => ($totals['y'] / $samples) * $scale,
            'z' => ($totals['z'] / $samples) * $scale,
        ];

        $expected_g = [
            'x' => match ($vertical_axis) {
                AxisOrientation::X => 1.0,
                AxisOrientation::X_INVERTED => -1.0,
                default => 0.0,
            },
            'y' => match ($vertical_axis) {
                AxisOrientation::Y => 1.0,
                AxisOrientation::Y_INVERTED => -1.0,
                default => 0.0,
            },
            'z' => match ($vertical_axis) {
                AxisOrientation::Z => 1.0,
                AxisOrientation::Z_INVERTED => -1.0,
                default => 0.0,
            },
        ];

        [$x_offset, $y_offset, $z_offset] = $this->getOffset();

        $offsets = [
            static::clampOffsetByte($x_offset + (int) round(($expected_g['x'] - $measured_g['x']) / $offset_scale)),
            static::clampOffsetByte($y_offset + (int) round(($expected_g['y'] - $measured_g['y']) / $offset_scale)),
            static::clampOffsetByte($z_offset + (int) round(($expected_g['z'] - $measured_g['z']) / $offset_scale)),
        ];

        $this->setOffset($offsets);

        return $offsets;
    }

    private static function clampOffsetByte(int $value): int
    {
        return max(-128, min(127, $value));
    }

    public function getEvents(): array
    {
        $interrupt_source_register = $this->readData(ADXL345OpCode::INTERRUPT_SOURCE_REGISTER, 1)[0] ?? 0;
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
        $active_interrupts = $this->readData(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? 0;
        $this->sendCommand(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [0x0]);
        $this->sendCommand(ADXL345OpCode::ACTIVITY_AND_INACTIVITY_CONTROL_REGISTER, [0b01110000]);
        $this->sendCommand(ADXL345OpCode::THRESHOLD_ACTIVITY_REGISTER, [18]);
        $active_interrupts |= 0b00010000;
        $this->sendCommand(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$active_interrupts]);
        $this->enabled_interrupts['motion'] = true;
    }

    protected function disableMotionDetection(): void
    {
        $active_interrupts = $this->readData(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? 0;
        $active_interrupts &= ~0b00010000;
        $this->sendCommand(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$active_interrupts]);
        unset($this->enabled_interrupts['motion']);
    }

    public function setFreefallDetection(bool $flag): void
    {
        $flag ? $this->enableFreefallDetection() : $this->disableFreefallDetection();
    }

    protected function enableFreefallDetection(): void
    {
        $active_interrupts = $this->readData(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? 0;
        $this->sendCommand(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [0x0]);
        $this->sendCommand(ADXL345OpCode::THRESHOLD_FREE_FALL_REGISTER, [10]);
        $this->sendCommand(ADXL345OpCode::TIME_FREE_FALL_REGISTER, [25]);
        $active_interrupts |= 0b00000100;
        $this->sendCommand(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$active_interrupts]);
        $this->enabled_interrupts['freefall'] = true;
    }

    protected function disableFreefallDetection(): void
    {
        $active_interrupts = $this->readData(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? 0;
        $active_interrupts &= ~0b00000100;
        $this->sendCommand(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$active_interrupts]);
        unset($this->enabled_interrupts['freefall']);
    }

    public function setTapDetection(bool $flag): void
    {
        $flag ? $this->enableTapDetection() : $this->disableTapDetection();
    }

    protected function enableTapDetection(): void
    {
        $active_interrupts = $this->readData(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? 0;
        $this->sendCommand(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [0x0]);
        $this->sendCommand(ADXL345OpCode::TAP_AXES_REGISTER, [0b00000111]);
        $this->sendCommand(ADXL345OpCode::THRESHOLD_TAP_REGISTER, [20]);
        $this->sendCommand(ADXL345OpCode::DURATION_REGISTER, [50]);
        $active_interrupts |= 0b01000000;
        $this->sendCommand(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$active_interrupts]);
        $this->enabled_interrupts['tap'] = 1;
    }

    protected function disableTapDetection(): void
    {
        $active_interrupts = $this->readData(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, 1)[0] ?? 0;
        $active_interrupts &= ~0b01000000;
        $active_interrupts &= ~0b00100000;
        $this->sendCommand(ADXL345OpCode::INTERRUPTS_ENABLED_REGISTER, [$active_interrupts]);
        unset($this->enabled_interrupts['tap']);
    }
}
