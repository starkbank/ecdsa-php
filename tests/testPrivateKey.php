<?php

namespace EllipticCurve\Test;
use EllipticCurve\Test\TestCase;


echo "\n\nRunning Private Key tests:";
\Test\printHeader("Private Key");


class TestPrivateKey extends TestCase
{
    public function testStringConversion()
    {
        $privateKey1 = new \EllipticCurve\PrivateKey();
        $string = $privateKey1->toString();
        $privateKey2 = \EllipticCurve\PrivateKey::fromString($string);
        \Test\assertEqual($privateKey1->secret, $privateKey2->secret);
        \Test\assertEqual($privateKey1->curve, $privateKey2->curve);
    }

    public function testDerConversion()
    {
        $privateKey1 = new \EllipticCurve\PrivateKey();
        $der = $privateKey1->toDer();
        $privateKey2 = \EllipticCurve\PrivateKey::fromDer($der);
        \Test\assertEqual($privateKey1->secret, $privateKey2->secret);
        \Test\assertEqual($privateKey1->curve, $privateKey2->curve);
    }

    public function testPemConversion()
    {
        $privateKey1 = new \EllipticCurve\PrivateKey();
        $pem = $privateKey1->toPem();
        $privateKey2 = \EllipticCurve\PrivateKey::fromPem($pem);
        \Test\assertEqual($privateKey1->secret, $privateKey2->secret);
        \Test\assertEqual($privateKey1->curve, $privateKey2->curve);
    }
    
}


$tests = new TestPrivateKey();
$tests->run();
