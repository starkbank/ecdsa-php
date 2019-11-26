<?php

namespace starkbank\ecdsa_php;

class PublicKey {
    
    function __construct ($openSslPublicKey) {
        $this->openSslPublicKey = $openSslPublicKey;
    }

    function toString () {
        return base64_encode($this->toDer());
    }

    function toDer () {
        openssl_pkey_export($this->openSslPublicKey, $out, null);
        
        return $out;
    }

    function toPem () {
        $der = $this->toDer();
        $pem = chunk_split(base64_encode($der), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        return $pem;
    }

    static function fromPem ($str) {
        return new PrivateKey(openssl_get_publickey($str));
    }

    static function fromDer ($str) {
        return new PrivateKey(openssl_get_publickey($str));
    }

    static function fromString ($str) {
        return new PrivateKey(openssl_get_publickey(base64_decode($str)));
    }
}

?>