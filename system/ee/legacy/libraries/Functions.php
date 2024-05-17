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
 * Core Functions
 */
class EE_Functions
{
    public $seed = false; // Whether we've seeded our rand() function.  We only seed once per script execution
    public $cached_url = array();
    public $cached_path = array();
    public $cached_index = array();
    public $cached_captcha = '';
    public $template_map = array();
    public $template_type = '';
    public $action_ids = array();
    public $file_paths = array();
    public $conditional_debug = false;
    public $catfields = array();
    protected $cat_array = array();
    protected $temp_array = array();
    public static $protected_data = array();

    /**
     * Fetch base site index
     *
     * @access public
     * @param bool
     * @param bool
     * @return string
     */
    public function fetch_site_index($add_slash = false, $sess_id = true)
    {
        if (isset($this->cached_index[$add_slash . $sess_id . $this->template_type])) {
            return $this->cached_index[$add_slash . $sess_id . $this->template_type];
        }

        $url = ee()->config->slash_item('site_url');

        $url .= ee()->config->item('site_index');

        if (ee()->config->item('force_query_string') == 'y') {
            $url .= '?';
        }

        if (ee()->config->item('website_session_type') != 'c' && is_object(ee()->session) && REQ != 'CP' && $sess_id == true && $this->template_type == 'webpage') {
            $url .= (ee()->session->session_id('user')) ? "/S=" . ee()->session->session_id('user') . "/" : '';
        }

        if ($add_slash == true) {
            if (substr($url, -1) != '/') {
                $url .= "/";
            }
        }

        $this->cached_index[$add_slash . $sess_id . $this->template_type] = $url;

        return $url;
    }

    /**
     * Create a URL for a Template Route
     *
     * The input to this function is parsed and added to the
     * full site URL to create a full URL/URI
     *
     * @access public
     * @param string
     * @param bool
     * @return string
     */
    public function create_route($segment, $sess_id = true)
    {
        if (is_array($segment)) {
            $tag = trim($segment[0], "{}");
            $segment = $segment[1];
        }

        if (isset($this->cached_url[$segment])) {
            return $this->cached_url[$segment];
        }

        $full_segment = $segment;
        $parts = ee('Variables/Parser')->parseTagParameters($tag);

        $template = $parts['route'];
        $template = trim($template, '"\' ');
        list($group, $template) = explode('/', $template);

        if (! empty($group) && ! empty($template)) {
            ee()->load->library('template_router');
            $route = ee()->template_router->fetch_route($group, $template);

            if (empty($route)) {
                return "{route=$segment}";
            } else {
                unset($parts['route']);
                $segment = $route->build($parts);
            }
        }

        $base = $this->fetch_site_index(0, $sess_id) . '/' . trim_slashes($segment);

        $out = reduce_double_slashes($base);

        $this->cached_url[$full_segment] = $out;

        return $out;
    }

    /**
     * Create a custom URL
     *
     * The input to this function is parsed and added to the
     * full site URL to create a full URL/URI
     *
     * @access public
     * @param string
     * @param bool
     * @return string
     */
    public function create_url($segment, $sess_id = true)
    {
        // Since this function can be used via a callback
        // we'll fetch the segment if it's an array
        if (is_array($segment)) {
            $segment = $segment[1];
        }

        if (isset($this->cached_url[$segment])) {
            return $this->cached_url[$segment];
        }

        $full_segment = $segment;
        $segment = str_replace(array("'", '"'), '', $segment);
        $segment = preg_replace("/(.+?(\/))index(\/)(.*?)/", "\\1\\2", $segment);
        $segment = preg_replace("/(.+?(\/))index$/", "\\1", $segment);

        // These are exceptions to the normal path rules
        if ($segment == '' or strtolower($segment) == 'site_index') {
            return $this->fetch_site_index();
        }

        if (strtolower($segment) == 'logout') {
            $qs = (ee()->config->item('force_query_string') == 'y') ? '' : '?';
            $xid = bool_config_item('disable_csrf_protection') ? '' : AMP . 'csrf_token=' . CSRF_TOKEN;

            return $this->fetch_site_index(0, 0) . $qs . 'ACT=' . $this->fetch_action_id('Member', 'member_logout') . $xid;
        }

        // END Specials

        $base = $this->fetch_site_index(0, $sess_id) . '/' . trim_slashes($segment);

        $out = reduce_double_slashes($base);

        $this->cached_url[$full_segment] = $out;

        return $out;
    }

    /**
     * Creates a url for Pages links
     *
     * @access public
     * @return string
     */
    public function create_page_url($base_url, $segment, $trailing_slash = false)
    {
        if (ee()->config->item('force_query_string') == 'y') {
            if (strpos($base_url, ee()->config->item('index_page') . '/') !== false) {
                $base_url = rtrim($base_url, '/');
            }

            $base_url .= '?';
        }

        $base = $base_url . '/' . trim_slashes($segment);

        if (substr($base, -1) != '/' && $trailing_slash == true) {
            $base .= '/';
        }

        $out = reduce_double_slashes($base);

        return parse_config_variables($out);
    }

    /**
     * Fetch site index with URI query string
     *
     * @access public
     * @return string
     */
    public function fetch_current_uri()
    {
        $url = rtrim(reduce_double_slashes($this->fetch_site_index(1) . ee()->uri->uri_string), '/');
        $url = str_replace(array('"', "'"), array('%22', '%27'), $url);

        return $url;
    }

    /**
     * Prep Query String
     *
     * This function checks to see if "Force Query Strings" is on.
     * If so it adds a question mark to the URL if needed
     *
     * @access public
     * @param string
     * @return string
     */
    public function prep_query_string($str)
    {
        if (stristr($str, '.php') && substr($str, -7) == '/index/') {
            $str = substr($str, 0, -6);
        }

        if (strpos($str, '?') === false && ee()->config->item('force_query_string') == 'y') {
            if (stristr($str, '.php')) {
                $str = preg_replace("#(.+?)\.php(.*?)#", "\\1.php?\\2", $str);
            } else {
                $str .= "?";
            }
        }

        return $str;
    }

    /**
     * Convert EE Tags to Entities
     *
     * @access public
     * @param string $str            String with EE Tags to encode
     * @param bool   $convert_curly  Set to TRUE to convert all curly brackets
     *                               to entities, otherwise only {exp:...,
     *                               {embed..., {path.., {redirect..., and
     *                               {if... tags are encoded
     * @return string String with encoded EE tags
     */
    public function encode_ee_tags($str, $convert_curly = false)
    {
        return (string) ee('Format')->make('Text', $str)->encodeEETags(['encode_vars' => $convert_curly]);
    }

