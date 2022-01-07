<?php declare(strict_types=1);

namespace DCarbone\Go\Time;

/**
 * Class Month
 * @package DCarbone\Go\Time
 */
class Month implements \JsonSerializable
{
    /** @var array */
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

    /** @var array */
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

    /** @var int */
    private int $m = 1;

    /**
     * Month constructor.
     * @param int $m
     */
    public function __construct(int $m = 1)
    {
        if (isset(self::MONTHS[$m])) {
            $this->m = $m;
        }
    }

    /**
     * @return int
     */
    public function Ord(): int
    {
        return $this->m;
    }

    /**
     * @param int $ord
     * @return bool
     */
    public function Is(int $ord): bool
    {
        return $this->m === $ord;
    }

    /**
     * @return string
     */
    public function Short(): string
    {
        return self::SHORT[$this->m] ?? 'UNK';
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return self::MONTHS[$this->m] ?? 'UNKNOWN';
    }

    /**
     * @return string
     */
    public function jsonSerialize(): mixed
    {
        return (string)$this;
    }
}