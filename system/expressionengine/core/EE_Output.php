<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Output Display Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Output extends CI_Output {

	var $out_type		= 'webpage';
	var $refresh_msg	= TRUE;			// TRUE/FALSE - whether to show the "You will be redirected in 5 seconds" message.
	var $refresh_time	= 1;			// Number of seconds for redirects

	var $remove_unparsed_variables = FALSE; // whether to remove left-over variables that had bad syntax

	// --------------------------------------------------------------------

	/**
	 * Set Header
	 *
	 * Lets you set a server header which will be outputted with the final display.
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_header($header, $replace = TRUE)
	{
		$EE =& get_instance();

		// We always need to send a content type

		if ($EE->config->item('send_headers') != 'y' && strncasecmp($header, 'content-type', 12) != 0)
		{
			return;
		}

		parent::set_header($header, $replace);
	}

	// --------------------------------------------------------------------

	/**
	 * Display the final output
	 *
	 * @access	public
	 * @return	void
	 */
	function _display($output = '')
	{
		$EE =& get_instance();

		if ($output == '')
		{
			$output = $this->final_output;
		}


		// Generate No-Cache Headers

		if ($EE->config->item('send_headers') == 'y' && $this->out_type != 'feed' && $this->out_type != '404' && $this->out_type != 'cp_asset')
		{
			$this->set_status_header(200);

			$this->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			$this->set_header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
			$this->set_header("Pragma: no-cache");
		}


		// Content Type Headers
		// Also need to do some extra work for feeds

		switch ($this->out_type)
		{
			case 'webpage':	$this->set_header("Content-Type: text/html; charset=".$EE->config->item('charset'));
				break;
			case 'css':		$this->set_header("Content-type: text/css");
				break;
			case 'js':		$this->set_header("Content-type: text/javascript");
							$this->enable_profiler = FALSE;
				break;
			case '404':		$this->set_status_header(404);
							$this->set_header("Date: ".gmdate("D, d M Y H:i:s")." GMT");
				break;
			case 'xml':		$this->set_header("Content-Type: text/xml");
							$output = trim($output);
				break;
			case 'feed':	$this->_send_feed($output);
				break;
			default: // Likely a custom template type
				// -------------------------------------------
				// 'template_types' hook.
				//  - Provide information for custom template types.
				//
				$template_types = $EE->extensions->call('template_types', array());
				//
				// -------------------------------------------

				if (isset($template_types[$this->out_type]))
				{
					// Set custom headers as defined by the template_headers key,
					// and replace any headers as necessary
					if (isset($template_types[$this->out_type]['template_headers']))
					{
						foreach ($template_types[$this->out_type]['template_headers'] as $header)
						{
							$this->set_header($header, TRUE);
						}
					}
				}
				break;
		}

		// Compress the output
		// We simply set the ci config value to true

		if ($EE->config->item('gzip_output') == 'y' AND REQ == 'PAGE')
		{
			$EE->config->set_item('compress_output', TRUE);
		}


		// Parse query count
		if (REQ != 'CP')
		{
			$output = str_replace(LD.'total_queries'.RD, $EE->db->query_count, $output);
		}

		// Send it to the CI method for final processing
		parent::_display($output);
	}

	// --------------------------------------------------------------------

	/**
	 * Do extra processing for feeds
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function _send_feed(&$output)
	{
		$EE =& get_instance();

		$request = ( ! function_exists('getallheaders')) ? array() : @getallheaders();

		if (preg_match("|<ee\:last_update>(.*?)<\/ee\:last_update>|", $output, $matches))
		{
			$last_update = $matches['1'];
			$output = str_replace($matches['0'], '', $output);
		}
		else
		{
			$last_update = $EE->localize->now;
		}

		$output = trim($output);


		// Check for the 'If-Modified-Since' Header

		if ($EE->config->item('send_headers') == 'y' && isset($request['If-Modified-Since']) && trim($request['If-Modified-Since']) != '')
		{
			$x				= explode(';', $request['If-Modified-Since']);
			$modify_tstamp	= strtotime($x['0']);

			// If no new content, send no data

			if ($last_update <= $modify_tstamp)
			{
				$this->set_status_header(304);
				exit;
			}
		}

		$this->set_status_header(200);
		$this->set_header("Content-Type: text/xml; charset=".$EE->config->item('output_charset'));

		$this->set_header('Expires: '.gmdate('D, d M Y H:i:s', $last_update+(60*60)).' GMT'); // One hour
		$this->set_header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_update).' GMT');
		$this->set_header("Cache-Control: no-store, no-cache, must-revalidate");
		$this->set_header("Cache-Control: post-check=0, pre-check=0", false);
		$this->set_header("Pragma: no-cache");


		// Swap XML declaration for RSS files

		$output = preg_replace("/{\?xml(.+?)\?}/", "<?xml\\1?".">", $output);
	}

	// --------------------------------------------------------------------

	/**
	 * Display fatal error message
	 *
	 * @access	public
	 * @return	void
	 */
	function fatal_error($error_msg = '', $use_lang = TRUE)
	{
		$EE =& get_instance();
		$heading = ($use_lang == TRUE && is_object($EE->lang)) ? $EE->lang->line('error') : 'Error Message';

		$data = array(	'title' 	=> $heading,
						'heading'	=> $heading,
						'content'	=> '<p>'.$error_msg.'</p>'
					 );

		$this->show_message($data);
	}


	// --------------------------------------------------------------------

	/**
	 * System is off message
	 *
	 * @access	public
	 * @return	void
	 */
	function system_off_msg()
	{
		$EE =& get_instance();
		$query = $EE->db->query("SELECT template_data FROM exp_specialty_templates WHERE site_id = '".$EE->db->escape_str($EE->config->item('site_id'))."' AND template_name = 'offline_template'");

		$this->set_status_header(503, 'Service Temporarily Unavailable');
		@header('Retry-After: 3600');

		echo $query->row('template_data') ;
		exit;
	}

	// --------------------------------------------------------------------

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
	function show_message($data, $xhtml = TRUE)
	{
		$EE =& get_instance();

		@header("Cache-Control: no-cache, must-revalidate");
		@header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		@header("Pragma: no-cache");

		foreach (array('title', 'heading', 'content', 'redirect', 'rate', 'link') as $val)
		{
			if ( ! isset($data[$val]))
			{
				$data[$val] = '';
			}
		}

		if ( ! is_numeric($data['rate']) OR $data['rate'] == '')
		{
			$data['rate'] = $this->refresh_time;
		}

		$data['meta_refresh']	= ($data['redirect'] != '') ? "<meta http-equiv='refresh' content='".$data['rate']."; url=".$EE->security->xss_clean($data['redirect'])."'>" : '';
		$data['charset']		= $EE->config->item('output_charset');

		if (is_array($data['link']) AND count($data['link']) > 0)
		{
			$refresh_msg = ($data['redirect'] != '' AND $this->refresh_msg == TRUE) ? $EE->lang->line('click_if_no_redirect') : '';

			$ltitle = ($refresh_msg == '') ? $data['link']['1'] : $refresh_msg;

			$url = (strtolower($data['link']['0']) == 'javascript:history.go(-1)') ? $data['link']['0'] : $EE->security->xss_clean($data['link']['0']);

			$data['link'] = "<a href='".$url."'>".$ltitle."</a>";
		}

		if ($xhtml == TRUE && isset($EE->session))
		{
			$EE->load->library('typography');

			$data['content'] = $EE->typography->parse_type(stripslashes($data['content']), array('text_format' => 'xhtml'));
		}

		$EE->db->select('template_data');
		$EE->db->where('site_id', $EE->config->item('site_id'));
		$EE->db->where('template_name', 'message_template');
		$query = $EE->db->get('specialty_templates');

		$row = $query->row_array();

		foreach ($data as $key => $val)
		{
			$row['template_data']  = str_replace('{'.$key.'}', $val, $row['template_data'] );
		}

		echo  stripslashes($row['template_data'] );
		exit;
	}

	// --------------------------------------------------------------------

	/**
	 * Show user error
	 *
	 * @access	public
	 * @param	string
	 * @param	mixed
	 * @param	string
	 * @return	void
	 */
	function show_user_error($type = 'submission', $errors, $heading = '')
	{
		if (defined('REQ') && REQ == 'CP')
		{
		// Deprecation temporarily removed due to some lingering complex use cases in the CP.
		//	ee()->load->library('logger');
		//	ee()->logger->deprecated('2.6', 'show_error()');
		}

		$this->set_header("Content-Type: text/html; charset=".ee()->config->item('charset'));

		if ($type != 'off')
		{
			if ($type == 'general')
			{
				$heading = ee()->lang->line('general_error');
			}
			else
			{
				$heading = ee()->lang->line('submission_error');
			}
		}

		$content  = '<ul>';

		if ( ! is_array($errors))
		{
			$content.= "<li>".$errors."</li>\n";
		}
		else
		{
			foreach ($errors as $val)
			{
				$content.= "<li>".$val."</li>\n";
			}
		}

		$content .= "</ul>";

		$data = array(
			'title' 	=> ee()->lang->line('error'),
			'heading'	=> $heading,
			'content'	=> $content,
			'redirect'	=> '',
			'link'		=> array('JavaScript:history.go(-1)', ee()->lang->line('return_to_previous'))
		);

		$this->_restore_xid($content);
		$this->show_message($data, 0);
	}

	// --------------------------------------------------------------------

	/**
	 * Restore XID
	 *
	 * By default our error output provides a back link for the user. In
	 * a lot of cases these errors indicate that a form was not filled in
	 * correctly, so we want to allow XID reuse.
	 *
	 * Exceptions to this rule are authorization errors and invalid actions.
	 *
	 * @access	public
	 * @param	string	error response that will be shown to the user
	 * @return	void
	 */
	protected function _restore_xid($str)
	{
		$xid_reuse_exceptions = array(
			lang('not_authorized'),
			lang('unauthorized_access'),
			lang('invalid_action')
		);

		foreach ($xid_reuse_exceptions as $exception)
		{
			if (strpos($str, $exception) !== FALSE)
			{
				return;
			}
		}

		ee()->security->restore_xid();
	}

	// --------------------------------------------------------------------

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
	function send_ajax_response($msg, $error = FALSE)
	{
		$this->enable_profiler(FALSE);

		if ($error === TRUE)
		{
			$this->set_status_header(500);
		}

		$EE =& get_instance();

		if ($EE->config->item('send_headers') == 'y')
		{
			if (is_array($msg))
			{
				@header('Content-Type: application/json; charset=UTF-8');
			}
			else
			{
				@header('Content-Type: text/html; charset=UTF-8');
			}
		}

		$EE->load->library('javascript');
		exit(json_encode($msg));
	}

	// --------------------------------------------------------------------

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
	function send_cache_headers($modified, $max_age = 172800, $etag_path = NULL)
	{
		$EE =& get_instance();

		if ($EE->config->item('send_headers') == 'y')
		{
			$max_age		= (int) $max_age;
			$modified		= (int) $modified;
			$modified_since	= $EE->input->server('HTTP_IF_MODIFIED_SINCE');

			// Remove anything after the semicolon

			if ($pos = strrpos($modified_since, ';') !== FALSE)
			{
				$modified_since = substr($modified_since, 0, $pos);
			}

			// If the file is in the client cache, we'll
			// send a 304 and be done with it.

			if ($modified_since && (strtotime($modified_since) == $modified))
			{
				$this->set_status_header(304);
				exit;
			}

			// All times GMT
			$modified = gmdate('D, d M Y H:i:s', $modified).' GMT';
			$expires = gmdate('D, d M Y H:i:s', time() + $max_age).' GMT';

			$this->set_status_header(200);
			$this->set_header("Cache-Control: max-age={$max_age}, must-revalidate");
			$this->set_header('Vary: Accept-Encoding');
			$this->set_header('Last-Modified: '.$modified);
			$this->set_header('Expires: '.$expires);

			// Send a custom ETag to maintain a useful cache in
			// load-balanced environments
			if ( ! is_null($etag_path))
			{
				$this->set_header("ETag: ".md5($modified.$etag_path));
			}
		}
	}

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------
}
// END CLASS

/* End of file EE_Output.php */
/* Location: ./system/expressionengine/libraries/EE_Output.php */
