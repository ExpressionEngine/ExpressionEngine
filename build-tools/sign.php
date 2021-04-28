<?php

if (!isset($argv[1])) {
    echo 'No data to sign';
}

if (!getenv('RELEASE_KEY_PASSWORD') || !getenv('RELEASE_KEY')) {
    echo 'Signing key is missing';
}

$data = $argv[1];
$private_key = openssl_pkey_get_private(getenv('RELEASE_KEY'), getenv('RELEASE_KEY_PASSWORD'));
$signature = null;

openssl_sign($data, $signature, $private_key, OPENSSL_ALGO_SHA384);

echo base64_encode($signature);
