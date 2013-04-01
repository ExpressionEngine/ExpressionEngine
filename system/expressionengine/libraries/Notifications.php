<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * ExpressionEngine Core Notifications Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Notifications {

	/**
	 * Constructor
	 */
	function __construct()
	{
		// Get EE superobject reference
		$this->EE =& get_instance();
		
		ee()->load->library('api');
		ee()->load->library('email');
	}
	
	// --------------------------------------------------------------------

	/**
	 * Send admin notification
	 *
	 * Sends an admin notification email
	 *
	 * @access	public
	 * @param	string
	 * @param	int
	 * @param	int
	 */
	function send_admin_notification($notify_address, $channel_id, $entry_id)
	{
		ee()->api->instantiate('channel_structure');
		ee()->load->model('channel_entries_model');
		
		$e = ee()->channel_entries_model->get_entry($entry_id, $channel_id);
		$c = ee()->api_channel_structure->get_channel_info($channel_id);
		
		$swap = array(
						'name'				=> ee()->session->userdata('screen_name'),
						'email'				=> ee()->session->userdata('email'),
						'channel_name'		=> $c->row('channel_title'),
						'entry_title'		=> $e->row('title'),
						'entry_url'			=> reduce_double_slashes($c->row('channel_url').'/'.$e->row('url_title')),
						'comment_url'		=> reduce_double_slashes($c->row('comment_url').'/'.$e->row('url_title'))
		);
		
		$template = ee()->functions->fetch_email_template('admin_notify_entry');
		$email_tit = ee()->functions->var_swap($template['title'], $swap);
		$email_msg = ee()->functions->var_swap($template['data'], $swap);


		// We don't want to send a notification to the user
		// triggering the event

		if (strpos($notify_address, ee()->session->userdata('email')) !== FALSE)
		{
			$notify_address = str_replace(ee()->session->userdata('email'), "", $notify_address);
		}

		$notify_address = reduce_multiples($notify_address, ',', TRUE);

		if ($notify_address != '')
		{
			//	Send email
			ee()->load->library('email');

			foreach (explode(',', $notify_address) as $addy)
			{
				ee()->email->EE_initialize();
				ee()->email->wordwrap = false;
				ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
				ee()->email->to($addy);
				ee()->email->reply_to(ee()->config->item('webmaster_email'));
				ee()->email->subject($email_tit);
				ee()->email->message(entities_to_ascii($email_msg));
				ee()->email->send();
			}
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Send checksum notification
	 *
	 * Sends a notification email to the webmaster if a bootstrap file
	 * was changed.
	 *
	 * @access	public
	 * @param	string
	 * @param	int
	 * @param	int
	 */
	function send_checksum_notification($changed)
	{
		//	Send email
		ee()->load->library('email');
		ee()->load->helper('text');

		$subject = ee()->lang->line('checksum_email_subject');
		$message = ee()->lang->line('checksum_email_message');
		
		$message = str_replace(
			array('{url}', '{changed}'),
			array(ee()->config->item('base_url'), implode("\n", $changed)),
			$message
		);
		
		ee()->email->EE_initialize();
		ee()->email->wordwrap = false;
		ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
		ee()->email->to(ee()->config->item('webmaster_email'));
		ee()->email->reply_to(ee()->config->item('webmaster_email'));
		ee()->email->subject($subject);
		ee()->email->message(entities_to_ascii($message));
		ee()->email->send();
	}
}

// END Notifications class


/* End of file Notifications.php */
/* Location: ./system/expressionengine/libraries/Notifications.php */