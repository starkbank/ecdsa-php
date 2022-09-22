<?php

namespace EllipticCurve\Test;

use \EllipticCurve\Signature;
use \EllipticCurve\Test\TestCase;


echo "\n\nRunning Signature tests:";
\Test\printHeader("Signature");


class TestSignature extends TestCase
{
    public function testDerConversion()
    {
        $privateKey = new \EllipticCurve\PrivateKey;
        $message = "This is a text message";

        $signature1 = \EllipticCurve\Ecdsa::sign($message, $privateKey);
        
        $der = $signature1->toDer();
        $signature2 = Signature::fromDer($der);
        
        \Test\assertEqual($signature1->r, $signature2->r);
        \Test\assertEqual($signature1->s, $signature2->s);
    }

    public function testBase64Conversion()
    {
        $privateKey = new \EllipticCurve\PrivateKey;
        $message = "This is a text message";

        $signature1 = \EllipticCurve\Ecdsa::sign($message, $privateKey);

        $base64 = $signature1->toBase64();

        $signature2 = \EllipticCurve\Signature::fromBase64($base64);

        \Test\assertEqual($signature1->r, $signature2->r);
        \Test\assertEqual($signature1->s, $signature2->s);
    }
}


$tests = new TestSignature();
$tests->run();
