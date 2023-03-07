## A lightweight and fast PHP ECDSA

### Overview

This is a pure PHP implementation of the Elliptic Curve Digital Signature Algorithm. It is compatible with OpenSSL and uses elegant math such as Jacobian Coordinates to spped up the ECDSA on pure PHP.

### Installation

#### Composer

To install the package with Composer, run:

```sh
composer require starkbank/ecdsa
```

To use the bindings, use Composer's autoload:

```sh
require_once('vendor/autoload.php');
```

#### External dependencies
The package makes use of the 'GNU Multiple Precision' (GMP) library. For installation details, see: https://www.php.net/manual/en/gmp.installation.php

### Curves

We currently support `secp256k1`, but you can add more curves to your project. You just need to use the `Curve::add()` method.

### Speed

We ran a test on a Macbook Air M1 2020 using PHP 8.1. We ran the library 100 times and got the average time displayed bellow:

| Library            | sign          | verify  |
| ------------------ |:-------------:| -------:|
| starkbank-ecdsa    |     1.9ms     |  3.7ms  |

### Sample Code

How to sign a json message for [Stark Bank]:

```php

# Generate privateKey from PEM string
$privateKey = EllipticCurve\PrivateKey::fromPem("
    -----BEGIN EC PARAMETERS-----
    BgUrgQQACg==
    -----END EC PARAMETERS-----
    -----BEGIN EC PRIVATE KEY-----
    MHQCAQEEIODvZuS34wFbt0X53+P5EnSj6tMjfVK01dD1dgDH02RzoAcGBSuBBAAK
    oUQDQgAE/nvHu/SQQaos9TUljQsUuKI15Zr5SabPrbwtbfT/408rkVVzq8vAisbB
    RmpeRREXj5aog/Mq8RrdYy75W9q/Ig==
    -----END EC PRIVATE KEY-----
");


# Create message from json
$message = array(
    "transfers" => array(
        array(
            "amount" => 100000000,
            "taxId" => "594.739.480-42",
            "name" => "Daenerys Targaryen Stormborn",
            "bankCode" => "341",
            "branchCode" => "2201",
            "accountNumber" => "76543-8",
            "tags" => array("daenerys", "targaryen", "transfer-1-external-id")
        )
    )
);

$message = json_encode($message, JSON_PRETTY_PRINT);

$signature = EllipticCurve\Ecdsa::sign($message, $privateKey);

# Generate Signature in base64. This result can be sent to Stark Bank in header as Digital-Signature parameter
echo "\n" . $signature->toBase64();

# To double check if message matches the signature
$publicKey = $privateKey->publicKey();

echo "\n" . EllipticCurve\Ecdsa::verify($message, $signature, $publicKey);

```

Simple use:

```php

# Generate new Keys
$privateKey = new EllipticCurve\PrivateKey;
$publicKey = $privateKey->publicKey();

$message = "My test message";

# Generate Signature
$signature = EllipticCurve\Ecdsa::sign($message, $privateKey);

# Verify if signature is valid
echo "\n" . EllipticCurve\Ecdsa::verify($message, $signature, $publicKey);

```

How to add more curves:

```php
$newCurve = new EllipticCurve\Curve(
    "0xf1fd178c0b3ad58f10126de8ce42435b3961adbcabc8ca6de8fcf353d86e9c00",
    "0xee353fca5428a9300d4aba754a44c00fdfec0c9ae4b1a1803075ed967b7bb73f",
    "0xf1fd178c0b3ad58f10126de8ce42435b3961adbcabc8ca6de8fcf353d86e9c03",
    "0xf1fd178c0b3ad58f10126de8ce42435b53dc67e140d2bf941ffdd459c6d655e1",
    "0xb6b3d4c356c139eb31183d4749d423958c27d2dcaf98b70164c97a2dd98f5cff",
    "0x6142e0f7c8b204911f9271f0f3ecef8c2701c307e8e4c9e183115a1554062cfb",
    "frp256v1",
    array(1, 2, 250, 1, 223, 101, 256, 1)
);

EllipticCurve\Curve::add($newCurve);

$publicKeyPem = "-----BEGIN PUBLIC KEY-----
MFswFQYHKoZIzj0CAQYKKoF6AYFfZYIAAQNCAATeEFFYiQL+HmDYTf+QDmvQmWGD
dRJPqLj11do8okvkSxq2lwB6Ct4aITMlCyg3f1msafc/ROSN/Vgj69bDhZK6
-----END PUBLIC KEY-----";

$publicKey = EllipticCurve\PublicKey::fromPem($publicKeyPem);

print_r($publicKey->toPem());

```

### OpenSSL

This library is compatible with OpenSSL, so you can use it to generate keys:

```
openssl ecparam -name secp256k1 -genkey -out privateKey.pem
openssl ec -in privateKey.pem -pubout -out publicKey.pem
```

Create a message.txt file and sign it:

```
openssl dgst -sha256 -sign privateKey.pem -out signatureDer.txt message.txt
```

It's time to verify:

```php

$publicKeyPem = EllipticCurve\Utils\File::read("publicKey.pem");
$signatureDer = EllipticCurve\Utils\File::read("signatureDer.txt");
$message = EllipticCurve\Utils\File::read("message.txt");

$publicKey = EllipticCurve\PublicKey::fromPem($publicKeyPem);
$signature = EllipticCurve\Signature::fromDer($signatureDer);

echo "\n" . EllipticCurve\Ecdsa::verify($message, $signature, $publicKey);

```

You can also verify it on terminal:

```
openssl dgst -sha256 -verify publicKey.pem -signature signatureDer.txt message.txt
```

NOTE: If you want to create a Digital Signature to use in the [Stark Bank], you need to convert the binary signature to base64.

```
openssl base64 -in signatureDer.txt -out signatureBase64.txt
```

You can also verify it with this library:

```php
$signatureDer = EllipticCurve\Utils\File::read("signatureDer.txt");

$signature = EllipticCurve\Signature::fromDer($signatureDer);

echo "\n" . $signature->toBase64();
```

[Stark Bank]: https://starkbank.com

### Run all unit tests

```sh
php tests/test.php
```

[python-ecdsa]: https://github.com/warner/python-ecdsa
