<?php

namespace EllipticCurve\Test;
use EllipticCurve\Test\TestCase;


echo "\n\nRunning Public Key tests:";
\Test\printHeader("Public Key");


class TestPublicKey extends TestCase
{
    public function testStringConversion()
    {
        $privateKey = new \EllipticCurve\PrivateKey();
        $publicKey1 = $privateKey->publicKey();
        $string = $publicKey1->toString();
        $publicKey2 = \EllipticCurve\PublicKey::fromString($string);
        \Test\assertEqual($publicKey1->point->x, $publicKey2->point->x);
        \Test\assertEqual($publicKey1->point->y, $publicKey2->point->y);
        \Test\assertEqual($publicKey1->curve, $publicKey2->curve);
    }

    public function testDerConversion()
    {
        $privateKey = new \EllipticCurve\PrivateKey();
        $publicKey1 = $privateKey->publicKey();
        $der = $publicKey1->toDer();
        $publicKey2 = \EllipticCurve\PublicKey::fromDer($der);
        \Test\assertEqual($publicKey1->point->x, $publicKey2->point->x);
        \Test\assertEqual($publicKey1->point->y, $publicKey2->point->y);
        \Test\assertEqual($publicKey1->curve, $publicKey2->curve);
    }

    public function testPemConversion()
    {
        $privateKey = new \EllipticCurve\PrivateKey();
        $publicKey1 = $privateKey->publicKey();
        $pem = $publicKey1->toPem();
        $publicKey2 = \EllipticCurve\PublicKey::fromPem($pem);
        \Test\assertEqual($publicKey1->point->x, $publicKey2->point->x);
        \Test\assertEqual($publicKey1->point->y, $publicKey2->point->y);
        \Test\assertEqual($publicKey1->curve, $publicKey2->curve);
    }
}


$tests = new TestPublicKey();
$tests->run();
