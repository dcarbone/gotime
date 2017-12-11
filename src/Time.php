<?php namespace DCarbone\Go;

use DCarbone\Go\Time\Duration;

/**
 * Class Time
 * @package DCarbone\Go
 */
class Time {
    const Nanosecond = 1;
    const Microsecond = 1000 * self::Nanosecond;
    const Millisecond = 1000 * self::Microsecond;
    const Second = 1000 * self::Millisecond;
    const Minute = 60 * self::Second;
    const Hour = 60 * self::Minute;

    const unitMap = [
        'ns' => self::Nanosecond,
        'us' => self::Microsecond,
        'µs' => self::Microsecond,
        'μs' => self::Microsecond,
        'ms' => self::Millisecond,
        's'  => self::Second,
        'm'  => self::Minute,
        'h'  => self::Hour,
    ];

    /**
     * @param string $string
     * @return \DCarbone\Go\Time\Duration
     */
    function ParseDuration(string $string): Duration {
        if (0 === strlen($string)) {
            throw new \InvalidArgumentException('Invalid duration: empty input');
        }
        $bits = [];
        $orig = $string;
        $neg = '-' === $string[0];

        // consume symbol
        if ('-' === $string[0] || '+' === $string[0]) {
            $string = substr($string, 1);
        }

        if ('0' === $string) {
            return new Duration();
        } else if ('' === $string) {
            throw new \InvalidArgumentException("Invalid duration: {$orig}");
        }

        $bit = '';
        foreach (str_split($string) as $s) {
            $chr = ord($s);
            // number
            if (48 <= $chr && $chr <= 57) {
                if ('' !== $bit) {
                    $bits[] = $bit;
                    $bit = '';
                }
                $bit .= $s;
            }
        }

        if (0 === count($bits)) {
            throw new \InvalidArgumentException("Invalid duration: {$orig}");
        }


        $parts = [
            'ns' => self::Nanosecond,
            'us' => self::Microsecond,

        ];
        foreach ($bits as $bit) {

        }

        $orig = $string;
        $d = 0;
        $neg = false;

        if ('' !== $string) {
            $c = $string[0];
            if ('-' === $c || '+' === $c) {
                $neg = '-' === $c;
                $string = substr($string, 1);
            }
        }
        if ('0' === $string) {
            return new Duration(0);
        }
        if ('' === $string) {
            throw new \InvalidArgumentException("Invalid duration: {$orig}");
        }
        while ('' !== $string) {
            $v = $f = 0;
            $scale = 1.0;
            if (!('.' === $string[0] || 0 <= $string[0] && $string[0] <= 9)) {
                throw new \InvalidArgumentException("Invalid duration: {$orig}");
            }
            $pl = strlen($string);
            $post = false;

        }
    }


    /**
     * @param string $buff
     * @param int $v
     * @param int $prec
     * @return int
     */
    public static function fmtFrac(string &$buff, int $v, int $prec): int {
        $print = false;
        for ($i = 0; $i < $prec; $i++) {
            $digit = $v % 10;
            $print = $print || $digit !== 0;
            if ($print) {
                $buff = "{$digit}{$buff}";
            }
            $v = floor($v /= 10);
        }
        if ($print) {
            $buff = ".{$buff}";
        }
        return $v;
    }

    /**
     * @param string $buff
     * @param int $v
     * @return void
     */
    public static function fmtInt(string &$buff, int $v) {
        while ($v > 0) {
            $buff = sprintf('%d%s', $v % 10, $buff);
            $v = floor($v /= 10);
        }
    }
}
