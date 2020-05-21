<?php

namespace EllipticCurve;


class Signature {

    function __construct ($der) {
        $this->der = $der;
    }

    function toDer () {
        return $this->der;
    }

    function toBase64 () {
        return base64_encode($this->der);
    }

    static function fromDer ($str) {
        return new Signature($str);
    }

    static function fromBase64 ($str) {
        // Convert Base64URL to Base64 by replacing “-” with “+” and “_” with “/”
        $str = strtr($str, '-_', '+/');
        return new Signature(base64_decode($str));
    }
}

?>
