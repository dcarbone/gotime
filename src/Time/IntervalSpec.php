<?php declare(strict_types=1);

namespace DCarbone\Go\Time;

/**
 * Class IntervalSpec
 * @package DCarbone\Go\Time
 */
class IntervalSpec
{
    /** @var string */
    public $spec = '';
    /** @var bool */
    public $invert = false;
    /** @var float */
    public $f = 0.0;

    /**
     * IntervalSpec constructor.
     * @param string $spec
     * @param bool   $invert
     * @param float  $f
     */
    public function __construct(string $spec = '', bool $invert = false, float $f = 0.0)
    {
        $this->spec = $spec;
        $this->invert = $invert;
        $this->f = $f;
    }
}