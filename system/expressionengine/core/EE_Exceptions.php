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
 * ExpressionEngine Exceptions Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Exceptions extends CI_Exceptions {

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

		$EE =& get_instance();

		// let's be kind if it's a submission error, and offer a back link
		if ( ! empty($_POST) && ! AJAX_REQUEST)
		{
			$message .= '<p><a href="javascript:history.go(-1);">&#171; '.$EE->lang->line('back').'</a></p>';
		}

		// Ajax Requests get a reasonable response
		if (defined('AJAX_REQUEST') && AJAX_REQUEST)
		{
			$EE->output->send_ajax_response(array(
				'error'	=> $message
			));
		}

		// CP requests get no change in treatment
		// nor do errors that occur in code prior to template parsing
		// since the db, typography, etc. aren't available yet
		if ( ! defined('REQ') OR REQ == 'CP' OR ( ! isset($EE->TMPL)))
		{
			if (ob_get_level() > $this->ob_level + 1)
			{
				ob_end_flush();
			}

			ob_start();

			if (isset($EE->session->userdata))
			{
				$cp_theme = ( ! $EE->session->userdata('cp_theme')) ? $EE->config->item('cp_theme') : $EE->session->userdata('cp_theme');

				if (defined('PATH_THEMES') && (file_exists(PATH_THEMES.'cp_themes/'.$cp_theme.'/errors/'.$template.'.php')))
				{
					include(PATH_THEMES.'cp_themes/'.$cp_theme.'/errors/'.$template.'.php');
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
		$EE->output->fatal_error($message);
	}

	// --------------------------------------------------------------------

}
// END Exceptions Class

/* End of file EE_Exceptions.php */
/* Location: ./system/expressionengine/libraries/EE_Exceptions.php */
