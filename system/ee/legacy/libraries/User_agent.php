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
 * User Agent Class
 *
 * Identifies the platform, browser, robot, or mobile devise of the browsing agent
 */
class EE_User_agent
{
    public $agent = null;

    public $is_browser = false;
    public $is_robot = false;
    public $is_mobile = false;

    public $languages = array();
    public $charsets = array();

    public $platforms = array();
    public $browsers = array();
    public $mobiles = array();
    public $robots = array();

    public $platform = '';
    public $browser = '';
    public $version = '';
    public $mobile = '';
    public $robot = '';

    /**
     * Constructor
     *
     * Sets the User Agent and runs the compilation routine
     *
     * @access	public
     * @return	void
     */
    public function __construct()
    {
        ee()->load->library('logger');
        ee()->logger->developer('User_agent library has been deprecated', true, 604800);

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->agent = trim($_SERVER['HTTP_USER_AGENT']);
        }

        if (! is_null($this->agent)) {
            if ($this->_load_agent_file()) {
                $this->_compile_data();
            }
        }

        log_message('debug', "User Agent Class Initialized");
    }

    /**
     * Compile the User Agent Data
     *
     * @access	private
     * @return	bool
     */
    public function _load_agent_file()
    {
        $agents = ee()->config->loadFile('user_agents');

        $this->platforms = $agents['platforms'];
        $this->browsers = $agents['browsers'];
        $this->mobiles = $agents['mobiles'];
        $this->robots = $agents['robots'];

        unset($agents);

        return true;
    }

    /**
     * Compile the User Agent Data
     *
     * @access	private
     * @return	bool
     */
    public function _compile_data()
    {
        $this->_set_platform();

        foreach (array('_set_browser', '_set_robot', '_set_mobile') as $function) {
            if ($this->$function() === true) {
                break;
            }
        }
    }

    /**
     * Set the Platform
     *
     * @access	private
     * @return	mixed
     */
    public function _set_platform()
    {
        if (is_array($this->platforms) and count($this->platforms) > 0) {
            foreach ($this->platforms as $key => $val) {
                if (preg_match("|" . preg_quote($key) . "|i", $this->agent)) {
                    $this->platform = $val;

                    return true;
                }
            }
        }
        $this->platform = 'Unknown Platform';
    }

    /**
     * Set the Browser
     *
     * @access	private
     * @return	bool
     */
    public function _set_browser()
    {
        if (is_array($this->browsers) and count($this->browsers) > 0) {
            foreach ($this->browsers as $key => $val) {
                if (preg_match("|" . preg_quote($key) . ".*?([0-9\.]+)|i", $this->agent, $match)) {
                    $this->is_browser = true;
                    $this->version = $match[1];
                    $this->browser = $val;
                    $this->_set_mobile();

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Set the Robot
     *
     * @access	private
     * @return	bool
     */
    public function _set_robot()
    {
        if (is_array($this->robots) and count($this->robots) > 0) {
            foreach ($this->robots as $key => $val) {
                if (preg_match("|" . preg_quote($key) . "|i", $this->agent)) {
                    $this->is_robot = true;
                    $this->robot = $val;

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Set the Mobile Device
     *
     * @access	private
     * @return	bool
     */
    public function _set_mobile()
    {
        if (is_array($this->mobiles) and count($this->mobiles) > 0) {
            foreach ($this->mobiles as $key => $val) {
                if (false !== (strpos(strtolower($this->agent), $key))) {
                    $this->is_mobile = true;
                    $this->mobile = $val;

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Set the accepted languages
     *
     * @access	private
     * @return	void
     */
    public function _set_languages()
    {
        if ((count($this->languages) == 0) and isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) and $_SERVER['HTTP_ACCEPT_LANGUAGE'] != '') {
            $languages = preg_replace('/(;q=[0-9\.]+)/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])));

            $this->languages = explode(',', $languages);
        }

        if (count($this->languages) == 0) {
            $this->languages = array('Undefined');
        }
    }

    /**
     * Set the accepted character sets
     *
     * @access	private
     * @return	void
     */
    public function _set_charsets()
    {
        if ((count($this->charsets) == 0) and isset($_SERVER['HTTP_ACCEPT_CHARSET']) and $_SERVER['HTTP_ACCEPT_CHARSET'] != '') {
            $charsets = preg_replace('/(;q=.+)/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_CHARSET'])));

            $this->charsets = explode(',', $charsets);
        }

        if (count($this->charsets) == 0) {
            $this->charsets = array('Undefined');
        }
    }

    /**
     * Is Browser
     *
     * @access	public
     * @return	bool
     */
    public function is_browser()
    {
        return $this->is_browser;
    }

    /**
     * Is Robot
     *
     * @access	public
     * @return	bool
     */
    public function is_robot()
    {
        return $this->is_robot;
    }

    /**
     * Is Mobile
     *
     * @access	public
     * @return	bool
     */
    public function is_mobile()
    {
        return $this->is_mobile;
    }

    /**
     * Is this a referral from another site?
     *
     * @access	public
     * @return	bool
     */
    public function is_referral()
    {
        return (! isset($_SERVER['HTTP_REFERER']) or $_SERVER['HTTP_REFERER'] == '') ? false : true;
    }

    /**
     * Agent String
     *
     * @access	public
     * @return	string
     */
    public function agent_string()
    {
        return $this->agent;
    }

    /**
     * Get Platform
     *
     * @access	public
     * @return	string
     */
    public function platform()
    {
        return $this->platform;
    }

    /**
     * Get Browser Name
     *
     * @access	public
     * @return	string
     */
    public function browser()
    {
        return $this->browser;
    }

    /**
     * Get the Browser Version
     *
     * @access	public
     * @return	string
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * Get The Robot Name
     *
     * @access	public
     * @return	string
     */
    public function robot()
    {
        return $this->robot;
    }
    /**
     * Get the Mobile Device
     *
     * @access	public
     * @return	string
     */
    public function mobile()
    {
        return $this->mobile;
    }

    /**
     * Get the referrer
     *
     * @access	public
     * @return	bool
     */
    public function referrer()
    {
        return (! isset($_SERVER['HTTP_REFERER']) or $_SERVER['HTTP_REFERER'] == '') ? '' : trim($_SERVER['HTTP_REFERER']);
    }

    /**
     * Get the accepted languages
     *
     * @access	public
     * @return	array
     */
    public function languages()
    {
        if (count($this->languages) == 0) {
            $this->_set_languages();
        }

        return $this->languages;
    }

    /**
     * Get the accepted Character Sets
     *
     * @access	public
     * @return	array
     */
    public function charsets()
    {
        if (count($this->charsets) == 0) {
            $this->_set_charsets();
        }

        return $this->charsets;
    }

    /**
     * Test for a particular language
     *
     * @access	public
     * @return	bool
     */
    public function accept_lang($lang = 'en')
    {
        return (in_array(strtolower($lang), $this->languages(), true)) ? true : false;
    }

    /**
     * Test for a particular character set
     *
     * @access	public
     * @return	bool
     */
    public function accept_charset($charset = 'utf-8')
    {
        return (in_array(strtolower($charset), $this->charsets(), true)) ? true : false;
    }
}

// EOF
