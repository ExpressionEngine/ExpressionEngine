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
 * Member Management Auth
 */
class Member_auth extends Member
{
    /**
     * Login Page
     *
     * @param   string  number of pages to return back to in the
     *                  exp_tracker cookie
     */
    public function profile_login_form($return = '-2')
    {
        // If they are already logged in then send them away.
        if (ee()->session->userdata('member_id') !== 0) {
            return ee()->functions->redirect(ee()->functions->fetch_site_index());
        }

        $login_form = $this->_load_element('login_form');

        if (ee()->config->item('website_session_type') != 'c') {
            $login_form = $this->_deny_if('auto_login', $login_form);
        } else {
            $login_form = $this->_allow_if('auto_login', $login_form);
        }

        // match {form_declaration} or {form_declaration return="foo"}
        // [0] => {form_declaration return="foo"}
        // [1] => form_declaration return="foo"
        // [2] =>  return="foo"
        // [3] => "
        // [4] => foo
        preg_match(
            "/" . LD . "(form_declaration" . "(\s+return\s*=\s*(\042|\047)([^\\3]*?)\\3)?)" . RD . "/s",
            $login_form,
            $match
        );

        if (empty($match)) {
            // don't even return the login template because the form will not work since
            // the template does not contain a {form_declaration}
            return;
        }

        $data['hidden_fields']['ACT'] = ee()->functions->fetch_action_id('Member', 'member_login');

        if (isset($match['4'])) {
            $data['hidden_fields']['RET'] = (substr($match['4'], 0, 4) !== 'http') ? ee()->functions->create_url($match['4']) : $match['4'];
        } elseif ($this->in_forum == true) {
            $data['hidden_fields']['RET'] = $this->forum_path;
        } else {
            $data['hidden_fields']['RET'] = ($return == 'self') ? $this->_member_path($this->request . '/' . $this->cur_id) : $return;
        }

        $data['hidden_fields']['FROM'] = ($this->in_forum === true) ? 'forum' : '';
        $data['id'] = 'member_login_form';

        $this->_set_page_title(lang('member_login'));

        return $this->_var_swap($login_form, array(
            $match['1'] => ee()->functions->form_declaration($data)));
    }

    /**
     * Member Login
     */
    public function member_login()
    {
        $return = ee()->input->get_post('RET');

        if (empty($return)) {
            $return_link = ee()->functions->fetch_site_index();
        } elseif (is_numeric($return)) {
            $return_link = ee()->functions->form_backtrack($return);
        } else {
            $return_link = $return;
        }

        ee()->load->library('auth');
        ee()->lang->loadfile('login');

        /* -------------------------------------------
        /* 'member_member_login_start' hook.
        /*  - Take control of member login routine
        /*  - Added EE 1.4.2
        */
        ee()->extensions->call('member_member_login_start');
        if (ee()->extensions->end_script === true) {
            return;
        }
        /*
        /* -------------------------------------------*/

        // Figure out how many sites we're dealing with here
        $sites = ee()->config->item('multi_login_sites');
        $sites_array = explode('|', $sites);
        $protocolsReplace = [
            'http://' => 'https://',
            'https://' => 'http://'
        ];

        // No username/password?  Bounce them...
        $multi = (ee()->input->get('multi') && count($sites_array) > 0)
            ? ee()->input->get('multi')
            : false;
        $username = ee()->input->post('username');
        $password = ee()->input->post('password');

        if (! $multi && ! ($username && $password)) {
            return ee()->output->show_user_error('general', lang('mbr_form_empty'));
        }

        if (strlen($password) > PASSWORD_MAX_LENGTH) {
            return ee()->output->show_user_error('general', lang('credential_missmatch'));
        }

        // This should go in the auth lib.
        if (! ee()->auth->check_require_ip()) {
            return ee()->output->show_user_error('general', lang('unauthorized_request'));
        }

        // Check password lockout status
        if (true === ee()->session->check_password_lockout($username)) {
            $line = lang('password_lockout_in_effect');
            $line = sprintf($line, ee()->config->item('password_lockout_interval'));

            ee()->output->show_user_error('general', $line);
        }

        $success = '';

        // Log me in.
        if ($multi) {
            // Multiple Site Login
            $incoming = $this->_do_multi_auth($sites, $multi);

            $current_url = ee()->functions->fetch_site_index();
            $current_search_url = preg_replace('/\/S=.*$/', '', $current_url);
            do {
                $current_idx = array_search($current_search_url, $sites_array);
                $current_search_url = str_replace(array_key_first($protocolsReplace), array_shift($protocolsReplace), $current_search_url);
            } while ($current_idx === false && !empty($protocolsReplace));

            // Figure out return
            if (! $return_link = ee()->input->get('RET')) {
                $return_link = $sites[ee()->input->get('orig')];
            } else {
                $return_link = base64_decode(strtr($return_link, '_-', '/='));
            }
        } else {
            // Regular Login
            $incoming = $this->_do_auth($username, $password);

            $current_url = ee()->functions->fetch_site_index();
            $current_search_url = preg_replace('/\/S=.*$/', '', $current_url);
            $current_idx = array_search($current_search_url, $sites_array);

            $return_link = reduce_double_slashes(ee()->functions->form_backtrack());
        }

        if (!empty($sites) && $current_idx === false) {
            ee()->output->show_user_error('general', lang('multi_auth_redirect_site_not_found'));
        }

        // Set login state
        if ($multi) {
            $login_state = $multi;
        } else {
            $login_state = random_string('md5');
            ee()->db->update(
                'sessions',
                array('login_state' => $login_state),
                array('session_id' => ee()->session->userdata('session_id'))
            );
        }

        // More sites?
        if ($sites) {
            $this->_redirect_next_site($sites, $current_idx, $current_url, $login_state);
        }

        // Clear out login_state
        ee()->db->set('login_state', null)
            ->where('session_id', ee()->session->userdata('session_id'))
            ->update('sessions');

        return ee()->functions->redirect($return_link);
    }