    /**
     * Extract path info
     *
     * We use this to extract the template group/template name
     * from path variables, like {some_var path="channel/index"}
     *
     * @access public
     * @param string
     * @return string
     */
    public function extract_path($str)
    {
        if (preg_match("#=(.*)#", $str, $match)) {
            $match[1] = trim($match[1], '}');

            if (isset($this->cached_path[$match[1]])) {
                return $this->cached_path[$match[1]];
            }

            $path = trim_slashes(str_replace(array("'",'"'), "", $match[1]));

            if (substr($path, -6) == 'index/') {
                $path = str_replace('/index', '', $path);
            }

            if (substr($path, -5) == 'index') {
                $path = str_replace('/index', '', $path);
            }

            $this->cached_path[$match[1]] = $path;

            return $path;
        } else {
            return 'SITE_INDEX';
        }
    }

    /**
     * Replace variables
     *
     * @access public
     * @param string
     * @param string
     * @return string
     */
    public function var_swap($str, $data)
    {
        if (! is_array($data)) {
            return false;
        }

        foreach ($data as $key => $val) {
            $str = str_replace('{' . $key . '}', (string)$val, $str);
        }

        return $str;
    }

    /**
     * Create an encrypted string of form params for the front-end forms.
     * @param  array  $params Custom params for specific forms
     * @return string         Encoded data
     */
    public function get_protected_form_params($params = array())
    {
        // Setup some default params that every front-end form can use.
        $default_params = array(
            'return_error' => ee()->TMPL->fetch_param('return_error'),
            'inline_errors' => ee()->TMPL->fetch_param('inline_errors'),
        );

        // Merge in any custom params.
        $params = array_merge($default_params, $params);

        return $this->protect_data($params);
    }

    /**
     * Take array data and encode it for use in form posts.
     * @param  array $data Array data you want to encode
     * @return string      Encoded data
     */
    public function protect_data($data)
    {
        if (! is_array($data)) {
            return false;
        }

        // JSON encode the data (as the encrypt system expects a string) and then encrypt it.
        return ee('Encrypt')->encode(json_encode($data));
    }

    /**
     * Check for and decode protected form data for use after form submission.
     * @return string|array|boolean The decoded data or false if no data exists.
     */
    public function handle_protected()
    {
        // Grab the protected data from the posted form.
        $protected = ee()->input->get_post('P');

        if (! empty($protected)) {
            // Decrypt and json decode the resulting data.
            self::$protected_data = json_decode(ee('Encrypt')->decode($protected), true);

            // Sanity check that the data decrypted / decoded properly.
            if (! is_array(self::$protected_data)) {
                self::$protected_data = array();
            }
        }

        return self::$protected_data;
    }

    /**
     * Determine the return link based on various factors. Used in form returns.
     * @return  string|bool  URL to redirect to or false
     */
    public function determine_return($go_to_index = false)
    {
        $return = ee()->input->get_post('RET');
        $return_link = false;

        if (empty($return) && $go_to_index === true) {
            // If we don't have a return in the POST and we've specified to go to the site index.
            $return_link = ee()->functions->fetch_site_index();
        } elseif (is_numeric($return)) {
            // If the return is a number, it's a reference to how many pages back we have to go.
            $return_link = ee()->functions->form_backtrack($return);
        } elseif (substr(strtolower($return), 0, 4) === 'http') {
            // If we're using a fully qualified URL, don't modify it.
            $return_link = $return;
        } else {
            // If we're here, the return is a relative URL or template path so prepend the site URL to it.
            $return_link = ee()->functions->create_url((string) $return);
        }

        return $return_link;
    }

    /**
     * Determine the return link based on various factors. Used in form returns.
     * @return string URL to redirect to
     */
    public function determine_error_return()
    {
        // Find out if we have the `return_error` param in our protected data.
        if (! empty(self::$protected_data['return_error'])) {
            return self::$protected_data['return_error'];
        } elseif (! empty(self::$protected_data['inline_errors']) && self::$protected_data['inline_errors'] === 'yes') {
            // If they specified inline errors, return to the page the form submitted from.
            return ee()->functions->form_backtrack(1);
        }

        // There was no return page or inline specified so the error will go to the standard output.
        return false;
    }

    /**
     * Redirect
     *
     * @access public
     * @param string
     * @return void
     */
    public function redirect($location, $method = false, $status_code = null)
    {
        // Remove hard line breaks and carriage returns
        $location = str_replace(array("\n", "\r"), '', $location);

        // Remove any and all line breaks
        while (stripos($location, '%0d') !== false or stripos($location, '%0a') !== false) {
            $location = str_ireplace(array('%0d', '%0a'), '', $location);
        }

        $location = $this->insert_action_ids($location);
        $location = ee()->uri->reformat($location);

        if (isset(ee()->session) && count(ee()->session->flashdata)) {
            // Ajax requests don't redirect - serve the flashdata

            if (ee()->input->is_ajax_request()) {
                // We want the data that would be available for the next request
                ee()->session->_age_flashdata();

                die(json_encode(ee()->session->flashdata));
            }
        }

        if ($method === false) {
            $method = ee()->config->item('redirect_method');
        }

        switch ($method) {
            case 'refresh':
                $header = "Refresh: 0;url=$location";

                break;
            default:
                $header = "Location: $location";

                break;
        }

        if ($status_code !== null && $status_code >= 300 && $status_code <= 308) {
            header($header, true, $status_code);
        } else {
            header($header);
        }

        exit;
    }

    /**
     * Random number/password generator
     *
     * @access public
     * @param string
     * @param int
     * @return string
     */
    public function random($type = 'encrypt', $len = 8)
    {
        return random_string($type, $len);
    }

