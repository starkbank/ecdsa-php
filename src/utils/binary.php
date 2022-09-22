<?php

namespace EllipticCurve\Utils;
use EllipticCurve\Utils\Integer;


class Binary
{
    public static function hexFromInt($number)
    {
        $hexadecimal = gmp_strval($number, 16);
        if (strlen($hexadecimal) % 2 == 1)
            $hexadecimal = "0" . $hexadecimal;
        return $hexadecimal;
    }

    public static function intFromHex($hexadecimal)
    {
        return Integer::toBigInt($hexadecimal, 16);
    }

    public static function hexFromByteString($byteString)
    {
        return bin2hex($byteString);
    }

    public static function byteStringFromHex($hexadecimal)
    {
        return hex2bin($hexadecimal);
    }

    public static function numberFromByteString($byteString)
    {
        return Binary::intFromHex(Binary::hexFromByteString($byteString));
    }

    public static function base64FromByteString($byteString)
    {
        return base64_encode($byteString);
    }

    public static function byteStringFromBase64($base64String)
    {
        return base64_decode($base64String);
    }

    public static function bitsFromHex($hexadecimal)
    {
        return str_pad(gmp_strval(Binary::intFromHex($hexadecimal), 2), 4 * strlen($hexadecimal), "0", STR_PAD_LEFT);
    }
}
