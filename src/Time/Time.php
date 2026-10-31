<?php declare(strict_types=1);

namespace DCarbone\Go\Time;

use DCarbone\Go\Time as TimeNS;

/**
 * Extends \DateTime with nanosecond precision.  \DateTime itself only stores microseconds, so the
 * sub-microsecond remainder (0-999ns) is tracked separately.  It is preserved by add(), sub(), modify(),
 * setDate(), setISODate(), and setTimezone(), and reset by setTimestamp(), setTime(), and setMicrosecond().
 */
class Time extends \DateTime implements \JsonSerializable
{
    public const DefaultFormat             = 'Y-m-d H:i:s.u000 O e';
    public const DefaultFormatNoSubSeconds = 'Y-m-d H:i:s O e';

    // matches a fractional second with more than 6 digits, capturing [prefix + first 6 digits, remaining digits, suffix]
    private const subMicroPattern = '/^(.*?\d[.,]\d{6})(\d+)(.*)$/s';

    private int $nsec = 0;

    /**
     * Fractional seconds beyond microsecond precision (up to 9 digits) are retained, e.g.
     * "2024-01-02T03:04:05.123456789Z" or "@1704164645.123456789".
     *
     * @throws \DateMalformedStringException
     * @throws \Exception
     */
    public function __construct(string $datetime = 'now', \DateTimeZone|null $timezone = null)
    {
        if (!preg_match(self::subMicroPattern, $datetime, $m)) {
            parent::__construct($datetime, $timezone);
            return;
        }

        parent::__construct($m[1] . $m[3], $timezone);
        $nsec = self::_parseSubMicro($m[2]);
        if (str_starts_with(ltrim($m[1]), '@-')) {
            $this->_setUnix($this->Unix(), $this->Nanosecond() - $nsec);
        } else {
            $this->nsec = $nsec;
        }
    }

    /**
     * Construct a UTC Time from seconds and nanoseconds since the Unix epoch.  $nsec may be outside [0, 999999999].
     */
    public static function fromUnix(int $sec, int $nsec = 0): static
    {
        [$sec, $nsec] = self::_normalize($sec, $nsec);
        $us = intdiv($nsec, TimeNS::Microsecond);
        if (0 <= $sec || 0 === $us) {
            $t = new static(sprintf('@%d.%06d', $sec, $us));
        } else {
            $t = new static(sprintf('@-%d.%06d', -($sec + 1), 1000000 - $us));
        }
        $t->nsec = $nsec % TimeNS::Microsecond;
        return $t;
    }

    /**
     * If a format containing "u" fails to parse due to a fractional second with more than 6 digits, parsing is
     * retried with the fraction truncated to 6 digits and the remaining digits (up to 9 total) are retained.
     *
     * @throws \DateMalformedStringException
     * @throws \Exception
     */
    public static function createFromFormat(string $format, string $datetime, \DateTimeZone|null $timezone = null): static|false
    {
        $t = self::_parentFromFormat($format, $datetime, $timezone);
        if (false !== $t) {
            return $t;
        }
        $sub = self::_splitSubMicro($format, $datetime);
        if (null === $sub) {
            return false;
        }
        $t = self::_parentFromFormat($format, $sub[0], $timezone);
        if (false === $t) {
            return false;
        }
        $t->nsec = $sub[1];
        return $t;
    }

    /**
     * Locates the input digits following a fully consumed (6 digit) unescaped "u" field and removes them.
     *
     * @return array{string, int}|null [input without the extra digits, sub-microsecond nanoseconds]
     */
    private static function _splitSubMicro(string $format, string $datetime): ?array
    {
        $len = strlen($format);
        for ($i = 0; $i < $len; $i++) {
            if ('\\' === $format[$i]) {
                $i++;
                continue;
            }
            if ('u' !== $format[$i]) {
                continue;
            }
            // parse only up to and including this "u"; "+" reports the remainder's offset as a trailing data warning
            $r = date_parse_from_format(substr($format, 0, $i + 1) . '+', $datetime);
            $end = array_search('Trailing data', $r['warnings'], true);
            if ([] !== $r['errors'] || !is_int($end) || 6 > $end || 6 !== strspn($datetime, '0123456789', $end - 6, 6)) {
                continue;
            }
            $n = strspn($datetime, '0123456789', $end);
            if (0 === $n) {
                continue;
            }
            return [
                substr($datetime, 0, $end) . substr($datetime, $end + $n),
                self::_parseSubMicro(substr($datetime, $end, $n)),
            ];
        }
        return null;
    }

    /**
     * \DateTime::createFromFormat() instantiates the called class, but is declared as returning \DateTime.
     */
    private static function _parentFromFormat(string $format, string $datetime, ?\DateTimeZone $timezone): static|false
    {
        $t = parent::createFromFormat($format, $datetime, $timezone);
        if (false === $t || $t instanceof static) {
            return $t;
        }
        return static::createFromInterface($t);
    }