    /**
     * Check against minimum username/password length
     *
     * @param   object  member auth object
     * @param   string  username
     * @param   string  password
     * @return  void    a redirect on failure, or nothing
     */
    private function _check_min_unpwd($member_obj, $username, $password)
    {
        $uml = ee()->config->item('un_min_len');
        $pml = ee()->config->item('pw_min_len');

        $ulen = strlen($username);
        $plen = strlen($password);

        if ($ulen < $uml or $plen < $pml) {
            $trigger = '';
            if (ee()->input->get_post('FROM') == 'forum') {
                $this->basepath = ee()->input->get_post('mbase');
                $trigger = ee()->input->get_post('trigger');
            }

            $path = 'unpw_update/' . $member_obj->member('member_id') . '_' . $ulen . '_' . $plen;

            if ($trigger != '') {
                $path .= '/' . $trigger;
            }

            return ee()->functions->redirect($this->_member_path($path));
        }
    }

    /**
     * Do member auth
     *
     * @param   string  POSTed username
     * @param   string  POSTed password
     * @return  object  session data.
     */
    private function _do_auth($username, $password)
    {
        $sess = ee()->auth->authenticate_username($username, $password);

        if ($sess === false) {
            $sess = ee()->auth->authenticate_email($username, $password);
        }

        if (! $sess) {
            ee()->session->save_password_lockout($username);

            if (empty($username) or empty($password)) {
                return ee()->output->show_user_error('general', lang('mbr_form_empty'));
            } else {
                return ee()->output->show_user_error('general', lang('invalid_existing_un_pw'));
            }
        }

        // Banned
        if ($sess->is_banned()) {
            return ee()->output->show_user_error('general', lang('not_authorized'));
        }

        // Allow multiple logins?
        // Do we allow multiple logins on the same account?
        if (ee()->config->item('allow_multi_logins') == 'n') {
            if ($sess->has_other_session()) {
                return ee()->output->show_user_error('general', lang('not_authorized'));
            }
        }

        // Check user/pass minimum length
        $this->_check_min_unpwd($sess, $username, $password);

        $csrf_token = ee()->csrf->refresh_token();

        // Start Session
        // "Remember Me" is one year
        if (isset($_POST['auto_login'])) {
            $sess->remember_me();
        }

        $anon = (ee()->input->post('anon') == 1) ? false : true;

        $sess->anon($anon);

        $sess->start_session();
        $this->_update_online_user_stats();

        return $sess;
    }