    /**
     * Form declaration
     *
     * This function is used by modules when they need to create forms
     *
     * @access public
     * @param string
     * @return string
     */
    public function form_declaration($data)
    {
        // Load the form helper
        ee()->load->helper('form');

        // Load the default values for parameters that can be provided via $data array variable
        // NB. secure and hidden_fields are not legit HTML form tags, and so excluded from pass_thru
        $deft = array(
            'hidden_fields' => array(),
            'action' => '',
            'id' => '',
            'name' => '',
            'class' => '',
            'secure' => true,
            'enctype' => '',
            'onsubmit' => '',
            'data_attributes' => '',
            'target' => ''
        );

        // Set values for 'missing' default keys in $data to their default values
        foreach ($deft as $key => $val) {
            if (! isset($data[$key])) {
                $data[$key] = $val;
            }
        }

        if (is_array($data['hidden_fields']) && ! isset($data['hidden_fields']['site_id'])) {
            $data['hidden_fields']['site_id'] = ee()->config->item('site_id');
        }

        // -------------------------------------------
        // 'form_declaration_modify_data' hook.
        //  - Modify the $data parameters before they are processed
        //  - Added EE 1.4.0
        //
        if (ee()->extensions->active_hook('form_declaration_modify_data') === true) {
            $data = ee()->extensions->call('form_declaration_modify_data', $data);
        }
        //
        // -------------------------------------------

        // -------------------------------------------
        // 'form_declaration_return' hook.
        //  - Take control of the form_declaration function
        //  - Added EE 1.4.0
        //
        if (ee()->extensions->active_hook('form_declaration_return') === true) {
            $form = ee()->extensions->call('form_declaration_return', $data);
            if (ee()->extensions->end_script === true) {
                return $form;
            }
        }
        //
        // -------------------------------------------

        if ($data['action'] == '') {
            $data['action'] = $this->fetch_site_index();
        }

        if ($data['onsubmit'] != '') {
            $data['onsubmit'] = 'onsubmit="' . trim($data['onsubmit']) . '"';
        }

        if (substr($data['action'], -1) == '?') {
            $data['action'] = substr($data['action'], 0, -1);
        }

        if (isset(ee()->TMPL)) {
            // set form ID and class, if set by tagparam
            if (empty($data['id'])) {
                $data['id'] = ee()->TMPL->form_id;
            }
            if (empty($data['class'])) {
                $data['form_class'] = ee()->TMPL->form_class;
            }
        }

        $data['name'] = ($data['name'] != '') ? 'name="' . $data['name'] . '" ' : '';
        $data['id'] = ($data['id'] != '') ? 'id="' . $data['id'] . '" ' : '';
        $data['class'] = ($data['class'] != '') ? 'class="' . $data['class'] . '" ' : '';
        $data['target'] = ($data['target'] != '') ? 'target="' . $data['target'] . '" ' : '';

        if ($data['enctype'] == 'multi' or strtolower($data['enctype']) == 'multipart/form-data') {
            $data['enctype'] = 'enctype="multipart/form-data" ';
        }

        foreach ($data as $key => $val) {
            if (strpos($key, 'data-') === 0 || strpos($key, 'aria-') === 0) {
                $data['data_attributes'] .= ee('Security/XSS')->clean($key) . '="' . htmlentities(ee('Security/XSS')->clean($val), ENT_QUOTES, 'UTF-8') . '" ';
            }
        }

        // Next section is for the 'pass-through' functionality
        $_pass_thru = array();
        $valid_form_attributes = array();
        $_pass_thru_string = '';

        /**
         * Valid HTML Form attributes are determined based on the list in
         * on the config/valid_form_attributes.php file .
         */

        $valid_form_attributes = ee()->config->loadFile('valid_form_attributes');

        if (isset(ee()->TMPL) && ! empty(ee()->TMPL->tagparams) && ! empty($valid_form_attributes)) {
            foreach (ee()->TMPL->tagparams as $key => $val) {
                // Ignore the parameter if $key is defined in $deft (and so already being processed by function)
                // or if the parameter is not in the list of approved attributes
                // or if the parameter begins with either aria- or data-
                if (! array_key_exists(strtolower($key), $deft) && in_array(strtolower($key), $valid_form_attributes)) {
                    // Append the key to end of the $_pass_thru variable
                    // If the attribute has a value set then add this value to the key enclosed within an ="" construct
                    $_pass_thru[$key] = (strlen($val) > 0) ? '="' . htmlentities(ee('Security/XSS')->clean($val), ENT_QUOTES, 'UTF-8') . '"' : '';
                }
                // data- and aria- attributes can also be passed through
                if (strpos($key, 'data-') === 0 || strpos($key, 'aria-') === 0) {
                    $data['data_attributes'] .= ee('Security/XSS')->clean(strip_tags($key)) . '="' . htmlentities(ee('Security/XSS')->clean($val), ENT_QUOTES, 'UTF-8') . '" ';
                }
            }
        }
        // Build pass-through attribute string
        foreach ($_pass_thru as $key => $val) {
            $_pass_thru_string .= " " . $key . (strlen($val) > 0 ? $val : '');
        }

        // Construct the opening form tag - including appending any pass_thru parameters
        $form = '<form ' . $data['id'] . $data['class'] . $data['name'] . $data['target'] . $data['data_attributes'] . 'method="post" action="' . $data['action'] . '" ' . $data['onsubmit'] . ' ' . $data['enctype'] . $_pass_thru_string . ">\n";

        if ($data['secure'] == true) {
            unset($data['hidden_fields']['XID']);
            $data['hidden_fields']['csrf_token'] = '{csrf_token}'; // we use the tag instead of the constant to allow caching of the template
        }

        if (is_array($data['hidden_fields'])) {
            $form .= "<div class='hiddenFields'>\n";

            foreach ($data['hidden_fields'] as $key => $val) {
                $form .= '<input type="hidden" name="' . $key . '" value="' . form_prep($val) . '" />' . "\n";
            }

            $form .= "</div>\n\n";
        }

        return $form;
    }

