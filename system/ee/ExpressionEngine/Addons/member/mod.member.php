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
 * Member Management Front-end Class
 */
class Member
{
    public $trigger = 'member';
    public $member_template = true;
    public $member_fields = [];
    public $theme_class = 'profile_theme';
    public $request = 'public_profile';
    public $no_menu = array(
        'public_profile', 'memberlist', 'do_member_search',
        'member_search', 'register', 'smileys', 'login',
        'unpw_update', 'email_console', 'send_email',
        'forgot_password', 'reset_password',
        'delete', 'member_mini_search', 'do_member_mini_search',
    );

    public $no_login = array(
        'public_profile', 'memberlist', 'do_member_search',
        'member_search', 'register', 'forgot_password', 'unpw_update',
        'reset_password'
    );

    public $id_override = array(
        'edit_subscriptions', 'memberlist', 'member_search',
        'browse_avatars', 'messages', 'unpw_update'
    );

    public $no_breadcrumb = array(
        'email_console', 'send_email', 'member_mini_search', 'do_member_mini_search'
    );

    public $simple_page = array(
        'email_console', 'send_email', 'smileys', 'member_mini_search', 'do_member_mini_search'
    );

    public $page_title = '';
    public $basepath = '';
    public $forum_path = '';
    public $image_url = '';
    public $theme_path = '';
    public $cur_id = '';
    public $uri_extra = '';
    public $return_data = '';
    public $javascript = '';
    public $head_extra = '';
    public $var_single = '';
    public $var_pair = '';
    public $var_cond = '';
    public $css_file_path = '';
    public $board_id = '';
    public $show_headings = true;
    public $in_forum = false;
    public $is_admin = false;
    public $breadcrumb = true;
    public $crumb_map = array(
        'profile' => 'your_control_panel',
        'delete' => 'mbr_delete',
        'reset_password' => 'mbr_reset_password',
        'forgot_password' => 'mbr_forgotten_password',
        'login' => 'mbr_login',
        'unpw_update' => 'settings_update',
        'register' => 'mbr_member_registration',
        'email' => 'mbr_email_member',
        'send_email' => 'mbr_send_email',
        'profile_main' => 'mbr_my_account',
        'edit_profile' => 'mbr_edit_your_profile',
        'edit_email' => 'email_settings',
        'edit_userpass' => 'username_and_password',
        'edit_localization' => 'localization_settings',
        'edit_subscriptions' => 'subscriptions',
        'edit_ignore_list' => 'ignore_list',
        'edit_notepad' => 'notepad',
        'edit_avatar' => 'edit_avatar',
        'edit_photo' => 'edit_photo',
        'edit_preferences' => 'edit_preferences',
        'update_preferences' => 'update_preferences',
        'upload_photo' => 'update_photo',
        'browse_avatars' => 'browse_avatars',
        'update_profile' => 'profile_updated',
        'update_email' => 'mbr_email_updated',
        'update_userpass' => 'username_and_password',
        'update_localization' => 'localization_settings',
        'update_subscriptions' => 'subscription_manager',
        'update_ignore_list' => 'ignore_list',
        'update_notepad' => 'notepad',
        'select_avatar' => 'update_avatar',
        'upload_avatar' => 'upload_avatar',
        'update_avatar' => 'update_avatar',
        'pm_view' => 'private_messages',
        'pm' => 'compose_message',
        'view_folder' => 'view_folder',
        'view_message' => 'view_message',
        'edit_signature' => 'edit_signature',
        'update_signature' => 'update_signature',
        'compose' => 'compose_message',
        'deleted' => 'deleted_messages',
        'folders' => 'edit_folders',
        'buddies' => 'buddy_list',
        'blocked' => 'blocked_list',
        'edit_folders' => 'edit_folders',
        'inbox' => 'view_folder',
        'edit_list' => 'edit_list',
        'send_message' => 'view_folder',
        'modify_messages' => 'private_messages',
        'bulletin_board' => 'bulletin_board',
        'send_bulletin' => 'send_bulletin',
        'sending_bulletin' => 'sending_bulletin'
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        ee()->lang->loadfile('myaccount');
        ee()->lang->loadfile('member');

        ee()->functions->template_type = 'webpage';

        if (isset(ee()->TMPL) && is_object(ee()->TMPL)) {
            $this->trigger = ee()->TMPL->fetch_param('profile_trigger', ee()->config->item('profile_trigger'));
            $this->member_template = false;
        } else {
            // For custom fields that use the template library
            ee()->load->library('template', null, 'TMPL');
            $this->trigger = ee()->config->item('profile_trigger');
        }

        if ($this->member_template == true && REQ != 'ACTION' && !ee('Request')->isPost()) {
            ee()->load->library('logger');
            ee()->logger->developer('Member profile templates are now legacy and not recommended to use. Please use regular templates and {exp:member:...} tags.', true, 60 * 60 * 24 * 30);

            if (!ee('Config')->getFile()->getBoolean('legacy_member_templates')) {
                ee()->logger->developer('Someone tried to access legacy member template, but those are not enabled in config.php', true, 60 * 60 * 24 * 30);

                return ee()->output->show_user_error('general', lang('legacy_member_templates_not_enabled'));
            }
        }
    }

    /**
     * Prep the Request String
     */
    public function _prep_request()
    {
        // Typcially the profile page URLs will be something like:
        //
        // index.php/member/123/
        // index.php/member/memberlist/
        // index.php/member/profile/
        // etc...
        //
        // The second segment will be assigned to the $this->request variable.
        // This determines what page is shown. Anything after that will normally
        // be an ID number, so we'll assign it to the $this->cur_id variable.

        $this->request = trim_slashes(ee()->uri->uri_string);

        if (false !== ($pos = strpos($this->request, $this->trigger . '/'))) {
            $this->request = substr($this->request, $pos);
        }

        if (preg_match("#/simple#", $this->request)) {
            $this->request = str_replace("/simple", '', $this->request);
            $this->show_headings = false;
        }

        if (! $this->member_template) {
            $this->request = str_replace($this->trigger . '/', '', $this->request);
        }

        if ($this->request == $this->trigger) {
            $this->request = '';
        } elseif (strpos($this->request, '/') !== false) {
            $xr = explode("/", $this->request);
            $this->request = str_replace(current($xr) . '/', '', $this->request);
        }

        // Determine the ID number, if any
        $this->cur_id = '';

        if (strpos($this->request, '/') !== false) {
            $x = explode("/", $this->request);

            if (count($x) > 2) {
                $this->request = $x[0];
                $this->cur_id = $x[1];
                $this->uri_extra = $x[2];
            } else {
                $this->request = $x[0];
                $this->cur_id = $x[1];
            }
        }

        // Is this a public profile request?
        // Public member profiles are found at:
        //
        // index.php/member/123/
        //
        // Since the second segment contains a number instead of the
        // normal text string we know it's a public profile request.
        // We'll do a little reassignment...

        if (is_numeric($this->request)) {
            $this->cur_id = $this->request;
            $this->request = 'public_profile';
        }

        if ($this->request == '') {
            $this->request = 'public_profile';
        }

        // Disable the full page view
        if (in_array($this->request, $this->simple_page)) {
            $this->show_headings = false;
        }

        if (in_array($this->request, $this->no_breadcrumb)) {
            $this->breadcrumb = false;
        }

        // Validate ID number
        // The $this->cur_id variable can only contain a number.
        // There are a few exceptions like the memberlist page and the
        // subscriptions page

        if (
            ! in_array($this->request, $this->id_override) &&
            $this->cur_id != '' && ! is_numeric($this->cur_id)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Run the Member Class
     */
    public function manager()
    {
        // Prep the request
        if (! $this->_prep_request()) {
            $this->_show_404_template();
        }

        // -------------------------------------------
        // 'member_manager' hook.
        //  - Seize control over any Member Module user side request
        //  - Added: 1.5.2
        //
        if (ee()->extensions->active_hook('member_manager') === true) {
            $edata = ee()->extensions->call('member_manager', $this);
            if (ee()->extensions->end_script === true) {
                return $edata;
            }
        }
        //
        // -------------------------------------------

        // Is the user logged in?
        if (
            $this->request != 'login' &&
            ! in_array($this->request, $this->no_login) &&
            ee()->session->userdata('member_id') == 0
        ) {
            return $this->_final_prep($this->profile_login_form('self'));
        }

        // Left-side Menu
        $left = (! in_array($this->request, $this->no_menu)) ? $this->profile_menu() : '';

        // Validate the request
        $methods = array(
            'public_profile',
            'memberlist',
            'member_search',
            'do_member_search',
            'login',
            'unpw_update',
            'register',
            'profile',
            'edit_preferences',
            'update_preferences',
            'edit_profile',
            'update_profile',
            'edit_email',
            'update_email',
            'edit_userpass',
            'update_userpass',
            'edit_localization',
            'update_localization',
            'edit_notepad',
            'update_notepad',
            'edit_signature',
            'update_signature',
            'edit_avatar',
            'browse_avatars',
            'select_avatar',
            'upload_avatar',
            'edit_photo',
            'upload_photo',
            'edit_subscriptions',
            'update_subscriptions',
            'edit_ignore_list',
            'update_ignore_list',
            'member_mini_search',
            'do_member_mini_search',
            'email_console',
            'send_email',
            'forgot_password',
            'reset_password',
            'smileys',
            'messages',
            'delete'
        );

        if (! in_array($this->request, $methods)) {
            $this->_show_404_template();
        }

        // Call the requested function
        if ($this->request == 'profile') {
            $this->request = 'profile_main';
        }
        if ($this->request == 'register') {
            $this->request = 'registration_form';
        }
        if ($this->cur_id == 'member_search') {
            $left = '';
            $this->breadcrumb = false;
            $this->show_headings = false;
        }
        if ($this->cur_id == 'do_member_search') {
            $left = '';
            $this->breadcrumb = false;
            $this->show_headings = false;
        }
        if ($this->cur_id == 'buddy_search') {
            $left = '';
            $this->breadcrumb = false;
            $this->show_headings = false;
        }
        if ($this->cur_id == 'do_buddy_search') {
            $left = '';
            $this->breadcrumb = false;
            $this->show_headings = false;
        }

        $function = $this->request;

        if (in_array($function, array('upload_photo', 'upload_avatar', 'upload_signature_image', '_upload_image'))) {
            require_once PATH_ADDONS . 'member/mod.member_images.php';

            $MI = new Member_images();

            foreach (get_object_vars($this) as $key => $value) {
                $MI->{$key} = $value;
            }

            $content = $MI->$function();
        } else {
            $content = $this->$function();
        }

        if ($this->cur_id == 'edit_folders') {
            $left = $this->profile_menu();
        }
        if ($this->cur_id == 'send_message') {
            $left = $this->profile_menu();
        }

        // Parse the template the template
        if ($left == '') {
            $out = $this->_var_swap(
                $this->_load_element('basic_profile'),
                array(
                    'include:content' => $content
                )
            );
        } else {
            $out = $this->_var_swap(
                $this->_load_element('full_profile'),
                array(
                    'include:menu' => $left,
                    'include:content' => $content
                )
            );
        }

        // Output the finalized request
        return $this->_final_prep($out);
    }

    /**
     * Private Messages
     */
    public function messages()
    {
        if (! class_exists('EE_Messages')) {
            require APPPATH . 'libraries/Messages.php';
        }

        if (! EE_Messages::can_send_pm()) {
            return $this->profile_main();
        }

        $MESS = new EE_Messages();
        $MESS->base_url = $this->_member_path('messages') . '/';
        $MESS->allegiance = 'user';
        $MESS->theme_path = $this->theme_path;
        $MESS->request = $this->cur_id;
        $MESS->cur_id = $this->uri_extra;
        $MESS->MS = & $this;
        $MESS->manager();

        $this->page_title = $MESS->title;
        $this->head_extra = $MESS->header_javascript;

        return $MESS->return_data;
    }

    /**
     * Member Profile - Menu
     */
    public function profile_menu()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->profile_menu();
    }

    /**
     * Private Messages - Menu
     */
    public function pm_menu()
    {
        if (! class_exists('EE_Messages')) {
            require APPPATH . 'libraries/Messages.php';
        }

        if (! EE_Messages::can_send_pm()) {
            return '';
        }

        $MESS = new EE_Messages();
        $MESS->base_url = $this->_member_path('messages');
        $MESS->allegiance = 'user';
        $MESS->theme_path = $this->theme_path;
        $MESS->MS = & $this;

        $MESS->create_menu();

        return $MESS->menu;
    }

    /**
     * Member Profile Main Page
     */
    public function profile_main()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->profile_main();
    }

