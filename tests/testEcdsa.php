<?php

namespace EllipticCurve\Test;

use EllipticCurve\Test\TestCase;
use \EllipticCurve\Signature;


echo "\n\nRunning ECDSA tests:";
\Test\printHeader("ECDSA");


class TestEcdsa extends TestCase
{
    public function testVerifyRightMessage()
    {
        $privateKey = new \EllipticCurve\PrivateKey;
        $publicKey = $privateKey->publicKey();

        $message = "This is the right message";
        $signature = \EllipticCurve\Ecdsa::sign($message, $privateKey);
        \Test\assertTrue(\EllipticCurve\Ecdsa::verify($message, $signature, $publicKey));
    }

    public function testVerifyWrongMessage()
    {
        $privateKey = new \EllipticCurve\PrivateKey;
        $publicKey = $privateKey->publicKey();

        $message1 = "This is the right message";
        $message2 = "This is the wrong message";
        $signature = \EllipticCurve\Ecdsa::sign($message1, $privateKey);
        \Test\assertFalse(\EllipticCurve\Ecdsa::verify($message2, $signature, $publicKey));
    }


    public function testZeroSignature()
    {
        $privateKey = new \EllipticCurve\PrivateKey;
        $publicKey = $privateKey->publicKey();

        $message = "This is the wrong message";
        \Test\assertFalse(\EllipticCurve\Ecdsa::verify($message, new Signature(0, 0), $publicKey));
    }
}


$tests = new TestEcdsa();
$tests->run();
