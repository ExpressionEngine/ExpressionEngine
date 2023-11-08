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
 * ExpressionEngine Authentication Library
 * ExpressionEngine User Classes (* = current):
 *
 *   1. Session
 *   2. Authentication*
 *   3. Permissions
 *
 * Doing authentication securely relies heavily on handling user
 * passwords responsibly. Thanks to steadily increasing computing
 * power, cryptographic hashing algorithms evolve continuously.
 *
 * To deal with this we continually try to upgrade the user. The
 * general authentication flow therefore becomes:
 *
 *   1. Grab user info using a unique identifier.
 *
 *   2. Determine the function used for their stored password.
 *   We do this by looking at the length of the hash. This
 *   also means that we can never support two algorithms of
 *   the same length. Not a big problem.
 *
 *   3. Determine if their old password hash was salted.
 *   This is easy; we store the salt with their userdata.
 *
 *   4. Hash the input password with the old salt and hash function.
 *   If this fails we're done, the password was incorrect.
 *
 *   5. Check if we can improve security of their password.
 *   If it wasn't salted, we salt it. If we support a newer
 *   hash function, we create a new salt and rehash the password.
 *
 * EE Dev Note: In EE's db the password and salt column
 * should always be as long as the best available hash.
 *
 */

use ExpressionEngine\Service\Member\Member;

class Auth
{
    public $errors = array();

    // Hashing algorithms to try with their respective
    // byte sizes. The byte sizes are used to identify
    // the hash function, so they must be unique!

    private $hash_algos = array(
        128 => 'sha512',
        64 => 'sha256',
        40 => 'sha1',
        32 => 'md5'
    );