    /**
     * Form backtrack
     *
     * This function lets us return a user to a previously
     * visited page after submitting a form.  The page
     * is determined by the offset that the admin
     * places in each form
     *
     * @access public
     * @param string
     * @return string
     */
    public function form_backtrack($offset = '')
    {
        $ret = $this->fetch_site_index();

        if ($offset != '') {
            if (isset(ee()->session->tracker[$offset])) {
                if (ee()->session->tracker[$offset] != 'index') {
                    return reduce_double_slashes($this->fetch_site_index() . '/' . ee()->session->tracker[$offset]);
                }
            }
        }

        if (isset($_POST['RET'])) {
            if (strncmp($_POST['RET'], '-', 1) == 0) {
                $return = str_replace("-", "", $_POST['RET']);

                if (isset(ee()->session->tracker[$return])) {
                    if (ee()->session->tracker[$return] != 'index') {
                        $ret = $this->fetch_site_index() . '/' . ee()->session->tracker[$return];
                    }
                }
            } else {
                if (strpos($_POST['RET'], '/') !== false) {
                    if (
                        strncasecmp($_POST['RET'], 'http://', 7) == 0 or
                        strncasecmp($_POST['RET'], 'https://', 8) == 0 or
                        strncasecmp($_POST['RET'], 'www.', 4) == 0
                    ) {
                        $ret = $_POST['RET'];
                    } else {
                        $ret = $this->create_url($_POST['RET']);
                    }
                } else {
                    $ret = $_POST['RET'];
                }
            }

            // We need to slug in the session ID if the admin is running
            // their site using sessions only.  Normally the ee()->functions->fetch_site_index()
            // function adds the session ID automatically, except in cases when the
            // $_POST['RET'] variable is set. Since the login routine relies on the RET
            // info to know where to redirect back to we need to sandwich in the session ID.
            if (ee()->config->item('website_session_type') != 'c') {
                $id = ee()->session->session_id('user');

                if ($id != '' && ! stristr($ret, $id)) {
                    $url = ee()->config->slash_item('site_url');

                    $url .= ee()->config->item('site_index');

                    if (ee()->config->item('force_query_string') == 'y') {
                        $url .= '?';
                    }

                    $sess_id = "/S=" . $id . "/";

                    $ret = str_replace($url, $url . $sess_id, $ret);
                }
            }
        }

        return reduce_double_slashes($ret);
    }

    /**
     * eval()
     *
     * Evaluates a string as PHP
     *
     * @access public
     * @param string
     * @return mixed
     */
    public function evaluate($str)
    {
        return eval('?' . '>' . $str . '<?php ');
    }

    /**
     * Encode email from template callback
     *
     * @access public
     * @param string
     * @return string
     */
    public function encode_email($str)
    {
        if (isset(ee()->session->cache['functions']['emails'][$str])) {
            return preg_replace("/(eeEncEmail_)\w+/", '\\1' . ee()->functions->random('alpha', 10), ee()->session->cache['functions']['emails'][$str]);
        }

        $email = (is_array($str)) ? trim($str[1]) : trim($str);

        $title = '';
        $email = str_replace(array('"', "'"), '', $email);

        if ($p = strpos($email, "title=")) {
            $title = substr($email, $p + 6);
            $email = trim(substr($email, 0, $p));
        }

        ee()->load->library('typography');
        ee()->typography->initialize();

        $encoded = ee()->typography->encode_email($email, $title, true);

        ee()->session->cache['functions']['emails'][$str] = $encoded;

        return $encoded;
    }

    /**
     * Character limiter
     *
     * @access public
     * @param string
     * @return string
     */
    public function char_limiter($str, $num = 500)
    {
        if (strlen($str) < $num) {
            return $str;
        }

        $str = str_replace("\n", " ", $str);

        $str = preg_replace("/\s+/", " ", $str);

        if (strlen($str) <= $num) {
            return $str;
        }
        $str = trim($str);

        $out = "";

        foreach (explode(" ", trim($str)) as $val) {
            $out .= $val;

            if (strlen($out) >= $num) {
                return (strlen($out) == strlen($str)) ? $out : $out . '&#8230;';
            }

            $out .= ' ';
        }
    }

    /**
     * Word limiter
     *
     * @access public
     * @param string
     * @return string
     */
    public function word_limiter($str, $num = 100)
    {
        if (strlen($str) < $num) {
            return $str;
        }

        $word = preg_split('/\s/u', $str, -1, PREG_SPLIT_NO_EMPTY);

        if (count($word) <= $num) {
            return $str;
        }

        $str = "";

        for ($i = 0; $i < $num; $i++) {
            $str .= $word[$i] . " ";
        }

        return trim($str) . '&#8230;';
    }

    /**
     * Fetch Email Template
     *
     * @access public
     * @param string
     * @return string
     */
    public function fetch_email_template($name)
    {
        $query = ee()->db->query("SELECT template_name, data_title, template_data, enable_template FROM exp_specialty_templates WHERE site_id = '" . ee()->db->escape_str(ee()->config->item('site_id')) . "' AND template_name = '" . ee()->db->escape_str($name) . "'");

        // Unlikely that this is necessary but it's possible a bad template request could
        // happen if a user hasn't run the update script.
        if ($query->num_rows() == 0) {
            return array('title' => '', 'data' => '');
        }

        if ($query->row('enable_template') == 'y') {
            return array('title' => $query->row('data_title'), 'data' => $query->row('template_data'));
        }

        if (ee()->session->userdata['language'] != '') {
            $user_lang = ee()->session->userdata['language'];
        } else {
            if (ee()->input->cookie('language')) {
                $user_lang = ee()->input->cookie('language');
            } elseif (ee()->config->item('deft_lang') != '') {
                $user_lang = ee()->config->item('deft_lang');
            } else {
                $user_lang = 'english';
            }
        }

        $user_lang = ee()->security->sanitize_filename($user_lang);

        if (function_exists($name)) {
            $title = $name . '_title';

            return array('title' => $title(), 'data' => $name());
        } else {
            if (! @include(APPPATH . 'language/' . $user_lang . '/email_data.php')) {
                return array('title' => $query->row('data_title'), 'data' => $query->row('template_data'));
            }

            if (function_exists($name)) {
                $title = $name . '_title';

                return array('title' => $title(), 'data' => $name());
            } else {
                return array('title' => $query->row('data_title'), 'data' => $query->row('template_data'));
            }
        }
    }

    /**
     * Create pull-down optios from dirctory map
     *
     * @access public
     * @param array
     * @param string
     * @return string
     */
    public function render_map_as_select_options($zarray, $array_name = '')
    {
        foreach ($zarray as $key => $val) {
            if (is_array($val)) {
                if ($array_name != '') {
                    $key = $array_name . '/' . $key;
                }

                $this->render_map_as_select_options($val, $key);
            } else {
                if ($array_name != '') {
                    $val = $array_name . '/' . $val;
                }

                if (substr($val, -4) == '.php') {
                    if ($val != 'theme_master.php') {
                        $this->template_map[] = $val;
                    }
                }
            }
        }
    }

    /**
     * Fetch names of installed language packs
     *
     * DEPRECATED IN 2.0
     *
     * @access public
     * @param string
     * @return string
     */
    public function language_pack_names($default)
    {
        ee()->logger->deprecated('3.0');
        $dirs = ee()->lang->language_pack_names();

        return form_dropdown('language', $dirs, $default);
    }