    /**
     * Do Multi-site authentication
     *
     * @param array $sites Array of site URLs to login to
     * @param string $login_state The hash identifying the member
     * @return  object  member auth object
     */
    private function _do_multi_auth($sites, $login_state)
    {
        if (! $sites or empty($login_state)) {
            return ee()->output->show_user_error('general', lang('not_authorized'));
        }

        // Kill old sessions first
        ee()->session->gc_probability = 100;
        ee()->session->delete_old_sessions();

        // Grab session
        $sess_q = ee()->db->get_where('sessions', array(
            'user_agent' => substr(ee()->input->user_agent(), 0, 120),
            'login_state' => $login_state
        ));

        if (! $sess_q->num_rows()) {
            return ee()->output->show_user_error('general', lang('not_authorized'));
        }

        // Grab member
        $mem_q = ee()->db->get_where('members', array(
            'member_id' => $sess_q->row('member_id')
        ));

        if (! $mem_q->num_rows()) {
            return false;
        }

        $incoming = new Auth_result($mem_q->row());
        $csrf_token = ee()->csrf->refresh_token();

        // this is silly - only works for the first site
        if (isset($_POST['auto_login'])) {
            $incoming->remember_me();
        }

        // hook onto an existing session
        $incoming->use_session_id($sess_q->row('session_id'));
        $incoming->start_session();

        $new_row = $sess_q->row_array();
        $some_row['site_id'] = ee()->config->item('site_id');

        return $incoming;
    }
    /**
     * Redirect next site
     *
     * This function redirects to the next site for multi-site login based on
     * the array setup in config.php
     */
    public function _redirect_next_site($sites, $current_idx, $current_url, $login_state)
    {
        $sites = explode('|', $sites);
        $num_sites = count($sites);
        $orig_id = ee()->input->get('orig_site_id');
        $orig_idx = ee()->input->get('orig');
        $return = ee()->input->get('RET');

        $next_idx = $current_idx + 1;

        // first site, no qs yet
        if ($orig_id === false) {
            $orig_id = ee()->config->item('site_id');
            $orig_idx = $current_idx;
            $next_idx = ($current_idx == '0') ? '1' : '0';
            $return = reduce_double_slashes(ee()->functions->form_backtrack());
            $return = strtr(base64_encode($return), '/=', '_-');
        } elseif ($next_idx == $orig_idx) {
            $next_idx++;
        }

        // Do we have another?
        if (isset($sites[$next_idx])) {
            $action_id = ee()->db->select('action_id')
                ->where('class', 'Member')
                ->where('method', 'member_login')
                ->get('actions');

            // next site
            $next_qs = array(
                'ACT' => $action_id->row('action_id'),
                'RET' => $return,
                'cur' => $next_idx,
                'orig' => $orig_idx,
                'multi' => $login_state,
                'orig_site_id' => $orig_id,
            );

            $next_url = $sites[$next_idx] . '?' . http_build_query($next_qs);

            return ee()->functions->redirect($next_url);
        }
    }

    /**
     * Update online user stats
     */
    private function _update_online_user_stats()
    {
        if (
            ee()->config->item('enable_online_user_tracking') == 'n'
            or ee()->config->item('disable_all_tracking') == 'y'
        ) {
            return;
        }

        // Update stats
        $cutoff = ee()->localize->now - (15 * 60);
        $anon = (ee()->input->post('anon') == 1) ? '' : 'y';

        $in_forum = (ee()->input->get_post('FROM') == 'forum') ? 'y' : 'n';

        $escaped_ip = ee()->db->escape_str(ee()->input->ip_address());

        ee()->db->where('site_id', ee()->config->item('site_id'))
            ->where("(ip_address = '" . $escaped_ip . "' AND member_id = '0')", '', false)
            ->or_where('date < ', $cutoff)
            ->delete('online_users');

        $data = array(
            'member_id' => ee()->session->userdata('member_id'),
            'name' => (ee()->session->userdata('screen_name') == '') ? ee()->session->userdata('username') : ee()->session->userdata('screen_name'),
            'ip_address' => ee()->input->ip_address(),
            'in_forum' => $in_forum,
            'date' => ee()->localize->now,
            'anon' => $anon,
            'site_id' => ee()->config->item('site_id')
        );

        ee()->db->where('ip_address', ee()->input->ip_address())
            ->where('member_id', $data['member_id'])
            ->update('online_users', $data);
    }

    /**
     * Member Logout
     */
    public function member_logout()
    {
        // Handle our protected data if any. This contains our extra params.
        $protected = ee()->functions->handle_protected();

        // Determine where we need to return to in case of success or error.
        $return_link = ee()->functions->determine_return(true);
        $return_error_link = ee()->functions->determine_error_return();

        // If they are already logged out then send them away.
        if (ee()->session->userdata('member_id') === 0) {
            return ee()->functions->redirect($return_link);
        }

        // If this is a GET request, they're using the `path="logout"` tag so check the CSRF Token.
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $token = false;
            if (! $token) {
                $token = ee()->input->get('csrf_token');
            }
            if (! $token) {
                $token = ee()->input->get('XID');
            }

            if (! bool_config_item('disable_csrf_protection') && $token != CSRF_TOKEN) {
                return ee()->output->show_user_error('general', array(lang('not_authorized')));
            }
        }
        // Kill the session and cookies
        ee()->db->where('site_id', ee()->config->item('site_id'));
        ee()->db->where('ip_address', ee()->input->ip_address());
        ee()->db->where('member_id', ee()->session->userdata('member_id'));
        ee()->db->delete('online_users');

