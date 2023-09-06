<?php

/**
 * 
 * Elliptic Curve Equation
 * 
 * yˆ2 = xˆ3 + A*x + B (mod P)
 */

namespace EllipticCurve;

use Exception;
use EllipticCurve\Utils\Binary;
use EllipticCurve\Utils\Integer;


class Curve
{
    public $A, $B, $P, $N, $G, $name, $nistName, $oid;

    function __construct($A, $B, $P, $N, $Gx, $Gy, $name, $oid, $nistName=null)
    {
        $this->A = gmp_init($A);
        $this->B = gmp_init($B);
        $this->P = gmp_init($P);
        $this->N = gmp_init($N);
        $this->G = new Point($Gx, $Gy);
        $this->name = $name;
        $this->nistName = $nistName;
        $this->oid = $oid;
    }

    /**
    Verify if the point `p` is on the curve 

    ## Parameters:
        - p: Point p = Point(x, y)
        
    ## Return: [boolean]
     */
    function contains($p)
    {
        if (!(($p->x >= 0) and ($p->x <= $this->P - 1)))
            return false;
        if (!(($p->y >= 0) and ($p->y <= $this->P - 1)))
            return false;
        if (Integer::modulo($p->y**2 - ($p->x**3 + $this->A * $p->x + $this->B), $this->P) != 0)
            return false;
        return true;
    }

    function length()
    {
        return gmp_div_q(1 + strlen(Binary::hexFromInt($this->N)), 2);
    }

    function oidString()
    {
        return join(".", $this->oid);
    }

    function y($x, $isEven)
    {
        $ySquared = gmp_mod(gmp_add(gmp_add(gmp_powm($x, 3, $this->P), gmp_mul($this->A, $x)), $this->B), $this->P);
        $y = Math::modularSquareRoot($ySquared, $this->P);

        if ($isEven != (gmp_intval(gmp_mod($y, 2)) == 0))
        {
            $y = gmp_sub($this->P, $y);
        }
        return $y;
    }

    public static $supportedCurves = [];
    public static $_curvesByOid = [];

    public static function add($curve)
    {
        self::$supportedCurves[$curve->name] = $curve;
        self::$_curvesByOid[$curve->oidString()] = $curve;
    }

    public static function getByOid($oid)
    {
        $oidString = join(".", $oid);
    
        if (!array_key_exists($oidString, Curve::$_curvesByOid)) {
            $supportedCurvesNames = [];
            foreach (Curve::$supportedCurves as $curve) {
                $supportedCurvesNames[] = $curve->name;
            }
            throw new Exception(
                sprintf("Unknown curve with oid %s; The following are registred: %s",
                    $oidString, join(", ", $supportedCurvesNames))
            );
        }
        return Curve::$_curvesByOid[$oidString];
    }
}


$secp256k1 = new Curve(
    "0x0000000000000000000000000000000000000000000000000000000000000000",
    "0x0000000000000000000000000000000000000000000000000000000000000007",
    "0xfffffffffffffffffffffffffffffffffffffffffffffffffffffffefffffc2f",
    "0xfffffffffffffffffffffffffffffffebaaedce6af48a03bbfd25e8cd0364141",
    "0x79be667ef9dcbbac55a06295ce870b07029bfcdb2dce28d959f2815b16f81798",
    "0x483ada7726a3c4655da4fbfc0e1108a8fd17b448a68554199c47d08ffb10d4b8",
    "secp256k1",
    array(1, 3, 132, 0, 10)
);
Curve::add($secp256k1);


$prime256v1 = new Curve(
    "0xffffffff00000001000000000000000000000000fffffffffffffffffffffffc",
    "0x5ac635d8aa3a93e7b3ebbd55769886bc651d06b0cc53b0f63bce3c3e27d2604b",
    "0xffffffff00000001000000000000000000000000ffffffffffffffffffffffff",
    "0xffffffff00000000ffffffffffffffffbce6faada7179e84f3b9cac2fc632551",
    "0x6b17d1f2e12c4247f8bce6e563a440f277037d812deb33a0f4a13945d898c296",
    "0x4fe342e2fe1a7f9b8ee7eb4a7c0f9e162bce33576b315ececbb6406837bf51f5",
    "prime256v1",
    array(1, 2, 840, 10045, 3, 1, 7),
    "P-256"
);
Curve::add($prime256v1);
