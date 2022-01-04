<?php declare(strict_types=1);

namespace DCarbone\Go\Time;

use DCarbone\Go\Time as TimeNS;

/**
 * Class Time
 * @package DCarbone\Go\Time
 *
 * TODO: Improve efficiency
 */
class Time extends \DateTime
{
    public const DefaultFormat             = 'Y-m-d H:i:s.u000 O e';
    public const DefaultFormatNoSubSeconds = 'Y-m-d H:i:s O e';

    private const FromDateTimeFormat = 'Y-m-d H:i:s.u O';

    /**
     * @param string $format
     * @param string $time
     * @param \DateTimeZone|null $timezone
     * @return false|static
     * @throws \Exception
     */
    #[\ReturnTypeWillChange]
    public static function createFromFormat($format, $time, \DateTimeZone $timezone = null)
    {
        if ($dt = parent::createFromFormat($format, $time, $timezone)) {
            // todo: find more efficient implementation
            return new static($dt->format(self::FromDateTimeFormat), $timezone);
        }
        return false;
    }

    /**
     * @return string
     */
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

    /**
     * @return int
     */
    public function Second(): int
    {
        return (int)$this->format('s');
    }

    /**
     * @return int
     */
    public function Minute(): int
    {
        return (int)$this->format('i');
    }

    /**
     * @return int
     */
    public function Hour(): int
    {
        return (int)$this->format('H');
    }

    /**
     * @return int
     */
    public function Day(): int
    {
        return (int)$this->format('d');
    }

    /**
     * @return \DCarbone\Go\Time\Weekday
     */
    public function Weekday(): Weekday
    {
        return new Weekday((int)$this->format('w'));
    }

    /**
     * @return \DCarbone\Go\Time\Month
     */
    public function Month(): Month
    {
        return new Month((int)$this->format('m'));
    }

    /**
     * @return int
     */
    public function Year(): int
    {
        return (int)$this->format('Y');
    }

    /**
     * @return \DCarbone\Go\Time\Duration
     */
    public function UnixNanoDuration(): Duration
    {
        return new Duration($this->UnixNano());
    }

    /**
     * @return int
     */
    public function UnixNano(): int
    {
        return intval($this->Unix() * TimeNS::Second) + $this->Nanosecond();
    }

    /**
     * @return int
     */
    public function Unix(): int
    {
        return (int)$this->format('U');
    }

    /**
     * NOTE: PHP is only capable of microsecond accuracy at this point.
     *
     * @return int
     */
    public function Nanosecond(): int
    {
        return (int)$this->format('u') * TimeNS::Microsecond;
    }

    /**
     * @return bool
     */
    public function IsZero(): bool
    {
        return 0 === $this->UnixNano();
    }

    /**
     * @param \DCarbone\Go\Time\Time $t
     * @return bool
     */
    public function Before(Time $t): bool
    {
        return $this->UnixNano() < $t->UnixNano();
    }

    /**
     * @param \DateTimeInterface $dt
     * @return bool
     */
    public function BeforeDateTime(\DateTimeInterface $dt): bool
    {
        return $this->UnixNano() <
            ((int)$dt->format('U') * TimeNS::Second +
                (int)$dt->format('u') * TimeNS::Microsecond);
    }

    /**
     * @param \DCarbone\Go\Time\Time $t
     * @return bool
     */
    public function After(Time $t): bool
    {
        return $this->UnixNano() > $t->UnixNano();
    }

    /**
     * @param \DateTimeInterface $dt
     * @return bool
     */
    public function AfterDateTime(\DateTimeInterface $dt): bool
    {
        return $this->UnixNano() > intval(
                ((int)$dt->format('U') * TimeNS::Second + (int)$dt->format('u') * TimeNS::Microsecond)
            );
    }

    /**
     * @param \DCarbone\Go\Time\Time $t
     * @return bool
     */
    public function Equal(Time $t): bool
    {
        return $this->UnixNano() === $t->UnixNano();
    }

    /**
     * @param \DateTimeInterface $dt
     * @return bool
     */
    public function EqualDateTime(\DateTimeInterface $dt): bool
    {
        return $this->UnixNano() === intval(
                ((int)$dt->format('U') * TimeNS::Second + (int)$dt->format('u') * TimeNS::Nanosecond)
            );
    }

    /**
     * @param \DCarbone\Go\Time\Duration $d
     * @return \DCarbone\Go\Time\Time
     * @throws \Exception
     */
    public function AddDuration(Duration $d): Time
    {
        return $this->add($d->DateInterval());
    }

    /**
     * @param \DCarbone\Go\Time\Duration $d
     * @return \DCarbone\Go\Time\Time
     * @throws \Exception
     */
    public function SubDuration(Duration $d): Time
    {
        return $this->sub($d->DateInterval());
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        if (0 === $this->Nanosecond()) {
            return $this->format(self::DefaultFormatNoSubSeconds);
        }
        return $this->format(self::DefaultFormat);
    }
}