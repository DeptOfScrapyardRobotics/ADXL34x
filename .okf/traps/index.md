# Traps

* [sample-per-call.md](/traps/sample-per-call.md) - x() / y() / z() each take own sample; use acceleration()
* [sleep-3hz-is-1hz.md](/traps/sleep-3hz-is-1hz.md) - SleepSamplingRate::SLEEP_3HZ selects 1 Hz wake-up sampling
* [level-interrupts-repeat.md](/traps/level-interrupts-repeat.md) - DATA_READY / WATERMARK / OVERRUN repeat until data read
* [shared-int-line.md](/traps/shared-int-line.md) - don't GPIO::watch an INT line the dispatcher uses
* [celestial-body-scale.md](/traps/celestial-body-scale.md) - acceleration(CelestialBody) gives m/s² only with TERRA
