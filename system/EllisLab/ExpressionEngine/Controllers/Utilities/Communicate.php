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
			$cache = ee()->api->get('EmailCache', $id)
				->with('MemberGroup')
				->all();

			$groups = $cache->getMemberGroups();

			$query = ee()->communicate_model->get_cached_member_groups($id);

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$member_groups[] = $row['group_id'];
				}
			}
		}

		// Set up member group emailing options
		if ( ! ee()->cp->allowed_group('can_email_member_groups'))
		{
			$vars['member_groups'] = FALSE;
		}
		else
		{
			$addt_where = array('include_in_mailinglists' => 'y');

			$query = ee()->member_model->get_member_groups('', $addt_where);

			foreach ($query->result() as $row)
			{
				$checked = (ee()->input->post('group_'.$row->group_id) !== FALSE OR in_array($row->group_id, $member_groups));

				$vars['member_groups'][$row->group_title] = array('name' => 'group_'.$row->group_id, 'value' => $row->group_id, 'checked' => $checked);
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
		// Fetch $_POST data
		// We'll turn the $_POST data into variables for simplicity

		$groups = array();
		$emails = array();

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

		ee()->load->model('communicate_model');

		ee()->load->library('email');
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
			'plaintext_alt'		=> '',	// Relic of the past
		);

		//  Apply text formatting if necessary

		if ($text_fmt != 'none' && $text_fmt != '')
		{
			ee()->load->library('typography');
			ee()->typography->initialize(array(
				'parse_images'	=> FALSE,
				'parse_smileys'	=> FALSE
			));

			if (ee()->config->item('enable_censoring') == 'y' &&
				ee()->config->item('censored_words') != '')
        	{
				$subject = ee()->typography->filter_censored_words($subject);
			}

			$message = ee()->typography->parse_type($message, array(
				'text_format'   => $text_fmt,
				'html_format'   => 'all',
				'auto_links'	=> 'n',
				'allow_img_url' => 'y'
			));
		}

		//  Send a single email

		if (count($groups) == 0)
		{
			$to = $recipient;

			ee()->email->wordwrap  = ($wordwrap == 'y') ? TRUE : FALSE;
			ee()->email->mailtype  = $mailtype;
			ee()->email->from($from, $name);
			ee()->email->to($to);
			ee()->email->cc($cc);
			ee()->email->bcc($bcc);
			ee()->email->subject($subject);
			ee()->email->message($message);

			$error = FALSE;

			if ( ! ee()->email->send(FALSE))
			{
				$error = TRUE;
			}

			$debug_msg = ee()->email->print_debugger(array());

			$this->_delete_attachments(); // Remove attachments now

			if ($error == TRUE)
			{
				show_error(lang('error_sending_email').BR.BR.$debug_msg);
			}

			// Save cache data

			$cache_data['total_sent'] = $this->_fetch_total($to, $cc, $bcc);

			ee()->api->make('EmailCache', $cache_data)->save();

			ee()->view->set_message('success', lang('email_sent_message'), $debug_msg, TRUE);
			ee()->functions->redirect(cp_url('utilities/communicate/sent'));
		}

		// Is Batch Mode set?

		$batch_mode = ee()->config->item('email_batchmode');
		$batch_size = (string) ee()->config->item('email_batch_size');

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

			$query = ee()->member_model->get_member_emails('', $where);

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
			$to = ($recipient == '') ? ee()->session->userdata['email'] : $recipient;

			ee()->email->wordwrap  = ($wordwrap == 'y') ? TRUE : FALSE;
			ee()->email->mailtype  = $mailtype;
			ee()->email->from($from, $name);
			ee()->email->to($to);
			ee()->email->cc($cc);
			ee()->email->bcc($bcc);
			ee()->email->subject($subject);
			ee()->email->message($message);

			$error = FALSE;

			if ( ! ee()->email->send(FALSE))
			{
				$error = TRUE;
			}

			$debug_msg = ee()->email->print_debugger(array());

			// Remove attachments only if member groups or mailing lists
			// don't need them
			if (empty($emails))
			{
				$this->_delete_attachments();
			}

			if ($error == TRUE)
			{
				show_error(lang('error_sending_email').BR.BR.$debug_msg);
			}

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
						$emails['r'][] = $address;
					}
				}
			}
		}

		//  Store email cache
		$cache_data['total_sent'] = 0;
		$cache_data['recipient_array'] = serialize($emails);
		$cache = ee()->api->make('EmailCache', $cache_data);
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
			ee()->email->wordwrap  = ($wordwrap == 'y') ? TRUE : FALSE;
			ee()->email->mailtype  = $mailtype;

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

				ee()->email->clear();
				ee()->email->to($val);
				ee()->email->from($from, $name);
				ee()->email->subject($subject);

				// We need to add the unsubscribe link to emails - but only ones
				// from the mailing list.  When we gathered the email addresses
				// above, we added one of three prefixes to the array key:
				//
				// m = member id
				// r = general recipient

				// Make a copy so we don't mess up the original
				$msg = $message;

				$msg = str_replace('{name}', $screen_name, $msg);

				ee()->email->message($msg);

				if ( ! ee()->email->send(FALSE))
				{
					// Let's adjust the recipient array up to this point
					reset($emails);
					$emails = array_slice($emails, $total_sent);
					$this->communicate_model->update_email_cache($total_sent, $emails, $id);

					$debug_msg = ee()->email->print_debugger(array());

					show_error(lang('error_sending_email').BR.BR.$debug_msg);
				}

				$total_sent++;
			}

			$debug_msg = ee()->email->print_debugger(array());

			$this->_delete_attachments(); // Remove attachments now

			//  Update email cache
			$this->communicate_model->update_email_cache($total_sent, '', $id);

			ee()->cp->render('tools/email_sent', array(
				'debug' => $debug_msg,
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
			'EE_view_disable'	=> TRUE,
			'maincontent_state'	=> ' style="width:100%; display:block"'
		);

		ee()->view->cp_page_title = lang('sending_email');

		ee()->load->view('_shared/refresh_message', $data);
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
