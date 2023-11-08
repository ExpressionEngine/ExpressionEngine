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
 * Exceptions
 */
class EE_Exceptions
{
    private $ob_level;

    protected $php_errors_output = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ob_level = ob_get_level();
    }

    /**
     * Exception Logger
     *
     * This function logs PHP generated error messages
     *
     * @param	string	the error severity
     * @param	string	the error string
     * @param	string	the error filepath
     * @param	string	the error line number
     * @return	string
     */
    public function log_exception($severity, $message, $filepath, $line)
    {
        list($error_constant, $error_category) = $this->lookupSeverity($severity);

        log_message('error', 'Severity: ' . $error_constant . '  --> ' . $message . ' ' . $filepath . ' ' . $line, true);
    }

    /**
     * 404 Page Not Found Handler
     *
     * @param	string
     * @return	string
     */
    public function show_404($page = '', $log_error = true)
    {
        if (defined('REQ') && constant('REQ') == 'CP') {
            throw new \ExpressionEngine\Error\FileNotFound();
        }

        $heading = "404 Page Not Found";
        $message = "The page you requested was not found.";

        // By default we log this, but allow a dev to skip it
        if ($log_error) {
            log_message('error', '404 Page Not Found --> ' . $page);
        }

        echo $this->show_error($heading, $message, 'error_general', 404);
        exit;
    }

    /**
     * Native PHP error handler
     *
     * @param	string	the error severity
     * @param	string	the error string
     * @param	string	the error filepath
     * @param	string	the error line number
     * @return	string
     */
    public function show_php_error($severity, $message, $filepath, $line)
    {
        $this->php_errors_output = true;

        list($error_constant, $error_category) = $this->lookupSeverity($severity);

        if (REQ == 'CLI') {
            stdout('PHP ' . $error_category . ':', CLI_STDOUT_FAILURE);
            echo $message . "\n";
            echo $filepath . ": $line\n\n";

            return;
        }

        $syspath = SYSPATH;

        // normalize for Windows servers
        if (DIRECTORY_SEPARATOR == '\\') {
            $syspath = str_replace('\\', '/', $syspath);
            $filepath = str_replace('\\', '/', $filepath);
            $message = str_replace('\\', '/', $message);
        }

        $filepath = str_replace($syspath, '', $filepath);
        $message = str_replace($syspath, '', $message);
        $message = htmlentities($message, ENT_QUOTES, 'UTF-8', false);

        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_flush();
        }

        ob_start();

        if (file_exists(APPPATH)) {
            include(APPPATH . 'errors/error_php.php');
        } else {
            include(BASEPATH . 'errors/error_php.php');
        }

        $buffer = ob_get_contents();
        ob_end_clean();
        echo $buffer;
    }

    /**
     * Has output PHP errors?
     * @return boolean whether or not we have displayed any PHP errors
     */
    public function hasOutputPhpErrors()
    {
        return $this->php_errors_output;
    }

    /**
     * Show Error
     *
     * Take over CI's Error template to use the EE user error template
     *
     * @param	string	the heading
     * @param	string	the message
     * @param	string	the template
     * @return	string
     */
    public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        if (REQ == 'CLI') {
            $cli = new \ExpressionEngine\Cli\Cli();
            $cli->fail($message);
        }

        set_status_header($status_code);

        // Ajax Requests get a reasonable response
        if (defined('AJAX_REQUEST') && AJAX_REQUEST) {
            ee()->output->send_ajax_response(array(
                'error' => $message
            ));
        }

        if (is_array($message)) {
            $message = '<p>' . implode("</p>\n\n<p>", $message) . '</p>';
        }

        // If we have the template class we can show their error template
        if (function_exists('ee') && isset(ee()->TMPL)) {
            ee()->output->fatal_error($message);
        }

        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_flush();
        }

        ob_start();

        if (file_exists(APPPATH)) {
            include(APPPATH . 'errors/' . $template . '.php');
        } else {
            include(BASEPATH . 'errors/' . $template . '.php');
        }

        $buffer = ob_get_contents();
        ob_end_clean();

        return $buffer;

        /*
                // "safe" HTML typography in EE will strip paragraph tags, and needs newlines to indicate paragraphs
                $message = '<p>'.implode("</p>\n\n<p>", ( ! is_array($message)) ? array($message) : $message).'</p>';

                if ( ! class_exists('CI_Controller'))
                {
                    // too early to do anything pretty
                    exit($message);
                }

                // let's be kind if it's a submission error, and offer a back link
                if ( ! empty($_POST) && ! (defined('AJAX_REQUEST') && AJAX_REQUEST))
                {
                    $message .= '<p><a href="javascript:history.go(-1);">&#171; '.ee()->lang->line('back').'</a></p>';
                }

                // Ajax Requests get a reasonable response
                if (defined('AJAX_REQUEST') && AJAX_REQUEST)
                {
                    ee()->output->send_ajax_response(array(
                        'error'	=> $message
                    ));
                }

                // Error occurred on a frontend request

                // AR DB errors can result in a memory loop on subsequent queries so we output them now
                if ($template == 'error_db')
                {
                    exit($message);
                }

                // everything is in place to show the
                // custom error template
                ee()->output->fatal_error($message);
                */
    }

    public function show_exception($exception, $status_code = 500)
    {
        set_status_header($status_code);

        $error_type = get_class($exception);

        $message = $exception->getMessage();

        $syspath = SYSPATH;
        $filepath = $exception->getFile();

        // normalize for Windows servers
        if (DIRECTORY_SEPARATOR == '\\') {
            $syspath = str_replace('\\', '/', $syspath);
            $filepath = str_replace('\\', '/', $filepath);
            $message = str_replace('\\', '/', $message);
        }

        // Replace system path
        $filepath = str_replace($syspath, '', $filepath);
        $message = str_replace($syspath, '', $message);

        $message = htmlentities($message, ENT_QUOTES, 'UTF-8', false);

        // whitelist formatting tags
        foreach (['i', 'b', 'br'] as $tag) {
            $message = str_replace(["&lt;{$tag}&gt;", "&lt;/{$tag}&gt;"], ["<{$tag}>", "</{$tag}>"], $message);
        }

        //allow links to docs
        $message = preg_replace('/&lt;a href=&quot;https:\/\/docs\.expressionengine\.com(.*)&quot;&gt;(.*)&lt;\/a&gt;/i', '<a href="https://docs.expressionengine.com${1}">${2}</a>', $message);

        $location = $filepath . ':' . $exception->getLine();
        $trace = explode("\n", $exception->getTraceAsString());
        $partial_path = substr($syspath, 0, 15);

        // Replace the system paths in the stack trace
        foreach ($trace as &$line) {
            $path = SYSPATH;

            // Go back a few directory levels from the system directory  in case
            // we need to replace paths in a file not in the system directory
            $i = 0;
            while ($i < 3 && $path !== '/') {
                // Make sure we have a trailing slash for the replace
                $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                $quoted_path = preg_quote($path, '/');
                $line = preg_replace('/^(#\d+\s+)' . $quoted_path . '/', '$1', $line);
                $path = dirname($path);
                $i++;
            }

            $line = str_replace($partial_path, '', $line);
            $line = htmlentities($line, ENT_QUOTES, 'UTF-8');
        }

        // We'll only want to show certain information, like file paths, if we're allowed
        $debug = (bool) (DEBUG or (isset(ee()->config) && ee()->config->item('debug') > 1) or (isset(ee()->session) && ee('Permission')->isSuperAdmin()));

        // Hide sensitive information such as file paths and database information
        if (! $debug) {
            $location_parts = explode('/', $location);
            $location = array_pop($location_parts);

            if (strpos($message, 'SQLSTATE') !== false) {
                $message = 'There was a database connection error or a problem with a query. Log in as a super admin or enable debugging for more information.';
            }
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            $return = [
                'messageType' => 'error',
                'message' => $message,
                'trace' => $trace
            ];
            echo json_encode($return);
            exit;
        }

        // If the request came from the cli, show an appropriate message
        if (REQ === 'CLI') {
            echo "$error_type caught:\n";
            echo html_entity_decode($message) . "\n";
            echo html_entity_decode($location) . "\n";
            foreach ($trace as $stackItem) {
                echo html_entity_decode($stackItem) . "\n";
            }
            exit;
        }

        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_flush();
        }

        ob_start();

        if (defined('EE_APPPATH')) {
            include(EE_APPPATH . 'errors/error_exception.php');
        } else {
            include(APPPATH . 'errors/error_exception.php');
        }

        $buffer = ob_get_contents();
        ob_end_clean();
        echo $buffer;
        exit;
    }

    /**
     * @return Array of [PHP Severity constant, Human severity name]
     */
    private function lookupSeverity($severity)
    {
        switch ($severity) {
            case E_ERROR:
                return array('E_ERROR', 'Error');
            case E_WARNING:
                return array('E_WARNING', 'Warning');
            case E_PARSE:
                return array('E_PARSE', 'Error');
            case E_NOTICE:
                return array('E_NOTICE', 'Notice');
            case E_CORE_ERROR:
                return array('E_CORE_ERROR', 'Error');
            case E_CORE_WARNING:
                return array('E_CORE_WARNING', 'Warning');
            case E_COMPILE_ERROR:
                return array('E_COMPILE_ERROR', 'Error');
            case E_COMPILE_WARNING:
                return array('E_COMPILE_WARNING', 'Warning');
            case E_USER_ERROR:
                return array('E_USER_ERROR', 'Error');
            case E_USER_WARNING:
                return array('E_USER_WARNING', 'Warning');
            case E_USER_NOTICE:
                return array('E_USER_NOTICE', 'Notice');
            case E_STRICT:
                return array('E_STRICT', 'Notice');
            case E_RECOVERABLE_ERROR:
                return array('E_RECOVERABLE_ERROR', 'Error');
            case E_DEPRECATED:
                return array('E_DEPRECATED', 'Deprecated');
            case E_USER_DEPRECATED:
                return array('E_USER_DEPRECATED', 'Deprecated');
            default:
                return array('UNKNOWN', 'Error');
        }
    }
}
// END Exceptions Class

// EOF
