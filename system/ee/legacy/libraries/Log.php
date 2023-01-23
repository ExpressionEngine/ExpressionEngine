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
 * Logging Class
 */
class EE_Log
{
    protected $_log_path;
    protected $_threshold = 1;
    protected $_date_fmt = 'Y-m-d H:i:s';
    protected $_enabled = true;
    protected $_levels = array('ERROR' => '1', 'DEBUG' => '2',  'INFO' => '3', 'ALL' => '4');

    /**
     * Constructor
     */
    public function __construct()
    {
        $config = & get_config();

        $this->_log_path = SYSPATH . 'user/logs/';

        if (! is_dir($this->_log_path) || ! is_really_writable($this->_log_path)) {
            $this->_enabled = false;
        }

        if (is_numeric($config['log_threshold'])) {
            $this->_threshold = $config['log_threshold'];
        }

        if (isset($config['log_date_format']) && $config['log_date_format'] != '') {
            $this->_date_fmt = $config['log_date_format'];
        }
    }

    /**
     * Write Log File
     *
     * Generally this function will be called using the global log_message() function
     *
     * @param	string	the error level
     * @param	string	the error message
     * @param	bool	whether the error is a native PHP error
     * @return	bool
     */
    public function write_log($level = 'error', $msg = '', $php_error = false)
    {
        if ($this->_enabled === false) {
            return false;
        }

        $level = strtoupper($level);

        if (! isset($this->_levels[$level]) or ($this->_levels[$level] > $this->_threshold)) {
            return false;
        }

        $filepath = $this->_log_path . 'log-' . date('Y-m-d') . '.php';
        $message = '';

        if (! file_exists($filepath)) {
            $message .= "<" . "?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?" . ">\n\n";
        }

        if (! $fp = @fopen($filepath, FOPEN_WRITE_CREATE)) {
            return false;
        }

        $message .= $level . ' ' . (($level == 'INFO') ? ' -' : '-') . ' ' . date($this->_date_fmt) . ' --> ' . $msg . "\n";

        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);

        @chmod($filepath, FILE_WRITE_MODE);

        return true;
    }
}
// END Log Class

// EOF
