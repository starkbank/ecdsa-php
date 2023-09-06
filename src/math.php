<?php

namespace EllipticCurve;
use EllipticCurve\Point;
use EllipticCurve\Utils\Integer;


class Math
{
    public static function modularSquareRoot($value, $prime)
    {
        return gmp_powm($value, gmp_div_q(gmp_add($prime, 1), 4), $prime);
    }

    /**
    Fast way to multiply point and scalar in elliptic curves

    ## Parameters:
        - p: First Point to multiply
        - n: Scalar to multiply
        - N: Order of the elliptic curve
        - P: Prime number in the module of the equation Y^2 = X^3 + A*X + B (mod p)
        - A: Coefficient of the first-order term of the equation Y^2 = X^3 + A*X + B (mod p)
        
    ## Return:
        - Point that represents the product of First Point and scalar;
     */
    public static function multiply($p, $n, $N, $A, $P)
    {
        return Math::fromJacobian(
            Math::jacobianMultiply(Math::toJacobian($p), $n, $N, $A, $P), $P
        );
    }

    /**
    Fast way to add two points in elliptic curves

    ## Parameters:
        - p: First Point you want to add
        - n: Second Point you want to add
        - P: Prime number in the module of the equation Y^2 = X^3 + A*X + B (mod p)
        - A: Coefficient of the first-order term of the equation Y^2 = X^3 + A*X + B (mod p)
        
    ## Return:
        - Point that represents the sum of First and Second Point
     */
    public static function add($p, $q, $A, $P)
    {
        return Math::fromJacobian(
            Math::jacobianAdd(Math::toJacobian($p), Math::toJacobian($q), $A, $P), $P
        );
    }

    /**
    Extended Euclidean Algorithm. It's the 'division' in elliptic curves

    ## Parameters:
        - x: Divisor
        - n: Mod for division
        
    ## Return:
        - Value representing the division
     */
    public static function inv($x, $n)
    {
        if ($x == 0)
            return Integer::toBigInt(0);

        $lm = Integer::toBigInt(1);
        $hm = Integer::toBigInt(0);
        $low = Integer::modulo($x, $n);
        $high = $n;

        while ($low > 1) {
            $r = gmp_div_q($high, $low);
            $nm = $hm - $lm * $r;
            $nw = $high - $low * $r;
            $high = $low;
            $hm = $lm;
            $low = $nw;
            $lm = $nm;
        }

        return Integer::modulo($lm, $n);
    }

    /**
    Convert point to Jacobian coordinates

    ## Parameters:
        - p: First Point you want to convert

    ## Return:
        - Point in Jacobian coordinates
     */
    private static function toJacobian($p)
    {
        return new Point($p->x, $p->y, 1);
    }

    /**
    Convert point back from Jacobian coordinates

    ## Parameters:
        - p: First Point you want to convert
        - P: Prime number in the module of the equation Y^2 = X^3 + A*X + B (mod p)
        
    ## Return:
        - Point in default coordinates
     */
    private static function fromJacobian($p, $P)
    {
        $z = Math::inv($p->z, $P);
        $x = Integer::modulo($p->x * $z ** 2, $P);
        $y = Integer::modulo($p->y * $z ** 3, $P);

        return new Point($x, $y, 0);
    }

    /**
    Double a point in elliptic curves

    ## Parameters:
        - p: First Point you want to double
        - P: Prime number in the module of the equation Y^2 = X^3 + A*X + B (mod p)
        - A: Coefficient of the first-order term of the equation Y^2 = X^3 + A*X + B (mod p)
        
    ## Return:
        - Point that represents the sum of First Point and itself
     */
    private static function jacobianDouble($p, $A, $P)
    {
        if ($p->y == 0)
            return new Point(0, 0, 0);

        $ysq = Integer::modulo($p->y ** 2, $P);
        $S = Integer::modulo(4 * $p->x * $ysq, $P);
        $M = Integer::modulo(3 * $p->x ** 2 + $A * $p->z ** 4, $P);
        $nx = Integer::modulo($M ** 2 - 2 * $S, $P);
        $ny = Integer::modulo($M * ($S - $nx) - 8 * $ysq ** 2, $P);
        $nz = Integer::modulo(2 * $p->y * $p->z, $P);

        return new Point($nx, $ny, $nz);
    }

    /**
    Add two points in elliptic curves

    ## Parameters:
        - p: First Point you want to add
        - q: Second Point you want to add
        - P: Prime number in the module of the equation Y^2 = X^3 + A*X + B (mod p)
        - A: Coefficient of the first-order term of the equation Y^2 = X^3 + A*X + B (mod p)
        
    ## Return:
        - Point that represents the sum of First and Second Point
     */
    private static function jacobianAdd($p, $q, $A, $P)
    {
        if ($p->y == 0)
            return $q;
        if ($q->y == 0)
            return $p;

        $U1 = Integer::modulo($p->x * $q->z ** 2, $P);
        $U2 = Integer::modulo($q->x * $p->z ** 2, $P);
        $S1 = Integer::modulo($p->y * $q->z ** 3, $P);
        $S2 = Integer::modulo($q->y * $p->z ** 3, $P);

        if ($U1 == $U2) {
            if ($S1 != $S2)
                return new Point(0, 0, 1);
            return Math::jacobianDouble($p, $A, $P);
        }

        $H = $U2 - $U1;
        $R = $S2 - $S1;
        $H2 = Integer::modulo($H * $H, $P);
        $H3 = Integer::modulo($H * $H2, $P);
        $U1H2 = Integer::modulo($U1 * $H2, $P);
        $nx = Integer::modulo($R ** 2 - $H3 - 2 * $U1H2, $P);
        $ny = Integer::modulo($R * ($U1H2 - $nx) - $S1 * $H3, $P);
        $nz = Integer::modulo($H * $p->z * $q->z, $P);

        return new Point($nx, $ny, $nz);
    }

    /**
    Multiply point and scalar in elliptic curves

    ## Parameters:
        - p: First Point you want to multiply
        - n: Scalar to mutiply
        - N: Order of the elliptic curve
        - P: Prime number in the module of the equation Y^2 = X^3 + A*X + B (mod p)
        - A: Coefficient of the first-order term of the equation Y^2 = X^3 + A*X + B (mod p)
        
    ## Return:
        - Point that represents the product of First Point and scalar;
     */
    private static function jacobianMultiply($p, $n, $N, $A, $P)
    {
        if ($p->y == 0 or $n == 0)
            return new Point(0, 0, 1);

        if ($n == 1)
            return $p;
        
        if ($n < 0 or $n >= $N) {
            return Math::jacobianMultiply($p, Integer::modulo($n, $N), $N, $A, $P);
        }
            
        if (Integer::modulo($n, 2) == 0) {
            $divisao = gmp_div_q($n, 2);
            return Math::jacobianDouble(
                Math::jacobianMultiply($p, $divisao, $N, $A, $P), $A, $P
            );
        }

        return Math::jacobianAdd(
            Math::jacobianDouble(Math::jacobianMultiply($p, gmp_div_q($n, 2), $N, $A, $P), $A, $P), $p, $A, $P
        );
    }
}
