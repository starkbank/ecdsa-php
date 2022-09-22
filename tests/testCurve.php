<?php

namespace EllipticCurve\Test;
use EllipticCurve\Test\TestCase;
use EllipticCurve\Curve;
use Exception;


echo "\n\nRunning Curve tests:";
\Test\printHeader("Curve");


class TestCurve extends TestCase
{
    public function testSupportedCurve()
    {
        $newCurve = new Curve(
            "0x0000000000000000000000000000000000000000000000000000000000000000",
            "0x0000000000000000000000000000000000000000000000000000000000000007",
            "0xfffffffffffffffffffffffffffffffffffffffffffffffffffffffefffffc2f",
            "0xfffffffffffffffffffffffffffffffebaaedce6af48a03bbfd25e8cd0364141",
            "0x79be667ef9dcbbac55a06295ce870b07029bfcdb2dce28d959f2815b16f81798",
            "0x483ada7726a3c4655da4fbfc0e1108a8fd17b448a68554199c47d08ffb10d4b8",
            "secp256k1",
            array(1, 3, 132, 0, 10)
        );
        echo " testing with {$newCurve->name}";
    
        $privateKey1 = new \EllipticCurve\PrivateKey($newCurve);
        $publicKey1 = $privateKey1->publicKey();

        $privateKeyPem = $privateKey1->toPem();
        $publicKeyPem = $publicKey1->toPem();

        $privateKey2 = \EllipticCurve\PrivateKey::fromPem($privateKeyPem);
        $publicKey2 = \EllipticCurve\PublicKey::fromPem($publicKeyPem);

        $message = "test";

        $signatureBase64 = \EllipticCurve\Ecdsa::sign($message, $privateKey2)->toBase64();

        $signature = \EllipticCurve\Signature::fromBase64($signatureBase64);

        $result = \EllipticCurve\Ecdsa::verify($message, $signature, $publicKey2);
        \Test\assertEqual($result, true);
    }

    public function testUnsupportedCurve()
    {
        //This is an alternative 256-bit field elliptic curve in the Weierstrass form
        $newCurve = new Curve(
            "0xa9fb57dba1eea9bc3e660a909d838d726e3bf623d52620282013481d1f6e5374",
            "0x662c61c430d84ea4fe66a7733d0b76b7bf93ebc4af2f49256ae58101fee92b04",
            "0xa9fb57dba1eea9bc3e660a909d838d726e3bf623d52620282013481d1f6e5377",
            "0xa9fb57dba1eea9bc3e660a909d838d718c397aa3b561a6f7901e0e82974856a7",
            "0xa3e8eb3cc1cfe7b7732213b23a656149afa142c47aafbc2b79a191562e1305f4",
            "0x2d996c823439c56d7f7b22e14644417e69bcb6de39d027001dabe8f35b25c9be",
            "brainpoolP256t1",
            array(1, 3, 36, 3, 3, 2, 8, 1, 1, 8)
        );
        echo " testing with {$newCurve->name}";
    
        $privateKey1 = new \EllipticCurve\PrivateKey($newCurve);
        $publicKey1 = $privateKey1->publicKey();

        $privateKeyPem = $privateKey1->toPem();
        $publicKeyPem = $publicKey1->toPem();

        try {
            $privateKey2 = \EllipticCurve\PrivateKey::fromPem($privateKeyPem);
        } catch (Exception $e) {
            if (!(preg_match("/Unknown curve(.*)/", $e->getMessage()) == 1)) {
                throw new Exception("failed");
            }
        }

        try {
            $publicKey2 = \EllipticCurve\PublicKey::fromPem($publicKeyPem);
        } catch (Exception $e) {
            if (!(preg_match("/Unknown curve(.*)/", $e->getMessage()) == 1)) {
                throw new Exception("failed");
            }
        }

        \Test\assertEqual(true, true);
    }

    public function testAddNewCurve()
    {
        $newCurve = new Curve(
            "0xf1fd178c0b3ad58f10126de8ce42435b3961adbcabc8ca6de8fcf353d86e9c00",
            "0xee353fca5428a9300d4aba754a44c00fdfec0c9ae4b1a1803075ed967b7bb73f",
            "0xf1fd178c0b3ad58f10126de8ce42435b3961adbcabc8ca6de8fcf353d86e9c03",
            "0xf1fd178c0b3ad58f10126de8ce42435b53dc67e140d2bf941ffdd459c6d655e1",
            "0xb6b3d4c356c139eb31183d4749d423958c27d2dcaf98b70164c97a2dd98f5cff",
            "0x6142e0f7c8b204911f9271f0f3ecef8c2701c307e8e4c9e183115a1554062cfb",
            "frp256v1",
            array(1, 2, 250, 1, 223, 101, 256, 1)
        );
        Curve::add($newCurve);
        echo " testing with {$newCurve->name}";
    
        $privateKey1 = new \EllipticCurve\PrivateKey($newCurve);
        $publicKey1 = $privateKey1->publicKey();

        $privateKeyPem = $privateKey1->toPem();
        $publicKeyPem = $publicKey1->toPem();

        $privateKey2 = \EllipticCurve\PrivateKey::fromPem($privateKeyPem);
        $publicKey2 = \EllipticCurve\PublicKey::fromPem($publicKeyPem);

        $message = "test";

        $signatureBase64 = \EllipticCurve\Ecdsa::sign($message, $privateKey2)->toBase64();

        $signature = \EllipticCurve\Signature::fromBase64($signatureBase64);

        $result = \EllipticCurve\Ecdsa::verify($message, $signature, $publicKey2);
        \Test\assertEqual($result, true);
    }
}


$tests = new TestCurve();
$tests->run();
