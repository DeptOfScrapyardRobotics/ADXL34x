<?php

namespace DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Concerns;

use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Breakouts\ADXL343ActivityControl;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Breakouts\ADXL343DataFormat;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Breakouts\ADXL343InterruptFunctions;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Breakouts\ADXL343InterruptMap;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Breakouts\ADXL343PowerControl;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Breakouts\ADXL343TapAxes;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums\ADXL343DataRate;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums\ADXL343OpCode;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums\ADXL343Range;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL343\Enums\ADXL343SleepSamplingRate;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\ADXL34xException;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Enums\AxisOrientation;
use DeptOfScrapyardRobotics\Sensors\ADXL34x\Transports\ADXL34xDataTransport;
use GeneralPurposeIO\Contracts\NutsAndBolts\Splices16Bits;

trait ADXL343API
{
    use Splices16Bits;

    /** Interrupt routing as last written or read: the dispatcher reads these instead of the bus. */
    protected ?ADXL343InterruptFunctions $_interrupt_enable = null;

    protected ?ADXL343InterruptMap $_interrupt_map = null;

    protected ?bool $_interrupt_active_low = null;

    abstract public function transport(): ADXL34xDataTransport;

    protected function sendCommand(ADXL343OpCode $register, array $command_data = []): int
    {
        return $this->transport()->write($register->value, $command_data);
    }

    protected function readData(ADXL343OpCode $register, int $length): array
    {
        return $this->transport()->read($register->value, $length);
    }

    public function getDeviceId(): int
    {
        [$id] = $this->readData(ADXL343OpCode::DEVICE_ID_REGISTER, 1);

        return (int) $id;
    }

    public function getPowerControl(): ADXL343PowerControl
    {
        $byte = $this->readData(ADXL343OpCode::POWER_CONTROL_REGISTER, 1)[0];

        return ADXL343PowerControl::fromByte($byte);
    }

    public function setPowerControl(ADXL343PowerControl $power_control): void
    {
        $this->sendCommand(ADXL343OpCode::POWER_CONTROL_REGISTER, [$power_control->toByte()]);
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
        $this->setPowerControl($new_control);
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
        $this->setPowerControl($new_control);
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
        $this->setPowerControl($new_control);
    }

    public function getWakeup(): ADXL343SleepSamplingRate
    {
        return $this->getPowerControl()->wakeup;
    }

    public function setWakeup(ADXL343SleepSamplingRate $wakeup): void
    {
        $pwr_control = $this->getPowerControl();
        $new_control = new $pwr_control(
            $pwr_control->link,
            $pwr_control->measurement_mode,
            $pwr_control->sleep_mode,
            $wakeup
        );
        $this->setPowerControl($new_control);
    }

    public function getEnabledInterrupts(): ADXL343InterruptFunctions
    {
        $byte = $this->readData(ADXL343OpCode::INTERRUPTS_ENABLED_REGISTER, 1)[0];

        return $this->_interrupt_enable = ADXL343InterruptFunctions::fromByte($byte);
    }

    public function setEnabledInterrupts(ADXL343InterruptFunctions $interrupts): void
    {
        $this->sendCommand(ADXL343OpCode::INTERRUPTS_ENABLED_REGISTER, [$interrupts->toByte()]);
        $this->_interrupt_enable = $interrupts;
    }

    /** INT_MAP: which line each function fires on. */
    public function getInterruptMap(): ADXL343InterruptMap
    {
        $byte = $this->readData(ADXL343OpCode::INTERRUPT_MAP_REGISTER, 1)[0];

        return $this->_interrupt_map = ADXL343InterruptMap::fromByte($byte);
    }

    public function setInterruptMap(ADXL343InterruptMap $map): void
    {
        $this->sendCommand(ADXL343OpCode::INTERRUPT_MAP_REGISTER, [$map->toByte()]);
        $this->_interrupt_map = $map;
    }

    /** INT_SOURCE: what has fired. Reading it clears single tap, double tap, activity, inactivity and free fall. */
    public function getInterruptSource(): ADXL343InterruptFunctions
    {
        return ADXL343InterruptFunctions::fromByte($this->readData(ADXL343OpCode::INTERRUPT_SOURCE_REGISTER, 1)[0]);
    }

    /**
     * Enable mask and line map, from the bus only the first time.
     *
     * @return array{ADXL343InterruptFunctions, ADXL343InterruptMap}
     */
    public function interruptRouting(): array
    {
        $enabled = $this->_interrupt_enable ?? $this->getEnabledInterrupts();

        if ($enabled->functions() === []) {
            return [$enabled, $this->_interrupt_map ?? ADXL343InterruptMap::none()];
        }

        return [$enabled, $this->_interrupt_map ?? $this->getInterruptMap()];
    }

