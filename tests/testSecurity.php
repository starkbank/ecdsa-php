<?php

namespace EllipticCurve\Test;

use EllipticCurve\Test\TestCase;
use EllipticCurve\Ecdsa;
use EllipticCurve\PrivateKey;
use EllipticCurve\PublicKey;
use EllipticCurve\Signature;
use EllipticCurve\Point;
use EllipticCurve\Math;
use EllipticCurve\CurveFp;
use EllipticCurve\Utils\Binary;
use EllipticCurve\Utils\Integer;


echo "\n\nRunning Security tests:";


// ============================================================================
// RFC 6979 Known Answer Tests (prime256v1/SHA-256)
// ============================================================================
\Test\printHeader("RFC 6979 Known Answer (prime256v1)");

class TestRfc6979KnownAnswer extends TestCase
{
    private $privateKey;
    private $publicKey;

    function __construct()
    {
        $this->privateKey = new PrivateKey(
            CurveFp::$supportedCurves["prime256v1"],
            gmp_init("0xC9AFA9D845BA75166B5C215767B1D6934E50C3DB36E89B127B8A622B120F6721")
        );
        $this->publicKey = $this->privateKey->publicKey();
    }

    public function testPublicKeyMatchesRfc()
    {
        \Test\assertTrue(
            $this->publicKey->point->x == gmp_init("0x60FED4BA255A9D31C961EB74C6356D68C049B8923B61FA6CE669622E60F29FB6"),
            "public key x mismatch"
        );
        \Test\assertTrue(
            $this->publicKey->point->y == gmp_init("0x7903FE1008B8BC99A41AE9E95628BC64F2F1B20C2D7E9F5177A3C294D4462299"),
            "public key y mismatch"
        );
    }

    public function testSampleMessageSignature()
    {
        $sig = Ecdsa::sign("sample", $this->privateKey);
        // r matches RFC 6979 A.2.5 exactly
        \Test\assertTrue(
            $sig->r == gmp_init("0xEFD48B2AACB6A8FD1140DD9CD45E81D69D2C877B56AAF991C34D0EA84EAF3716"),
            "r mismatch for 'sample'"
        );
        // s is low-S normalized: N - 0xF7CB1C942D657C41D436C7A1B6E29F65F3E900DBB9AFF4064DC4AB2F843ACDA8
        \Test\assertTrue(
            $sig->s == gmp_init("0x0834E36AD29A83BF2BC9385E491D6099C8FDF9D1ED67AA7EA5F51F93782857A9"),
            "s mismatch for 'sample'"
        );
        \Test\assertTrue(Ecdsa::verify("sample", $sig, $this->publicKey), "verify failed for 'sample'");
    }

    public function testTestMessageSignature()
    {
        $sig = Ecdsa::sign("test", $this->privateKey);
        // r matches RFC 6979 A.2.5 exactly
        \Test\assertTrue(
            $sig->r == gmp_init("0xF1ABB023518351CD71D881567B1EA663ED3EFCF6C5132B354F28D3B0B7D38367"),
            "r mismatch for 'test'"
        );
        // s already low-S, matches RFC directly
        \Test\assertTrue(
            $sig->s == gmp_init("0x019F4113742A2B14BD25926B49C649155F267E60D3814B4C0CC84250E46F0083"),
            "s mismatch for 'test'"
        );
        \Test\assertTrue(Ecdsa::verify("test", $sig, $this->publicKey), "verify failed for 'test'");
    }
}

$tests = new TestRfc6979KnownAnswer();
$tests->run();


// ============================================================================
// Secp256k1 Known Answer Tests
// ============================================================================
\Test\printHeader("Secp256k1 Known Answer");

class TestSecp256k1KnownAnswer extends TestCase
{
    private $privateKey;
    private $publicKey;

    function __construct()
    {
        $this->privateKey = new PrivateKey(CurveFp::$supportedCurves["secp256k1"], 1);
        $this->publicKey = $this->privateKey->publicKey();
    }

