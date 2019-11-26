<?php

namespace starkbank\ecdsa_php;


class PrivateKey {
    function __construct($curve="secp256k1", $openSslPrivateKey=null) {
        if (is_null($openSslPrivateKey)) {
            $config = array(
                "config" => getenv("OPENSSL_CONF"),
                "private_key_type" => OPENSSL_KEYTYPE_EC,
                "curve_name" => $curve
            );

            $response = openssl_pkey_new($config);

            openssl_pkey_export($response, $openSslPrivateKey, null, $config);
        }

        $this->openSslPrivateKey = $openSslPrivateKey;
    }

    function publicKey() {
        // $openSslPublicKey = openssl_pkey_get_details($response)["key"];
        $openSslPublicKey = openssl_pkey_get_public($this->openSslPrivateKey)["key"];

        return new PublicKey($openSslPublicKey);
    }

    function toString () {
        return base64_encode($this->toDer());
    }

    function toDer () {
        openssl_pkey_export($this->openSslPrivateKey, $out, null);
        
        return $out;
    }

    function toPem () {
        $der = $this->toDer();
        $pem = chunk_split(base64_encode($der), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        return $pem;
    }

    static function fromPem ($str) {
        return new PrivateKey(null, openssl_get_privatekey($str));
    }

    static function fromDer ($str) {
        return new PrivateKey(null, openssl_get_privatekey($str));
    }

    static function fromString ($str) {
        return new PrivateKey(null, openssl_get_privatekey(base64_decode($str)));
    }

}

?>