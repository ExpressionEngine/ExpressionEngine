<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Fixed-lived MFA approval, independent of remembered password authentication.
 * Opt in with mfa_session_length (positive seconds); unset/zero disables reuse.
 * The window is checked only when authenticating again, not during activity.
 */
class Mfa_session
{
    private $cookie;
    private $token;

    /** Read the incoming credential without caching its approval. */
    public function __construct()
    {
        $this->cookie = 'mfa_session_' . (int) ee()->config->item('site_id');
        $token = ee()->input->cookie($this->cookie);
        $this->token = is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token) ? $token : false;
    }

    /**
     * Issue approval only after a code was verified against this member snapshot.
     * The issuing login must still exist and the credentials must be unchanged.
     *
     * @param \ExpressionEngine\Model\Member\Member $member Verified member snapshot.
     * @param string $session_id The login session which completed verification.
     * @return bool Whether a new approval was persisted.
     */
    public function create($member, $session_id)
    {
        $length = $this->session_length();
        if (!$length || !$member->member_id || $member->enable_mfa !== true || !$session_id) {
            return false;
        }

        $now = ee()->localize->now;
        $token = bin2hex(random_bytes(32));
        $approvals = ee()->db->dbprefix('mfa_sessions');
        $sessions = ee()->db->dbprefix('sessions');
        $members = ee()->db->dbprefix('members');
        $created = ee()->db->query("INSERT INTO {$approvals}
            (token_hash, member_id, site_id, verified_at, expires_at, mfa_login_id, credential_hash)
            SELECT ?, m.member_id, ?, ?, ?,
                COALESCE(NULLIF(s.mfa_login_id, ''), SHA2(s.session_id, 256)),
                SHA2(CONCAT(m.password, ':', m.unique_id, ':', m.backup_mfa_code), 256)
            FROM {$members} m JOIN {$sessions} s ON s.member_id = m.member_id
            WHERE m.member_id = ? AND s.session_id = ? AND s.mfa_flag = 'skip'
                AND m.enable_mfa = 'y' AND BINARY m.password = ?
                AND BINARY m.unique_id = ? AND BINARY m.backup_mfa_code = ?", [
                hash('sha256', $token), ee()->config->item('site_id'), $now, $now + $length,
                $member->member_id, $session_id, $member->password,
                $member->unique_id, $member->backup_mfa_code
            ]);

        if (!$created || ee()->db->affected_rows() !== 1) {
            return false;
        }

        // Replace only the incoming approval. Concurrent successful verifications
        // each need a usable token regardless of response order. Logout still
        // revokes all approvals created by this login via mfa_login_id.
        if ($this->token) {
            ee()->db->delete('mfa_sessions', [
                'member_id' => $member->member_id,
                'site_id' => ee()->config->item('site_id'),
                'token_hash' => hash('sha256', $this->token)
            ]);
        }
        ee()->db->delete('mfa_sessions', ['expires_at <=' => $now]);
        $this->token = $token;
        ee()->input->set_cookie($this->cookie, $token, $length);

        return true;
    }

    /**
     * Restore MFA at an authentication boundary, never on ordinary activity.
     * Approval, current credentials and revocation are checked in one write.
     * Credential binding also covers the gap before the member revocation hook.
     *
     * @param int $member_id The authenticated member.
     * @param string $session_id The new password or remembered-login session.
     * @return string|false Stable login identity, or false when approval failed.
     */
    public function restore($member_id, $session_id)
    {
        $length = $this->session_length();
        if (!$length || !$this->token || !$member_id || !$session_id) {
            return false;
        }

        $now = ee()->localize->now;
        $approvals = ee()->db->dbprefix('mfa_sessions');
        $sessions = ee()->db->dbprefix('sessions');
        $members = ee()->db->dbprefix('members');
        $updated = ee()->db->query("UPDATE {$sessions} s
            JOIN {$approvals} a ON a.member_id = s.member_id
            JOIN {$members} m ON m.member_id = a.member_id
            SET s.mfa_flag = 'skip', s.mfa_login_id = a.mfa_login_id
            WHERE s.session_id = ? AND s.member_id = ? AND s.mfa_flag = 'show'
                AND a.token_hash = ? AND a.site_id = ?
                AND a.expires_at > ? AND a.verified_at <= ? AND a.verified_at > ?
                AND m.enable_mfa = 'y'
                AND a.credential_hash = SHA2(CONCAT(m.password, ':', m.unique_id, ':', m.backup_mfa_code), 256)", [
                $session_id, $member_id, hash('sha256', $this->token), ee()->config->item('site_id'),
                $now, $now, $now - $length
            ]);

        if (!$updated || ee()->db->affected_rows() !== 1) {
            return false;
        }

        // Keep the in-memory session attached to the same login as the approval.
        // A concurrent logout may already have removed it after the UPDATE.
        return ee()->db->select('mfa_login_id')
            ->get_where('sessions', ['session_id' => $session_id])->row('mfa_login_id') ?: false;
    }

    /**
     * Delete the login and its MFA approvals, including after session expiry.
     * Live sessions and approvals are removed atomically to prevent reuse during logout.
     *
     * @param int $member_id The member being logged out, or zero for a guest.
     * @param string $session_id The login session being destroyed.
     * @param string|null $login_id Stable login identity across session ID rotation.
     * @return void
     */
    public function delete($member_id, $session_id, $login_id = null)
    {
        if ($login_id) {
            // A concurrent request may already have rotated the browser ID.
            // Delete the login and its MFA approval in the same write so a
            // concurrent restore cannot reuse approval between the deletions.
            $sessions = ee()->db->dbprefix('sessions');
            $hashes = ee()->db->dbprefix('security_hashes');
            $approvals = ee()->db->dbprefix('mfa_sessions');
            ee()->db->query("DELETE s, h, a FROM {$sessions} s
                LEFT JOIN {$hashes} h ON h.session_id = s.session_id
                LEFT JOIN {$approvals} a ON a.member_id = s.member_id
                    AND a.mfa_login_id = s.mfa_login_id
                WHERE s.member_id = ? AND s.mfa_login_id = ?", [
                    $member_id, $login_id
                ]);
        } elseif ($session_id) {
            ee()->db->where('session_id', $session_id);
            ee()->db->delete(array('sessions', 'security_hashes'));
        }

        if ($login_id || ee()->db->table_exists('mfa_sessions')) {
            $token_hash = $this->token ? hash('sha256', $this->token) : '';
            // The approval can outlive its issuing login. Recover that login's
            // identity so logout also removes overlapping approvals after expiry.
            $approval = $this->token ? ee()->db->select('member_id, mfa_login_id')
                ->get_where('mfa_sessions', ['token_hash' => $token_hash])->row_array() : [];
            ee()->db->where('token_hash', $token_hash)
                ->or_start_group()
                    ->where('member_id', (int) $member_id)
                    ->where('mfa_login_id', $login_id ?: hash('sha256', (string) $session_id))
                ->end_group()
                ->or_start_group()
                    ->where('member_id', $approval['member_id'] ?? 0)
                    ->where('mfa_login_id', $approval['mfa_login_id'] ?? '')
                ->end_group()
                ->delete('mfa_sessions');
        }
        $this->token = false;
        ee()->input->delete_cookie($this->cookie);
    }

    /**
     * Credential changes revoke approvals across all browsers and sites.
     *
     * @param int $member_id The member whose credentials changed.
     * @return void
     */
    public function revoke_member($member_id)
    {
        // Member updates can also occur before this table's installer step.
        if (ee()->db->table_exists('mfa_sessions')) {
            ee()->db->delete('mfa_sessions', ['member_id' => (int) $member_id]);
        }
    }

    /** @return int Positive reuse window in seconds, or zero when disabled. */
    private function session_length()
    {
        $length = ee()->config->item('mfa_session_length');
        $enabled = ee()->config->item('enable_mfa');
        $session_type = ee()->config->item(REQ == 'CP' ? 'cp_session_type' : 'website_session_type');
        if (($enabled !== false && $enabled !== 'y') || $session_type === 's'
            || (!is_int($length) && !is_string($length))) {
            return 0;
        }

        return filter_var($length, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1,
            'max_range' => PHP_INT_MAX - ee()->localize->now]]) ?: 0;
    }
}
