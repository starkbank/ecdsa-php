<?php

namespace EllipticCurve;

use Exception;
use EllipticCurve\Utils\Der;
use EllipticCurve\Utils\Pem;
use EllipticCurve\Curve;
use EllipticCurve\Utils\Binary;
use EllipticCurve\Utils\DerFieldType;


class PublicKey
{
    private static $pemTemplate = "-----BEGIN PUBLIC KEY-----\n{content}\n-----END PUBLIC KEY-----\n";
    private static $ecdsaPublicKeyOid = array(1, 2, 840, 10045, 2, 1);
    private static $evenTag = "02";
    private static $oddTag = "03";
    public $point;
    public $curve;
    
    function __construct($point, $curve)
    {
        $this->point = $point;
        $this->curve = $curve;
    }

    function toString($encoded=false)
    {
        $baseLength = gmp_intval(2 * $this->curve->length());
        $xHex = str_pad(Binary::hexFromInt($this->point->x), $baseLength, "0", STR_PAD_LEFT);
        $yHex = str_pad(Binary::hexFromInt($this->point->y), $baseLength, "0", STR_PAD_LEFT);
        $string = $xHex . $yHex;
        if ($encoded) {
            return "0004" . $string;
        }
        return $string;
    }

    function toCompressed() 
    {
        $baseLength = gmp_intval(2 * $this->curve->length());
        
        if (gmp_intval($this->point->y % 2) == 0)
        {
            $parityTag = self::$evenTag;
        } else {
            $parityTag = self::$oddTag;
        }

        $xHex = str_pad(Binary::hexFromInt($this->point->x), $baseLength, "0", STR_PAD_LEFT);
        return $parityTag . $xHex;
    }

    function toDer()
    {
        $hexadecimal = Der::encodeConstructed(
            Der::encodeConstructed(
                Der::encodePrimitive(DerFieldType::$object, self::$ecdsaPublicKeyOid),
                Der::encodePrimitive(DerFieldType::$object, $this->curve->oid)
            ),
            Der::encodePrimitive(DerFieldType::$bitString, $this->toString(true))
        );
        return Binary::byteStringFromHex($hexadecimal);
    }

    function toPem()
    {
        $der = $this->toDer();
        return Pem::create(Binary::base64FromByteString($der), self::$pemTemplate);
    }

    static function fromPem($string)
    {
        $publicKeyPem = Pem::getContent($string, self::$pemTemplate);
        return PublicKey::fromDer(Binary::byteStringFromBase64($publicKeyPem));
    }

    static function fromDer($string)
    {
        $hexadecimal = Binary::hexFromByteString($string);
        list($curveData, $pointString) = Der::parse($hexadecimal)[0];
        list($publicKeyOid, $curveOid) = $curveData;
        if ($publicKeyOid != self::$ecdsaPublicKeyOid) {
            throw new Exception(sprintf("The Public Key Object Identifier (OID) should be %s, but %s was found instead.",
                self::$ecdsaPublicKeyOid, $publicKeyOid)
            );
        }
        $curve = Curve::getByOid($curveOid);
        return PublicKey::fromString($pointString, $curve);
    }

    static function fromString($string, $curve=null, $validatePoint=true)
    {
        $curve = is_null($curve) ? Curve::$supportedCurves["secp256k1"] : $curve;
        $baseLength = gmp_intval(2 * $curve->length());
        if ((strlen($string) > 2 * $baseLength) and (substr($string, 0, 4) == "0004")) {
            $string = substr($string, 4);
        }

        $xs = substr($string, 0, $baseLength);
        $ys = substr($string, $baseLength);

        $p = new Point(
            Binary::intFromHex($xs),
            Binary::intFromHex($ys)
        );
        $publicKey = new PublicKey($p, $curve);
        if (!$validatePoint)
            return $publicKey;
        if ($p->isAtInfinity())
            throw new Exception("Public Key point is at infinity");
        if (!$curve->contains($p))
            throw new Exception(sprintf("Point (%d,%d) is not valid for curve %s", $p->x, $p->y, $curve->name));
        if (!Math::multiply($p, $curve->N, $curve->N, $curve->A, $curve->P)->isAtInfinity())
            throw new Exception(sprintf("Point (%d,%d) * %s.N is not at infinity", $p->x, $p->y, $curve->name));
        return $publicKey;
    }

    static function fromCompressed($string, $curve=null)
    {
        $curve = is_null($curve) ? Curve::$supportedCurves["secp256k1"] : $curve;
        $parityTag = substr($string, 0, 2);
        $xHex = substr($string, 2, strlen($string));

        if (!in_array($parityTag, array(self::$evenTag, self::$oddTag)))
        {
            throw new Exception(sprintf("Compressed string should start with 02 or 03"));
        }

        $x = Binary::intFromHex($xHex);
        $isEven = $parityTag === self::$evenTag;
        $y = $curve->y($x, $isEven);
        
        return new PublicKey($point=new Point(Binary::intFromHex($x), Binary::intFromHex($y)), $curve=$curve);
    }
}