    public function testPublicKeyIsGenerator()
    {
        $secp256k1 = CurveFp::$supportedCurves["secp256k1"];
        \Test\assertTrue($this->publicKey->point->x == $secp256k1->G->x, "x mismatch");
        \Test\assertTrue($this->publicKey->point->y == $secp256k1->G->y, "y mismatch");
    }

    public function testSampleMessageSignature()
    {
        $sig = Ecdsa::sign("sample", $this->privateKey);
        \Test\assertTrue(
            $sig->r == gmp_init("0x58DB657BCD631038BEA07B4941172F0167ACA98F12B55E3176BD1C35435D6501"),
            "r mismatch"
        );
        \Test\assertTrue(
            $sig->s == gmp_init("0x3A78E73D8FF8AB554E13C10F6390D81A882F91945D6275493882676170B53A57"),
            "s mismatch"
        );
        \Test\assertTrue(Ecdsa::verify("sample", $sig, $this->publicKey), "verify failed");
    }

    public function testTestMessageSignature()
    {
        $sig = Ecdsa::sign("test", $this->privateKey);
        \Test\assertTrue(
            $sig->r == gmp_init("0x98DF3AAED18D1299109E9732E3015F7E68E5D1FDEAD6924809B410D970A3B0CE"),
            "r mismatch"
        );
        \Test\assertTrue(
            $sig->s == gmp_init("0x3EF15987C6592379BAAD6392586A382D63952572632FCD951AE75E7471C144C6"),
            "s mismatch"
        );
        \Test\assertTrue(Ecdsa::verify("test", $sig, $this->publicKey), "verify failed");
    }
}

$tests = new TestSecp256k1KnownAnswer();
$tests->run();


// ============================================================================
// Malleability Tests
// ============================================================================
\Test\printHeader("Malleability");

class TestMalleability extends TestCase
{
    public function testSignAlwaysProducesLowS()
    {
        $success = true;
        for ($i = 0; $i < 100; $i++) {
            $privateKey = new PrivateKey();
            $signature = Ecdsa::sign("test message", $privateKey);
            if ($signature->s > gmp_div_q($privateKey->curve->N, 2)) {
                $success = false;
                break;
            }
        }
        \Test\assertTrue($success, "found high-s signature");
    }

    public function testHighSSignatureStillVerifies()
    {
        $privateKey = new PrivateKey();
        $publicKey = $privateKey->publicKey();
        $message = "test message";

        $signature = Ecdsa::sign($message, $privateKey);
        $highS = new Signature($signature->r, $privateKey->curve->N - $signature->s);

        \Test\assertTrue(Ecdsa::verify($message, $signature, $publicKey), "low-s verify failed");
        \Test\assertTrue(Ecdsa::verify($message, $highS, $publicKey), "high-s verify failed");
    }
}

$tests = new TestMalleability();
$tests->run();


// ============================================================================
// Public Key Validation Tests
// ============================================================================
\Test\printHeader("Public Key Validation");

class TestPublicKeyValidation extends TestCase
{
    public function testRejectOffCurvePublicKey()
    {
        $privateKey = new PrivateKey();
        $publicKey = $privateKey->publicKey();
        $message = "test message";

        $signature = Ecdsa::sign($message, $privateKey);

        $offCurvePoint = new Point($publicKey->point->x, $publicKey->point->y + 1);
        $offCurveKey = new PublicKey($offCurvePoint, $publicKey->curve);

        \Test\assertFalse(Ecdsa::verify($message, $signature, $offCurveKey), "off-curve key should fail");
    }

    public function testFromStringRejectsOffCurvePoint()
    {
        $p = (new PrivateKey())->publicKey();
        $badY = str_pad(Binary::hexFromInt($p->point->y + 1), 2 * gmp_intval($p->curve->length()), "0", STR_PAD_LEFT);
        $badHex = str_pad(Binary::hexFromInt($p->point->x), 2 * gmp_intval($p->curve->length()), "0", STR_PAD_LEFT) . $badY;
        \Test\assertThrows(function() use ($badHex, $p) {
            PublicKey::fromString($badHex, $p->curve);
        });
    }

