<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Dependency\IPLib;

/**
 * Core Input
 */
class EE_Input
{
    public $SID = ''; // Session ID extracted from the URI segments

    public $security;
    public $uni;
    public $ip_address = false;
    public $user_agent = false;
    public $_allow_get_array = true;
    public $_standardize_newlines = true;
    public $_enable_xss = false; // Set automatically based on config setting

    protected $headers = array();

    /**
     * Constructor
     *
     * Sets whether to globally enable the XSS processing
     * and whether to allow the $_GET array
     *
     */
    public function __construct()
    {
        log_message('debug', "Input Class Initialized");

        $this->_allow_get_array = true;// (config_item('enable_query_strings') === TRUE) ? TRUE : FALSE;
        $this->_enable_xss = (config_item('global_xss_filtering') === true) ? true : false;

        global $SEC;
        $this->security = & $SEC;

        // Do we need the UTF-8 class?
        if (UTF8_ENABLED === true) {
            global $UNI;
            $this->uni = & $UNI;
        }

        // Sanitize global arrays
        $this->_sanitize_globals();
    }

    /**
     * Delete a Cookie
     *
     * Delete a cookie with the given name.  Prefix will be automatically set
     * from the configuation file, as will domain and path.  Httponly must be
     * must be equal to the value used when setting the cookie.
     *
     * @param   string  The name of the cookie to be deleted.
     *
     * @return  boolean FALSE if output has already been sent (and thus the
     *                  cookie not set), TRUE otherwise.
     */
    public function delete_cookie($name)
    {
        $data = array(
            'name' => $name,
            'value' => '',
            'expire' => ee()->localize->now - 86500,
        );

        return $this->_set_cookie($data);
    }

