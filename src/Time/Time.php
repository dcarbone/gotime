<?php namespace DCarbone\Go\Time;

use DCarbone\Go\Time as TimeNS;

/**
 * Class Time
 * @package DCarbone\Go\Time
 *
 * TODO: Improve efficiency
 */
class Time extends \DateTime {

    /** @var array */
    protected static $lastErrors = [];

    /**
     * @param string $format
     * @param string $time
     * @param \DateTimeZone|null $timezone
     * @return \DCarbone\Go\Time\Time|false
     */
    public static function createFromFormat($format, $time, $timezone = null) {
        if ($dt = parent::createFromFormat($format, $time, $timezone)) {
            return new static($dt->format('Y-m-d H:i:s.u O'));
        }
        return false;
    }

    /**
     * @return string
     */
    public static function getLastErrorsString(): string {
        $errs = \DateTime::getLastErrors();
        if (!is_array($errs)) {
            return '';
        }
        $errstr = '';
        if ($errs['warning_count'] ?? 0 > 0) {
            $errstr = sprintf('Warnings: ["%s"]', implode('", "', $errs['warnings'] ?? []));
        }
        if ($errs['error_count'] ?? 0 > 0) {
            if ($errstr !== '') {
                $errstr .= '; ';
            }
            $errstr = sprintf('%sErrors: ["%s"]', $errstr, implode('", "', $errs['errors'] ?? []));
        }
        return '' === $errstr ? 'no errors reported' : '';
    }

    /**
     * NOTE: PHP is only capable of microsecond accuracy at this point.
     *
     * @return int
     */
    public function Nanosecond(): int {
        return (int)$this->format('u') * TimeNS::Microsecond;
    }

    /**
     * @return int
     */
    public function Second(): int {
        return (int)$this->format('s');
    }

    /**
     * @return int
     */
    public function Minute(): int {
        return (int)$this->format('i');
    }

    /**
     * @return int
     */
    public function Hour(): int {
        return (int)$this->format('H');
    }

    /**
     * @return int
     */
    public function Day(): int {
        return (int)$this->format('d');
    }

    /**
     * @return \DCarbone\Go\Time\Weekday
     */
    public function Weekday(): Weekday {
        return new Weekday((int)$this->format('w'));
    }

    /**
     * @return \DCarbone\Go\Time\Month
     */
    public function Month(): Month {
        return new Month((int)$this->format('m'));
    }

    /**
     * @return int
     */
    public function Year(): int {
        return (int)$this->format('Y');
    }

    /**
     * @return int
     */
    public function Unix(): int {
        return (int)$this->format('U');
    }

    /**
     * @return int
     */
    public function UnixNano(): int {
        return ($this->Unix() * TimeNS::Second) + $this->Nanosecond();
    }

    /**
     * @return bool
     */
    public function IsZero(): bool {
        return 0 === $this->UnixNano();
    }

    /**
     * @param \DCarbone\Go\Time\Time $t
     * @return bool
     */
    public function Before(Time $t): bool {
        return $this->UnixNano() < $t->UnixNano();
    }

    /**
     * @param \DateTime $dt
     * @return bool
     */
    public function BeforeDateTime(\DateTime $dt): bool {
        return $this->UnixNano() <
            ((int)$dt->format('U') * TimeNS::Second +
                (int)$dt->format('u') * TimeNS::Microsecond);
    }

    /**
     * @param \DCarbone\Go\Time\Time $t
     * @return bool
     */
    public function After(Time $t): bool {
        return $this->UnixNano() > $t->UnixNano();
    }

    /**
     * @param \DateTime $dt
     * @return bool
     */
    public function AfterDateTime(\DateTime $dt): bool {
        return $this->UnixNano() >
            ((int)$dt->format('U') * TimeNS::Second +
                (int)$dt->format('u') * TimeNS::Microsecond);
    }

    /**
     * @param \DCarbone\Go\Time\Duration $d
     * @throws \Exception
     */
    public function AddDuration(Duration $d) {
        $this->add($d->DateInterval());
    }

    /**
     * @param \DCarbone\Go\Time\Duration $d
     * @throws \Exception
     */
    public function SubDuration(Duration $d) {
        $this->sub($d->DateInterval());
    }

    /**
     * @return string
     */
    public function __toString(): string {
        if (0 === $this->Nanosecond()) {
            return $this->format('Y-m-d H:i:s O e');
        }
        return $this->format('Y-m-d H:i:s.u000 O e');
    }
}