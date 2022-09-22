<?php

namespace EllipticCurve;

use EllipticCurve\Signature;
use EllipticCurve\Utils\Binary;
use EllipticCurve\Utils\Integer;


class Ecdsa
{
    public static function sign($message, $privateKey, $hashfunc="sha256")
    {
        $byteMessage = hash($hashfunc, $message);
        $numberMessage = Binary::intFromHex($byteMessage);
        $curve = $privateKey->curve;

        $r = Integer::toBigInt(0);
        $s = Integer::toBigInt(0);
        $randSignPoint = null;
        
        while ($r == 0 or $s == 0) {
            $randNum = Integer::between(1, $curve->N - 1);
            $randSignPoint = Math::multiply($curve->G, $randNum, $curve->N, $curve->A, $curve->P);
            $r = Integer::modulo($randSignPoint->x, $curve->N);
            $s = Integer::modulo(($numberMessage + $r * $privateKey->secret) * (Math::inv($randNum, $curve->N)), $curve->N);
        }
        $recoveryId = gmp_intval($randSignPoint->y & 1);
        if ($randSignPoint->y > $curve->N) {
            $recoveryId += 2;
        }

        return new Signature($r, $s, $recoveryId);
    }

    public static function verify($message, $signature, $publicKey, $hashfunc="sha256")
    {
        $byteMessage = hash($hashfunc, $message);
        $numberMessage = Binary::intFromHex($byteMessage);
        $curve = $publicKey->curve;
        
        $r = $signature->r;
        $s = $signature->s;
        
        if (!($r >= 1 and $r <= $curve->N - 1))
            return false;
        if (!($s >= 1 and $s <= $curve->N - 1))
            return false;
        
        $inv = Math::inv($s, $curve->N);
        $u1 = Math::multiply($curve->G, Integer::modulo($numberMessage * $inv, $curve->N), $curve->N, $curve->A, $curve->P);
        $u2 = Math::multiply($publicKey->point, Integer::modulo($r * $inv, $curve->N), $curve->N, $curve->A, $curve->P);
        
        $v = Math::add($u1, $u2, $curve->A, $curve->P);
        if ($v->isAtInfinity())
            return false;
        return (Integer::modulo($v->x, $curve->N) == $r);
    }
}
