<?php

namespace EllipticCurve;

use EllipticCurve\Signature;
use EllipticCurve\Utils\Binary;
use EllipticCurve\Utils\Integer;


class Ecdsa
{
    public static function sign($message, $privateKey, $hashfunc="sha256")
    {
        $curve = $privateKey->curve;
        $byteMessage = hash($hashfunc, $message, true);
        $numberMessage = Binary::numberFromByteString($byteMessage, $curve->nBitLength);

        $r = Integer::toBigInt(0);
        $s = Integer::toBigInt(0);
        $randSignPoint = null;
        $kIterator = Integer::rfc6979($byteMessage, $privateKey->secret, $curve, $hashfunc);

        while ($r == 0 or $s == 0) {
            $randNum = $kIterator->current();
            $kIterator->next();
            $randSignPoint = Math::multiplyGenerator($curve, $randNum);
            $r = Integer::modulo($randSignPoint->x, $curve->N);
            $s = Integer::modulo(($numberMessage + $r * $privateKey->secret) * (Math::inv($randNum, $curve->N)), $curve->N);
        }
        $recoveryId = gmp_intval($randSignPoint->y & 1);
        if ($randSignPoint->y > $curve->N) {
            $recoveryId += 2;
        }
        if ($s > gmp_div_q($curve->N, 2)) {
            $s = $curve->N - $s;
            $recoveryId ^= 1;
        }

        return new Signature($r, $s, $recoveryId);
    }

    public static function verify($message, $signature, $publicKey, $hashfunc="sha256")
    {
        $byteMessage = hash($hashfunc, $message, true);
        $curve = $publicKey->curve;
        $numberMessage = Binary::numberFromByteString($byteMessage, $curve->nBitLength);

        $r = $signature->r;
        $s = $signature->s;

        if (!($r >= 1 and $r <= $curve->N - 1))
            return false;
        if (!($s >= 1 and $s <= $curve->N - 1))
            return false;
        if (!$curve->contains($publicKey->point))
            return false;

        $inv = Math::inv($s, $curve->N);
        $v = Math::multiplyAndAdd(
            $curve->G, Integer::modulo($numberMessage * $inv, $curve->N),
            $publicKey->point, Integer::modulo($r * $inv, $curve->N),
            $curve->N, $curve->A, $curve->P, $curve
        );
        if ($v->isAtInfinity())
            return false;
        return (Integer::modulo($v->x, $curve->N) == $r);
    }
}