    public function testFromStringRejectsInfinityPoint()
    {
        $secp256k1 = CurveFp::$supportedCurves["secp256k1"];
        $zeroHex = str_repeat("00", 2 * gmp_intval($secp256k1->length()));
        \Test\assertThrows(function() use ($zeroHex, $secp256k1) {
            PublicKey::fromString($zeroHex, $secp256k1);
        });
    }
}

$tests = new TestPublicKeyValidation();
$tests->run();


// ============================================================================
// Forgery Attempt Tests
// ============================================================================
\Test\printHeader("Forgery Attempt");

class TestForgeryAttempt extends TestCase
{
    private $privateKey;
    private $publicKey;
    private $message;
    private $signature;

    function __construct()
    {
        $this->privateKey = new PrivateKey();
        $this->publicKey = $this->privateKey->publicKey();
        $this->message = "authentic message";
        $this->signature = Ecdsa::sign($this->message, $this->privateKey);
    }

    public function testRejectZeroSignature()
    {
        \Test\assertFalse(Ecdsa::verify($this->message, new Signature(0, 0), $this->publicKey));
    }

    public function testRejectREqualsZero()
    {
        \Test\assertFalse(Ecdsa::verify($this->message, new Signature(0, $this->signature->s), $this->publicKey));
    }

    public function testRejectSEqualsZero()
    {
        \Test\assertFalse(Ecdsa::verify($this->message, new Signature($this->signature->r, 0), $this->publicKey));
    }

    public function testRejectREqualsN()
    {
        $N = $this->publicKey->curve->N;
        \Test\assertFalse(Ecdsa::verify($this->message, new Signature($N, $this->signature->s), $this->publicKey));
    }

    public function testRejectSEqualsN()
    {
        $N = $this->publicKey->curve->N;
        \Test\assertFalse(Ecdsa::verify($this->message, new Signature($this->signature->r, $N), $this->publicKey));
    }

    public function testRejectRExceedsN()
    {
        $N = $this->publicKey->curve->N;
        \Test\assertFalse(Ecdsa::verify($this->message, new Signature($N + 1, $this->signature->s), $this->publicKey));
    }

    public function testRejectArbitrarySignature()
    {
        \Test\assertFalse(Ecdsa::verify($this->message, new Signature(1, 1), $this->publicKey));
    }

    public function testRejectBoundarySignature()
    {
        $N = $this->publicKey->curve->N;
        \Test\assertFalse(Ecdsa::verify($this->message, new Signature($N - 1, $N - 1), $this->publicKey));
    }

    public function testWrongKeyRejected()
    {
        $otherKey = (new PrivateKey())->publicKey();
        \Test\assertFalse(Ecdsa::verify($this->message, $this->signature, $otherKey));
    }
}

$tests = new TestForgeryAttempt();
$tests->run();


// ============================================================================
// RFC 6979 Tests
// ============================================================================
\Test\printHeader("RFC 6979");

class TestRfc6979 extends TestCase
{
    public function testDeterministicSignature()
    {
        $privateKey = new PrivateKey();
        $message = "test message";

        $signature1 = Ecdsa::sign($message, $privateKey);
        $signature2 = Ecdsa::sign($message, $privateKey);

        \Test\assertTrue($signature1->r == $signature2->r, "r not deterministic");
        \Test\assertTrue($signature1->s == $signature2->s, "s not deterministic");
    }

    public function testDifferentMessagesDifferentSignatures()
    {
        $privateKey = new PrivateKey();

        $signature1 = Ecdsa::sign("message 1", $privateKey);
        $signature2 = Ecdsa::sign("message 2", $privateKey);

        \Test\assertTrue($signature1->r != $signature2->r || $signature1->s != $signature2->s, "signatures should differ for different messages");
    }

