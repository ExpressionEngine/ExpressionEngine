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
 * ExpressionEngine Email Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Update File
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Email {

	var $email_time_interval = '20'; // In seconds
	var $email_max_emails = '50'; // Total recipients, not emails

	var $use_captchas = 'n';

	private $_user_recipients = '';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		if (ee()->config->item('email_module_captchas') === FALSE OR 
			ee()->config->item('email_module_captchas') == 'n')
		{
			$this->use_captchas = 'n';
		}
		elseif (ee()->config->item('email_module_captchas') == 'y')
		{
			$this->use_captchas = (ee()->config->item('captcha_require_members') == 'y'  OR
								  (ee()->config->item('captcha_require_members') == 'n' AND ee()->session->userdata('member_id') == 0)) ? 'y' : 'n';
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Contact Form
	 */
	public function contact_form()
	{
		$tagdata = ee()->TMPL->tagdata;

		// Recipient Email Checking
		$user_recipients = ee()->TMPL->fetch_param('user_recipients', 'no');

		// Backwards compatible with previously documented "true/false" parameters (now "yes/no")
		$this->_user_recipients = ($user_recipients == 'true' OR $user_recipients == 'yes') ? 'yes' : 'no'; 

		$recipients = ee()->TMPL->fetch_param('recipients', '');
		$channel = ee()->TMPL->fetch_param('channel', '');

		// No email left behind act
		if ($this->_user_recipients == 'no' && $recipients == '')
		{
			$recipients = ee()->config->item('webmaster_email');
		}

		// Clean and check recipient emails, if any
		if ($recipients != '')
		{
			$array = $this->validate_recipients($recipients);

			// Put together into string again
			$recipients = implode(',',$array['approved']);
		}

		// Conditionals
		$cond = array();
		$cond['logged_in']	= (ee()->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
		$cond['logged_out']	= (ee()->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
		$cond['captcha']	= ($this->use_captchas == 'y') ? 'TRUE' : 'FALSE';

		$tagdata = ee()->functions->prep_conditionals($tagdata, $cond);

		// Load the form helper
		ee()->load->helper('form');

		// Parse "single" variables
		foreach (ee()->TMPL->var_single as $key => $val)
		{
			// parse {member_name}
			if ($key == 'member_name')
			{
				$name = (ee()->session->userdata['screen_name'] != '') ? ee()->session->userdata['screen_name'] : ee()->session->userdata['username'];
				$tagdata = ee()->TMPL->swap_var_single($key, form_prep($name), $tagdata);
			}

			// {member_email}
			if ($key == 'member_email')
			{
				$email = (ee()->session->userdata['email'] == '') ? '' : ee()->session->userdata['email'];
				$tagdata = ee()->TMPL->swap_var_single($key, form_prep($email), $tagdata);
			}

			// {current_time}

			if (strncmp($key, 'current_time', 12) == 0)
			{
				$tagdata = ee()->TMPL->swap_var_single($key, ee()->localize->format_date($val), $tagdata);
			}

			if (($key == 'author_email' OR $key == 'author_name') && ! isset($$key))
			{
				if (ee()->uri->query_string != '')
				{
					$entry_id = ee()->uri->query_string;

					if ($channel != '')
					{
						ee()->db->join('channels c', 'c.channel_id = ct.channel_id', 'left');
						ee()->functions->ar_andor_string($channel, 'c.channel_name');
					}

					$table = ( ! is_numeric($entry_id)) ? 'ct.url_title' : 'ct.entry_id';

					$query = ee()->db->select('m.username, m.email, m.screen_name')
										  ->from(array('channel_titles ct', 'members m'))
										  ->where('m.member_id = ct.author_id', '', FALSE)
										  ->where($table, $entry_id)
										  ->get();

					if ($query->num_rows() == 0)
					{
						$author_name = '';
					}
					else
					{
						$author_name = ($query->row('screen_name')  != '') ? $query->row('screen_name')  : $query->row('username') ;
					}

					$author_email = ($query->num_rows() == 0) ? '' : $query->row('email') ;
				}
				else
				{
					$author_email = '';
					$author_name = '';
				}

				// Do them both now and save ourselves a query
				$tagdata = ee()->TMPL->swap_var_single('author_email', $author_email, $tagdata);
				$tagdata = ee()->TMPL->swap_var_single('author_name', $author_name, $tagdata);
			}
		}

		// Create form
		return $this->_setup_form($tagdata, $recipients, 'contact_form', FALSE);
	}

	// --------------------------------------------------------------------

	/**
	 * Tell a friend form
	 *
	 * {exp:email:tell_a_friend charset="utf-8" allow_html='n'}
	 * {exp:email:tell_a_friend charset="utf-8" allow_html='<p>,<a>' recipients='sales@ellislab.com'}
	 * {member_email}, {member_name}, {current_time format="%Y %d %m"}
	 */
	public function tell_a_friend()
	{
		if (ee()->uri->query_string == '')
		{
			return FALSE;
		}

		// Recipient Email Checking
		$this->_user_recipients = 'yes';  // By default

		$recipients	= ee()->TMPL->fetch_param('recipients', '');
		$charset	= ee()->TMPL->fetch_param('charset', '');
		$allow_html	= ee()->TMPL->fetch_param('allow_html');

		// Equalize $allow_html value
		$allow_html = (is_string($allow_html) AND in_array($allow_html, array('yes', 'y', 'true'))) ? TRUE : $allow_html;
		$allow_html = (is_string($allow_html) AND in_array($allow_html, array('no', 'n', 'false'))) ? FALSE : $allow_html;

		if ( ! ee()->TMPL->fetch_param('status'))
		{
			ee()->TMPL->tagparams['status'] = 'open';
		}

		// Clean and check recipient emails, if any
		if ($recipients != '')
		{
			$array = $this->validate_recipients($recipients);

			// Put together into string again
			$recipients = implode(',',$array['approved']);
		}

		// Parse page number
		// We need to strip the page number from the URL for two reasons:
		// 1. So we can create pagination links
		// 2. So it won't confuse the query with an improper proper ID

		// Conditionally get query string based on whether or not the page
		// was accessed through the Pages module
		$qstring = (empty(ee()->uri->page_query_string)) ? ee()->uri->query_string : ee()->uri->page_query_string;

		if (preg_match("#/P(\d+)#", $qstring, $match))
		{
			$current_page = $match['1'];

			$qstring = reduce_double_slashes(str_replace($match[0], '', $qstring));
		}

		// Remove "N"
		// The recent comments feature uses "N" as the URL indicator
		// It needs to be removed if presenst
		if (preg_match("#/N(\d+)#", $qstring, $match))
		{
			$qstring = reduce_double_slashes(str_replace($match[0], '', $qstring));
		}

		/* -------------------------------------
		/*  'email_module_tellafriend_override' hook.
		/*  - Allow use of Tell-A-Friend for things besides channel entries
		/*  - Added EE 1.5.1
		*/
			if (ee()->extensions->active_hook('email_module_tellafriend_override') === TRUE)
			{
				$tagdata = ee()->extensions->call('email_module_tellafriend_override', $qstring, $this);
				if (ee()->extensions->end_script === TRUE) return $tagdata;
			}
			else // Else Do the Default Channel Processing
			{
				$entry_id = trim($qstring);

				// If there is a slash in the entry ID we'll kill everything after it.
				$entry_id = preg_replace("#/.+#", "", $entry_id);

				//  Do we have a vaild channel and ID number?
				$timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;
				$channel = ee()->TMPL->fetch_param('channel', '');

				ee()->db->select('entry_id')
					 ->from(array('channel_titles ct', 'channels c'))
					 ->where('ct.channel_id = c.channel_id', '', FALSE)
					 ->where('(ct.expiration_date = 0 OR expiration_date > '.$timestamp.')', '', FALSE)
					 ->where('ct.status !=', 'closed');
				
				$table = ( ! is_numeric($entry_id)) ? 'ct.url_title' : 'ct.entry_id';

				ee()->db->where($table, $entry_id);

				if ($channel != '')
				{
					ee()->functions->ar_andor_string($channel, 'c.channel_name');
				}

				$query = ee()->db->get();

				// Bad ID?  See ya!
				if ($query->num_rows() === 0)
				{
					return FALSE;
				}

				// Fetch the channel entry
				if ( ! class_exists('Channel'))
				{
					require PATH_MOD.'channel/mod.channel.php';
				}

				$channel = new Channel;

				$channel->fetch_custom_channel_fields();
				$channel->fetch_custom_member_fields();
				$channel->build_sql_query();

				if ($channel->sql == '')
				{
					return FALSE;
				}

				$channel->query = ee()->db->query($channel->sql);

				if ($channel->query->num_rows() === 0)
				{
					return FALSE;
				}

				ee()->load->library('typography');
				ee()->typography->initialize(array(
					'encode_email'	=> FALSE,
					'convert_curly'	=> FALSE
				));

				$channel->fetch_categories();
				$channel->parse_channel_entries();
				$tagdata = $channel->return_data;

			}
		/*
		/* -------------------------------------*/

		// Parse conditionals
		$cond = array();
		$cond['captcha'] = ($this->use_captchas == 'y') ? 'TRUE' : 'FALSE';

		$tagdata = ee()->functions->prep_conditionals($tagdata, $cond);

		// Parse tell-a-friend variables

		// {member_name}
		$tagdata = ee()->TMPL->swap_var_single('member_name', ee()->session->userdata['screen_name'], $tagdata);

		// {member_email}
		$tagdata = ee()->TMPL->swap_var_single('member_email', ee()->session->userdata['email'], $tagdata);

		// A little work on the form field's values

		// Match values in input fields
		$tagdata = $this->_strip_field_html(
			$tagdata,
			"/<input(.*?)value=\"(.*?)\"/",
			$allow_html
		);

		// Remove line breaks
		$LB = 'snookums9loves4wookie';
		$tagdata = str_replace(array("\r\n", "\r", "\n"), $LB, $tagdata);

		// Match textarea content
		$tagdata = $this->_strip_field_html(
			$tagdata,
			"/<textarea(.*?)>(.*?)<\/textarea>/",
			$allow_html
		);

		$tagdata = str_replace($LB, "\n", $tagdata);

		$allow = ($allow_html !== FALSE) ? TRUE : FALSE;
		
		return $this->_setup_form($tagdata, $recipients, 'tellafriend_form', $allow);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Strips fields of HTML based on $allow_html
	 * 
	 * @param string $template Template string to parse
	 * @param string $field_regex Regular expression for the form field to 
	 * 		search for
	 * @param bool|string $allow_html Either boolean if completely allowing or
	 * 		disallowing html or a comma delimited string of html elements to 
	 * 		explicitly allow
	 * 
	 * @return string $template with html parsed out of it
	 */
	private function _strip_field_html($template, $field_regex, $allow_html)
	{
		// Make sure allow_html isn't true first, then run preg_match_all
		if ($allow_html !== TRUE 
			AND preg_match_all($field_regex, $template, $matches))
		{
			foreach($matches['2'] as $value)
			{
				if ($allow_html === FALSE)
			 	{
			 		$new = strip_tags($value);
			 	}
			 	else
			 	{
			 		$new = strip_tags($value, $allow_html);
			 	}
			 
			 	$template = str_replace($value, $new, $template);
			}
		}
		
		return $template;
	}
	
	// -------------------------------------------------------------------------

	/**
	 * Send Email
	 */
	public function send_email()
	{
		$error = array();

		// Blacklist/Whitelist Check
		if (ee()->blacklist->blacklisted == 'y' && ee()->blacklist->whitelisted == 'n')
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Is the nation of the user banend?
		ee()->session->nation_ban_check();
		  
		// Check and Set
		$default = array(
			'subject', 'message', 'from', 'user_recipients', 'to', 
			'recipients', 'name', 'required'
		);

		foreach ($default as $val)
		{
			if ( ! isset($_POST[$val]))
			{
				$_POST[$val] = '';
			}
			else
			{
				if (is_array($_POST[$val]) && ($val == 'message' OR $val == 'required'))
				{
					$temp = '';
					foreach($_POST[$val] as $post_value)
					{
						$temp .= ee()->input->_clean_input_data($post_value)."\n";
					}

					$_POST[$val] = $temp;
				}

				if ($val == 'recipients')
				{
					if ( function_exists('mcrypt_encrypt') )
					{
						$init_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
						$init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);

						$decoded_recipients = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(ee()->db->username.ee()->db->password), base64_decode($_POST[$val]), MCRYPT_MODE_ECB, $init_vect), "\0");
					}
					else
					{
						$raw = base64_decode($_POST[$val]);

						$hash = substr($raw, -32);
						$decoded_recipients = substr($raw, 0, -32);

						if ($hash != md5(ee()->db->username.ee()->db->password.$decoded_recipients))
						{
							$decoded_recipients = '';
						}
					}

					$_POST[$val] = $decoded_recipients;
				}

				$_POST[$val] = ee()->security->xss_clean(trim(stripslashes($_POST[$val])));
			}
		}

		// Clean incoming
		$clean = array('subject', 'from', 'user_recipients', 'to', 'recipients', 'name');

		foreach ($clean as $val)
		{
			$_POST[$val] = strip_tags($_POST[$val]);
		}

		ee()->lang->loadfile('email');

		// Basic Security Check
		if (ee()->session->userdata('ip_address') == '' OR 
			ee()->session->userdata('user_agent') == '')
		{
			return ee()->output->show_user_error('general', array(lang('em_unauthorized_request')));
		}

		// Return Variables
		$x = explode('|',$_POST['RET']);
		unset($_POST['RET']);
		
		if (is_numeric($x['0']))
		{
			$return_link = ee()->functions->form_backtrack($x['0']);
		}
		else
		{
			$return_link = $x[0];

			if ($x[0] == '' OR ! preg_match('{^http(s)?:\/\/}i', $x[0]))
			{
				$return_link = ee()->functions->form_backtrack(1);
			}
		}

		$site_name = (ee()->config->item('site_name') == '') ? lang('back') : stripslashes(ee()->config->item('site_name'));

		$return_name = ( ! isset($x['1']) OR $x['1'] == '') ? $site_name : $x['1'];

		// ERROR Checking
		// If the message is empty, bounce them back
		if ($_POST['message'] == '')
		{
			return ee()->output->show_user_error('general', array(lang('message_required')));
		}

		// If the from field is empty, error
		ee()->load->helper('email');

		if ($_POST['from'] == '' OR ! valid_email($_POST['from']))
		{
			return ee()->output->show_user_error('general', array(lang('em_sender_required')));
		}

		// If no recipients, bounce them back

		if ($_POST['recipients'] == '' && $_POST['to'] == '')
		{
			return ee()->output->show_user_error('general', array(lang('em_no_valid_recipients')));
		}

		// Is the user banned?
		if (ee()->session->userdata['is_banned'] == TRUE)
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Check Form Hash
		if ( ! ee()->security->secure_forms_check(ee()->input->post('XID')))
		{
			return ee()->output->show_user_error('general', array(lang('not_authorized')));
		}

		// Check Tracking Class
		$day_ago = ee()->localize->now - 60*60*24;
		$query = ee()->db->query("DELETE FROM exp_email_tracker WHERE email_date < '{$day_ago}'");

		if (ee()->session->userdata['username'] === false OR ee()->session->userdata['username'] == '')
		{
			$query = ee()->db->query("SELECT *
				FROM exp_email_tracker
				WHERE sender_ip = '".ee()->input->ip_address()."'
				ORDER BY email_date DESC");
		}
		else
		{
			$query = ee()->db->query("SELECT *
				FROM exp_email_tracker
				WHERE sender_username = '".ee()->db->escape_str(ee()->session->userdata['username'])."'
				OR sender_ip = '".ee()->input->ip_address()."'
				ORDER BY email_date DESC");
		}

		if ($query->num_rows() > 0)
		{
			// Max Emails - Quick check
			if ($query->num_rows() >= $this->email_max_emails)
			{
				return ee()->output->show_user_error('general', array(lang('em_limit_exceeded')));
			}

			// Max Emails - Indepth check
			$total_sent = 0;

			foreach($query->result_array() as $row)
			{
				$total_sent = $total_sent + $row['number_recipients'];
			}

			if ($total_sent >= $this->email_max_emails)
			{
				return ee()->output->show_user_error('general', array(lang('em_limit_exceeded')));
			}

			// Interval check
			if ($query->row('email_date')  > (ee()->localize->now - $this->email_time_interval))
			{
				$error[] = str_replace("%s", $this->email_time_interval, lang('em_interval_warning'));
				return ee()->output->show_user_error('general', $error);
			}
		}

		// Review Recipients
		$_POST['user_recipients'] = ($_POST['user_recipients'] == md5(ee()->db->username.ee()->db->password.'y')) ? 'y' : 'n';

		if ($_POST['user_recipients'] == 'y' && trim($_POST['to']) != '')
		{
			$array = $this->validate_recipients($_POST['to']);

			$error = array_merge($error, $array['error']);
			$approved_tos = $array['approved'];
		}
		else
		{
			$approved_tos = array();
		}

		if (trim($_POST['recipients']) != '')
		{
			$array = $this->validate_recipients($_POST['recipients']);
			$approved_recipients = $array['approved'];
		}
		else
		{
			$approved_recipients = array();
		}

		// If we have no valid emails to send, back they go.
		if ($_POST['user_recipients'] == 'y' && count($approved_tos) == 0)
		{
			$error[] = lang('em_no_valid_recipients');
		}
		elseif ( count($approved_recipients) == 0 && count($approved_tos) == 0)
		{
			$error[] = lang('em_no_valid_recipients');
		}

		// Is from email banned?
		if (ee()->session->ban_check('email', $_POST['from']))
		{
			$error[] = lang('em_banned_from_email');
		}

		// Do we have errors to display?
		if (count($error) > 0)
		{
			return ee()->output->show_user_error('submission', $error);
		}

		// Check CAPTCHA
		if ($this->use_captchas == 'y')
		{
			if ( ! isset($_POST['captcha']) OR $_POST['captcha'] == '')
			{
				return ee()->output->show_user_error('general', array(lang('captcha_required')));
			}

			$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_captcha
				WHERE word='".ee()->db->escape_str($_POST['captcha'])."'
				AND ip_address = '".ee()->input->ip_address()."'
				AND date > UNIX_TIMESTAMP()-7200");

			if ($query->row('count')  == 0)
			{
				return ee()->output->show_user_error('submission', array(lang('captcha_incorrect')));
			}

			ee()->db->query("DELETE FROM exp_captcha
				WHERE (word='".ee()->db->escape_str($_POST['captcha'])."'
				AND ip_address = '".ee()->input->ip_address()."')
				OR date < UNIX_TIMESTAMP()-7200");
		}

		// Censored Word Checking
		ee()->load->library('typography');
		ee()->typography->initialize();

		// Load the text helper
		ee()->load->helper('text');

		$subject = entities_to_ascii($_POST['subject']);
		$subject = ee()->typography->filter_censored_words($subject);

		$message = ($_POST['required'] != '') ? $_POST['required']."\n".$_POST['message'] : $_POST['message'];
		$message = ee()->security->xss_clean($message);

		if (isset($_POST['allow_html']) && $_POST['allow_html'] == 'y' && 
			strlen(strip_tags($message)) != strlen($message))
		{
			$mail_type = 'html';
		}
		else
		{
			$mail_type = 'plain';
		}

		$message = entities_to_ascii($message);
		$message = ee()->typography->filter_censored_words($message);

		// Send email
		ee()->load->library('email');
		ee()->email->wordwrap = true;
		ee()->email->mailtype = $mail_type;
		ee()->email->priority = '3';

		if (isset($_POST['charset']) && $_POST['charset'] != '')
		{
			ee()->email->charset = $_POST['charset'];
		}

		if ( count($approved_recipients) == 0 && count($approved_tos) > 0) // No Hidden Recipients
		{
			foreach ($approved_tos as $val)
			{
				ee()->email->EE_initialize();
				ee()->email->to($val);

				if (isset($_POST['replyto']) && $_POST['replyto'] == 'yes')
				{
					ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
					ee()->email->reply_to($_POST['from'], $_POST['name']);
				}
				else
				{
					ee()->email->from($_POST['from'],$_POST['name']);
				}

				ee()->email->subject($subject);
				ee()->email->message($message);
				ee()->email->send();
			}
		}
		elseif ( count($approved_recipients) > 0 && count($approved_tos) == 0) // Hidden Recipients Only
		{
			foreach ($approved_recipients as $val)
			{
				ee()->email->EE_initialize();
				ee()->email->to($val);

				if (isset($_POST['replyto']) && $_POST['replyto'] == 'yes')
				{
					ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
					ee()->email->reply_to($_POST['from'], $_POST['name']);
				}
				else
				{
					ee()->email->from($_POST['from'],$_POST['name']);
				}

				ee()->email->subject($subject);
				ee()->email->message($message);
				ee()->email->send();
			}
		}
		else // Combination of Hidden and Regular Recipients, BCC hidden on every regular recipient email
		{
			foreach ($approved_tos as $val)
			{
				ee()->email->EE_initialize();
				ee()->email->to($val);
				ee()->email->bcc(implode(',', $approved_recipients));

				if (isset($_POST['replyto']) && $_POST['replyto'] == 'yes')
				{
					ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
					ee()->email->reply_to($_POST['from'], $_POST['name']);
				}
				else
				{
					ee()->email->from($_POST['from'], $_POST['name']);
				}

				ee()->email->subject($subject);
				ee()->email->message($message);
				ee()->email->send();
			}
		}


		// Store in tracking class
		$data = array(
			'email_date'		=> ee()->localize->now,
			'sender_ip'			=> ee()->input->ip_address(),
			'sender_email'		=> $_POST['from'],
			'sender_username'	=> ee()->session->userdata['username'],
			'number_recipients'	=> count($approved_tos) + count($approved_recipients)
		);

		ee()->db->query(ee()->db->insert_string('exp_email_tracker', $data));

		/* -------------------------------------
		/*  'email_module_send_email_end' hook.
		/*  - After emails are sent, do some additional processing
		/*  - Added EE 1.5.1
		*/
			if (ee()->extensions->active_hook('email_module_send_email_end') === TRUE)
			{
				ee()->extensions->call('email_module_send_email_end', $subject, $message, $approved_tos, $approved_recipients);
				if (ee()->extensions->end_script === TRUE) return;
			}
		/*
		/* -------------------------------------*/

		// Thank you message
		$data = array(
			'title' 	=> lang('email_module_name'),
			'heading'	=> lang('thank_you'),
			'content'	=> lang('em_email_sent'),
			'redirect'	=> $return_link,
			'link'		=> array($return_link, $return_name)
		);

		if (ee()->input->get_post('redirect') !== FALSE)
		{
			if(is_numeric(ee()->input->get_post('redirect')))
			{
				$data['rate'] = ee()->input->get_post('redirect');
			}
			elseif(ee()->input->get_post('redirect') == 'none')
			{
				$data['redirect'] = '';
			}
		}

		ee()->output->show_message($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Validate List of Emails
	 */
	function validate_recipients($str)
	{
		// Remove white space and replace with comma
		$recipients = preg_replace("/\s*(\S+)\s*/", "\\1,", $str);

		// Remove any existing doubles
		$recipients = str_replace(",,", ",", $recipients);

		// Remove any comma at the end
		if (substr($recipients, -1) == ",")
		{
			$recipients = substr($recipients, 0, -1);
		}

		// Break into an array via commas and remove duplicates
		$emails = preg_split('/[,]/', $recipients);
		$emails = array_unique($emails);

		// Emails to send email to...

		$error = array();
		$approved_emails = array();

		ee()->load->helper('email');

		foreach ($emails as $email)
		{
			 if (trim($email) == '') continue;

			 if (valid_email($email))
			 {
				  if ( ! ee()->session->ban_check('email', $email))
				  {
						$approved_emails[] = $email;
				  }
				  else
				  {
						$error['ban_recp'] = lang('em_banned_recipient');
				  }
			 }
			 else
			 {
			 	$error['bad_recp'] = lang('em_invalid_recipient');
			 }
		}

		return array('approved' => $approved_emails, 'error' => $error);
	}

	// --------------------------------------------------------------------

	/**
	 * Setup forms
	 *
	 * @param 	string 	$tagdata
	 * @param 	string 	$recipients
	 * @param 	string 	$form_id
	 * @param 	boolean
	 * @return 	string
	 */
	private function _setup_form($tagdata, $recipients, $form_id = NULL, $allow_html = FALSE)
	{
		$charset = ee()->TMPL->fetch_param('charset', '');

		$recipients = $this->_encrypt_recipients($recipients);

		$data = array(
			'id'			=> (ee()->TMPL->form_id == '') ? $form_id : ee()->TMPL->form_id,
			'class'			=> ee()->TMPL->form_class,
			'hidden_fields'	=> array(
				'ACT'				=> ee()->functions->fetch_action_id('Email', 'send_email'),
				'RET'				=> ee()->TMPL->fetch_param('return', ''),
				'URI'				=> (ee()->uri->uri_string == '') ? 'index' : ee()->uri->uri_string,
				'recipients'		=> base64_encode($recipients),
				'user_recipients'	=> ($this->_user_recipients == 'yes') ? md5(ee()->db->username.ee()->db->password.'y') : md5(ee()->db->username.ee()->db->password.'n'),
				'charset'			=> $charset,
				'redirect'			=> ee()->TMPL->fetch_param('redirect', ''),
				'replyto'			=> ee()->TMPL->fetch_param('replyto', '')
			)
		);
		
		if ($allow_html)
		{
			$data['hidden_fields']['allow_html'] = 'y';
		}

		$name = ee()->TMPL->fetch_param('name', FALSE);

 		if ($name && preg_match("#^[a-zA-Z0-9_\-]+$#i", $name, $match))
		{
			$data['name'] = $name;
		}

		$res  = ee()->functions->form_declaration($data);
		$res .= stripslashes($tagdata);
		$res .= "</form>";//echo $res; exit;
		return $res;
	}

	// --------------------------------------------------------------------

	/**
	 * Encrypt Recipients list
	 */
	private function _encrypt_recipients($recipients)
	{
		if (function_exists('mcrypt_encrypt'))
		{
			$init_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
			$init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);

			return mcrypt_encrypt(
				MCRYPT_RIJNDAEL_256,
				md5(ee()->db->username.ee()->db->password),
				$recipients,
				MCRYPT_MODE_ECB,
				$init_vect
			);
		}

		return $recipients.md5(ee()->db->username.ee()->db->password.$recipients);
	}	
}
// END CLASS

/* End of file mod.email.php */
/* Location: ./system/expressionengine/modules/email/mod.email.php */