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
        $sec = $this->nanoseconds / Time::Second;
        $nsec = $this->nanoseconds % Time::Second;
        return $sec + $nsec / GOTIME_FLOAT_DIVISOR;
    }

    /**
     * @return float
     */
    public function Minutes(): float {
        $min = $this->nanoseconds / Time::Minute;
        $nsec = $this->nanoseconds % Time::Minute;
        return $min + $nsec / (60 * GOTIME_FLOAT_DIVISOR);
    }

    /**
     * @return float
     */
    public function Hours(): float {
        $hour = $this->nanoseconds / Time::Hour;
        $nsec = $this->nanoseconds % Time::Hour;
        return $hour + $nsec / (60 * 60 * GOTIME_FLOAT_DIVISOR);
    }

    /**
     * @return \DateTime
     */
    public function DateTime(): \DateTime {
        return \DateTime::createFromFormat('U', $this->Seconds());
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
            $u = Time::fmtFrac($buff, $u, $prec);
            Time::fmtInt($buff, $u);
        } else {
            $buff = "s{$buff}";
            $u = Time::fmtFrac($buff, $u, 9);

            Time::fmtInt($buff, $u % 60);

            $u = floor($u /= 60);

            if ($u > 0) {
                $buff = "m{$buff}";

                Time::fmtInt($buff, $u % 60);
                $u = floor($u /= 60);

                if ($u > 0) {
                    $buff = "h{$buff}";
                    Time::fmtInt($buff, $u);
                }
            }
        }

        return $neg ? "-{$buff}" : $buff;
    }
}