        ee()->session->destroy();

        ee()->input->delete_cookie('read_topics');

        $csrf_token = ee()->csrf->refresh_token();

        /* -------------------------------------------
        /* 'member_member_logout' hook.
        /*  - Perform additional actions after logout
        /*  - Added EE 1.6.1
        */
        ee()->extensions->call('member_member_logout');
        if (ee()->extensions->end_script === true) {
            return;
        }
        /*
        /* -------------------------------------------*/

        if (ee()->input->get_post('FROM') == 'forum' && bool_config_item('forum_is_installed')) {
            if (
                ee()->input->get_post('board_id') !== false
                && is_numeric(ee()->input->get_post('board_id'))
            ) {
                $query = ee()->db->select("board_forum_url, board_label")
                    ->where('board_id', (int) ee()->input->get_post('board_id'))
                    ->get('forum_boards');
            } else {
                $query = ee()->db->select('board_forum_url, board_label')
                    ->where('board_id', (int) 1)
                    ->get('forum_boards');
            }

            $forum_return_link = $query->row('board_forum_url') ;

            if (! empty($forum_return_link)) {
                $return_link = parse_config_variables($forum_return_link);
            }
        }

        return ee()->functions->redirect($return_link);
    }

    public function send_username()
    {
        // Handle our protected data if any. This contains our extra params.
        $protected = ee()->functions->handle_protected();

        // Determine where we need to return to in case of success or error.
        $return_success_link = ee()->functions->determine_return();
        $return_error_link = ee()->functions->determine_error_return();

        // Is user banned?
        if (ee()->session->userdata('is_banned') === true) {
            return ee()->output->show_user_error('general', array(lang('not_authorized')), '', $return_error_link);
        }

        // Error trapping
        if (! $address = ee()->input->post('email')) {
            return ee()->output->show_user_error('submission', array(lang('invalid_email_address')), '', $return_error_link);
        }

        ee()->load->helper('email');
        if (! valid_email($address)) {
            return ee()->output->show_user_error('submission', array(lang('invalid_email_address')), '', $return_error_link);
        }

        if (ee()->input->get_post('FROM') == 'forum' && bool_config_item('forum_is_installed')) {
            if (
                ee()->input->get_post('board_id') !== false
                && is_numeric(ee()->input->get_post('board_id'))
            ) {
                $query = ee()->db->select('board_forum_url, board_id, board_label')
                    ->where('board_id', (int) ee()->input->get_post('board_id'))
                    ->get('forum_boards');
            } else {
                $query = ee()->db->select('board_forum_url, board_id, board_label')
                    ->where('board_id', (int) 1)
                    ->get('forum_boards');
            }

            $return = parse_config_variables($query->row('board_forum_url'));
            $site_name = $query->row('board_label') ;
            $board_id = $query->row('board_id') ;
        } else {
            $site_name = stripslashes(ee()->config->item('site_name'));
            $return = ee()->config->item('site_url');
        }

        $forum_id = (ee()->input->get_post('FROM') == 'forum') ? '&r=f&board_id=' . $board_id : '';

        $address = strip_tags($address);

        $memberQuery = ee()->db->select('member_id, username, screen_name')
            ->where('email', $address)
            ->get('members');

        if ($memberQuery->num_rows() == 0) {
            // Build success message
            $data = array(
                'title' => lang('mbr_passwd_email_sent'),
                'heading' => lang('thank_you'),
                'content' => lang('forgotten_username_email_sent'),
                'link' => array($return, $site_name)
            );

            ee()->output->show_message($data);
        }

        $member_id = $memberQuery->row('member_id');
        $username = $memberQuery->row('username');
        $name = ($memberQuery->row('screen_name') == '') ? $memberQuery->row('username') : $memberQuery->row('screen_name');

        $template = ee()->functions->fetch_email_template('forgot_username_instructions');

        if (! empty($protected['email_template'])) {
            $email_template = ee()->TMPL->fetch_template_from_path($protected['email_template']);
        } else {
            $email_template = $template['data'];
        }

        // Check if we have a password reset subject param, otherwise, use the one from the default template.
        if (! empty($protected['email_subject'])) {
            $email_subject = $protected['email_subject'];
        } else {
            $email_subject = $template['title'];
        }

        // Build the email message
        $swap = array(
            'name' => $name,
            'username' => $username,
            'site_name' => $site_name,
            'site_url' => $return
        );

        // _var_swap calls string replace on $template[] for each key in
        // $swap.  If the key doesn't exist then no swapping happens.
        $email_subject = $this->_var_swap($email_subject, $swap);
        $email_msg = $this->_var_swap($email_template, $swap);

        // Instantiate the email class
        ee()->load->library('email');
        ee()->email->wordwrap = true;
        ee()->email->mailtype = ee()->config->item('mail_format');
        ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
        ee()->email->to($address);
        ee()->email->subject($email_subject);
        ee()->email->message($email_msg);

        if (! ee()->email->send()) {
            return ee()->output->show_user_error('submission', array(lang('error_sending_email')), '', $return_error_link);
        }

        // Build success message
        $data = array(
            'title' => lang('mbr_username_email_sent'),
            'heading' => lang('thank_you'),
            'content' => lang('forgotten_email_sent'),
            'link' => array($return, $site_name)
        );

        // If we have a success return link, go to that, otherwise, output the standard message.
        ee()->output->show_message($data, true, $return_success_link);
    }

    /**
     * Member Forgot Password Form
     *
     * Displays a form to the user asking for their e-mail address and posts
     * the results to Member_auth::send_reset_token().  If the user is logged
     * in, it sends them away.
     *
     * @param   string  pages to return back to
     */
    public function forgot_password($ret = '-3')
    {
        // If the user is logged in already, then send them away.  They have no
        // business here.
        if (ee()->session->userdata('member_id') !== 0) {
            return ee()->functions->redirect(ee()->functions->fetch_site_index());
        }

        $data = array(
            'id' => 'forgot_password_form',
            'hidden_fields' => array(
                'ACT' => ee()->functions->fetch_action_id('Member', 'send_reset_token'),
                'FROM' => ($this->in_forum == true) ? 'forum' : ''
            )
        );

        if ($this->in_forum === true) {
            $data['hidden_fields']['board_id'] = $this->board_id;
        }

        // keep this page out of the tracker so login/reset requests don't bounce back here
        ee()->session->do_not_track();

        $this->_set_page_title(lang('mbr_forgotten_password'));

        $forgot_form = $this->_load_element('forgot_form');

        // match {form_declaration} or {form_declaration form_class="foo"}
        // [0] => {form_declaration form_class="foo"}
        // [1] => form_declaration form_class="foo"
        // [2] =>  form_class="foo"
        // [3] => "
        // [4] => foo
        preg_match(
            "/" . LD . "(form_declaration" . "(\s+form_class\s*=\s*(\042|\047)([^\\3]*?)\\3)?)" . RD . "/s",
            $forgot_form,
            $match
        );

        if (empty($match)) {
            // don't even return the template because the form will not work since
            // the template does not contain a {form_declaration}
            return;
        }

        // check for the form_class variable from the template and add to $data
        if (isset($match['4'])) {
            $data['class'] = $match['4'];
        }

        return $this->_var_swap(
            $forgot_form,
            array(
                $match[1] => ee()->functions->form_declaration($data)
            )
        );
    }

    /**
     * E-mail Forgotten Password Reset Token to User
     *
     * Handler page for the forgotten password form.  Processes the e-mail
     * given us in the form, generates a token and then sends that token
     * to the given e-mail with a backlink to a location where the user
     * can set their password.  Expects to find the e-mail in `$_POST['email']`.
     *
     * @return void
     */
    public function send_reset_token()
    {
        // Handle our protected data if any. This contains our extra params.
        $protected = ee()->functions->handle_protected();

        // Determine where we need to return to in case of success or error.
        $return_success_link = ee()->functions->determine_return();
        $return_error_link = ee()->functions->determine_error_return();

        // if this user is logged in, then send them away.
        if (ee()->session->userdata('member_id') !== 0) {
            return ee()->functions->redirect($return_success_link);
        }

        // Is user banned?
        if (ee()->session->userdata('is_banned') === true) {
            return ee()->output->show_user_error('general', array(lang('not_authorized')), '', $return_error_link);
        }

        // Error trapping
        if (! $address = ee()->input->post('email')) {
            return ee()->output->show_user_error('submission', array(lang('invalid_email_address')), '', $return_error_link);
        }

        ee()->load->helper('email');
        if (! valid_email($address)) {
            return ee()->output->show_user_error('submission', array(lang('invalid_email_address')), '', $return_error_link);
        }

        if (ee()->input->get_post('FROM') == 'forum' && bool_config_item('forum_is_installed')) {
            if (
                ee()->input->get_post('board_id') !== false
                && is_numeric(ee()->input->get_post('board_id'))
            ) {
                $query = ee()->db->select('board_forum_url, board_id, board_label')
                    ->where('board_id', (int) ee()->input->get_post('board_id'))
                    ->get('forum_boards');
            } else {
                $query = ee()->db->select('board_forum_url, board_id, board_label')
                    ->where('board_id', (int) 1)
                    ->get('forum_boards');
            }

            $return = parse_config_variables($query->row('board_forum_url'));
            $site_name = $query->row('board_label') ;
            $board_id = $query->row('board_id') ;
        } else {
            $site_name = stripslashes(ee()->config->item('site_name'));
            $return = ee()->config->item('site_url');
        }

        $forum_id = (ee()->input->get_post('FROM') == 'forum') ? '&r=f&board_id=' . $board_id : '';

        $address = strip_tags($address);

        $memberQuery = ee()->db->select('member_id, username, screen_name')
            ->where('email', $address)
            ->get('members');

        if ($memberQuery->num_rows() == 0) {
            // Build success message
            $data = array(
                'title' => lang('mbr_passwd_email_sent'),
                'heading' => lang('thank_you'),
                'content' => lang('forgotten_email_sent'),
                'link' => array($return, $site_name)
            );

            ee()->output->show_message($data);
        }

        $member_id = $memberQuery->row('member_id');
        $username = $memberQuery->row('username');
        $name = ($memberQuery->row('screen_name') == '') ? $memberQuery->row('username') : $memberQuery->row('screen_name');

        // Kill old data from the reset_password field
        ee()->db->where('date <', $this->getTokenExpiration())
            ->delete('reset_password');

        // Check flood control
        $max_requests_in_a_day = 3;
        $requests = ee()->db->where('member_id', $member_id)
            ->count_all_results('reset_password');

        if ($requests >= $max_requests_in_a_day) {
            return ee()->output->show_user_error('submission', array(lang('password_reset_flood_lock')), '', $return_error_link);
        }

        // Create a new DB record with the temporary reset code
        $resetcode = ee()->functions->random('alnum', 8);
        $data = array('member_id' => $member_id, 'resetcode' => $resetcode, 'date' => ee()->localize->now);
        ee()->db->query(ee()->db->insert_string('exp_reset_password', $data));

        $template = ee()->functions->fetch_email_template('forgot_password_instructions');

        // Determine if they have a forgot password member template or if we should use the default.
        if (! empty($protected['password_reset_url'])) {
            $reset_url = trim(strtolower($protected['password_reset_url']));

            // Make sure it's an actual URL.
            if (substr($reset_url, 0, 4) !== 'http') {
                $reset_url = ee()->functions->fetch_site_index(0, 0) . '/' . $reset_url;
            }
        } else {
            $reset_url = reduce_double_slashes(ee()->functions->fetch_site_index(0, 0) . '/' . ee()->config->item('profile_trigger') . '/reset_password');
        }

        // Add the reset code and possible forum_id to the reset pass url.
        $reset_url .= '?id=' . $resetcode . $forum_id;

        if (! empty($protected['email_template'])) {
            $email_template = ee()->TMPL->fetch_template_and_parse_from_path($protected['email_template']);
        } else {
            $email_template = $template['data'];
        }

        // Check if we have a password reset subject param, otherwise, use the one from the default template.
        if (! empty($protected['email_subject'])) {
            $email_subject = $protected['email_subject'];
        } else {
            $email_subject = $template['title'];
        }

        // Build the email message
        $swap = array(
            'name' => $name,
            'username' => $username,
            'reset_url' => $reset_url,
            'site_name' => $site_name,
            'site_url' => $return
        );

        // _var_swap calls string replace on $template[] for each key in
        // $swap.  If the key doesn't exist then no swapping happens.
        $email_subject = $this->_var_swap($email_subject, $swap);
        $email_msg = $this->_var_swap($email_template, $swap);

        // Instantiate the email class
        ee()->load->library('email');
        ee()->email->wordwrap = true;
        ee()->email->mailtype = ee()->config->item('mail_format');
        ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
        ee()->email->to($address);
        ee()->email->subject($email_subject);
        ee()->email->message($email_msg);

        if (! ee()->email->send()) {
            return ee()->output->show_user_error('submission', array(lang('error_sending_email')), '', $return_error_link);
        }

        // Build success message
        $data = array(
            'title' => lang('mbr_passwd_email_sent'),
            'heading' => lang('thank_you'),
            'content' => lang('forgotten_email_sent'),
            'link' => array($return, $site_name)
        );

        // If we have a success return link, go to that, otherwise, output the standard message.
        ee()->output->show_message($data, true, $return_success_link);
    }

    /**
     * Reset Password Form Method
     *
     * If a user arrives at this page with a valid token in their $_GET array,
     * use that token to look up the associated member and then present them
     * with a form allowing them to change their password. After resetting the
     * password, send them back to their original location (either member/login)
     * or the forum's login page.
     *
     * @return string The HTML of the form to allow the user to reset their password.
     */
    public function reset_password()
    {
        // if the use is logged in, then send them away
        if (ee()->session->userdata('member_id') !== 0) {
            return ee()->functions->redirect(ee()->functions->fetch_site_index());
        }
        // If the user is banned, send them away.
        if (ee()->session->userdata('is_banned') === true) {
            return ee()->output->show_user_error('general', array(lang('not_authorized')));
        }

        // They didn't include their token.  Give em an error.
        if (! ($resetcode = ee()->input->get_post('id'))) {
            return ee()->output->show_user_error('submission', array(lang('mbr_no_reset_id')));
        }

        // Make sure the token is valid and belongs to a member.
        $member_id_query = ee()->db->select('member_id')
            ->where('resetcode', $resetcode)
            ->where('date >', $this->getTokenExpiration())
            ->get('reset_password');

        if ($member_id_query->num_rows() === 0) {
            return ee()->output->show_user_error('submission', array(lang('mbr_id_not_found')));
        }

        // Check to see whether we're in the forum or not.
        $in_forum = isset($_GET['r']) && $_GET['r'] == 'f';

        $data = array(
            'id' => 'reset_password_form',
            'hidden_fields' => array(
                'ACT' => ee()->functions->fetch_action_id('Member', 'process_reset_password'),
                'FROM' => ($in_forum == true) ? 'forum' : '',
                'resetcode' => $resetcode
            )
        );

        if ($in_forum === true) {
            $data['hidden_fields']['board_id'] = (int) $_GET['board_id'];
        }

        $this->_set_page_title(lang('mbr_reset_password'));

        // fetch the member for variables in the template
        $member = ee('Model')->get('Member', $member_id_query->row('member_id'))->first();

        $variables = [
            'username' => $member->username,
            'screen_name' => $member->screen_name,
            'form_declaration' => ee()->functions->form_declaration($data),
        ];

        return $this->_var_swap(
            $this->_load_element('reset_password_form'),
            $variables
        );
    }

    /**
     * Reset Password Processing Action
     *
     * Processing action to process a reset password.  Sent here by the form presented
     * to the user in `Member_auth::reset_password()`.  Process the form and return
     * the user to the appropriate login page.  Expects to find the contents of the
     * form in `$_POST`.
     *
     * @since 2.6
     */
    public function process_reset_password()
    {
        // Handle our protected data if any. This contains our extra params.
        $protected = ee()->functions->handle_protected();

        // Determine where we need to return to in case of success or error.
        $return_success_link = ee()->functions->determine_return();
        $return_error_link = ee()->functions->determine_error_return();

        // if the user is logged in, then send them away
        if (ee()->session->userdata('member_id') !== 0) {
            return ee()->functions->redirect(ee()->functions->fetch_site_index());
        }

        // If the user is banned, send them away.
        if (ee()->session->userdata('is_banned') === true) {
            return ee()->output->show_user_error('general', array(lang('not_authorized')), '', $return_error_link);
        }

        if (! ($resetcode = ee()->input->get_post('resetcode'))) {
            return ee()->output->show_user_error('submission', array(lang('mbr_no_reset_id')), '', $return_error_link);
        }

        // We'll use this in a couple of places to determine whether a token is still valid
        // or not.  Tokens expire after exactly 1 day.
        $expired = $this->getTokenExpiration();

        // Make sure the token is valid and belongs to a member.
        $member_id_query = ee()->db->select('r.member_id, m.username')
            ->from('reset_password r')
            ->join('members m', 'r.member_id = m.member_id')
            ->where([
                'r.resetcode' => $resetcode,
                'r.date >' => $expired
            ])
            ->limit(1)
            ->get();

        if ($member_id_query->num_rows() === 0) {
            return ee()->output->show_user_error('submission', array(lang('mbr_id_not_found')), '', $return_error_link);
        }

        // If we're here, the reset code was in the URL properly so make sure it's on the error_link
        // as the native EE backtracker doesn't append querystrings.
        if (! empty($return_error_link) && strpos($return_error_link, 'id=') === false) {
            $return_error_link .= '?id=' . $resetcode;
        }

        // Ensure the passwords match.
        if (! ($password = ee()->input->get_post('password'))) {
            return ee()->output->show_user_error('submission', array(lang('mbr_missing_password')), '', $return_error_link);
        }

        if (! ($password_confirm = ee()->input->get_post('password_confirm'))) {
            return ee()->output->show_user_error('submission', array(lang('mbr_missing_confirm')), '', $return_error_link);
        }

        // Validate the password. This will also
        // handle checking whether the password and its confirmation
        // match.

        $pw_data = array(
            'username' => $member_id_query->row('username'),
            'password' => $password,
            'password_confirm' => $password_confirm
        );

        $validationRules = [
            'password' => 'validPassword|passwordMatchesSecurityPolicy|matches[password_confirm]'
        ];

        $validationResult = ee('Validation')->make($validationRules)->validate($pw_data);

        if ($validationResult->isNotValid()) {
            $errors = [];
            foreach ($validationResult->getAllErrors() as $error) {
                $errors = array_merge($errors, array_values($error));
            }

            return ee()->output->show_user_error('submission', $errors, '', $return_error_link);
        }

        // Update the database with the new password.  Apply the appropriate salt first.
        ee()->load->library('auth');
        ee()->auth->update_password(
            $member_id_query->row('member_id'),
            $password
        );

        // Invalidate the old token.  While we're at it, may as well wipe out expired
        // tokens too, just to keep them from building up.
        ee()->db->where('date <', $expired)
            ->or_where('member_id', $member_id_query->row('member_id'))
            ->delete('reset_password');

        // If we can get their last URL from the tracker,
        // then we'll use it.
        if (isset(ee()->session->tracker[2])) {
            $seg = (ee()->session->tracker[2] != 'index') ? ee()->session->tracker[2] : '';
            $site_name = stripslashes(ee()->config->item('site_name'));
            $return = reduce_double_slashes(ee()->functions->fetch_site_index() . '/' . $seg);
        }
        // Otherwise, it's entirely possible they are clicking the e-mail link after
        // their session has expired.  In that case, the only information we have
        // about where they came from is in the POST data (where it came from the GET data).
        // Use it to get them as close as possible to where they started.
        elseif (ee()->input->get_post('FROM') == 'forum' && bool_config_item('forum_is_installed')) {
            $board_id = ee()->input->get_post('board_id');
            $board_id = ($board_id === false or ! is_numeric($board_id)) ? 1 : $board_id;

            $forum_query = ee()->db->select('board_forum_url, board_label')
                ->where('board_id', (int) $board_id)
                ->get('forum_boards');

            $site_name = $forum_query->row('board_label');
            $return = parse_config_variables($forum_query->row('board_forum_url'));
        } else {
            $site_name = stripslashes(ee()->config->item('site_name'));
            $return = ee()->functions->fetch_site_index();
        }

        // Build the success message that we'll show to the user.
        $data = array(
            'title' => lang('mbr_password_changed'),
            'heading' => lang('mbr_password_changed'),
            'content' => lang('mbr_successfully_changed_password'),
            'link' => array($return, $site_name), // The link to show them. In the form of (URL, Name)
            'redirect' => $return, // Redirect them to this URL...
            'rate' => '5' // ...after 5 seconds.

        );

        /* -------------------------------------------
        /* 'member_process_reset_password' hook.
        /*  - Additional processing after user resets password
        /*  - Added EE 2.9.3
        /*  - Member ID parameter added 4.0.0
        */
        if (ee()->extensions->active_hook('member_process_reset_password') === true) {
            $data = ee()->extensions->call('member_process_reset_password', $data, $member_id_query->row('member_id'));
            if (ee()->extensions->end_script === true) {
                return;
            }
        }
        /*
        /* -------------------------------------------*/

        ee()->output->show_message($data, true, $return_success_link);
    }

    /**
     * Creates a timestamp for use in determinig a token's expiration
     */
    private function getTokenExpiration()
    {
        return ee()->localize->now - (60 * 60); // One hour
    }
}
// END CLASS

// EOF
