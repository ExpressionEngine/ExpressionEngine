<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Core Session
 *
 * ExpressionEngine User Classes (* = current):
 *
 *   1. Session*
 *   2. Authentication
 *   3. Permissions
 *
 * There are three validation types, set in the config file:
 *
 *   1. User cookies AND session ID (cs)
 *
 * 	This is the most secure way to run a site. A session cookie is set
 * 	with a random ID, and a browser fingerprint is added to the URL.
 *
 * 	The cookie expires when you have been inactive longer than two
 * 	hours (one hour in the control panel). The fingerprint in the url will be
 * 	lost when you close the browser.
 *
 * 	Using this setting does NOT allow 'stay logged-in' capability, as each
 * 	session has a finite lifespan.
 *
 *   2. Cookies only - (c)
 *
 * 	With this validation type, a session ID string is not added to the url.
 * 	Therefore users can remain permanently logged in if they choose the
 * 	remember me option. This will set a second cookie that expires in a year.
 *
 * 	This setting is obviously less secure because it does not provide a safety
 * 	net if you share your computer or access your site from a public computer.
 * 	It relies solely on the session_id/remember_me cookies. You must log out.
 *
 *   3. Session ID only (s).
 *
 * 	Most compatible as it does not rely on cookies at all. Instead, only the
 * 	URL query string ID is used.
 *
 * 	No stay-logged in capability. The session will expire after one hour of
 * 	inactivity, so in terms of security, it is preferable to number 2.
 *
 * 	NOTE: The control panel and public pages can each have their own
 * 	      session preference.
 */
class EE_Session
{
    public $user_session_len = 7200;  // User sessions expire in two hours
    public $cpan_session_len = 3600;  // Admin sessions expire in one hour
    public $valid_session_types = array('cs', 'c', 's');

    public $c_session = 'sessionid';
    public $c_anon = 'anon';
    public $c_prefix = '';

    public $sdata = array();
    public $userdata = array();
    public $tracker = array();
    public $flashdata = array();

    public $sess_crypt_key = '';

    public $cookie_ttl = '';
    protected $activity_cookie_ttl = 31536000; // Activity cookie expiration:  One year

    public $session_length = '';
    public $validation_type = '';

    public $access_cp = false;
    public $cookies_exist = false;
    public $session_exists = false;

    public $mfa_flag = 'skip';

    public $validation;

    // Garbage collection probability. Used to kill expired sessions.
    public $gc_probability = 5;

    // Store data for just this page load.
    // Multi-dimensional array with module/class name,
    // e.g. $this->cache['module']['var_name']
    // Use set_cache() and cache() methods.
    public $cache = array();

    private $session_model = null;
    private $member_model = null;

    /**
     * Session Class Constructor
     */
    public function __construct()
    {
        // If they load this library manually we need to make sure our
        // dependencies are all here. This can happen in the cp_js_end hook.
        ee()->load->library('remember');
        ee()->load->library('localize');

        if (ee()->config->item('website_session_length')) {
            $this->user_session_len = ee()->config->item('website_session_length');
        }

        if (ee()->config->item('cp_session_length')) {
            $this->cpan_session_len = ee()->config->item('cp_session_length');
        }

        $this->session_length = $this->_setup_session_length();

        $this->cookie_ttl = $this->_setup_cookie_ttl();

        $this->sess_crypt_key = ee()->config->item('session_crypt_key')
            ?: ee()->config->item('encryption_key');

        // Set Default Session Values
        // Set USER-DATA as GUEST until proven otherwise
        $this->_initialize_userdata();

        // Set SESSION data as GUEST until proven otherwise
        $this->_initialize_session();

        // -------------------------------------------
        // 'sessions_start' hook.
        //  - Reset any session class variable
        //  - Override the whole session check
        //  - Modify default/guest settings
        //
        ee()->extensions->call('sessions_start', $this);
        if (ee()->extensions->end_script === true) {
            return;
        }
        //
        // -------------------------------------------

        // Set the validation type
        $this->validation = (REQ == 'CP') ? ee()->config->item('cp_session_type') : ee()->config->item('website_session_type');

        // default to "cookies and sessions" if validation type doesn't exist or is invalid
        if (! in_array($this->validation, $this->valid_session_types)) {
            $this->validation = 'cs';
        }

        // Grab the session ID and update browser fingerprint based on the validation type
        // we use the same URL key whether it's getting the session ID or the browser fingerprint,
        // simplifying URI parsing and complicating session hijacking attempts
        switch ($this->validation) {
            case 's':
                $this->sdata['session_id'] = (ee()->input->get('S')) ? ee()->input->get('S') : ee()->uri->session_id;

                break;
            case 'c':
                $this->sdata['session_id'] = ee()->input->cookie($this->c_session);

                break;
            case 'cs':
            default:
                $this->sdata['session_id'] = ee()->input->cookie($this->c_session);
                $this->sdata['fingerprint'] = (ee()->input->get('S')) ? ee()->input->get('S') : ee()->uri->session_id;

                break;
        }

        // Check remember me
        $remembered = (bool) ee()->remember->exists();

        // Did we find a session ID?
        $session_id = ($this->sdata['session_id'] != '' or ($this->validation == 'c' && $remembered)) ? true : false;

        // Fetch Session Data
        // IMPORTANT: The session data must be fetched before the member data so don't move this.
        if ($session_id === true && $this->fetch_session_data() === true) {
            $this->session_exists = true;
        }

        $member_exists = (bool) $this->fetch_member_data();

        // Update/Create Session and fetch member data
        if ($session_id === false or $member_exists === false) {
            $this->fetch_guest_data();
        } else {
            if ($this->session_exists === true) {
                $this->update_session();
            } else {
                if ($this->validation == 'c') {
                    $this->create_new_session($this->userdata['member_id']);
                } else {
                    $this->fetch_guest_data();
                }
            }
        }

        // Fetch "tracker" cookie
        if (REQ != 'CP') {
            $this->tracker = $this->tracker();
        }

        // Kill old sessions
        $this->delete_old_sessions();

        // Merge Session and User Data Arrays
        // We merge these into into one array for portability
        $this->userdata = array_merge($this->userdata, $this->sdata);

        // -------------------------------------------
        // 'sessions_end' hook.
        //  - Modify the user's session/member data.
        //  - Additional Session or Login methods (ex: log in to other system)
        //
        ee()->extensions->call('sessions_end', $this);
        if (ee()->extensions->end_script === true) {
            return;
        }
        //
        // -------------------------------------------

        // Garbage collection

        unset($this->sdata);
        unset($session_id);
        unset($rememebered);
        unset($member_exists);
    }

