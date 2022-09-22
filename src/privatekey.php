<?php

namespace EllipticCurve;

use Exception;
use EllipticCurve\Curve;
use EllipticCurve\Utils\Der;
use EllipticCurve\Utils\Pem;
use EllipticCurve\PublicKey;
use EllipticCurve\Utils\Binary;
use EllipticCurve\Utils\Integer;
use EllipticCurve\Utils\DerFieldType;


class PrivateKey
{
    private static $pemTemplate = "-----BEGIN EC PRIVATE KEY-----\n{content}\n-----END EC PRIVATE KEY-----";
    public $curve;
    public $secret;

    function __construct($curve=null, $secret=null)
    {
        $this->curve = is_null($curve) ? Curve::$supportedCurves["secp256k1"] : $curve;
        $this->secret = is_null($secret) ? Integer::between(1, $this->curve->N - 1) : $secret;
    }
    
    function publicKey()
    {
        $curve = $this->curve;
        $publicPoint = Math::multiply(
            $curve->G,
            $this->secret,
            $curve->N,
            $curve->A,
            $curve->P
        );
        return new PublicKey($publicPoint, $curve);
    }

    function toString()
    {
        return Binary::hexFromInt($this->secret);
    }

    function toDer()
    {
        $publicKeyString = $this->publicKey()->toString(true);
        $hexadecimal = Der::encodeConstructed(
            Der::encodePrimitive(DerFieldType::$integer, 1),
            Der::encodePrimitive(DerFieldType::$octetString, Binary::hexFromInt($this->secret)),
            Der::encodePrimitive(DerFieldType::$oidContainer, Der::encodePrimitive(DerFieldType::$object, $this->curve->oid)),
            Der::encodePrimitive(DerFieldType::$publicKeyPointContainer, Der::encodePrimitive(DerFieldType::$bitString, $publicKeyString))
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
        $privateKeyPem = Pem::getContent($string, self::$pemTemplate);
        return PrivateKey::fromDer(Binary::byteStringFromBase64($privateKeyPem));
    }

    static function fromDer($string)
    {
        $hexadecimal = Binary::hexFromByteString($string);
        list($privateKeyFlag, $secretHex, $curveData, $publicKeyString) = Der::parse($hexadecimal)[0];

        if ($privateKeyFlag != 1) {
            throw new Exception(sprintf("Private keys should start with a '1' flag, but a '%s' was found instead"), $privateKeyFlag);
        }
        $curve = Curve::getByOid($curveData[0]);
        $privateKey = PrivateKey::fromString($secretHex, $curve);
        if ($privateKey->publicKey()->toString(true) != $publicKeyString[0])
            throw new Exception("The public key described inside the private key file doesn`t match the actual key of the pair");
        return $privateKey;
    }

    static function fromString($string, $curve=null)
    {
        return new PrivateKey($curve, Binary::intFromHex($string));
    }
}