    /**
     * Set a Cookie
     *
     * Set a cookie with a particular name, value and expiration.  Determine
     * whether the cookie should be HTTP only or not.  Domain, path and prefix
     * are kept as parameters to maintain compatibility with
     * CI_Input::set_cookie() however, they are ignored in favor of the
     * configuration file values. Expiration may be set to 0 to create a cookie
     * that expires at the end of the session (when the browser closes), or
     * given a time in seconds to indicate that a cookie should expire that
     * many seconds from the moment it is set.
     *
     * @param   string  The name to assign the cookie.
     *                  This will be prefixed with the value from the config file or exp_.
     * @param   string  The value to assign the cookie.
     *                  This will be automatically URL encoded when set and decoded when retrieved.
     * @param   string  A time in seconds after which the cookie should expire.
     *                  The cookie will be set to expire this many seconds after it is set.
     * @param   string  The domain. IGNORED
     *                  Kept only for consistency with CI_Input::set_cookie(). Set from config.
     * @param   string  The path.  IGNORED
     *                  Kept only for consistency with CI_Input::set_cookie(). Set from config.
     * @param   string  The prefix.  IGNORED
     *                  Kept only for consistency with CI_Input::set_cookie(). Set from config.
     *
     * @return  boolean FALSE if output has already been sent, TRUE otherwise.
     */
    public function set_cookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '')
    {
        if (! $this->cookieIsAllowed($name)) {
            return;
        }

        $data = array(
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
            // We have to set these so we can
            // check them and give the deprecation
            // warning.  However, they will be
            // ignored.
            'domain' => $domain,
            'path' => $path,
            'prefix' => $prefix
        );

        // If name is an array, then most of the values we just set in the data
        // array are probably their defaults.  Override the defaults with
        // whatever happens to be in the array.  Yes, this is ugly as all get
        // out.
        if (is_array($name)) {
            foreach (array('value', 'expire', 'name', 'domain', 'path', 'prefix') as $item) {
                if (isset($name[$item])) {
                    $data[$item] = $name[$item];
                }
            }
        }

        if ($data['domain'] !== '' || $data['path'] !== '/' || $data['prefix'] !== '') {
            ee()->load->library('logger');
            ee()->logger->developer('Warning: domain, path and prefix must be set in EE\'s configuration files and cannot be overriden in set_cookie.');
        }

        // Clean up the value.
        $data['value'] = stripslashes($data['value']);

        // check the cookie setting for expiration date
        if (is_numeric($data['expire'])) {
            $loadedCookieSettings = ee('CookieRegistry')->getCookieSettings($name);
            if (!empty($loadedCookieSettings)) {
                if ($loadedCookieSettings['cookie_lifetime'] === null) {
                    $cookieSettings = ee('Model')->get('CookieSetting')->filter('cookie_name', $name)->first();
                    $cookieSettings->cookie_lifetime = $data['expire'];
                    $cookieSettings->save();
                    ee('CookieRegistry')->registerCookieSettings($cookieSettings);
                } else {
                    if (is_numeric($loadedCookieSettings['cookie_lifetime'])) {
                        $data['expire'] = ($loadedCookieSettings['cookie_enforced_lifetime'] !== null) ? $loadedCookieSettings['cookie_enforced_lifetime'] : $loadedCookieSettings['cookie_lifetime'];
                    }
                }
            }
        }

        // Handle expiration dates.
        if (! is_numeric($data['expire'])) {
            ee()->load->library('logger');
            ee()->logger->deprecated('2.8', 'EE_Input::delete_cookie()');
            $data['expire'] = ee()->localize->now - 86500;
        } elseif ($data['expire'] > 0) {
            $data['expire'] = ee()->localize->now + $data['expire'];
        } else {
            $data['expire'] = 0;
        }

        $this->_set_cookie($data);
    }

    /**
     * Set a Cookie
     *
     * Protected method called from EE_Input::set_cookie() and
     * EE_Input::delete_cookie(). Handles the common config file logic, calls
     * the set_cookie_end hook and sets the cookie.
     *
     * Must recieve name, value, and expire in the parameter array or
     * will throw an exception.
     *
     * @param   mixed   The array of data containing name, value, expire and httponly.
     *                  Must contain those parameters.
     * @return  bool    If output exists prior to calling this method it will
     *                  fail with FALSE, otherwise it will return TRUE.
     *                  This does not indicate whether the user accepts the cookie.
     */
    protected function _set_cookie(array $data)
    {
        // Always assume we'll forget and catch ourselves.  The earlier you catch this sort of screw up the better.
        if (! isset($data['name']) || ! isset($data['value']) || ! isset($data['expire'])) {
            throw new RuntimeException('EE_Input::_set_cookie() is missing key data.');
        }

        // Set prefix, path and domain. We'll pull em out of config.
        if (REQ == 'CP' && ee()->config->item('multiple_sites_enabled') == 'y') {
            $data['prefix'] = (! ee()->config->cp_cookie_prefix) ? 'exp_' : ee()->config->cp_cookie_prefix;
            $data['path'] = (! ee()->config->cp_cookie_path) ? '/' : ee()->config->cp_cookie_path;
            $data['domain'] = (! ee()->config->cp_cookie_domain) ? '' : ee()->config->cp_cookie_domain;
            $data['httponly'] = (! ee()->config->cp_cookie_httponly) ? 'y' : ee()->config->cp_cookie_httponly;
        } else {
            $data['prefix'] = (! ee()->config->item('cookie_prefix')) ? 'exp_' : ee()->config->item('cookie_prefix') . '_';
            $data['path'] = (! ee()->config->item('cookie_path')) ? '/' : ee()->config->item('cookie_path');
            $data['domain'] = (! ee()->config->item('cookie_domain')) ? '' : ee()->config->item('cookie_domain');
            $data['httponly'] = (! ee()->config->item('cookie_httponly')) ? 'y' : ee()->config->item('cookie_httponly');
        }

        // Turn httponly into a true boolean.
        $data['httponly'] = get_bool_from_string($data['httponly']);

        // Deal with secure cookies.
        $data['secure_cookie'] = bool_config_item('cookie_secure');

        if ($data['secure_cookie']) {
            if (ee('Request') && !ee('Request')->isEncrypted()) {
                return false;
            }
        }

        $data = $this->validateCookieData($data);

        /* -------------------------------------------
        /* 'set_cookie_end' hook.
        /*  - Take control of Cookie setting routine
        /*  - Added EE 2.5.0
        */
        $hookResponse = ee()->extensions->call('set_cookie_end', $data);

        // Only accept hook response if it includes valid data. At minimum it should return name, value, expire.
        if (is_array($hookResponse) && empty(array_diff_key(array_flip(['expire', 'name', 'value']), $hookResponse))) {
            $data = array_replace($data, $hookResponse);
        }

        if (ee()->extensions->end_script === true) {
            return false;
        }
        /*
        /* -------------------------------------------*/

        $data = $this->validateCookieData($data);

        if (PHP_VERSION_ID < 70300) {
            // Older versions of PHP do not support an array as the 3rd parameter,
            // thus the SameSite setting must be hacked in with the path option.
            return setcookie(
                $data['prefix'] . $data['name'],
                $data['value'],
                $data['expire'],
                $data['path'] . '; SameSite=' . $data['samesite'],
                $data['domain'],
                $data['secure_cookie'],
                $data['httponly']
            );
        }

        return setcookie($data['prefix'] . $data['name'], $data['value'], [
            'expires' => $data['expire'],
            'path' => $data['path'],
            'domain' => $data['domain'],
            'secure' => $data['secure_cookie'],
            'httponly' => $data['httponly'],
            'samesite' => $data['samesite'],
        ]);
    }

    /**
     * Validate the cookie settings to maintain data integrity.
     * SameSite explained: https://web.dev/samesite-cookies-explained
     * Allow default setting for all site cookies, then allow for overrides when this method is called.
     * Cookies without a SameSite attribute will be treated as SameSite=Lax.
     *
     * @param array $data
     * @return array
     */
    private function validateCookieData($data = [])
    {
        // Set a default value if undefined
        if (!isset($data['samesite'])) {
            $data['samesite'] = 'Lax';
        }

        // Allow site administrators to override EE and/or 3rd part add-on cookies.
        // There is nothing wrong with wanting all cookies to be Strict.
        $data['samesite'] = ee()->config->item('cookie_samesite') ?: $data['samesite'];

        // Don't allow invalid values for samesite
        if (!in_array($data['samesite'], ['Lax', 'None', 'Strict'])) {
            $data['samesite'] = 'Lax';
        }

        // Cookies with SameSite=None must also specify Secure, meaning they require a secure context.
        if ($data['samesite'] === 'None') {
            $data['secure_cookie'] = true;
        }

        return $data;
    }

    /**
     * Is the cookie allowed?
     *
     * @param  string $name Name of the cookie
     * @return boolean Whether or not it's allowed to be set
     */
    private function cookieIsAllowed($name)
    {
        // only worry about it if consent is required
        if (bool_config_item('require_cookie_consent') !== true) {
            return true;
        }

        // Need a local ref for PHP < 7, can't do ee('CookieRegistry')::CONST
        $cookie_reg = ee('CookieRegistry');

        // unregistered cookies, pass, but log
        if (! $cookie_reg->isRegistered($name)) {
            ee()->load->library('logger');
            ee()->logger->developer('A cookie (' . htmlentities($name) . ') is being sent without being properly registered, and does not meet cookie compliance policies. Register this cookie appropriately in your addon.setup.php file.', true, 604800);

            return true;
        }

        switch ($cookie_reg->getType($name)) {
            case $cookie_reg::NECESSARY:
                return true;
            case $cookie_reg::FUNCTIONALITY:
                return ee('Consent')->hasGranted('ee:cookies_functionality');
            case $cookie_reg::PERFORMANCE:
                return ee('Consent')->hasGranted('ee:cookies_performance');
            case $cookie_reg::TARGETING:
                return ee('Consent')->hasGranted('ee:cookies_targeting');
        }

        // something bad happened
        return false;
    }

    /**
     * Fetch from array
     *
     * This is a helper function to retrieve values from global arrays
     *
     * @access  private
     * @param   array
     * @param   string
     * @param   bool
     * @return  string
     */
    public function _fetch_from_array(&$array, $index = '', $xss_clean = false)
    {
        if (! isset($array[$index])) {
            return false;
        }

        if ($xss_clean === true) {
            return ee('Security/XSS')->clean($array[$index]);
        }

        return $array[$index];
    }

    /**
    * Fetch an item from the GET array
    *
    * @access   public
    * @param    string
    * @param    bool
    * @return   string
    */
    public function get($index = '', $xss_clean = false)
    {
        return $this->_fetch_from_array($_GET, $index, $xss_clean);
    }

    /**
    * Fetch an item from the POST array
    *
    * @access   public
    * @param    string
    * @param    bool
    * @return   string
    */
    public function post($index = '', $xss_clean = false)
    {
        return $this->_fetch_from_array($_POST, $index, $xss_clean);
    }

    /**
    * Fetch an item from either the GET array or the POST
    *
    * @access   public
    * @param    string  The index key
    * @param    bool    XSS cleaning
    * @return   string
    */
    public function get_post($index = '', $xss_clean = false)
    {
        if (! isset($_POST[$index])) {
            return $this->get($index, $xss_clean);
        } else {
            return $this->post($index, $xss_clean);
        }
    }

    /**
    * Fetch an item from the SERVER array
    *
    * @access   public
    * @param    string
    * @param    bool
    * @return   string
    */
    public function server($index = '', $xss_clean = false)
    {
        return $this->_fetch_from_array($_SERVER, $index, $xss_clean);
    }

    /**
    * Fetch the IP Address
    *
    * @access   public
    * @return   string
    */
    public function ip_address()
    {
        if ($this->ip_address !== false) {
            return $this->ip_address;
        }

        if (REQ == 'CLI') {
            return '0.0.0.0';
        }

        $this->ip_address = $_SERVER['REMOTE_ADDR'];

        // is the server behind reverse proxy or load balancer?
        $proxy_ips = ee()->config->loadFile('proxy');
        if (empty($proxy_ips)) {
            // legacy config, deprecated
            $proxy_ips = config_item('proxy_ips');
            if (! empty($proxy_ips)) {
                $proxy_ips = explode(',', str_replace(' ', '', $proxy_ips));
            }
        }

        if (!empty($proxy_ips)) {
            // does the IP address that we got actuall belong to proxy/balancer?
            $proxyIsValid = false;
            $address = IPLib\Factory::parseAddressString($this->ip_address);
            foreach ($proxy_ips as $proxy_ip) {
                $range = IPLib\Factory::parseRangeString($proxy_ip);
                if ($range->contains($address)) {
                    $proxyIsValid = true;
                    break;
                }
            }

            if ($proxyIsValid) {
                $possibleIpHeaders = [
                    'HTTP_X_CLIENT_IP',
                    'HTTP_CLIENT_IP',
                    'HTTP_X_FORWARDED_FOR',
                    'HTTP_FORWARDED_FOR',
                    'HTTP_X_FORWARDED',
                    'HTTP_FORWARDED',
                    'HTTP_CF_CONNECTING_IP',
                    'HTTP_X_CLUSTER_CLIENT_IP'
                ];

                foreach ($possibleIpHeaders as $header) {
                    if (($spoof = $this->server($header)) !== false) {
                        // Some proxies typically list the whole chain of IP
                        // addresses through which the client has reached us.
                        // e.g. client_ip, proxy_ip1, proxy_ip2, etc.
                        if (strpos($spoof, ',') !== false) {
                            $spoof = explode(',', $spoof, 2);
                            $spoof = $spoof[0];
                        }

                        if ($this->valid_ip($spoof)) {
                            $this->ip_address = $spoof;
                            break;
                        }
                    }
                }
            }
        }

        if (! $this->valid_ip($this->ip_address)) {
            $this->ip_address = '0.0.0.0';
        }

        return $this->ip_address;
    }

    /**
    * Validate IP Address
    *
    * @access   public
    * @param    string
    * @param    string  ipv4 or ipv6
    * @return   bool
    */
    public function valid_ip($ip, $which = '')
    {
        // First check if filter_var is available
        if (is_callable('filter_var')) {
            switch ($which) {
                case 'ipv4':
                    $flag = FILTER_FLAG_IPV4;
                    $filtered = filter_var($ip, FILTER_VALIDATE_IP, $flag);

                    break;
                case 'ipv6':
                    $flag = FILTER_FLAG_IPV6;
                    $filtered = filter_var($ip, FILTER_VALIDATE_IP, $flag);

                    break;
                default:
                    $filtered = filter_var($ip, FILTER_VALIDATE_IP);

                    break;
            }

            return $filtered !== false;
        }

        // If it's not we'll do it manually
        $which = strtolower($which);

        if ($which != 'ipv6' or $which != 'ipv4') {
            if (strpos($ip, ':') !== false) {
                $which = 'ipv6';
            } elseif (strpos($ip, '.') !== false) {
                $which = 'ipv4';
            } else {
                return false;
            }
        }

        $func = '_valid_' . $which;

        return $this->$func($ip);
    }

    /**
    * Validate IPv4 Address
    *
    * Updated version suggested by Geert De Deckere
    *
    * @access   protected
    * @param    string
    * @return   bool
    */
    protected function _valid_ipv4($ip)
    {
        $ip_segments = explode('.', $ip);

        // Always 4 segments needed
        if (count($ip_segments) != 4) {
            return false;
        }
        // IP can not start with 0
        if ($ip_segments[0][0] == '0') {
            return false;
        }

        // Check each segment
        foreach ($ip_segments as $segment) {
            // IP segments must be digits and can not be
            // longer than 3 digits or greater then 255
            if ($segment == '' or preg_match("/[^0-9]/", $segment) or $segment > 255 or strlen($segment) > 3) {
                return false;
            }
        }

        return true;
    }

    /**
    * Validate IPv6 Address
    *
    * @access   protected
    * @param    string
    * @return   bool
    */
    protected function _valid_ipv6($str)
    {
        // 8 groups, separated by :
        // 0-ffff per group
        // one set of consecutive 0 groups can be collapsed to ::

        $groups = 8;
        $collapsed = false;

        $chunks = array_filter(
            preg_split('/(:{1,2})/', $str, -1, PREG_SPLIT_DELIM_CAPTURE)
        );

        // Rule out easy nonsense
        if (current($chunks) == ':' or end($chunks) == ':') {
            return false;
        }

        // PHP supports IPv4-mapped IPv6 addresses, so we'll expect those as well
        if (strpos(end($chunks), '.') !== false) {
            $ipv4 = array_pop($chunks);

            if (! $this->_valid_ipv4($ipv4)) {
                return false;
            }

            $groups--;
        }

        while ($seg = array_pop($chunks)) {
            if ($seg[0] == ':') {
                if (--$groups == 0) {
                    return false;   // too many groups
                }

                if (strlen($seg) > 2) {
                    return false;   // long separator
                }

                if ($seg == '::') {
                    if ($collapsed) {
                        return false;   // multiple collapsed
                    }

                    $collapsed = true;
                }
            } elseif (preg_match("/[^0-9a-f]/i", $seg) or strlen($seg) > 4) {
                return false; // invalid segment
            }
        }

        return $collapsed or $groups == 1;
    }

    /**
     * Compare an IP versus the current IP
     *
     * @param string $ip IP address to compare to current address
     * @param int $accuracy The number of octets you want to check, 4 being full
     *              accuracy, 0 being no check at all
     * @return boolean TRUE if they match up, FALSE otherwise
     */
    public function compare_ip($ip, $accuracy = 4)
    {
        // If accuracy is 0, then no check is necessary
        if ($accuracy === 0) {
            return true;
        }

        // If accuracy is 4, do a standard check
        if ($accuracy === 4) {
            return ($ip == $this->ip_address());
        }

        // Otherwise let's start breaking things up
        $comparison_ip = explode('.', $ip);
        $current_ip = explode('.', $this->ip_address());

        // Check each octet up to the desired accuracy
        for ($octet = 0; $octet < $accuracy; $octet++) {
            if ($comparison_ip[$octet] !== $current_ip[$octet]) {
                return false;
            }
        }

        return true;
    }

    /**
    * User Agent
    *
    * @access   public
    * @return   string
    */
    public function user_agent()
    {
        if ($this->user_agent !== false) {
            return $this->user_agent;
        }

        $this->user_agent = (! isset($_SERVER['HTTP_USER_AGENT'])) ? false : $_SERVER['HTTP_USER_AGENT'];

        return $this->user_agent;
    }

    /**
     * Fetch an item from the COOKIE array
     *
     * This method overrides the one in the CI class since EE cookies have a particular prefix
     *
     * @access  public
     * @param   string
     * @param   bool
     * @return  string
     */
    public function cookie($index = '', $xss_clean = false)
    {
        $prefix = (! ee()->config->item('cookie_prefix')) ? 'exp_' : ee()->config->item('cookie_prefix') . '_';
        $cookie = $this->_fetch_from_array($_COOKIE, $prefix . $index, $xss_clean);

        return ($cookie) ? stripslashes($cookie) : false;
    }

    /**
     * Request Headers
     *
     * In Apache, you can simply call apache_request_headers(), however for
     * people running other webservers the function is undefined.
     *
     * @return array
     */
    public function request_headers($xss_clean = false)
    {
        // Look at Apache go!
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers['Content-Type'] = (isset($_SERVER['CONTENT_TYPE'])) ? $_SERVER['CONTENT_TYPE'] : @getenv('CONTENT_TYPE');

            foreach ($_SERVER as $key => $val) {
                if (strncmp($key, 'HTTP_', 5) === 0) {
                    $headers[substr($key, 5)] = $this->_fetch_from_array($_SERVER, $key, $xss_clean);
                }
            }
        }

        // take SOME_HEADER and turn it into Some-Header
        foreach ($headers as $key => $val) {
            $key = str_replace('_', ' ', strtolower($key));
            $key = str_replace(' ', '-', ucwords($key));

            $this->headers[$key] = $val;
        }

        return $this->headers;
    }

    /**
     * Get Request Header
     *
     * Returns the value of a single member of the headers class member
     *
     * @param   string  array key for $this->headers
     * @param   boolean XSS Clean or not
     * @return  mixed   FALSE on failure, string on success
     */
    public function get_request_header($index, $xss_clean = false)
    {
        if (empty($this->headers)) {
            $this->request_headers();
        }

        if (! isset($this->headers[$index])) {
            return false;
        }

        if ($xss_clean === true) {
            return ee('Security/XSS')->clean($this->headers[$index]);
        }

        return $this->headers[$index];
    }

    /**
     * Is ajax Request?
     *
     * Test to see if a request contains the HTTP_X_REQUESTED_WITH header
     *
     * @return  boolean
     */
    public function is_ajax_request()
    {
        return ($this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest');
    }

    /**
     * Filter GET Data
     *
     * Filters GET data for security
     *
     * @access  public
     * @param   string
     * @return  void
     */
    public function filter_get_data($request_type = 'PAGE')
    {
        /*
        * --------------------------------------------------------------------
        *  Is the request a URL redirect redirect?  Moved from the index so we can have config variables!
        * --------------------------------------------------------------------
        *
        * All external links that appear in the ExpressionEngine control panel
        * are redirected to this index.php file first, before being sent to the
        * final destination, so that the location of the control panel will not
        * end up in the referrer logs of other sites.
        *
        */

        if (isset($_GET['URL'])) {
            if (! file_exists(APPPATH . 'libraries/Redirect.php')) {
                exit('Some components appear to be missing from your ExpressionEngine installation.');
            }

            require(APPPATH . 'libraries/Redirect.php');

            exit();  // We halt system execution since we're done
        }
    }

    /**
     * Remove session ID from string
     *
     * This function is used mainly by the Input class to strip
     * session IDs if they are used in public pages.
     *
     * @param   string
     * @return  string
     */
    public function remove_session_id($str)
    {
        return preg_replace("#S=.+?/#", "", $str);
    }

    /**
     * Sanitize Globals
     *
     * This function does the following:
     *
     * Unsets $_GET data (if query strings are not enabled)
     *
     * Unsets all globals if register_globals is enabled
     *
     * Standardizes newline characters to \n
     *
     * For action requests we need to fully allow GET variables, so we set
     * an exception in EE_Config. For css, we only need that one and it's a
     * path, so we'll do some stricter cleaning.
     *
     * @param   string
     * @return  string
     */
    public function _sanitize_globals()
    {
        $_css = $this->get('css');

        // It would be "wrong" to unset any of these GLOBALS.
        $protected = array('_SERVER', '_GET', '_POST', '_FILES', '_REQUEST',
            '_SESSION', '_ENV', 'GLOBALS',
            'system_folder', 'application_folder', 'BM', 'EXT',
            'CFG', 'URI', 'RTR', 'OUT', 'IN');

        // Unset globals for securiy.
        // This is effectively the same as register_globals = off
        foreach (array($_GET, $_POST, $_COOKIE) as $global) {
            if (! is_array($global)) {
                if (! in_array($global, $protected)) {
                    global $$global;

                    $$global = null;
                }
            } else {
                foreach ($global as $key => $val) {
                    if (! in_array($key, $protected)) {
                        global $$key;

                        $$key = null;
                    }
                }
            }
        }

        // Is $_GET data allowed? If not we'll set the $_GET to an empty array
        if ($this->_allow_get_array == false) {
            $_GET = array();
        } else {
            if (is_array($_GET) and count($_GET) > 0) {
                foreach ($_GET as $key => $val) {
                    $_GET[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
                }
            }
        }

        // Clean $_POST Data
        if (is_array($_POST) and count($_POST) > 0) {
            foreach ($_POST as $key => $val) {
                $_POST[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
            }
        }

        // Clean $_COOKIE Data
        if (is_array($_COOKIE) and count($_COOKIE) > 0) {
            // Also get rid of specially treated cookies that might be set by a server
            // or silly application, that are of no use to a CI application anyway
            // but that when present will trip our 'Disallowed Key Characters' alarm
            // http://www.ietf.org/rfc/rfc2109.txt
            // note that the key names below are single quoted strings, and are not PHP variables
            unset($_COOKIE['$Version']);
            unset($_COOKIE['$Path']);
            unset($_COOKIE['$Domain']);

            $cookie_prefix = (! config_item('cookie_prefix')) ? 'exp_' : config_item('cookie_prefix');

            foreach ($_COOKIE as $key => $val) {
                // Clean only our cookies
                if (substr($key, 0, strlen($cookie_prefix)) == $cookie_prefix) {
                    $_COOKIE[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
                }
            }
        }

        // Sanitize PHP_SELF
        $_SERVER['PHP_SELF'] = strip_tags($_SERVER['PHP_SELF']);

        if ($_css) {
            $_GET['css'] = remove_invisible_characters($_css);
        }
    }

    /**
     * Clean GET data
     *
     * If the GET value is disallowed, we show an error to superadmins
     * For non-super, we unset the variable and let them go on their merry way
     *
     * @param   string Variable's key
     * @param   mixed Variable's value- may be string or array
     * @deprecated 5.2.3
     * @return  string
     */
    public function _clean_get_input_data($str)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('5.2.3', "nada. Don't execute user input, duh. Use nothing");

        return true;
    }

    /**
    * Clean Keys
    *
    * This is a helper function. To prevent malicious users
    * from trying to exploit keys we make sure that keys are
    * only named with alpha-numeric text and a few other items.
    *
    * @access   private
    * @param    string
    * @return   string
    */
    public function _clean_input_keys($str)
    {
        // Clean UTF-8 if supported
        if (UTF8_ENABLED === true) {
            $str = $this->uni->clean_string($str);
        }

        return $str;
    }

    /**
    * Clean Input Data
    *
    * This is a helper function. It escapes data and
    * standardizes newline characters to \n
    *
    * @access   private
    * @param    string
    * @return   string
    */
    public function _clean_input_data($str)
    {
        if (is_array($str)) {
            $new_array = array();
            foreach ($str as $key => $val) {
                $new_array[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
            }

            return $new_array;
        }

        // Clean UTF-8 if supported
        if (UTF8_ENABLED === true) {
            $str = $this->uni->clean_string($str);
        }

        // Remove control characters
        $str = remove_invisible_characters($str, false);

        // Should we filter the input data?
        if ($this->_enable_xss === true) {
            $str = ee('Security/XSS')->clean($str);
        }

        // Standardize newlines if needed
        if ($this->_standardize_newlines == true) {
            if (strpos($str, "\r") !== false) {
                $str = str_replace(array("\r\n", "\r"), "\n", $str);
            }
        }

        return $str;
    }
}
// END CLASS

// EOF
