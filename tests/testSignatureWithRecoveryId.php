<?php

namespace EllipticCurve\Test;

use \EllipticCurve\Signature;
use \EllipticCurve\Test\TestCase;


echo "\n\nRunning Signature with Recovery Id tests:";
\Test\printHeader("Signature with Recovery Id");


class TestSignatureRecoveryId extends TestCase
{
    public function testDerConversion()
    {
        $privateKey = new \EllipticCurve\PrivateKey();
        $message = "This is a text message";

        $signature1 = \EllipticCurve\Ecdsa::sign($message, $privateKey);
        
        $der = $signature1->toDer(true);
        $signature2 = Signature::fromDer($der, true);
        
        \Test\assertEqual($signature1->r, $signature2->r);
        \Test\assertEqual($signature1->s, $signature2->s);
        \Test\assertEqual($signature1->recoveryId, $signature2->recoveryId);
    }

    public function testBase64Conversion()
    {
        $privateKey = new \EllipticCurve\PrivateKey();
        $message = "This is a text message";

        $signature1 = \EllipticCurve\Ecdsa::sign($message, $privateKey);

        $base64 = $signature1->toBase64(true);

        $signature2 = \EllipticCurve\Signature::fromBase64($base64, true);

        \Test\assertEqual($signature1->r, $signature2->r);
        \Test\assertEqual($signature1->s, $signature2->s);
        \Test\assertEqual($signature1->recoveryId, $signature2->recoveryId);
    }
}


$tests = new TestSignatureRecoveryId();
$tests->run();
