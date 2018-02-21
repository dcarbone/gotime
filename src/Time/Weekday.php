<?php namespace DCarbone\Go\Time;

/**
 * Class Weekday
 * @package DCarbone\Go\Time
 */
class Weekday implements \JsonSerializable {
    /** @var array */
    private static $weekdays = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ];

    /** @var array */
    private static $short = [
        'Sun',
        'Mon',
        'Tue',
        'Wed',
        'Thu',
        'Fri',
        'Sat',
    ];

    /** @var int */
    private $d = 0;

    /**
     * Weekday constructor.
     * @param int $d
     */
    public function __construct(int $d = 0) {
        if (isset(self::$weekdays[$d])) {
            $this->d = $d;
        }
    }

    /**
     * @return int
     */
    public function Ord(): int {
        return $this->d;
    }

    /**
     * @param int $d
     * @return bool
     */
    public function Is(int $d): bool {
        return $this->d === $d;
    }

    public function Short(): string {
        return self::$short[$this->d] ?? 'UNK';
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return self::$weekdays[$this->d] ?? 'UNKNOWN';
    }

    /**
     * @return string
     */
    public function jsonSerialize() {
        return (string)$this;
    }
}