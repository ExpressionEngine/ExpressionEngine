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
 * Core Remember Me
 */
class Remember
{
    private $max_per_site = 5;			// remembers per site per user
    private $gc_probability = 10;		// percentage of logins that gc

    protected $table = 'remember_me';

    protected $data = null;
    protected $cookie = 'remember';
    protected $cookie_value = false;
    protected $expiry = 1209600;		// default expiration of two weeks, in seconds (60*60*24*14)

    protected $ip_address = '';
    protected $user_agent = '';

    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct($params = array())
    {
        $this->cookie_value = ee()->input->cookie($this->cookie);
        $this->expiry = (isset($params['remember_me_ttl'])) ? $params['remember_me_ttl'] : $this->expiry;

        $this->ip_address = ee()->input->ip_address();
        $this->user_agent = substr(ee()->input->user_agent(), 0, 120);
    }

    /**
     * Create a new remembered login without inheriting MFA verification.
     *
     * @return void
     */
    public function create()
    {
        // this is a good time to check how many they have
        $active = ee()->db
            ->order_by('last_refresh', 'ASC')
            ->get_where($this->table, array(
                'member_id' => ee()->session->userdata('member_id'),
                'site_id' => ee()->config->item('site_id')
            ))
            ->result();

        $this->cookie_value = $this->_generate_id();

        $this->data = array(
            'remember_me_id' => $this->cookie_value,
            'member_id' => ee()->session->userdata('member_id'),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'admin_sess' => ee()->session->userdata('admin_sess'),
            'site_id' => ee()->config->item('site_id'),
            'expiration' => ee()->localize->now + $this->expiry,
            'last_refresh' => ee()->localize->now,
            'mfa_verified_at' => 0
        );

        ee()->db->set($this->data);

        // If they have too many remembered sessions,
        // we replace their oldest one.
        if (count($active) >= $this->max_per_site) {
            ee()->db->where('remember_me_id', $active[0]->remember_me_id);
            ee()->db->update($this->table);
        } else {
            ee()->db->insert($this->table);
            $this->_garbage_collect();
        }

        $this->_set_cookie($this->data['remember_me_id'], $this->expiry);
    }

    /**
     * Check if a remember me cookie + valid data exists
     *
     * @return void
     */
    public function exists()
    {
        if ($this->data === null) {
            $this->data = array();

            return $this->_validate_db();
        }

        return count($this->data);
    }

    /**
     * Remember me data accessor
     *
     * @return void
     */
    public function data($key)
    {
        return (isset($this->data[$key])) ? $this->data[$key] : null;
    }

    /**
     * Remember successful MFA for the current member and site.
     *
     * @param int $member_id The member whose one-time code was verified.
     * @return void
     */
    public function remember_mfa($member_id)
    {
        if (!$this->_mfa_ttl() || !$this->_matches_mfa_member($member_id)) {
            return;
        }

        // A token issued before MFA must not gain verification in place: a copy
        // of that token could otherwise inherit the member's successful code.
        $id = $this->_generate_id();
        $verified_at = ee()->localize->now;

        ee()->db->where('remember_me_id', $this->cookie_value)
            ->update($this->table, [
                'remember_me_id' => $id,
                'mfa_verified_at' => $verified_at
            ]);

        // Another request may have revoked or rotated the token after validation.
        if (ee()->db->affected_rows() !== 1) {
            $this->data['mfa_verified_at'] = 0;
            return;
        }

        $this->data['remember_me_id'] = $id;
        $this->data['mfa_verified_at'] = $verified_at;
        $this->_set_cookie($id, $this->data('expiration') - $verified_at);
    }

    /**
     * Check whether a remembered login can reuse its MFA verification.
     *
     * @param int $member_id The member whose session is being restored.
     * @return bool
     */
    public function is_mfa_verified($member_id)
    {
        $ttl = $this->_mfa_ttl();

        if (!$ttl || !$this->_matches_mfa_member($member_id)) {
            return false;
        }

        $verified_at = (int) $this->data('mfa_verified_at');
        $now = ee()->localize->now;

        return $verified_at > 0 && $verified_at <= $now && ($now - $verified_at) < $ttl;
    }

    /**
     * Revoke remembered MFA after a member's authentication settings change.
     *
     * @param int $member_id The member whose remembered logins must be cleared.
     * @return void
     */
    public function clear_mfa($member_id)
    {
        ee()->db->where('member_id', $member_id)
            ->update($this->table, ['mfa_verified_at' => 0]);

        if ((int) $this->data('member_id') === (int) $member_id) {
            $this->data['mfa_verified_at'] = 0;
        }
    }

