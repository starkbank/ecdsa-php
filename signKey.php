<?php

// Use SHA256 hash algorithm
$alg = OPENSSL_ALGO_SHA256;
$privateKey =
"-----BEGIN EC PARAMETERS-----
BgUrgQQACg==
-----END EC PARAMETERS-----
-----BEGIN EC PRIVATE KEY-----
MHQCAQEEIHI6VMaMwvRag0foPp87+nhby3QrftcEsBHee6sdr0aZoAcGBSuBBAAK
oUQDQgAE91vCtp7tO4FyJbpgSS824PiuLR7LPNdwt+rcIe0uE19RUJz2Jgm8tRRD
HmBVzoQXNxcwVD1HfRMtU0wnUJOuAQ==
-----END EC PRIVATE KEY-----";
// Both string and file link works the same
// $privateKey = openssl_get_privatekey("file://privateKey.pem");
$privateKey = openssl_get_privatekey($privateKey);

$message = array(
  "transaction" => array(
		"externalId" => "0b4df2f43d694d98a816b16298ee9174",
		"tags" => array("fornecedores", "war", "lannister", "1/2"),
		"description" => "A lannister always pays his debts. Wall War 1/2"
	),
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

echo "Message:\n" . $message . "\n";


// Sign the message
$signature = null;

if (openssl_sign($message, $signature, $privateKey, $alg)) {
    $signature = base64_encode($signature); // Send the data in base64

    echo "Signature: " . $signature . "\n";
} else {
    echo "Failed to sign message: " . openssl_error_string() . "\n";
    exit;
}

$publicKey =
"-----BEGIN PUBLIC KEY-----
MFYwEAYHKoZIzj0CAQYFK4EEAAoDQgAE91vCtp7tO4FyJbpgSS824PiuLR7LPNdw
t+rcIe0uE19RUJz2Jgm8tRRDHmBVzoQXNxcwVD1HfRMtU0wnUJOuAQ==
-----END PUBLIC KEY-----";
// Both string and file link works the same
// $publicKey = openssl_get_publickey("file://publicKey.pem");
$publicKey = openssl_get_publickey($publicKey);

// Verify signature.
$success = openssl_verify($message, base64_decode($signature), $publicKey, $alg);

if ($success === -1) {
    echo "openssl_verify() failed with error.  " . openssl_error_string() . "\n";
} elseif ($success === 1) {
    echo "Signature verification was successful!\n";
} else {
    echo "Signature verification failed.  Incorrect key or data has been tampered with\n";
}
?>
