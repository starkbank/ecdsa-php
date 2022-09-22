<?php

namespace EllipticCurve\Utils;

use GMP;
use Exception;
use ValueError;


class Integer
{
    public static function modulo($x, $n)
    {
        $mod = gmp_div_r($x, $n);
        
        if ($mod < 0) {
            $mod += $n;
        }
        
        return $mod;
    }

    public static function toBigInt($value, $base=0)
    {
        return ($value instanceof GMP) ? $value : gmp_init($value, $base);
    }

    /**
    Return integer x in the range: min <= x <= max

    ## Parameters (required):
        - min: minimum value of the integer
        - max: maximum value of the integer
        
    ## Return:
     */
    public static function between($min, $max)
    {
        $range = $max - $min;
        if ($range < 0) {
            throw new ValueError("Argument #1 (\$min) must be less than or equal to argument #2 (\$max)");
        }

        if ($range == 0) {
            return $min;
        }

        $randomInt = Integer::generate($range);
        while ($randomInt > $range) {
            $randomInt = Integer::generate($range);
        }

        return gmp_add($randomInt, $min);
    }

    private static function generate($max)
    {
        $bitsNeeded = Integer::bitLength($max, 2);
        $bytesNeeded = intdiv($bitsNeeded, 8) + 1;
        
        $randomBytes = random_bytes($bytesNeeded);
        $randomHexadecimal = Binary::hexFromByteString($randomBytes);
        $randomBits = substr(Binary::bitsFromHex($randomHexadecimal), 0, $bitsNeeded);

        $randomInt = Integer::toBigInt($randomBits, 2);
        return $randomInt;
    }

    private static function bitLength($number)
    {
        for ($power = 1; $power < PHP_INT_MAX; $power++) {
            if($number >= gmp_pow(2, $power - 1) and $number < gmp_pow(2, $power))
                return $power;
        }
        throw new Exception("Bit length calculation exceeded limit.");
    }
}
