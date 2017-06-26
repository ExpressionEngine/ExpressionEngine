<?php

namespace EllisLab\Addons\Comment\Service;

class Notifications {

	protected $comment;
	protected $recipients;
	protected $variables;

	public function __construct($comment, $url)
	{
		$this->setupRecipients($comment);
		$this->setupVariables($comment, $url);

		$this->comment = $comment;
	}

	private function setupRecipients($comment)
	{
		ee()->load->library('subscription');
		ee()->subscription->init('comment', array('entry_id' => $comment->entry_id), TRUE);

		// Remove the current user
		$ignore = (ee()->session->userdata('member_id') != 0) ? ee()->session->userdata('member_id') : ee()->input->post('email');

		// Grab them all
		$subscriptions = ee()->subscription->get_subscriptions($ignore);
		ee()->load->model('comment_model');
		ee()->comment_model->recount_entry_comments(array($comment->entry_id));
		$this->recipients = ee()->comment_model->fetch_email_recipients($comment->entry_id, $subscriptions);
	}

	private function setupVariables($comment, $url)
	{
		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'parse_images'		=> FALSE,
			'allow_headings'	=> FALSE,
			'smileys'			=> FALSE,
			'word_censor'		=> (ee()->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE)
		);

		$parsed_comment = ee()->typography->parse_type(
			$comment->comment,
			array(
				'text_format'	=> 'none',
				'html_format'	=> 'none',
				'auto_links'	=> 'n',
				'allow_img_url' => 'n'
			)
		);

		$path = ($comment->Channel->comment_url) ?: $comment->Channel->channel_url;
		$action_id  = ee()->functions->fetch_action_id('Comment_mcp', 'delete_comment_notification');

		$this->variables = array(
			'name'				=> $comment->name,
			'name_of_commenter'	=> $comment->name,
			'email'				=> $comment->email,
			'url'				=> $comment->url,
			'location'			=> $comment->location,
			'channel_name'		=> $comment->Channel->channel_title,
			'entry_title'		=> $comment->Entry->title,
			'comment_id'		=> $comment->comment_id,
			'comment'			=> $parsed_comment,
			'comment_url'		=> $url,
			'delete_link'		=> ee('CP/URL')->make('addons/settings/comment/delete_comment_confirm', array('comment_id' => $comment->comment_id)),
			'approve_link'		=> ee('CP/URL')->make('addons/settings/comment/delete_comment_confirm', array('comment_id' => $comment->comment_id, 'status' => 'o')),
			'close_link'		=> ee('CP/URL')->make('addons/settings/comment/delete_comment_confirm', array('comment_id' => $comment->comment_id, 'status' => 'c')),
			'channel_id'		=> $comment->channel_id,
			'entry_id'			=> $comment->entry_id,
			'url_title'			=> $comment->Entry->url_title,
			'comment_url_title_auto_path' => reduce_double_slashes($path.'/'.$comment->Entry->url_title)
		);
	}

	public function send_admin_notifications()
	{
		$addresses = array();

		if ($this->comment->Channel->comment_notify == 'y')
		{
			$addresses = $this->commaDelimToArray($this->comment->Channel->comment_notify_emails);
		}

		if ($this->comment->Channel->comment_notify_authors == 'y')
		{
			$addresses[] = $this->comment->Entry->Author->email;
		}

		$addresses = array_unique($addresses);

		// don't send admin notifications to the comment author if they are an admin, seems silly
		// @todo remove ridiculous that/this dance when PHP 5.3 is no longer supported
		$that = $this;
		$addresses = array_filter($addresses,
			function($value) use ($that)
			{
				if (ee()->session->userdata('member_id') == 0)
				{
					return TRUE;
				}

				return ee()->session->userdata('member_id') != $this->comment->author_id;
			}
		);

		if (empty($addresses))
		{
			return;
		}

		$template = ee()->functions->fetch_email_template('admin_notify_comment');
		$replyto = ($this->comment->email) ?: ee()->config->item('webmaster_email');
		$this->send($template, $addresses, $replyto);
	}

	public function send_user_notifications()
	{

	}

	/**
	 * Comma-delimited Emails to Array
	 *
	 * @param  string $str Comma-delimited email addresses
	 * @return array Array of email addresses
	 */
	private function commaDelimToArray($str)
	{
		if ( ! $str)
		{
			return array();
		}

		if (strpos($str, ',') !== FALSE)
		{
			$emails = preg_split('/[\s,]/', $str, -1, PREG_SPLIT_NO_EMPTY);
		}
		else
		{
			$emails = (array) trim($str);
		}

		return $emails;
	}

	private function send($template, $to, $replyto)
	{
		// keep track of all notifications sent, prevent both admin and user notifications
		// from being sent to the same person, as well as potential duplicates if for some
		// reason there are duplicate addresses in the $to array
		static $sent = array();

		ee()->load->library('email');
		ee()->load->helper('text');

		$subject = ee()->functions->var_swap($template['title'], $this->variables);
		$message = ee()->functions->var_swap($template['data'], $this->variables);

		foreach ($to as $address)
		{
			if (in_array($address, $sent))
			{
				continue;
			}

			ee()->email->EE_initialize();
			ee()->email->wordwrap = FALSE;
			ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
			ee()->email->to($address);
			ee()->email->reply_to($replyto);
			ee()->email->subject($subject);
			ee()->email->message(entities_to_ascii($message));
			ee()->email->send();

			$sent[] = $address;
		}
	}
}
// END CLASS

// EOF
