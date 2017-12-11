<?php namespace DCarbone\Go\Time;

use DCarbone\Go\Time;

/**
 * Class Duration
 * @package DCarbone\GOTime
 */
class Duration implements \JsonSerializable {
    /** @var int */
    private $nanoseconds = 0;

    /**
     * TimeDuration constructor.
     * @param int $nanoseconds
     */
    public function __construct(int $nanoseconds = 0) {
        $this->nanoseconds = $nanoseconds;
    }

    /**
     * @return int
     */
    public function Nanoseconds(): int {
        return $this->nanoseconds;
    }

    /**
     * @return float
     */
    public function Seconds(): float {
        return $this->nanoseconds / Time::Second;
    }

    /**
     * @return float
     */
    public function Minutes(): float {
        return $this->nanoseconds / Time::Minute;
    }

    /**
     * @return float
     */
    public function Hours(): float {
        return $this->nanoseconds / Time::Hour;
    }

    /**
     * @return \DateTime
     */
    public function DateTime(): \DateTime {
        return \DateTime::createFromFormat('U', intdiv($this->nanoseconds, Time::Second));
    }

    /**
     * @return int
     */
    public function jsonSerialize() {
        return $this->nanoseconds;
    }

    /**
     * TODO: improve efficiency a bit...
     *
     * @return string
     */
    public function __toString() {
        if (0 === $this->nanoseconds) {
            return '0s';
        }

        $buff = '';

        $u = $this->nanoseconds;
        $neg = $this->nanoseconds < 0;
        if ($neg) {
            $u = -$u;
        }

        if ($u < Time::Second) {
            $prec = 0;
            switch (true) {
                case $u < Time::Microsecond:
                    $buff = 'ns';
                    break;
                case $u < Time::Millisecond:
                    $prec = 3;
                    $buff = 'µs';
                    break;
                default:
                    $prec = 6;
                    $buff = 'ms';
            }
            $u = self::fmtFrac($buff, $u, $prec);
            self::fmtInt($buff, $u);
        } else {
            $buff = "s{$buff}";
            $u = self::fmtFrac($buff, $u, 9);

            self::fmtInt($buff, $u % 60);

            $u = intdiv($u, 60);

            if ($u > 0) {
                $buff = "m{$buff}";

                self::fmtInt($buff, $u % 60);
                $u = intdiv($u, 60);

                if ($u > 0) {
                    $buff = "h{$buff}";
                    self::fmtInt($buff, $u);
                }
            }
        }

        return $neg ? "-{$buff}" : $buff;
    }

    /**
     * @param string $buff
     * @param int $v
     * @param int $prec
     * @return int
     */
    private static function fmtFrac(string &$buff, int $v, int $prec): int {
        $print = false;
        for ($i = 0; $i < $prec; $i++) {
            $digit = $v % 10;
            $print = $print || $digit !== 0;
            if ($print) {
                $buff = "{$digit}{$buff}";
            }
            $v = intdiv($v, 10);
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
    private static function fmtInt(string &$buff, int $v) {
        if (0 === $v) {
            $buff = "0{$buff}";
        } else {
            while ($v > 0) {
                $buff = sprintf('%d%s', $v % 10, $buff);
                $v = intdiv($v, 10);
            }
        }
    }
}
