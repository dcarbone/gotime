<?php declare(strict_types=1);

namespace DCarbone\Go\Time;

class IntervalSpec
{
    public string $spec = '';
    public bool $invert = false;
    public float $f = 0.0;

    public function __construct(string $spec = '', bool $invert = false, float $f = 0.0)
    {
        $this->spec = $spec;
        $this->invert = $invert;
        $this->f = $f;
    }
}