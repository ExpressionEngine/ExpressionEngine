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
    private $cookie = 'mfa_session';
    private $token;

    /** Read the incoming credential without caching its approval. */
    public function __construct()
    {
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
            (token_hash, member_id, site_id, verified_at, expires_at, issued_session_hash)
            SELECT ?, m.member_id, ?, ?, ?, ?
            FROM {$members} m JOIN {$sessions} s ON s.member_id = m.member_id
            WHERE m.member_id = ? AND s.session_id = ? AND s.mfa_flag = 'skip'
                AND m.enable_mfa = 'y' AND m.password = ?
                AND m.unique_id = ? AND m.backup_mfa_code = ?", [
                hash('sha256', $token), ee()->config->item('site_id'), $now, $now + $length,
                hash('sha256', $session_id), $member->member_id, $session_id, $member->password,
                $member->unique_id, $member->backup_mfa_code
            ]);

        if (!$created || ee()->db->affected_rows() !== 1) {
            return false;
        }

        // Replace both the previous cookie and any approval issued by an
        // overlapping verification of this login. Old tokens never gain trust.
        ee()->db->query("DELETE FROM {$approvals} WHERE member_id = ? AND site_id = ?
            AND (token_hash = ? OR issued_session_hash = ?) AND token_hash != ?", [
                $member->member_id, ee()->config->item('site_id'),
                $this->token ? hash('sha256', $this->token) : '',
                hash('sha256', $session_id), hash('sha256', $token)
            ]);
        ee()->db->query("DELETE FROM {$approvals} WHERE expires_at <= ?", [$now]);
        $this->token = $token;
        ee()->input->set_cookie($this->cookie, $token, $length);

        return true;
    }

    /**
     * Restore MFA at an authentication boundary, never on ordinary activity.
     * Approval and revocation are checked in the same write, without a cache.
     *
     * @param int $member_id The authenticated member.
     * @param string $session_id The new password or remembered-login session.
     * @return bool Whether MFA approval was persisted on that session.
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
        $updated = ee()->db->query("UPDATE {$sessions} SET mfa_flag = 'skip'
            WHERE session_id = ? AND member_id = ? AND mfa_flag = 'show'
            AND EXISTS (
                SELECT 1 FROM {$approvals} a JOIN {$members} m ON m.member_id = a.member_id
                WHERE a.token_hash = ? AND a.member_id = ? AND a.site_id = ?
                    AND a.expires_at > ? AND a.verified_at <= ? AND a.verified_at > ?
                    AND m.enable_mfa = 'y'
            )", [
                $session_id, $member_id,
                hash('sha256', $this->token), $member_id, ee()->config->item('site_id'),
                $now, $now, $now - $length
            ]);

        return $updated && ee()->db->affected_rows() === 1;
    }

    /**
     * Called after deleting the login session. Also remove approvals issued by
     * an overlapping verification request which used the previous cookie.
     *
     * @param int $member_id The member being logged out, or zero for a guest.
     * @param string $session_id The login session being destroyed.
     * @return void
     */
    public function delete($member_id, $session_id)
    {
        if (ee()->db->table_exists('mfa_sessions')) {
            $table = ee()->db->dbprefix('mfa_sessions');
            ee()->db->query("DELETE FROM {$table}
                WHERE token_hash = ? OR (member_id = ? AND issued_session_hash = ?)", [
                    $this->token ? hash('sha256', $this->token) : '', (int) $member_id, hash('sha256', (string) $session_id)
                ]);
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
            $table = ee()->db->dbprefix('mfa_sessions');
            ee()->db->query("DELETE FROM {$table} WHERE member_id = ?", [(int) $member_id]);
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
