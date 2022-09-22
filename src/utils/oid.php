<?php

namespace EllipticCurve\Utils;

use EllipticCurve\Utils\Binary;


class Oid
{
    public static function oidFromHex($hexadecimal)
    {
        $firstByte = substr($hexadecimal, 0, 2);
        $remainingBytes = substr($hexadecimal, 2);
        $firstByteInt = gmp_intval(Binary::intFromHex($firstByte));
        $oid = array(intdiv($firstByteInt, 40), $firstByteInt % 40);
        $oidInt = 0;
        while (strlen($remainingBytes) > 0) {
            $byte = substr($remainingBytes, 0, 2);
            $remainingBytes = substr($remainingBytes, 2);
            $byteInt = gmp_intval(Binary::intFromHex($byte));
            if ($byteInt >= 128) {
                $oidInt = (128 * $oidInt) + ($byteInt - 128);
                continue;
            }
            $oidInt = (128 * $oidInt) + $byteInt;
            $oid[] = $oidInt;
            $oidInt = 0;
        }
        return $oid;
    }
    
    public static function oidToHex($oid)
    {
        $hexadecimal = Binary::hexFromInt(40 * $oid[0] + $oid[1]);
        foreach (array_slice($oid, 2) as $number) {
            $hexadecimal .= Oid::oidNumberToHex($number);
        }
        return $hexadecimal;
    }
    
    private static function oidNumberToHex($number)
    {
        $hexadecimal = "";
        $endDelta = 0;
        while ($number > 0) {
            $hexadecimal = Binary::hexFromInt(($number % 128) + $endDelta) . $hexadecimal;
            $number = intdiv($number, 128);
            $endDelta = 128;
        }
        return $hexadecimal ? $hexadecimal : "00";
    }
}
