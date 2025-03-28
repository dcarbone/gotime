<?php declare(strict_types=1);

namespace DCarbone\Go\Time;

class DateInterval extends \DateInterval
{
    public function __construct(string $interval_spec, bool $invert = false, float $microseconds = 0.0)
    {
        parent::__construct($interval_spec);
        $this->invert = $invert ? 1 : 0;
        $this->f = $microseconds;
    }

    /**
     * @throws \DateMalformedIntervalStringException
     */
    public static function fromIntervalSpec(IntervalSpec $spec): DateInterval
    {
        return new self($spec->spec, $spec->invert, $spec->f);
    }
}