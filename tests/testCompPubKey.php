<?php

namespace EllipticCurve\Test;

use EllipticCurve\PublicKey;
use EllipticCurve\Test\TestCase;


echo "\n\nRunning Compressed Public Key Test tests:";
\Test\printHeader("Compressed Public Key Test");


class TestCompPubKey extends TestCase
{
    public function testBatch()
    {
        for ($i = 1; $i <= 1000; $i++) {
            $privateKey = new \EllipticCurve\PrivateKey();
            $publicKey = $privateKey->publicKey();
            $publicKeyString = $publicKey->toCompressed();
            $recoveredPublicKey = \EllipticCurve\PublicKey::fromCompressed($publicKeyString, $publicKey->curve);
            \Test\assertEqual($publicKey->point->x, $recoveredPublicKey->point->x);
            \Test\assertEqual($publicKey->point->y, $recoveredPublicKey->point->y);
        }
        
    }

    public function testFromCompressedEven()
    {
        $publicKeyCompressed = "0252972572d465d016d4c501887b8df303eee3ed602c056b1eb09260dfa0da0ab2";
        $publicKey1 = \EllipticCurve\PublicKey::fromCompressed($publicKeyCompressed);
        $publicKey2 = \EllipticCurve\PublicKey::fromPem("\n-----BEGIN PUBLIC KEY-----\nMFYwEAYHKoZIzj0CAQYFK4EEAAoDQgAEUpclctRl0BbUxQGIe43zA+7j7WAsBWse\nsJJg36DaCrKIdC9NyX2e22/ZRrq8AC/fsG8myvEXuUBe15J1dj/bHA==\n-----END PUBLIC KEY-----\n");
        \Test\assertEqual($publicKey1->toPem(), $publicKey2->toPem());
    }

    public function testFromCompressedOdd()
    {
        $publicKeyCompressed = "0318ed2e1ec629e2d3dae7be1103d4f911c24e0c80e70038f5eb5548245c475f50";
        $publicKey1 = \EllipticCurve\PublicKey::fromCompressed($publicKeyCompressed);
        $publicKey2 = \EllipticCurve\PublicKey::fromPem("\n-----BEGIN PUBLIC KEY-----\nMFYwEAYHKoZIzj0CAQYFK4EEAAoDQgAEGO0uHsYp4tPa574RA9T5EcJODIDnADj1\n61VIJFxHX1BMIg0B4cpBnLG6SzOTthXpndIKpr8HEHj3D9lJAI50EQ==\n-----END PUBLIC KEY-----\n");
        \Test\assertEqual($publicKey1->toPem(), $publicKey2->toPem());      
    }

    public function testToCompressedEven()
    {
        $publicKey = \EllipticCurve\PublicKey::fromPem("-----BEGIN PUBLIC KEY-----\nMFYwEAYHKoZIzj0CAQYFK4EEAAoDQgAEUpclctRl0BbUxQGIe43zA+7j7WAsBWse\nsJJg36DaCrKIdC9NyX2e22/ZRrq8AC/fsG8myvEXuUBe15J1dj/bHA==\n-----END PUBLIC KEY-----");
        $publicKeyCompressed = $publicKey->toCompressed();
        \Test\assertEqual($publicKeyCompressed, "0252972572d465d016d4c501887b8df303eee3ed602c056b1eb09260dfa0da0ab2");
        
    }

    public function testToCompressedOdd()
    {
        $publicKey = \EllipticCurve\PublicKey::fromPem("-----BEGIN PUBLIC KEY-----\nMFYwEAYHKoZIzj0CAQYFK4EEAAoDQgAEGO0uHsYp4tPa574RA9T5EcJODIDnADj1\n61VIJFxHX1BMIg0B4cpBnLG6SzOTthXpndIKpr8HEHj3D9lJAI50EQ==\n-----END PUBLIC KEY-----");
        $publicKeyCompressed = $publicKey->toCompressed();
        \Test\assertEqual($publicKeyCompressed, "0318ed2e1ec629e2d3dae7be1103d4f911c24e0c80e70038f5eb5548245c475f50");     
    }
}


$tests = new TestCompPubKey();
$tests->run();
