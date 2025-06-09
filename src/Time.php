<?php declare(strict_types=1);

namespace DCarbone\Go;

/**
 * Class Time
 * @package DCarbone\Go
 */
abstract class Time
{
    public const Nanosecond  = 1;
    public const Microsecond = 1000 * self::Nanosecond;
    public const Millisecond = 1000 * self::Microsecond;
    public const Second      = 1000 * self::Millisecond;
    public const Minute      = 60 * self::Second;
    public const Hour        = 60 * self::Minute;

    // @see https://pumas.nasa.gov/examples/index.php?id=46
    private const yearToHours = 365.2422 * 24;
    // @see https://en.wikipedia.org/wiki/Month
    private const monthsToHours = 30.436875 * 24;

    /** @var array */
    private const unitMap = [
        'ns' => self::Nanosecond,
        'us' => self::Microsecond,
        'µs' => self::Microsecond,
        'μs' => self::Microsecond,
        'ms' => self::Millisecond,
        's'  => self::Second,
        'm'  => self::Minute,
        'h'  => self::Hour,
    ];

    private static Time\Duration $_zeroDuration;

    private function __construct()
    {
        // not designed to be constructed
    }

    public static function New(): Time\Time
    {
        return new Time\Time('@0');
    }

    public static function Now(): Time\Time
    {
        $mt = microtime();
        return Time\Time::createFromFormat('0.u00 U', $mt);
    }

    /**
     * @throws \Exception
     */
    public static function Since(Time\Time $t): Time\Duration
    {
        return static::Now()->sub($t->UnixNanoDuration()->DateInterval())->UnixNanoDuration();
    }

    public static function SinceDateTime(\DateTimeInterface $dt): Time\Duration
    {
        return Time::Duration((clone $dt)->diff(new \DateTime()));
    }

    /**
     * @throws \DateInvalidOperationException
     * @throws \Exception
     */
    public static function Until(Time\Time $t): Time\Duration
    {
        return (clone $t)->sub(time::Now()->UnixNanoDuration()->DateInterval())->UnixNanoDuration();
    }

    public static function UntilDateTime(\DateTimeInterface $dt): Time\Duration
    {
        return Time::Duration((new \DateTime())->diff($dt, false));
    }

    public static function CompareDuration(Time\Duration $d1, Time\Duration $d2): int
    {
        return $d1->Compare($d2);
    }

    public static function ParseDuration(string $s): Time\Duration
    {
        if (0 === strlen($s)) {
            throw self::_invalidDurationException($s);
        }

        $d = 0;
        $orig = $s;

        $neg = '-' === $s[0];
        // consume symbol
        if ('-' === $s[0] || '+' === $s[0]) {
            $s = substr($s, 1);
        }

        if ('0' === $s) {
            return new Time\Duration();
        } elseif ('' === $s) {
            throw self::_invalidDurationException($orig);
        }

        while ('' !== $s) {
            $ord = ord($s[0]);
            // at this point in the loop only [0-9.] are expected
            if (46 !== $ord && (48 > $ord || $ord > 57)) {
                throw self::_invalidDurationException($orig);
            }
            $v = 0;
            $pl = strlen($s);
            for ($i = 0; $i < $pl; $i++) {
                $ord = ord($s[$i]);
                if (48 > $ord || $ord > 57) {
                    break;
                }
                if (GOTIME_OVERFLOW_INT < $v) {
                    throw self::_invalidDurationException($orig);
                }
                $v = $v * 10 + (int)$s[$i];
                if (GOTIME_OVERFLOW_INT < $v) {
                    throw self::_invalidDurationException($orig);
                }
            }
            $s = substr($s, $i);
            $pre = $pl !== strlen($s);

            $post = false;
            $f = 0;
            $scale = 1;
            $overflow = false;
            if ('.' === $s[0]) {
                $s = substr($s, 1);
                $pl = strlen($s);
                for ($i = 0; $i < $pl; $i++) {
                    $ord = ord($s[$i]);
                    if (48 > $ord || $ord > 57) {
                        break;
                    }
                    if ($overflow) {
                        continue;
                    }
                    if (GOTIME_OVERFLOW_INT < $f) {
                        $overflow = true;
                        continue;
                    }
                    $y = $f * 10 + (int)$s[$i];
                    if (0 > $y) {
                        $overflow = true;
                        continue;
                    }
                    $f = $y;
                    $scale *= 10;
                }
                $s = substr($s, $i);
                $post = $pl != strlen($s);
            }

            if (!$pre && !$post) {
                throw self::_invalidDurationException($orig);
            }

            $pl = strlen($s);
            for ($i = 0; $i < $pl; $i++) {
                $ord = ord($s[$i]);
                if (46 === $ord || (48 <= $ord && $ord <= 57)) {
                    break;
                }
            }
            $u = substr($s, 0, $i);
            $unit = self::unitMap[$u] ?? null;
            if (null === $unit) {
                throw self::_invalidDurationUnitException($u, $orig);
            }
            if (intdiv(PHP_INT_MAX, $unit) < $v) {
                throw self::_invalidDurationException($orig);
            }
            $v *= $unit;
            if (0 < $f) {
                $v += (int)($f * ($unit / $scale));
                if (0 > $v) {
                    throw self::_invalidDurationException($orig);
                }
            }

            $d += $v;
            if (0 > $d) {
                throw self::_invalidDurationException($orig);
            }
            $s = substr($s, $i);
        }

        return new Time\Duration($neg ? -$d : $d);
    }

