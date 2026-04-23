<?php

require_once(__DIR__."/vendor/autoload.php");

$ROUNDS = 100;

$privateKey = new EllipticCurve\PrivateKey();
$publicKey = $privateKey->publicKey();
$message = "This is a benchmark test message";

// Warmup
$sig = EllipticCurve\Ecdsa::sign($message, $privateKey);
EllipticCurve\Ecdsa::verify($message, $sig, $publicKey);

// Benchmark sign
$start = microtime(true);
for ($i = 0; $i < $ROUNDS; $i++) {
    $sig = EllipticCurve\Ecdsa::sign($message, $privateKey);
}
$signTime = (microtime(true) - $start) / $ROUNDS * 1000;

// Benchmark verify
$start = microtime(true);
for ($i = 0; $i < $ROUNDS; $i++) {
    EllipticCurve\Ecdsa::verify($message, $sig, $publicKey);
}
$verifyTime = (microtime(true) - $start) / $ROUNDS * 1000;

echo "\n";
echo sprintf("starkbank-ecdsa benchmark (%d rounds)\n", $ROUNDS);
echo "---------------------------------------\n";
echo sprintf("sign:    %.1fms\n", $signTime);
echo sprintf("verify:  %.1fms\n", $verifyTime);
echo "\n";