    public function testDifferentKeysDifferentSignatures()
    {
        $message = "test message";

        $signature1 = Ecdsa::sign($message, new PrivateKey());
        $signature2 = Ecdsa::sign($message, new PrivateKey());

        \Test\assertTrue($signature1->r != $signature2->r || $signature1->s != $signature2->s, "signatures should differ for different keys");
    }
}

$tests = new TestRfc6979();
$tests->run();


// ============================================================================
// Edge Case Message Tests
// ============================================================================
\Test\printHeader("Edge Case Messages");

class TestEdgeCaseMessage extends TestCase
{
    private $privateKey;
    private $publicKey;

    function __construct()
    {
        $this->privateKey = new PrivateKey();
        $this->publicKey = $this->privateKey->publicKey();
    }

    private function signAndVerify($message)
    {
        $sig = Ecdsa::sign($message, $this->privateKey);
        \Test\assertTrue(Ecdsa::verify($message, $sig, $this->publicKey), "verify failed");
        \Test\assertFalse(Ecdsa::verify($message . "x", $sig, $this->publicKey), "tampered should fail");
    }

    public function testEmptyMessage()
    {
        $this->signAndVerify("");
    }

    public function testSingleCharMessage()
    {
        $this->signAndVerify("a");
    }

    public function testUnicodeMessage()
    {
        $this->signAndVerify("\xC3\xA9\xC3\xA8\xC3\xAA\xC3\xAB");
    }

    public function testEmojiMessage()
    {
        $this->signAndVerify("\xF0\x9F\x94\x92\xF0\x9F\x94\x91");
    }

    public function testNullByteMessage()
    {
        $this->signAndVerify("before\x00after");
    }

    public function testLongMessage()
    {
        $this->signAndVerify(str_repeat("a", 10000));
    }

    public function testNewlinesAndWhitespace()
    {
        $this->signAndVerify("  line1\n\tline2\r\n  ");
    }
}

$tests = new TestEdgeCaseMessage();
$tests->run();


// ============================================================================
// Serialization Round Trip Tests
// ============================================================================
\Test\printHeader("Serialization Round Trip");

class TestSerializationRoundTrip extends TestCase
{
    private $privateKey;
    private $publicKey;
    private $message;
    private $signature;

    function __construct()
    {
        $this->privateKey = new PrivateKey();
        $this->publicKey = $this->privateKey->publicKey();
        $this->message = "round-trip test";
        $this->signature = Ecdsa::sign($this->message, $this->privateKey);
    }

    public function testSignatureDerRoundTrip()
    {
        $der = $this->signature->toDer();
        $restored = Signature::fromDer($der);
        \Test\assertTrue($restored->r == $this->signature->r, "r mismatch");
        \Test\assertTrue($restored->s == $this->signature->s, "s mismatch");
        \Test\assertTrue(Ecdsa::verify($this->message, $restored, $this->publicKey), "verify failed");
    }

    public function testSignatureBase64RoundTrip()
    {
        $b64 = $this->signature->toBase64();
        $restored = Signature::fromBase64($b64);
        \Test\assertTrue($restored->r == $this->signature->r, "r mismatch");
        \Test\assertTrue($restored->s == $this->signature->s, "s mismatch");
        \Test\assertTrue(Ecdsa::verify($this->message, $restored, $this->publicKey), "verify failed");
    }

    public function testSignatureDerWithRecoveryIdRoundTrip()
    {
        $der = $this->signature->toDer(true);
        $restored = Signature::fromDer($der, true);
        \Test\assertTrue($restored->r == $this->signature->r, "r mismatch");
        \Test\assertTrue($restored->s == $this->signature->s, "s mismatch");
        \Test\assertTrue($restored->recoveryId == $this->signature->recoveryId, "recoveryId mismatch");
    }

    public function testPrivateKeyPemRoundTrip()
    {
        $pem = $this->privateKey->toPem();
        $restored = PrivateKey::fromPem($pem);
        \Test\assertTrue($restored->secret == $this->privateKey->secret, "secret mismatch");
        \Test\assertTrue($restored->curve->name == $this->privateKey->curve->name, "curve name mismatch");
    }