    public function setSessionCookies()
    {
        if (bool_config_item('forum_is_installed')) {
            ee()->input->set_cookie('last_visit', $this->userdata['last_visit'], $this->activity_cookie_ttl);
            ee()->input->set_cookie('last_activity', ee()->localize->now, $this->activity_cookie_ttl);
        }

        // Update session ID cookie
        if ($this->session_exists === true && $this->validation != 's') {
            ee()->input->set_cookie($this->c_session, $this->userdata['session_id'], $this->cookie_ttl);
        }

        if (REQ == 'PAGE') {
            $this->set_tracker_cookie($this->tracker);
        }

        $this->_prep_flashdata();
        ee()->remember->refresh();
    }

    public function getSessionModel()
    {
        return $this->session_model;
    }

    /**
     * Fetch all session data
     *
     * @return	array
     */
    public function all_userdata()
    {
        return $this->userdata;
    }

    /**
     * Check for banned data
     */
    public function ban_check($type = 'ip', $match = '')
    {
        switch ($type) {
            case 'ip':
                $ban = ee()->config->item('banned_ips');
                $match = ee()->input->ip_address();

                break;
            case 'email':
                $ban = ee()->config->item('banned_emails');

                break;
            case 'username':
                $ban = ee()->config->item('banned_usernames');

                break;
            case 'screen_name':
                $ban = ee()->config->item('banned_screen_names');

                break;
        }

        if ($ban == '') {
            return false;
        }

        $match = (string) $match;
        foreach (explode('|', $ban) as $val) {
            if ($val == '*') {
                continue;
            }

            if (substr($val, -1) == '*') {
                $val = str_replace('*', '', $val);

                if (strncmp($match, $val, strlen($val)) == 0) {
                    return true;
                }
            } elseif (strncmp($val, '*', 1) == 0) {
                $val = str_replace('*', '', $val);

                if (substr($match, - strlen($val)) == $val) {
                    return true;
                }
            } elseif ($val == $match) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Session Cache
     *
     * This method extracts a value from the session cache.
     *
     * @param 	string 	Super Class/Unique Identifier
     * @param 	string 	Key to extract from the cache.
     * @param 	mixed 	Default value to return if key doesn't exist
     * @return 	mixed
     */
    public function cache($class, $key, $default = false)
    {
        return (isset($this->cache[$class][$key])) ? $this->cache[$class][$key] : $default;
    }

    /**
     * Check password lockout
     */
    public function check_password_lockout($username = '')
    {
        if (ee()->config->item('password_lockout') == 'n' or
            ee()->config->item('password_lockout_interval') == '') {
            return false;
        }

        $interval = ee()->config->item('password_lockout_interval') * 60;

        $lockout = ee()->db->select("COUNT(*) as count")
            ->where('login_date > ', time() - $interval)
            ->where('ip_address', ee()->input->ip_address())
            ->where('username', $username)
            ->get('password_lockout');

        return ($lockout->row('count') >= 4) ? true : false;
    }

    /**
     * Create New Session
     *
     * @param 	int 		member_id
     * @param 	boolean		admin session or not
     * @param 	boolen 		can this session see front-end debugging?
     * @return 	string 		Session ID
     */
    public function create_new_session($member_id, $admin_session = false, $can_debug = false)
    {
        if (! is_object($this->member_model) || $this->member_model->member_id != $member_id) {
            $this->_setupMemberModel($member_id);
        }

        if ($this->access_cp == true or $this->member_model->can('access_cp')) {
            $this->sdata['admin_sess'] = 1;
        } else {
            $this->sdata['admin_sess'] = ($admin_session == false) ? 0 : 1;
        }

        $crypt_key = $this->member_model->crypt_key;

        // Create crypt key for member if one doesn't exist
        if (empty($crypt_key)) {
            $crypt_key = ee('Encrypt')->generateKey();
            ee()->db->update(
                'members',
                array('crypt_key' => $crypt_key),
                array('member_id' => $member_id)
            );
        }

        $this->sdata['session_id'] = ee()->functions->random();
        $this->sdata['ip_address'] = ee()->input->ip_address();
        $this->sdata['user_agent'] = substr(ee()->input->user_agent(), 0, 120);
        $this->sdata['member_id'] = (int) $member_id;
        $this->sdata['last_activity'] = ee()->localize->now;
        $this->sdata['sess_start'] = $this->sdata['last_activity'];
        $this->sdata['fingerprint'] = $this->_create_fingerprint((string) $crypt_key);
        $this->sdata['can_debug'] = ($can_debug) ? 'y' : 'n';
        $this->sdata['mfa_flag'] = ($this->member_model->enable_mfa === true) ? 'show' : 'skip';

        $this->userdata['member_id'] = (int) $member_id;
        $this->userdata['role_id'] = (int) $this->member_model->role_id;
        $this->userdata['session_id'] = $this->sdata['session_id'];
        $this->userdata['fingerprint'] = $this->sdata['fingerprint'];
        $this->userdata['site_id'] = ee()->config->item('site_id');
        $this->userdata['mfa_enabled'] = $this->member_model->enable_mfa;

        // Set the session cookie, ONLY if this method is not called from the context of the constructor, i.e. a login action
        if (isset(ee()->session)) {
            ee()->input->set_cookie($this->c_session, $this->userdata['session_id'], $this->cookie_ttl);
        }

        ee()->db->query(ee()->db->insert_string('exp_sessions', $this->sdata));

        $this->session_exists = true;

        return $this->sdata['session_id'];
    }

    /**
     * Delete old sessions if probability is met
     *
     * By default, the probability is set to 5 percent.
     * That means sessions will only be deleted one
     * out of ten times a page is loaded.
     */
    public function delete_old_sessions()
    {
        srand(time());

        if ((rand() % 100) < $this->gc_probability) {
            $sessions_to_delete = [];
            $cp_expire = ee()->localize->now - $this->cpan_session_len;
            $user_expire = ee()->localize->now - $this->user_session_len;

            // Get only the CP sessions that have expired.
            $admin_results = ee()->db->select('session_id')
                ->where('admin_sess', 1)
                ->where('last_activity < ', $cp_expire)
                ->get('sessions');

            // Loop through the old sessions and delete their session and security_hash data.
            foreach ($admin_results->result() as $row) {
                $sessions_to_delete[] = $row->session_id;
            }

            // Get only the user sessions that have expired.
            $user_results = ee()->db->select('session_id')
                ->where('admin_sess', 0)
                ->where('last_activity < ', $user_expire)
                ->get('sessions');

            // Loop through the old sessions and delete their session and security_hash data.
            foreach ($user_results->result() as $row) {
                $sessions_to_delete[] = $row->session_id;
            }

            // Free the memory from this query as it's at the start of our execution loop.
            $admin_results->free_result();
            $user_results->free_result();

            if (!empty($sessions_to_delete)) {
                ee()->db->where_in('session_id', $sessions_to_delete);
                ee()->db->delete(array('security_hashes', 'sessions'));
            }
        }
    }

    /**
     * Delete old password lockout data
     */
    public function delete_password_lockout()
    {
        if (ee()->config->item('password_lockout') == 'n') {
            return false;
        }

        $interval = (int) ee()->config->item('password_lockout_interval') * 60;

        $expire = time() - $interval;

        srand(time());

        if ((rand() % 100) < $this->gc_probability) {
            ee()->db->where('login_date <', $expire)
                ->delete('password_lockout');
        }
    }

    /**
     * Lock the control panel
     *
     * This logs the user out of the cp, but keeps their frontend session
     * active. We do this when we trigger the CP idle modal to prevent
     * tampering.
     */
    public function lock_cp()
    {
        if (ee()->session->userdata('admin_sess') == 0) {
            return;
        }

        ee()->db->set('admin_sess', 0)
            ->where('session_id', $this->userdata['session_id'])
            ->update('sessions');
    }

    /**
     * Destroy session. Essentially logging a user off.
     */
    public function destroy()
    {
        if ($this->userdata['session_id'] === 0) {
            // just to be sure
            $this->fetch_guest_data();

            return;
        }

        ee()->db->where('session_id', $this->userdata['session_id']);
        ee()->db->delete(array('sessions', 'security_hashes'));

        // Really should redirect after calling this
        // method, but if someone doesn't - we're safe
        $this->fetch_guest_data();

        ee()->remember->delete();
        ee()->input->delete_cookie($this->c_session);
        ee()->input->delete_cookie($this->c_anon);
        ee()->input->delete_cookie('tracker');
    }

    /**
     * Fetch guest data
     */
    public function fetch_guest_data()
    {
        $role = ee('Model')->get('Role', 3)->with('RoleSettings', 'Permissions')->all()->first();

        $this->userdata = array_merge($this->userdata, $role->RoleSettings->getValues());

        foreach ($role->Permissions as $permission) {
            $this->userdata[$permission->permission] = 'y';
        }

        $this->userdata['primary_role_id'] = $this->userdata['group_id'] = $role->getId();
        $this->userdata['primary_role_name'] = $this->userdata['group_title'] = $role->name;
        $this->userdata['primary_role_description'] = $this->userdata['group_description'] = $role->description;
        $this->userdata['primary_role_short_name'] = $role->short_name;
        $this->userdata['total_comments'] = 0;
        $this->userdata['total_entries'] = 0;
        $this->userdata['private_messages'] = 0;
        $this->userdata['total_forum_posts'] = 0;
        $this->userdata['total_forum_topics'] = 0;
        $this->userdata['total_forum_replies'] = 0;
        $this->userdata['display_signatures'] = 'y';
        $this->userdata['display_avatars'] = 'y';
        $this->userdata['display_photos'] = 'y';
        $this->userdata['parse_smileys'] = 'y';

        // The following cookie info is only used with the forum module.
        // It enables us to track "read topics" with users who are not
        // logged in.

        // Has the user been active before? If not we set the "last_activity" to the current time.
        $this->sdata['last_activity'] = (int) (! ee()->input->cookie('last_activity')) ? ee()->localize->now : ee()->input->cookie('last_activity');

        // Is the "last_visit" cookie set?  If not, we set the last visit
        // date to ten years ago. This is a kind of funky thing to do but
        // it enables the forum to show all topics as unread. Since the
        // last_visit stats are only available for logged-in members it
        // doesn't hurt anything to set it this way for guests.
        if (! ee()->input->cookie('last_visit')) {
            $this->userdata['last_visit'] = ee()->localize->now - ($this->activity_cookie_ttl * 10);
        } else {
            $this->userdata['last_visit'] = (int) ee()->input->cookie('last_visit');
        }

        // If the user has been inactive longer than the session length we'll
        // set the "last_visit" cookie with the "last_activity" date.

        if (($this->sdata['last_activity'] + $this->session_length) < ee()->localize->now) {
            $this->userdata['last_visit'] = $this->sdata['last_activity'];
        }
    }

    /**
     * Fetch member data
     */
    public function fetch_member_data()
    {
        $member_query = $this->_do_member_query();

        if (empty($member_query) || $member_query->num_rows() == 0) {
            $this->_initialize_session();

            return false;
        }

        // Turn the query rows into array values
        foreach ($member_query->row_array() as $key => $val) {
            if (in_array($key, ['timezone', 'date_format', 'time_format', 'week_start', 'include_seconds']) && $val === '') {
                $val = null;
            }

            if ($key != 'crypt_key') {
                $this->userdata[$key] = $val;
            } else {
                // we don't add the session encryption key to userdata, to avoid accidental disclosure
                $this->sess_crypt_key = $val;
            }
        }

        // Add in Primary Role data
        $this->userdata['primary_role_id'] = $this->member_model->PrimaryRole->getId();
        $this->userdata['primary_role_name'] = $this->member_model->PrimaryRole->name;
        $this->userdata['primary_role_short_name'] = $this->member_model->PrimaryRole->short_name;
        $this->userdata['primary_role_description'] = $this->member_model->PrimaryRole->description;

        // Member Group backwards compatibility
        $this->userdata['group_id'] = $this->member_model->PrimaryRole->getId();
        $this->userdata['group_title'] = $this->member_model->PrimaryRole->name;
        $this->userdata['group_description'] = $this->member_model->PrimaryRole->description;

        // Add in the Permissions for backwards compatibility
        $permissions = $this->member_model->getPermissions();
        foreach ($permissions as $perm => $perm_id) {
            $this->userdata[$perm] = 'y';
        }
        //ensure we get those cached, as they are not cached on model layer yet
        $this->set_cache("ExpressionEngine\Model\Member\Member", "Member/{$this->userdata['member_id']}/Permissions", $permissions);

        // Remember me may have validated the user agent for us, if so create a fingerprint now that we
        // can salt it properly for the user
        if ($this->validation == 'c' && ee()->remember->exists()) {
            $this->sdata['fingerprint'] = $this->_create_fingerprint($this->sess_crypt_key);
        }

        // validate the fingerprint as a last measure for 'c' and 's' sessions, since the fingerprint is only
        // propogated in 'cs' sessions. Obviously this passes if Remember me validated for us
        if ($this->sdata['fingerprint'] != $this->_create_fingerprint($this->sess_crypt_key)) {
            $this->_initialize_session();
            $this->_initialize_userdata();

            return false;
        }

        // Check MFA state
        $this->userdata['mfa_enabled'] = $this->member_model->enable_mfa;

        // Create the array for the Ignore List
        $this->userdata['ignore_list'] = ($this->userdata['ignore_list'] == '') ? array() : explode('|', $this->userdata['ignore_list']);

        // Fix the values for forum posts and replies
        $this->userdata['total_forum_posts'] = $member_query->row('total_forum_topics') + $member_query->row('total_forum_posts') ;
        $this->userdata['total_forum_replies'] = $member_query->row('total_forum_posts') ;

        $this->userdata['display_photos'] = ee()->config->item('enable_photos');

        //  Are users allowed to localize?
        if (ee()->config->item('allow_member_localization') == 'n' or empty($this->userdata['date_format'])) {
            $this->userdata['timezone'] = ee()->config->item('default_site_timezone');
            $this->userdata['date_format'] = ee()->config->item('date_format') ? ee()->config->item('date_format') : '%n/%j/%Y';
            $this->userdata['time_format'] = ee()->config->item('time_format') ? ee()->config->item('time_format') : '12';
            $this->userdata['week_start'] = ee()->config->item('week_start') ? ee()->config->item('week_start') : 'sunday';
            $this->userdata['include_seconds'] = ee()->config->item('include_seconds') ? ee()->config->item('include_seconds') : 'n';
        }

        // Assign Sites, Channel, Template, and Module Access Privs
        if (REQ == 'CP') {
            $this->_setup_channel_privs($this->member_model->isSuperAdmin());
            $this->_setup_module_privs();
            $this->_setup_template_privs();
            $this->_setup_assigned_sites($this->member_model->isSuperAdmin());
        }

        // Does the member have admin privileges?

        if ($this->member_model->can('access_cp')) {
            $this->access_cp = true;
        } else {
            $this->sdata['admin_sess'] = 0;
        }

        // Update the session array with the member_id

        if ($this->validation == 'c') {
            $this->sdata['member_id'] = (int) $member_query->row('member_id');
        }

        // If the user has been inactive for longer than the session length
        // we'll update their last_visit item so that it contains the last_activity
        // date.  That way, we can show the exact time they were last visitng the site.

        if (($this->userdata['last_visit'] == 0) or
            (($member_query->row('last_activity') + $this->session_length) < ee()->localize->now)) {
            $last_act = ($member_query->row('last_activity') > 0) ? $member_query->row('last_activity') : ee()->localize->now;

            ee()->db->where('member_id', (int) $this->sdata['member_id']);
            ee()->db->update('members', array(
                'last_visit' => $last_act,
                'last_activity' => ee()->localize->now
            ));

            $this->userdata['last_visit'] = $member_query->row('last_activity') ;
        }

        // Update member 'last activity' date field for this member.
        // We update this ever 5 minutes.  It's used with the session table
        // so we can update sessions

        if (($member_query->row('last_activity') + 300) < ee()->localize->now) {
            ee()->db->where('member_id', (int) $this->sdata['member_id']);
            ee()->db->update('members', array(
                'last_activity' => ee()->localize->now
            ));
        }

        $member_query->free_result();

        return true;
    }

    /**
     * Fetch session data
     *
     * @return 	boolean
     */
    public function fetch_session_data()
    {
        $session = ee('Model')->get('Session')
            ->filter('session_id', (string) $this->sdata['session_id']);

        // We already have a fingerprint to compare if they're running cs sessions
        // otherwise we'll do it after fetching their member data, presuming the session ID is valid
        if ($this->validation == 'cs') {
            $session = $session->filter('fingerprint', (string) $this->sdata['fingerprint']);
        }

        $session = $session->first();

        if (! $session or $session->member_id == 0) {
            $this->_initialize_session();

            return false;
        }

        $this->session_model = $session;

        // Assign member ID to session array
        $this->sdata['member_id'] = (int) $session->member_id;

        // Assign masquerader ID to session array
        $this->sdata['can_debug'] = $session->can_debug;

        $this->sdata['mfa_flag'] = $session->mfa_flag;

        // Is this an admin session?
        $this->sdata['admin_sess'] = ($session->admin_sess == 1) ? 1 : 0;

        // Log last activity
        $this->sdata['last_activity'] = $session->last_activity;
        $this->sdata['sess_start'] = $session->sess_start;

        // Set the fingerprint for c and s sessions to validate when fetching member data
        $this->sdata['fingerprint'] = $session->fingerprint;

        // If session has expired, delete it and set session data to GUEST
        if ($this->validation != 'c') {
            if ($session->last_activity < (ee()->localize->now - $this->session_length)) {
                ee()->db->where('session_id', $this->sdata['session_id']);
                ee()->db->delete(array('sessions', 'security_hashes'));

                $this->_initialize_session();

                return false;
            }
        }

        return true;
    }

    /**
     * Get flashdata by key
     *
     * @param	string
     * @return	mixed
     */
    public function flashdata($key = '')
    {
        return isset($this->flashdata[$key]) ? $this->flashdata[$key] : false;
    }

    /**
     * Is the nation banned?
     */
    public function nation_ban_check($show_error = true)
    {
        $ip_to_nation = ee('Addon')->get('ip_to_nation');

        if (ee()->config->item('require_ip_for_posting') != 'y' or (! $ip_to_nation or ! $ip_to_nation->isInstalled())) {
            return false;
        }

        // all IPv4 go to IPv6 mapped
        $addr = ee()->input->ip_address();

        if (strpos($addr, ':') === false && strpos($addr, '.') !== false) {
            $addr = '::' . $addr;
        }

        $addr = inet_pton($addr);
        $addr = ee()->db->escape_str($addr);

        $query = ee()->db
            ->select('country')
            ->where("ip_range_low <= '{$addr}'", '', false)
            ->where("ip_range_high >= '{$addr}'", '', false)
            ->order_by('ip_range_low', 'desc')
            ->limit(1, 0)
            ->get('ip2nation');

        if ($query->num_rows() == 1) {
            ee()->db->where(array(
                'code' => $query->row('country'),
                'banned' => 'y'
            ));

            if (ee()->db->count_all_results('ip2nation_countries')) {
                if ($show_error == true) {
                    return ee()->output->fatal_error(ee()->config->item('ban_message'), 0);
                }

                return false;
            }
        }
    }

    /**
     * Save password lockout
     */
    public function save_password_lockout($username = '')
    {
        if (ee()->config->item('password_lockout') == 'n') {
            return false;
        }

        $data = array(
            'login_date' => time(),
            'ip_address' => ee()->input->ip_address(),
            'user_agent' => $this->userdata['user_agent'],
            'username' => $username
        );

        ee()->db->insert('password_lockout', $data);
    }

    /**
     * Set Session Cache
     *
     * This method is a setter for the $cache class variable.
     * Note, this is not persistent across requests
     *
     * @param 	string 	Super Class/Unique Identifier
     * @param 	string 	Key for cached item
     * @param 	mixed 	item to put in the cache
     * @return 	object
     */
    public function set_cache($class, $key, $val)
    {
        if (! isset($this->cache[$class])) {
            $this->cache[$class] = array();
        }

        $this->cache[$class][$key] = $val;

        return $this;
    }

    /**
     * Set flashdata
     *
     * @param	mixed
     * @return	mixed
     */
    public function set_flashdata($key, $val = '')
    {
        if (! is_array($key)) {
            $key = array($key => $val);
        }

        foreach ($key as $k => $v) {
            $this->flashdata[':new:' . $k] = $v;
        }

        $this->_set_flash_cookie();
    }

    /**
     * Tracker
     *
     * This functions lets us store the visitor's last five pages viewed
     * in a cookie.  We use this to facilitate redirection after logging-in,
     * or other form submissions
     */
    public function tracker()
    {
        $tracker = ee()->input->cookie('tracker');

        if ($tracker != false) {
            $tracker = json_decode($tracker, true);
        }

        if (! is_array($tracker)) {
            $tracker = array();
        }

        if (! empty($tracker)) {
            if (! isset($tracker['token'])) {
                $tracker = array();
            } else {
                $tracker_token = $tracker['token'];
                unset($tracker['token']);

                // Check for funny business
                if (! ee('Encrypt')->verifySignature(
                    implode('', $tracker),
                    $tracker_token,
                    ee()->config->item('session_crypt_key'),
                    'sha384'
                )) {
                    $tracker = array();
                }
            }
        }

        $uri = (ee()->uri->uri_string == '') ? 'index' : ee()->uri->uri_string;

        $uri = str_replace("\\", "/", $uri);

        // If someone is messing with the URI we won't set the cookie

        if (! isset($_GET['ACT'])) {
            if (preg_match('/[^a-z0-9\%\_\/\-\.]/i', $uri)) {
                return array();
            }

            if (! isset($tracker['0'])) {
                $tracker[] = $uri;
            } else {
                if (count($tracker) == 5) {
                    array_pop($tracker);
                }

                if ($tracker['0'] != $uri) {
                    array_unshift($tracker, $uri);
                }
            }
        }

        return $tracker;
    }

    /**
     * This will set the tracker cookie with proper encoding
     *
     * @param array	$tracker An optional tracker array to set, defaults to
     *                       ee()->session->tracker
     */
    public function set_tracker_cookie($tracker = null)
    {
        if (ee()->config->item('enable_tracking_cookie') === 'n') {
            return true;
        }
        
        if (is_null($tracker)) {
            $tracker = $this->tracker;
        }

        // We add a hash to the end so we can check for manipulation
        if (! empty($tracker)) {
            unset($tracker['token']);

            $tracker['token'] = ee('Encrypt')->sign(
                implode('', $tracker),
                ee()->config->item('session_crypt_key'),
                'sha384'
            );
        }

        ee()->input->set_cookie('tracker', json_encode($tracker), 0);
    }

    /**
     * This will un-set the most recent URL from the tracker
     */
    public function do_not_track()
    {
        static $shifted;
        if ($shifted !== true) {
            array_shift($this->tracker);
            $shifted = true;
        }
        $this->set_tracker_cookie();
    }

    /**
     * Update Member session
     */
    public function update_session()
    {
        $this->sdata['last_activity'] = ee()->localize->now;

        $cur_session_id = $this->sdata['session_id'];

        // generate a new session ID if they've remained active during the whole
        // TTL but only if the session ID is being transported via a cookie, or
        // the rotation would cause you to have an invalid session in other open
        // windows or tabs. Note that the fingerprint is not affected by a
        // session id change, so it also works for cs.
        if ($this->validation != 's' && ($this->sdata['last_activity'] - $this->sdata['sess_start']) > $this->session_length) {
            $this->sdata['session_id'] = ee()->functions->random();
            $this->userdata['session_id'] = $this->sdata['session_id'];
            $this->sdata['sess_start'] = $this->sdata['last_activity'];

            // Security hashes are tied to session ids. Fix them.
            ee()->db->set('session_id', $this->sdata['session_id'])
                ->where('session_id', $cur_session_id)
                ->update('security_hashes');
        }

        ee()->db->query(ee()->db->update_string('exp_sessions', $this->sdata, "session_id = '" . $cur_session_id . "'"));

        // We'll unset the "last activity" item from the session data array.
        // We do this to avoid a conflict with the "last_activity" item in the
        // userdata array since we'll be merging the two arrays in a later step
        unset($this->sdata['last_activity']);
    }

    /**
     * Fetch a session item
     *
     * @param 	string 		Userdata item to return
     * @param 	default 	value returned if the key isn't set
     * @return 	mixed 		$default on failure, item on success
     */
    public function userdata($which, $default = false)
    {
        return (! isset($this->userdata[$which])) ? $default : $this->userdata[$which];
    }

    public function getMember()
    {
        return $this->member_model;
    }

    /**
     * Fetch the current session id or fingerprint
     *
     * @param 	string 		'admin' or 'user' depending on session type
     * @return 	string 		the session id or fingerprint
     */
    public function session_id($which = 'admin')
    {
        $session_type = ($which == 'user') ? ee()->config->item('website_session_type') : ee()->config->item('cp_session_type');

        $s = 0;

        switch ($session_type) {
            case 's':
                $s = ee()->session->userdata('session_id', 0);

                break;
            case 'cs':
                $s = ee()->session->userdata('fingerprint', 0);

                break;
        }

        return ($s);
    }

    /**
     * Get the currently used language pack. Will return the user's language
     * pack if a session exists, otherwise will fall back to the default
     * language. If that's not set for some reason, we'll just use English.
     * @return string Language pack name to use
     */
    public function get_language()
    {
        if ($this->userdata['language'] != '') {
            return $this->userdata['language'];
        }
        if (ee()->input->cookie('language')) {
            return ee()->input->cookie('language');
        } elseif (ee()->config->item('deft_lang') != '') {
            return ee()->config->item('deft_lang');
        }

        return 'english';
    }

    /**
     * Age flashdata
     *
     * Removes old, marks current as old, etc
     *
     * @return	void
     */
    public function _age_flashdata()
    {
        foreach ($this->flashdata as $key => $val) {
            if (strpos($key, ':old:') !== 0) {
                if (strpos($key, ':new:') === 0) {
                    $this->flashdata[substr($key, 5)] = $val;
                } else {
                    $this->flashdata[':old:' . $key] = $val;
                }
            }

            unset($this->flashdata[$key]);
        }

        $this->_set_flash_cookie();
    }

    /**
     * Reverse-age flashdata. This is useful when redirecting but knowing we'll
     * need to preserve flashdata to the next screen.
     */
    public function benjaminButtonFlashdata()
    {
        foreach ($this->flashdata as $key => $val) {
            if (strpos($key, ':new:') === false &&
                strpos($key, ':old:') === false) {
                $this->flashdata[':new:' . $key] = $val;
            }

            unset($this->flashdata[$key]);
        }

        $this->_set_flash_cookie();
    }

    /**
     * Do ban Check
     *
     * @return 	boolean
     */
    protected function _do_ban_check()
    {
        // Is the user banned?
        // We only look for banned IPs if it's not a control panel request.
        // We test for banned admins separately in the front controller
        $ban_status = false;

        if (REQ != 'CP') {
            if ($this->ban_check('ip')) {
                switch (ee()->config->item('ban_action')) {
                    case 'message':
                        return ee()->output->fatal_error(ee()->config->item('ban_message'), 0);

                        break;
                    case 'bounce':
                        ee()->functions->redirect(ee()->config->item('ban_destination')); exit;

                        break;
                    default:
                        $ban_status = true;

                        break;
                }
            }
        }

        return $ban_status;
    }

    /**
     * Perform the big query to grab member data
     *
     * @return 	object 	database result.
     */
    protected function _do_member_query()
    {
        // Query DB for member data.  Depending on the validation type we'll
        // either use the cookie data or the member ID gathered with the session query.
        $data = [];

        $member_id = $this->sdata['member_id'];

        // remember me
        if ($this->sdata['member_id'] == 0 &&
            $this->validation == 'c' &&
            ee()->remember->data('member_id')) {
            $member_id = ee()->remember->data('member_id');
        }

        if (! empty($member_id)) {
            ee()->db->from(array('members m', 'role_settings g'))
                ->where('g.site_id', (int) ee()->config->item('site_id'))
                ->where('m.role_id', ' g.role_id', false);

            ee()->db->where('member_id', (int) $member_id);

            $data = ee()->db->get();
        }

        if (! is_object($this->member_model) || $this->member_model->member_id != $member_id) {
            $this->_setupMemberModel($member_id);
        }

        return $data;
    }

    /**
     * Pupulate member_model with logged in member object
     *
     * @param int $memberId
     * @return Member model
     */
    private function _setupMemberModel($memberId)
    {
        $memberQuery = ee('Model')->get('Member', $memberId)
            ->with(['PrimaryRole' => 'RoleSettings'])
            ->with('Roles')
            ->with('RoleGroups');
        if (REQ == 'CP') {
            $memberQuery->with('EntryManagerViews');
        }
        $memberQuery->filter('RoleSettings.site_id', ee()->config->item('site_id'));
        $this->member_model = $memberQuery->all()->first();
    }

    /**
     * Reset session data as GUEST
     *
     * @return 	void
     */
    protected function _initialize_session()
    {
        $this->sdata = array(
            'session_id' => 0,
            'fingerprint' => 0,
            'member_id' => 0,
            'admin_sess' => 0,
            'mfa_flag' => 'skip',
            'ip_address' => ee()->input->ip_address(),
            'user_agent' => substr(ee()->input->user_agent(), 0, 120),
            'last_activity' => 0,
            'sess_start' => 0
        );
    }

    /**
     * Reset userdata as GUEST
     *
     * @return 	void
     */
    protected function _initialize_userdata()
    {
        // my_* cookies used by guests in the comment form
        $this->userdata = array(
            'username' => ee('Cookie')->getSignedCookie('my_name', true),
            'screen_name' => '',
            'email' => ee('Cookie')->getSignedCookie('my_email', true),
            'url' => ee('Cookie')->getSignedCookie('my_url', true),
            'location' => ee('Cookie')->getSignedCookie('my_location', true),
            'avatar_filename' => '',
            'avatar_width' => '',
            'avatar_height' => '',
            'language' => '',
            'timezone' => ee()->config->item('default_site_timezone'),
            'date_format' => ee()->config->item('date_format') ? ee()->config->item('date_format') : '%n/%j/%Y',
            'time_format' => ee()->config->item('time_format') ? ee()->config->item('time_format') : '12',
            'week_start' => ee()->config->item('week_start') ? ee()->config->item('week_start') : 'sunday',
            'include_seconds' => ee()->config->item('include_seconds') ? ee()->config->item('include_seconds') : 'n',
            'role_id' => '3',
            'access_cp' => 0,
            'mfa_enabled' => !empty($this->member_model) ? $this->member_model->enable_mfa : false,
            'last_visit' => 0,
            'is_banned' => $this->_do_ban_check(),
            'ignore_list' => array()
        );
    }

    /**
     * Prep flashdata
     *
     * Grabs the cookie and validates the signature
     *
     * @return	void
     */
    protected function _prep_flashdata()
    {
        if ($this->flashdata = ee('Cookie')->getSignedCookie('flash')) {
            $this->_age_flashdata();

            return;
        }

        $this->flashdata = array();
    }

    /**
     * Create a browser fingerprint
     *
     * @return	string
     */
    protected function _create_fingerprint($salt = 'kosher')
    {
        return md5(ee()->input->user_agent() . $salt);
    }

    /**
     * Set signed flashdata cookie
     *
     * @return	void
     */
    protected function _set_flash_cookie()
    {
        if (count($this->flashdata) > 0) {
            ee('Cookie')->setSignedCookie('flash', $this->flashdata);
        }
    }

    /**
     * Setup Assigned Sites
     *
     * @return void
     */
    protected function _setup_assigned_sites($is_superadmin = false)
    {
        // Fetch Assigned Sites Available to User
        $assigned_sites = ee('Model')->get('Site')
            ->fields('site_id', 'site_label')
            ->order('site_label', 'asc');

        if (! $is_superadmin) {
            $roles = $this->getMember()->getAllRoles()->pluck('role_id');
            $site_ids = ee('Model')->get('Permission')
                ->fields('site_id')
                ->filter('permission', 'can_access_cp')
                ->filter('role_id', 'IN', $roles)
                ->all()
                ->pluck('site_id');

            if (empty($site_ids)) {
                $this->userdata['assigned_sites'] = [];

                return;
            }

            $assigned_sites->filter('site_id', 'IN', $site_ids);
        }

        $this->userdata['assigned_sites'] = $assigned_sites->all()
            ->getDictionary('site_id', 'site_label');
    }

    /**
     * Setup CP Channel Privileges
     *
     * @return void
     */
    protected function _setup_channel_privs($is_superadmin = false)
    {
        // Fetch channel privileges
        $assigned_channels = array();

        if ($is_superadmin) {
            ee()->db->select('channel_id, channel_title');
            ee()->db->order_by('channel_title');
            $res = ee()->db->get_where(
                'channels',
                array('site_id' => ee()->config->item('site_id'))
            );

            if ($res->num_rows() > 0) {
                foreach ($res->result() as $row) {
                    $assigned_channels[$row->channel_id] = $row->channel_title;
                }
            }

            $res->free_result();
        } else {
            $site_id = ee()->config->item('site_id');

            $assigned_channels = $this->member_model->getAssignedChannels()
                ->filter(function ($channel) use ($site_id) {
                    return $channel->site_id == $site_id;
                })
                ->getDictionary('channel_id', 'channel_title');
        }

        $this->userdata['assigned_channels'] = $assigned_channels;
    }

    /**
     * Setup Module Privileges
     *
     * @return void
     */
    protected function _setup_module_privs()
    {
        $assigned_modules = array();

        foreach ($this->member_model->getAssignedModules() as $module) {
            $assigned_modules[$module->getId()] = true;
        }

        $this->userdata['assigned_modules'] = $assigned_modules;
    }

    /**
     * Setup Session Lengths
     *
     * @return 	integer Session length in seconds
     */
    protected function _setup_session_length()
    {
        return (REQ == 'CP') ? $this->cpan_session_len : $this->user_session_len;
    }

    /**
     * Setup Session Cookie Timeout
     *
     * @return 	int Cookie timeout in seconds
     */
    protected function _setup_cookie_ttl()
    {
        if (bool_config_item('expire_session_on_browser_close')) {
            return 0;
        }

        return $this->session_length;
    }

    /**
     * Setup Template Privileges
     *
     * @return void
     */
    protected function _setup_template_privs()
    {
        $assigned_template_groups = [];

        foreach ($this->member_model->getAssignedTemplateGroups() as $template_group) {
            $assigned_template_groups[$template_group->getId()] = true;
        }

        $this->userdata['assigned_template_groups'] = $assigned_template_groups;
    }
}
// END CLASS

// EOF
