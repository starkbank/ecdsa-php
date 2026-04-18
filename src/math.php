<?php

namespace EllipticCurve;
use EllipticCurve\Point;
use EllipticCurve\Utils\Integer;


class Math
{
    const GENERATOR_WINDOW_BITS = 4;

    /**
     * Tonelli-Shanks algorithm for modular square root. Works for all odd primes.
     */
    public static function modularSquareRoot($value, $prime)
    {
        if ($value == 0)
            return gmp_init(0);
        if ($prime == 2)
            return gmp_mod($value, 2);

        // Factor out powers of 2: prime - 1 = Q * 2^S
        $Q = $prime - 1;
        $S = 0;
        while (gmp_mod($Q, 2) == 0) {
            $Q = gmp_div_q($Q, 2);
            $S += 1;
        }

        if ($S == 1) {  // prime = 3 (mod 4)
            return gmp_powm($value, gmp_div_q(gmp_add($prime, 1), 4), $prime);
        }

        // Find a quadratic non-residue z
        $z = gmp_init(2);
        while (gmp_powm($z, gmp_div_q($prime - 1, 2), $prime) != $prime - 1) {
            $z = $z + 1;
        }

        $M = $S;
        $c = gmp_powm($z, $Q, $prime);
        $t = gmp_powm($value, $Q, $prime);
        $R = gmp_powm($value, gmp_div_q($Q + 1, 2), $prime);

        while (true) {
            if ($t == 1) {
                return $R;
            }

            // Find the least i such that t^(2^i) = 1 (mod prime)
            $i = 1;
            $temp = gmp_mod($t * $t, $prime);
            while ($temp != 1) {
                $temp = gmp_mod($temp * $temp, $prime);
                $i += 1;
            }

            $b = gmp_powm($c, gmp_pow(2, $M - $i - 1), $prime);
            $M = $i;
            $c = gmp_mod($b * $b, $prime);
            $t = gmp_mod($t * $c, $prime);
            $R = gmp_mod($R * $b, $prime);
        }
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
     * Fast scalar multiplication n*G where G is the curve generator, using
     * a precomputed window table (2^w-ary method). Roughly 2-3x faster
     * than variable-base multiplication because doublings stay cheap and
     * additions use pre-stored multiples of G.
     *
     * @param CurveFp $curve Elliptic curve with generator G
     * @param mixed $n Scalar multiplier
     * @return Point n*G
     */
    public static function multiplyGenerator($curve, $n)
    {
        if ($n < 0 or $n >= $curve->N) {
            $n = Integer::modulo($n, $curve->N);
        }
        if ($n == 0) {
            return new Point(0, 0, 0);
        }

        $table = Math::generatorTable($curve);
        $w = Math::GENERATOR_WINDOW_BITS;
        $mask = (1 << $w) - 1;
        $A = $curve->A;
        $P = $curve->P;

        $r = new Point(0, 0, 1);  // Jacobian infinity (y=0 triggers early-return in add)
        $startBit = intdiv($curve->nBitLength - 1, $w) * $w;
        for ($bit = $startBit; $bit >= 0; $bit -= $w) {
            for ($j = 0; $j < $w; $j++) {
                $r = Math::jacobianDouble($r, $A, $P);
            }
            $window = 0;
            for ($k = 0; $k < $w; $k++) {
                if (gmp_testbit($n, $bit + $k)) {
                    $window |= (1 << $k);
                }
            }
            if ($window) {
                $r = Math::jacobianAdd($r, $table[$window], $A, $P);
            }
        }
        return Math::fromJacobian($r, $P);
    }

    private static function generatorTable($curve)
    {
        if ($curve->_generatorTable !== null) {
            return $curve->_generatorTable;
        }
        $w = Math::GENERATOR_WINDOW_BITS;
        $A = $curve->A;
        $P = $curve->P;
        $G = new Point($curve->G->x, $curve->G->y, 1);
        $table = [new Point(0, 0, 1), $G];
        $size = (1 << $w);
        for ($i = 2; $i < $size; $i++) {
            $table[] = Math::jacobianAdd($table[$i - 1], $G, $A, $P);
        }
        $curve->_generatorTable = $table;
        return $table;
    }

    /**
    Fast way to add two points in elliptic curves

    ## Parameters:
        - p: First Point you want to add
        - q: Second Point you want to add
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
     * Compute n1*p1 + n2*p2 using Shamir's trick (simultaneous double-and-add).
     * Not constant-time - use only with public scalars (e.g. verification).
     *
     * @param Point $p1 First point
     * @param mixed $n1 First scalar
     * @param Point $p2 Second point
     * @param mixed $n2 Second scalar
     * @param mixed $N Order of the elliptic curve
     * @param mixed $A Coefficient of the first-order term
     * @param mixed $P Prime number in the module
     * @return Point n1*p1 + n2*p2
     */
    public static function multiplyAndAdd($p1, $n1, $p2, $n2, $N, $A, $P)
    {
        return Math::fromJacobian(
            Math::shamirMultiply(
                Math::toJacobian($p1), $n1,
                Math::toJacobian($p2), $n2,
                $N, $A, $P
            ), $P
        );
    }

    /**
     * Modular inverse via extended Euclidean algorithm (gmp_invert, implemented in C).
     * Roughly 2-3x faster than Fermat's little theorem for 256-bit operands.
     *
     * @param mixed $x Divisor (must be coprime to n)
     * @param mixed $n Mod for division
     * @return mixed Value representing the division
     */
    public static function inv($x, $n)
    {
        if ($x == 0)
            return Integer::toBigInt(0);

        return gmp_invert($x, $n);
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
        if ($p->y == 0)
            return new Point(0, 0, 0);

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
        $py = $p->y;
        if ($py == 0)
            return new Point(0, 0, 0);

        $px = $p->x;
        $pz = $p->z;
        $ysq = Integer::modulo($py * $py, $P);
        $S = Integer::modulo(4 * $px * $ysq, $P);
        $pz2 = Integer::modulo($pz * $pz, $P);
        if ($A == 0) {
            $M = Integer::modulo(3 * $px * $px, $P);
        } elseif ($A == $P - 3) {
            $M = Integer::modulo(3 * ($px - $pz2) * ($px + $pz2), $P);
        } else {
            $M = Integer::modulo(3 * $px * $px + $A * $pz2 * $pz2, $P);
        }
        $nx = Integer::modulo($M * $M - 2 * $S, $P);
        $ny = Integer::modulo($M * ($S - $nx) - 8 * $ysq * $ysq, $P);
        $nz = Integer::modulo(2 * $py * $pz, $P);

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

        $px = $p->x; $py = $p->y; $pz = $p->z;
        $qx = $q->x; $qy = $q->y; $qz = $q->z;

        $qz2 = Integer::modulo($qz * $qz, $P);
        $pz2 = Integer::modulo($pz * $pz, $P);
        $U1 = Integer::modulo($px * $qz2, $P);
        $U2 = Integer::modulo($qx * $pz2, $P);
        $S1 = Integer::modulo($py * $qz2 * $qz, $P);
        $S2 = Integer::modulo($qy * $pz2 * $pz, $P);

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
        $nz = Integer::modulo($H * $pz * $qz, $P);

        return new Point($nx, $ny, $nz);
    }

    /**
     * Multiply point and scalar in elliptic curves using Montgomery ladder
     * for constant-time execution.
     *
     * @param Point $p First Point to multiply
     * @param mixed $n Scalar to multiply
     * @param mixed $N Order of the elliptic curve
     * @param mixed $P Prime number in the module
     * @param mixed $A Coefficient of the first-order term
     * @return Point that represents the scalar multiplication
     */
    private static function jacobianMultiply($p, $n, $N, $A, $P)
    {
        if ($p->y == 0 or $n == 0)
            return new Point(0, 0, 1);

        if ($n < 0 or $n >= $N) {
            $n = Integer::modulo($n, $N);
        }

        if ($n == 0)
            return new Point(0, 0, 1);

        // Montgomery ladder: always performs one add and one double per bit
        $r0 = new Point(0, 0, 1);
        $r1 = new Point($p->x, $p->y, $p->z);

        $bitLen = Integer::bitLength($n);
        for ($i = $bitLen - 1; $i >= 0; $i--) {
            if (gmp_testbit($n, $i) == 0) {
                $r1 = Math::jacobianAdd($r0, $r1, $A, $P);
                $r0 = Math::jacobianDouble($r0, $A, $P);
            } else {
                $r0 = Math::jacobianAdd($r0, $r1, $A, $P);
                $r1 = Math::jacobianDouble($r1, $A, $P);
            }
        }

        return $r0;
    }

    /**
     * Compute n1*p1 + n2*p2 using Shamir's trick (simultaneous double-and-add).
     * Not constant-time - use only with public scalars (e.g. verification).
     */
    private static function shamirMultiply($jp1, $n1, $jp2, $n2, $N, $A, $P)
    {
        if ($n1 < 0 or $n1 >= $N) {
            $n1 = Integer::modulo($n1, $N);
        }
        if ($n2 < 0 or $n2 >= $N) {
            $n2 = Integer::modulo($n2, $N);
        }

        $jp1p2 = Math::jacobianAdd($jp1, $jp2, $A, $P);

        $l = max(Integer::bitLength($n1), Integer::bitLength($n2));
        $r = new Point(0, 0, 1);

        for ($i = $l - 1; $i >= 0; $i--) {
            $r = Math::jacobianDouble($r, $A, $P);
            $b1 = gmp_testbit($n1, $i) ? 1 : 0;
            $b2 = gmp_testbit($n2, $i) ? 1 : 0;
            if ($b1) {
                $r = Math::jacobianAdd($r, $b2 ? $jp1p2 : $jp1, $A, $P);
            } elseif ($b2) {
                $r = Math::jacobianAdd($r, $jp2, $A, $P);
            }
        }

        return $r;
    }
}
