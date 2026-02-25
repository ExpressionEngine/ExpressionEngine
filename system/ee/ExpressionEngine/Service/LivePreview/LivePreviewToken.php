<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\LivePreview;

use ExpressionEngine\Library\Security\SignedToken;
use ExpressionEngine\Service\Permission\Permission;

/**
 * Signed token helper for Live Preview requests.
 */
class LivePreviewToken
{
    /**
     * Signed token helper instance.
     */
    private $token;
    /**
     * Session service delegate for resolving session state.
     */
    private $session_delegate;
    /**
     * Default token TTL in seconds.
     */
    private $ttl = 600;
    /**
     * Purpose binding for tokens.
     */
    private $purpose = 'live_preview';

    /**
     * @param mixed $session_delegate Session service providing userdata().
     * @param mixed|null $signer Optional signer delegate implementing sign().
     * @param string|null $key Signing key override.
     */
    public function __construct($session_delegate, $signer = null, ?string $key = null)
    {
        $this->session_delegate = $session_delegate;
        $key = $key ?: $this->resolveKey();

        $this->token = new SignedToken($key, [
            'purpose' => $this->purpose,
            'ttl' => $this->ttl,
            'algo' => 'sha256',
            'signer' => $signer
        ]);
    }

    /**
     * Issue a signed token bound to the Live Preview context.
     */
    public function issue(
        int $member_id,
        int $channel_id,
        ?int $entry_id,
        ?string $origin = null,
        ?string $return = null,
        ?int $ttl = null,
        ?int $site_id = null
    ): string {
        $site_id = $site_id ?? (int) ee()->config->item('site_id');
        $session_id = (string) $this->session_delegate->userdata('session_id');

        $origin_hash = $this->hashValue($this->normalizeOrigin($origin));
        $return_hash = $this->hashValue($this->normalizeReturn($return));

        $claims = [
            'member_id' => $member_id,
            'channel_id' => $channel_id,
            'entry_id' => $entry_id,
            'site_id' => $site_id
        ];

        if (!empty($session_id)) {
            $claims['session_id'] = $session_id;
        }
        if (!empty($origin_hash)) {
            $claims['origin_hash'] = $origin_hash;
        }
        if (!empty($return_hash)) {
            $claims['return_hash'] = $return_hash;
        }

        return $this->token->issue($claims, $ttl);
    }

    /**
     * Issue a token from request-provided bindings.
     *
     * @param string|null $from_param Base64-url encoded origin or raw origin (when $encoded=false).
     * @param string|null $return_param Base64-url encoded return or raw return (when $encoded=false).
     * @param bool $encoded Whether $from_param/$return_param are encoded.
     */
    public function issueFromRequest(
        int $member_id,
        int $channel_id,
        ?int $entry_id,
        ?string $from_param,
        ?string $return_param,
        ?int $ttl = null,
        ?int $site_id = null,
        bool $encoded = true
    ): string {
        $origin = $this->decodeBinding($from_param, $encoded);
        $return = $this->decodeBinding($return_param, $encoded);

        return $this->issue(
            $member_id,
            $channel_id,
            $entry_id,
            $origin,
            $return,
            $ttl,
            $site_id
        );
    }

    /**
     * Validate a token against channel/entry/site and request bindings.
     *
     * @return array|null
     */
    public function validate(
        ?string $token,
        int $channel_id,
        ?int $entry_id,
        ?string $origin = null,
        ?string $return = null,
        ?int $site_id = null
    ): ?array {
        if (empty($token)) {
            return null;
        }

        $site_id = $site_id ?? (int) ee()->config->item('site_id');
        $meta = $this->token->validate($token);
        if (!is_array($meta)) {
            return null;
        }

        $meta_channel_id = (int) ($meta['channel_id'] ?? 0);
        $meta_entry_id = $meta['entry_id'] ?? null;
        $meta_site_id = (int) ($meta['site_id'] ?? 0);

        if ($meta_site_id !== $site_id) {
            return null;
        }
        if ($meta_channel_id !== $channel_id) {
            return null;
        }
        if (!empty($meta_entry_id)) {
            if (empty($entry_id) || (int) $meta_entry_id !== (int) $entry_id) {
                return null;
            }
        } elseif (!empty($entry_id)) {
            return null;
        }

        if (!$this->validateOriginBinding($meta, $origin)) {
            return null;
        }
        if (!$this->validateReturnBinding($meta, $return)) {
            return null;
        }
        if (!$this->validateSessionBinding($meta)) {
            return null;
        }

        return $meta;
    }