    /**
     * Get the configured MFA reuse period, treating invalid values as disabled.
     *
     * @return int
     */
    protected function _mfa_ttl()
    {
        $ttl = ee()->config->item('mfa_remember_ttl');

        if (!is_int($ttl) && !is_string($ttl)) {
            return 0;
        }

        return filter_var($ttl, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
    }

    /**
     * Require a valid remembered login belonging to this member and site.
     *
     * @param int $member_id The member being authenticated.
     * @return bool
     */
    protected function _matches_mfa_member($member_id)
    {
        return $member_id > 0
            && $this->exists()
            && (int) $this->data('member_id') === (int) $member_id
            && (int) $this->data('site_id') === (int) ee()->config->item('site_id')
            && $this->data('expiration') > ee()->localize->now;
    }

    /**
     * Clear the current remember me
     *
     * @return void
     */
    public function delete()
    {
        if ($this->cookie_value) {
            ee()->db->where('remember_me_id', $this->cookie_value);
            ee()->db->delete($this->table);
        }

        $this->data = array();
        $this->_delete_cookie();
    }

    /**
     * Clear all remember me's except for the current one
     *
     * Used when changing passwords to disable old
     * remember me's that may have been created with
     * compromised credentials
     *
     * @param	int		$member_id	the member ID to clear other remember me's for
     * @return	void
     */
    public function delete_others($member_id = null)
    {
        $member_id = ($member_id) ?: ee()->session->userdata('member_id');

        ee()->db->where('member_id', $member_id);

        if ($this->cookie_value) {
            ee()->db->where('remember_me_id !=', $this->cookie_value);
        }

        ee()->db->delete($this->table);
    }

    /**
     * Refresh the remembered login without extending its MFA verification.
     *
     * @return void
     */
    public function refresh()
    {
        if (! $this->exists()) {
            return;
        }

        $yesterday = ee()->localize->now - 60 * 60 * 24;

        if ($this->data['last_refresh'] < $yesterday) {
            $id = $this->_generate_id();

            // push the expiration date ahead by as much as we've lost
            $adjust_expire = ee()->localize->now - $this->data['last_refresh'];

            $this->data['last_refresh'] = ee()->localize->now;
            $this->data['remember_me_id'] = $id;
            $this->data['expiration'] += $adjust_expire;

            // Write only rotating fields so a concurrent MFA revocation stays cleared.
            ee()->db->where('remember_me_id', $this->cookie_value)
                ->set([
                    'last_refresh' => $this->data['last_refresh'],
                    'remember_me_id' => $this->data['remember_me_id'],
                    'expiration' => $this->data['expiration']
                ])
                ->update($this->table);

            $expiration = $this->data['expiration'] - ee()->localize->now;
            $this->_set_cookie($id, $expiration);
        }
    }

    /**
     * Expiry getter
     *
     * @return int
     */
    public function get_expiry()
    {
        return $this->expiry;
    }

    /**
     * Get the remember me data in the db and validate it
     *
     * @return bool
     */
    protected function _validate_db()
    {
        if (! $this->cookie_value) {
            return false;
        }

        // grab the db entry
        $rem_q = ee()->db->get_where($this->table, array(
            'remember_me_id' => $this->cookie_value
        ));

        if ($rem_q->num_rows() != 1) {
            $this->_delete_cookie();

            return false;
        }

        $rem_data = $rem_q->row_array();
        $rem_q->free_result();

        // validate browser markers
        if ($this->user_agent != $rem_data['user_agent']) {
            $this->_delete_cookie();

            return false;
        }

        // validate time
        if ($rem_data['expiration'] < ee()->localize->now) {
            $this->_delete_cookie();

            return false;
        }

        // remember the data we grabbed (haha!)
        $this->data = $rem_data;

        return true;
    }

    /**
     * Generates a unique id
     *
     * @return string	random 40 character string
     */
    protected function _generate_id()
    {
        return ee()->functions->random();
    }

    /**
     * Delete the remember me cookie
     *
     * @return void
     */
    protected function _delete_cookie()
    {
        $this->cookie_value = false;
        ee()->input->delete_cookie($this->cookie);
    }

    /**
     * Set the remember me cookie
     *
     * @return void
     */
    protected function _set_cookie($value, $expiration)
    {
        $this->cookie_value = $value;
        ee()->input->set_cookie($this->cookie, $value, $expiration);
    }

    /**
     * Garbage collect
     *
     * @return void
     */
    protected function _garbage_collect()
    {
        srand(time());

        if ((rand() % 100) < $this->gc_probability) {
            $expired = ee()->localize->now - $this->expiry;

            ee()->db->where('expiration <', ee()->localize->now)
                ->or_where('last_refresh <', $expired)
                ->delete($this->table);
        }
    }
}

// END Remember class

// EOF
