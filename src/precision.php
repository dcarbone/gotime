<?php declare(strict_types=1);

namespace DCarbone\Go;

define('GOTIME_OVERFLOW_INT', intdiv(PHP_INT_MAX, 10));
define('GOTIME_GTE71', PHP_VERSION_ID >= 70100);