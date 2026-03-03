<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC
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
     * Signing key.
     */
    private $key;
    /**
     * Optional purpose/audience string to bind tokens to a use-case.
     */
    private $purpose;
    /**
     * Default TTL in seconds.
     */
    private $ttl;
    /**
     * Hashing algorithm used for HMAC signing.
     */
    private $algo;
    /**
     * Clock skew allowance in seconds for iat/exp checks.
     */
    private $clock_skew;
    /**
     * Optional signer delegate implementing sign($data, $key, $algo).
     */
    private $signer;

    /**
     * @param string $key Signing key.
     * @param array $options Optional settings: purpose, ttl, algo, clock_skew, signer.
     */
    public function __construct(string $key, array $options = [])
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
     * @param array $claims Arbitrary payload data.
     * @param int|null $ttl Override TTL in seconds.
     * @param int|null $issued_at Override issued at timestamp.
     */
    public function issue(array $claims, ?int $ttl = null, ?int $issued_at = null): string
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
     * Validate a signed token and return claims or null if invalid/expired.
     *
     * @param string|null $token
     * @return array|null
     */
    public function validate(?string $token): ?array
    {
        if (empty($token)) {
            return null;
        }

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload_encoded, $signature] = $parts;
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
     * Generate HMAC signature for the payload.
     */
    private function sign(string $payload): string
    {
        if ($this->signer && method_exists($this->signer, 'sign')) {
            return $this->signer->sign($payload, $this->key, $this->algo);
        }

        return hash_hmac($this->algo, $payload, $this->key);
    }

    /**
     * Verify HMAC signature in constant-time when possible.
     */
    private function verify(string $payload, string $signature): bool
    {
        $expected = $this->sign($payload);

        if (function_exists('hash_equals')) {
            return hash_equals($expected, $signature);
        }

        return $expected === $signature;
    }

    /**
     * URL-safe base64 encode.
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * URL-safe base64 decode.
     *
     * @return string|false
     */
    private function base64UrlDecode(string $data)
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
     */
    private function now(): int
    {
        if (function_exists('ee') && is_object(ee()) && isset(ee()->localize->now)) {
            return (int) ee()->localize->now;
        }

        return time();
    }
}
