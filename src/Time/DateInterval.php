<?php declare(strict_types=1);

namespace DCarbone\Go\Time;

/**
 * Class DateInterval
 * @package DCarbone\Go\Time
 */
class DateInterval extends \DateInterval
{
    /**
     * DateInterval constructor.
     * @param string $interval_spec
     * @param bool $invert
     * @param float $microseconds
     * @throws \Exception
     */
    public function __construct(string $interval_spec, bool $invert = false, float $microseconds = 0.0)
    {
        parent::__construct($interval_spec);
        $this->invert = $invert ? 1 : 0;
        $this->f = $microseconds;
    }

    /**
     * @param \DCarbone\Go\Time\IntervalSpec $spec
     * @return \DCarbone\Go\Time\DateInterval
     * @throws \Exception
     */
    public static function fromIntervalSpec(IntervalSpec $spec): DateInterval
    {
        return new self($spec->spec, $spec->invert, $spec->f);
    }
}