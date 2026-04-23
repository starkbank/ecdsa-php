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
     * Return the bit length of a GMP number
     */
    public static function bitLength($number)
    {
        $number = Integer::toBigInt($number);
        if ($number == 0) return 0;
        // gmp_scan1 finds the highest set bit position
        $hex = gmp_strval($number, 16);
        return (strlen($hex) - 1) * 4 + strlen(decbin(hexdec($hex[0])));
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

    /**
     * Generate nonce values via hedged RFC 6979: deterministic k derivation
     * with fresh random entropy mixed into K-init (hedged RFC 6979 §3.6).
     * Same message and key yield different signatures, while preserving
     * RFC 6979's protection against RNG failures.
     *
     * Returns a Generator that yields nonce values.
     *
     * @param string $hashBytes  Raw hash bytes
     * @param mixed  $secret     Private key scalar (GMP)
     * @param object $curve      Curve object with N property
     * @param string $hashfunc   Hash function name (e.g., "sha256")
     * @return \Generator
     */
    public static function rfc6979($hashBytes, $secret, $curve, $hashfunc)
    {
        $orderBitLen = $curve->nBitLength;
        $orderByteLen = intdiv($orderBitLen + 7, 8);

        $secretHex = str_pad(Binary::hexFromInt($secret), $orderByteLen * 2, "0", STR_PAD_LEFT);
        $secretBytes = Binary::byteStringFromHex($secretHex);

        $hashReduced = Integer::modulo(Binary::numberFromByteString($hashBytes, $orderBitLen), $curve->N);
        $hashHex = str_pad(Binary::hexFromInt($hashReduced), $orderByteLen * 2, "0", STR_PAD_LEFT);
        $hashOctets = Binary::byteStringFromHex($hashHex);

        $extraEntropyMax = gmp_sub(gmp_pow(2, $orderByteLen * 8), 1);
        $extraEntropyHex = str_pad(Binary::hexFromInt(Integer::between(0, $extraEntropyMax)), $orderByteLen * 2, "0", STR_PAD_LEFT);
        $extraEntropy = Binary::byteStringFromHex($extraEntropyHex);

        $hLen = strlen(hash($hashfunc, "", true));
        $V = str_repeat("\x01", $hLen);
        $K = str_repeat("\x00", $hLen);

        $K = hash_hmac($hashfunc, $V . "\x00" . $secretBytes . $hashOctets . $extraEntropy, $K, true);
        $V = hash_hmac($hashfunc, $V, $K, true);
        $K = hash_hmac($hashfunc, $V . "\x01" . $secretBytes . $hashOctets . $extraEntropy, $K, true);
        $V = hash_hmac($hashfunc, $V, $K, true);

        while (true) {
            $T = "";
            while (strlen($T) * 8 < $orderBitLen) {
                $V = hash_hmac($hashfunc, $V, $K, true);
                $T .= $V;
            }

            $k = Binary::numberFromByteString($T, $orderBitLen);

            if ($k >= 1 && $k <= $curve->N - 1) {
                yield $k;
            }

            $K = hash_hmac($hashfunc, $V . "\x00", $K, true);
            $V = hash_hmac($hashfunc, $V, $K, true);
        }
    }

    private static function generate($max)
    {
        $bitsNeeded = Integer::bitLength($max);
        $bytesNeeded = intdiv($bitsNeeded, 8) + 1;

        $randomBytes = random_bytes($bytesNeeded);
        $randomHexadecimal = Binary::hexFromByteString($randomBytes);
        $randomBits = substr(Binary::bitsFromHex($randomHexadecimal), 0, $bitsNeeded);

        $randomInt = Integer::toBigInt($randomBits, 2);
        return $randomInt;
    }
}
