<?php

namespace EllipticCurve;

use EllipticCurve\Utils\Integer;


class Point
{
    public $x, $y, $z;

    function __construct($x=0, $y=0, $z=0)
    {
        $this->x = Integer::toBigInt($x);
        $this->y = Integer::toBigInt($y);
        $this->z = Integer::toBigInt($z);
    }

    function __toString()
    {
        return "({$this->x}, {$this->y}, {$this->z})";
    }

    function isAtInfinity()
    {
        return $this->y == 0;
    }
}
