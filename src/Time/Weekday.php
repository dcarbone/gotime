<?php declare(strict_types=1);

namespace DCarbone\Go\Time;

class Weekday implements \JsonSerializable
{
    private const WEEKDAYS = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ];

    private const SHORT = [
        'Sun',
        'Mon',
        'Tue',
        'Wed',
        'Thu',
        'Fri',
        'Sat',
    ];

    private int $d = 0;

    public function __construct(int $d = 0)
    {
        if (isset(self::WEEKDAYS[$d])) {
            $this->d = $d;
        }
    }

    public function Ord(): int
    {
        return $this->d;
    }

    public function Is(int|Weekday $other): bool
    {
        return ($other instanceof Weekday) ? $this->d == $other->d : $this->d === $other;
    }

    public function Short(): string
    {
        return self::SHORT[$this->d] ?? 'UNK';
    }

    public function __toString(): string
    {
        return self::WEEKDAYS[$this->d] ?? 'UNKNOWN';
    }

    public function jsonSerialize(): string
    {
        return (string)$this;
    }
}