    /**
     * Member Public Profile
     */
    public function public_profile()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->public_profile();
    }

    /**
     * Login Page
     */
    public function profile_login_form($return = '-2')
    {
        if (! class_exists('Member_auth')) {
            require PATH_ADDONS . 'member/mod.member_auth.php';
        }

        $MA = new Member_auth();

        foreach (get_object_vars($this) as $key => $value) {
            $MA->{$key} = $value;
        }

        return $MA->profile_login_form($return);
    }

    /**
     * MFA links, directly available
     *
     * @return string
     */
    public function mfa_links()
    {
        if (ee()->session->userdata('member_id') == 0) {
            return ee()->TMPL->no_results();
        }

        $data = [
            'enable_mfa_link' => '',
            'disable_mfa_link' => '',
        ];

        if (ee('pro:Access')->hasRequiredLicense() && (ee()->config->item('enable_mfa') === false || ee()->config->item('enable_mfa') === 'y')) {
            $return = ee()->TMPL->fetch_param('return', ee()->uri->uri_string);
            if (ee()->session->userdata('mfa_enabled') == true) {
                $data['disable_mfa_link'] = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . ee()->functions->fetch_action_id('Pro', 'disableMfa') . AMP . 'RET=' . $return;
            }
            if (ee()->session->userdata('mfa_enabled') == false) {
                $data['enable_mfa_link'] = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . ee()->functions->fetch_action_id('Pro', 'enableMfa') . AMP . 'RET=' . $return;
            }
        }

        return ee()->functions->insert_action_ids(ee()->TMPL->parse_variables_row(ee()->TMPL->tagdata, $data));
    }

    /**
     * Member Profile Edit Page
     */
    public function edit_profile()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->edit_profile();
    }

    /**
     * Profile Update
     */
    public function update_profile()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->update_profile();
    }

    /**
     * Forum Preferences
     */
    public function edit_preferences()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->edit_preferences();
    }

    /**
     * Update Preferences
     */
    public function update_preferences()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->update_preferences();
    }

    /**
     * Email Settings
     */
    public function edit_email()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->edit_email();
    }

    /**
     * Email Update
     */
    public function update_email()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->update_email();
    }

    /**
     * Username/Password Preferences
     */
    public function edit_userpass()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->edit_userpass();
    }

    /**
     * Username/Password Update
     */
    public function update_userpass()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->update_userpass();
    }

    /**
     * Localization Edit Form
     */
    public function edit_localization()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->edit_localization();
    }

    /**
     * Update Localization Prefs
     */
    public function update_localization()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->update_localization();
    }

    /**
     * Signature Edit Form
     */
    public function edit_signature()
    {
        if (! class_exists('Member_images')) {
            require PATH_ADDONS . 'member/mod.member_images.php';
        }

        $MI = new Member_images();

        foreach (get_object_vars($this) as $key => $value) {
            $MI->{$key} = $value;
        }

        return $MI->edit_signature();
    }

    /**
     * Update Signature
     */
    public function update_signature()
    {
        if (! class_exists('Member_images')) {
            require PATH_ADDONS . 'member/mod.member_images.php';
        }

        $MI = new Member_images();

        foreach (get_object_vars($this) as $key => $value) {
            $MI->{$key} = $value;
        }

        return $MI->update_signature();
    }

    /**
     * Avatar Edit Form
     */
    public function edit_avatar()
    {
        if (! class_exists('Member_images')) {
            require PATH_ADDONS . 'member/mod.member_images.php';
        }

        $MI = new Member_images();

        foreach (get_object_vars($this) as $key => $value) {
            $MI->{$key} = $value;
        }

        return $MI->edit_avatar();
    }

    /**
     * Browse Avatars
     */
    public function browse_avatars()
    {
        if (! class_exists('Member_images')) {
            require PATH_ADDONS . 'member/mod.member_images.php';
        }

        $MI = new Member_images();

        foreach (get_object_vars($this) as $key => $value) {
            $MI->{$key} = $value;
        }

        return $MI->browse_avatars();
    }

    /**
     * Select Avatar From Library
     */
    public function select_avatar()
    {
        if (! class_exists('Member_images')) {
            require PATH_ADDONS . 'member/mod.member_images.php';
        }

        $MI = new Member_images();

        foreach (get_object_vars($this) as $key => $value) {
            $MI->{$key} = $value;
        }

        return $MI->select_avatar();
    }

    /**
     * Upload Avatar
     */
    public function upload_avatar()
    {
        if (! class_exists('Member_images')) {
            require PATH_ADDONS . 'member/mod.member_images.php';
        }

        $MI = new Member_images();

        foreach (get_object_vars($this) as $key => $value) {
            $MI->{$key} = $value;
        }

        return $MI->upload_avatar();
    }

    /**
     * Photo Edit Form
     */
    public function edit_photo()
    {
        if (! class_exists('Member_images')) {
            require PATH_ADDONS . 'member/mod.member_images.php';
        }

        $MI = new Member_images();

        foreach (get_object_vars($this) as $key => $value) {
            $MI->{$key} = $value;
        }

        return $MI->edit_photo();
    }

    /**
     * Notepad Edit Form
     */
    public function edit_notepad()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->edit_notepad();
    }

    /**
     * Update Notepad
     */
    public function update_notepad()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->update_notepad();
    }

    /**
     * Member Login
     */
    public function member_login()
    {
        if (! class_exists('Member_auth')) {
            require PATH_ADDONS . 'member/mod.member_auth.php';
        }

        $MA = new Member_auth();

        foreach (get_object_vars($this) as $key => $value) {
            $MA->{$key} = $value;
        }

        $MA->member_login();
    }

    /**
     * Manual Logout Form
     *
     * This lets users create a stand-alone logout form in any template
     */
    public function logout_form()
    {
        // Create form
        $data['hidden_fields'] = array(
            'ACT' => ee()->functions->fetch_action_id('Member', 'member_logout'),
            'RET' => (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "") ? ee()->TMPL->fetch_param('return') : '-2'
        );

        if (ee()->TMPL->fetch_param('form_name') && ee()->TMPL->fetch_param('form_name') != "") {
            $data['name'] = ee()->TMPL->fetch_param('form_name');
        }

        $data['id'] = ee()->TMPL->form_id;

        $data['class'] = ee()->TMPL->form_class;

        $data['action'] = ee()->TMPL->fetch_param('action');

        $res = ee()->functions->form_declaration($data);

        $res .= stripslashes(ee()->TMPL->tagdata);

        $res .= "</form>";

        return $res;
    }

    /**
     * Member Logout
     */
    public function member_logout()
    {
        if (! class_exists('Member_auth')) {
            require PATH_ADDONS . 'member/mod.member_auth.php';
        }

        $MA = new Member_auth();

        foreach (get_object_vars($this) as $key => $value) {
            $MA->{$key} = $value;
        }

        $MA->member_logout();
    }

    /**
     * Manual Forgot Password Form
     *
     * This lets users create a stand-alone form in any template
     */
    public function forgot_username_form()
    {
        // Create form
        $data['hidden_fields'] = array(
            'ACT' => ee()->functions->fetch_action_id('Member', 'send_username'),
            'RET' => (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "") ? ee()->TMPL->fetch_param('return') : '-1',
            'P' => ee()->functions->get_protected_form_params(array(
                'email_subject' => ee()->TMPL->fetch_param('email_subject'),
                'email_template' => ee()->TMPL->fetch_param('email_template')
            ))
        );

        if (ee()->TMPL->fetch_param('form_name') && ee()->TMPL->fetch_param('form_name') != "") {
            $data['name'] = ee()->TMPL->fetch_param('form_name');
        }

        $data['id'] = ee()->TMPL->form_id;

        $data['class'] = ee()->TMPL->form_class;

        $data['action'] = ee()->TMPL->fetch_param('action');

        $res = ee()->functions->form_declaration($data);

        $res .= stripslashes(ee()->TMPL->tagdata);

        $res .= "</form>";

        return $res;
    }

    public function send_username()
    {
        if (! class_exists('Member_auth')) {
            require PATH_ADDONS . 'member/mod.member_auth.php';
        }

        $MA = new Member_auth();

        foreach (get_object_vars($this) as $key => $value) {
            $MA->{$key} = $value;
        }

        return $MA->send_username();
    }

    /**
     * Manual Forgot Password Form
     *
     * This lets users create a stand-alone form in any template
     */
    public function forgot_password_form()
    {
        // Create form
        $data['hidden_fields'] = array(
            'ACT' => ee()->functions->fetch_action_id('Member', 'send_reset_token'),
            'RET' => (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "") ? ee()->TMPL->fetch_param('return') : '-1',
            'P' => ee()->functions->get_protected_form_params(array(
                'password_reset_url' => ee()->TMPL->fetch_param('password_reset_url'),
                'email_subject' => ee()->TMPL->fetch_param('email_subject'),
                'email_template' => ee()->TMPL->fetch_param('email_template')
            ))
        );

        if (ee()->TMPL->fetch_param('form_name') && ee()->TMPL->fetch_param('form_name') != "") {
            $data['name'] = ee()->TMPL->fetch_param('form_name');
        }

        $data['id'] = ee()->TMPL->form_id;

        $data['class'] = ee()->TMPL->form_class;

        $data['action'] = ee()->TMPL->fetch_param('action');

        $res = ee()->functions->form_declaration($data);

        $res .= stripslashes(ee()->TMPL->tagdata);

        $res .= "</form>";

        return $res;
    }

    /**
     * Member Forgot Password Form
     */
    public function forgot_password($ret = '-3')
    {
        if (! class_exists('Member_auth')) {
            require PATH_ADDONS . 'member/mod.member_auth.php';
        }

        $MA = new Member_auth();

        foreach (get_object_vars($this) as $key => $value) {
            $MA->{$key} = $value;
        }

        $this->_set_page_title(lang('mbr_forgotten_password'));

        return $MA->forgot_password($ret);
    }

    /**
     * Retreive Forgotten Password
     */
    public function send_reset_token()
    {
        if (! class_exists('Member_auth')) {
            require PATH_ADDONS . 'member/mod.member_auth.php';
        }

        $MA = new Member_auth();

        foreach (get_object_vars($this) as $key => $value) {
            $MA->{$key} = $value;
        }

        $MA->send_reset_token();
    }

    public function reset_password_form()
    {
        // Handle our protected data if any. This contains our extra params.
        $protected = ee()->functions->handle_protected();

        // Determine where we need to return to in case of success or error.
        $return_success_link = ee()->functions->determine_return();
        $return_error_link = ee()->functions->determine_error_return();

        // If the user is banned, send them away.
        if (ee()->session->userdata('is_banned') === true) {
            return ee()->output->show_user_error('general', array(lang('not_authorized')), '', $return_error_link);
        }

        // They didn't include their token.  Give em an error.
        if (! ($resetcode = ee()->input->get_post('id'))) {
            return ee()->output->show_user_error('submission', array(lang('mbr_no_reset_id')), '', $return_error_link);
        }

        // Make sure the token is valid and belongs to a member.
        $member_id_query = ee()->db->select('member_id')
            ->where('resetcode', $resetcode)
            ->where('date >', (ee()->localize->now - (60 * 60)))
            ->get('reset_password');

        if ($member_id_query->num_rows() === 0) {
            return ee()->output->show_user_error('submission', array(lang('mbr_id_not_found')), '', $return_error_link);
        }

        // Check to see whether we're in the forum or not.
        $in_forum = isset($_GET['r']) && $_GET['r'] == 'f';

        // Create form
        $data['hidden_fields'] = array(
            'ACT' => ee()->functions->fetch_action_id('Member', 'process_reset_password'),
            'RET' => (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "") ? ee()->TMPL->fetch_param('return') : '',
            'FROM' => ($in_forum == true) ? 'forum' : '',
            'P' => ee()->functions->get_protected_form_params(),
            'resetcode' => $resetcode
        );

        if ($in_forum === true) {
            $data['hidden_fields']['board_id'] = (int) $_GET['board_id'];
        }

        if (ee()->TMPL->fetch_param('form_name') && ee()->TMPL->fetch_param('form_name') != "") {
            $data['name'] = ee()->TMPL->fetch_param('form_name');
        }

        $data['id'] = ee()->TMPL->form_id;

        $data['class'] = ee()->TMPL->form_class;

        $data['action'] = ee()->TMPL->fetch_param('action');

        $res = ee()->functions->form_declaration($data);

        $res .= stripslashes(ee()->TMPL->tagdata);

        $res .= "</form>";

        return $res;
    }

    /**
     * Reset the user's password
     */
    public function reset_password()
    {
        if (! class_exists('Member_auth')) {
            require PATH_ADDONS . 'member/mod.member_auth.php';
        }

        $MA = new Member_auth();

        foreach (get_object_vars($this) as $key => $value) {
            $MA->{$key} = $value;
        }

        return $MA->reset_password();
    }

    /**
     *
     */
    public function process_reset_password()
    {
        if (! class_exists('Member_auth')) {
            require PATH_ADDONS . 'member/mod.member_auth.php';
        }

        $MA = new Member_auth();

        foreach (get_object_vars($this) as $key => $value) {
            $MA->{$key} = $value;
        }

        return $MA->process_reset_password();
    }

    /**
     * Subscriptions Edit Form
     */
    public function edit_subscriptions()
    {
        if (! class_exists('Member_subscriptions')) {
            require PATH_ADDONS . 'member/mod.member_subscriptions.php';
        }

        $MS = new Member_subscriptions();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->edit_subscriptions();
    }

    /**
     * Update Subscriptions
     */
    public function update_subscriptions()
    {
        if (! class_exists('Member_subscriptions')) {
            require PATH_ADDONS . 'member/mod.member_subscriptions.php';
        }

        $MS = new Member_subscriptions();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->update_subscriptions();
    }

    /**
     * Edit Ignore List Form
     */
    public function edit_ignore_list()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->edit_ignore_list();
    }

    /**
     * Update Ignore List
     */
    public function update_ignore_list()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->update_ignore_list();
    }

    /**
     * Member Mini Search
     */
    public function member_mini_search()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        $this->_set_page_title(ee()->lang->line('member_search'));

        return $MS->member_mini_search();
    }

    /**
     * Do Member Mini Search
     */
    public function do_member_mini_search()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        $this->_set_page_title(ee()->lang->line('member_search'));

        return $MS->do_member_mini_search();
    }

    /**
     * Member Registration Form
     */
    public function registration_form()
    {
        if (! class_exists('Member_register')) {
            require PATH_ADDONS . 'member/mod.member_register.php';
        }

        $MR = new Member_register();

        foreach (get_object_vars($this) as $key => $value) {
            $MR->{$key} = $value;
        }

        $this->_set_page_title(lang('member_registration'));

        return $MR->registration_form();
    }

    /**
     * ReCaptcha Check
     * Checks to see if the ReCaptcha Score was valid and strong enough.
     */
    public function recaptcha_check()
    {
        // Make the POST request
        $data = [
            'secret' => ee()->config->item('recaptcha_site_secret'),
            'response' => ee()->input->get_post('rec'),
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($curl);
        $result = json_decode($response, true);

        // Mostly random string
        $string = bin2hex(openssl_random_pseudo_bytes(10));

        $captcha = ee('Model')->make('Captcha');
        $captcha->date = ee()->localize->now;
        $captcha->ip_address = ee()->input->ip_address();
        $captcha->word = $string;
        $captcha->save();

        if ($result['success'] !== true || $result['score'] < ee()->config->item('recaptcha_score_threshold')) {
            $string = "failed";
        }

        return ee()->output->send_ajax_response(['success' => $result['success'], 'code' => $string]);
    }

    /**
     * Register Member
     */
    public function register_member()
    {
        if (! class_exists('Member_register')) {
            require PATH_ADDONS . 'member/mod.member_register.php';
        }

        $MR = new Member_register();

        foreach (get_object_vars($this) as $key => $value) {
            $MR->{$key} = $value;
        }

        $MR->register_member();
    }

    /**
     * Member Self-Activation
     */
    public function activate_member()
    {
        if (! class_exists('Member_register')) {
            require PATH_ADDONS . 'member/mod.member_register.php';
        }

        $MR = new Member_register();

        foreach (get_object_vars($this) as $key => $value) {
            $MR->{$key} = $value;
        }

        $MR->activate_member();
    }

    /**
     * Delete Page
     */
    public function delete()
    {
        return $this->confirm_delete_form();
    }

    /**
     * Self-delete confirmation form
     */
    public function confirm_delete_form()
    {
        if (! ee('Permission')->can('delete_self')) {
            return ee()->output->show_user_error('general', ee()->lang->line('cannot_delete_self'));
        } else {
            $delete_form = $this->_load_element('delete_confirmation_form');

            $data['hidden_fields']['ACT'] = ee()->functions->fetch_action_id('Member', 'member_delete');
            $data['onsubmit'] = "if( ! confirm('{lang:final_delete_confirm}')) return false;";
            $data['id'] = 'member_delete_form';

            $this->_set_page_title(ee()->lang->line('member_delete'));

            return $this->_var_swap($delete_form, array('form_declaration' => ee()->functions->form_declaration($data)));
        }
    }

    /**
     * Member self-delete
     */
    public function member_delete()
    {
        // Make sure they got here via a form
        if (! ee()->input->post('ACT')) {
            // No output for you, Mr. URL Hax0r
            return false;
        }

        ee()->lang->loadfile('login');

        // No sneakiness - we'll do this in case the site administrator
        // has foolishly turned off secure forms and some monkey is
        // trying to delete their account from an off-site form or
        // after logging out.

        if (
            ee()->session->userdata('member_id') == 0 or
            ! ee('Permission')->can('delete_self')
        ) {
            return ee()->output->show_user_error('general', ee()->lang->line('not_authorized'));
        }

        // If the user is a SuperAdmin, then no deletion
        if (ee('Permission')->isSuperAdmin()) {
            return ee()->output->show_user_error('general', ee()->lang->line('cannot_delete_super_admin'));
        }

        // Is IP and User Agent required for login?  Then, same here.
        if (ee()->config->item('require_ip_for_login') == 'y') {
            if (ee()->session->userdata('ip_address') == '' or
                ee()->session->userdata('user_agent') == '') {
                return ee()->output->show_user_error('general', ee()->lang->line('unauthorized_request'));
            }
        }

        // Check password lockout status
        if (ee()->session->check_password_lockout(ee()->session->userdata('username')) === true) {
            ee()->lang->loadfile('login');

            return ee()->output->show_user_error(
                'general',
                sprintf(lang('password_lockout_in_effect'), ee()->config->item('password_lockout_interval'))
            );
        }

        // Are you who you say you are, or someone sitting at someone
        // else's computer being mean?!
        ee()->load->library('auth');

        if (
            ! ee()->auth->authenticate_id(
                ee()->session->userdata('member_id'),
                ee()->input->post('password')
            )
        ) {
            ee()->session->save_password_lockout(ee()->session->userdata('username'));

            return ee()->output->show_user_error('general', ee()->lang->line('invalid_pw'));
        }

        // No turning back, get to deletin'!
        ee('Model')->get('Member', ee()->session->userdata('member_id'))->delete();

        // Email notification recipients
        if (ee()->session->userdata('mbr_delete_notify_emails') != '') {
            $notify_address = ee()->session->userdata('mbr_delete_notify_emails');

            $swap = array(
                'name' => ee()->session->userdata('screen_name'),
                'email' => ee()->session->userdata('email'),
                'site_name' => stripslashes(ee()->config->item('site_name'))
            );

            $email_subject = ee()->functions->var_swap(ee()->lang->line('mbr_delete_notify_title'), $swap);
            $email_msg = ee()->functions->var_swap(ee()->lang->line('mbr_delete_notify_message'), $swap);

            // No notification for the user themselves, if they're in the list
            if (strpos($notify_address, ee()->session->userdata('email')) !== false) {
                $notify_address = str_replace(ee()->session->userdata('email'), "", $notify_address);
            }

            // Remove multiple commas
            $notify_address = reduce_multiples($notify_address, ',', true);

            if ($notify_address != '') {
                // Send email
                ee()->load->library('email');

                // Load the text helper
                ee()->load->helper('text');

                foreach (explode(',', $notify_address) as $addy) {
                    ee()->email->EE_initialize();
                    ee()->email->wordwrap = false;
                    ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
                    ee()->email->to($addy);
                    ee()->email->reply_to(ee()->config->item('webmaster_email'));
                    ee()->email->subject($email_subject);
                    ee()->email->message(entities_to_ascii($email_msg));
                    ee()->email->send();
                }
            }
        }

        ee()->db->where('session_id', ee()->session->userdata('session_id'))
            ->delete('sessions');

        ee()->input->delete_cookie(ee()->session->c_session);
        ee()->input->delete_cookie(ee()->session->c_anon);
        ee()->input->delete_cookie('read_topics');
        ee()->input->delete_cookie('tracker');

        // Build Success Message
        $url = ee()->config->item('site_url');
        $name = stripslashes(ee()->config->item('site_name'));

        $data = array('title' => ee()->lang->line('mbr_delete'),
            'heading' => ee()->lang->line('thank_you'),
            'content' => ee()->lang->line('mbr_account_deleted'),
            'redirect' => '',
            'link' => array($url, $name)
        );

        ee()->output->show_message($data);
    }

    /**
     * Login Page
     */
    public function login()
    {
        return $this->profile_login_form();
    }

    /**
     * Manual Login Form
     *
     * This lets users create a stand-alone login form in any template
     */
    public function login_form()
    {
        if (ee()->config->item('website_session_type') != 'c') {
            ee()->TMPL->tagdata = preg_replace("/{if\s+auto_login}.*?{" . '\/' . "if}/s", '', ee()->TMPL->tagdata);
        } else {
            ee()->TMPL->tagdata = preg_replace("/{if\s+auto_login}(.*?){" . '\/' . "if}/s", "\\1", ee()->TMPL->tagdata);
        }

        // Create form
        $data['hidden_fields'] = array(
            'ACT' => ee()->functions->fetch_action_id('Member', 'member_login'),
            'RET' => (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "") ? ee()->TMPL->fetch_param('return') : '-2'
        );

        if (
            ee()->TMPL->fetch_param('name') !== false &&
            preg_match("#^[a-zA-Z0-9_\-]+$#i", ee()->TMPL->fetch_param('name'), $match)
        ) {
            $data['name'] = ee()->TMPL->fetch_param('name');
            ee()->TMPL->log_item('Member Login Form:  The \'name\' parameter has been deprecated.  Please use form_name');
        } elseif (ee()->TMPL->fetch_param('form_name') && ee()->TMPL->fetch_param('form_name') != "") {
            $data['name'] = ee()->TMPL->fetch_param('form_name');
        }

        if (
            ee()->TMPL->fetch_param('id') !== false &&
            preg_match("#^[a-zA-Z0-9_\-]+$#i", ee()->TMPL->fetch_param('id'))
        ) {
            $data['id'] = ee()->TMPL->fetch_param('id');
            ee()->TMPL->log_item('Member Login Form:  The \'id\' parameter has been deprecated.  Please use form_id');
        } else {
            $data['id'] = ee()->TMPL->form_id;
        }

        $data['class'] = ee()->TMPL->form_class;

        $data['action'] = ee()->TMPL->fetch_param('action');

        $res = ee()->functions->form_declaration($data);

        $res .= stripslashes(ee()->TMPL->tagdata);

        $res .= "</form>";

        return $res;
    }

    /**
     * Username/password update
     */
    public function unpw_update()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        return $MS->unpw_update();
    }

    /**
     * Update the username/password
     */
    public function update_un_pw()
    {
        if (! class_exists('Member_settings')) {
            require PATH_ADDONS . 'member/mod.member_settings.php';
        }

        $MS = new Member_settings();

        foreach (get_object_vars($this) as $key => $value) {
            $MS->{$key} = $value;
        }

        $MS->update_un_pw();
    }

    /**
     * Member Email Form
     */
    public function email_console()
    {
        if (! class_exists('Member_memberlist')) {
            require PATH_ADDONS . 'member/mod.member_memberlist.php';
        }

        $MM = new Member_memberlist();

        foreach (get_object_vars($this) as $key => $value) {
            $MM->{$key} = $value;
        }

        return $MM->email_console();
    }

    /**
     * Send Member Email
     */
    public function send_email()
    {
        if (! class_exists('Member_memberlist')) {
            require PATH_ADDONS . 'member/mod.member_memberlist.php';
        }

        $MM = new Member_memberlist();

        foreach (get_object_vars($this) as $key => $value) {
            $MM->{$key} = $value;
        }

        return $MM->send_email();
    }

    /**
     * Member List
     */
    public function memberlist()
    {
        if (! class_exists('Member_memberlist')) {
            require PATH_ADDONS . 'member/mod.member_memberlist.php';
        }

        $MM = new Member_memberlist();

        foreach (get_object_vars($this) as $key => $value) {
            $MM->{$key} = $value;
        }

        return $MM->memberlist();
    }

    /**
     * Member Search Form
     *
     * This lets users create a stand-alone form in any template
     */
    public function member_search_form()
    {
        $result_page = ee()->TMPL->fetch_param('result_page');

        if (!empty($result_page) && substr($result_page, 0, 4) !== 'http' && substr($result_page, 0, 1) !== '/') {
            $result_page = '/' . $result_page;
        }

        // Create form
        $data['hidden_fields'] = array(
            'ACT' => ee()->functions->fetch_action_id('Member', 'do_member_search'),
            'RET' => (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "") ? ee()->TMPL->fetch_param('return') : '-1',
            'P' => ee()->functions->get_protected_form_params(array(
                'result_page' => $result_page,
            ))
        );

        if (ee()->TMPL->fetch_param('form_name') && ee()->TMPL->fetch_param('form_name') != "") {
            $data['name'] = ee()->TMPL->fetch_param('form_name');
        }

        $data['id'] = ee()->TMPL->form_id;

        $data['class'] = ee()->TMPL->form_class;

        // Use the `result_page` as our action. If empty, it'll default to the ACT URL.
        $data['action'] = (ee()->TMPL->fetch_param('result_page') && ee()->TMPL->fetch_param('result_page') != "") ? strtolower(ee()->TMPL->fetch_param('result_page')) : '';

        // If the action is relative, make sure it has a leading slash so we don't append it to the current url.
        if (!empty($data['action']) && substr($data['action'], 0, 4) !== 'http' && substr($data['action'], 0, 1) !== '/') {
            $data['action'] = '/' . $data['action'];
        }

        $res = ee()->functions->form_declaration($data);

        $res .= stripslashes(ee()->TMPL->tagdata);

        $res .= "</form>";

        return $res;
    }

    /**
     * Member Search Results
     */
    public function member_search()
    {
        if (! class_exists('Member_memberlist')) {
            require PATH_ADDONS . 'member/mod.member_memberlist.php';
        }

        $MM = new Member_memberlist();

        foreach (get_object_vars($this) as $key => $value) {
            $MM->{$key} = $value;
        }

        return $MM->member_search();
    }

    /**
     * Do A Member Search
     */
    public function do_member_search()
    {
        if (! class_exists('Member_memberlist')) {
            require PATH_ADDONS . 'member/mod.member_memberlist.php';
        }

        $MM = new Member_memberlist();

        foreach (get_object_vars($this) as $key => $value) {
            $MM->{$key} = $value;
        }

        return $MM->do_member_search();
    }

    /**
     * Emoticons
     */
    public function smileys()
    {
        if (ee()->session->userdata('member_id') == 0) {
            return ee()->output->fatal_error(ee()->lang->line('must_be_logged_in'));
        }

        $class_path = PATH_ADDONS . 'emoticon/emoticons.php';

        if (! is_file($class_path) or ! @include_once($class_path)) {
            return ee()->output->fatal_error('Unable to locate the smiley images');
        }

        if (! is_array($smileys)) {
            return;
        }

        $path = ee()->config->slash_item('emoticon_url');

        ob_start(); ?>
        <script type="text/javascript">
        <!--

        function add_smiley(smiley)
        {
            var el = opener.document.getElementById('submit_post').body;

            if ('selectionStart' in el) {
                newStart = el.selectionStart + smiley.length;

                el.value = el.value.substr(0, el.selectionStart) +
                                smiley +
                                el.value.substr(el.selectionEnd, el.value.length);
                el.setSelectionRange(newStart, newStart);
            }
            else if (opener.document.selection) {
                el.focus();
                opener.document.selection.createRange().text = smiley;
            }
            else {
                el.value += " " + smiley + " ";
            }

            el.focus();
            window.close();
        }

        //-->
        </script>

        <?php

        $javascript = ob_get_contents();
        ob_end_clean();
        $r = $javascript;

        $i = 1;

        $dups = array();

        foreach ($smileys as $key => $val) {
            if ($i == 1) {
                $r .= "<tr>\n";
            }

            if (in_array($smileys[$key]['0'], $dups)) {
                continue;
            }

            $r .= "<td class='tableCellOne' align='center'><a href=\"#\" onclick=\"return add_smiley('" . $key . "');\"><img src=\"" . $path . $smileys[$key]['0'] . "\" width=\"" . $smileys[$key]['1'] . "\" height=\"" . $smileys[$key]['2'] . "\" alt=\"" . $smileys[$key]['3'] . "\" border=\"0\" /></a></td>\n";

            $dups[] = $smileys[$key]['0'];

            if ($i == 10) {
                $r .= "</tr>\n";

                $i = 1;
            } else {
                $i++;
            }
        }

        $r = rtrim($r);

        if (substr($r, -5) != "</tr>") {
            $r .= "</tr>\n";
        }

        $this->_set_page_title(ee()->lang->line('smileys'));

        return str_replace('{include:smileys}', $r, $this->_load_element('emoticon_page'));
    }

    /**
     * Convet special characters
     */
    public function _convert_special_chars($str)
    {
        return str_replace(array('<', '>', '{', '}', '\'', '"', '?'), array('&lt;', '&gt;', '&#123;', '&#125;', '&apos;', '&quot;', '&#63;'), $str);
    }

    /**
     * Parse the index template
     */
    public function _parse_index_template($str)
    {
        $req = ($this->request == '') ? 'profile' : $this->request;

        // We have to call this before putting it into the array
        $breadcrumb = $this->breadcrumb();

        return $this->_var_swap(
            ee()->TMPL->tagdata,
            array(
                'stylesheet' => "<style type='text/css'>\n\n" . $this->_load_element('stylesheet') . "\n\n</style>",
                'javascript' => $this->javascript,
                'heading' => $this->page_title,
                'breadcrumb' => $breadcrumb,
                'content' => $str,
                'copyright' => $this->_load_element('copyright')
            )
        );
    }

    /**
     * Member Home Page
     */
    public function _member_page($str)
    {
        $template = $this->_load_element('member_page');

        if ($this->show_headings == true) {
            $template = $this->_allow_if('show_headings', $template);
        } else {
            $template = $this->_deny_if('show_headings', $template);
        }

        // We have to call this before putting it into the array
        $breadcrumb = $this->breadcrumb();

        $header = $this->_load_element('html_header');
        $css = $this->_load_element('stylesheet');

        $header = str_replace('{include:stylesheet}', $css, $header);
        $header = str_replace('{include:head_extra}', $this->head_extra, $header);

        return $this->_var_swap(
            $template,
            array(

                'include:html_header' => $header,
                'include:page_header' => $this->_load_element('page_header'),
                'include:page_subheader' => $this->_load_element('page_subheader'),
                'include:member_manager' => $str,
                'include:breadcrumb' => $breadcrumb,
                'include:html_footer' => $this->_load_element('html_footer')
            )
        );
    }

    /**
     * Load theme element
     */
    public function _load_element($which)
    {
        if ($this->theme_path == '') {
            $theme = (ee()->config->item('member_theme') == '') ? 'default' : ee()->config->item('member_theme');
            $this->theme_path = ee('Theme')->getPath('member/' . $theme . '/');
        }

        if (! file_exists($this->theme_path . $which . '.html')) {
            $data = array('title' => ee()->lang->line('error'),
                'heading' => ee()->lang->line('general_error'),
                'content' => ee()->lang->line('nonexistent_page'),
                'redirect' => '',
                'link' => array(ee()->config->item('site_url'), stripslashes(ee()->config->item('site_name')))
            );

            set_status_header(404);

            return ee()->output->show_message($data, 0);
        }

        return $this->_prep_element(trim(file_get_contents($this->theme_path . $which . '.html')));
    }

    /**
     * Trigger Error Template
     */
    public function _trigger_error($heading, $message = '', $use_lang = true)
    {
        return $this->_var_swap(
            $this->_load_element('error'),
            array(
                'lang:heading' => ee()->lang->line($heading),
                'lang:message' => ($use_lang == true) ? ee()->lang->line($message) : $message
            )
        );
    }

    /**
     * Sets the title of the page
     */
    public function _set_page_title($title)
    {
        if ($this->page_title == '') {
            $this->page_title = $title;
        }
    }

    /**
     * Member Breadcrumb
     */
    public function breadcrumb()
    {
        if ($this->breadcrumb == false) {
            return '';
        }

        $crumbs = $this->_crumb_trail(
            array(
                'link' => ee()->config->item('site_url'),
                'title' => stripslashes(ee()->config->item('site_name'))
            )
        );

        if (ee()->uri->segment(2) == '') {
            return $this->_build_crumbs(ee()->lang->line('member_profile'), $crumbs, ee()->lang->line('member_profile'));
        }

        if (ee()->uri->segment(2) == 'messages') {
            $crumbs .= $this->_crumb_trail(
                array(
                    'link' => $this->_member_path('/profile'),
                    'title' => ee()->lang->line('control_panel_home')
                )
            );

            $pm_page = (false !== ($mbr_crumb = $this->_fetch_member_crumb(ee()->uri->segment(3)))) ? ee()->lang->line($mbr_crumb) : ee()->lang->line('view_folder');

            return $this->_build_crumbs($pm_page, $crumbs, $pm_page);
        }

        if (is_numeric(ee()->uri->segment(2))) {
            $query = ee()->db->query("SELECT screen_name FROM exp_members WHERE member_id = '" . ee()->uri->segment(2) . "'");

            $crumbs .= $this->_crumb_trail(
                array(
                    'link' => $this->_member_path('/memberlist'),
                    'title' => ee()->lang->line('mbr_memberlist')
                )
            );

            return $this->_build_crumbs($query->row('screen_name'), $crumbs, $query->row('screen_name'));
        } else {
            if (ee()->uri->segment(2) == 'memberlist') {
                return $this->_build_crumbs(ee()->lang->line('mbr_memberlist'), $crumbs, ee()->lang->line('mbr_memberlist'));
            } elseif (ee()->uri->segment(2) == 'member_search' or ee()->uri->segment(2) == 'do_member_search') {
                return $this->_build_crumbs(ee()->lang->line('member_search'), $crumbs, ee()->lang->line('member_search'));
            } elseif (ee()->uri->segment(2) != 'profile' and ! in_array(ee()->uri->segment(2), $this->no_menu)) {
                $crumbs .= $this->_crumb_trail(
                    array(
                        'link' => $this->_member_path('/profile'),
                        'title' => ee()->lang->line('control_panel_home')
                    )
                );
            }
        }

        if (false !== ($mbr_crumb = $this->_fetch_member_crumb(ee()->uri->segment(2)))) {
            return $this->_build_crumbs(ee()->lang->line($mbr_crumb), $crumbs, ee()->lang->line($mbr_crumb));
        }
    }

    /**
     * Breadcrumb trail links
     */
    public function _crumb_trail($data)
    {
        $trail = $this->_load_element('breadcrumb_trail');

        $crumbs = '';

        $crumbs .= $this->_var_swap(
            $trail,
            array(
                'crumb_link' => $data['link'],
                'crumb_title' => $data['title']
            )
        );

        return $crumbs;
    }

    /**
     * Finalize the Crumbs
     */
    public function _build_crumbs($title, $crumbs, $str)
    {
        $this->_set_page_title(($title == '') ? 'Powered By ExpressionEngine' : $title);

        $crumbs .= str_replace('{crumb_title}', $str, $this->_load_element('breadcrumb_current_page'));

        $breadcrumb = $this->_load_element('breadcrumb');

        $breadcrumb = str_replace('{name}', ee()->session->userdata('screen_name'), $breadcrumb);

        return str_replace('{breadcrumb_links}', $crumbs, $breadcrumb);
    }

    /**
     * Fetch member profile crumb item
     */
    public function _fetch_member_crumb($item = '')
    {
        if ($item == '') {
            return false;
        }

        return (! isset($this->crumb_map[$item])) ? false : $this->crumb_map[$item];
    }

    /**
     * Prep Element Data
     *
     * Right now we only use this to parse the logged-in/logged-out vars
     */
    public function _prep_element($str)
    {
        if ($str == '') {
            return '';
        }

        if (ee()->session->userdata('member_id') == 0) {
            $str = $this->_deny_if('logged_in', $str);
            $str = $this->_allow_if('logged_out', $str);
        } else {
            $str = $this->_allow_if('logged_in', $str);
            $str = $this->_deny_if('logged_out', $str);
        }

        // Parse the forum conditional
        if (ee()->config->item('forum_is_installed') == "y") {
            $str = $this->_allow_if('forum_installed', $str);
        } else {
            $str = $this->_deny_if('forum_installed', $str);
        }

        // Parse the self deletion conditional
        if (
            ee('Permission')->can('delete_self') &&
            ! ee('Permission')->isSuperAdmin()
        ) {
            $str = $this->_allow_if('can_delete', $str);
        } else {
            $str = $this->_deny_if('can_delete', $str);
        }

        return $str;
    }

    /**
     * Finalize a few things
     */
    public function _final_prep($str)
    {
        // Which mode are we in?
        // This class can either be run in "stand-alone" mode or through the template engine.
        $template_parser = false;

        if (class_exists('Template')) {
            if (ee()->TMPL->tagdata != '') {
                $str = $this->_parse_index_template($str);
                $template_parser = true;
                ee()->TMPL->disable_caching = true;
            }
        }

        if ($template_parser == false and $this->in_forum == false) {
            $str = $this->_member_page($str);
        }

        // Parse the language text
        if (preg_match_all("/{lang:(.+?)\}/i", $str, $matches)) {
            for ($j = 0; $j < count($matches['0']); $j++) {
                $line = (ee()->lang->line($matches['1'][$j]) == $matches['1'][$j]) ? ee()->lang->line('mbr_' . $matches['1'][$j]) : ee()->lang->line($matches['1'][$j]);

                $str = str_replace($matches['0'][$j], $line, $str);
            }
        }

        // Parse old style path variables
        // This is here for backward compatibility for people with older templates
        $str = preg_replace_callback("/" . LD . "\s*path=(.*?)" . RD . "/", array( & ee()->functions, 'create_url'), $str);

        if (preg_match_all("#" . LD . "\s*(profile_path\s*=.*?)" . RD . "#", $str, $matches)) {
            $i = 0;
            foreach ($matches['1'] as $val) {
                $path = ee()->functions->create_url(ee()->functions->extract_path($val) . '/' . ee()->session->userdata('member_id'));
                $str = preg_replace("#" . $matches['0'][$i++] . "#", $path, $str, 1);
            }
        }
        // -------

        $simple = ($this->show_headings == false) ? '/simple' : '';

        // Parse {switch="foo|bar"} variables
        if (preg_match_all("/" . LD . "(switch\s*=.+?)" . RD . "/i", $str, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $sparam = ee('Variables/Parser')->parseTagParameters($match[1]);

                if (isset($sparam['switch'])) {
                    $sopt = explode("|", $sparam['switch']);

                    $i = 1;
                    while (($pos = strpos($str, LD . $match[1] . RD)) !== false) {
                        $str = substr_replace($str, $sopt[($i++ + count($sopt) - 1) % count($sopt)], $pos, strlen(LD . $match[1] . RD));
                    }
                }
            }
        }

        // Set some paths
        $theme = (ee()->session->userdata('profile_theme') != '') ? ee()->session->userdata('profile_theme') : ee()->config->item('member_theme');

        if ($this->image_url == '') {
            $theme = ($theme == '') ? 'default' : $theme;
            $this->image_url = ee('Theme')->getUrl('member/' . $theme . '/images/');
        }

        // Finalize the output
        $str = ee()->functions->prep_conditionals($str, array('current_request' => $this->request));

        $str = $this->_var_swap(
            $str,
            array(
                'lang' => ee()->config->item('xml_lang'),
                'charset' => ee()->config->item('output_charset'),
                'path:image_url' => $this->image_url,
                'path:your_control_panel' => $this->_member_path('profile'),
                'path:your_profile' => $this->_member_path(ee()->session->userdata('member_id')),
                'path:edit_preferences' => $this->_member_path('edit_preferences'),
                'path:register' => $this->_member_path('register' . $simple),
                'path:private_messages' => $this->_member_path('messages'),
                'path:memberlist' => $this->_member_path('memberlist'),
                'path:signature' => $this->_member_path('edit_signature'),
                'path:avatar' => $this->_member_path('edit_avatar'),
                'path:photo' => $this->_member_path('edit_photo'),
                'path:smileys' => $this->_member_path('smileys'),
                'path:forgot' => $this->_member_path('forgot_password' . $simple),
                'path:login' => $this->_member_path('login' . $simple),
                'path:delete' => $this->_member_path('delete'),
                'page_title' => $this->page_title,
                'site_name' => stripslashes(ee()->config->item('site_name')),
                'path:theme_css' => '',
                'current_request' => $this->request,
                'username_max_length' => USERNAME_MAX_LENGTH,
                'password_max_length' => PASSWORD_MAX_LENGTH
            )
        );

        // parse regular global vars
        ee()->load->library('template', null, 'TMPL');

        // load up any Snippets
        ee()->db->select('snippet_name, snippet_contents');
        ee()->db->where('(site_id = ' . ee()->db->escape_str(ee()->config->item('site_id')) . ' OR site_id = 0)');
        $fresh = ee()->db->get('snippets');

        if ($fresh->num_rows() > 0) {
            $snippets = array();

            foreach ($fresh->result() as $var) {
                $snippets[$var->snippet_name] = $var->snippet_contents;
            }

            ee()->config->_global_vars = array_merge(ee()->config->_global_vars, $snippets);

            unset($snippets);
            unset($fresh);
        }

        if (! $this->in_forum) {
            ee()->TMPL->parse($str);
            $str = ee()->TMPL->parse_globals(ee()->TMPL->final_template);
        }

        //  Add security hashes to forms
        if (! class_exists('Template')) {
            $str = ee()->functions->insert_action_ids(ee()->functions->add_form_security_hash($str));
        }

        if (! is_object(ee()->TMPL)) {
            // cleanup unparsed conditionals and annotations
            $str = preg_replace("/" . LD . "if\s+.*?" . RD . ".*?" . LD . '\/if' . RD . "/s", "", $str);
            $str = preg_replace("/\{!--.*?--\}/s", '', $str);
        }

        return $str;
    }

    /**
     * Set base values of class vars
     */
    public function _set_properties($props = array())
    {
        if (count($props) > 0) {
            foreach ($props as $key => $val) {
                $this->$key = $val;
            }
        }
    }

    /**
     * Sets the member basepath
     */
    public function _member_set_basepath()
    {
        $this->basepath = ee()->functions->create_url($this->trigger);
    }

    /**
     * Compiles a path string
     */
    public function _member_path($uri = '')
    {
        if ($this->basepath == '') {
            $this->_member_set_basepath();
        }

        return reduce_double_slashes($this->basepath . '/' . $uri);
    }

    /**
     * Helpers for "if" conditions
     */
    public function _deny_if($cond, $str, $replace = '')
    {
        return preg_replace("/\{if\s+" . $cond . "\}.+?\{\/if\}/si", $replace, $str);
    }

    public function _allow_if($cond, $str)
    {
        return preg_replace("/\{if\s+" . $cond . "\}(.+?)\{\/if\}/si", "\\1", $str);
    }

    /**
     * Replace variables
     */
    public function _var_swap($str, $data)
    {
        if (! is_array($data)) {
            return false;
        }

        foreach ($data as $key => $val) {
            $str = str_replace('{' . $key . '}', $val, $str);
        }

        return $str;
    }

    /**
     * Swap single variables with final value
     */
    public function _var_swap_single($search, $replace, $source, $encode_ee_tags = true)
    {
        if ($encode_ee_tags) {
            $replace = ee()->functions->encode_ee_tags($replace, true);
        }

        return str_replace(LD . $search . RD, $replace, $source);
    }

    /**
     * Show 404 Template
     *
     * Show the real 404 template instead of an ACT error when we cannot
     * find the page that was requested.
     *
     * @access protected
     */
    protected function _show_404_template()
    {
        // 404 it
        ee()->load->library('template', null, 'TMPL');
        ee()->TMPL->show_404();
    }

    /**
     * Custom Member Profile Data
     */
    public function custom_profile_data($typography = true)
    {
        if (ee()->TMPL->fetch_param('username')) {
            $member = ee('Model')
                ->get('Member')
                ->filter('username', ee('Security/XSS')->clean(ee()->TMPL->fetch_param('username')))
                ->first();
        } else {
            $member_id = (! ee()->TMPL->fetch_param('member_id')) ? ee()->session->userdata('member_id') : ee()->TMPL->fetch_param('member_id');
            $member = ee('Model')
                ->get('Member', $member_id)
                ->first();
        }

        if (! $member) {
            return ee()->TMPL->tagdata = '';
        }

        $results = $member->getValues() + ['group_title' => $member->PrimaryRole->name, 'primary_role_name' => $member->PrimaryRole->name];
        unset($results['password']);
        unset($results['unique_id']);
        unset($results['crypt_key']);
        unset($results['authcode']);
        unset($results['salt']);
        unset($results['backup_mfa_code']);

        $default_fields = $results;

        // Is there an avatar?
        $avatar_path = $member->getAvatarUrl();
        if (! empty($avatar_path)) {
            $avatar_width = $results['avatar_width'];
            $avatar_height = $results['avatar_height'];
            $avatar = true;
        } else {
            $avatar_path = '';
            $avatar_width = '';
            $avatar_height = '';
            $avatar = false;
        }

        // Is there a member photo?
        if (ee()->config->item('enable_photos') == 'y' and $results['photo_filename'] != '') {
            $photo_path = ee()->config->item('photo_url') . $results['photo_filename'];
            $photo_width = $results['photo_width'];
            $photo_height = $results['photo_height'];
            $photo = true;
        } else {
            $photo_path = '';
            $photo_width = '';
            $photo_height = '';
            $photo = false;
        }

        // Is there a signature image?
        if (ee()->config->item('enable_signatures') == 'y') {
            $sig_img_path = $member->getSignatureImageUrl();
            $sig_img_width = $results['sig_img_width'];
            $sig_img_height = $results['sig_img_height'];
            $sig_img_image = true;
        } else {
            $sig_img_path = '';
            $sig_img_width = '';
            $sig_img_height = '';
            $sig_img = false;
        }

        // Parse variables
        if ($this->in_forum == true) {
            $search_path = $this->forum_path . 'member_search/' . $this->cur_id . '/';
        } else {
            $search_path = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . ee()->functions->fetch_action_id('Search', 'do_search') . '&amp;mbr=' . urlencode($results['member_id']);
        }

        $more_fields = array(
            'send_private_message' => $this->_member_path('messages/pm/' . $member->getId()),
            'search_path' => $search_path,
            'avatar' => $avatar,
            'avatar_url' => $avatar_path,
            'avatar_filename' => $results['avatar_filename'],
            'avatar_width' => $avatar_width,
            'avatar_height' => $avatar_height,
            'photo' => $photo,
            'photo_url' => $photo_path,
            'photo_filename' => $results['photo_filename'],
            'photo_width' => $photo_width,
            'photo_height' => $photo_height,
            'signature_image_url' => $sig_img_path,
            'signature_image_filename' => $results['sig_img_filename'],
            'signature_image_width' => $sig_img_width,
            'signature_image_height' => $sig_img_height
        );

        $dates = array(
            'last_visit' => (empty($default_fields['last_visit'])) ? '' : $default_fields['last_visit'],
            'last_activity' => (empty($default_fields['last_activity'])) ? '' : $default_fields['last_activity'],
            'join_date' => (empty($default_fields['join_date'])) ? '' : $default_fields['join_date'],
            'last_entry_date' => (empty($default_fields['last_entry_date'])) ? '' : $default_fields['last_entry_date'],
            'last_forum_post_date' => (empty($default_fields['last_forum_post_date'])) ? '' : $default_fields['last_forum_post_date'],
            'last_comment_date' => (empty($default_fields['last_comment_date'])) ? '' : $default_fields['last_comment_date']
        );

        // parse date variables
        ee()->TMPL->tagdata = ee()->TMPL->parse_date_variables(ee()->TMPL->tagdata, $dates);

        //  {name}
        $name = (! $default_fields['screen_name']) ? $default_fields['username'] : $default_fields['screen_name'];
        $more_fields['name'] = $this->_convert_special_chars($name);
        //  {member_group}
        $more_fields['member_group'] = $default_fields['group_title'];
        //  {timezone}
        $more_fields['timezone'] = ($default_fields['timezone'] != '') ? ee()->lang->line($default_fields['timezone']) : '';
        foreach (ee()->TMPL->var_single as $key => $val) {
            //  {local_time}
            if (strncmp($key, 'local_time', 10) == 0) {
                $locale = false;

                if (ee()->session->userdata('member_id') != $this->cur_id) {
                    // Default is UTC?
                    $locale = ($default_fields['timezone'] == '') ? 'UTC' : $default_fields['timezone'];
                }

                ee()->TMPL->tagdata = $this->_var_swap_single(
                    $key,
                    ee()->localize->format_date($val, null, $locale),
                    ee()->TMPL->tagdata
                );
            }
        }

        // Special consideration for {total_forum_replies}, and
        // {total_forum_posts} whose meanings do not match the
        // database field names
        $more_fields['total_forum_replies'] = $default_fields['total_forum_posts'];
        $more_fields['total_forum_posts'] = $default_fields['total_forum_topics'] + $default_fields['total_forum_posts'];

        $default_fields = array_merge($default_fields, $more_fields);

        if (! class_exists('Channel')) {
            require PATH_ADDONS . 'channel/mod.channel.php';
        }
        $channel = new Channel();
        $channel->fetch_custom_member_fields();

        // Load the parser
        ee()->load->library('channel_entries_parser');
        $parser = ee()->channel_entries_parser->create(ee()->TMPL->tagdata);

        $data = [
            'entries' => [array_merge(
                [
                    'site_id' => ee()->config->item('site_id'),
                    'entry_id' => $results['member_id'],
                    'entry_date' => null,
                    'edit_date' => null,
                    'recent_comment_date' => null,
                    'expiration_date' => null,
                    'comment_expiration_date' => null,
                    'allow_comments' => null,
                    'channel_title' => null,
                    'channel_name' => null,
                    'entry_site_id' => null,
                    'channel_url' => null,
                    'comment_url' => null,
                ],
                $default_fields
            )]
        ];

        ee()->TMPL->tagdata = $parser->parse($channel, $data);

        return ee()->TMPL->tagdata;
    }

    /**
     * Member Role Groups list
     */
    public function role_groups()
    {
        $member_id = (!ee()->TMPL->fetch_param('member_id')) ? ee()->session->userdata('member_id') : ee()->TMPL->fetch_param('member_id');

        $member = ee('Model')
            ->get('Member', $member_id)
            ->with(['Roles' => 'RoleGroups AS GroupsByRole'])
            ->with('RoleGroups')
            ->all()
            ->first();

        if (!$member) {
            return ee()->TMPL->no_results();
        }

        $vars = [];
        // Role groups that are assigned directly to member
        foreach ($member->RoleGroups as $roleGroup) {
            if ($roleGroup->group_id === 0 && $roleGroup->name === null) {
                continue;
            }

            $vars[$roleGroup->group_id] = [
                'role_group_id' => $roleGroup->group_id,
                'role_group_name' => $roleGroup->name,
            ];
        };

        // Role groups that are assigned via Roles
        foreach ($member->Roles as $role) {
            foreach ($role->RoleGroups as $roleGroup) {
                if ($roleGroup->group_id === 0 && $roleGroup->name === null) {
                    continue;
                }

                if (isset($vars[$roleGroup->group_id])) {
                    continue;
                }

                $vars[$roleGroup->group_id] = [
                    'role_group_id' => $roleGroup->group_id,
                    'role_group_name' => $roleGroup->name,
                ];
            }
        }

        if (empty($vars)) {
            return ee()->TMPL->no_results();
        }

        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, array_values($vars));
    }

    /**
     * Member roles list
     */
    public function roles()
    {
        if (ee()->TMPL->fetch_param('username')) {
            $member = ee('Model')
                ->get('Member')
                ->with('PrimaryRole', 'Roles', 'RoleGroups')
                ->filter('username', ee('Security/XSS')->clean(ee()->TMPL->fetch_param('username')))
                ->all()
                ->first();
        } else {
            $member_id = (! ee()->TMPL->fetch_param('member_id')) ? ee()->session->userdata('member_id') : ee()->TMPL->fetch_param('member_id');
            $member = ee('Model')
                ->get('Member', $member_id)
                ->with('PrimaryRole', 'Roles', 'RoleGroups')
                ->all()
                ->first();
        }

        if (!$member) {
            return ee()->TMPL->no_results();
        }

        $roles = $member->getAllRoles()->getDictionary('role_id', 'name');

        $vars = [];
        $i = 0;
        foreach ($roles as $id => $role) {
            $vars[$i++] = [
                'role_id' => $id,
                'name' => $role,
                'is_primary_role' => ($id == $member->PrimaryRole->getId()),
                'primary_role_id' => $member->PrimaryRole->getId(),
                'primary_role_name' => $member->PrimaryRole->name
            ];
        }

        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);
    }

    /**
     * Check member role assignment
     */
    public function has_role()
    {
        if (ee()->TMPL->fetch_param('role_id') == '') {
            return ee()->TMPL->no_results();
        }

        $member_id = (!ee()->TMPL->fetch_param('member_id')) ? ee()->session->userdata('member_id') : ee()->TMPL->fetch_param('member_id');

        $member = ee('Model')
            ->get('Member', $member_id)
            ->with('PrimaryRole', 'Roles', 'RoleGroups')
            ->first();

        if (!$member) {
            return ee()->TMPL->no_results();
        }

        $roles = $member->getAllRoles()->pluck('role_id');

        $rolesToCheck = explode('|', ee()->TMPL->fetch_param('role_id'));

        if (array_intersect($rolesToCheck, $roles)) {
            return ee()->TMPL->tagdata;
        }

        return ee()->TMPL->no_results();
    }

    /**
     * AJAX validation endpoint
     *
     * @return JSON
     */
    public function validate()
    {
        if (! AJAX_REQUEST || ee('Request')->method() != 'POST') {
            show_error(lang('unauthorized_access'), 403);
        }
        $result = [
            'success' => true
        ];
        ee()->lang->load('login');
        $fields = !empty(ee('Request')->get('fields')) ? explode('|', ee('Request')->get('fields')) : [];
        if (empty($fields) || array_intersect(['all', 'username', 'password', 'email', 'screen_name'], $fields)) {
            $member = ee()->session->getMember();
            if (ee('Permission')->can('edit_members') && !empty(ee('Request')->post('member_id'))) {
                $member = ee('Model')->get('Member', (int) ee('Request')->post('member_id'));
            }
            if (empty($member)) {
                $member = ee('Model')->make('Member');
            }
            $member->set($_POST);

            if (empty($fields) || in_array('all', $fields)) {
                $validation = $member->validate();

                if (!$validation->isValid()) {
                    $result['success'] = false;
                    $result['errors'] = $validation->getAllErrors();
                }
            } else {
                $validationRules = [];
                foreach (['username', 'password', 'email', 'screen_name'] as $field) {
                    if (in_array($field, $fields)) {
                        switch ($field) {
                            case 'username':
                                $validationRules[$field] = 'uniqueUsername|validUsername|notBanned';
                                break;
                            case 'password':
                                $validationRules[$field] = 'validPassword|passwordMatchesSecurityPolicy';
                                break;
                            case 'email':
                                $validationRules[$field] = 'email|uniqueEmail|max_length[254]|notBanned';
                                break;
                            case 'screen_name':
                                $validationRules[$field] = 'validScreenName|notBanned';
                                break;
                            default:
                                break;
                        }
                    }
                }
                if (!empty($validationRules)) {
                    $validation = ee('Validation')->make($validationRules)->validate($_POST);
                    if (!$validation->isValid()) {
                        $errors = [];
                        foreach ($validation->getAllErrors() as $field => $fieldErrors) {
                            $errors[$field] = implode("\r\n", $fieldErrors);
                        }
                        $result['success'] = false;
                        $result['errors'] = $errors;
                    }
                }
            }
        }

        $password = ee('Request')->post('password');
        if ((empty($fields) || array_intersect(['all', 'password', 'password_rank'], $fields)) && !empty($password)) {
            $result['rank'] = ee('Member')->calculatePasswordComplexity($password);
            if ($result['rank'] >= 80) {
                $result['rank_text'] = lang('password_rank_very_strong');
            } elseif ($result['rank'] >= 60) {
                $result['rank_text'] = lang('password_rank_strong');
            } elseif ($result['rank'] >= 40) {
                $result['rank_text'] = lang('password_rank_good');
            } else {
                $result['rank_text'] = lang('password_rank_weak');
            }
        }
        ee()->output->send_ajax_response($result);
    }

    /**
     * Get URL to password validation endpoint
     *
     * @return String
     */
    public function validation_url()
    {
        $action_id = ee()->db->select('action_id')
            ->where('class', 'Member')
            ->where('method', 'validate')
            ->get('actions');
        $url = ee()->functions->fetch_site_index() . QUERY_MARKER . 'ACT=' . $action_id->row('action_id');
        if (!empty(ee()->TMPL->fetch_param('fields'))) {
            $url .= '&fields=' . ee()->TMPL->fetch_param('fields');
        }

        return $url;
    }

    /**
     * Parse a custom member field
     *
     * @param   int     $field_id   Member field ID
     * @param   array   $field      Tag information as parsed by ee('Variables/Parser')->parseVariableProperties()
     * @param   mixed   $data       Data for this field
     * @param   string  $tagdata    Tagdata to perform the replacement in
     * @param   string  $member_id  ID for the member this data is associated
     * @return  string  String with variable parsed
     */
    protected function parseField($field_id, $field, $data, $tagdata, $member_id, $row = array(), $tag = false)
    {
        if (! isset($this->member_fields[$field_id])) {
            return $tagdata;
        }

        $member_field = $this->member_fields[$field_id];

        $default_row = array(
            'channel_html_formatting' => 'safe',
            'channel_auto_link_urls' => 'y',
            'channel_allow_img_urls' => 'n'
        );
        $row = array_merge($default_row, $row);

        return $member_field->parse($data, $member_id, 'member', $field, $tagdata, $row, $tag);
    }

    /**
     * Ignore List
     */
    public function ignore_list()
    {
        $pre = 'ignore_';
        $prelen = strlen($pre);

        if ($member_id = ee()->TMPL->fetch_param('member_id')) {
            $query = ee()->db->query("SELECT ignore_list FROM exp_members WHERE member_id = '{$member_id}'");

            if ($query->num_rows() == 0) {
                return ee()->TMPL->no_results();
            }

            $ignored = ($query->row('ignore_list') == '') ? array() : explode('|', $query->row('ignore_list'));
        } else {
            $ignored = ee()->session->userdata('ignore_list');
        }

        $query = ee()->db->query("SELECT m.member_id, m.role_id, m.role_id AS group_id, m.username, m.screen_name, m.email, m.ip_address, m.total_entries, m.total_comments, m.private_messages, m.total_forum_topics, m.total_forum_posts AS total_forum_replies, m.total_forum_topics + m.total_forum_posts AS total_forum_posts,
                            r.name AS group_description FROM exp_members AS m, exp_roles AS r
                            WHERE r.role_id = m.role_id
                            AND m.member_id IN ('" . implode("', '", $ignored) . "')");

        if ($query->num_rows() == 0) {
            return ee()->TMPL->no_results();
        }

        $tagdata = ee()->TMPL->tagdata;
        $out = '';

        foreach ($query->result_array() as $row) {
            $temp = $tagdata;

            foreach (ee()->TMPL->var_single as $key => $val) {
                $val = substr($val, $prelen);

                if (isset($row[$val])) {
                    $temp = ee()->TMPL->swap_var_single($pre . $val, ee()->functions->encode_ee_tags($row[$val]), $temp);
                }
            }

            $out .= $temp;
        }

        return ee()->TMPL->tagdata = $out;
    }

    /**
     * Create a language dropdown list
     * @param  string $default Default language
     * @return string          Code for a language dropdown
     */
    protected function get_language_listing($default = 'english')
    {
        $dirs = ee()->lang->language_pack_names();

        return form_dropdown('language', $dirs, $default);
    }
}
// END CLASS

// EOF
