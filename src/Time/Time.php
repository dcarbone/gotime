<?php declare(strict_types=1);

namespace DCarbone\Go\Time;

use DCarbone\Go\Time as TimeNS;

class Time extends \DateTime
{
    public const DefaultFormat             = 'Y-m-d H:i:s.u000 O e';
    public const DefaultFormatNoSubSeconds = 'Y-m-d H:i:s O e';

    private const FromDateTimeFormat = 'Y-m-d H:i:s.u O';

    /**
     * @throws \DateMalformedStringException
     */
    #[\ReturnTypeWillChange]
    public static function createFromFormat(string $format, string $datetime, \DateTimeZone $timezone = null): bool|static
    {
        if ($dt = parent::createFromFormat($format, $datetime, $timezone)) {
            // todo: find more efficient implementation
            return new static($dt->format(self::FromDateTimeFormat), $timezone);
        }
        return false;
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

    public function UnixNano(): int
    {
        return intval($this->Unix() * TimeNS::Second) + $this->Nanosecond();
    }

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

    public function IsZero(): bool
    {
        return 0 === $this->UnixNano();
    }

    public function Before(Time $t): bool
    {
        return $this->UnixNano() < $t->UnixNano();
    }

    public function BeforeDateTime(\DateTimeInterface $dt): bool
    {
        return $this->UnixNano() <
            ((int)$dt->format('U') * TimeNS::Second +
                (int)$dt->format('u') * TimeNS::Microsecond);
    }

    public function After(Time $t): bool
    {
        return $this->UnixNano() > $t->UnixNano();
    }

    public function AfterDateTime(\DateTimeInterface $dt): bool
    {
        return $this->UnixNano() > intval(
                ((int)$dt->format('U') * TimeNS::Second + (int)$dt->format('u') * TimeNS::Microsecond)
            );
    }

    public function Equal(Time $t): bool
    {
        return $this->UnixNano() === $t->UnixNano();
    }

    public function EqualDateTime(\DateTimeInterface $dt): bool
    {
        return $this->UnixNano() === intval(
                ((int)$dt->format('U') * TimeNS::Second + (int)$dt->format('u') * TimeNS::Nanosecond)
            );
    }

    /**
     * @throws \Exception
     */
    public function AddDuration(Duration $d): Time
    {
        return $this->add($d->DateInterval());
    }

    /**
     * @throws \Exception
     */
    public function SubDuration(Duration $d): Time
    {
        return $this->sub($d->DateInterval());
    }

    public function __toString(): string
    {
        if (0 === $this->Nanosecond()) {
            return $this->format(self::DefaultFormatNoSubSeconds);
        }
        return $this->format(self::DefaultFormat);
    }
}