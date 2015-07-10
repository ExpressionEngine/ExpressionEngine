<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Exceptions Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Exceptions {

	var $action;
	var $severity;
	var $message;
	var $filename;
	var $line;
	var $ob_level;

	var $levels = array(
						E_ERROR				=>	'Error',
						E_WARNING			=>	'Warning',
						E_PARSE				=>	'Parsing Error',
						E_NOTICE			=>	'Notice',
						E_CORE_ERROR		=>	'Core Error',
						E_CORE_WARNING		=>	'Core Warning',
						E_COMPILE_ERROR		=>	'Compile Error',
						E_COMPILE_WARNING	=>	'Compile Warning',
						E_USER_ERROR		=>	'User Error',
						E_USER_WARNING		=>	'User Warning',
						E_USER_NOTICE		=>	'User Notice',
						E_STRICT			=>	'Runtime Notice'
					);


	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->ob_level = ob_get_level();
		// Note:  Do not log messages from this constructor.
	}

	// --------------------------------------------------------------------

	/**
	 * Exception Logger
	 *
	 * This function logs PHP generated error messages
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	function log_exception($severity, $message, $filepath, $line)
	{
		$severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

		log_message('error', 'Severity: '.$severity.'  --> '.$message. ' '.$filepath.' '.$line, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * 404 Page Not Found Handler
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	function show_404($page = '', $log_error = TRUE)
	{
		$heading = "404 Page Not Found";
		$message = "The page you requested was not found.";

		// By default we log this, but allow a dev to skip it
		if ($log_error)
		{
			log_message('error', '404 Page Not Found --> '.$page);
		}

		echo $this->show_error($heading, $message, 'error_404', 404);
		exit;
	}

	// --------------------------------------------------------------------

	/**
	 * Native PHP error handler
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	function show_php_error($severity, $message, $filepath, $line)
	{
		$severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

		$filepath = str_replace("\\", "/", $filepath);

		// For safety reasons we do not show the full file path
		if (FALSE !== strpos($filepath, '/'))
		{
			$x = explode('/', $filepath);
			$filepath = $x[count($x)-2].'/'.end($x);
		}

		if (ob_get_level() > $this->ob_level + 1)
		{
			ob_end_flush();
		}
		ob_start();
		include(APPPATH.'errors/error_php.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
	}

	// --------------------------------------------------------------------

	/**
	 * Show Error
	 *
	 * Take over CI's Error template to use the EE user error template
	 *
	 * @access	public
	 * @param	string	the heading
	 * @param	string	the message
	 * @param	string	the template
	 * @return	string
	 */
	function show_error($heading, $message, $template = 'error_general', $status_code = 500)
	{
		set_status_header($status_code);

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

		// CP requests get no change in treatment
		// nor do errors that occur in code prior to template parsing
		// since the db, typography, etc. aren't available yet
		if ( ! defined('REQ') OR REQ == 'CP' OR ( ! isset(ee()->TMPL)))
		{
			if (ob_get_level() > $this->ob_level + 1)
			{
				ob_end_flush();
			}

			ob_start();

			if (isset(ee()->session) && isset(ee()->session->userdata))
			{
				if (defined('PATH_CP_THEME') &&
					(file_exists(PATH_CP_THEME.'/views/errors/'.$template.'.php')))
				{
					include(PATH_CP_THEME.'/views/errors/'.$template.'.php');
				}
				else
				{
					include(APPPATH.'errors/'.$template.'.php');
				}
			}
			else
			{
				include(APPPATH.'errors/'.$template.'.php');
			}

			$buffer = ob_get_contents();
			ob_end_clean();
			return $buffer;
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
	}

	// --------------------------------------------------------------------

}
// END Exceptions Class

/* End of file EE_Exceptions.php */
/* Location: ./system/expressionengine/libraries/EE_Exceptions.php */