    /**
     * Validate token and resolve member/permission context.
     *
     * @return array|null ['member_id' => int, 'permission' => Permission]
     */
    public function validateAndResolveMember(
        ?string $token,
        int $channel_id,
        ?int $entry_id,
        ?string $origin = null,
        ?string $return = null,
        ?int $site_id = null
    ): ?array {
        $meta = $this->validate($token, $channel_id, $entry_id, $origin, $return, $site_id);
        if (!is_array($meta)) {
            return null;
        }

        $session_member_id = (int) ee()->session->userdata('member_id');
        $token_member_id = (int) ($meta['member_id'] ?? 0);
        if ($session_member_id && $token_member_id && $session_member_id !== $token_member_id) {
            return null;
        }

        $member_id = $session_member_id ?: $token_member_id;
        if (empty($member_id)) {
            return null;
        }

        $permission = ee('Permission', $meta['site_id'] ?? null);
        if ($session_member_id !== $member_id) {
            $member = ee('Model')->get('Member', $member_id)->with('Roles')->first();
            if (empty($member)) {
                return null;
            }
            $permission = new Permission(
                ee('Model'),
                [],
                $member->getPermissions(),
                $member->Roles->getDictionary('role_id', 'name'),
                $meta['site_id'] ?? ee()->config->item('site_id')
            );
        }

        return [
            'member_id' => $member_id,
            'permission' => $permission
        ];
    }

    /**
     * Resolve the signing key for Live Preview tokens.
     */
    private function resolveKey(): string
    {
        $key = ee()->config->item('live_preview_key');
        if (empty($key)) {
            $key = ee()->config->item('encryption_key');
        }

        return (string) $key;
    }

    /**
     * Normalize an origin-like string to scheme://host[:port].
     */
    private function normalizeOrigin(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);
        if (strpos($value, '//') === 0) {
            $value = (ee('Request')->isEncrypted() ? 'https:' : 'http:') . $value;
        } elseif (!preg_match('#^https?://#i', $value)) {
            $value = (ee('Request')->isEncrypted() ? 'https://' : 'http://') . $value;
        }

        $parts = parse_url($value);
        if (!$parts || empty($parts['host'])) {
            return null;
        }

        $scheme = !empty($parts['scheme']) ? strtolower($parts['scheme']) : (ee('Request')->isEncrypted() ? 'https' : 'http');
        $host = strtolower($parts['host']);
        if (strpos($host, ':') !== false && strpos($host, '[') !== 0) {
            $host = '[' . $host . ']';
        }
        if (!empty($parts['port'])) {
            $host .= ':' . $parts['port'];
        }

        return $scheme . '://' . $host;
    }

    /**
     * Normalize return value for binding.
     */
    private function normalizeReturn(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * Decode request-bound values when base64-encoded.
     */
    private function decodeBinding(?string $value, bool $encoded): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! $encoded) {
            $value = trim($value);

            return $value !== '' ? $value : null;
        }

        $decoded = base64_decode(rawurldecode($value));
        if ($decoded === false) {
            return null;
        }

        $decoded = trim($decoded);

        return $decoded !== '' ? $decoded : null;
    }

    /**
     * Hash a binding value using SHA-256.
     */
    private function hashValue(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return hash('sha256', $value);
    }

    /**
     * Validate origin binding if present in token.
     */
    private function validateOriginBinding(array $meta, ?string $origin): bool
    {
        if (empty($meta['origin_hash'])) {
            return true;
        }

        $normalized = $this->normalizeOrigin($origin);
        if (empty($normalized)) {
            return false;
        }

        $expected = $this->hashValue($normalized);

        return $this->hashEquals($expected, (string) $meta['origin_hash']);
    }

    /**
     * Validate return binding if present in token.
     */
    private function validateReturnBinding(array $meta, ?string $return): bool
    {
        if (empty($meta['return_hash'])) {
            return true;
        }

        $normalized = $this->normalizeReturn($return);
        if (empty($normalized)) {
            return false;
        }

        $expected = $this->hashValue($normalized);

        return $this->hashEquals($expected, (string) $meta['return_hash']);
    }

    /**
     * Validate session binding when possible.
     *
     * If a session is available, require a matching session_id claim.
     */
    private function validateSessionBinding(array $meta): bool
    {
        $current_session = (string) $this->session_delegate->userdata('session_id');
        $token_session = (string) ($meta['session_id'] ?? '');
        $has_cookie = isset($this->session_delegate->cookies_exist) ? (bool) $this->session_delegate->cookies_exist : !empty($current_session);

        if (!$has_cookie) {
            return true;
        }

        if (!empty($current_session) && empty($token_session)) {
            return false;
        }

        if (!empty($current_session) && !empty($token_session) && $current_session !== $token_session) {
            return false;
        }

        return true;
    }

    /**
     * Constant-time string compare.
     */
    private function hashEquals(?string $expected, ?string $actual): bool
    {
        if ($expected === null || $actual === null) {
            return false;
        }

        if (function_exists('hash_equals')) {
            return hash_equals($expected, $actual);
        }

        return $expected === $actual;
    }
}
