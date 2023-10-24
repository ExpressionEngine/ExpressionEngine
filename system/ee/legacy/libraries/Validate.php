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
 * Core Validation
 */
class EE_Validate
{
    public $member_id = '';
    public $val_type = 'update';
    public $fetch_lang = true;
    public $require_cpw = false;
    public $username = '';
    public $cur_username = '';
    public $screen_name = '';
    public $cur_screen_name = '';
    public $password = '';
    public $password_confirm = '';
    public $email = '';
    public $cur_email = '';
    public $errors = array();
    public $enable_log = false;
    public $log_msg = array();
    private $cur_password;

    /**
     * Construct
     */
    public function __construct($data = '')
    {
        $vars = array(
            'member_id', 'username', 'cur_username', 'screen_name',
            'cur_screen_name', 'password', 'password_confirm',
            'cur_password', 'email', 'cur_email'
        );

        if (is_array($data)) {
            foreach ($vars as $val) {
                $this->$val = (isset($data[$val])) ? $data[$val] : '';
            }
        }

        if (isset($data['fetch_lang'])) {
            $this->fetch_lang = $data['fetch_lang'];
        }
        if (isset($data['require_cpw'])) {
            $this->require_cpw = $data['require_cpw'];
        }
        if (isset($data['enable_log'])) {
            $this->enable_log = $data['enable_log'];
        }
        if (isset($data['val_type'])) {
            $this->val_type = $data['val_type'];
        }
        if ($this->fetch_lang == true) {
            ee()->lang->loadfile('myaccount');
        }
        if ($this->require_cpw == true) {
            $this->password_safety_check();
        }
    }

    /**
     * Password safety check
     *
     */
    public function password_safety_check()
    {
        //deprecated, but will not throw deprecation error until 6.4
        //ee()->load->library('logger');
        //ee()->logger->deprecated('6.4', "ee('Validation')->validate()");

        if ($this->cur_password == '') {
            return $this->errors[] = ee()->lang->line('missing_current_password');
        }

        ee()->load->library('auth');

        $authed = ee()->auth->authenticate_id((int) ee()->session->userdata('member_id'), $this->cur_password);

        if (!$authed) {
            $this->errors[] = ee()->lang->line('invalid_password');
        }
    }

    /**
     * Validate Username
     */
    public function validate_username()
    {
        //deprecated, but will not throw deprecation error until 6.4
        //ee()->load->library('logger');
        //ee()->logger->deprecated('6.4', "ee('Validation')->validate()");

        $type = $this->val_type;

        // Is username missing?
        if ($this->username == '') {
            return $this->errors[] = ee()->lang->line('missing_username');
        }

        // Is username formatting correct?
        // Reserved characters:  |  "  '  !
        if (preg_match("/[\|'\"!<>\{\}]/", $this->username)) {
            $this->errors[] = ee()->lang->line('invalid_characters_in_username');
        }

        // Is username min length correct?
        $len = ee()->config->item('un_min_len');

        if (strlen($this->username) < $len) {
            $this->errors[] = sprintf(lang('username_too_short'), $len);
        }

        // Is username max length correct?
        if (strlen($this->username) > USERNAME_MAX_LENGTH) {
            $this->errors[] = ee()->lang->line('username_too_long');
        }

        // Set validation type
        if ($this->cur_username != '') {
            if ($this->cur_username != $this->username) {
                $type = 'new';

                if ($this->enable_log == true) {
                    $this->log_msg[] = ee()->lang->line('username_changed') . NBS . NBS . $this->username;
                }
            }
        }

        if ($type == 'new') {
            // Is username banned?
            if (ee()->session->ban_check('username', $this->username)) {
                $this->errors[] = ee()->lang->line('username_taken');
            }

            // Is username taken?
            ee()->db->from('members');
            ee()->db->where('username = LOWER(' . ee()->db->escape($this->username) . ')', null, false);
            ee()->db->where('LOWER(username) = ' . ee()->db->escape(strtolower($this->username)), null, false);
            $count = ee()->db->count_all_results();

            if ($count > 0) {
                $this->errors[] = ee()->lang->line('username_taken');
            }
        }
    }

