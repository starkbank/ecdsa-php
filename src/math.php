<?php

namespace EllipticCurve;
use EllipticCurve\Point;
use EllipticCurve\Utils\Integer;


class Math
{
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
     * Fast scalar multiplication n*G using a precomputed affine table of
     * powers-of-two multiples of G and the width-2 NAF of n. Every non-zero
     * NAF digit triggers one mixed add and zero doublings, trading the ~256
     * doublings of a windowed method for ~86 adds on average - a large net
     * reduction in field multiplications for 256-bit scalars.
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

        $table = Math::generatorPowersTable($curve);
        $A = $curve->A;
        $P = $curve->P;

        $r = new Point(0, 0, 1);
        $i = 0;
        $k = Integer::toBigInt($n);
        $three = gmp_init(3);
        $zero = gmp_init(0);
        while ($k > 0) {
            if (gmp_testbit($k, 0)) {
                // digit = 2 - (k & 3) -> -1 or +1
                $low2 = gmp_intval(gmp_and($k, $three));
                $digit = 2 - $low2;
                $k = $k - $digit;
                $g = $table[$i];
                if ($digit == 1) {
                    $r = Math::jacobianAdd($r, $g, $A, $P);
                } else {
                    $r = Math::jacobianAdd($r, new Point($g->x, $P - $g->y, 1), $A, $P);
                }
            }
            $k = gmp_div_q($k, 2);
            $i += 1;
        }
        return Math::fromJacobian($r, $P);
    }

    /**
     * Build [G, 2G, 4G, ..., 2^nBitLength * G] in affine (z=1) form, so each
     * add in multiplyGenerator hits the mixed-add fast path.
     */
    private static function generatorPowersTable($curve)
    {
        if ($curve->_generatorTable !== null) {
            return $curve->_generatorTable;
        }
        $A = $curve->A;
        $P = $curve->P;
        $current = new Point($curve->G->x, $curve->G->y, 1);
        $table = [$current];
        // NAF of an nBitLength-bit scalar can be up to nBitLength+1 digits.
        for ($j = 0; $j < $curve->nBitLength; $j++) {
            $doubled = Math::jacobianDouble($current, $A, $P);
            if ($doubled->y == 0) {
                $current = $doubled;
            } else {
                $zInv = Math::inv($doubled->z, $P);
                $zInv2 = Integer::modulo($zInv * $zInv, $P);
                $zInv3 = Integer::modulo($zInv2 * $zInv, $P);
                $current = new Point(
                    Integer::modulo($doubled->x * $zInv2, $P),
                    Integer::modulo($doubled->y * $zInv3, $P),
                    1
                );
            }
            $table[] = $current;
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
     * Compute n1*p1 + n2*p2. If $curve is given and exposes glvParams
     * (e.g. secp256k1), uses the GLV endomorphism to split both scalars into
     * ~128-bit halves and run a 4-scalar simultaneous multi-exponentiation.
     * Otherwise falls back to Shamir's trick with JSF. Not constant-time -
     * use only with public scalars (e.g. verification).
     *
     * @param Point  $p1    First point
     * @param mixed  $n1    First scalar
     * @param Point  $p2    Second point
     * @param mixed  $n2    Second scalar
     * @param mixed  $N     Order of the elliptic curve (ignored when $curve given)
     * @param mixed  $A     Coefficient of the first-order term (ignored when $curve given)
     * @param mixed  $P     Prime number in the module (ignored when $curve given)
     * @param object $curve Optional curve; enables GLV if $curve->glvParams is set
     * @return Point n1*p1 + n2*p2
     */
    public static function multiplyAndAdd($p1, $n1, $p2, $n2, $N=null, $A=null, $P=null, $curve=null)
    {
        if ($curve !== null) {
            $N = $curve->N;
            $A = $curve->A;
            $P = $curve->P;
            if ($curve->glvParams !== null) {
                return Math::glvMultiplyAndAdd($p1, $n1, $p2, $n2, $curve);
            }
        }
        return Math::fromJacobian(
            Math::shamirMultiply(
                Math::toJacobian($p1), $n1,
                Math::toJacobian($p2), $n2,
                $N, $A, $P
            ), $P
        );
    }

    /**
     * Compute n1*p1 + n2*p2 using the GLV endomorphism. Splits each 256-bit
     * scalar into two ~128-bit scalars via k == k1 + k2*lambda (mod N), then
     * runs a 4-scalar simultaneous double-and-add over (p1, phi(p1), p2,
     * phi(p2)) with a 16-entry precomputed table of subset sums. Halves the
     * loop length versus the plain Shamir path.
     */
    private static function glvMultiplyAndAdd($p1, $n1, $p2, $n2, $curve)
    {
        $glv = $curve->glvParams;
        $N = $curve->N;
        $A = $curve->A;
        $P = $curve->P;
        $beta = $glv["beta"];

        list($k1, $k2) = Math::glvDecompose(Integer::modulo($n1, $N), $glv, $N);
        list($k3, $k4) = Math::glvDecompose(Integer::modulo($n2, $N), $glv, $N);

        // Base points (affine, z=1) - phi((x,y)) = (beta*x mod P, y).
        $bases = [
            new Point($p1->x, $p1->y, 1),
            new Point(Integer::modulo($beta * $p1->x, $P), $p1->y, 1),
            new Point($p2->x, $p2->y, 1),
            new Point(Integer::modulo($beta * $p2->x, $P), $p2->y, 1),
        ];
        $scalars = [$k1, $k2, $k3, $k4];
        for ($i = 0; $i < 4; $i++) {
            if ($scalars[$i] < 0) {
                $scalars[$i] = -$scalars[$i];
                $bases[$i] = new Point($bases[$i]->x, $P - $bases[$i]->y, 1);
            }
        }

        // Precompute table[idx] = sum of bases[i] selected by bits of idx.
        $table = array_fill(0, 16, new Point(0, 0, 1));
        for ($idx = 1; $idx < 16; $idx++) {
            // low = idx & -idx; i = bit position of low
            $low = $idx & -$idx;
            $i = 0;
            $tmp = $low;
            while ($tmp > 1) { $tmp >>= 1; $i++; }
            $table[$idx] = Math::jacobianAdd($table[$idx ^ $low], $bases[$i], $A, $P);
        }

        $maxLen = 0;
        foreach ($scalars as $s) {
            $bl = Integer::bitLength($s);
            if ($bl > $maxLen) $maxLen = $bl;
        }

        $r = new Point(0, 0, 1);
        $s0 = $scalars[0]; $s1 = $scalars[1]; $s2 = $scalars[2]; $s3 = $scalars[3];
        for ($bit = $maxLen - 1; $bit >= 0; $bit--) {
            $r = Math::jacobianDouble($r, $A, $P);
            $idx = (gmp_testbit($s0, $bit) ? 1 : 0)
                 | (gmp_testbit($s1, $bit) ? 2 : 0)
                 | (gmp_testbit($s2, $bit) ? 4 : 0)
                 | (gmp_testbit($s3, $bit) ? 8 : 0);
            if ($idx) {
                $r = Math::jacobianAdd($r, $table[$idx], $A, $P);
            }
        }

        return Math::fromJacobian($r, $P);
    }

    /**
     * Decompose k into (k1, k2) with k == k1 + k2*lambda (mod N) and
     * |k1|, |k2| ~ sqrt(N). Babai rounding against the precomputed basis
     * {(a1, b1), (a2, b2)}; k1 and k2 may be negative.
     */
    private static function glvDecompose($k, $glv, $N)
    {
        $a1 = $glv["a1"]; $b1 = $glv["b1"];
        $a2 = $glv["a2"]; $b2 = $glv["b2"];
        $halfN = gmp_div_q($N, 2);
        // k is non-negative (reduced mod N); b2*k + halfN is non-negative,
        // and -b1*k + halfN is non-negative (b1 is negative), so gmp_div_q's
        // truncate-toward-zero matches Python's floor division here.
        $c1 = gmp_div_q($b2 * $k + $halfN, $N);
        $c2 = gmp_div_q(-$b1 * $k + $halfN, $N);
        $k1 = $k - $c1 * $a1 - $c2 * $a2;
        $k2 = -$c1 * $b1 - $c2 * $b2;
        return [$k1, $k2];
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

        $pz2 = Integer::modulo($pz * $pz, $P);
        $U2 = Integer::modulo($qx * $pz2, $P);
        $S2 = Integer::modulo($qy * $pz2 * $pz, $P);

        if ($qz == 1) {
            // Mixed affine+Jacobian add: qz2=qz3=1 saves four multiplications.
            $U1 = $px;
            $S1 = $py;
        } else {
            $qz2 = Integer::modulo($qz * $qz, $P);
            $U1 = Integer::modulo($px * $qz2, $P);
            $S1 = Integer::modulo($py * $qz2 * $qz, $P);
        }

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
        $nz = ($qz == 1) ? Integer::modulo($H * $pz, $P) : Integer::modulo($H * $pz * $qz, $P);

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
     * Compute n1*p1 + n2*p2 using Shamir's trick with Joint Sparse Form
     * (Solinas 2001). JSF picks signed digits in {-1, 0, 1} so at most ~l/2
     * digit pairs are non-zero, versus ~3l/4 for the raw binary form. Not
     * constant-time - use only with public scalars (e.g. verification).
     */
    private static function shamirMultiply($jp1, $n1, $jp2, $n2, $N, $A, $P)
    {
        if ($n1 < 0 or $n1 >= $N) {
            $n1 = Integer::modulo($n1, $N);
        }
        if ($n2 < 0 or $n2 >= $N) {
            $n2 = Integer::modulo($n2, $N);
        }

        if ($n1 == 0 and $n2 == 0) {
            return new Point(0, 0, 1);
        }

        $neg = function ($pt) use ($P) {
            return new Point($pt->x, $pt->y == 0 ? gmp_init(0) : $P - $pt->y, $pt->z);
        };

        $jp1p2 = Math::jacobianAdd($jp1, $jp2, $A, $P);
        $jp1mp2 = Math::jacobianAdd($jp1, $neg($jp2), $A, $P);
        $addTable = [
            "1,0"   => $jp1,
            "-1,0"  => $neg($jp1),
            "0,1"   => $jp2,
            "0,-1"  => $neg($jp2),
            "1,1"   => $jp1p2,
            "-1,-1" => $neg($jp1p2),
            "1,-1"  => $jp1mp2,
            "-1,1"  => $neg($jp1mp2),
        ];

        $digits = Math::jsfDigits($n1, $n2);
        $r = new Point(0, 0, 1);
        foreach ($digits as $pair) {
            list($u0, $u1) = $pair;
            $r = Math::jacobianDouble($r, $A, $P);
            if ($u0 != 0 or $u1 != 0) {
                $r = Math::jacobianAdd($r, $addTable["$u0,$u1"], $A, $P);
            }
        }

        return $r;
    }

    /**
     * Joint Sparse Form of (k0, k1): list of signed-digit pairs (u0, u1) in
     * {-1, 0, 1}, ordered MSB-first. At most one of any two consecutive pairs
     * is non-zero, giving density ~1/2 instead of ~3/4 from raw binary.
     */
    private static function jsfDigits($k0, $k1)
    {
        $digits = [];
        $d0 = 0;
        $d1 = 0;
        $k0 = Integer::toBigInt($k0);
        $k1 = Integer::toBigInt($k1);
        $three = gmp_init(3);
        $seven = gmp_init(7);
        while ($k0 + $d0 != 0 or $k1 + $d1 != 0) {
            $a0 = $k0 + $d0;
            $a1 = $k1 + $d1;
            if (gmp_testbit($a0, 0)) {
                $a0m3 = gmp_intval(gmp_and($a0, $three));
                $u0 = ($a0m3 == 1) ? 1 : -1;
                $a0m7 = gmp_intval(gmp_and($a0, $seven));
                $a1m3 = gmp_intval(gmp_and($a1, $three));
                if (($a0m7 == 3 or $a0m7 == 5) and $a1m3 == 2) {
                    $u0 = -$u0;
                }
            } else {
                $u0 = 0;
            }
            if (gmp_testbit($a1, 0)) {
                $a1m3 = gmp_intval(gmp_and($a1, $three));
                $u1 = ($a1m3 == 1) ? 1 : -1;
                $a1m7 = gmp_intval(gmp_and($a1, $seven));
                $a0m3 = gmp_intval(gmp_and($a0, $three));
                if (($a1m7 == 3 or $a1m7 == 5) and $a0m3 == 2) {
                    $u1 = -$u1;
                }
            } else {
                $u1 = 0;
            }
            $digits[] = [$u0, $u1];
            if (2 * $d0 == 1 + $u0) {
                $d0 = 1 - $d0;
            }
            if (2 * $d1 == 1 + $u1) {
                $d1 = 1 - $d1;
            }
            $k0 = gmp_div_q($k0, 2);
            $k1 = gmp_div_q($k1, 2);
        }
        return array_reverse($digits);
    }
}
