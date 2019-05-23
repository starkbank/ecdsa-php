## How to use ECDSA signature with built-in PHP OpenSSL bindings

### Sign messages

PHP has the function [`openssl_get_privatekey`](openssl_get_privatekey) that can load ECDSA private keys the PEM string or file link.

```php
<?php
// Load from file link
$privateKeyString = openssl_get_privatekey("file://privateKey.pem");

// Load from PEM string
$privateKeyString =
"-----BEGIN EC PARAMETERS-----
BgUrgQQACg==
-----END EC PARAMETERS-----
-----BEGIN EC PRIVATE KEY-----
MHQCAQEEIHI6VMaMwvRag0foPp87+nhby3QrftcEsBHee6sdr0aZoAcGBSuBBAAK
oUQDQgAE91vCtp7tO4FyJbpgSS824PiuLR7LPNdwt+rcIe0uE19RUJz2Jgm8tRRD
HmBVzoQXNxcwVD1HfRMtU0wnUJOuAQ==
-----END EC PRIVATE KEY-----";

$privateKey = openssl_get_privatekey($privateKeyString);

?>
```

To create a signature for the message, use the function [`openssl_sign`](openssl_sign) with SHA256 as hash algorithm.  
For the Stark Bank APIs that use ECDSA signature, the signature needs to be sent in base64 instead of binary DER format.


```php
<?php
$alg = OPENSSL_ALGO_SHA256;
$signature = null;

if (openssl_sign($message, $signature, $privateKey, $alg)) {
    $signature = base64_encode($signature); // Send the data in base64

    echo "Signature: " . $signature . "\n";
} else {
    echo "Failed to sign message: " . openssl_error_string() . "\n";
}
?>
```

### Verify signature

Similarly to the private key, PHP has the function [`openssl_get_publickey`](openssl_get_publickey) for public keys.

```php
<?php
// Load from file link
$publicKeyString = openssl_get_publickey("file://publicKey.pem");

// Load from PEM string
$publicKeyString =
"-----BEGIN PUBLIC KEY-----
MFYwEAYHKoZIzj0CAQYFK4EEAAoDQgAE91vCtp7tO4FyJbpgSS824PiuLR7LPNdw
t+rcIe0uE19RUJz2Jgm8tRRDHmBVzoQXNxcwVD1HfRMtU0wnUJOuAQ==
-----END PUBLIC KEY-----";

$publicKey = openssl_get_publickey($publicKeyString);

?>
```

To verify the message signature, use the function [`openssl_verify`](`openssl_verify`) with SHA256 hash algorithm.  
Whenever you receive a signed message from Stark Bank systems, the signature is sent in base64.

```php
<?php
$alg = OPENSSL_ALGO_SHA256;
$success = openssl_verify($message, base64_decode($signature), $publicKey, $alg);

if ($success === -1) {
    echo "openssl_verify() failed with error.  " . openssl_error_string() . "\n";
} elseif ($success === 1) {
    echo "Signature verification was successful!\n";
} else {
    echo "Signature verification failed.  Incorrect key or data has been tampered with\n";
}
?>
```

### Complete example

You can find a complete example of signing and verifying in the file [`signKey.php`](/signKey.php)


[openssl_get_privatekey]: https://www.php.net/manual/en/function.openssl-get-privatekey.php
[openssl_sign]: https://www.php.net/manual/en/function.openssl-sign.php
[openssl_get_publickey]: https://www.php.net/manual/en/function.openssl-get-publickey.php
[openssl_verify]: https://www.php.net/manual/en/function.openssl-verify.php
