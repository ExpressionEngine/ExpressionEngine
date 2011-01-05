<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Notifications {

	/**
	 * Constructor
	 */
	function __construct()
	{
		// Get EE superobject reference
		$this->EE =& get_instance();
		
		$this->EE->load->library('api');
		$this->EE->load->library('email');
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
		$this->EE->api->instantiate('channel_structure');
		$this->EE->load->model('channel_entries_model');
		
		$e = $this->EE->channel_entries_model->get_entry($entry_id, $channel_id);
		$c = $this->EE->api_channel_structure->get_channel_info($channel_id);
		
		$swap = array(
						'name'				=> $this->EE->session->userdata('screen_name'),
						'email'				=> $this->EE->session->userdata('email'),
						'channel_name'		=> $c->row('channel_title'),
						'entry_title'		=> $e->row('title'),
						'entry_url'			=> $this->EE->functions->remove_double_slashes($c->row('channel_url').'/'.$e->row('url_title')),
						'comment_url'		=> $this->EE->functions->remove_double_slashes($c->row('comment_url').'/'.$e->row('url_title'))
		);
		
		$template = $this->EE->functions->fetch_email_template('admin_notify_entry');
		$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
		$email_msg = $this->EE->functions->var_swap($template['data'], $swap);


		// We don't want to send a notification to the user
		// triggering the event

		if (strpos($notify_address, $this->EE->session->userdata('email')) !== FALSE)
		{
			$notify_address = str_replace($this->EE->session->userdata('email'), "", $notify_address);
		}

		$this->EE->load->helper('string');
		$notify_address = reduce_multiples($notify_address, ',', TRUE);

		if ($notify_address != '')
		{
			//	Send email
			$this->EE->load->library('email');

			foreach (explode(',', $notify_address) as $addy)
			{
				$this->EE->email->EE_initialize();
				$this->EE->email->wordwrap = false;
				$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
				$this->EE->email->to($addy);
				$this->EE->email->reply_to($this->EE->config->item('webmaster_email'));
				$this->EE->email->subject($email_tit);
				$this->EE->email->message(entities_to_ascii($email_msg));
				$this->EE->email->send();
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
		$this->EE->load->library('email');
		$this->EE->load->helper('text');

		$subject = $this->EE->lang->line('checksum_email_subject');
		$message = $this->EE->lang->line('checksum_email_message');
		
		$message = str_replace(
			array('{url}', '{changed}'),
			array($this->EE->config->item('base_url'), implode("\n", $changed)),
			$message
		);
		
		$this->EE->email->EE_initialize();
		$this->EE->email->wordwrap = false;
		$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
		$this->EE->email->to($this->EE->config->item('webmaster_email'));
		$this->EE->email->reply_to($this->EE->config->item('webmaster_email'));
		$this->EE->email->subject($subject);
		$this->EE->email->message(entities_to_ascii($message));
		$this->EE->email->send();
	}
}

// END Notifications class


/* End of file Notifications.php */
/* Location: ./system/expressionengine/libraries/Notifications.php */