<?php

namespace starkbank\ecdsa_php;

class Signature {
    
    function __construct ($der) {
        $this->der = $der;
    }

    function toDer () {
        return $this->der;
    }

    function toBase64 () {
        base64_encode($this->toDer());
    }

    static function fromDer ($str) {
        return new Signature($str);
    }

    static function fromBase64 ($str) {
        return $this->fromDer(base64_decode($str));
    }

}

?>