    /**
     * Delete cache files
     *
     * @access public
     * @param string
     * @return string
     */
    public function clear_caching($which, $sub_dir = '')
    {
        $options = array('page', 'db', 'tag', 'sql');

        if (in_array($which, $options)) {
            ee()->cache->delete('/' . $which . '_cache/');
        } elseif ($which == 'all') {
            foreach ($options as $option) {
                ee()->cache->delete('/' . $option . '_cache/');
            }
        }
        if ($which == 'jumpmenu') {
            ee('CP/JumpMenu')->clearAllCaches();
        }
        if (isset(ee()->extensions) && ee()->extensions->active_hook('cache_clearing_end') === true) {
            $result = ee()->extensions->call('cache_clearing_end', $which);
        }
    }

    /**
     * Delete Direcories
     *
     * @access public
     * @param string
     * @param bool
     * @return void
     */
    public function delete_directory($path, $del_root = false)
    {
        return ee('Filesystem')->deleteDir($path, ! $del_root);
    }

    /**
     * Fetch allowed channels
     *
     * This function fetches the ID numbers of the
     * channels assigned to the currently logged in user.
     *
     * @access public
     * @param bool
     * @return array
     */
    public function fetch_assigned_channels($all_sites = false)
    {
        if (REQ == 'CLI') {
            $channels = ee('Model')->get('Channel')->fields('channel_id');
            if (!$all_sites) {
                $channels->filter('site_id', ee()->config->item('site_id'));
            }
            return $channels->all()->pluck('channel_id');
        }
        if (ee()->session->getMember()) {
            return ee()->session->getMember()->getAssignedChannels()->pluck('channel_id');
        }

        return [];
    }

    /**
     * Log Search terms
     *
     * @access public
     * @param string
     * @param string
     * @return void
     */
    public function log_search_terms($terms = '', $type = 'site')
    {
        if ($terms == '' or ee()->db->table_exists('exp_search_log') === false) {
            return;
        }

        if (ee()->config->item('enable_search_log') == 'n') {
            return;
        }

        ee()->load->helper('xml');

        $search_log = array(
            'member_id' => ee()->session->userdata('member_id'),
            'screen_name' => ee()->session->userdata('screen_name'),
            'ip_address' => ee()->input->ip_address(),
            'search_date' => ee()->localize->now,
            'search_type' => $type,
            'search_terms' => xml_convert(ee()->functions->encode_ee_tags(ee('Security/XSS')->clean($terms), true)),
            'site_id' => ee()->config->item('site_id')
        );

        ee()->db->query(ee()->db->insert_string('exp_search_log', $search_log));

        // Prune Database
        srand(time());
        if ((rand() % 100) < 5) {
            $max = (! is_numeric(ee()->config->item('max_logged_searches'))) ? 500 : ee()->config->item('max_logged_searches');

            $query = ee()->db->query("SELECT MAX(id) as search_id FROM exp_search_log WHERE site_id = '" . ee()->db->escape_str(ee()->config->item('site_id')) . "'");

            $row = $query->row_array();

            if (isset($row['search_id']) && $row['search_id'] > $max) {
                ee()->db->query("DELETE FROM exp_search_log WHERE site_id = '" . ee()->db->escape_str(ee()->config->item('site_id')) . "' AND id < " . ($row['search_id'] - $max) . "");
            }
        }
    }

    /**
     * Fetch Action ID
     *
     * @access public
     * @param string
     * @param string
     * @return string
     */
    public function fetch_action_id($class, $method)
    {
        if ($class == '' or $method == '') {
            return false;
        }

        $this->action_ids[ucfirst($class)][$method] = $method;

        return LD . 'AID:' . ucfirst($class) . ':' . $method . RD;
    }