    /** INT_INVERT from DATA_FORMAT, from the bus only the first time. */
    public function interruptsActiveLow(): bool
    {
        return $this->_interrupt_active_low ?? $this->getDataFormat()->int_invert;
    }

    public function getTapThreshold(): int
    {
        return $this->readByte(ADXL343OpCode::THRESHOLD_TAP_REGISTER);
    }

    /** 62.5 mg per count. */
    public function setTapThreshold(int $value): void
    {
        $this->writeByte(ADXL343OpCode::THRESHOLD_TAP_REGISTER, $value, 'tap_threshold');
    }

    public function getTapDuration(): int
    {
        return $this->readByte(ADXL343OpCode::DURATION_REGISTER);
    }

    /** 625 µs per count: the longest a tap may stay above threshold. */
    public function setTapDuration(int $value): void
    {
        $this->writeByte(ADXL343OpCode::DURATION_REGISTER, $value, 'tap_duration');
    }

    public function getTapLatency(): int
    {
        return $this->readByte(ADXL343OpCode::LATENCY_REGISTER);
    }

    /** 1.25 ms per count: wait after a tap before the double-tap window opens. 0 disables double tap. */
    public function setTapLatency(int $value): void
    {
        $this->writeByte(ADXL343OpCode::LATENCY_REGISTER, $value, 'tap_latency');
    }

    public function getTapWindow(): int
    {
        return $this->readByte(ADXL343OpCode::WINDOW_REGISTER);
    }

    /** 1.25 ms per count: how long the second tap may take. 0 disables double tap. */
    public function setTapWindow(int $value): void
    {
        $this->writeByte(ADXL343OpCode::WINDOW_REGISTER, $value, 'tap_window');
    }

    public function getTapAxes(): ADXL343TapAxes
    {
        return ADXL343TapAxes::fromByte($this->readByte(ADXL343OpCode::TAP_AXES_REGISTER));
    }

    public function setTapAxes(ADXL343TapAxes $axes): void
    {
        $this->sendCommand(ADXL343OpCode::TAP_AXES_REGISTER, [$axes->toByte()]);
    }

    public function getActivityThreshold(): int
    {
        return $this->readByte(ADXL343OpCode::THRESHOLD_ACTIVITY_REGISTER);
    }

    /** 62.5 mg per count. */
    public function setActivityThreshold(int $value): void
    {
        $this->writeByte(ADXL343OpCode::THRESHOLD_ACTIVITY_REGISTER, $value, 'activity_threshold');
    }

    public function getInactivityThreshold(): int
    {
        return $this->readByte(ADXL343OpCode::THRESHOLD_INACTIVITY_REGISTER);
    }

    /** 62.5 mg per count. */
    public function setInactivityThreshold(int $value): void
    {
        $this->writeByte(ADXL343OpCode::THRESHOLD_INACTIVITY_REGISTER, $value, 'inactivity_threshold');
    }

    public function getInactivityTime(): int
    {
        return $this->readByte(ADXL343OpCode::TIME_INACTIVITY_REGISTER);
    }

    /** 1 s per count: how long motion must stay below the inactivity threshold. */
    public function setInactivityTime(int $value): void
    {
        $this->writeByte(ADXL343OpCode::TIME_INACTIVITY_REGISTER, $value, 'inactivity_time');
    }

    public function getActivityControl(): ADXL343ActivityControl
    {
        return ADXL343ActivityControl::fromByte($this->readByte(ADXL343OpCode::ACTIVITY_AND_INACTIVITY_CONTROL_REGISTER));
    }

    public function setActivityControl(ADXL343ActivityControl $control): void
    {
        $this->sendCommand(ADXL343OpCode::ACTIVITY_AND_INACTIVITY_CONTROL_REGISTER, [$control->toByte()]);
    }

    public function getFreeFallThreshold(): int
    {
        return $this->readByte(ADXL343OpCode::THRESHOLD_FREE_FALL_REGISTER);
    }

    /** 62.5 mg per count; the datasheet recommends 300 mg to 600 mg (0x05 to 0x09). */
    public function setFreeFallThreshold(int $value): void
    {
        $this->writeByte(ADXL343OpCode::THRESHOLD_FREE_FALL_REGISTER, $value, 'free_fall_threshold');
    }

    public function getFreeFallTime(): int
    {
        return $this->readByte(ADXL343OpCode::TIME_FREE_FALL_REGISTER);
    }

    /** 5 ms per count; the datasheet recommends 100 ms to 350 ms (0x14 to 0x46). */
    public function setFreeFallTime(int $value): void
    {
        $this->writeByte(ADXL343OpCode::TIME_FREE_FALL_REGISTER, $value, 'free_fall_time');
    }

