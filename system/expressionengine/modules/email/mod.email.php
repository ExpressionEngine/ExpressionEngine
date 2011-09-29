<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Email Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Update File
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
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

		if ($this->EE->config->item('email_module_captchas') === FALSE OR 
			$this->EE->config->item('email_module_captchas') == 'n')
		{
			$this->use_captchas = 'n';
		}
		elseif ($this->EE->config->item('email_module_captchas') == 'y')
		{
			$this->use_captchas = ($this->EE->config->item('captcha_require_members') == 'y'  OR
								  ($this->EE->config->item('captcha_require_members') == 'n' AND $this->EE->session->userdata('member_id') == 0)) ? 'y' : 'n';
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Contact Form
	 */
	public function contact_form()
	{
		$tagdata = $this->EE->TMPL->tagdata;

		// Recipient Email Checking
		$user_recipients = $this->EE->TMPL->fetch_param('user_recipients', 'no');

		// Backwards compatible with previously documented "true/false" parameters (now "yes/no")
		$this->_user_recipients = ($user_recipients == 'true' OR $user_recipients == 'yes') ? 'yes' : 'no'; 

		$recipients = $this->EE->TMPL->fetch_param('recipients', '');
		$channel = $this->EE->TMPL->fetch_param('channel', '');

		// No email left behind act
		if ($this->_user_recipients == 'no' && $recipients == '')
		{
			$recipients = $this->EE->config->item('webmaster_email');
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
		$cond['logged_in']	= ($this->EE->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
		$cond['logged_out']	= ($this->EE->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
		$cond['captcha']	= ($this->use_captchas == 'y') ? 'TRUE' : 'FALSE';

		$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);

		// Load the form helper
		$this->EE->load->helper('form');

		// Parse "single" variables
		foreach ($this->EE->TMPL->var_single as $key => $val)
		{
			// parse {member_name}
			if ($key == 'member_name')
			{
				$name = ($this->EE->session->userdata['screen_name'] != '') ? $this->EE->session->userdata['screen_name'] : $this->EE->session->userdata['username'];
				$tagdata = $this->EE->TMPL->swap_var_single($key, form_prep($name), $tagdata);
			}

			// {member_email}
			if ($key == 'member_email')
			{
				$email = ($this->EE->session->userdata['email'] == '') ? '' : $this->EE->session->userdata['email'];
				$tagdata = $this->EE->TMPL->swap_var_single($key, form_prep($email), $tagdata);
			}

			// {current_time}

			if (strncmp($key, 'current_time', 12) == 0)
			{
				$now = $this->EE->localize->set_localized_time();
				$tagdata = $this->EE->TMPL->swap_var_single($key, $this->EE->localize->decode_date($val,$now), $tagdata);
			}

			if (($key == 'author_email' OR $key == 'author_name') && ! isset($$key))
			{
				if ($this->EE->uri->query_string != '')
				{
					$entry_id = $this->EE->uri->query_string;

					if ($channel != '')
					{
						$this->EE->db->join('channels c', 'c.channel_id = ct.channel_id', 'left');
						$this->EE->functions->ar_andor_string($channel, 'c.channel_name');
					}

					$table = ( ! is_numeric($entry_id)) ? 'ct.url_title' : 'ct.entry_id';

					$query = $this->EE->db->select('m.username, m.email, m.screen_name')
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
				$tagdata = $this->EE->TMPL->swap_var_single('author_email', $author_email, $tagdata);
				$tagdata = $this->EE->TMPL->swap_var_single('author_name', $author_name, $tagdata);
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
	 *{exp:email:tell_a_friend charset="utf-8" allow_html='<p>,<a>' recipients='sales@expressionengine.com'}
	 * {member_email}, {member_name}, {current_time format="%Y %d %m"}
	 */
	public function tell_a_friend()
	{
		if ($this->EE->uri->query_string == '')
		{
			return FALSE;
		}

		// Recipient Email Checking
		$this->_user_recipients = 'true';  // By default

		$recipients	= $this->EE->TMPL->fetch_param('recipients', '');
		$charset	= $this->EE->TMPL->fetch_param('charset', '');
		$allow_html	= $this->EE->TMPL->fetch_param('allow_html', 'n');

		if ( ! $this->EE->TMPL->fetch_param('status'))
		{
			$this->EE->TMPL->tagparams['status'] = 'open';
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

		$qstring = $this->EE->uri->query_string;

		if (preg_match("#/P(\d+)#", $qstring, $match))
		{
			$current_page = $match['1'];

			$qstring = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $qstring));
		}

		// Remove "N"
		// The recent comments feature uses "N" as the URL indicator
		// It needs to be removed if presenst
		if (preg_match("#/N(\d+)#", $qstring, $match))
		{
			$qstring = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $qstring));
		}

		/* -------------------------------------
		/*  'email_module_tellafriend_override' hook.
		/*  - Allow use of Tell-A-Friend for things besides channel entries
		/*  - Added EE 1.5.1
		*/
			if ($this->EE->extensions->active_hook('email_module_tellafriend_override') === TRUE)
			{
				$tagdata = $this->EE->extensions->call('email_module_tellafriend_override', $qstring, $this);
				if ($this->EE->extensions->end_script === TRUE) return $tagdata;
			}
			else // Else Do the Default Channel Processing
			{
				$entry_id = trim($qstring);

				// If there is a slash in the entry ID we'll kill everything after it.
				$entry_id = preg_replace("#/.+#", "", $entry_id);

				//  Do we have a vaild channel and ID number?
				$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now;
				$channel = $this->EE->TMPL->fetch_param('channel', '');

				$this->EE->db->select('entry_id')
							 ->from(array('channel_titles ct', 'channels c'))
							 ->where('ct.channel_id = c.channel_id', '', FALSE)
							 ->where('(ct.expiration_date = 0 OR expiration_date > '.$timestamp.')', '', FALSE)
							 ->where('ct.status !=', 'closed');
				
				$table = ( ! is_numeric($entry_id)) ? 'ct.url_title' : 'ct.entry_id';

				$this->EE->db->where($table, $entry_id);

				if ($channel != '')
				{
					$this->EE->functions->ar_andor_string($channel, 'c.channel_name');
				}

				$query = $this->EE->db->get();

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

				$channel->query = $this->EE->db->query($channel->sql);

				if ($channel->query->num_rows() === 0)
				{
					return FALSE;
				}

				$this->EE->load->library('typography');
				$this->EE->typography->initialize(array(
								'encode_email'	=> FALSE,
								'convert_curly'	=> FALSE)
								);

				$channel->fetch_categories();
				$channel->parse_channel_entries();
				$tagdata = $channel->return_data;

			}
		/*
		/* -------------------------------------*/

		// Parse conditionals
		$cond = array();
		$cond['captcha'] = ($this->use_captchas == 'y') ? 'TRUE' : 'FALSE';

		$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);

		// Parse tell-a-friend variables

		// {member_name}
		$tagdata = $this->EE->TMPL->swap_var_single('member_name', $this->EE->session->userdata['screen_name'], $tagdata);

		// {member_email}
		$tagdata = $this->EE->TMPL->swap_var_single('member_email', $this->EE->session->userdata['email'], $tagdata);

		// A little work on the form field's values

		// Match values in input fields
		preg_match_all("/<input(.*?)value=\"(.*?)\"/", $tagdata, $matches);
		
		if (count($matches) > 0 && $allow_html != 'y')
		{
			 foreach($matches['2'] as $value)
			 {
			 	if ($allow_html == 'n')
			 	{
			 		$new = strip_tags($value);
			 	}
			 	else
			 	{
			 		$new = strip_tags($value, $allow_html);
			 	}
			 
			 	$tagdata = str_replace($value,$new, $tagdata);
			 }
		}

		// Remove line breaks
		$LB = 'snookums9loves4wookie';
		$tagdata = str_replace(array("\r\n", "\r", "\n"), $LB, $tagdata);

		// Match textarea content
		preg_match_all("/<textarea(.*?)>(.*?)<\/textarea>/", $tagdata, $matches);

		if (count($matches) > 0 && $allow_html != 'y')
		{
			foreach($matches['2'] as $value)
			{
				if ($allow_html == 'n')
			 	{
			 		$new = strip_tags($value);
			 	}
			 	else
			 	{
			 		$new = strip_tags($value, $allow_html);
			 	}
			 
			 	$tagdata = str_replace($value, $new, $tagdata);
			}
		}

		$tagdata = str_replace($LB, "\n", $tagdata);

		$recipients = $this->_encrypt_recipients($recipients);

		$allow = ($allow_html == 'y') ? TRUE : FALSE;

		return $this->_setup_form($tagdata, $recipients, 'tellafriend_form', $allow);
	}

	// --------------------------------------------------------------------

	/**
	 * Send Email
	 */
	public function send_email()
	{
		$error = array();

		// Blacklist/Whitelist Check
		if ($this->EE->blacklist->blacklisted == 'y' && $this->EE->blacklist->whitelisted == 'n')
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		// Is the nation of the user banend?
		$this->EE->session->nation_ban_check();
		  
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
						$temp .= $this->EE->input->_clean_input_data($post_value)."\n";
					}

					$_POST[$val] = $temp;
				}

				if ($val == 'recipients')
				{
					if ( function_exists('mcrypt_encrypt') )
					{
						$init_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
						$init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);

						$decoded_recipients = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($this->EE->db->username.$this->EE->db->password), base64_decode($_POST[$val]), MCRYPT_MODE_ECB, $init_vect), "\0");
					}
					else
					{
						$raw = base64_decode($_POST[$val]);

						$hash = substr($raw, -32);
						$decoded_recipients = substr($raw, 0, -32);

						if ($hash != md5($this->EE->db->username.$this->EE->db->password.$decoded_recipients))
						{
							$decoded_recipients = '';
						}
					}

					$_POST[$val] = $decoded_recipients;
				}

				$_POST[$val] = $this->EE->security->xss_clean(trim(stripslashes($_POST[$val])));
			}
		}

		// Clean incoming
		$clean = array('subject', 'from', 'user_recipients', 'to', 'recipients', 'name');

		foreach ($clean as $val)
		{
			$_POST[$val] = strip_tags($_POST[$val]);
		}

		$this->EE->lang->loadfile('email');

		// Basic Security Check
		if ($this->EE->session->userdata('ip_address') == '' OR 
			$this->EE->session->userdata('user_agent') == '')
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('em_unauthorized_request')));
		}

		// Return Variables
		$x = explode('|',$_POST['RET']);
		unset($_POST['RET']);

		if (is_numeric($x['0']))
		{
			$return_link = $this->EE->functions->form_backtrack($x['0']);
		}
		else
		{
			$return_link = $x[0];

			if ($x[0] == '' OR ! preg_match('{^http(s)?:\/\/}i', $x[0]))
			{
				$return_link = $this->EE->functions->form_backtrack(2);
			}
		}

		$site_name = ($this->EE->config->item('site_name') == '') ? $this->EE->lang->line('back') : stripslashes($this->EE->config->item('site_name'));

		$return_name = ( ! isset($x['1']) OR $x['1'] == '') ? $site_name : $x['1'];

		// ERROR Checking
		// If the message is empty, bounce them back
		if ($_POST['message'] == '')
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('message_required')));
		}

		// If the from field is empty, error
		$this->EE->load->helper('email');

		if ($_POST['from'] == '' OR ! valid_email($_POST['from']))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('em_sender_required')));
		}

		// If no recipients, bounce them back

		if ($_POST['recipients'] == '' && $_POST['to'] == '')
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('em_no_valid_recipients')));
		}

		// Is the user banned?
		if ($this->EE->session->userdata['is_banned'] == TRUE)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		// Check Form Hash
		if ( ! $this->EE->security->check_xid($this->EE->input->post('XID')))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		// Check Tracking Class
		$day_ago = $this->EE->localize->now - 60*60*24;
		$query = $this->EE->db->query("DELETE FROM exp_email_tracker WHERE email_date < '{$day_ago}'");

		if ($this->EE->session->userdata['username'] === false OR $this->EE->session->userdata['username'] == '')
		{
			$query = $this->EE->db->query("SELECT *
								FROM exp_email_tracker
								WHERE sender_ip = '".$this->EE->input->ip_address()."'
								ORDER BY email_date DESC");
		}
		else
		{
			$query = $this->EE->db->query("SELECT *
								FROM exp_email_tracker
								WHERE sender_username = '".$this->EE->db->escape_str($this->EE->session->userdata['username'])."'
								OR sender_ip = '".$this->EE->input->ip_address()."'
								ORDER BY email_date DESC");
		}

		if ($query->num_rows() > 0)
		{
			// Max Emails - Quick check
			if ($query->num_rows() >= $this->email_max_emails)
			{
				return $this->EE->output->show_user_error('general', array($this->EE->lang->line('em_limit_exceeded')));
			}

			// Max Emails - Indepth check
			$total_sent = 0;

			foreach($query->result_array() as $row)
			{
				$total_sent = $total_sent + $row['number_recipients'];
			}

			if ($total_sent >= $this->email_max_emails)
			{
				return $this->EE->output->show_user_error('general', array($this->EE->lang->line('em_limit_exceeded')));
			}

			// Interval check
			if ($query->row('email_date')  > ($this->EE->localize->now - $this->email_time_interval))
			{
				$error[] = str_replace("%s", $this->email_time_interval, $this->EE->lang->line('em_interval_warning'));
				return $this->EE->output->show_user_error('general', $error);
			}
		}

		// Review Recipients
		$_POST['user_recipients'] = ($_POST['user_recipients'] == md5($this->EE->db->username.$this->EE->db->password.'y')) ? 'y' : 'n';

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
			$error[] = $this->EE->lang->line('em_no_valid_recipients');
		}
		elseif ( count($approved_recipients) == 0 && count($approved_tos) == 0)
		{
			$error[] = $this->EE->lang->line('em_no_valid_recipients');
		}

		// Is from email banned?
		if ($this->EE->session->ban_check('email', $_POST['from']))
		{
			$error[] = $this->EE->lang->line('em_banned_from_email');
		}

		// Do we have errors to display?
		if (count($error) > 0)
		{
			return $this->EE->output->show_user_error('submission', $error);
		}

		// Check CAPTCHA
		if ($this->use_captchas == 'y')
		{
			if ( ! isset($_POST['captcha']) OR $_POST['captcha'] == '')
			{
				return $this->EE->output->show_user_error('general', array($this->EE->lang->line('captcha_required')));
			}

			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_captcha
								 WHERE word='".$this->EE->db->escape_str($_POST['captcha'])."'
								 AND ip_address = '".$this->EE->input->ip_address()."'
								 AND date > UNIX_TIMESTAMP()-7200");

			if ($query->row('count')  == 0)
			{
				return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('captcha_incorrect')));
			}

			$this->EE->db->query("DELETE FROM exp_captcha
						WHERE (word='".$this->EE->db->escape_str($_POST['captcha'])."'
						AND ip_address = '".$this->EE->input->ip_address()."')
						OR date < UNIX_TIMESTAMP()-7200");
		}

		// Censored Word Checking
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();

		// Load the text helper
		$this->EE->load->helper('text');

		$subject = entities_to_ascii($_POST['subject']);
		$subject = $this->EE->typography->filter_censored_words($subject);

		$message = ($_POST['required'] != '') ? $_POST['required']."\n".$_POST['message'] : $_POST['message'];
		$message = $this->EE->security->xss_clean($message);

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
		$message = $this->EE->typography->filter_censored_words($message);

		// Send email
		$this->EE->load->library('email');
		$this->EE->email->wordwrap = true;
		$this->EE->email->mailtype = $mail_type;
		$this->EE->email->priority = '3';

		if (isset($_POST['charset']) && $_POST['charset'] != '')
		{
			$this->EE->email->charset = $_POST['charset'];
		}

		if ( count($approved_recipients) == 0 && count($approved_tos) > 0) // No Hidden Recipients
		{
			foreach ($approved_tos as $val)
			{
				$this->EE->email->EE_initialize();
				$this->EE->email->to($val);

				if (isset($_POST['replyto']) && $_POST['replyto'] == 'yes')
				{
					$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
					$this->EE->email->reply_to($_POST['from'], $_POST['name']);
				}
				else
				{
					$this->EE->email->from($_POST['from'],$_POST['name']);
				}

				$this->EE->email->subject($subject);
				$this->EE->email->message($message);
				$this->EE->email->send();
			}
		}
		elseif ( count($approved_recipients) > 0 && count($approved_tos) == 0) // Hidden Recipients Only
		{
			foreach ($approved_recipients as $val)
			{
				$this->EE->email->EE_initialize();
				$this->EE->email->to($val);

				if (isset($_POST['replyto']) && $_POST['replyto'] == 'yes')
				{
					$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
					$this->EE->email->reply_to($_POST['from'], $_POST['name']);
				}
				else
				{
					$this->EE->email->from($_POST['from'],$_POST['name']);
				}

				$this->EE->email->subject($subject);
				$this->EE->email->message($message);
				$this->EE->email->send();
			}
		}
		else // Combination of Hidden and Regular Recipients, BCC hidden on every regular recipient email
		{
			foreach ($approved_tos as $val)
			{
				$this->EE->email->EE_initialize();
				$this->EE->email->to($val);
				$this->EE->email->bcc(implode(',', $approved_recipients));

				if (isset($_POST['replyto']) && $_POST['replyto'] == 'yes')
				{
					$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
					$this->EE->email->reply_to($_POST['from'], $_POST['name']);
				}
				else
				{
					$this->EE->email->from($_POST['from'], $_POST['name']);
				}

				$this->EE->email->subject($subject);
				$this->EE->email->message($message);
				$this->EE->email->send();
			}
		}


		// Store in tracking class
		$data = array(	'email_date'		=> $this->EE->localize->now,
						'sender_ip'			=> $this->EE->input->ip_address(),
						'sender_email'		=> $_POST['from'],
						'sender_username'	=> $this->EE->session->userdata['username'],
						'number_recipients'	=> count($approved_tos) + count($approved_recipients)
					);

		$this->EE->db->query($this->EE->db->insert_string('exp_email_tracker', $data));

		// Delete spam hashes
		if (isset($_POST['XID']))
		{
			$this->EE->db->query("DELETE FROM exp_security_hashes WHERE (hash='".$this->EE->db->escape_str($_POST['XID'])."' AND ip_address = '".$this->EE->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
		}

		/* -------------------------------------
		/*  'email_module_send_email_end' hook.
		/*  - After emails are sent, do some additional processing
		/*  - Added EE 1.5.1
		*/
			if ($this->EE->extensions->active_hook('email_module_send_email_end') === TRUE)
			{
				$edata = $this->EE->extensions->call('email_module_send_email_end', $subject, $message, $approved_tos, $approved_recipients);
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		/*
		/* -------------------------------------*/

		// Thank you message
		$data = array(	'title' 	=> $this->EE->lang->line('email_module_name'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('em_email_sent'),
						'redirect'	=> $return_link,
						'link'		=> array($return_link, $return_name)
					 );

		if ($this->EE->input->get_post('redirect') !== FALSE)
		{
			if(is_numeric($this->EE->input->get_post('redirect')))
			{
				$data['rate'] = $this->EE->input->get_post('redirect');
			}
			elseif($this->EE->input->get_post('redirect') == 'none')
			{
				$data['redirect'] = '';
			}
		}

		$this->EE->output->show_message($data);
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

		$this->EE->load->helper('email');

		foreach ($emails as $email)
		{
			 if (trim($email) == '') continue;

			 if (valid_email($email))
			 {
				  if ( ! $this->EE->session->ban_check('email', $email))
				  {
						$approved_emails[] = $email;
				  }
				  else
				  {
						$error['ban_recp'] = $this->EE->lang->line('em_banned_recipient');
				  }
			 }
			 else
			 {
			 	$error['bad_recp'] = $this->EE->lang->line('em_invalid_recipient');
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
		$charset = $this->EE->TMPL->fetch_param('charset', '');

		$recipients = $this->_encrypt_recipients($recipients);

		$data = array(
			'id'	=> ($this->EE->TMPL->form_id == '') ? 'contact_form' : $this->EE->TMPL->form_id,
			'class'	=> $this->EE->TMPL->form_class,
			'hidden_fields'	=> array(
				'ACT'	=> $this->EE->functions->fetch_action_id('Email', 'send_email'),
				'RET'	=> $this->EE->TMPL->fetch_param('return', ''),
				'URI'	=> ($this->EE->uri->uri_string == '') ? 'index' : $this->EE->uri->uri_string,
				'recipients'		=> base64_encode($recipients),
				'user_recipients'	=> ($this->_user_recipients == 'true') ? md5($this->EE->db->username.$this->EE->db->password.'y') : md5($this->EE->db->username.$this->EE->db->password.'n'),
				'charset'			=> $charset,
				'redirect'			=> $this->EE->TMPL->fetch_param('redirect', ''),
				'replyto'			=> $this->EE->TMPL->fetch_param('replyto', '')
			)
		);

		if ($allow_html)
		{
			$data['hidden_fields']['allow_html'] = $allow_html;
		}

		$name = $this->EE->TMPL->fetch_param('name', FALSE);

 		if ($name && preg_match("#^[a-zA-Z0-9_\-]+$#i", $name, $match))
		{
			$data['name'] = $name;
		}

		$res  = $this->EE->functions->form_declaration($data);
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

		return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, 
								md5($this->EE->db->username.$this->EE->db->password), 
									$recipients, MCRYPT_MODE_ECB, $init_vect);
		}

		return $recipients.md5($this->EE->db->username.$this->EE->db->password.$recipients);
	}	
}
// END CLASS

/* End of file mod.email.php */
/* Location: ./system/expressionengine/modules/email/mod.email.php */