    public static function createFromInterface(\DateTimeInterface $object): static
    {
        $t = parent::createFromInterface($object);
        if ($object instanceof self) {
            $t->nsec = $object->nsec;
        }
        return $t;
    }

    public static function __set_state(array $array): static
    {
        $t = static::createFromInterface(parent::__set_state($array));
        $t->nsec = (int)($array['nsec'] ?? 0);
        return $t;
    }

    public static function getLastErrorsString(): string
    {
        $errs = \DateTime::getLastErrors();
        if (!is_array($errs)) {
            return '';
        }
        $errstr = '';
        if (($errs['warning_count'] ?? 0) > 0) {
            $errstr = sprintf('Warnings: ["%s"]', implode('", "', $errs['warnings'] ?? []));
        }
        if (($errs['error_count'] ?? 0) > 0) {
            if ($errstr !== '') {
                $errstr .= '; ';
            }
            $errstr = sprintf('%sErrors: ["%s"]', $errstr, implode('", "', $errs['errors'] ?? []));
        }
        return $errstr;
    }

    public function setTimestamp(int $timestamp): static
    {
        $this->nsec = 0;
        return parent::setTimestamp($timestamp);
    }

    public function setTime(int $hour, int $minute, int $second = 0, int $microsecond = 0): static
    {
        $this->nsec = 0;
        return parent::setTime($hour, $minute, $second, $microsecond);
    }

    /**
     * Only available on PHP 8.4+.
     */
    public function setMicrosecond(int $microsecond): static
    {
        $this->nsec = 0;
        return parent::setMicrosecond($microsecond);
    }

    public function Second(): int
    {
        return (int)$this->format('s');
    }

    public function Minute(): int
    {
        return (int)$this->format('i');
    }

    public function Hour(): int
    {
        return (int)$this->format('H');
    }

    public function Day(): int
    {
        return (int)$this->format('d');
    }

    public function Weekday(): Weekday
    {
        return new Weekday((int)$this->format('w'));
    }

    public function Month(): Month
    {
        return new Month((int)$this->format('m'));
    }

    public function Year(): int
    {
        return (int)$this->format('Y');
    }

    public function UnixNanoDuration(): Duration
    {
        return new Duration($this->UnixNano());
    }

    /**
     * @throws \RangeException if the time cannot be represented as an int nanosecond count (dates before 1678
     *                         or after 2262).
     */
    public function UnixNano(): int
    {
        return $this->_unixScaled(TimeNS::Nanosecond);
    }

    public function UnixMicro(): int
    {
        return $this->_unixScaled(TimeNS::Microsecond);
    }

    public function UnixMilli(): int
    {
        return $this->_unixScaled(TimeNS::Millisecond);
    }

    public function Unix(): int
    {
        return (int)$this->format('U');
    }

    /**
     * Nanosecond offset within the second, in the range [0, 999999999].
     */
    public function Nanosecond(): int
    {
        return (int)$this->format('u') * TimeNS::Microsecond + $this->nsec;
    }

    public function IsZero(): bool
    {
        return 0 === $this->Unix() && 0 === $this->Nanosecond();
    }

    /**
     * Returns -1 if this time is before $t, 1 if after, and 0 if equal.
     */
    public function Compare(\DateTimeInterface $t): int
    {
        [$s1, $n1] = [$this->Unix(), $this->Nanosecond()];
        [$s2, $n2] = self::_parts($t);
        return $s1 <=> $s2 ?: $n1 <=> $n2;
    }

    public function Before(Time $t): bool
    {
        return -1 === $this->Compare($t);
    }

    public function BeforeDateTime(\DateTimeInterface $dt): bool
    {
        return -1 === $this->Compare($dt);
    }

    public function After(Time $t): bool
    {
        return 1 === $this->Compare($t);
    }

    public function AfterDateTime(\DateTimeInterface $dt): bool
    {
        return 1 === $this->Compare($dt);
    }

    public function Equal(Time $t): bool
    {
        return 0 === $this->Compare($t);
    }

    public function EqualDateTime(\DateTimeInterface $dt): bool
    {
        return 0 === $this->Compare($dt);
    }

