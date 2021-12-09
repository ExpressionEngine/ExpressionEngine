<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Output Display
 */
class EE_Output
{
    public $out_type = 'webpage';
    public $refresh_msg = true;			// TRUE/FALSE - whether to show the "You will be redirected in 5 seconds" message.
    public $refresh_time = 1;			// Number of seconds for redirects

    public $remove_unparsed_variables = false; // whether to remove left-over variables that had bad syntax

    public $final_output = '';
    public $cache_expiration = 0;
    public $headers = array();
    public $enable_profiler = false;
    public $parse_exec_vars = true;	// whether or not to parse variables like {elapsed_time} and {memory_usage}

    public $_zlib_oc = false;

    public function __construct()
    {
        $this->_zlib_oc = @ini_get('zlib.output_compression');

        log_message('debug', "Output Class Initialized");
    }

    /**
     * Get Output
     *
     * Returns the current output string
     *
     * @access	public
     * @return	string
     */
    public function get_output()
    {
        return $this->final_output;
    }

    /**
     * Set Output
     *
     * Sets the output string
     *
     * @access	public
     * @param	string
     * @return	void
     */
    public function set_output($output)
    {
        $this->final_output = $output;
    }

    /**
     * Append Output
     *
     * Appends data onto the output string
     *
     * @access	public
     * @param	string
     * @return	void
     */
    public function append_output($output)
    {
        $this->final_output .= $output;
    }

    /**
     * Set Header
     *
     * Lets you set a server header which will be outputted with the final display.
     *
     * Note:  If a file is cached, headers will not be sent.  We need to figure out
     * how to permit header data to be saved with the cache data...
     *
     * @access	public
     * @param	string
     * @return	void
     */
    public function set_header($header, $replace = true)
    {
        // We always need to send a content type

        if (ee()->config->item('send_headers') != 'y' && strncasecmp($header, 'content-type', 12) != 0) {
            return;
        }

        // If zlib.output_compression is enabled it will compress the output,
        // but it will not modify the content-length header to compensate for
        // the reduction, causing the browser to hang waiting for more data.
        // We'll just skip content-length in those cases.

        if ($this->_zlib_oc && strncasecmp($header, 'content-length', 14) == 0) {
            return;
        }

        $this->headers[] = array($header, $replace);
    }

    /**
     * Set HTTP Status Header
     * moved to Common procedural functions in 1.7.2
     *
     * @access	public
     * @param	int		the status code
     * @param	string
     * @return	void
     */
    public function set_status_header($code = 200, $text = '')
    {
        set_status_header($code, $text);
    }

    /**
     * Enable/disable Profiler
     *
     * @access	public
     * @param	bool
     * @return	void
     */
    public function enable_profiler($val = true)
    {
        $this->enable_profiler = (is_bool($val)) ? $val : true;
    }

    /**
     * Set Cache
     *
     * @access	public
     * @param	integer
     * @return	void
     */
    public function cache($time)
    {
        $this->cache_expiration = (! is_numeric($time)) ? 0 : $time;
    }

    /**
     * add content before closing body tag
     */
    public function add_to_foot($output, $content)
    {
        // If the output data contains closing </body> and </html> tags
        // we will remove them and add them back after we insert the profile data
        if (preg_match("|</body>.*?</html>|is", $output)) {
            $output = preg_replace("|</body>.*?</html>|is", '', $output);
            $output .= $content;
            $output .= '</body></html>';
        } else {
            $output .= $content;
        }

        return $output;
    }

