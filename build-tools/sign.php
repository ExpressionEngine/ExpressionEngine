<?php

if (!isset($argv[1])) {
    echo 'No data to sign';
}

if (!getenv('RELEASE_PRIVATE_KEY')) {
    echo 'Signing key is missing';
}

if (!getenv('RELEASE_KEY_PASSWORD')) {
    echo 'Signing key password is missing';
}

$data = $argv[1];
$private_key = openssl_pkey_get_private(getenv('RELEASE_PRIVATE_KEY'), getenv('RELEASE_KEY_PASSWORD'));
$signature = null;

openssl_sign($data, $signature, $private_key, OPENSSL_ALGO_SHA384);

echo base64_encode($signature);