    private const BCRYPT_HASH_LENGTH = 60;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        // Remove any hash algos that we don't have
        // access to in this environment
        $this->hash_algos = array_intersect($this->hash_algos, hash_algos());
    }

    /**
     * Authenticate with an id
     *
     * @access public
     */
    public function authenticate_id($id, $password)
    {
        $member = ee()->db->get_where('members', array('member_id' => $id));

        return $this->_authenticate($member, $password);
    }

    /**
     * Authenticate with email
     *
     * @access public
     */
    public function authenticate_email($email, $password)
    {
        $member = ee()->db->get_where('members', array('email' => $email));

        return $this->_authenticate($member, $password);
    }

    /**
     * Authenticate with username
     *
     * @access public
     */
    public function authenticate_username($username, $password)
    {
        $member = ee()->db->get_where('members', array('username' => $username));

        return $this->_authenticate($member, $password);
    }

    /**
     * Authenticate from basic http auth
     *
     * @access public
     */
    public function authenticate_http_basic(
        $not_allowed_groups = array(),
        $realm = 'Authentication Required'
    ) {
        $always_disallowed = array(2, 3, 4);

        $not_allowed_groups = array_merge($not_allowed_groups, $always_disallowed);

        $authed = $this->_retrieve_http_basic();

        if ($authed !== false) {
            $member = ee('Model')->get('Member', $authed->member('member_id'))->with('PrimaryRole', 'Roles', 'RoleGroups')->all()->first();

            $authed = false;

            foreach ($member->getAllRoles()->pluck('role_id') as $role_id) {
                // If they have even a single Role that is allowed, then they are allowed
                if (! in_array($role_id, $not_allowed_groups) || $role_id == 1) {
                    $authed = true;

                    break;
                }
            }
        }

        if ($authed === false) {
            @header('WWW-Authenticate: Basic realm="' . $realm . '"');
            ee()->output->set_status_header(401);
            @header("Date: " . gmdate("D, d M Y H:i:s") . " GMT");
            exit("HTTP/1.0 401 Unauthorized");
        }

        return true;
    }

    /**
     * Authenticate from digest http auth
     *
     * @access public
     */
    public function authenticate_http_digest()
    {
        die('@todo');
    }

    /**
     * Run through the majority of the authentication checks
     *
     * @return array(
     *      username: from POST
     *      password: from POST
     *      incoming: from Auth library, using username and password
     *  )
     *
     * Your best option is to use:
     * list($username, $password, $incoming) = $this->verify()
     *
     * If an error results, the lang key will be added to $this->(auth->)errors[]
     * and this method will return FALSE
     */
    public function verify()
    {
        // If this is being called from the CP, use the hook
        if (REQ == 'CP') {
            /* -------------------------------------------
            /* 'login_authenticate_start' hook.
            /*  - Take control of CP authentication routine
            /*  - Added EE 1.4.2
            */
            ee()->extensions->call('login_authenticate_start');
            if (ee()->extensions->end_script === true) {
                return;
            }
            /*
            /* -------------------------------------------*/
        }

        $username = (string) ee()->input->post('username');

        // No username/password?  Bounce them...
        if (! $username) {
            $this->errors[] = 'no_username';

            return false;
        }

        ee()->session->set_flashdata('username', $username);

        if (! ee()->input->get_post('password')) {
            $this->errors[] = 'no_password';

            return false;
        }

        // Is IP and User Agent required for login?
        if (! ee()->auth->check_require_ip()) {
            $this->errors[] = 'unauthorized_request';

            return false;
        }

        // Check password lockout status
        if (ee()->session->check_password_lockout($username) === true) {
            ee()->lang->loadfile('login');

            $line = lang('password_lockout_in_effect');
            $line = sprintf($line, ee()->config->item('password_lockout_interval'));

            if (AJAX_REQUEST) {
                ee()->output->send_ajax_response(array(
                    'messageType' => 'logout'
                ));
            }

            ee()->session->set_flashdata('message', $line);
            ee()->functions->redirect(ee('CP/URL')->make('login')->compile());
        }

        //  Check credentials
        // ----------------------------------------------------------------
        $password = (string) ee()->input->post('password');

        // Ensure password is not too long
        if (strlen($password) > PASSWORD_MAX_LENGTH) {
            $this->errors[] = 'credential_missmatch';

            return false;
        }

        // Allow users to register with Username
        // ----------------------------------------------------------------
        $incoming = ee()->auth->authenticate_username($username, $password);

        // Allow users to register with Email
        // ----------------------------------------------------------------
        if (! $incoming) {
            $incoming = ee()->auth->authenticate_email($username, $password);
        }

        // Not even close
        if (! $incoming) {
            ee()->session->save_password_lockout($username);
            $this->errors[] = 'credential_missmatch';

            return false;
        }

        // Banned
        if ($incoming->is_banned()) {
            return ee()->output->fatal_error(lang('not_authorized'));
        }

        // No cp access
        if (REQ == 'CP' && ! $incoming->has_permission('can_access_cp')) {
            $this->errors[] = 'not_authorized';

            return false;
        }

        // Do we allow multiple logins on the same account?
        if (ee()->config->item('allow_multi_logins') == 'n') {
            if ($incoming->has_other_session()) {
                $this->errors[] = 'multi_login_warning';

                return false;
            }
        }

        return array($username, $password, $incoming);
    }

    /**
     * Check Required IP
     *
     * @access public
     * @return boolean
     */
    public function check_require_ip()
    {
        if (ee()->config->item('require_ip_for_login') == 'y') {
            if (
                ee()->session->userdata('ip_address') == '' or
                ee()->session->userdata('user_agent') == ''
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hash Password
     *
     * Call it with just a password to generate a new hash/salt pair.
     * Call with an existing salt and hash_size if you need to compare
     * to an old password. The latter is mostly internal, you probably
     * want one of the authenticate_* methods instead.
     *
     * @access public
     */
    public function hash_password($password, $salt = false, $h_byte_size = false)
    {
        // Even for md5, collisions usually happen above 1024 bits, so
        // we artifically limit their password to reasonable size.
        if (! $password or strlen($password) > PASSWORD_MAX_LENGTH) {
            return false;
        }

        // No hash function specified? Use the best one
        // we have access to in this environment.
        if ($h_byte_size === false || $h_byte_size == self::BCRYPT_HASH_LENGTH) {
            return [
                'salt' => '',
                'password' => password_hash($password, PASSWORD_BCRYPT)
            ];
        } elseif (! isset($this->hash_algos[$h_byte_size])) {
            // What are they feeding us? This can happen if
            // they move servers and the new environment is
            // less secure. Nothing we can do but fail. Hard.

            die('Fatal Error: No matching hash algorithm.');
        }

        // No salt? (not even blank), we'll regenerate
        if ($salt === false) {
            $salt = '';

            // The salt should never be displayed, so any
            // visible ascii character is fair game.
            for ($i = 0; $i < $h_byte_size; $i++) {
                $salt .= chr(mt_rand(33, 126));
            }
        } elseif (strlen($salt) !== $h_byte_size) {
            // they passed us a salt that isn't the right length,
            // this can happen if old code resets a new password
            // ignore it
            $salt = '';
        }

        return array(
            'salt' => $salt,
            'password' => hash($this->hash_algos[$h_byte_size], $salt . $password)
        );
    }

    /**
     * Update Username
     *
     * @access public
     */
    public function update_username($member_id, $username)
    {
        ee()->db->where('member_id', (int) $member_id);
        ee()->db->set('username', $username);
        ee()->db->update('members');

        return (bool) ee()->db->affected_rows();
    }
    /**
     * Update Password
     *
     * @access public
     */
    public function update_password($member_id, $password)
    {
        $hashed_pair = $this->hash_password($password);

        if ($hashed_pair === false) {
            return false;
        }

        // remove old remember me's and sessions, so that
        // changing your password effectively logs out people
        // using the old one.
        ee()->remember->delete_others($member_id);

        ee()->db->where('member_id', (int) $member_id);
        ee()->db->where('session_id !=', (string) ee()->session->userdata('session_id'));
        ee()->db->delete('sessions');

        // update password in db
        $member = ee('Model')->get('Member')
            ->filter('member_id', $member_id)
            ->first();

        $member->set($hashed_pair);
        $member->save();

        return (bool) ee()->db->affected_rows();
    }

    /**
     * Authenticate
     *
     * @access private
     */
    private function _authenticate(CI_DB_result $member, $password)
    {
        $always_disallowed = array(Member::GUESTS, Member::PENDING);

        if (bool_config_item('allow_pending_login')) {
            $always_disallowed = array_diff($always_disallowed, array(Member::PENDING));
        }

        if ($member->num_rows() !== 1) {
            return false;
        }

        if (in_array($member->row('role_id'), $always_disallowed)) {
            if ($member->row('role_id') == Member::PENDING) {
                ee()->lang->loadfile('member');
                $error = lang('mbr_account_not_active');
            } else {
                $error = lang('not_authorized');
            }
            return ee()->output->show_user_error('general', $error);
        }

        $m_salt = $member->row('salt');
        $m_pass = $member->row('password');

        // hash using the algo used for this password
        $h_byte_size = strlen($m_pass);

        // Bcrypt hash
        if ($h_byte_size == self::BCRYPT_HASH_LENGTH && ! password_verify($password, $m_pass)) {
            return false;
        } elseif ($h_byte_size != self::BCRYPT_HASH_LENGTH) {
            $hashed_pair = $this->hash_password($password, $m_salt, $h_byte_size);

            if ($hashed_pair === false or $m_pass !== $hashed_pair['password']) {
                return false;
            }
        }

        // Officially a valid user, but are they as secure as possible?
        // ----------------------------------------------------------------

        // Not hashed or better algo available?
        if ($h_byte_size != self::BCRYPT_HASH_LENGTH) {
            $m_id = $member->row('member_id');
            $this->update_password($m_id, $password);
        }

        $authed = new Auth_result($member->row());
        $member->free_result();

        return $authed;
    }

    /**
     * Retrieve Basic HTTP Credentials
     *
     * @access private
     */
    private function _retrieve_http_basic()
    {
        //  Find Username, Please
        // ----------------------------------------------------------------

        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $user = $_SERVER['PHP_AUTH_USER'];
        } elseif (isset($_ENV['REMOTE_USER'])) {
            $user = $_ENV['REMOTE_USER'];
        } elseif (@getenv('REMOTE_USER')) {
            $user = getenv('REMOTE_USER');
        } elseif (isset($_ENV['AUTH_USER'])) {
            $user = $_ENV['AUTH_USER'];
        } elseif (@getenv('AUTH_USER')) {
            $user = getenv('AUTH_USER');
        }

        //  Find Password, Please
        // ----------------------------------------------------------------

        if (isset($_SERVER['PHP_AUTH_PW'])) {
            $pass = $_SERVER['PHP_AUTH_PW'];
        } elseif (isset($_ENV['REMOTE_PASSWORD'])) {
            $pass = $_ENV['REMOTE_PASSWORD'];
        } elseif (@getenv('REMOTE_PASSWORD')) {
            $pass = getenv('REMOTE_PASSWORD');
        } elseif (isset($_ENV['AUTH_PASSWORD'])) {
            $pass = $_ENV['AUTH_PASSWORD'];
        } elseif (@getenv('AUTH_PASSWORD')) {
            $pass = getenv('AUTH_PASSWORD');
        }

        // Authentication for IIS
        // ----------------------------------------------------------------

        if (! isset($user) or ! isset($pass) or (empty($user) && empty($pass))) {
            if (isset($_SERVER['HTTP_AUTHORIZATION']) && substr($_SERVER['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ') {
                list($user, $pass) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            } elseif (! empty($_ENV) && isset($_ENV['HTTP_AUTHORIZATION']) && substr($_ENV['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ') {
                list($user, $pass) = explode(':', base64_decode(substr($_ENV['HTTP_AUTHORIZATION'], 6)));
            } elseif (@getenv('HTTP_AUTHORIZATION') && substr(getenv('HTTP_AUTHORIZATION'), 0, 6) == 'Basic ') {
                list($user, $pass) = explode(':', base64_decode(substr(getenv('HTTP_AUTHORIZATION'), 6)));
            }
        }

        //  Authentication for FastCGI
        // ----------------------------------------------------------------

        if (! isset($user) or ! isset($pass) or (empty($user) && empty($pass))) {
            if (! empty($_ENV) && isset($_ENV['Authorization']) && substr($_ENV['Authorization'], 0, 6) == 'Basic ') {
                list($user, $pass) = explode(':', base64_decode(substr($_ENV['Authorization'], 6)));
            } elseif (@getenv('Authorization') && substr(getenv('Authorization'), 0, 6) == 'Basic ') {
                list($user, $pass) = explode(':', base64_decode(substr(getenv('Authorization'), 6)));
            }
        }

        if (! isset($user) or ! isset($pass) or (empty($user) && empty($pass))) {
            return false;
        }

        // Check password Lockout
        if (ee()->session->check_password_lockout($user) === true) {
            return false;
        }

        return $this->authenticate_username($user, $pass);
    }
}
// END Auth class

class Auth_result
{
    private $group;
    private $member;
    private $permissions = [];
    private $session_id;
    private $remember_me = false;
    private $anon = false;

    /**
     * Constructor
     *
     * @access public
     * @param   object  member query row
     * @param   int     session id if using multi login
     */
    public function __construct(stdClass $member)
    {
        $this->member = $member;
    }

    /**
     * Group data getter
     *
     * @access public
     */
    public function group($key, $default = false)
    {
        if (! is_object($this->group)) {
            $group_q = ee()->db->get_where('member_groups', array(
                'group_id' => $this->member('group_id'),
                'site_id' => ee()->config->item('site_id'),
            ));

            $this->group = $group_q->row();

            $group_q->free_result();
        }

        return isset($this->group->$key) ? $this->group->$key : $default;
    }

    /**
     * Multi-login check
     *
     * @access public
     */
    public function has_other_session()
    {
        // Kill old sessions first
        ee()->session->gc_probability = 100;
        ee()->session->delete_old_sessions();

        $expire = time() - ee()->session->session_length;

        // See if there is a current session
        ee()->db->select('ip_address, user_agent');
        ee()->db->where('member_id', $this->member('member_id'));
        ee()->db->where('last_activity >', $expire);
        $result = ee()->db->get('sessions');

        // If a session exists, trigger the error message
        if ($result->num_rows() == 1) {
            $ip = ee()->session->userdata['ip_address'];
            $ua = ee()->session->userdata['user_agent'];

            if (
                $ip != $result->row('ip_address') or
                $ua != $result->row('user_agent')
            ) {
                $result->free_result();

                return true;
            }
        }

        $result->free_result();

        return false;
    }

    /**
     * Simplified permission checks
     *
     * @access public
     */
    public function has_permission($perm)
    {
        if (empty($this->permissions)) {
            $member = ee('Model')->get('Member', $this->member('member_id'))->with('PrimaryRole', 'Roles', 'RoleGroups')->all()->first();

            if ($member->role_id == 1) {
                return true;
            }

            $this->permissions = ee('Model')->get('Permission')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('role_id', 'IN', $member->getAllRoles()->pluck('role_id'))
                ->all()
                ->getDictionary('permission', 'permission_id');
        }

        return (array_key_exists($perm, $this->permissions));
    }

    /**
     * Ban check
     *
     * @access public
     */
    public function is_banned()
    {
        if ($this->member('role_id') != 1) {
            return ee()->session->ban_check();
        }

        return false;
    }

    /**
     * Member data getter
     *
     * @access public
     */
    public function member($key, $default = false)
    {
        return isset($this->member->$key) ? $this->member->$key : $default;
    }

    /**
     * Anon setter
     *
     * @access public
     */
    public function anon($anon)
    {
        $this->anon = $anon;
    }

    /**
     * Start session
     *
     * Handles all of the checks and cookie stuff
     *
     * @access public
     */
    public function start_session($cp_sess = false)
    {
        $multi = $this->session_id ? true : false;
        $sess_type = $cp_sess ? 'cp_session_type' : 'website_session_type';

        if ($multi) {
            // multi login - we have a session
            ee()->input->set_cookie(
                ee()->session->c_session,
                $this->session_id,
                ee()->session->session_length
            );

            ee()->session->userdata['session_id'] = $this->session_id;
        } else {
            // Create a new session
            $this->session_id = ee()->session->create_new_session(
                $this->member('member_id'),
                $cp_sess
            );
        }

        if (ee()->config->item($sess_type) != 's') {
            $expire = ee()->remember->get_expiry();

            if ($this->anon) {
                ee()->input->set_cookie(ee()->session->c_anon, 1, $expire);
            } else {
                // Unset the anon cookie
                ee()->input->delete_cookie(ee()->session->c_anon);
            }

            // (un)set remember me
            if ($this->remember_me) {
                ee()->remember->create();
            } else {
                ee()->remember->delete();
            }
        }

        // check MFA setting with respect to current site
        $roleSettings = ee('Model')->get('Role')->with('RoleSettings')->filter('role_id', $this->member('role_id'))->filter('RoleSettings.site_id', ee()->config->item('site_id'))->first();
        if (!empty($roleSettings) && !is_null($roleSettings->RoleSettings) && $roleSettings->RoleSettings->first()->require_mfa === true && ee()->session->getMember()->enable_mfa !== true) {
            $sessions = ee('Model')
                ->get('Session')
                ->filter('member_id', ee()->session->userdata('member_id'))
                ->filter('fingerprint', ee()->session->userdata('fingerprint'))
                ->all();
            foreach ($sessions as $session) {
                $session->mfa_flag = 'show';
                $session->save();
            }
            ee()->session->mfa_flag = 'show';
        }

        if ($cp_sess === true) {
            // Log the login

            // We'll manually add the username to the Session array so
            // the logger class can use it.
            ee()->session->userdata['username'] = $this->member('username');
            ee()->logger->log_action(lang('member_logged_in'));

            // -------------------------------------------
            // 'cp_member_login' hook.
            //  - Additional processing when a member is logging into CP
            //
            ee()->extensions->call('cp_member_login', $this->_hook_data());
            if (ee()->extensions->end_script === true) {
                return;
            }
            //
            // -------------------------------------------
        } elseif ($multi) {
            // -------------------------------------------
            // 'member_member_login_multi' hook.
            //  - Additional processing when a member is logging into multiple sites
            //
            ee()->extensions->call('member_member_login_multi', $this->_hook_data());
            if (ee()->extensions->end_script === true) {
                return;
            }
            //
            // -------------------------------------------
        } else {
            // -------------------------------------------
            // 'member_member_login_single' hook.
            //  - Additional processing when a member is logging into single site
            //
            ee()->extensions->call('member_member_login_single', $this->_hook_data());
            if (ee()->extensions->end_script === true) {
                return;
            }
            //
            // -------------------------------------------
        }

        // Delete old password lockouts
        ee()->session->delete_password_lockout();
    }

    /**
     * Session id getter session
     *
     * Only works after the session has been started
     *
     * @access public
     */
    public function session_id()
    {
        return $this->session_id;
    }

    /**
     * Session id setter
     *
     * Tells start_session to latch onto an existing session for
     * the multi site login
     *
     * @access public
     */
    public function use_session_id($session_id)
    {
        $this->session_id = $session_id;
    }

    /**
     * Remember me
     *
     * Whether or not this session will be started with 'remember me'
     *
     * @access public
     */
    public function remember_me($remember = true)
    {
        $this->remember_me = ($remember) ? true : false;
    }

    /**
     * Hook data utility method
     *
     * We cannot change the hook parameter without lots of warning, so
     * this is a silly workaround. Doing a clone aslo isolates the hook
     * from the rest of the code, which I like.
     *
     * @access private
     */
    private function _hook_data()
    {
        $obj = clone $this->member;
        $obj->session_id = $this->session_id;
        $obj->can_access_cp = $this->has_permission('can_access_cp');

        return $obj;
    }
}
// END CLASS

// EOF
