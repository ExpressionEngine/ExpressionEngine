<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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
     * @var SignedToken
     */
    private $token;

    /**
     * @var mixed Session service delegate for resolving session state
     */
    private $session_delegate;

    /**
     * @var int Default token TTL
     */
    private $ttl = 600;

    /**
     * @var string Purpose binding
     */
    private $purpose = 'live_preview';

    /**
     * @param mixed $session_delegate Session service providing userdata()
     * @param mixed $signer Optional signer delegate implementing sign()
     * @param string|null $key Signing key override
     */
    public function __construct($session_delegate, $signer = null, $key = null)
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
     * Issue a signed token bound to live preview context.
     *
     * @param int $member_id
     * @param int $channel_id
     * @param int|null $entry_id
     * @param string|null $origin
     * @param string|null $return
     * @param int|null $ttl
     * @param int|null $site_id
     * @return string
     */
    public function issue(
        $member_id,
        $channel_id,
        $entry_id,
        $origin = null,
        $return = null,
        $ttl = null,
        $site_id = null
    ) {
        $site_id = $site_id ?? (int) ee()->config->item('site_id');
        $session_id = (string) $this->session_delegate->userdata('session_id');

        $origin_hash = $this->hashValue($this->normalizeOrigin($origin));
        $return_hash = $this->hashValue($this->normalizeReturn($return));

        $claims = [
            'member_id' => (int) $member_id,
            'channel_id' => (int) $channel_id,
            'entry_id' => $entry_id,
            'site_id' => (int) $site_id
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
     * Issue from request-bound values.
     *
     * @param int $member_id
     * @param int $channel_id
     * @param int|null $entry_id
     * @param string|null $from_param Encoded or raw origin binding
     * @param string|null $return_param Encoded or raw return binding
     * @param int|null $ttl
     * @param int|null $site_id
     * @param bool $encoded Whether the params are base64-url encoded
     * @return string
     */
    public function issueFromRequest(
        $member_id,
        $channel_id,
        $entry_id,
        $from_param,
        $return_param,
        $ttl = null,
        $site_id = null,
        $encoded = true
    ) {
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
     * @param string|null $token
     * @param int $channel_id
     * @param int|null $entry_id
     * @param string|null $origin
     * @param string|null $return
     * @param int|null $site_id
     * @return array|null
     */
    public function validate(
        $token,
        $channel_id,
        $entry_id,
        $origin = null,
        $return = null,
        $site_id = null
    ) {
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

        if ($meta_site_id !== (int) $site_id) {
            return null;
        }
        if ($meta_channel_id !== (int) $channel_id) {
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
     * @param string|null $token
     * @param int $channel_id
     * @param int|null $entry_id
     * @param string|null $origin
     * @param string|null $return
     * @param int|null $site_id
     * @return array|null ['member_id' => int, 'permission' => Permission]
     */
    public function validateAndResolveMember(
        $token,
        $channel_id,
        $entry_id,
        $origin = null,
        $return = null,
        $site_id = null
    ) {
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
     * Resolve the signing key for live preview tokens.
     *
     * @return string
     */
    private function resolveKey()
    {
        $key = ee()->config->item('live_preview_key');
        if (empty($key)) {
            $key = ee()->config->item('encryption_key');
        }

        return (string) $key;
    }

    /**
     * Normalize an origin-like value to scheme://host[:port].
     *
     * @param string|null $value
     * @return string|null
     */
    private function normalizeOrigin($value)
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
        if (!empty($parts['port'])) {
            $host .= ':' . $parts['port'];
        }

        return $scheme . '://' . $host;
    }

    /**
     * Normalize return binding value.
     *
     * @param string|null $value
     * @return string|null
     */
    private function normalizeReturn($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * Decode request-bound values when base64-url encoded.
     *
     * @param string|null $value
     * @param bool $encoded
     * @return string|null
     */
    private function decodeBinding($value, $encoded)
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
     * Hash a binding value with SHA-256.
     *
     * @param string|null $value
     * @return string|null
     */
    private function hashValue($value)
    {
        if (empty($value)) {
            return null;
        }

        return hash('sha256', $value);
    }

    /**
     * Validate origin binding when present in token.
     *
     * @param array $meta
     * @param string|null $origin
     * @return bool
     */
    private function validateOriginBinding(array $meta, $origin)
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
     * Validate return binding when present in token.
     *
     * @param array $meta
     * @param string|null $return
     * @return bool
     */
    private function validateReturnBinding(array $meta, $return)
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
     * @param array $meta
     * @return bool
     */
    private function validateSessionBinding(array $meta)
    {
        $current_session = (string) $this->session_delegate->userdata('session_id');
        $token_session = (string) ($meta['session_id'] ?? '');
        $has_cookie = isset($this->session_delegate->cookies_exist)
            ? (bool) $this->session_delegate->cookies_exist
            : !empty($current_session);

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
     * Constant-time string comparison.
     *
     * @param string|null $expected
     * @param string|null $actual
     * @return bool
     */
    private function hashEquals($expected, $actual)
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
