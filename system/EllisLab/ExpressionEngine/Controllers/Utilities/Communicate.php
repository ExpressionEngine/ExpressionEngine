<?php

namespace EllisLab\ExpressionEngine\Controllers\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Library\CP\Pagination;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Communicate Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Communicate extends Utilities {
	private $attachments = array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_tools', 'can_access_comm'))
		{
			show_error(lang('unauthorized_access'));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Index
	 */
	public function index($id = NULL)
	{
		$default = array(
			'from'		 	=> ee()->session->userdata('email'),
			'recipient'  	=> '',
			'cc'			=> '',
			'bcc'			=> '',
			'subject' 		=> '',
			'message'		=> '',
			'mailtype'		=> ee()->config->item('mail_format'),
			'wordwrap'		=> ee()->config->item('word_wrap')
		);

		$vars['mailtype_options'] = array(
			'text'		=> lang('plain_text'),
			'markdown'	=> lang('markdown'),
			'html'		=> lang('html')
		);

		$member_groups = array();

		/** -----------------------------
		/**  Fetch form data from cache
		/** -----------------------------*/
		if ($id)
		{
			$caches = ee()->api->get('EmailCache', $id)
				->with('MemberGroups')
				->all();

			$member_groups = $caches[0]->getMemberGroups()->getIds();
		}

		// Set up member group emailing options
		if ( ! ee()->cp->allowed_group('can_email_member_groups'))
		{
			$vars['member_groups'] = FALSE;
		}
		else
		{
			$groups = ee()->api->get('MemberGroup')
				->with('Members')
				->filter('include_in_mailinglists', 'y')
				->all();

			foreach ($groups as $group)
			{
				$checked = (ee()->input->post('group_'.$group->group_id) !== FALSE OR in_array($group->group_id, $member_groups));

				$vars['member_groups'][$group->group_title]['attrs'] = array('name' => 'group_'.$group->group_id, 'value' => $group->group_id, 'checked' => $checked);
				$vars['member_groups'][$group->group_title]['members'] = count($group->getMembers());
				if ($vars['member_groups'][$group->group_title]['members'] == 0)
				{
					$vars['member_groups'][$group->group_title]['attrs']['disabled'] = 'disabled';
				}
			}
		}

		ee()->view->cp_page_title = lang('communicate');
		ee()->cp->render('utilities/communicate', $vars + $default);
	}

	/**
	 * Send Email
	 */
	public function send()
	{
		ee()->load->library('email');

		// Fetch $_POST data
		// We'll turn the $_POST data into variables for simplicity

		$groups = array();

		$form_fields = array(
			'subject',
			'message',
			'mailtype',
			'wordwrap',
			'from',
			'attachment',
			'recipient',
			'cc',
			'bcc'
		);

		$wordwrap = 'n';

		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 6) == 'group_')
			{
				$groups[] = ee()->input->post($key);
			}
			elseif (in_array($key, $form_fields))
			{
				$$key = ee()->input->post($key);
			}
		}

		//  Verify privileges
		if (count($groups) > 0 && ! ee()->cp->allowed_group('can_email_member_groups'))
		{
			show_error(lang('not_allowed_to_email_member_groups'));
		}

		// Set to allow a check for at least one recipient
		$_POST['total_gl_recipients'] = count($groups);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules('subject', 'lang:subject', 'required');
		ee()->form_validation->set_rules('message', 'lang:message', 'required');
		ee()->form_validation->set_rules('from', 'lang:from', 'required|valid_email');
		ee()->form_validation->set_rules('cc', 'lang:cc', 'valid_emails');
		ee()->form_validation->set_rules('bcc', 'lang:bcc', 'valid_emails');
		ee()->form_validation->set_rules('recipient', 'lang:recipient', 'valid_emails|callback__check_for_recipients');
		ee()->form_validation->set_rules('attachment', 'lang:attachment', 'callback__attachment_handler');

		if (ee()->form_validation->run() === FALSE)
		{
			return $this->index();
		}

		$name = ee()->session->userdata('screen_name');

		ee()->view->cp_page_title = lang('email_success');
		$debug_msg = '';

		switch ($mailtype)
		{
			case 'text':
				$text_fmt = 'none';
				break;

			case 'markdown':
				$text_fmt = 'markdown';
				$mailtype = 'html';
				break;

			case 'html':
				$text_fmt = 'xhtml';
				break;
		}

		// Assign data for caching
		$cache_data = array(
			'cache_date'		=> ee()->localize->now,
			'total_sent'		=> 0,
			'from_name'	 		=> $name,
			'from_email'		=> $from,
			'recipient'			=> $recipient,
			'cc'				=> $cc,
			'bcc'				=> $bcc,
			'recipient_array'	=> '',
			'subject'			=> $subject,
			'message'			=> $message,
			'mailtype'			=> $mailtype,
			'wordwrap'	  		=> $wordwrap,
			'text_fmt'			=> $text_fmt,
			'total_sent'		=> 0,
			'plaintext_alt'		=> '',	// Relic of the past
		);

		$email = ee()->api->make('EmailCache', $cache_data);
		$email->save();

		//  Send a single email
		if (count($groups) == 0)
		{
			$debug_msg = $this->deliverOneEmail($email, $recipient);

			ee()->view->set_message('success', lang('email_sent_message'), $debug_msg, TRUE);
			ee()->functions->redirect(cp_url('utilities/communicate/sent'));
		}

		// Get member group emails
		$member_groups = ee()->api->get('MemberGroup', $groups)
			->with('Members')
			->filter('include_in_mailinglists', 'y') // for safety
			->all();

		$email_addresses = array();
		foreach ($member_groups as $group)
		{
			foreach ($group->getMembers() as $member)
			{
				$email_addresses[] = $member->email;
			}
		}

		if (empty($email_addresses) AND $recipient == '')
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
			$to = ($recipient == '') ? ee()->session->userdata['email'] : $recipient;
			$debug_msg = $this->deliverOneEmail($email, $to, empty($email_addresses));

			$total_sent = $this->_fetch_total($to, $cc, $bcc);
		}
		else
		{
			// No CC/BCCs? Convert recipients to an array so we can include them in the email sending cycle

			if ($recipient != '')
			{
				foreach (explode(',', $recipient) as $address)
				{
					$address = trim($address);

					if ( ! empty($address))
					{
						$email_addresses[] = $address;
					}
				}
			}
		}

		//  Store email cache
		$email->recipient_array = serialize($email_addresses);
		$email->setMemberGroups(ee()->api->get('MemberGroup', $groups)->all());
		$email->save();
		$id = $email->cache_id;

		// Is Batch Mode set?

		$batch_mode = ee()->config->item('email_batchmode');
		$batch_size = (int) ee()->config->item('email_batch_size');

		if (count($email_addresses) <= $batch_size)
		{
			$batch_mode = 'n';
		}

		/** ----------------------------------------
		/**  If batch-mode is not set, send emails
		/** ----------------------------------------*/

		if ($batch_mode == 'n')
		{
			$total_sent = $this->deliverManyEmails($email);

			$debug_msg = ee()->email->print_debugger(array());

			$this->_delete_attachments(); // Remove attachments now

			ee()->view->set_message('success', lang('total_emails_sent') . ' ' . $total_sent, $debug_msg, TRUE);
			ee()->functions->redirect(cp_url('utilities/communicate/sent'));
		}

		/** ----------------------------------------
		/**  Start Batch-Mode
		/** ----------------------------------------*/

		ee()->view->set_refresh(cp_url('utilities/communicate/batch/' . $email->cache_id), 6, TRUE);

		$alert = array(
			'type' => 'warn',
			'title' => lang('batchmode_ready_to_begin'),
			'description' => lang('batchmode_warning')
		);
		ee()->view->set_alert('standard', $alert, TRUE);

		ee()->functions->redirect(cp_url('utilities/communicate'));
	}

	// --------------------------------------------------------------------

	/**
	 * Batch Email Send
	 *
	 * Sends email in batch mode
	 *
	 * @param int $id	The cache_id to send
	 */
	public function batch($id)
	{
		ee()->load->library('email');

		if (ee()->config->item('email_batchmode') != 'y')
		{
			show_error(lang('batchmode_disabled'));
		}

		if ( ! ctype_digit($id))
		{
			show_error(lang('problem_with_id'));
		}

		$email = ee()->api->get('EmailCache', $id)->first();

		if (is_null($email))
		{
			show_error(lang('cache_data_missing'));
		}

		$start = $email->total_sent;

		$this->deliverManyEmails($email);

		if ($email->recipient_array == '')
		{
			$debug_msg = ee()->email->print_debugger(array());

			$this->_delete_attachments(); // Remove attachments now

			ee()->view->set_message('success', lang('total_emails_sent') . ' ' . $email->total_sent, $debug_msg, TRUE);
			ee()->functions->redirect(cp_url('utilities/communicate/sent'));
		}
		else
		{
			$stats = str_replace("%x", ($start + 1), lang('currently_sending_batch'));
			$stats = str_replace("%y", ($email->total_sent), $stats);

			$message = $stats.BR.BR.lang('emails_remaining').NBS.NBS.count(unserialize($email->recipient_array));

			ee()->view->set_refresh(cp_url('utilities/communicate/batch/' . $email->cache_id), 6, TRUE);

			$alert = array(
				'type' => 'warn',
				'title' => $message,
				'description' => lang('batchmode_warning')
			);
			ee()->view->set_alert('standard', $alert, TRUE);

			ee()->functions->redirect(cp_url('utilities/communicate'));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Sends a single email handling errors
	 */
	private function deliverOneEmail($email, $to, $delete = TRUE)
	{
		if ( ! $this->deliverEmail($email, $to, $email->cc, $email->bcc))
		{
			$error = TRUE;
		}

		if ($delete)
		{
			$this->_delete_attachments(); // Remove attachments now
		}

		$debug_msg = ee()->email->print_debugger(array());

		if ($error == TRUE)
		{
			show_error(lang('error_sending_email').BR.BR.$debug_msg);
		}

		// Save cache data
		$email->total_sent = $this->_fetch_total($to, $email->cc, $email->bcc);
		$email->save();
		return $debug_msg;
	}

	// --------------------------------------------------------------------

	/**
	 * Sends multiple emails handling errors
	 */
	private function deliverManyEmails($email)
	{
		$recipient_array = unserialize($email->recipient_array);
		if ( ! is_array($recipient_array) OR count($recipient_array) < 1)
		{
			return 0;
		}
		$batch_size = (int) ee()->config->item('email_batch_size');

		$number_to_send = ($batch_size > count($recipient_array)) ?
			count($recipient_array) : $batch_size;

		$total_sent = $email->total_sent;
		for ($x = 0; $x < $number_to_send; $x++)
		{
			$email_address = array_shift($recipient_array);
			if ( ! $this->deliverEmail($email, $email_address))
			{
				// Let's adjust the recipient array up to this point
				$recipient_array = array_unshift($recipient_array, $email_address);

				$email->total_sent += $total_sent;
				$email->recipient_array = serialize($recipient_array);
				$email->save();

				$debug_msg = ee()->email->print_debugger(array());

				show_error(lang('error_sending_email').BR.BR.$debug_msg);
			}
			$total_sent++;
		}

		$email->total_sent = $total_sent;
		$email->recipient_array = (count($recipient_array)) ? serialize($recipient_array) : "";
		$email->save();
		return $total_sent;
	}


	// --------------------------------------------------------------------

	/**
	 * Delivers an email
	 */
	private function deliverEmail($email, $to, $cc = NULL, $bcc = NULL)
	{
		$subject = $email->subject;
		$message = $email->message;

		//  Apply text formatting if necessary
		if ($email->text_fmt != 'none' && $email->text_fmt != '')
		{
			ee()->load->library('typography');
			ee()->typography->initialize(array(
				'parse_images'	=> FALSE,
				'parse_smileys'	=> FALSE
			));

			if (ee()->config->item('enable_censoring') == 'y' &&
				ee()->config->item('censored_words') != '')
        	{
				$subject = ee()->typography->filter_censored_words($email->subject);
			}

			$message = ee()->typography->parse_type($email->message, array(
				'text_format'   => $email->text_fmt,
				'html_format'   => 'all',
				'auto_links'	=> 'n',
				'allow_img_url' => 'y'
			));
		}

		ee()->email->clear();
		ee()->email->wordwrap  = ($email->wordwrap == 'y') ? TRUE : FALSE;
		ee()->email->mailtype  = $email->mailtype;
		ee()->email->from($email->from_email, $email->from_name);
		ee()->email->to($to);

		if ( ! is_null($cc))
		{
			ee()->email->cc($email->cc);
		}

		if ( ! is_null($bcc))
		{
			ee()->email->bcc($email->bcc);
		}

		ee()->email->subject($subject);
		ee()->email->message($message);

		return ee()->email->send(FALSE);
	}

	// --------------------------------------------------------------------

	/**
	 * View sent emails
	 */
	public function sent()
	{
		if ( ! ee()->cp->allowed_group('can_send_cached_email'))
		{
			show_error(lang('not_allowed_to_email_cache'));
		}

		$base_url = new URL('utilities/communicate/sent', ee()->session->session_id());

		$vars = array(
			'highlight'				=> 'subject',
			'subject_sort_url'		=> '',
			'subject_direction'		=> 'asc',
			'date_sort_url'			=> '',
			'date_direction'		=> 'asc',
			'total_sent_sort_url'	=> '',
			'total_sent_direction'	=> 'asc',
			'status_sort_url'		=> '',
			'status_direction'		=> 'asc',
			'emails'				=> array()
		);

		$order_by = 'subject';
		$order_direction = 'asc';

		$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
		$page = ($page > 0) ? $page : 1;

		$offset = ($page - 1) * 50; // Offset is 0 indexed

		$emails = ee()->api->get('EmailCache');

		$count = $emails->count();

		$emails = $emails->order($order_by, $order_direction)
			->limit(50)
			->offset($offset)
			->all();

		foreach ($emails as $email)
		{
			$vars['emails'][] = array(
				'id'			=> $email->cache_id,
				'subject'		=> $email->subject,
				'date'			=> ee()->localize->human_time($email->cache_date),
				'total_sent'	=> $email->total_sent,
				'status'		=> ''
			);
		}

		$pagination = new Pagination(50, $count, $page);
		$vars['links'] = $pagination->cp_links($base_url);

		ee()->view->cp_page_title = lang('view_email_cache');
		ee()->cp->render('utilities/communicate-sent', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Check for recipients
	 *
	 * An internal validation function for callbacks
	 *
	 * @param	string
	 * @return	bool
	 */
	public function _check_for_recipients($str)
	{
		if ( ! $str && ee()->input->post('total_gl_recipients') < 1)
		{
			ee()->form_validation->set_message('_check_for_recipients', lang('empty_form_fields'));
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Attachment Handler
	 *
	 * Used to manage and validate attachments. Must remain public,
	 * it's a form validation callback.
	 *
	 * @return	bool
	 */
	public function _attachment_handler()
	{
		// File Attachments?
		if ( ! isset($_FILES['attachment']['name']) OR empty($_FILES['attachment']['name']))
		{
			return TRUE;
		}

		ee()->load->library('upload');
		ee()->upload->initialize(array(
			'allowed_types'	=> '*',
			'use_temp_dir'	=> TRUE
		));

		if ( ! ee()->upload->do_upload('attachment'))
		{
			ee()->form_validation->set_message('_attachment_handler', lang('attachment_problem'));
			return FALSE;
		}

		$data = ee()->upload->data();

		$this->attachments[] = $data['full_path'];
		ee()->email->attach($data['full_path']);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Total
	 *
	 * Returns a total of email addresses from an undetermined number of strings
	 * containing comma-delimited addresses
	 *
	 * @param	string 	// any number of comma delimited strings
	 * @return	string
	 */
	private function _fetch_total()
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
	 */
	private function _delete_attachments()
	{
		foreach ($this->attachments as $file)
		{
			if (file_exists($file))
			{
				unlink($file);
			}
		}
	}

}
// END CLASS

/* End of file Communicate.php */
/* Location: ./system/expressionengine/controllers/cp/Utilities/Communicate.php */