<?php

namespace EllipticCurve\Test;

require_once(__DIR__."/../vendor/autoload.php");
require_once("assert.php");


echo "\n\nStarting tests\n";


class TestCase
{
    public function run() {
        $methods = get_class_methods($this);

        foreach($methods as $method) {
            if ($method != "__construct" and $method != "run") {
                \Test\printSubHeader($method);
                $this->{$method}();
            }
        }
    }
}

include_once("testPublicKey.php");
include_once("testCompPubKey.php");
include_once("testEcdsa.php");
include_once("testPrivateKey.php");
include_once("testSignature.php");
include_once("testSignatureWithRecoveryId.php");
include_once("testOpenSSL.php");
include_once("testCurve.php");
include_once("testRandomInteger.php");
include_once("testRandom.php");

echo "\n\nAll tests concluded\n\n";