    /**
     * Insert Action IDs
     *
     * @access public
     * @param string
     * @return string
     */
    public function insert_action_ids($str)
    {
        if (count($this->action_ids) == 0) {
            return $str;
        }

        $sql = "SELECT action_id, class, method FROM exp_actions WHERE";

        foreach ($this->action_ids as $key => $value) {
            foreach ($value as $k => $v) {
                $sql .= " (class= '" . ee()->db->escape_str($key) . "' AND method = '" . ee()->db->escape_str($v) . "') OR";
            }
        }

        $query = ee()->db->query(substr($sql, 0, -3));

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $str = str_replace(LD . 'AID:' . $row['class'] . ':' . $row['method'] . RD, $row['action_id'], $str);
            }
        }

        return $str;
    }

    /**
     * Get Categories for Channel Entry/Entries
     *
     * @access public
     * @param string
     * @param string
     * @return array
     */
    public function get_categories($cat_group, $entry_id)
    {
        // fetch the custom category fields
        $field_sqla = '';
        $field_sqlb = '';

        $query = ee()->db->query("SELECT field_id, field_name FROM exp_category_fields WHERE group_id IN ('" . str_replace('|', "','", ee()->db->escape_str($cat_group)) . "')");

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
            }

            $field_sqla = ", cg.field_html_formatting, fd.* ";
            $field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id
							LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id";
        }

        $sql = "SELECT		c.cat_name, c.cat_url_title, c.cat_id, c.cat_image, p.cat_id, c.parent_id, c.cat_description, c.group_id
				{$field_sqla}
				FROM		(exp_categories AS c, exp_category_posts AS p)
				{$field_sqlb}
				WHERE		c.group_id	IN ('" . str_replace('|', "','", ee()->db->escape_str($cat_group)) . "')
				AND			p.entry_id	= '" . $entry_id . "'
				AND			c.cat_id 	= p.cat_id
				ORDER BY	c.parent_id, c.cat_order";

        $sql = str_replace("\t", " ", $sql);
        $query = ee()->db->query($sql);

        $this->cat_array = array();
        $parents = array();

        if ($query->num_rows() > 0) {
            $this->temp_array = array();

            foreach ($query->result_array() as $row) {
                $this->temp_array[$row['cat_id']] = array($row['cat_id'], $row['parent_id'], $row['cat_name'], $row['cat_image'], $row['cat_description'], $row['group_id'], $row['cat_url_title']);

                if ($field_sqla != '') {
                    foreach ($row as $k => $v) {
                        if (strpos($k, 'field') !== false) {
                            $this->temp_array[$row['cat_id']][$k] = $v;
                        }
                    }
                }

                if ($row['parent_id'] > 0 && ! isset($this->temp_array[$row['parent_id']])) {
                    $parents[$row['parent_id']] = '';
                }
                unset($parents[$row['cat_id']]);
            }

            foreach ($this->temp_array as $k => $v) {
                if (isset($parents[$v[1]])) {
                    $v[1] = 0;
                }

                if (0 == $v[1]) {
                    $this->cat_array[] = $v;
                    $this->process_subcategories($k);
                }
            }

            unset($this->temp_array);
        }
    }

    /**
     * Process Subcategories
     *
     * @access public
     * @param string
     * @return void
     */
    public function process_subcategories($parent_id)
    {
        foreach ($this->temp_array as $key => $val) {
            if ($parent_id == $val[1]) {
                $this->cat_array[] = $val;
                $this->process_subcategories($key);
            }
        }
    }

    /**
     * Add security hashes to forms
     *
     * @access public
     * @param string
     * @return string
     */
    public function add_form_security_hash($str)
    {
        if (!defined('CSRF_TOKEN')) {
            ee()->security->have_valid_xid();
        }
        
        // Add security hash. Need to replace the legacy XID one as well.
        $str = str_replace('{csrf_token}', CSRF_TOKEN, $str);
        $str = str_replace('{XID_HASH}', CSRF_TOKEN, $str);

        return $str;
    }

    /**
     * Generate CAPTCHA
     *
     * @access public
     * @param string
     * @return string
     */
    public function create_captcha($old_word = '', $force_word = false)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('3.0', "ee('Captcha')->create()");

        return ee('Captcha')->create($old_word, $force_word);
    }

    /**
     * SQL "AND" or "OR" string for conditional tag parameters
     *
     * This function lets us build a specific type of query
     * needed when tags have conditional parameters:
     *
     * {exp:some_tag  param="value1|value2|value3"}
     *
     * Or the parameter can contain "not":
     *
     * {exp:some_tag  param="not value1|value2|value3"}
     *
     * This function explodes the pipes and constructs a series of AND
     * conditions or OR conditions
     *
     * We should probably put this in the DB class but it's not
     * something that is typically used
     *
     * @access public
     * @param string
     * @param string
     * @param string
     * @param bool
     * @return string
     */
    public function sql_andor_string($str, $field, $prefix = '', $null = false)
    {
        if ($str == "" or $field == "") {
            return '';
        }

        $str = trim($str);
        $sql = '';
        $not = '';

        if ($prefix != '') {
            $prefix .= '.';
        }

        if (strpos($str, '|') !== false) {
            $parts = preg_split('/\|/', $str, -1, PREG_SPLIT_NO_EMPTY);
            $parts = array_map('trim', array_map(array(ee()->db, 'escape_str'), $parts));

            if (count($parts) > 0) {
                if (strncasecmp($parts[0], 'not ', 4) == 0) {
                    $parts[0] = substr($parts[0], 4);
                    $not = 'NOT ';
                }

                if ($null === true) {
                    $sql .= "AND ({$prefix}{$field} {$not}IN ('" . implode("','", $parts) . "') OR {$prefix}{$field} IS NULL)";
                } else {
                    $sql .= "AND {$prefix}{$field} {$not}IN ('" . implode("','", $parts) . "')";
                }
            }
        } else {
            if (strncasecmp($str, 'not ', 4) == 0) {
                $str = trim(substr($str, 3));
                $not = '!';
            }

            if ($null === true) {
                $sql .= "AND ({$prefix}{$field} {$not}= '" . ee()->db->escape_str($str) . "' OR {$prefix}{$field} IS NULL)";
            } else {
                $sql .= "AND {$prefix}{$field} {$not}= '" . ee()->db->escape_str($str) . "'";
            }
        }

        return $sql;
    }

    /**
     * AR "AND" or "OR" string for conditional tag parameters
     *
     * This function lets us build a specific type of query
     * needed when tags have conditional parameters:
     *
     * {exp:some_tag  param="value1|value2|value3"}
     *
     * Or the parameter can contain "not":
     *
     * {exp:some_tag  param="not value1|value2|value3"}
     *
     * This function explodes the pipes and builds an AR query.
     *
     * We should probably put this in the DB class but it's not
     * something that is typically used
     *
     * @access public
     * @param string
     * @param string
     * @param string
     * @param bool
     */
    public function ar_andor_string($str, $field, $prefix = '', $null = false)
    {
        if ($str == "" or $field == "") {
            return '';
        }

        $str = trim($str);

        if ($prefix != '') {
            $prefix .= '.';
        }

        if (strpos($str, '|') !== false) {
            $parts = preg_split('/\|/', $str, -1, PREG_SPLIT_NO_EMPTY);
            $parts = array_map('trim', array_map(array(ee()->db, 'escape_str'), $parts));

            if (count($parts) > 0) {
                if ($null === true) {
                    // MySQL Only
                    if (strncasecmp($parts[0], 'not ', 4) == 0) {
                        $parts[0] = substr($parts[0], 4);
                        $sql = "({$prefix}{$field} NOT IN ('" . implode("','", $parts) . "') OR {$prefix}{$field} IS NULL)";
                    } else {
                        $sql = "({$prefix}{$field} IN ('" . implode("','", $parts) . "') OR {$prefix}{$field} IS NULL)";
                    }

                    ee()->db->where($sql);
                // END MySQL Only
                } else {
                    if (strncasecmp($parts[0], 'not ', 4) == 0) {
                        $parts[0] = substr($parts[0], 4);
                        ee()->db->where_not_in($prefix . $field, $parts);
                    } else {
                        ee()->db->where_in($prefix . $field, $parts);
                    }
                }
            }
        } else {
            if ($null === true) {
                // MySQL Only
                if (strncasecmp($str, 'not ', 4) == 0) {
                    $str = trim(substr($str, 3));
                    $sql = "({$prefix}{$field} != '" . ee()->db->escape_str($str) . "' OR {$prefix}{$field} IS NULL)";
                } else {
                    $sql = "({$prefix}{$field} = '" . ee()->db->escape_str($str) . "' OR {$prefix}{$field} IS NULL)";
                }

                ee()->db->where($sql);
            // END MySQL Only
            } else {
                if (strncasecmp($str, 'not ', 4) == 0) {
                    $str = trim(substr($str, 3));

                    ee()->db->where($prefix . $field . ' !=', $str);
                } else {
                    ee()->db->where($prefix . $field, $str);
                }
            }
        }
    }

    /**
     * Assign Conditional Variables
     *
     * @access public
     * @param string
     * @param string
     * @param string
     * @param string
     * @return array
     */
    public function assign_conditional_variables($str, $slash = '/', $LD = '{', $RD = '}')
    {
        // The first half of this function simply gathers the openging "if" tags
        // and a numeric value that corresponds to the depth of nesting.
        // The second half parses out the chunks

        $conds = array();
        $var_cond = array();

        $modified_str = $str; // Not an alias!

        // Find the conditionals.
        // Added a \s in there to make sure it does not match {if:elseif} or {if:else} would would give
        // us a bad array and cause havoc.
        if (! preg_match_all("/" . $LD . "if(\s.*?)" . $RD . "/s", $modified_str, $eek)) {
            return $var_cond;
        }

        $total_conditionals = count($eek[0]);

        // Mark all opening conditionals, sequentially.
        if (! empty($modified_str)) {
            for ($i = 0; $i < $total_conditionals; $i++) {
                // Embedded variable fix
                if ($ld_location = strpos($eek[1][$i], $LD)) {
                    if (preg_match_all("|" . preg_quote($eek[0][$i]) . "(.*?)" . $RD . "|s", $modified_str, $fix_eek)) {
                        if (count($fix_eek) > 0) {
                            $eek[0][$i] = $fix_eek[0][0];
                            $eek[1][$i] .= $RD . $fix_eek[1][0];
                        }
                    }
                }

                $modified_string_length = strlen($eek[1][$i]);
                $replace_value[$i] = $LD . 'if' . $i;
                $p1 = strpos($modified_str, $eek[0][$i]);
                $p2 = $p1 + strlen($replace_value[$i] . $eek[1][$i]) - strlen($i);
                $p3 = strlen($modified_str);
                $modified_str = substr($modified_str, 0, $p1) . $replace_value[$i] . $eek[1][$i] . substr($modified_str, $p2, $p3);
            }
        }

        // Mark all closing conditions.
        $closed_position = array();
        for ($t = $i - 1; $t >= 0; $t--) {
            // Find the conditional's start
            $coordinate = strpos($modified_str, $LD . 'if' . $t);

            // Find the shortned string.
            $shortened = substr($modified_str, $coordinate);

            // Find the conditional's end. Should be first closing tag.
            $closed_position = strpos($shortened, $LD . $slash . 'if' . $RD);

            // Location of the next closing tag in main content var
            $p1 = $coordinate + $closed_position;
            $p2 = $p1 + strlen($LD . $slash . 'if' . $t . $RD) - 1;

            $modified_str = substr($modified_str, 0, $p1) . $LD . $slash . 'if' . $t . $RD . substr($modified_str, $p2);
        }

        // Create Rick's array
        for ($i = 0; $i < $total_conditionals; $i++) {
            $p1 = strpos($modified_str, $LD . 'if' . $i . ' ');
            $p2 = strpos($modified_str, $LD . $slash . 'if' . $i . $RD);
            $length = $p2 - $p1;
            $text_range = substr($modified_str, $p1, $length);

            // We use \d here because we want to look for one of the 'marked' conditionals, but
            // not an Advanced Conditional, which would have a colon
            if (preg_match_all("/" . $LD . "if(\d.*?)" . $RD . "/", $text_range, $depth_check)) {
                // Depth is minus one, since it counts itself
                $conds[] = array($LD . 'if' . $eek[1][$i] . $RD, count($depth_check[0]));
            }
        }

        // Create detailed conditional array
        $float = $str;
        $CE = $LD . $slash . 'if' . $RD;
        $offset = strlen($CE);
        $start = 1;
        $duplicates = array();

        foreach ($conds as $key => $val) {
            if ($val[1] > $start) {
                $start = $val[1];
            }

            $open_tag = strpos($float, $val[0]);

            $float = substr($float, $open_tag);

            $temp = $float;
            $len = 0;
            $duplicates = array();

            $i = 1;

            while (false !== ($in_point = strpos($temp, $CE))) {
                $temp = substr($temp, $in_point + $offset);

                $len += $in_point + $offset;

                if ($i === $val[1]) {
                    $tag = str_replace($LD, '', $val[0]);
                    $tag = str_replace($RD, '', $tag);

                    $outer = substr($float, 0, $len);

                    if (isset($duplicates[$val[1]]) && in_array($outer, $duplicates[$val[1]])) {
                        break;
                    }

                    $duplicates[$val[1]][] = $outer;

                    $inner = substr($outer, strlen($val[0]), -$offset);

                    $tag = str_replace("|", "\|", $tag);

                    $tagb = preg_replace("/^if/", "", $tag);

                    $field = (! preg_match("#(\S+?)\s*(\!=|==|<|>|<=|>=|<>|%)#s", $tag, $match)) ? trim($tagb) : $match[1];

                    // Array prototype:
                    // offset 0: the full opening tag sans delimiters:  if extended
                    // offset 1: the complete conditional chunk
                    // offset 2: the inner conditional chunk
                    // offset 3: the field name

                    $var_cond[$val[1]][] = array($tag, $outer, $inner, $field);

                    $float = substr($float, strlen($val[0]));

                    break;
                }

                $i++;
            }
        }

        // Parse Order
        $final_conds = array();

        for ($i = $start; $i > 0; --$i) {
            if (isset($var_cond[$i])) {
                $final_conds = array_merge($final_conds, $var_cond[$i]);
            }
        }

        return $final_conds;
    }

    /**
     * Assign Tag Variables
     *
     * Deprecated in 4.0.0
     *
     * @see ExpressionEngine\Service\Template\Variables\LegacyParser::extractVariables()
     */
    public function assign_variables($str = '', $slash = '/')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('4.0', "ee('Variables/Parser')->extractVariables()");

        return ee('Variables/Parser')->extractVariables($str);
    }

    /**
     * Find the Full Opening Tag
     *
     * Deprecated in 4.0.0
     *
     * @see ExpressionEngine\Service\Template\Variables\LegacyParser::getFullTag()
     */
    public function full_tag($str, $chunk = '', $open = '', $close = '')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('4.0', "ee('Variables/Parser')->getFullTag()");

        // LegacyParser::getFullTag() responsibly preg_quote()s whereas this old method put
        // the impetus on the developer to send a slash-quoted closing tag.
        $close = stripslashes($close);

        if ($chunk == '') {
            $chunk = (isset(ee()->TMPL) && is_object(ee()->TMPL)) ? ee()->TMPL->fl_tmpl : '';
        }
        if ($open == '') {
            $open = LD;
        }
        if ($close == '') {
            $close = RD;
        }

        return ee('Variables/Parser')->getFullTag($chunk, $str, $open, $close);
    }

    /**
     * Fetch simple conditionals
     *
     * @access public
     * @param string
     * @return string
     */
    public function fetch_simple_conditions($str)
    {
        if ($str == '') {
            return;
        }

        $str = str_replace(' ', '', trim($str, '|'));

        return explode('|', $str);
    }

    /**
     * Extract format= code from date variable
     *
     * Deprecated in 4.0.0
     *
     * @see ExpressionEngine\Service\Template\Variables\LegacyParser::extractDateFormat()
     */
    public function fetch_date_variables($datestr)
    {
        return ee('Variables/Parser')->extractDateFormat($datestr);
    }

    /**
     * Return parameters as an array
     *
     * Deprecated in 4.0.0
     *
     * @see ExpressionEngine\Service\Template\Variables\LegacyParser::parseTagParameters()
     */
    public function assign_parameters($str, $defaults = array())
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('4.0', "ee('Variables/Parser')->parseTagParameters()");

        $params = ee('Variables/Parser')->parseTagParameters($str, $defaults);

        // this legacy method returned FALSE with no parameters, the new method always return an array
        return (empty($params)) ? false : $params;
    }

    /**
     * Prep conditional
     *
     * This function lets us do a little prepping before
     * running any conditionals through eval()
     *
     * @access public
     * @param string
     * @return string
     */
    public function prep_conditional($cond = '')
    {
        $cond = preg_replace("/^if/", "", $cond);

        if (preg_match("/(\S+)\s*(\!=|==|<=|>=|<>|<|>|%)\s*(.+)/", $cond, $match)) {
            $cond = trim($match[1]) . ' ' . trim($match[2]) . ' ' . trim($match[3]);
        }

        $rcond = substr($cond, strpos($cond, ' '));
        $cond = str_replace($rcond, $rcond, $cond);

        // Since we allow the following shorthand condition: {if username}
        // but it's not legal PHP, we'll correct it by adding:  != ''

        if (! preg_match("/(\!=|==|<|>|<=|>=|<>|%)/", $cond)) {
            $cond .= ' != "" ';
        }

        return trim($cond);
    }

    /**
     * Reverse Key Sort
     *
     * @access public
     * @param string
     * @param string
     * @return string
     */
    public function reverse_key_sort($a, $b)
    {
        return strlen($b) > strlen($a);
    }

    /**
     * Prep conditionals
     *
     * @access public
     * @param string $str   The template string containing conditionals
     * @param string $vars  The variables to look for in the conditionals
     * @param string $safety If y, make sure conditionals are fully parseable
     *                      by replacing unknown variables with FALSE. This
     *                      defaults to n so that conditionals are slowly
     *                      filled and then turned into safely executable
     *                      ones with the safety on at the end.
     * @param string $prefix Prefix for the variables in $vars.
     * @return string The new template to use instead of $str.
     */
    public function prep_conditionals($str, $vars, $safety = 'n', $prefix = '')
    {
        if (! stristr($str, LD . 'if')) {
            return $str;
        }

        if (isset(ee()->TMPL) && isset(ee()->TMPL->embed_vars)) {
            // If this is being called from a module tag, embedded variables
            // aren't going to be available yet.  So this is a quick workaround
            // to ensure advanced conditionals using embedded variables can do
            // their thing in mod tags.
            $vars = array_merge($vars, ee()->TMPL->embed_vars);
        }

        $bool_safety = ($safety == 'n') ? false : true;

        $runner = \ExpressionEngine\Library\Parser\ParserFactory::createConditionalRunner();

        if ($bool_safety === true) {
            $runner->safetyOn();
        }

        if ($prefix) {
            $runner->setPrefix($prefix);
        }

        /* ---------------------------------
        /* Hidden Configuration Variables
        /*  - protect_javascript => Prevents advanced conditional parser from processing anything in <script> tags
        /* ---------------------------------*/

        if (isset(ee()->TMPL) && ee()->TMPL->protect_javascript) {
            $runner->enableProtectJavascript();
        }

        try {
            return $runner->processConditionals($str, $vars);
        } catch (\ExpressionEngine\Library\Parser\Conditional\Exception\ConditionalException $e) {
            $thrower = str_replace(
                array('\\', 'Conditional', 'Exception'),
                '',
                strrchr(get_class($e), '\\')
            );

            if (
                ee()->config->item('debug') == 2
                or (ee()->config->item('debug') == 1 && ee('Permission')->isSuperAdmin())
            ) {
                $error = lang('error_invalid_conditional') . "\n\n";
                $error .= '<strong>' . $thrower . ' State:</strong> ' . $e->getMessage();
            } else {
                $error = lang('generic_fatal_error');
            }

            ee()->output->set_status_header(500);
            ee()->output->fatal_error(nl2br($error));

            exit;
        }

        return $str;
    }

    /**
     * Fetch file upload paths
     *
     * @access public
     * @return array
     */
    public function fetch_file_paths()
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('3.0', 'File_upload_preferences_model::get_paths()');

        ee()->load->model('file_upload_preferences_model');
        $this->file_paths = ee()->file_upload_preferences_model->get_paths();

        return $this->file_paths;
    }

    /**
     * bookmarklet qstr decode
     *
     * @param string
     */
    public function bm_qstr_decode($str)
    {
        $str = str_replace("%20", " ", $str);
        $str = str_replace("%uFFA5", "&#8226;", $str);
        $str = str_replace("%uFFCA", " ", $str);
        $str = str_replace("%uFFC1", "-", $str);
        $str = str_replace("%uFFC9", "...", $str);
        $str = str_replace("%uFFD0", "-", $str);
        $str = str_replace("%uFFD1", "-", $str);
        $str = str_replace("%uFFD2", "\"", $str);
        $str = str_replace("%uFFD3", "\"", $str);
        $str = str_replace("%uFFD4", "\'", $str);
        $str = str_replace("%uFFD5", "\'", $str);

        $str = preg_replace("/\%u([0-9A-F]{4,4})/", "'&#'.base_convert('\\1',16,10).';'", $str);

        $str = ee('Security/XSS')->clean(stripslashes(urldecode($str)));

        return $str;
    }
}
// END CLASS

// EOF
