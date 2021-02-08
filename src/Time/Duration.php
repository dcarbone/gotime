<?php declare(strict_types=1);

namespace DCarbone\Go\Time;

use DCarbone\Go\Time;

/**
 * Class Duration
 * @package DCarbone\GOTime
 */
class Duration implements \JsonSerializable
{
    /** @var int */
    private $ns;

    /**
     * TimeDuration constructor.
     * @param int $nanoseconds
     */
    public function __construct(int $nanoseconds = 0)
    {
        $this->ns = $nanoseconds;
    }

    /**
     * @return int
     */
    public function Nanoseconds(): int
    {
        return $this->ns;
    }

    /**
     * @return float
     */
    public function Microseconds(): float
    {
        return $this->ns / Time::Microsecond;
    }

    /**
     * @return float
     */
    public function Milliseconds(): float
    {
        return $this->ns / Time::Millisecond;
    }

    /**
     * @return float
     */
    public function Seconds(): float
    {
        return $this->ns / Time::Second;
    }

    /**
     * @return float
     */
    public function Minutes(): float
    {
        return $this->ns / Time::Minute;
    }

    /**
     * @return float
     */
    public function Hours(): float
    {
        return $this->ns / Time::Hour;
    }

    /**
     * @param \DCarbone\Go\Time\Duration $m
     * @return \DCarbone\Go\Time\Duration
     */
    public function Truncate(Duration $m): Duration
    {
        if (0 >= $m->ns) {
            return clone $this;
        }
        return new Duration($this->ns - $this->ns % $m->ns);
    }

    /**
     * @param \DCarbone\Go\Time\Duration $m
     * @return \DCarbone\Go\Time\Duration
     */
    public function Round(Duration $m): Duration
    {
        if (0 >= $m->ns) {
            return clone $this;
        }
        $r = $this->ns % $m->ns;
        if (0 > $this->ns) {
            $r = -$r;
            // TODO: this might do weird shit if greater than PHP_INT_MAX...
            if ($r + $r < $m->ns) {
                return new Duration($this->ns + $r);
            }
            $d1 = $this->ns - $m->ns + $r;
            if ($d1 < $this->ns) {
                return new Duration($d1);
            }
            return new Duration();
        }
        // TODO: this might do weird shit if greater than PHP_INT_MAX...
        if ($r + $r < $m->ns) {
            return new Duration($this->ns - $r);
        }
        $d1 = $this->ns + $m->ns - $r;
        if ($d1 > $this->ns) {
            return new Duration($d1);
        }
        return new Duration(PHP_INT_MAX);
    }

    /**
     * @param \DCarbone\Go\Time\Duration $other
     * @return int
     */
    public function Compare(Duration $other): int
    {
        return $this->ns === $other->ns ? 0 : ($this->ns > $other->ns ? 1 : -1);
    }

    /**
     * @return \DateInterval
     * @throws \Exception
     */
    public function DateInterval(): \DateInterval
    {
        return DateInterval::fromIntervalSpec($this->IntervalSpec());
    }

    /**
     * @return \DCarbone\Go\Time\IntervalSpec
     */
    public function IntervalSpec(): IntervalSpec
    {
        $neg = 0 > $this->ns;
        $u = $neg ? -$this->ns : $this->ns;

        $spec = new IntervalSpec();

        if ($u < Time::Microsecond) {
            $spec->spec = 'PT0S';
            return $spec;
        }

        $u = intdiv($u, Time::Microsecond);

        $buff = '';
        $u = self::_fmtFrac($buff, $u, 6);
        $spec->f = (float)$buff;

        $buff = 'S';
        self::_fmtInt($buff, $u % 60);
        $u = intdiv($u, 60);
        if ($u > 0) {
            $buff = "M{$buff}";
            self::_fmtInt($buff, $u % 60);
            $u = intdiv($u, 60);
            if ($u > 0) {
                $buff = "H{$buff}";
                self::_fmtInt($buff, $u);
            }
        }

        $spec->invert = $neg;
        $spec->spec = sprintf('PT%s', $buff);

        return $spec;
    }

    /**
     * @param string $buff
     * @param int $v
     * @param int $prec
     * @return int
     */
    private static function _fmtFrac(string &$buff, int $v, int $prec): int
    {
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
    private static function _fmtInt(string &$buff, int $v)
    {
        if (0 === $v) {
            $buff = "0{$buff}";
        } else {
            while ($v > 0) {
                $buff = sprintf('%d%s', $v % 10, $buff);
                $v = intdiv($v, 10);
            }
        }
    }

    /**
     * @return int
     */
    public function jsonSerialize()
    {
        return $this->ns;
    }

    /**
     * TODO: improve efficiency a bit...
     *
     * @return string
     */
    public function __toString(): string
    {
        if (0 === $this->ns) {
            return '0s';
        }

        $neg = $this->ns < 0;
        $u = $neg ? -$this->ns : $this->ns;

        if ($u < Time::Second) {
            if ($u < Time::Microsecond) {
                $prec = 0;
                $buff = 'ns';
            } elseif ($u < Time::Millisecond) {
                $prec = 3;
                $buff = 'µs';
            } else {
                $prec = 6;
                $buff = 'ms';
            }
            $u = self::_fmtFrac($buff, $u, $prec);
            self::_fmtInt($buff, $u);
        } else {
            $buff = 's';
            $u = self::_fmtFrac($buff, $u, 9);

            self::_fmtInt($buff, $u % 60);

            $u = intdiv($u, 60);

            if ($u > 0) {
                $buff = "m{$buff}";

                self::_fmtInt($buff, $u % 60);
                $u = intdiv($u, 60);

                if ($u > 0) {
                    $buff = "h{$buff}";
                    self::_fmtInt($buff, $u);
                }
            }
        }

        return $neg ? "-{$buff}" : $buff;
    }
}
