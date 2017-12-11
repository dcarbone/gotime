<?php namespace DCarbone\Go;

$p = (int)ini_get('precision');
if (0 === $p) {
    $p = 17;
    ini_set('precision', $p);
}
define('GOTIME_FLOAT_PRECISION', $p);
define('GOTIME_FLOAT_DIVISOR', 1 * 10 ** $p);
unset($p);