    /**
     * Display the final output
     *
     * @access	public
     * @return	void
     */
    public function _display($output = '', $status = 200)
    {
        if ($output == '') {
            $output = $this->final_output;
        }

        // Generate No-Cache Headers

        if (ee()->config->item('send_headers') == 'y' && $this->out_type != 'feed' && $this->out_type != '404' && $this->out_type != 'cp_asset') {
            $this->set_status_header($status);

            if (! ee('Response')->hasHeader('Expires')) {
                $this->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            }

            if (! ee('Response')->hasHeader('Last-Modified')) {
                $this->set_header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            }

            if (! ee('Response')->hasHeader('Pragma')) {
                $this->set_header("Pragma: no-cache");
            }
        }

        // Content Type Headers
        // Also need to do some extra work for feeds

        switch ($this->out_type) {
            case 'webpage':
                if (! ee('Response')->hasHeader('Content-Type')) {
                    $this->set_header("Content-Type: text/html; charset=" . ee()->config->item('charset'));
                }

                break;
            case 'css':
                if (! ee('Response')->hasHeader('Content-Type')) {
                    $this->set_header("Content-type: text/css");
                }

                break;
            case 'js':
                if (! ee('Response')->hasHeader('Content-Type')) {
                    $this->set_header("Content-type: text/javascript");
                }
                $this->enable_profiler = false;

                break;
            case '404':
                $this->set_status_header(404);
                $this->set_header("Date: " . gmdate("D, d M Y H:i:s") . " GMT");

                break;
            case 'xml':
                if (! ee('Response')->hasHeader('Content-Type')) {
                    $this->set_header("Content-Type: text/xml");
                }
                $output = trim($output);

                break;
            case 'feed':
                $this->_send_feed($output);

                break;
            default: // Likely a custom template type
                // -------------------------------------------
                // 'template_types' hook.
                //  - Provide information for custom template types.
                //
                $template_types = ee()->extensions->call('template_types', array());
                //
                // -------------------------------------------

                if (isset($template_types[$this->out_type])) {
                    // Set custom headers as defined by the template_headers key,
                    // and replace any headers as necessary
                    if (isset($template_types[$this->out_type]['template_headers'])) {
                        foreach ($template_types[$this->out_type]['template_headers'] as $header) {
                            $this->set_header($header, true);
                        }
                    }
                }

                break;
        }

        // Compress the output
        // We simply set the ci config value to true

        if (ee()->config->item('gzip_output') == 'y' and REQ == 'PAGE') {
            ee()->config->set_item('compress_output', true);
        }

        // Send FLOC headers
        if(REQ == 'PAGE' && ee()->config->item('enable_floc') !== 'y') {
            $this->set_header("Permissions-Policy: interest-cohort=()");
        }

        // Parse query count
        if (REQ != 'CP') {
            $output = str_replace(LD . 'total_queries' . RD, ee()->db->query_count, $output);
        }

        // Note:  We use globals because we can't use $CI =& get_instance()
        // since this function is sometimes called by the caching mechanism,
        // which happens before the CI super object is available.
        global $BM, $CFG;

        // --------------------------------------------------------------------

        // Set the output data
        if ($output == '') {
            $output = & $this->final_output;
        }

        // --------------------------------------------------------------------

        // Do we need to write a cache file?
        if ($this->cache_expiration > 0) {
            $this->_write_cache($output);
        }

        // --------------------------------------------------------------------

        // Parse out the elapsed time and memory usage,
        // then swap the pseudo-variables with the data

        $elapsed = $BM->elapsed_time('total_execution_time_start', 'total_execution_time_end');

        if ($this->parse_exec_vars === true) {
            $memory = (! function_exists('memory_get_usage')) ? '0' : round(memory_get_usage() / 1024 / 1024, 2) . 'MB';

            $output = str_replace('{elapsed_time}', $elapsed, $output);
            $output = str_replace('{memory_usage}', $memory, $output);
        }

        // --------------------------------------------------------------------

        // Is compression requested?
        // if PHP errors have been output by our exception handler, we can't change encodings mid-stream, so also check for our error handling class having been loaded
        if ($CFG->item('compress_output') === true && $this->_zlib_oc == false) {
            // can't change encodings mid-stream, if we've already displayed PHP errors, we cannot Gzip the rest of the output
            $error_out = false;
            if (class_exists('EE_Exceptions')) {
                $exceptions = load_class('Exceptions', 'core');
                $error_out = $exceptions->hasOutputPhpErrors();
            }

            if (! $error_out && extension_loaded('zlib')) {
                if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) and strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
                    ob_start('ob_gzhandler');
                }
            }
        }

        // --------------------------------------------------------------------

        // Are there any server headers to send?
        if (count($this->headers) > 0) {
            foreach ($this->headers as $header) {
                @header($header[0], $header[1]);
            }
        }

        // --------------------------------------------------------------------
        // Include PRO stuff
        $installMode = false;
        if (file_exists(FCPATH . '.env.php') && (require FCPATH . '.env.php') == true) {
            $installMode = getenv('EE_INSTALL_MODE') === 'TRUE';
        }
        $inInstallMode = is_dir(SYSPATH . 'ee/installer/') && $installMode;
        if (IS_PRO && !$inInstallMode) {
            $output = ee('pro:Dock')->buildOutput($output);
        }
        if (REQ == 'PAGE' || (REQ == 'ACTION' && ee('LivePreview')->hasEntryData())) {
            if (isset(ee()->TMPL) && is_object(ee()->TMPL) && in_array(ee()->TMPL->template_type, ['webpage'])) {
                $output = preg_replace("/\{frontedit_link\s+(.*)\}/sU", '', $output);
                $output = preg_replace("/\<\!--\s*(\/\/\s*)*disable\s*frontedit\s*--\>/sU", '', $output);
            }
        }
        // --------------------------------------------------------------------

        // Do we need to generate profile data?
        // If so, load the Profile service and run it.
        if ($this->enable_profiler == true && (! (AJAX_REQUEST or ee('LivePreview')->hasEntryData()))) {
            $performance = array(
                'database' => number_format(ee('Database')->currentExecutionTime(), 4),
                'benchmarks' => ee()->benchmark->getBenchmarkTimings()
            );

            $profiler = ee('Profiler')
                ->addSection('performance', $performance)
                ->addSection('variables', array(
                    'server' => $_SERVER,
                    'cookie' => $_COOKIE,
                    'get' => $_GET,
                    'post' => $_POST,
                    'userdata' => ee()->session->all_userdata()
                ))
                ->addSection('database', array(ee('Database')));

            // Add the template debugger to the output

            if (isset(ee()->TMPL) &&
                is_object(ee()->TMPL) &&
                isset(ee()->TMPL->debugging) &&
                ee()->TMPL->debugging === true &&
                ee()->TMPL->template_type != 'js') {
                $profiler->addSection('template', ee()->TMPL->log);
            }

            if (REQ == 'CP') {
                $output = str_replace('<div id="output_profiler"></div>', $profiler->render(), $output);
            } else {
                $output = $this->add_to_foot($output, $profiler->render());
            }
        }

        if (REQ == 'PAGE') {
            /* -------------------------------------------
            /*	Hidden Configuration Variables
            /*	- remove_unparsed_vars => Whether or not to remove unparsed EE variables
            /*  This is most helpful if you wish for debug to be set to 0, as EE will not
            /*  strip out javascript.
            /* -------------------------------------------*/
            $remove_vars = (ee()->config->item('remove_unparsed_vars') == 'y');
            $this->remove_unparsed_variables($remove_vars);

            if (ee()->config->item('debug') == 0 &&
                $this->remove_unparsed_variables === true) {
                $output = preg_replace("/" . LD . "[^;\n]+?" . RD . "/", '', $output);
            }

            // Garbage Collection
            ee()->core->_garbage_collection();
        }

        // --------------------------------------------------------------------

        echo $output;  // Send it to the browser!

        log_message('debug', "Final output sent to browser");
        log_message('debug', "Total execution time: " . $elapsed);
    }

    /**
     * Do extra processing for feeds
     *
     * @access	private
     * @param	string
     * @return	void
     */
    public function _send_feed(&$output)
    {
        $request = (! function_exists('getallheaders')) ? array() : @getallheaders();

        if (preg_match("|<ee\:last_update>(.*?)<\/ee\:last_update>|", $output, $matches)) {
            $last_update = $matches['1'];
            $output = str_replace($matches['0'], '', $output);
        } else {
            $last_update = ee()->localize->now;
        }

        $output = trim($output);

        // Check for the 'If-Modified-Since' Header

        if (ee()->config->item('send_headers') == 'y' && isset($request['If-Modified-Since']) && trim($request['If-Modified-Since']) != '') {
            $x = explode(';', $request['If-Modified-Since']);
            $modify_tstamp = strtotime($x['0']);

            // If no new content, send no data

            if ($last_update <= $modify_tstamp) {
                $this->set_status_header(304);
                exit;
            }
        }

        $this->set_status_header(200);
        $this->set_header("Content-Type: text/xml; charset=" . ee()->config->item('output_charset'));

        $this->set_header('Expires: ' . gmdate('D, d M Y H:i:s', $last_update + (60 * 60)) . ' GMT'); // One hour
        $this->set_header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_update) . ' GMT');
        $this->set_header("Cache-Control: no-store, no-cache, must-revalidate");
        $this->set_header("Cache-Control: post-check=0, pre-check=0", false);
        $this->set_header("Pragma: no-cache");

        // Swap XML declaration for RSS files

        $output = preg_replace("/{\?xml(.+?)\?}/", "<?xml\\1?" . ">", $output);
    }

    /**
     * Display fatal error message
     *
     * @access	public
     * @return	void
     */
    public function fatal_error($error_msg = '', $use_lang = true)
    {
        $heading = ($use_lang == true && is_object(ee()->lang)) ? ee()->lang->line('error') : 'Error Message';

        $data = array('title' => $heading,
            'heading' => $heading,
            'content' => '<p>' . $error_msg . '</p>'
        );

        $this->show_message($data);
    }

    /**
     * System is off message
     *
     * @access	public
     * @return	void
     */
    public function system_off_msg()
    {
        $query = ee()->db->query("SELECT template_data FROM exp_specialty_templates WHERE site_id = '" . ee()->db->escape_str(ee()->config->item('site_id')) . "' AND template_name = 'offline_template'");

        $this->set_status_header(503, 'Service Temporarily Unavailable');
        @header('Retry-After: 3600');

        echo $query->row('template_data') ;
        exit;
    }

    /**
     * Show message
     *
     * This function and the next enable us to show error messages to
     * users when needed. For example, when a form is submitted without
     * the required info.
     *
     * This is not used in the control panel, only with publicly
     * accessible pages.
     *
     * @access	public
     * @param	mixed
     * @param	bool
     * @return	void
     */
    public function show_message($data, $xhtml = true, $redirect_url = false)
    {
        // If we have a redirect URL, use that instead of outputting the standard page.
        if (! empty($redirect_url)) {
            ee()->functions->redirect($redirect_url);
        }

        @header("Cache-Control: no-cache, must-revalidate");
        @header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        @header("Pragma: no-cache");

        foreach (array('title', 'heading', 'content', 'redirect', 'rate', 'link') as $val) {
            if (! isset($data[$val])) {
                $data[$val] = '';
            }
        }

        if (! is_numeric($data['rate']) or $data['rate'] == '') {
            $data['rate'] = $this->refresh_time;
        }

        $data['meta_refresh'] = '';

        if ($data['redirect'] != '') {
            $secure_redirect = ee('Security/XSS')->clean($data['redirect']);
            $secure_redirect = htmlentities($secure_redirect, ENT_QUOTES, 'UTF-8');
            $js_rate = $data['rate'] * 1000;

            $data['meta_refresh'] = "<script type='text/javascript'>setTimeout(function(){document.location='" . $secure_redirect . "'}," . $js_rate . ')</script>';
            $data['meta_refresh'] .= "<noscript><meta http-equiv='refresh' content='" . $data['rate'] . "; url=" . $secure_redirect . "'></noscript>";
        }

        $data['charset'] = ee()->config->item('output_charset');

        if (is_array($data['link']) and count($data['link']) > 0) {
            $refresh_msg = ($data['redirect'] != '' and $this->refresh_msg == true) ? ee()->lang->line('click_if_no_redirect') : '';

            $ltitle = ($refresh_msg == '') ? $data['link']['1'] : $refresh_msg;

            $url = (strtolower($data['link']['0']) == 'javascript:history.go(-1)') ? $data['link']['0'] : ee('Security/XSS')->clean($data['link']['0']);
            $url = htmlentities($url, ENT_QUOTES, 'UTF-8');

            $data['link'] = "<a href='" . $url . "'>" . $ltitle . "</a>";
        }

        if ($xhtml == true && isset(ee()->session)) {
            ee()->load->library('typography');

            $data['content'] = ee()->typography->parse_type(stripslashes($data['content']), array('text_format' => 'xhtml'));
        }

        $template_data = false;

        // Determine if we have an override template for the system messages.
        $template_group = ee('Model')->get('TemplateGroup')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('group_name', 'system_messages')->first();

        if (! empty($template_group)) {
            $template = ee('Model')->get('Template')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('group_id', $template_group->group_id)
                ->filter('template_name', 'generic')->first();

            if (!empty($template) && !empty($template->template_data)) {
                $template_data = $template->template_data;

                if (!isset(ee()->TMPL)) {
                    ee()->load->library('template', null, 'TMPL');
                }
                ee()->TMPL->parse($template_data);
                $template_data = ee()->TMPL->parse_globals(ee()->TMPL->final_template);
            }
        }

        if (empty($template_data)) {
            ee()->db->select('template_data');
            ee()->db->where('site_id', ee()->config->item('site_id'));
            ee()->db->where('template_name', 'message_template');
            $query = ee()->db->get('specialty_templates');

            $row = $query->row_array();
            $template_data = $row['template_data'];
        }

        foreach ($data as $key => $val) {
            $template_data = str_replace('{' . $key . '}', $val, $template_data);
        }

        $output = stripslashes($template_data);

        // -------------------------------------------
        // 'output_show_message' hook.
        //  - Modify the HTML output of the message
        //  - added 3.2.0
        //
        if (ee()->extensions->active_hook('output_show_message') === true) {
            $output = ee()->extensions->call('output_show_message', $data, $output);
        }
        //
        // -------------------------------------------

        echo $output;
        exit;
    }

    /**
     * Show user error
     *
     * @access	public
     * @param	string
     * @param	mixed
     * @param	string
     * @return	void
     */
    public function show_user_error($type = 'submission', $errors = '', $heading = '', $redirect_url = '')
    {
        // If we have a redirect URL, use that instead of outputting the standard error page.
        if (! empty($redirect_url)) {
            ee()->session->set_flashdata('errors', $errors);
            ee()->functions->redirect($redirect_url);
        } else {
            // If we're using an error template, kill our flashdata.
            ee()->session->_age_flashdata();
        }

        $this->set_header("Content-Type: text/html; charset=" . ee()->config->item('charset'));
        $this->set_status_header(403);

        if ($type != 'off') {
            if ($type == 'general') {
                $heading = ee()->lang->line('general_error');
            } else {
                $heading = ee()->lang->line('submission_error');
            }
        }

        $content = '<ul>';

        if (! is_array($errors)) {
            $content .= "<li>" . $errors . "</li>\n";
        } else {
            foreach ($errors as $val) {
                $content .= "<li>" . $val . "</li>\n";
            }
        }

        $content .= "</ul>";

        $data = array(
            'title' => ee()->lang->line('error'),
            'heading' => $heading,
            'content' => $content,
            'redirect' => '',
            'link' => array('JavaScript:history.go(-1)', ee()->lang->line('return_to_previous'))
        );

        $this->show_message($data, 0);
    }

    /**
     * Send AJAX response
     *
     * Outputs and exits content, makes sure profiler is disabled
     * and sends 500 status header on error
     *
     * @access	public
     * @param	string
     * @param	bool	whether or not the response is an error
     * @return	void
     */
    public function send_ajax_response($msg, $error = false)
    {
        $this->enable_profiler(false);

        if ($error === true) {
            $this->set_status_header(500);
        }

        if (ee()->config->item('send_headers') == 'y') {
            ee()->load->library('user_agent', array(), 'user_agent');

            // many browsers do not consistently like this content type
            if (is_array($msg) && in_array(ee()->user_agent->browser(), array('Safari', 'Chrome', 'Firefox'))) {
                @header('Content-Type: application/json; charset=UTF-8');
            } else {
                @header('Content-Type: text/html; charset=UTF-8');
            }
        }

        exit(json_encode($msg));
    }

    /**
     * Send Cache Headers
     *
     * Used to control client caching for JS, CSS
     *
     * @access	public
     * @param	int		Unix Timestamp, date of "file" modification
     * @param	int		max-age value
     * @param	string	path identifier for ETag, helpful in load balanced environs
     * @return	void
     */
    public function send_cache_headers($modified, $max_age = 172800, $etag_path = null)
    {
        if (ee()->config->item('send_headers') == 'y') {
            $max_age = (int) $max_age;
            $modified = (int) $modified;
            $modified_since = ee()->input->server('HTTP_IF_MODIFIED_SINCE');

            // Remove anything after the semicolon

            if ($pos = strrpos($modified_since, ';') !== false) {
                $modified_since = substr($modified_since, 0, $pos);
            }

            // If the file is in the client cache, we'll
            // send a 304 and be done with it.

            if ($modified_since && (strtotime($modified_since) == $modified)) {
                $this->set_status_header(304);
                exit;
            }

            // All times GMT
            $modified = gmdate('D, d M Y H:i:s', $modified) . ' GMT';
            $expires = gmdate('D, d M Y H:i:s', time() + $max_age) . ' GMT';

            $this->set_status_header(200);
            $this->set_header("Cache-Control: max-age={$max_age}, must-revalidate");
            $this->set_header('Vary: Accept-Encoding');
            $this->set_header('Last-Modified: ' . $modified);
            $this->set_header('Expires: ' . $expires);

            // Send a custom ETag to maintain a useful cache in
            // load-balanced environments
            if (! is_null($etag_path)) {
                $this->set_header("ETag: " . md5($modified . $etag_path));
            }
        }
    }

    /**
     * Setter for the remove_unparsed_variables class var
     *
     * used in the ee.php controller.
     *
     * @param 	boolean
     */
    public function remove_unparsed_variables($remove_unparsed_vars)
    {
        $this->remove_unparsed_variables = $remove_unparsed_vars;
    }

    /**
     * Display a generic Unauthorized Access error to the user, or
     * sends an error response back for Ajax requests
     *
     * @return void throws an error and halts processing
     */
    public function throwAuthError()
    {
        if (AJAX_REQUEST) {
            $this->send_ajax_response(lang('not_authorized'), true);
        } else {
            $this->show_user_error('submission', [lang('not_authorized')]);
        }
    }
}
// END CLASS

// EOF
