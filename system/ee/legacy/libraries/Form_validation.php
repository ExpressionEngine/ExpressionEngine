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
 * Form Validation
 */
class EE_Form_validation
{
    public $CI;
    public $_field_data = array();
    public $_config_rules = array();
    public $_error_array = array();
    public $_error_messages = array();
    public $_error_prefix = '<em class="ee-form-error-message">';
    public $_error_suffix = '</em>';
    public $error_string = '';
    public $_safe_form_data = false;
    public $old_values = array();
    public $_fieldtype = null;

    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct($rules = array())
    {
        // Instantiating the controller sets ee()->__legacy_controller
        // This fixes an issue with using form validation from the CLI
        if (! isset(ee()->__legacy_controller) && REQ === 'CLI') {
            new \Controller();
        }

        $this->CI = ee()->get('__legacy_controller');

        // Validation rules can be stored in a config file.
        $this->_config_rules = $rules;

        // Automatically load the form helper
        ee()->load->helper('form');
        ee()->load->helper('multibyte');

        // Set the character encoding in MB.
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding(ee()->config->item('charset'));
        }

        if (ee()->input->get('C') == 'addons_modules' &&
            ee()->input->get('M') == 'show_module_cp' &&
            isset(ee()->_mcp_reference)
        ) {
            $this->setCallbackObject(ee()->_mcp_reference);
        }
    }

    /**
     * Sets the callback object
     *
     * @access	public
     * @param	obj $obj	The object to use for callbacks
     * @return	void
     */
    public function setCallbackObject($obj)
    {
        $obj->lang = & ee()->lang;
        $obj->input = & ee()->input;
        $obj->security = & ee()->security;
        $this->CI = & $obj;
    }

    /**
     * Handles validations that are performed over AJAX
     *
     * This ultimately calls our parent run() method where all validation
     * happens, but this handles validation of single fields and the
     * sending of AJAX responses so our controllers don't have to.
     *
     * @return	void
     */
    public function run_ajax()
    {
        $result = (count($this->_field_data));

        // We should currently only be validating one field at a time,
        // and this POST field should have the name of it
        $field = ee()->input->post('ee_fv_field');

        // Remove any namespacing to run validation for the parent field
        $field = preg_replace('/\[.+?\]/', '', $field);

        // Unset any other rules that aren't for the field we want to
        // validate
        foreach ($this->_field_data as $key => $value) {
            if ($key != $field) {
                unset($this->_field_data[$key]);
            }
        }

        // Skip validation if we've emptied the field_data array,
        // can happen if we're not validating the requested field
        if (empty($this->_field_data) && $result) {
            $result = true;
        }

        // Validate the field
        if ($result !== true) {
            $result = $this->run();
        }

        // Send appropriate AJAX response based on validation result
        if ($result === false) {
            ee()->output->send_ajax_response(array('error' => form_error($field)));
        } else {
            ee()->output->send_ajax_response(['success']);
        }
    }

    /**
     * Returns TRUE/FALSE based on existance of validation errors
     *
     * @return	bool
     */
    public function errors_exist()
    {
        return ! empty($this->_error_array);
    }

    /**
     * Given a "sections" array formatted for the shared form view, sets
     * validation rules for fields that have defined options to make sure
     * only those defined options make it through POST, this is to keep out
     * form-tinkerers
     *
     * @param	array	$sections	Array of sections passed to form view
     */
    public function validateNonTextInputs($sections)
    {
        foreach ($sections as $settings) {
            // Support settings nested under a key for form groups
            if (isset($settings['settings'])) {
                $this->setRulesForSettings($settings['settings']);
            } else {
                $this->setRulesForSettings($settings);
            }
        }
    }

    /**
     * Actually sets the rules per `validateNonTextInputs()` above
     *
     * @param	array	$settings	Array of settings in shared form format
     */
    private function setRulesForSettings($settings)
    {
        foreach ($settings as $key => $setting) {
            if (is_array($setting)) {
                foreach ($setting['fields'] as $field_name => $field) {
                    // For ajaxified fields with options not currently showing, we skip
                    if (isset($field['filter_url']) && isset($field['choices']) && count($field['choices']) == 100) {
                        continue;
                    }

                    $enum = null;

                    // Account for empty state in React checkbox fields
                    if ($field['type'] == 'checkbox') {
                        $field['choices'][''] = '';
                    }

                    // If this field has 'choices', make sure only those
                    // choices are let through the submission
                    if (isset($field['choices'])) {
                        $enum = implode(',', array_keys($field['choices']));
                    }
                    // Only allow y/n through for yes_no fields
                    elseif ($field['type'] == 'yes_no') {
                        $enum = 'y,n';
                    }

                    if (isset($enum)) {
                        $this->set_rules(
                            $field_name,
                            $setting['title'],
                            'enum[' . $enum . ']'
                        );
                    }
                }
            }
        }
    }

    /**
     * Set Rules
     *
     * This function takes an array of field names and validation
     * rules as input, validates the info, and stores it
     *
     * @access	public
     * @param	mixed
     * @param	string
     * @return	void
     */
    public function set_rules($field, $label = '', $rules = '')
    {
        // No reason to set rules if we have no POST data
        if (count($_POST) == 0) {
            return $this;
        }

        // If an array was passed via the first parameter instead of indidual string
        // values we cycle through it and recursively call this function.
        if (is_array($field)) {
            foreach ($field as $row) {
                // Houston, we have a problem...
                if (! isset($row['field']) or ! isset($row['rules'])) {
                    continue;
                }

                // If the field label wasn't passed we use the field name
                $label = (! isset($row['label'])) ? $row['field'] : $row['label'];

                // Here we go!
                $this->set_rules($row['field'], $label, $row['rules']);
            }

            return $this;
        }

        // No fields? Nothing to do...
        if (! is_string($field) or ! is_string($rules) or $field == '') {
            return $this;
        }

        // If the field label wasn't passed we use the field name
        $label = ($label == '') ? $field : $label;

        // Is the field name an array?  We test for the existence of a bracket "[" in
        // the field name to determine this.  If it is an array, we break it apart
        // into its components so that we can fetch the corresponding POST data later
        if (strpos($field, '[') !== false and preg_match_all('/\[(.*?)\]/', $field, $matches)) {
            // Note: Due to a bug in current() that affects some versions
            // of PHP we can not pass function call directly into it
            $x = explode('[', $field);
            $indexes[] = current($x);

            for ($i = 0; $i < count($matches['0']); $i++) {
                if ($matches['1'][$i] != '') {
                    $indexes[] = $matches['1'][$i];
                }
            }

            $is_array = true;
        } else {
            $indexes = array();
            $is_array = false;
        }

        // Build our master array
        $this->_field_data[$field] = array(
            'field' => $field,
            'label' => $label,
            'rules' => $rules,
            'is_array' => $is_array,
            'keys' => $indexes,
            'postdata' => null,
            'error' => ''
        );

        return $this;
    }

    /**
     * Validate Username
     *
     * Calls the custom field validation
     *
     * @access	public
     * @param	string
     * @param	string	update / new
     * @return	bool
     */
    public function call_field_validation($data, $field_id)
    {
        $error = '';
        $value = true;

        $exists = ee()->api_channel_fields->setup_handler($field_id);

        if (! $exists) {
            return true;
        }

        $res = ee()->api_channel_fields->apply('validate', array($data));

        if (is_array($res)) {
            // Overwrites $error and $value if they're set
            // array('error' => ..., 'value' => ...)

            extract($res);
        } else {
            $error = $res;
        }

        if ($error !== true && $error != '') {
            $this->set_message('call_field_validation', $error);

            return false;
        }

        return $value;
    }

    /**
     * Validate Username
     *
     * Checks if the submitted username is valid
     *
     * @access	public
     * @param	string
     * @param	string	update / new
     * @return	bool
     */
    public function valid_username($str, $type)
    {
        //deprecated, but will not throw deprecation error until 6.4
        //ee()->load->library('logger');
        //ee()->logger->deprecated('6.4', "ee('Validation')->validate()");

        if (! $type) {
            $type = 'update';
        }

        $str = trim_nbs($str);

        // Is username formatting correct?
        // Reserved characters:  |  "  '  ! < > { }
        if (preg_match("/[\|'\"!<>\{\}]/", $str)) {
            $this->set_message('valid_username', ee()->lang->line('invalid_characters_in_username'));

            return false;
        }

        // Is username min length correct?
        $len = ee()->config->item('un_min_len');

        if (strlen($str) < $len) {
            $this->set_message('valid_username', str_replace('%d', $len, ee()->lang->line('username_too_short')));

            return false;
        }

        // Is username max length correct?
        if (strlen($str) > 50) {
            $this->set_message('valid_username', ee()->lang->line('username_too_long'));

            return false;
        }

        if ($current = $this->old_value('username')) {
            if ($current != $str) {
                $type = 'new';
            }
        }

        if ($type == 'new') {
            // Is username banned?

            if (ee()->session->ban_check('username', $str)) {
                $this->set_message('valid_username', ee()->lang->line('username_taken'));

                return false;
            }

            // Is username taken?

            ee()->db->where('username', $str);
            $count = ee()->db->count_all_results('members');

            if ($count > 0) {
                $this->set_message('valid_username', ee()->lang->line('username_taken'));

                return false;
            }
        }

        return $str;
    }

    /**
     * Validate Screen Name
     *
     * Checks if the submitted screen name is valid
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function valid_screen_name($str)
    {
        //deprecated, but will not throw deprecation error until 6.4
        //ee()->load->library('logger');
        //ee()->logger->deprecated('6.4', "ee('Validation')->validate()");

        $data = [
            'screen_name' => $str
        ];

        $rules = array(
            'screen_name' => 'validScreenName|notBanned'
        );

        $result = ee('Validation')->make($rules)->validate($data);

        if ($result->isNotValid()) {
            foreach ($result->getErrors('screen_name') as $key => $error) {
                $this->set_message('valid_screen_name', $error);
            }
            return false;
        }

        return true;
    }

    /**
     * Validate Password
     *
     * Checks if the submitted password is valid
     *
     * @access	public
     * @param	string
     * @param	string	username field post key
     * @return	bool
     */
    public function valid_password($str, $username_field)
    {
        //deprecated, but will not throw deprecation error until 6.4
        //ee()->load->library('logger');
        //ee()->logger->deprecated('6.4', "ee('Validation')->validate()");

        if (! $username_field) {
            $username_field = 'username';
        }

        $data = [
            'username' => ee('Security/XSS')->clean($_POST[$username_field]),
            'password' => $str
        ];

        $rules = array(
            'password' => 'validPassword|passwordMatchesSecurityPolicy'
        );

        $result = ee('Validation')->make($rules)->validate($data);

        if ($result->isNotValid()) {
            foreach ($result->getErrors('password') as $key => $error) {
                $this->set_message('valid_password', $error);
            }
            return false;
        }

        return true;
    }

    /**
     * Authorize Password
     *
     * Checks if the submitted password is valid for the logged-in user
     *
     * @param	string 	$password 	Password string
     * @return	bool
     */
    public function auth_password($password, $use_auth_timeout)
    {
        $auth_timeout = ($use_auth_timeout === 'useAuthTimeout');

        if ($auth_timeout && ee('Session')->isWithinAuthTimeout()) {
            ee('Session')->resetAuthTimeout();

            return true;
        }

        ee()->load->library('auth');
        $validate = ee()->auth->authenticate_id(
            ee()->session->userdata('member_id'),
            $password
        );

        if ($validate !== false && $auth_timeout) {
            ee('Session')->resetAuthTimeout();
        }

        return ($validate !== false);
    }

    /**
     * Validate Email
     *
     * Checks if the submitted email is valid
     *
     * @access	public
     * @param	string
     * @param	string	update / new
     * @return	bool
     */
    public function valid_user_email($str, $type)
    {
        //deprecated, but will not throw deprecation error until 6.4
        //ee()->load->library('logger');
        //ee()->logger->deprecated('6.4', "ee('Validation')->validate()");

        if (! $type) {
            $type = 'update';
        }

        $str = trim_nbs($str);

        // Is email valid?

        if (! $this->valid_email($str)) {
            $this->set_message('valid_user_email', ee()->lang->line('invalid_email_address'));

            return false;
        }

        if ($current = $this->old_value('email')) {
            if ($current != $str) {
                $type = 'new';
            }
        }

        if ($type == 'new') {
            // Is email banned?

            if (ee()->session->ban_check('email', $str)) {
                $this->set_message('valid_user_email', ee()->lang->line('email_taken'));

                return false;
            }

            // Duplicate emails?
            if (! ee('Validation')->check('uniqueEmail', $str)) {
                $this->set_message('valid_user_email', ee()->lang->line('email_taken'));

                return false;
            }
        }

        return $str;
    }

    /**
     * Check to see if a date is valid by passing it to
     * Localize::string_to_timestamp
     *
     * @param  String $date Date value to validate
     * @return Boolean      TRUE if it's a date, FALSE otherwise
     */
    public function valid_date($date)
    {
        ee()->load->library('localize');

        return (ee()->localize->string_to_timestamp($date, true, ee()->localize->get_date_format()) != false);
    }

    /**
     * Check to see if a string is unchanged after running it through
     * Security::xss_clean()
     *
     * @param  String $string The string to validate
     * @return Boolean        TRUE if it's unchanged, FALSE otherwise
     */
    public function valid_xss_check($string)
    {
        $valid = ($string == ee('Security/XSS')->clean($string));

        if (! $valid) {
            ee()->lang->loadfile('admin');
            $this->set_message(
                'valid_xss_check',
                sprintf(lang('invalid_xss_check'), ee('CP/URL')->make('homepage'))
            );
        }

        return $valid;
    }

    /**
     * File exists
     *
     * Validation callback that checks if a file exits
     *
     * @param	string	$file	Path to file
     * @return	boolean
     */
    public function file_exists($file)
    {
        $parsed = rtrim(parse_config_variables($file, $_POST), '\\/');

        try {
            $filesystem = ee('File')->getPath($parsed);
            return $filesystem->exists($parsed) || $filesystem->exists($parsed . DIRECTORY_SEPARATOR);
        }catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Path/file writeable
     *
     * Validation callback that checks if a path/file is writeable
     *
     * @param	string	$path	Path or path to file file
     * @return	boolean
     */
    public function writable($path)
    {
        
        $parsed = rtrim(parse_config_variables($path, $_POST), '\\/');
        
        try {
            $filesystem = ee('File')->getPath($parsed);
            return $filesystem->isWritable($parsed) || $filesystem->isWritable($parsed . DIRECTORY_SEPARATOR);
        } catch (\Exception $e) {
            return false;
        }
        
    }

    /**
     * Set old value
     *
     * Required for some rules to exclude current value from the
     * *exists* checks (email, username, screen name)
     *
     * @access	public
     * @param	mixed
     * @return	void
     */
    public function set_old_value($key, $val = '')
    {
        if (! is_array($key)) {
            $this->old_values[$key] = $val;
        } else {
            $this->old_values = array_merge($this->old_values, $key);
        }
    }

    /**
     * Get old value
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function old_value($key)
    {
        return (isset($this->old_values[$key])) ? $this->old_values[$key] : '';
    }

    /**
     * Sets additional object to check callbacks against such as fieldtypes
     * to allow third-party fieldtypes to validate their settings forms
     *
     * @param	object 	Fieldtype to check callbacks against
     * @return	void
     */
    public function set_fieldtype($fieldtype)
    {
        $this->_fieldtype = $fieldtype;
    }

    /**
     * Get the value from a form
     *
     * Permits you to repopulate a form field with the value it was submitted
     * with, or, if that value doesn't exist, with the default
     *
     * @access	public
     * @param	string	the field name
     * @param	string
     * @return	void
     */
    public function set_value($field = '', $default = '')
    {
        if (! isset($this->_field_data[$field])) {
            if (isset($_POST[$field])) {
                return form_prep($_POST[$field], $field);
            }

            return $default;
        }

        return $this->_field_data[$field]['postdata'];
    }

    /**
     * Prep a list
     *
     * Unifies spaces/newlines/commas/pipes to $delim
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function prep_list($str, $delim = " ")
    {
        $str = trim($str);

        if ($delim == " ") {
            $str = preg_replace("/\t+/", " ", $str);
            $str = preg_replace("/\s+/", " ", $str);
            $str = preg_replace("/[,|]+/", " ", $str);
            $str = str_replace(array("\r\n", "\r", "\n"), " ", $str);
        } else {
            $str = preg_replace("/[\s,|]+/", $delim, $str);
            $str = trim($str, $delim);
        }

        return $str;
    }

    /**
     * Enum
     *
     * Check if a value is in a set
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function enum($str, $opts)
    {
        $this->set_message('enum', 'The option you selected is not valid.');

        $opts = explode(',', $opts);

        // For checkboxes, for example
        if (is_array($str)) {
            return count(array_intersect($str, $opts)) == count($str);
        }

        return in_array($str, $opts);
    }

    /**
     * Executes the Validation routines
     *
     * This is almost a direct copy out of the CI Form_validation lib.
     * however there are a couple of differences in order to work with EE.
     *
     * @param 	protected
     * @param	array
     * @param	array
     * @param	mixed
     * @param	integer
     * @return	mixed
     */
    public function _execute($row, $rules, $postdata = null, $cycles = 0)
    {
        // If the $_POST data is an array we will run a recursive call
        if (is_array($postdata)) {
            foreach ($postdata as $key => $val) {
                $this->_execute($row, $rules, $val, $cycles);
                $cycles++;
            }

            return;
        }

        // --------------------------------------------------------------------

        // If the field is blank, but NOT required, no further tests are necessary
        $callback = false;
        $ee_hack = false;

        if (! in_array('required', $rules) and is_null($postdata)) {
            // Before we bail out, does the rule contain a callback?
            if (preg_match("/(callback_\w+)/", implode(' ', $rules), $match)) {
                $callback = true;
                $rules = (array('1' => $match[1]));
            } elseif (preg_match("/(call_field_validation\[.*?\])/", implode(' ', $rules), $match)) {
                $ee_hack = true;
                $rules = array($match[1]);
            } else {
                return;
            }
        }

        // --------------------------------------------------------------------

        // Isset Test. Typically this rule will only apply to checkboxes.
        if (is_null($postdata) and $callback == false and $ee_hack == false) {
            if (in_array('isset', $rules, true) or in_array('required', $rules)) {
                // Set the message type
                $type = (in_array('required', $rules)) ? 'required' : 'isset';

                if (! isset($this->_error_messages[$type])) {
                    if (false === ($line = ee()->lang->line($type))) {
                        $line = 'The field was not set';
                    }
                } else {
                    $line = $this->_error_messages[$type];
                }

                // Build the error message
                $message = sprintf($line, $this->_translate_fieldname($row['label']));

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if (! isset($this->_error_array[$row['field']])) {
                    $this->_error_array[$row['field']] = $message;
                }
            }

            return;
        }

        // --------------------------------------------------------------------
        // Cycle through each rule and run it
        foreach ($rules as $rule) {
            $_in_array = false;

            // We set the $postdata variable with the current data in our master array so that
            // each cycle of the loop is dealing with the processed data from the last cycle
            if ($row['is_array'] == true and is_array($this->_field_data[$row['field']]['postdata'])) {
                // We shouldn't need this safety, but just in case there isn't an array index
                // associated with this cycle we'll bail out
                if (! isset($this->_field_data[$row['field']]['postdata'][$cycles])) {
                    continue;
                }

                $postdata = $this->_field_data[$row['field']]['postdata'][$cycles];
                $_in_array = true;
            } else {
                $postdata = $this->_field_data[$row['field']]['postdata'];
            }

            // --------------------------------------------------------------------

            // Is the rule a callback?
            $callback = false;
            if (substr($rule, 0, 9) == 'callback_') {
                $rule = substr($rule, 9);
                $callback = true;
            }

            // Strip the parameter (if exists) from the rule
            // Rules can contain a parameter: max_length[5]
            $param = false;
            if (preg_match("/(.*?)\[(.*?)\]/", $rule, $match)) {
                $rule = $match[1];
                $param = $match[2];
            }

            // Call the function that corresponds to the rule
            if ($callback === true) {
                // Check the controller for the callback first
                if (method_exists($this->CI, $rule)) {
                    $object = $this->CI;
                }
                // Check fieldtype for the callback
                elseif (!empty($this->_fieldtype) && method_exists($this->_fieldtype, $rule)) {
                    $object = $this->_fieldtype;
                } else {
                    continue;
                }

                // Run the function and grab the result
                $result = $object->$rule($postdata, $param);

                // Re-assign the result to the master data array
                if ($_in_array == true) {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                } else {
                    $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                }

                // If the field isn't required and we just processed a callback we'll move on...
                if (! in_array('required', $rules, true) and $result !== false) {
                    continue;
                }
            } else {

                if (method_exists($this, $rule)) {
                    //this is the rule defined by this very lib
                    $result = $this->$rule($postdata, $param);
                } else {
                    //is this valid stand-alone validation rule?
                    $rule_class = 'ExpressionEngine\\Service\\Validation\\Rule\\' . implode('', array_map('ucfirst', explode('_', $rule)));
                    if (class_exists($rule_class)) {
                        $validator = ee('Validation')->make(array(
                            $row['field'] => $rule
                        ));
                        $validation = $validator->validate($_POST);
                        $result = $validation->isValid();
                        if ($result == false) {
                            $error = $validation->getErrors($row['field']);
                            $this->set_message($rule, array_shift($error));
                        }
                    } else {
                        // If our own wrapper function doesn't exist we see if a native PHP function does.
                        // Users can use any native PHP function call that has one param.
                        if (function_exists($rule)) {
                            $result = $rule($postdata);

                            if ($_in_array == true) {
                                $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                            } else {
                                $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                            }
                        }

                        continue;
                    }
                }

                if ($_in_array == true) {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                } else {
                    $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                }
            }

            // Did the rule test negatively?  If so, grab the error.
            if ($result === false) {
                if (! isset($this->_error_messages[$rule])) {
                    if (false === ($line = ee()->lang->line($rule))) {
                        $line = 'Unable to access an error message corresponding to your field name.';
                    }
                } else {
                    $line = $this->_error_messages[$rule];
                }

                // Is the parameter we are inserting into the error message the name
                // of another field?  If so we need to grab its "field label"
                if (isset($this->_field_data[$param]) and isset($this->_field_data[$param]['label'])) {
                    $param = $this->_translate_fieldname($this->_field_data[$param]['label']);
                }

                // Build the error message
                $message = sprintf($line, $param);

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if (! isset($this->_error_array[$row['field']])) {
                    $this->_error_array[$row['field']] = $message;
                }

                return;
            }
        }
    }

    /**
     * Lookup Dictionary Word
     *
     * Checks if a word is in the dictionary
     *
     * @access	private
     * @param	string
     * @param	string	update / new
     * @return	bool
     */
    public function _lookup_dictionary_word($target)
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

    /**
     * Set Error Message
     *
     * Lets users set their own error messages on the fly.  Note:  The key
     * name has to match the  function name that it corresponds to.
     *
     * @access	public
     * @param	string
     * @param	string
     * @return	string
     */
    public function set_message($lang, $val = '')
    {
        if (! is_array($lang)) {
            $lang = array($lang => $val);
        }

        $this->_error_messages = array_merge($this->_error_messages, $lang);

        return $this;
    }

    /**
     * Set The Error Delimiter
     *
     * Permits a prefix/suffix to be added to each error message
     *
     * @access	public
     * @param	string
     * @param	string
     * @return	void
     */
    public function set_error_delimiters($prefix = '<p>', $suffix = '</p>')
    {
        $this->_error_prefix = $prefix;
        $this->_error_suffix = $suffix;

        return $this;
    }

    /**
     * Get Error Message
     *
     * Gets the error message associated with a particular field
     *
     * @access	public
     * @param	string	the field name
     * @return	void
     */
    public function error($field = '', $prefix = '', $suffix = '')
    {
        if (! isset($this->_field_data[$field]['error']) or $this->_field_data[$field]['error'] == '') {
            return '';
        }

        if ($prefix == '') {
            $prefix = $this->_error_prefix;
        }

        if ($suffix == '') {
            $suffix = $this->_error_suffix;
        }

        return $prefix . $this->_field_data[$field]['error'] . $suffix;
    }

    /**
     * Error String
     *
     * Returns the error messages as a string, wrapped in the error delimiters
     *
     * @access	public
     * @param	string
     * @param	string
     * @return	str
     */
    public function error_string($prefix = '', $suffix = '')
    {
        // No errrors, validation passes!
        if (count($this->_error_array) === 0) {
            return '';
        }

        if ($prefix == '') {
            $prefix = $this->_error_prefix;
        }

        if ($suffix == '') {
            $suffix = $this->_error_suffix;
        }

        // Generate the error string
        $str = '';
        foreach ($this->_error_array as $val) {
            if ($val != '') {
                $str .= $prefix . $val . $suffix . "\n";
            }
        }

        return $str;
    }

    /**
     * Run the Validator
     *
     * This function does all the work.
     *
     * @access	public
     * @return	bool
     */
    public function run($group = '')
    {
        // Do we even have any data to process?  Mm?
        if (count($_POST) == 0) {
            return false;
        }

        // Does the _field_data array containing the validation rules exist?
        // If not, we look to see if they were assigned via a config file
        if (count($this->_field_data) == 0) {
            // No validation rules?  We're done...
            if (count($this->_config_rules) == 0) {
                return false;
            }

            // Is there a validation rule for the particular URI being accessed?
            $uri = ($group == '') ? trim(ee()->uri->ruri_string(), '/') : $group;

            if ($uri != '' and isset($this->_config_rules[$uri])) {
                $this->set_rules($this->_config_rules[$uri]);
            } else {
                $this->set_rules($this->_config_rules);
            }

            // We're we able to set the rules correctly?
            if (count($this->_field_data) == 0) {
                log_message('debug', "Unable to find validation rules");

                return false;
            }
        }

        // Load the language file containing error messages
        ee()->lang->load('form_validation');

        // Cycle through the rules for each field, match the
        // corresponding $_POST item and test for errors
        foreach ($this->_field_data as $field => $row) {
            // Fetch the data from the corresponding $_POST array and cache it in the _field_data array.
            // Depending on whether the field name is an array or a string will determine where we get it from.

            if ($row['is_array'] == true) {
                $this->_field_data[$field]['postdata'] = $this->_reduce_array($_POST, $row['keys']);
            } else {
                if (isset($_POST[$field]) and $_POST[$field] != "") {
                    $this->_field_data[$field]['postdata'] = $_POST[$field];
                }
            }

            $this->_execute($row, explode('|', $row['rules']), $this->_field_data[$field]['postdata']);
        }

        // Did we end up with any errors?
        $total_errors = count($this->_error_array);

        if ($total_errors > 0) {
            $this->_safe_form_data = true;
        }

        // Now we need to re-set the POST data with the new, processed data
        $this->_reset_post_array();

        // No errors, validation passes!
        if ($total_errors == 0) {
            return true;
        }

        // Validation fails
        return false;
    }

    /**
     * Traverse a multidimensional $_POST array index until the data is found
     *
     * @access	private
     * @param	array
     * @param	array
     * @param	integer
     * @return	mixed
     */
    public function _reduce_array($array, $keys, $i = 0)
    {
        if (is_array($array)) {
            if (isset($keys[$i])) {
                if (isset($array[$keys[$i]])) {
                    $array = $this->_reduce_array($array[$keys[$i]], $keys, ($i + 1));
                } else {
                    return null;
                }
            } else {
                return $array;
            }
        }

        return $array;
    }

    /**
     * Re-populate the _POST array with our finalized and processed data
     *
     * @access	private
     * @return	null
     */
    public function _reset_post_array()
    {
        foreach ($this->_field_data as $field => $row) {
            if (! is_null($row['postdata'])) {
                if ($row['is_array'] == false) {
                    if (isset($_POST[$row['field']])) {
                        $_POST[$row['field']] = $this->prep_for_form($row['postdata']);
                    }
                } else {
                    // start with a reference
                    $post_ref = & $_POST;

                    // before we assign values, make a reference to the right POST key
                    if (count($row['keys']) == 1) {
                        $post_ref = & $post_ref[current($row['keys'])];
                    } else {
                        foreach ($row['keys'] as $val) {
                            $post_ref = & $post_ref[$val];
                        }
                    }

                    if (is_array($row['postdata'])) {
                        $array = array();
                        foreach ($row['postdata'] as $k => $v) {
                            $array[$k] = $this->prep_for_form($v);
                        }

                        $post_ref = $array;
                    } else {
                        $post_ref = $this->prep_for_form($row['postdata']);
                    }
                }
            }
        }
    }

    /**
     * Translate a field name
     *
     * @access	private
     * @param	string	the field name
     * @return	string
     */
    public function _translate_fieldname($fieldname)
    {
        // Do we need to translate the field name?
        // We look for the prefix lang: to determine this
        if (substr($fieldname, 0, 5) == 'lang:') {
            // Grab the variable
            $line = substr($fieldname, 5);

            // Were we able to translate the field name?  If not we use $line
            if (false === ($fieldname = ee()->lang->line($line))) {
                return $line;
            }
        }

        return $fieldname;
    }

    /**
     * Set Select
     *
     * Enables pull-down lists to be set to the value the user
     * selected in the event of an error
     *
     * @access	public
     * @param	string
     * @param	string
     * @return	string
     */
    public function set_select($field = '', $value = '', $default = false)
    {
        if (! isset($this->_field_data[$field]) or ! isset($this->_field_data[$field]['postdata'])) {
            if ($default === true and count($this->_field_data) === 0) {
                return ' selected="selected"';
            }

            return '';
        }

        $field = $this->_field_data[$field]['postdata'];

        if (is_array($field)) {
            if (! in_array($value, $field)) {
                return '';
            }
        } else {
            if (($field == '' or $value == '') or ($field != $value)) {
                return '';
            }
        }

        return ' selected="selected"';
    }

    /**
     * Set Radio
     *
     * Enables radio buttons to be set to the value the user
     * selected in the event of an error
     *
     * @access	public
     * @param	string
     * @param	string
     * @return	string
     */
    public function set_radio($field = '', $value = '', $default = false)
    {
        if (! isset($this->_field_data[$field]) or ! isset($this->_field_data[$field]['postdata'])) {
            if ($default === true and count($this->_field_data) === 0) {
                return ' checked="checked"';
            }

            return '';
        }

        $field = $this->_field_data[$field]['postdata'];

        if (is_array($field)) {
            if (! in_array($value, $field)) {
                return '';
            }
        } else {
            if (($field == '' or $value == '') or ($field != $value)) {
                return '';
            }
        }

        return ' checked="checked"';
    }

    /**
     * Set Checkbox
     *
     * Enables checkboxes to be set to the value the user
     * selected in the event of an error
     *
     * @access	public
     * @param	string
     * @param	string
     * @return	string
     */
    public function set_checkbox($field = '', $value = '', $default = false)
    {
        if (! isset($this->_field_data[$field]) or ! isset($this->_field_data[$field]['postdata'])) {
            if ($default === true and count($this->_field_data) === 0) {
                return ' checked="checked"';
            }

            return '';
        }

        $field = $this->_field_data[$field]['postdata'];

        if (is_array($field)) {
            if (! in_array($value, $field)) {
                return '';
            }
        } else {
            if (($field == '' or $value == '') or ($field != $value)) {
                return '';
            }
        }

        return ' checked="checked"';
    }

    /**
     * Required
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function required($str)
    {
        if (! is_array($str)) {
            return (trim($str) == '') ? false : true;
        } else {
            return (! empty($str));
        }
    }

    /**
     * Match one field to another
     *
     * @access	public
     * @param	string
     * @param	field
     * @return	bool
     */
    public function matches($str, $field)
    {
        if (! isset($_POST[$field])) {
            return false;
        }

        $field = $_POST[$field];

        return ($str !== $field) ? false : true;
    }

    /**
     * Minimum Length
     *
     * @access	public
     * @param	string
     * @param	value
     * @return	bool
     */
    public function min_length($str, $val)
    {
        if (preg_match("/[^0-9]/", $val)) {
            return false;
        }

        return (ee_mb_strlen($str) < $val) ? false : true;
    }

    /**
     * Max Length
     *
     * @access	public
     * @param	string
     * @param	value
     * @return	bool
     */
    public function max_length($str, $val)
    {
        if (preg_match("/[^0-9]/", $val)) {
            return false;
        }

        return (ee_mb_strlen($str) > $val) ? false : true;
    }

    /**
     * Exact Length
     *
     * @access	public
     * @param	string
     * @param	value
     * @return	bool
     */
    public function exact_length($str, $val)
    {
        if (preg_match("/[^0-9]/", $val)) {
            return false;
        }

        return (ee_mb_strlen($str) != $val) ? false : true;
    }

    /**
     * Valid Email
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function valid_email($str)
    {
        return (bool) filter_var($str, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Valid Emails
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function valid_emails($str)
    {
        if (strpos($str, ',') === false) {
            return $this->valid_email(trim($str));
        }

        foreach (explode(',', $str) as $email) {
            if (trim($email) != '' && $this->valid_email(trim($email)) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate IP Address
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function valid_ip($ip)
    {
        return ee()->input->valid_ip($ip);
    }

    /**
     * Alpha
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function alpha($str)
    {
        return (! preg_match("/^([a-z])+$/i", $str)) ? false : true;
    }

    /**
     * Alpha-numeric
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function alpha_numeric($str)
    {
        return (! preg_match("/^([a-z0-9])+$/i", $str)) ? false : true;
    }

    /**
     * Alpha-numeric with underscores and dashes
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function alpha_dash($str)
    {
        return (! preg_match("/^([-a-z0-9_-])+$/i", $str)) ? false : true;
    }

    /**
     * Alpha-numeric with underscores, dashes, and spaces
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function alpha_dash_space($str)
    {
        return (! preg_match("/^([a-z0-9\_\-\s])+$/i", $str)) ? false : true;
    }

    /**
     * Numeric
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function numeric($str)
    {
        return (bool) preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $str);
    }

    /**
     * Is Numeric
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function is_numeric($str)
    {
        return (! is_numeric($str)) ? false : true;
    }

    /**
     * Integer
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function integer($str)
    {
        return (bool) preg_match('/^[\-+]?[0-9]+$/', $str);
    }

    /**
    * Greater than
    *
    * @param	string
    * @param	int
    * @return	bool
    */
    public function greater_than($str, $min)
    {
        return is_numeric($str) ? ($str > $min) : false;
    }

    /**
    * Equal to or Greater than
    *
    * @param	string
    * @param	int
    * @return	bool
    */
    public function greater_than_equal_to($str, $min)
    {
        return is_numeric($str) ? ($str >= $min) : false;
    }

    /**
    * Less than
    *
    * @param	string
    * @param	int
    * @return	bool
    */
    public function less_than($str, $max)
    {
        return is_numeric($str) ? ($str < $max) : false;
    }

    /**
    * Equal to or Less than
    *
    * @param	string
    * @param	int
    * @return	bool
    */
    public function less_than_equal_to($str, $max)
    {
        return is_numeric($str) ? ($str <= $max) : false;
    }

    /**
     * Is a Natural number  (0,1,2,3, etc.)
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function is_natural($str)
    {
        return (bool) preg_match('/^[0-9]+$/', $str);
    }

    /**
     * Is a Natural number, but not a zero  (1,2,3, etc.)
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function is_natural_no_zero($str)
    {
        if (! preg_match('/^[0-9]+$/', $str)) {
            return false;
        }

        if ($str == 0) {
            return false;
        }

        return true;
    }

    /**
     * Valid Base64
     *
     * Tests a string for characters outside of the Base64 alphabet
     * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function valid_base64($str)
    {
        return (bool) ! preg_match('/[^a-zA-Z0-9\/\+=]/', $str);
    }

    /**
     * Prep data for form
     *
     * This function allows HTML to be safely shown in a form.
     * Special characters are converted.
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function prep_for_form($data = '')
    {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = $this->prep_for_form($val);
            }

            return $data;
        }

        if ($this->_safe_form_data == false or $data === '') {
            return $data;
        }

        return str_replace(array("'", '"', '<', '>'), array("&#39;", "&quot;", '&lt;', '&gt;'), stripslashes($data));
    }

    /**
     * Prep URL
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function prep_url($str = '')
    {
        return (string) ee('Format')->make('Text', $str)->url();
    }

    /**
     * Strip Image Tags
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function strip_image_tags($str)
    {
        return ee()->input->strip_image_tags($str);
    }

    /**
     * XSS Clean
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function xss_clean($str)
    {
        return ee('Security/XSS')->clean($str);
    }

    /**
     * Convert PHP tags to entities
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function encode_php_tags($str)
    {
        return str_replace(array('<?php', '<?PHP', '<?', '?>', '<%', '%>'), array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;', '&lt;%', '%&gt;'), $str);
    }
}

// EOF
