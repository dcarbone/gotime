<?php declare(strict_types=1);

namespace DCarbone\Go\Time;

class Month implements \JsonSerializable
{
    private const MONTHS = [
        1  => 'January',
        2  => 'February',
        3  => 'March',
        4  => 'April',
        5  => 'May',
        6  => 'June',
        7  => 'July',
        8  => 'August',
        9  => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    private const SHORT = [
        1  => 'Jan',
        2  => 'Feb',
        3  => 'Mar',
        4  => 'Apr',
        5  => 'May',
        6  => 'Jun',
        7  => 'Jul',
        8  => 'Aug',
        9  => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dec',
    ];

    private int $m = 1;

    public function __construct(int $m = 1)
    {
        if (isset(self::MONTHS[$m])) {
            $this->m = $m;
        }
    }

    public function Ord(): int
    {
        return $this->m;
    }

    public function Is(int|Month $other): bool
    {
        return ($other instanceof Month) ? $this->m == $other->m : $this->m === $other;
    }

    public function Short(): string
    {
        return self::SHORT[$this->m] ?? 'UNK';
    }

    public function __toString(): string
    {
        return self::MONTHS[$this->m] ?? 'UNKNOWN';
    }

    public function jsonSerialize(): string
    {
        return (string)$this;
    }
}