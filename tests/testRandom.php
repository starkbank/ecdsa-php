<?php

namespace EllipticCurve\Test;
use EllipticCurve\Test\TestCase;


echo "\n\nRunning 1000 tests:";
\Test\printHeader("Random");


const SECOND_TO_MICROSECOND = 1000;


class TestRandom extends TestCase
{
    public function testMany()
    {
        $averageSign = 0;
        $averageVerify = 0;
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

            $startSign = microtime(true);
            $signatureBase64 = \EllipticCurve\Ecdsa::sign($message, $privateKey2)->toBase64();
            $elapsedTimeSign = microtime(true) - $startSign;
            $averageSign += $elapsedTimeSign;

            $signature = \EllipticCurve\Signature::fromBase64($signatureBase64);

            $startVerify = microtime(true);
            $result = \EllipticCurve\Ecdsa::verify($message, $signature, $publicKey2);
            $elapsedTimeVerify = microtime(true) - $startVerify;
            $averageVerify += $elapsedTimeVerify;
            $success = $success & $result;
        }
        \Test\assertEqual($success, true);
        $averageSign = ($averageSign / $testQuantity) * SECOND_TO_MICROSECOND;
        $averageVerify = ($averageVerify / $testQuantity) * SECOND_TO_MICROSECOND;
        echo sprintf("\nAverage time sign: %.1f ms.", $averageSign);
        echo sprintf("\nAverage time verify: %.1f ms.", $averageVerify);
    }
}


$tests = new TestRandom();
$tests->run();