    /**
     * Validate screen name
     */
    public function validate_screen_name()
    {
        //deprecated, but will not throw deprecation error until 6.4
        //ee()->load->library('logger');
        //ee()->logger->deprecated('6.4', "ee('Validation')->validate()");

        if ($this->screen_name == '') {
            if ($this->username == '') {
                return $this->errors[] = ee()->lang->line('missing_username');
            }

            return $this->screen_name = $this->username;
        }

        $data = [
            'screen_name' => $this->screen_name
        ];

        $rules = array(
            'screen_name' => 'validScreenName|notBanned'
        );

        $result = ee('Validation')->make($rules)->validate($data);

        if ($result->isNotValid()) {
            foreach ($result->getErrors('screen_name') as $key => $error) {
                $this->errors[] = $error;
            }
            return $this->errors;
        }
    }

    /**
     * Validate Password
     *
     * @return 	mixed 	array on failure, void on success
     */
    public function validate_password()
    {
        //deprecated, but will not throw deprecation error until 6.4
        //ee()->load->library('logger');
        //ee()->logger->deprecated('6.4', "ee('Validation')->validate()");

        $data = [
            'username' => $this->username,
            'password' => $this->password,
            'password_confirm' => $this->password_confirm
        ];

        $rules = array(
            'password' => 'required|validPassword|passwordMatchesSecurityPolicy',
            'password_confirm' => 'matches[password]'
        );

        $result = ee('Validation')->make($rules)->validate($data);

        if ($result->isNotValid()) {
            foreach ($result->getErrors('password') as $key => $error) {
                $this->errors[] = $error;
            }
            foreach ($result->getErrors('password_confirm') as $key => $error) {
                $this->errors[] = lang('missmatched_passwords');
            }
            return $this->errors;
        }
    }

    /**
     * Validate Email
     *
     *
     * @return 	mixed 	array on failure, void on success
     */
    public function validate_email()
    {
        //deprecated, but will not throw deprecation error until 6.4
        //ee()->load->library('logger');
        //ee()->logger->deprecated('6.4', "ee('Validation')->validate()");

        $type = $this->val_type;

        /** -------------------------------------
        /**  Is email missing?
        /** -------------------------------------*/
        if ($this->email == '') {
            return $this->errors[] = ee()->lang->line('missing_email');
        }

        /** -------------------------------------
        /**  Is email valid?
        /** -------------------------------------*/
        ee()->load->helper('email');

        if (! valid_email($this->email)) {
            return $this->errors[] = ee()->lang->line('invalid_email_address');
        }

        /** -------------------------------------
        /**  Set validation type
        /** -------------------------------------*/
        if ($this->cur_email != '') {
            if ($this->cur_email != $this->email) {
                if ($this->enable_log == true) {
                    $this->log_msg = ee()->lang->line('email_changed') . NBS . NBS . $this->email;
                }

                $type = 'new';
            }
        }

        if ($type == 'new') {
            /** -------------------------------------
            /**  Is email banned?
            /** -------------------------------------*/
            if (ee()->session->ban_check('email', $this->email)) {
                return $this->errors[] = ee()->lang->line('email_taken');
            }

            /** -------------------------------------
            /**  Duplicate emails?
            /** -------------------------------------*/
            if (! ee('Validation')->check('uniqueEmail', $this->email)) {
                $this->errors[] = ee()->lang->line('email_taken');
            }
        }
    }

    /**
     * Show Errors
     *
     * @return 	string
     */
    public function show_errors()
    {
        if (count($this->errors) > 0) {
            $msg = '';

            foreach ($this->errors as $val) {
                $msg .= $val . '<br />';
            }

            return $msg;
        }
    }

    /**
     * Lookup word in dictionary file
     *
     * @param 	string
     * @return 	boolean
     */
    public function lookup_dictionary_word($target)
    {
        //deprecated, but will not throw deprecation error until 6.4
        //ee()->load->library('logger');
        //ee()->logger->deprecated('6.4');

        if (ee()->config->item('allow_dictionary_pw') == 'y') {
            return false;
        }

        $file = !empty(ee()->config->item('name_of_dictionary_file')) ? ee()->config->item('name_of_dictionary_file') : 'dictionary.txt';
        $path = reduce_double_slashes(PATH_DICT . $file);

        if (! file_exists($path)) {
            return false;
        }

        $word_file = file($path);

        foreach ($word_file as $word) {
            if (trim(strtolower($word)) == $target) {
                return true;
            }
        }

        return false;
    }
}
// END CLASS

// EOF