    public function testPrivateKeyDerRoundTrip()
    {
        $der = $this->privateKey->toDer();
        $restored = PrivateKey::fromDer($der);
        \Test\assertTrue($restored->secret == $this->privateKey->secret, "secret mismatch");
    }

    public function testPublicKeyPemRoundTrip()
    {
        $pem = $this->publicKey->toPem();
        $restored = PublicKey::fromPem($pem);
        \Test\assertTrue($restored->point->x == $this->publicKey->point->x, "x mismatch");
        \Test\assertTrue($restored->point->y == $this->publicKey->point->y, "y mismatch");
    }

    public function testPublicKeyCompressedRoundTrip()
    {
        $compressed = $this->publicKey->toCompressed();
        $restored = PublicKey::fromCompressed($compressed, $this->publicKey->curve);
        \Test\assertTrue($restored->point->x == $this->publicKey->point->x, "x mismatch");
        \Test\assertTrue($restored->point->y == $this->publicKey->point->y, "y mismatch");
        \Test\assertTrue(Ecdsa::verify($this->message, $this->signature, $restored), "verify failed");
    }

    public function testPublicKeyCompressedEvenAndOdd()
    {
        $success = true;
        for ($i = 0; $i < 20; $i++) {
            $pk = new PrivateKey();
            $pub = $pk->publicKey();
            $compressed = $pub->toCompressed();
            $restored = PublicKey::fromCompressed($compressed, $pub->curve);
            if ($restored->point->x != $pub->point->x || $restored->point->y != $pub->point->y) {
                $success = false;
                break;
            }
        }
        \Test\assertTrue($success, "compressed round-trip failed");
    }

    public function testPrime256v1KeyRoundTrip()
    {
        $pk = new PrivateKey(CurveFp::$supportedCurves["prime256v1"]);
        $pem = $pk->toPem();
        $restored = PrivateKey::fromPem($pem);
        \Test\assertTrue($restored->secret == $pk->secret, "secret mismatch");
        \Test\assertTrue($restored->curve->name == "prime256v1", "curve name mismatch");
    }
}

$tests = new TestSerializationRoundTrip();
$tests->run();


// ============================================================================
// Tonelli-Shanks Tests
// ============================================================================
\Test\printHeader("Tonelli-Shanks");

class TestTonelliShanks extends TestCase
{
    public function testPrimeCongruent1Mod4()
    {
        // P = 17: 17 - 1 = 16 = 2^4, S = 4, exercises full Tonelli-Shanks
        $P = gmp_init(17);
        $success = true;
        for ($value = 1; $value < 17; $value++) {
            $v = gmp_init($value);
            if (gmp_powm($v, gmp_div_q($P - 1, 2), $P) == 1) {
                $root = Math::modularSquareRoot($v, $P);
                if (gmp_mod($root * $root, $P) != $v) {
                    $success = false;
                    break;
                }
            }
        }
        \Test\assertTrue($success, "Tonelli-Shanks failed for P=17");
    }

    public function testPrimeCongruent5Mod8()
    {
        // P = 13: 13 - 1 = 12 = 3 * 2^2, S = 2
        $P = gmp_init(13);
        $success = true;
        for ($value = 1; $value < 13; $value++) {
            $v = gmp_init($value);
            if (gmp_powm($v, gmp_div_q($P - 1, 2), $P) == 1) {
                $root = Math::modularSquareRoot($v, $P);
                if (gmp_mod($root * $root, $P) != $v) {
                    $success = false;
                    break;
                }
            }
        }
        \Test\assertTrue($success, "Tonelli-Shanks failed for P=13");
    }

