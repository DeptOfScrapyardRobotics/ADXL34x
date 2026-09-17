<?php

/*
| Proven against a recording fake I2C transport: every register byte the chip
| would see, every byte it would answer. Nothing here touches a bus. The
| live check is an ADXL345 on an FT232H at 0x53.
*/