    protected function readByte(ADXL343OpCode $register): int
    {
        return $this->readData($register, 1)[0];
    }

    protected function writeByte(ADXL343OpCode $register, int $value, string $name): void
    {
        if ($value < 0 || $value > 0xFF) {
            throw ADXL34xException::registerOutOfRange($name, $value);
        }

        $this->sendCommand($register, [$value]);
    }

    public function getDataRate(): ADXL343DataRate
    {
        $register = $this->readData(ADXL343OpCode::BW_RATE_REGISTER, 1)[0];
        $corrected = $register & 0x0F;

        return ADXL343DataRate::from($corrected);
    }

    public function setDataRate(ADXL343DataRate $rate): void
    {
        $this->sendCommand(ADXL343OpCode::BW_RATE_REGISTER, [$rate->value]);
    }

    public function getDataFormat(): ADXL343DataFormat
    {
        $format = ADXL343DataFormat::fromByte($this->readData(ADXL343OpCode::DATA_FORMAT_REGISTER, 1)[0]);
        $this->_interrupt_active_low = $format->int_invert;

        return $format;
    }

    public function setDataFormat(ADXL343DataFormat $format): void
    {
        $this->sendCommand(ADXL343OpCode::DATA_FORMAT_REGISTER, [$format->toByte()]);
        $this->_interrupt_active_low = $format->int_invert;
    }

    public function getRange(): ADXL343Range
    {
        return $this->getDataFormat()->range;
    }

    /** Change the range and nothing else in DATA_FORMAT. */
    public function setRange(ADXL343Range $range): void
    {
        $this->setDataFormat($this->getDataFormat()->withRange($range));
    }

    /** true = full resolution (3.9 mg/LSB at every range), false = 10-bit (LSB grows with range). */
    public function getResolution(): bool
    {
        return $this->getDataFormat()->full_resolution;
    }

    public function setResolution(bool $full): void
    {
        $this->setDataFormat($this->getDataFormat()->withResolution($full));
    }

    /** g per LSB as the chip is configured right now: one bus read. */
    public function scale(): float
    {
        $format = $this->getDataFormat();

        return $format->full_resolution ? ADXL343Range::G2->scale() : $format->range->scale();
    }

    public function getOffset(): array
    {
        [$x_offset, $y_offset, $z_offset] = $this->readData(ADXL343OpCode::X_OFFSET_REGISTER, 3);

        $x_offset = ($x_offset & 0x80) ? $x_offset - 0x100 : $x_offset;
        $y_offset = ($y_offset & 0x80) ? $y_offset - 0x100 : $y_offset;
        $z_offset = ($z_offset & 0x80) ? $z_offset - 0x100 : $z_offset;

        return [$x_offset, $y_offset, $z_offset];
    }

    public function setOffset(array $values): void
    {
        [$x_offset, $y_offset, $z_offset] = array_values($values);
        $this->sendCommand(ADXL343OpCode::X_OFFSET_REGISTER, [$x_offset]);
        $this->sendCommand(ADXL343OpCode::Y_OFFSET_REGISTER, [$y_offset]);
        $this->sendCommand(ADXL343OpCode::Z_OFFSET_REGISTER, [$z_offset]);
    }

    public function calibrate(AxisOrientation $vertical_axis = AxisOrientation::Z, int $samples = 32, int $delay_us = 20_000): array
    {
        $scale = $this->scale();
        $offset_scale = 0.0156;

        $totals = ['x' => 0.0, 'y' => 0.0, 'z' => 0.0];
        for ($i = 0; $i < $samples; $i++) {
            $raw = $this->raw();
            $totals['x'] += $raw['x'];
            $totals['y'] += $raw['y'];
            $totals['z'] += $raw['z'];
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

    /**
     * All three axes from one six-byte burst read, signed little-endian counts.
     *
     * @return array{x: int, y: int, z: int}
     */
    public function raw(): array
    {
        $data = $this->readData(ADXL343OpCode::DATA_FROM_X0_REGISTER, 6);

        return [
            'x' => $this->s16le($data[0], $data[1]),
            'y' => $this->s16le($data[2], $data[3]),
            'z' => $this->s16le($data[4], $data[5]),
        ];
    }

    public function getRawX(): int
    {
        return $this->raw()['x'];
    }

    public function getRawY(): int
    {
        return $this->raw()['y'];
    }

    public function getRawZ(): int
    {
        return $this->raw()['z'];
    }

    private static function clampOffsetByte(int $value): int
    {
        return max(-128, min(127, $value));
    }

}