    /**
     * Attempts to "cast" the provided input into a Time\Duration type
     */
    public static function Duration(null|string|int|float|Time\Duration|\DateInterval $input): Time\Duration
    {
        if (null === $input) {
            return new Time\Duration(0);
        }
        switch (gettype($input)) {
            default:
                throw new \UnexpectedValueException(
                    sprintf('Cannot cast input of type "%s" to "%s"', gettype($input), Time\Duration::class)
                );

            case 'string':
                return static::ParseDuration($input);
            case 'integer':
                return new Time\Duration($input);
            case 'double':
                return new Time\Duration((int)$input);
            case 'object':
                if ($input instanceof Time\Duration) {
                    return clone $input;
                }
                if ($input instanceof \DateInterval) {
                    // get base calculation
                    if (isset($input->f) && (is_float($input->f) || is_int($input->f))) {
                        $ns = ($input->f * 1e9); // convert to nano second integer
                    } else {
                        $ns = 0;
                    }
                    // seconds
                    if (is_int($input->s) && 0 < $input->s) {
                        $ns += ($input->s * Time::Second);
                    }
                    // minutes
                    if (is_int($input->i) && 0 < $input->i) {
                        $ns += ($input->i * Time::Minute);
                    }
                    // hours
                    if (is_int($input->h) && 0 < $input->h) {
                        $ns += ($input->h * Time::Hour);
                    }
                    // days
                    if (is_int($input->d) && 0 < $input->d) {
                        $ns += ($input->d * 24 * Time::Hour);
                    }
                    // months
                    if (is_int($input->m) && 0 < $input->m) {
                        $ns += ($input->m * self::monthsToHours * Time::Hour);
                    }
                    // years
                    if (is_int($input->y)) {
                        $ns += ($input->y * self::yearToHours * Time::Hour);
                    }
                    return new Time\Duration(intval($input->invert ? -$ns : $ns));
                }
                throw new \UnexpectedValueException(sprintf('Cannot handle object of type "%s"', get_class($input)));
        }
    }

    /**
     * Returns a zero duration instance.
     */
    public static function ZeroDuration(): Time\Duration
    {
        if (!isset(self::$_zeroDuration)) {
            self::$_zeroDuration = new Time\Duration(0);
        }
        return self::$_zeroDuration;
    }

    private static function _invalidDurationException(string $orig): \InvalidArgumentException
    {
        return new \InvalidArgumentException("Invalid duration: {$orig}");
    }

    private static function _invalidDurationUnitException(string $unit, string $orig): \InvalidArgumentException
    {
        return new \InvalidArgumentException("Unknown unit {$unit} in duration {$orig}");
    }
}
