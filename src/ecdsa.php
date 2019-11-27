<?php

namespace EcdsaPhp;

$alg = OPENSSL_ALGO_SHA256;


class Ecdsa {
    
    public static function sign ($message, $privateKey) {
        $signature = null;
        $signatureString = openssl_sign($message, $signature, $privateKey->openSslPrivateKey, $alg);
        return new Signature($signatureString);
    }

    public static function verify ($message, $signature, $publicKey) {
        $success = openssl_verify($message, base64_decode($signature->toDer()), $publicKey->openSslPublicKey, $alg);
        if ($success == 1) {
            return true;
        }
        return false;
    }
}

?>