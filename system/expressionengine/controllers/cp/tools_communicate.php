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
 * ExpressionEngine CP Tools Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Tools_communicate extends CI_Controller {

	var $mailinglist_exists	= FALSE;
	var $attachments		= array();
	var $perpage			= 50;
	var $pipe_length		= 5;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();
		
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_comm'))
		{
			show_error(lang('unauthorized_access'));
		}		
		
		if (file_exists(PATH_MOD.'mailinglist/mod.mailinglist.php') && $this->db->table_exists($this->db->dbprefix.'mailing_lists') === TRUE)
		{
			$this->mailinglist_exists = TRUE;
		}
		
		$this->load->model('communicate_model');
		$this->lang->loadfile('communicate');
	}
	
	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */	
	function index()
	{
		$this->load->library('spellcheck');
		$this->load->library('table');
		$this->load->helper(array('form'));
		$this->load->model('member_model');
		$this->load->model('addons_model');
		$this->load->model('tools_model');
		$this->lang->loadfile('tools');
		$this->lang->loadfile('communicate');

		$this->cp->set_variable('cp_page_title', lang('communicate'));

		$this->javascript->output('$("#plaintext_alt_cont").hide();');
		
		$this->javascript->change('#mailtype', '
			if ($("#mailtype").val() == "html")
			{
				// present alt text box
				$("#plaintext_alt_cont").slideDown();
			}
			else
			{
				$("#plaintext_alt_cont").slideUp();
			}

		');
		
		$vars['view_email_cache'] = FALSE;

		/** -----------------------------
		/**  Default Form Values
		/** -----------------------------*/
				
		$member_groups	= array();
		$mailing_lists	= array();
		
		$default = array(
			'name'			=> '',
			'from'		 	=> $this->session->userdata['email'],
			'recipient'  	=> '',
			'cc'			=> '',
			'bcc'			=> '',
			'subject' 		=> '',
			'message'		=> '',
			'plaintext_alt'	=> '',
			'priority'		=>  3,
			'text_fmt'		=> 'none',
			'mailtype'		=> $this->config->item('mail_format'),
			'wordwrap'		=> $this->config->item('word_wrap')
		);
		
		/** -----------------------------
		/**  Are we emailing a member?
		/** -----------------------------*/
		
		if ($this->input->get('email_member') != '' AND $this->cp->allowed_group('can_admin_members'))
		{
			$query = $this->member_model->get_member_emails('', array('m.member_id' => $this->input->get_post('email_member')));

			if ($query->num_rows() == 1)
			{
				$default['recipient'] = $query->row('email');
				$default['message'] = $query->row('screen_name') .",";
			}
		}

		$this->cp->set_breadcrumb(BASE.AMP.'C=tools', lang('tools'));

		/** -----------------------------
		/**  Fetch form data from cache
		/** -----------------------------*/
	
		if ($id = $this->input->get('id'))
		{	 
			if ( ! $this->cp->allowed_group('can_send_cached_email'))
			{	 
				show_error(lang('not_allowed_to_email_mailinglist'));
			}
			
			$this->cp->set_variable('cp_page_title', lang('view_email_cache'));
			$vars['view_email_cache'] = TRUE;
			
			// Fetch cached data
			
			$query = $this->communicate_model->get_cached_email($id);

			if ($query->num_rows() > 0)
			{
				// aliases
				$default['from_email'] =& $default['from'];
				$default['from_name'] =& $default['name'];
				
				foreach ($query->row_array() as $key => $val)
				{
					if (isset($default[$key]))
					{
						$default[$key] = $val;
					}
				}
			}
			
			// Fetch member group IDs
			
			$query = $this->communicate_model->get_cached_member_groups($id);

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$member_groups[] = $row['group_id'];
				}
			}

			if ($this->mailinglist_exists == TRUE)
			{
				// Fetch mailing list IDs
				
				$query = $this->communicate_model->get_cached_mailing_lists($id);

				if ($query->num_rows() > 0)
				{
					foreach ($query->result_array() as $row)
					{
						$mailing_lists[] = $row['list_id'];
					}
				}
			}
		}
		
		foreach ($default as $key => $val)
		{
			$vars[$key] = (isset($_POST[$key])) ? $this->input->post($key) : $val;
		}
		
		$vars['mailtype_options'] = array(
					'text'  => lang('plain_text'),
					'html'  => lang('html')
				);

		$vars['text_formatting'] = 'none';
		$vars['text_formatting_options'] = $this->addons_model->get_plugin_formatting(TRUE);

		$vars['word_wrap_options'] = array(
					'y'  => lang('on'),
					'n'  => lang('off')
				);
			

		$vars['priority_options'] = array(
					'1'  => lang('highest'),
					'2'  => lang('high'),
					'3'  => lang('normal'),
					'4'  => lang('low'),
					'5'  => lang('lowest')
				);

		// will be used to determine if attachments are available
		$vars['upload_dir_result'] = $this->tools_model->get_upload_preferences($this->session->userdata('member_group'));

		if ( ! $this->cp->allowed_group('can_email_mailinglist') OR ! isset($this->mailinglist_exists) OR $this->mailinglist_exists == FALSE)
		{
			$vars['mailing_lists'] = FALSE;
		}  
		else
		{
			$query = $this->communicate_model->get_mailing_lists();
			
			if ($query->num_rows() > 0)
			{
				foreach ($query->result() as $row)
				{
					$checked = ($this->input->post('list_'.$row->list_id) !== FALSE OR in_array($row->list_id, $mailing_lists));
					$vars['mailing_lists'][$row->list_title] = array('name' => 'list_'.$row->list_id, 'value' => $row->list_id, 'checked' => $checked);
				}
			}
		}
		
		if ( ! $this->cp->allowed_group('can_email_member_groups'))
		{
			$vars['member_groups'] = FALSE;
		}
		else
		{
			$addt_where = array('include_in_mailinglists' => 'y');
			
			$query = $this->member_model->get_member_groups('', $addt_where);

			foreach ($query->result() as $row)
			{
				$checked = ($this->input->post('group_'.$row->group_id) !== FALSE OR in_array($row->group_id, $member_groups));

				$vars['member_groups'][$row->group_title] = array('name' => 'group_'.$row->group_id, 'value' => $row->group_id, 'checked' => $checked);
			}
		}
		
		$this->javascript->compile();

		$this->load->view('tools/communicate', $vars);
	}

	// --------------------------------------------------------------------	

	/**
	 * Check for recipients
	 * 
	 * An internal validation function for callbacks
	 *
	 * @access	private
	 * @param	string
	 * @return	bool
	 */
	function _check_for_recipients($str)
	{
		if ( ! $str && $this->input->post('total_gl_recipients') < 1)
		{
			$this->form_validation->set_message('_check_for_recipients', lang('empty_form_fields'));
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------	

	/**
	 * Attachment Handler
	 * 
	 * Used to manage and validate attachments
	 *
	 * @access	private
	 * @return	bool
	 */
	function _attachment_handler()
	{
		// File Attachments?
		if ($_FILES['attachment']['name'] != '')
		{
			$temp_attachment = $_FILES['attachment']['tmp_name'];

			if ( ! is_uploaded_file($temp_attachment))
			{
				$this->form_validation->set_message('_attachment_handler', lang('attachment_problem'));
				return FALSE;
			}

			$temp_path = substr($temp_attachment, 0, strrpos($temp_attachment, DIRECTORY_SEPARATOR)+1);
			$attachment = $temp_path.$_FILES['attachment']['name'];

			// Try to give it a humane name. This should happen so quickly that multiple users
			// won't be able to collide, but check for that first
			if ( ! file_exists($attachment) AND ! rename($temp_attachment, $attachment))
			{
				// If we aren't able to rename this for any reason, then just attach
				// the file with the temp name instead.
				$attachment = $temp_attachment;
			}

			$this->attachments[] = $attachment;
			$this->email->attach($attachment);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------	

	/**
	 * Send Email
	 *
	 * @access	public
	 * @return	void
	 */
	function send_email()
	{
		$this->load->library('email');
		$this->cp->set_variable('cp_page_title', lang('email_success'));

		// Fetch $_POST data
		// We'll turn the $_POST data into variables for simplicity

		$groups = array();
		$list_ids = array();
		$emails = array();

		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 6) == 'group_')
			{
				$groups[] = $val;
			}
			elseif (substr($key, 0, 5) == 'list_')
			{
				$list_ids[] = $val;
			}
			else
			{
				$$key = $val;
			}
		}

		//  Verify privileges
		if (count($groups) > 0 && ! $this->cp->allowed_group('can_email_member_groups'))
		{
			show_error(lang('not_allowed_to_email_member_groups'));
		}

		if (count($list_ids) > 0 && ! $this->cp->allowed_group('can_email_mailinglist') && $this->mailinglist_exists == TRUE)
		{
			show_error(lang('not_allowed_to_email_mailinglist'));
		}

		// Set to allow a check for at least one recipient
		$_POST['total_gl_recipients'] = count($groups)+count($list_ids);

		$this->load->library('form_validation');
		$this->form_validation->set_rules('subject', 'lang:subject', 'required');
		$this->form_validation->set_rules('message', 'lang:message', 'required');
		$this->form_validation->set_rules('from', 'lang:from', 'required|valid_email');
		$this->form_validation->set_rules('accept_admin_email', '', '');
		$this->form_validation->set_rules('cc', 'lang:cc', 'valid_emails');
		$this->form_validation->set_rules('bcc', 'lang:bcc', 'valid_emails');				
		$this->form_validation->set_rules('recipient', 'lang:recipient', 'valid_emails|callback__check_for_recipients');
		$this->form_validation->set_rules('attachment', 'lang:attachment', 'callback__attachment_handler');

		$this->form_validation->set_error_delimiters('<br /><strong class="notice">', '</strong><br />');

		if ($this->form_validation->run() === FALSE)
		{
			return $this->index();
		}

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_communicate'=> lang('communicate')
		));

		// Assign data for caching
		$cache_data = array(
			'cache_date'		=> $this->localize->now,
			'total_sent'		=> 0,
			'from_name'	 		=> $name,
			'from_email'		=> $from,
			'recipient'			=> $recipient,
			'cc'				=> $cc,
			'bcc'				=> $bcc,
			'recipient_array'	=> '',
			'subject'			=> $subject,
			'message'			=> $message,
			'plaintext_alt'		=> $plaintext_alt,
			'mailtype'	  		=> $mailtype,
			'text_fmt'			=> $text_fmt,
			'wordwrap'	  		=> $wordwrap,
			'priority'	  		=> $priority
		);

		//  Apply text formatting if necessary

		if ($text_fmt != 'none' && $text_fmt != '')
		{
			$this->load->library('typography');
			$this->typography->initialize(array(
				'parse_images'	=> FALSE,
				'parse_smileys'	=> FALSE
			));

			if ($this->config->item('enable_censoring') == 'y' &&
				$this->config->item('censored_words') != '')
        	{
				$subject = $this->typography->filter_censored_words($subject);
			}

			$message = $this->typography->parse_type($message, array(
				'text_format'   => $text_fmt,
				'html_format'   => 'all',
				'auto_links'	=> 'n',
				'allow_img_url' => 'y'
			));
		}

		//  Send a single email

		if (count($groups) == 0 AND count($list_ids) == 0 )
		{ 
			$to = $recipient;

			$this->email->wordwrap  = ($wordwrap == 'y') ? TRUE : FALSE;
			$this->email->mailtype  = $mailtype;
			$this->email->priority  = $priority;
			$this->email->from($from, $name);
			$this->email->to($to);
			$this->email->cc($cc);
			$this->email->bcc($bcc); 
			$this->email->subject($subject);
			$this->email->message($message, $plaintext_alt);

			$error = FALSE;

			if ( ! $this->email->send())
			{
				$error = TRUE;
			}

			$this->_delete_attachments(); // Remove attachments now

			if ($error == TRUE)
			{
				show_error(lang('error_sending_email').BR.BR.implode(BR, $this->email->_debug_msg));
			}

			// Save cache data

			$cache_data['total_sent'] = $this->_fetch_total($to, $cc, $bcc);

			$this->communicate_model->save_cache_data($cache_data);

			$this->load->view('tools/email_sent', array(
				'debug' => $this->email->_debug_msg
			));
			
			return;
		}

		// Is Batch Mode set?

		$batch_mode = $this->config->item('email_batchmode');
		$batch_size = (string) $this->config->item('email_batch_size');

		if ( ! ctype_digit($batch_size))
		{
			$batch_mode = 'n';
		}

		/** ---------------------------------
		/**  Fetch member group emails
		/** ---------------------------------*/
		if (count($groups) > 0)
		{
			$where = array();
			
			$where['mg.include_in_mailinglists'] = 'y';
			
			if (isset($_POST['accept_admin_email']))
			{
				$where['m.accept_admin_email'] = 'y';
			}
			
			$where['mg.group_id'] = $groups;

			$query = $this->member_model->get_member_emails('', $where);

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$emails['m'.$row['member_id']] = array(
						$row['email'],
						$row['screen_name']
					);
				}
			}
		}

		/** ---------------------------------
		/**  Fetch mailing list emails
		/** ---------------------------------*/

		$list_templates = array();

		if ($this->mailinglist_exists == TRUE && count($list_ids) > 0)
		{
			foreach ($list_ids as $id)
			{
				// Fetch the template for each list
				$query = $this->communicate_model->get_mailing_lists($id);
				$list_templates[$id] = array(
					'list_title'	=> $query->row('list_title'),
					'list_template'	=> $query->row('list_template')
				);
			}

			$query = $this->communicate_model->get_mailing_list_emails($list_ids);

			// No result?  Show error message

			if ($query->num_rows() == 0 && count($emails) == 0)
			{
				show_error(lang('no_email_matching_criteria'));
			}

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$emails['l'.$row['authcode']] = array(
						$row['email'],
						$row['list_id']
					);
				}
			}
		}

		// After all that, do we have any emails?

		if (count($emails) == 0 AND $recipient == '')
		{
			show_error(lang('no_email_matching_criteria'));
		}
		
		/** ----------------------------------------
		/**  Do we have any CCs or BCCs?
		/** ----------------------------------------*/

		//  If so, we'll send those separately first

		$total_sent = 0;
		
		if ($cc != '' OR $bcc != '')
		{				
			$to = ($recipient == '') ? $this->session->userdata['email'] : $recipient;

			$this->email->wordwrap  = ($wordwrap == 'y') ? TRUE : FALSE;
			$this->email->mailtype  = $mailtype;
			$this->email->priority  = $priority;
			$this->email->from($from, $name);
			$this->email->to($to);
			$this->email->cc($cc);
			$this->email->bcc($bcc);
			$this->email->subject($subject);
			$this->email->message($message, $plaintext_alt);

			$error = FALSE;

			if ( ! $this->email->send())
			{
				$error = TRUE;
			}

			$this->_delete_attachments(); // Remove attachments now

			if ($error == TRUE)
			{
				show_error(lang('error_sending_email').BR.BR.implode(BR, $this->email->_debug_msg));
			}

			$total_sent = $this->_fetch_total($to, $cc, $bcc);
		}
		else
		{
			// No CC/BCCs? Convert recipients to an array so we can include them in the email sending cycle

			if ($recipient != '')
			{
				$emails['r'] = $this->email->_str_to_array($recipient);
			}
		}

		//  Store email cache
		$cache_data['total_sent'] = 0;
		$cache_data['recipient_array'] = serialize($emails);
		$id = $this->communicate_model->save_cache_data($cache_data, $groups, $list_ids);


		/** ----------------------------------------
		/**  If batch-mode is not set, send emails
		/** ----------------------------------------*/

		if (count($emails) <= $batch_size)
		{
			$batch_mode = 'n';
		}

		if ($batch_mode == 'n')
		{
			$action_id  = $this->functions->fetch_action_id('Mailinglist', 'unsubscribe');

			$this->email->wordwrap  = ($wordwrap == 'y') ? TRUE : FALSE;
			$this->email->mailtype  = $mailtype;
			$this->email->priority  = $priority;

			foreach ($emails as $key => $val)
			{
				$screen_name = '';
				$list_id = FALSE;

				if (is_array($val) AND substr($key, 0, 1) == 'm')
				{
					$screen_name = $val['1'];
					$val = $val['0'];
				}
				elseif (is_array($val) AND substr($key, 0, 1) == 'l')
				{
					$list_id = $val['1'];
					$val = $val['0'];
				}

				$this->email->EE_initialize();
				$this->email->to($val); 
				$this->email->from($from, $name);
				$this->email->subject($subject);

				// We need to add the unsubscribe link to emails - but only ones
				// from the mailing list.  When we gathered the email addresses
				// above, we added one of three prefixes to the array key:
				//
				// m = member id
				// l = mailing list
				// r = general recipient

				// Make a copy so we don't mess up the original
				$msg = $message;
				$msg_alt = $plaintext_alt;

				if (substr($key, 0, 1) == 'l')
				{
					$msg = $this->_parse_email_template($list_templates[$list_id], $msg, $action_id, substr($key, 1), $mailtype);
					$msg_alt = $this->_parse_email_template($list_templates[$list_id], $msg_alt, $action_id, substr($key, 1), 'plain');
				}

				$msg = str_replace('{name}', $screen_name, $msg);
				$msg_alt = str_replace('{name}', $screen_name, $msg_alt);

				$this->email->message($msg, $msg_alt);	
				
				if ( ! $this->email->send())
				{
					// Let's adjust the recipient array up to this point
					reset($recipient_array);
					$recipient_array = array_slice($recipient_array, $total_sent);
					$this->communicate_model->update_email_cache($total_sent, $recipient_array, $id);

					show_error(lang('error_sending_email').BR.BR.implode(BR, $this->email->_debug_msg));
				}

				$total_sent++;
			}

			$this->_delete_attachments(); // Remove attachments now

			//  Update email cache
			$this->communicate_model->update_email_cache($total_sent, '', $id);

			$this->load->view('tools/email_sent', array(
				'debug' => $this->email->_debug_msg,
				'total_sent' => $total_sent
			));
			
			return;
		}
		
		/** ----------------------------------------
		/**  Start Batch-Mode
		/** ----------------------------------------*/

		$data = array(
			'redirect_url'		=> BASE.AMP.'C=tools_communicate'.AMP.'M=batch_send'.AMP.'id='.$id,
			'refresh_rate'		=> 6,
			'refresh_message'	=> lang('batchmode_ready_to_begin'),
			'refresh_notice'	=> lang('batchmode_warning'),
			'refresh_heading'	=> lang('sending_email'),
		);
		
		$this->cp->set_variable('cp_page_title', lang('sending_email'));
		
		$this->load->view('_shared/refresh_message', $data);
		return;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Batch Email Send
	 *
	 * Sends email in batch mode
	 *
	 * @access	public
	 * @return	void
	 */
	function batch_send()
	{
		if ( ! $id = $this->input->get_post('id') OR ! ctype_digit($id))
		{
			show_error(lang('problem_with_id'));
		}
		
		/** -----------------------------
		/**  Fetch mailing list IDs
		/** -----------------------------*/

		$list_templates = array();

		if ($this->mailinglist_exists == TRUE)
		{
			$list_ids = array();

			$query = $this->communicate_model->get_cached_mailing_lists($id);
			
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$list_ids[] = $row['list_id'];
				}
				
				if (count($list_ids) > 0)
				{
					// Fetch the template for each list
					$query = $this->communicate_model->get_mailing_lists($list_ids);
			
					if ($query->num_rows() > 0)
					{
						foreach ($query->result_array() as $row)
						{
							$list_templates[$row['list_id']] = array('list_template' => $row['list_template'], 'list_title' => $row['list_title']);
						}
					}
				}
			}
		}

		/** -----------------------------
		/**  Fetch cached email
		/** -----------------------------*/
	
		$query = $this->communicate_model->get_cached_email($id);
	
		if ($query->num_rows() == 0)
		{
			show_error(lang('cache_data_missing'));
		}

		// Turn the result fields into variables
		foreach ($query->row_array() as $key => $val)
		{
			if ($key == 'recipient_array')
			{
				$$key = unserialize($val);
			}
			else
			{
				$$key = $val;
			}
		}
		
		/** -------------------------------------------------
		/**  Determine which emails correspond to this batch
		/** -------------------------------------------------*/

		$finished = FALSE;

		$total = count($recipient_array);

		$batch = $this->config->item('email_batch_size');

		if ($batch > $total)
		{
			$batch = $total;

			$finished = TRUE;
		}
		
		/** ---------------------------------------
		/**  Apply text formatting if necessary
		/** ---------------------------------------*/

		if ($text_fmt != 'none' && $text_fmt != '')
		{
			$this->load->library('typography');
			$this->typography->initialize(array(
				'parse_images'	=> FALSE,
				'parse_smileys'	=> FALSE
			));

			$message = $this->typography->parse_type($message, 
											  array(
													'text_format'   => $text_fmt,
													'html_format'   => 'all',
													'auto_links'	=> 'n',
													'allow_img_url' => 'y'
												  )
											);	
		}
		
		/** ---------------------
		/**  Send emails
		/** ---------------------*/

		$action_id  = $this->functions->fetch_action_id('Mailinglist', 'unsubscribe');

		$this->load->library('email');

		$this->email->wordwrap  = ($wordwrap == 'y') ? TRUE : FALSE;
		$this->email->mailtype  = $mailtype;
		$this->email->priority  = $priority;

		$i = 0;

		foreach ($recipient_array as $key => $val)
		{
			if ($i == $batch)
			{
				break;
			}

			$screen_name = '';
			$list_id = FALSE;

			if (is_array($val) AND substr($key, 0, 1) == 'm')
			{
				$screen_name = $val['1'];
				$val = $val['0'];
			}
			elseif (is_array($val) AND substr($key, 0, 1) == 'l')
			{
				$list_id = $val['1'];
				$val = $val['0'];
			}

			$this->email->EE_initialize();
			$this->email->to($val); 
			$this->email->from($from_email, $from_name);	
			$this->email->subject($subject);

			// We need to add the unsubscribe link to emails - but only ones
			// from the mailing list.  When we gathered the email addresses
			// above, we added one of three prefixes to the array key:
			//
			// m = member id
			// l = mailing list
			// r = general recipient

			// Make a copy so we don't mess up the original
			$msg = $message;
			$msg_alt = $plaintext_alt;

			if (substr($key, 0, 1) == 'l')
			{
				$msg = $this->_parse_email_template($list_templates[$list_id], $msg, $action_id, substr($key, 1), $mailtype);
				$msg_alt = $this->_parse_email_template($list_templates[$list_id], $msg_alt, $action_id, substr($key, 1), 'plain');
			}

			$msg = str_replace('{name}', $screen_name, $msg);
			$msg_alt = str_replace('{name}', $screen_name, $msg_alt);				

			$this->email->message($msg, $msg_alt);	

			$error = FALSE;

			if ( ! $this->email->send())
			{
				$error = TRUE;
			}

			$this->_delete_attachments(); // Remove attachments now

			if ($error == TRUE)
			{
				reset($recipient_array);
				$recipient_array = array_slice($recipient_array, $i);
				
				$n = $total_sent + $i;
				$this->communicate_model->update_email_cache($n, $recipient_array, $id);

				show_error(lang('error_sending_email').BR.BR.implode(BR, $this->email->_debug_msg));
			}

			$i++;
		}

		$n = $total_sent + $i;	

		/** ------------------------
		/**  More batches to do...
		/** ------------------------*/

		if ($finished == FALSE)
		{
			reset($recipient_array);

			$recipient_array = array_slice($recipient_array, $i);
			
			$this->communicate_model->update_email_cache($n, $recipient_array, $id);

			$stats = str_replace("%x", ($total_sent + 1), lang('currently_sending_batch'));
			$stats = str_replace("%y", $n, $stats);

			$remaining = $total - $batch;

			$vars['redirect_url'] =  BASE.AMP.'C=tools_communicate'.AMP.'M=batch_send'.AMP.'id='.$id;
			$vars['refresh_rate'] = 6;
			$vars['refresh_notice'] = lang('batchmode_warning');
			$vars['refresh_message'] = $stats.BR.BR.lang('emails_remaining').NBS.NBS.$remaining;
			$vars['refresh_heading'] = lang('sending_email');

			$this->load->view('_shared/refresh_message', $vars);
			return;
		}
		else
		{
			/** ------------------------
			/**  Finished!
			/** ------------------------*/
			
			$this->communicate_model->update_email_cache($n, '', $id);

			$total = $total_sent + $batch;

			$this->cp->set_variable('cp_page_title', lang('email_success'));
		
			$this->load->view('tools/email_sent', array('debug' => $this->email->_debug_msg, 'total_sent' => $total));
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * View Cache
	 *
	 * View Cached Emails
	 *
	 * @access	public
	 * @return	string
	 */
	function view_cache()
	{
		if ( ! $this->cp->allowed_group('can_send_cached_email'))
		{	 
			show_error(lang('not_allowed_to_email_cache'));
		}

		$this->lang->loadfile('tools');
		$this->lang->loadfile('communicate');

		$this->cp->add_js_script(array('plugin' => 'dataTables'));


	$this->javascript->output('
var oCache = {
	iCacheLower: -1
};

function fnSetKey( aoData, sKey, mValue )
{
	for ( var i=0, iLen=aoData.length ; i<iLen ; i++ )
	{
		if ( aoData[i].name == sKey )
		{
			aoData[i].value = mValue;
		}
	}
}

function fnGetKey( aoData, sKey )
{
	for ( var i=0, iLen=aoData.length ; i<iLen ; i++ )
	{
		if ( aoData[i].name == sKey )
		{
			return aoData[i].value;
		}
	}
	return null;
}

function fnDataTablesPipeline ( sSource, aoData, fnCallback ) {
	var iPipe = '.$this->pipe_length.';  /* Ajust the pipe size */
	
	var bNeedServer = false;
	var sEcho = fnGetKey(aoData, "sEcho");
	var iRequestStart = fnGetKey(aoData, "iDisplayStart");
	var iRequestLength = fnGetKey(aoData, "iDisplayLength");
	var iRequestEnd = iRequestStart + iRequestLength;

	
	oCache.iDisplayStart = iRequestStart;
	
	/* outside pipeline? */
	if ( oCache.iCacheLower < 0 || iRequestStart < oCache.iCacheLower || iRequestEnd > oCache.iCacheUpper )
	{
		bNeedServer = true;
	}
	
	/* sorting etc changed? */
	if ( oCache.lastRequest && !bNeedServer )
	{
		for( var i=0, iLen=aoData.length ; i<iLen ; i++ )
		{
			if ( aoData[i].name != "iDisplayStart" && aoData[i].name != "iDisplayLength" && aoData[i].name != "sEcho" )
			{
				if ( aoData[i].value != oCache.lastRequest[i].value )
				{
					bNeedServer = true;
					break;
				}
			}
		}
	}
	
	/* Store the request for checking next time around */
	oCache.lastRequest = aoData.slice();
	
	if ( bNeedServer )
	{
		if ( iRequestStart < oCache.iCacheLower )
		{
			iRequestStart = iRequestStart - (iRequestLength*(iPipe-1));
			if ( iRequestStart < 0 )
			{
				iRequestStart = 0;
			}
		}
		
		oCache.iCacheLower = iRequestStart;
		oCache.iCacheUpper = iRequestStart + (iRequestLength * iPipe);
		oCache.iDisplayLength = fnGetKey( aoData, "iDisplayLength" );
		fnSetKey( aoData, "iDisplayStart", iRequestStart );
		fnSetKey( aoData, "iDisplayLength", iRequestLength*iPipe );
		
		
		$.getJSON( sSource, aoData, function (json) { 
			/* Callback processing */
			oCache.lastJson = jQuery.extend(true, {}, json);
 			
			if ( oCache.iCacheLower != oCache.iDisplayStart )
			{
				json.aaData.splice( 0, oCache.iDisplayStart-oCache.iCacheLower );
			}
			json.aaData.splice( oCache.iDisplayLength, json.aaData.length );
			
			fnCallback(json)
		} );
	}
	else
	{
		json = jQuery.extend(true, {}, oCache.lastJson);
		json.sEcho = sEcho; /* Update the echo for each response */
		json.aaData.splice( 0, iRequestStart-oCache.iCacheLower );
		json.aaData.splice( iRequestLength, json.aaData.length );
		fnCallback(json);
		return;
	}
}

var time = new Date().getTime();
		
	oTable = $(".mainTable").dataTable( {	
			"sPaginationType": "full_numbers",
			"bLengthChange": false,
			"bFilter": false,
			"sWrapper": false,
			"sInfo": false,
			"bAutoWidth": false,
			"iDisplayLength": '.$this->perpage.',  

		"aoColumns": [null, null, null, null, { "bSortable" : false }, { "bSortable" : false } ],
			
		"oLanguage": {
			"sZeroRecords": "'.lang('ml_no_results').'",
			
			"oPaginate": {
				"sFirst": "<img src=\"'.$this->cp->cp_theme_url.'images/pagination_first_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sPrevious": "<img src=\"'.$this->cp->cp_theme_url.'images/pagination_prev_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sNext": "<img src=\"'.$this->cp->cp_theme_url.'images/pagination_next_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />", 
				"sLast": "<img src=\"'.$this->cp->cp_theme_url.'images/pagination_last_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />"
			}
		},
			
			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": EE.BASE+"&C=tools_communicate&M=view_cache_ajax_filter&time=" + time,
			"fnServerData": fnDataTablesPipeline

	} );

	');	
		
		$this->javascript->output('
									$(".toggle_all").toggle(
										function(){		
											$("input.toggle").each(function() {
												this.checked = true;
											});
										}, function (){
											var checked_status = this.checked;
											$("input.toggle").each(function() {
												this.checked = false;
											});
										}
									);'
								);

		$this->load->library('table');
		$this->load->helper('form');
		$this->cp->set_variable('cp_page_title', lang('view_email_cache'));

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_communicate'=> lang('communicate')
		));

		// some defaults
		$vars['cached_email'] = FALSE;
		
		$total = $this->db->count_all('email_cache');

		if ($total == 0)
		{
			$this->load->view('tools/view_cached_email', $vars);
			return;
		}
		
		$row = ( ! $this->input->get_post('per_page')) ? 0 : $this->input->get_post('per_page');
		$vars['pagination'] = FALSE;

		if ($total > $this->perpage)
		{
			$this->load->library('pagination');
			
			$config['base_url'] = BASE.AMP.'C=tools_communicate'.AMP.'M=view_cache';
			$config['total_rows'] = $total;
			$config['per_page'] = $this->perpage;
			$config['page_query_string'] = TRUE;
			$config['first_link'] = lang('pag_first_link');
			$config['last_link'] = lang('pag_last_link');
			
			$this->pagination->initialize($config);	
			$vars['pagination'] = $this->pagination->create_links();
		}

		$query = $this->communicate_model->get_cached_email('', $this->perpage, $row);
		
		foreach ($query->result_array() as $row)
		{
			$vars['cached_email'][] = array(
											'cache_id'		=> $row['cache_id'],
											'email_title'	=> $row['subject'],
											'email_date'	=> $this->localize->set_human_time($row['cache_date']),
											'total_sent'	=> $row['total_sent'],
											'status'		=> ($row['recipient_array'] == '') ? TRUE : FALSE
										);
		}
		
		$this->javascript->compile();
		
		$this->load->view('tools/view_cached_email', $vars);
	}


	// --------------------------------------------------------------------
	
	/**
	 * Ajax filter for cache
	 *
	 * Confirmation page for deleting cached emails
	 *
	 * @access	public
	 * @return	void
	 */
	function view_cache_ajax_filter()
	{
		$this->output->enable_profiler(FALSE);
		
		$col_map = array('subject', 'cache_date', 'total_sent', 'recipient_array', 'recipient_array');
		
		// Note- we pipeline the js, so pull more data than are displayed on the page		
		$perpage = $this->input->get_post('iDisplayLength');
		$offset = ($this->input->get_post('iDisplayStart')) ? $this->input->get_post('iDisplayStart') : 0; // Display start point
		$sEcho = $this->input->get_post('sEcho');	

		/* Ordering */
		$order = array();
		
		if ($this->input->get('iSortCol_0') !== FALSE)
		{
			for ( $i=0; $i < $this->input->get('iSortingCols'); $i++ )
			{
				if (isset($col_map[$this->input->get('iSortCol_'.$i)]))
				{
					$order[$col_map[$this->input->get('iSortCol_'.$i)]] = ($this->input->get('sSortDir_'.$i) == 'asc') ? 'asc' : 'desc';
				}
			}
		}
		
		$query = $this->communicate_model->get_cached_email('', $perpage, $offset, $order);
		
		$total = $this->db->count_all('email_cache');


		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $total;
		$j_response['iTotalDisplayRecords'] = $total;
					

		
		$tdata = array();
		$i = 0;
		
		foreach ($query->result_array() as $email)
		{
		
			$m[] = "<strong><a href='".BASE.AMP.'C=tools_communicate'.AMP.'M=view_email'.AMP.'id='.$email['cache_id']."'>{$email['subject']}</a></strong>";
			$m[] = $this->localize->set_human_time($email['cache_date']);
			$m[] = $email['total_sent'];
			$m[] = ($email['recipient_array'] == '') ? lang('complete') :
					lang('incomplete').NBS.NBS.'<a href="'.BASE.AMP
					.'C=tools_communicate'.AMP.'M=batch_send'.AMP.'id='.$email['cache_id'].'">Finish Sending</a>';
			$m[] = '<a href="'.BASE.AMP.'C=tools_communicate'.AMP.'id='.$email['cache_id'].'">'.lang('resend').'</a>';
			$m[] = '<input class="toggle" type="checkbox" name="email[]" value="'.$email['cache_id'].'" />';		

			$tdata[$i] = $m;
			$i++;
			unset($m);
		}		

		$j_response['aaData'] = $tdata;	
		$sOutput = $this->javascript->generate_json($j_response, TRUE);
	
		exit($sOutput);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Delete Emails Confirm
	 *
	 * Confirmation page for deleting cached emails
	 *
	 * @access	public
	 * @return	void
	 */
	function delete_emails_confirm()
	{
		if ( ! $this->cp->allowed_group('can_send_cached_email'))
		{	 
			show_error(lang('not_allowed_to_email_mailinglist'));
		}
		
		if ( ! $this->input->post('email'))
		{
			show_error(lang('bad_cache_ids'));
		}
		
		$query = $this->communicate_model->get_cached_email($this->input->post('email'), FALSE);
		
		if ($query->num_rows() == 0)
		{
			show_error(lang('bad_cache_ids'));
		}
		
		$i = 0;
		
		foreach ($query->result() as $row)
		{
			$vars['emails'][] = $row->subject;
			$vars['hidden']['email['.$i++.']'] = $row->cache_id;
		}
		
		$this->load->helper('form');

		$this->cp->set_variable('cp_page_title', lang('delete_emails'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=tools_communicate'.AMP.'M=view_cache', lang('view_email_cache'));
		
		$this->load->view('tools/email_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Delete Emails
	 *
	 * Deletes cached emails
	 *
	 * @access	public
	 * @return	string
	 */
	function delete_emails()
	{
		if ( ! $this->cp->allowed_group('can_send_cached_email'))
		{	 
			show_error(lang('not_allowed_to_email_mailinglist'));
		}
		
		if ( ! $this->input->post('email'))
		{
			show_error(lang('bad_cache_ids'));
		}
		
		$this->communicate_model->delete_emails($this->input->post('email'));
		
		$this->session->set_flashdata('message_success', lang('email_deleted'));
		$this->functions->redirect(BASE.AMP.'C=tools_communicate'.AMP.'M=view_cache');
	}

	// --------------------------------------------------------------------
	
	/**
	 * View Email
	 *
	 * Displays an individual email, with typography
	 *
	 * @access	public
	 * @return	string
	 */
	function view_email()
	{
		if ( ! $this->cp->allowed_group('can_send_cached_email'))
		{	 
			show_error(lang('not_allowed_to_email_mailinglist'));
		}
		
		$query = $this->communicate_model->get_cached_email($this->input->get_post('id'));
		
		if ($query->num_rows() == 0)
		{
			show_error(lang('no_cached_email'));
		}
		
		/** -----------------------------
		/**  Clean up message
		/** -----------------------------*/

		// If the message was submitted in HTML format
		// we'll remove everything except the body

		$message = $query->row('message');

		if ($query->row('mailtype')  == 'html')
		{
			$message = (preg_match("/<body.*?".">(.*)<\/body>/is", $message, $match)) ? $match['1'] : $message;
		}			

		/** -----------------------------
		/**  Render output
		/** -----------------------------*/

		$vars['subject'] = htmlentities($query->row('subject'));

		/** ----------------------------------------
		/**  Instantiate Typography class
		/** ----------------------------------------*/

		$this->load->library('typography');
		$this->typography->initialize();

		$vars['message'] = $this->typography->parse_type($message, array(
			'text_format'	=> 'xhtml',
			'html_format'	=> 'all',
			'auto_links'	=> 'y',
			'allow_img_url' => 'y'
		));
		
		$this->cp->set_variable('cp_page_title', $vars['subject']);

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_communicate'=> lang('communicate')
		));

		$this->load->view('tools/view_email', $vars);
	}

	// --------------------------------------------------------------------
		
	/**
	 * Parse Email Template
	 *
	 * Adds unsubscribe links to emails, etc.
	 *
	 * @access	private
	 * @param	string	template
	 * @param	string	message
	 * @param	string	action id
	 * @param	string	id for GET
	 * @param	string	html/plain
	 * @return	string
	 */
	function _parse_email_template($template, $message, $action_id, $code, $mailtype='plain')
	{
		if (is_array($template))
		{
			$list_title = $template['list_title'];
			$temp = $template['list_template'];
		}
		else
		{
			$list_title = '';
			$temp = $template;
		}

		$qs = ($this->config->item('force_query_string') == 'y') ? '' : '?';		
		$link_url = $this->functions->fetch_site_index(0, 0).$qs.'ACT='.$action_id.'&id='.$code;

		$temp = str_replace('{unsubscribe_url}', $link_url, $temp);	

		if ($mailtype == 'html')
		{
			$temp =  preg_replace("/\{if\s+html_email\}(.+?)\{\/if\}/si", "\\1", $temp);
			$temp =  preg_replace("/\{if\s+plain_email\}.+?\{\/if\}/si", "", $temp);
		}
		else
		{
			$temp =  preg_replace("/\{if\s+plain_email\}(.+?)\{\/if\}/si", "\\1", $temp);
			$temp =  preg_replace("/\{if\s+html_email\}.+?\{\/if\}/si", "", $temp);
		}

		$temp = str_replace('{mailing_list}', $list_title, $temp);

		return str_replace('{message_text}', $message, $temp);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Fetch Total
	 *
	 * Returns a total of email addresses from an undetermined number of strings
	 * containing comma-delimited addresses
	 *
	 * @access	private
	 * @param	string 	// any number of comma delimited strings
	 * @return	string
	 */
	function _fetch_total()
	{
		$strings = func_get_args();
		$total = 0;

		foreach ($strings as $string)
		{
			if ($string != '')
			{
				$total += substr_count($string, ',') + 1;				
			}			
		}
		
		return $total;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Attachments
	 *
	 * @access	private
	 */
	function _delete_attachments()
	{
		foreach ($this->attachments as $file)
		{
			unlink($file);
		}
	}

}
// END CLASS

/* End of file tools_communicate.php */
/* Location: ./system/expressionengine/controllers/cp/tools_communicate.php */