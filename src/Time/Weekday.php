<?php declare(strict_types=1);

namespace DCarbone\Go\Time;

/**
 * Class Weekday
 * @package DCarbone\Go\Time
 */
class Weekday implements \JsonSerializable
{
    /** @var array */
    private const WEEKDAYS = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ];

    /** @var array */
    private const SHORT = [
        'Sun',
        'Mon',
        'Tue',
        'Wed',
        'Thu',
        'Fri',
        'Sat',
    ];

    /** @var int */
    private int $d = 0;

    /**
     * Weekday constructor.
     * @param int $d
     */
    public function __construct(int $d = 0)
    {
        if (isset(self::WEEKDAYS[$d])) {
            $this->d = $d;
        }
    }

    /**
     * @return int
     */
    public function Ord(): int
    {
        return $this->d;
    }

    /**
     * @param int $d
     * @return bool
     */
    public function Is(int $d): bool
    {
        return $this->d === $d;
    }

    public function Short(): string
    {
        return self::SHORT[$this->d] ?? 'UNK';
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return self::WEEKDAYS[$this->d] ?? 'UNKNOWN';
    }

    /**
     * @return string
     */
    public function jsonSerialize(): mixed
    {
        return (string)$this;
    }
}