    /**
     * Returns the duration $this - $u.  As with Go, the result saturates at the minimum / maximum Duration.
     */
    public function SubTime(\DateTimeInterface $u): Duration
    {
        [$s1, $n1] = self::_parts($this);
        [$s2, $n2] = self::_parts($u);
        if ((0 > $s2 && $s1 > PHP_INT_MAX + $s2) || (0 < $s2 && $s1 < PHP_INT_MIN + $s2)) {
            return new Duration(0 > $s2 ? PHP_INT_MAX : PHP_INT_MIN);
        }
        $sd = $s1 - $s2;
        $nd = $n1 - $n2;
        // borrow/carry a second so $sd and $nd share a sign before range-checking
        if (0 < $sd && 0 > $nd) {
            $sd--;
            $nd += TimeNS::Second;
        } elseif (0 > $sd && 0 < $nd) {
            $sd++;
            $nd -= TimeNS::Second;
        }
        if (0 < $sd && $sd > intdiv(PHP_INT_MAX - $nd, TimeNS::Second)) {
            return new Duration(PHP_INT_MAX);
        }
        if (0 > $sd && $sd < intdiv(PHP_INT_MIN - $nd, TimeNS::Second)) {
            return new Duration(PHP_INT_MIN);
        }
        return new Duration($sd * TimeNS::Second + $nd);
    }

    /**
     * Adds $d to this time, in absolute (not wall-clock) time.
     */
    public function AddDuration(Duration $d): static
    {
        return $this->_addNanos($d->Nanoseconds());
    }

    /**
     * Subtracts $d from this time, in absolute (not wall-clock) time.
     */
    public function SubDuration(Duration $d): static
    {
        $ns = $d->Nanoseconds();
        if (PHP_INT_MIN === $ns) {
            return $this->_addNanos(PHP_INT_MAX)->_addNanos(1);
        }
        return $this->_addNanos(-$ns);
    }

    /**
     * Formats this time as RFC 3339 with nanosecond precision, trailing fractional zeros removed, and "Z" for a
     * zero UTC offset, matching Go's time.RFC3339Nano layout.
     */
    public function RFC3339Nano(): string
    {
        $s = $this->format('Y-m-d\TH:i:s');
        $ns = $this->Nanosecond();
        if (0 !== $ns) {
            $s .= '.' . rtrim(sprintf('%09d', $ns), '0');
        }
        return $s . (0 === $this->getOffset() ? 'Z' : $this->format('P'));
    }

    /**
     * @throws \RangeException if the year is outside [0, 9999], as RFC 3339 requires a four-digit year.
     */
    public function jsonSerialize(): string
    {
        $y = $this->Year();
        if (0 > $y || 9999 < $y) {
            throw new \RangeException(sprintf('Time::jsonSerialize: year %d outside of range [0,9999]', $y));
        }
        return $this->RFC3339Nano();
    }

    public function __toString(): string
    {
        if (0 === $this->Nanosecond()) {
            return $this->format(self::DefaultFormatNoSubSeconds);
        }
        return $this->format('Y-m-d H:i:s.u') . sprintf('%03d', $this->nsec) . $this->format(' O e');
    }

    private function _unixScaled(int $unit): int
    {
        $perSec = intdiv(TimeNS::Second, $unit);
        $sec = $this->Unix();
        $sub = intdiv($this->Nanosecond(), $unit);
        // borrow so that both terms share a sign, keeping the intermediate product in range for negative times
        if (0 > $sec && 0 < $sub) {
            $sec++;
            $sub -= $perSec;
        }
        if ((0 < $sec && $sec > intdiv(PHP_INT_MAX - $sub, $perSec))
            || (0 > $sec && $sec < intdiv(PHP_INT_MIN - $sub, $perSec))) {
            throw new \RangeException(sprintf('Time %s cannot be represented as an integer Unix offset', $this->RFC3339Nano()));
        }
        return $sec * $perSec + $sub;
    }

    private function _addNanos(int $ns): static
    {
        $this->_setUnix(
            $this->Unix() + intdiv($ns, TimeNS::Second),
            $this->Nanosecond() + $ns % TimeNS::Second,
        );
        return $this;
    }

    private function _setUnix(int $sec, int $nsec): void
    {
        [$sec, $nsec] = self::_normalize($sec, $nsec);
        parent::setTimestamp($sec);
        $us = intdiv($nsec, TimeNS::Microsecond);
        if (0 !== $us) {
            parent::modify("+{$us} usec");
        }
        $this->nsec = $nsec % TimeNS::Microsecond;
    }

    /**
     * @return array{int, int} [seconds, nanoseconds in the range [0, 999999999]]
     */
    private static function _normalize(int $sec, int $nsec): array
    {
        $sec += intdiv($nsec, TimeNS::Second);
        $nsec %= TimeNS::Second;
        if (0 > $nsec) {
            $nsec += TimeNS::Second;
            $sec--;
        }
        return [$sec, $nsec];
    }

    /**
     * @return array{int, int} [unix seconds, nanoseconds within the second]
     */
    private static function _parts(\DateTimeInterface $dt): array
    {
        if ($dt instanceof self) {
            return [$dt->Unix(), $dt->Nanosecond()];
        }
        return [(int)$dt->format('U'), (int)$dt->format('u') * TimeNS::Microsecond];
    }

    private static function _parseSubMicro(string $digits): int
    {
        return (int)str_pad(substr($digits, 0, 3), 3, '0');
    }
}
