<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Security;

/**
 * Signed token helper for short-lived, tamper-evident payloads.
 *
 * Tokens are composed as: base64url(payload) . '.' . signature
 */
class SignedToken
{
    /**
     * @var string Signing key
     */
    private $key;

    /**
     * @var string|null Optional purpose/audience binding
     */
    private $purpose;

    /**
     * @var int Default token TTL in seconds
     */
    private $ttl;

    /**
     * @var string Hashing algorithm used for signing
     */
    private $algo;

    /**
     * @var int Allowed clock skew for iat/exp checks
     */
    private $clock_skew;

    /**
     * @var mixed Optional signer delegate implementing sign($data, $key, $algo)
     */
    private $signer;

    /**
     * @param string $key
     * @param array $options Optional settings: purpose, ttl, algo, clock_skew, signer
     */
    public function __construct($key, array $options = [])
    {
        $this->key = $key;
        $this->purpose = $options['purpose'] ?? null;
        $this->ttl = $options['ttl'] ?? 600;
        $this->algo = $options['algo'] ?? 'sha256';
        $this->clock_skew = $options['clock_skew'] ?? 30;
        $this->signer = $options['signer'] ?? null;
    }

    /**
     * Issue a signed token for the provided claims.
     *
     * @param array $claims Arbitrary payload claims
     * @param int|null $ttl Override TTL in seconds
     * @param int|null $issued_at Override issued-at timestamp
     * @return string
     */
    public function issue(array $claims, $ttl = null, $issued_at = null)
    {
        $now = $issued_at ?? $this->now();
        $ttl = $ttl ?? $this->ttl;

        $claims['iat'] = $now;
        $claims['exp'] = $now + $ttl;
        if (!empty($this->purpose)) {
            $claims['purpose'] = $this->purpose;
        }

        $payload = json_encode($claims, JSON_UNESCAPED_SLASHES);
        $payload_encoded = $this->base64UrlEncode($payload);
        $signature = $this->sign($payload_encoded);

        return $payload_encoded . '.' . $signature;
    }

    /**
     * Validate a token and return claims or null when invalid.
     *
     * @param string|null $token
     * @return array|null
     */
    public function validate($token)
    {
        if (empty($token)) {
            return null;
        }

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        list($payload_encoded, $signature) = $parts;
        if (!$this->verify($payload_encoded, $signature)) {
            return null;
        }

        $payload = $this->base64UrlDecode($payload_encoded);
        if ($payload === false) {
            return null;
        }

        $claims = json_decode($payload, true);
        if (!is_array($claims)) {
            return null;
        }

        if (!empty($this->purpose) && ($claims['purpose'] ?? null) !== $this->purpose) {
            return null;
        }

        $now = $this->now();
        $exp = (int) ($claims['exp'] ?? 0);
        if ($exp && ($exp + $this->clock_skew) < $now) {
            return null;
        }

        $iat = (int) ($claims['iat'] ?? 0);
        if ($iat && ($iat - $this->clock_skew) > $now) {
            return null;
        }

        return $claims;
    }

    /**
     * Generate HMAC signature for payload.
     *
     * @param string $payload
     * @return string
     */
    private function sign($payload)
    {
        if ($this->signer && method_exists($this->signer, 'sign')) {
            return $this->signer->sign($payload, $this->key, $this->algo);
        }

        return hash_hmac($this->algo, $payload, $this->key);
    }

    /**
     * Verify HMAC signature in constant time where possible.
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    private function verify($payload, $signature)
    {
        $expected = $this->sign($payload);

        if (function_exists('hash_equals')) {
            return hash_equals($expected, $signature);
        }

        return $expected === $signature;
    }

    /**
     * URL-safe base64 encode.
     *
     * @param string $data
     * @return string
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * URL-safe base64 decode.
     *
     * @param string $data
     * @return string|false
     */
    private function base64UrlDecode($data)
    {
        $data = strtr($data, '-_', '+/');
        $padding = strlen($data) % 4;
        if ($padding) {
            $data .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($data);
    }

    /**
     * Resolve current timestamp.
     *
     * @return int
     */
    private function now()
    {
        if (function_exists('ee') && is_object(ee()) && isset(ee()->localize->now)) {
            return (int) ee()->localize->now;
        }

        return time();
    }
}