    public function testPrimeCongruent3Mod4()
    {
        // P = 7: fast path (S = 1)
        $P = gmp_init(7);
        $success = true;
        for ($value = 1; $value < 7; $value++) {
            $v = gmp_init($value);
            if (gmp_powm($v, gmp_div_q($P - 1, 2), $P) == 1) {
                $root = Math::modularSquareRoot($v, $P);
                if (gmp_mod($root * $root, $P) != $v) {
                    $success = false;
                    break;
                }
            }
        }
        \Test\assertTrue($success, "Tonelli-Shanks failed for P=7");
    }

    public function testZeroValue()
    {
        \Test\assertTrue(Math::modularSquareRoot(gmp_init(0), gmp_init(17)) == 0, "sqrt(0) should be 0");
    }
}

$tests = new TestTonelliShanks();
$tests->run();


// ============================================================================
// Hash Truncation Tests
// ============================================================================
\Test\printHeader("Hash Truncation");

class TestHashTruncation extends TestCase
{
    public function testSignVerifyWithSha512()
    {
        $privateKey = new PrivateKey();
        $publicKey = $privateKey->publicKey();
        $message = "test message";

        $signature = Ecdsa::sign($message, $privateKey, "sha512");

        \Test\assertTrue(Ecdsa::verify($message, $signature, $publicKey, "sha512"), "sha512 verify failed");
        \Test\assertFalse(Ecdsa::verify("wrong message", $signature, $publicKey, "sha512"), "wrong message should fail");
    }

    public function testSha512DeterministicSignature()
    {
        $privateKey = new PrivateKey();
        $message = "test message";

        $signature1 = Ecdsa::sign($message, $privateKey, "sha512");
        $signature2 = Ecdsa::sign($message, $privateKey, "sha512");

        \Test\assertTrue($signature1->r == $signature2->r, "r not deterministic");
        \Test\assertTrue($signature1->s == $signature2->s, "s not deterministic");
    }

    public function testHashMismatchFails()
    {
        $privateKey = new PrivateKey();
        $publicKey = $privateKey->publicKey();
        $message = "test message";

        $signature = Ecdsa::sign($message, $privateKey, "sha256");
        \Test\assertFalse(Ecdsa::verify($message, $signature, $publicKey, "sha512"), "hash mismatch should fail");
    }
}

$tests = new TestHashTruncation();
$tests->run();


// ============================================================================
// Prime256v1 Security Tests
// ============================================================================
\Test\printHeader("Prime256v1 Security");

class TestPrime256v1Security extends TestCase
{
    public function testSignVerify()
    {
        $prime256v1 = CurveFp::$supportedCurves["prime256v1"];
        $privateKey = new PrivateKey($prime256v1);
        $publicKey = $privateKey->publicKey();
        $message = "test message";

        $signature = Ecdsa::sign($message, $privateKey);

        \Test\assertTrue($signature->s <= gmp_div_q($prime256v1->N, 2), "s should be low");
        \Test\assertTrue(Ecdsa::verify($message, $signature, $publicKey), "verify failed");
    }

    public function testDeterministicSignature()
    {
        $prime256v1 = CurveFp::$supportedCurves["prime256v1"];
        $privateKey = new PrivateKey($prime256v1);
        $message = "test message";

        $signature1 = Ecdsa::sign($message, $privateKey);
        $signature2 = Ecdsa::sign($message, $privateKey);

        \Test\assertTrue($signature1->r == $signature2->r, "r not deterministic");
        \Test\assertTrue($signature1->s == $signature2->s, "s not deterministic");
    }

    public function testWrongCurveKeyFails()
    {
        $secp256k1 = CurveFp::$supportedCurves["secp256k1"];
        $prime256v1 = CurveFp::$supportedCurves["prime256v1"];
        $k1Key = new PrivateKey($secp256k1);
        $p256Key = new PrivateKey($prime256v1);
        $message = "cross-curve test";

        $sig = Ecdsa::sign($message, $k1Key);
        \Test\assertFalse(Ecdsa::verify($message, $sig, $p256Key->publicKey()), "cross-curve should fail");
    }
}

$tests = new TestPrime256v1Security();
$tests->run();
