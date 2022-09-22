<?php

namespace EllipticCurve;

use EllipticCurve\Utils\Der;
use EllipticCurve\Utils\Binary;
use EllipticCurve\Utils\DerFieldType;


class Signature
{
    public $r, $s;
    public $recoveryId;

    function __construct($r, $s, $recoveryId=null)
    {
        $this->r = $r;
        $this->s = $s;
        $this->recoveryId = $recoveryId;
    }

    function toDer($withRecoveryId=false)
    {
        $hexadecimal = $this->_toString();
        $encodedSequence = Binary::byteStringFromHex($hexadecimal);
        if (!$withRecoveryId)
            return $encodedSequence;
        return chr(27 + $this->recoveryId) . $encodedSequence;
    }

    function toBase64($withRecoveryId=false)
    {
        return Binary::base64FromByteString($this->toDer($withRecoveryId));
    }

    static function fromDer($string, $recoveryByte=false)
    {
        $recoveryId = null;
        if ($recoveryByte) {
            $recoveryId = is_int($string[0]) ? $string[0] : ord($string[0]);
            $recoveryId -= 27;
            $string = substr($string, 1);
        }

        $hexadecimal = Binary::hexFromByteString($string);
        return Signature::_fromString($hexadecimal, $recoveryId);
    }

    static function fromBase64($string, $recoveryByte=false)
    {
        $der = Binary::byteStringFromBase64($string);
        return Signature::fromDer($der, $recoveryByte);
    }

    function _toString()
    {
        return Der::encodeConstructed(
            Der::encodePrimitive(DerFieldType::$integer, $this->r),
            Der::encodePrimitive(DerFieldType::$integer, $this->s)
        );
    }

    static function _fromString($string, $recoveryId=null)
    {
        list($r, $s) = Der::parse($string)[0];
        return new Signature($r, $s, $recoveryId);
    }
}
