<?php

namespace EllipticCurve\Test;
use EllipticCurve\Test\TestCase;


echo "\n\nRunning 1000 tests:";
\Test\printHeader("Random");


class TestRandom extends TestCase
{
    public function testMany()
    {
        $testQuantity = 1000;
        $success = true;
        for ($i=0; $i<$testQuantity; $i++) {
            $privateKey1 = new \EllipticCurve\PrivateKey();
            $publicKey1 = $privateKey1->publicKey();

            $privateKeyPem = $privateKey1->toPem();
            $publicKeyPem = $publicKey1->toPem();

            $privateKey2 = \EllipticCurve\PrivateKey::fromPem($privateKeyPem);
            $publicKey2 = \EllipticCurve\PublicKey::fromPem($publicKeyPem);

            $message = "test";

            $signatureBase64 = \EllipticCurve\Ecdsa::sign($message, $privateKey2)->toBase64();

            $signature = \EllipticCurve\Signature::fromBase64($signatureBase64);

            $result = \EllipticCurve\Ecdsa::verify($message, $signature, $publicKey2);
            $success = $success & $result;
        }
        \Test\assertTrue($success);
    }
}


$tests = new TestRandom();
$tests->run();
