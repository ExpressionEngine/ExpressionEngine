<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Output extends CI_Output {

	var $out_type		= 'webpage';
	var $refresh_msg	= TRUE;			// TRUE/FALSE - whether to show the "You will be redirected in 5 seconds" message.
	var $refresh_time	= 1;			// Number of seconds for redirects
	
	var $remove_unparsed_variables = TRUE; // whether to remove left-over variables that had bad syntax
	
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
		}


		// Parse elapsed time and query count
			
		if (REQ != 'CP')
		{
			$output = str_replace(LD.'total_queries'.RD, $EE->db->query_count, $output);		


			// If 'debug' is turned off, we will remove any variables that didn't get parsed due to syntax errors.
	
			if ($EE->config->item('debug') == 0 AND $this->remove_unparsed_variables == TRUE)
			{
				$output = preg_replace("/".LD."[^;\n]+?".RD."/", '', $output);
			}
		}
		
		// Compress the output
		// We simply set the ci config value to true
		
		if ($EE->config->item('gzip_output') == 'y' AND REQ == 'PAGE')
		{
			$EE->config->set_item('compress_output', TRUE);
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
			$last_update = $EE->localize->set_gmt();					
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
		$EE =& get_instance();
		
		if ($type != 'off')
		{	  
			switch($type)
			{
				case 'submission' : $heading = $EE->lang->line('submission_error');
					break;
				case 'general'	: $heading = $EE->lang->line('general_error');
					break;
				default			: $heading = $EE->lang->line('submission_error');
					break;
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
		
		$data = array(	'title' 	=> $EE->lang->line('error'),
						'heading'	=> $heading,
						'content'	=> $content,
						'redirect'	=> '',
						'link'		=> array('JavaScript:history.go(-1)', $EE->lang->line('return_to_previous'))
					 );
				
		$this->show_message($data, 0);
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
				@header('Content-Type: application/json');
			}
			else
			{
				@header('Content-Type: text/html; charset=UTF-8');	
			}
		}
		
		$EE->load->library('javascript');
		exit($EE->javascript->generate_json($msg, TRUE));
	}

	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file EE_Output.php */
/* Location: ./system/expressionengine/libraries/EE_Output.php */