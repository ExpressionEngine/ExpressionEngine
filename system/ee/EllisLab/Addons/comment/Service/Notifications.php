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
		$recipients = ee()->comment_model->fetch_email_recipients($comment->entry_id, $subscriptions);

		foreach ($recipients as $recipient)
		{
			$this->recipients[$recipient[0]] = array(
				'email' => $recipient[0],
				'name_of_recipient' => $recipient[2],
				'subscription' => $subscriptions[$recipient[1]],
			);
		}
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
			'approve_link'      => ee('CP/URL')->make('addons/settings/comment/delete_comment_confirm', array('comment_id' => $comment->comment_id, 'status' => 'o')),
			'channel_id'        => $comment->channel_id,
			'channel_name'      => $comment->Channel->channel_title,
			'close_link'        => ee('CP/URL')->make('addons/settings/comment/delete_comment_confirm', array('comment_id' => $comment->comment_id, 'status' => 'c')),
			'comment'           => $parsed_comment,
			'comment_id'        => $comment->comment_id,
			'comment_url'       => $url,
			'comment_url_title_auto_path' => reduce_double_slashes($path.'/'.$comment->Entry->url_title),
			'delete_link'       => ee('CP/URL')->make('addons/settings/comment/delete_comment_confirm', array('comment_id' => $comment->comment_id)),
			'email'             => $comment->email,
			'entry_id'          => $comment->entry_id,
			'entry_title'       => $comment->Entry->title,
			'location'          => $comment->location,
			'name'              => $comment->name,
			'name_of_commenter' => $comment->name,
			'notification_removal_url' => ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id={subscription_id}&hash={hash}',
			'site_name'         => stripslashes(ee()->config->item('site_name')),
			'site_url'          => ee()->config->item('site_url'),
			'url'               => $comment->url,
			'url_title'         => $comment->Entry->url_title,
		);
	}

	public function send_admin_notifications()
	{
		$emails = array();

		if ($this->comment->Channel->comment_notify == 'y')
		{
			$emails = $this->commaDelimToArray($this->comment->Channel->comment_notify_emails);
		}

		if ($this->comment->Channel->comment_notify_authors == 'y')
		{
			$emails[] = $this->comment->Entry->Author->email;
		}

		$emails = array_unique($emails);

		// don't send admin notifications to the comment author if they are an admin, seems silly
		// @todo remove ridiculous that/this dance when PHP 5.3 is no longer supported
		$that = $this;
		$emails = array_filter($emails,
			function($value) use ($that)
			{
				if (ee()->session->userdata('member_id') == 0)
				{
					return TRUE;
				}

				return ee()->session->userdata('member_id') != $this->comment->author_id;
			}
		);

		if (empty($emails))
		{
			return;
		}

		$addresses = $this->structureAddresses($emails);

		$template = ee()->functions->fetch_email_template('admin_notify_comment');
		$replyto = ($this->comment->email) ?: ee()->config->item('webmaster_email');
		$this->send($template, $addresses, $replyto);
	}

	public function send_user_notifications()
	{
		if (empty($this->recipients))
		{
			return;
		}

		$template = ee()->functions->fetch_email_template('comment_notification');
		$replyto = ($this->comment->email) ?: ee()->config->item('webmaster_email');
		$this->send($template, $this->recipients, ee()->config->item('webmaster_email'));
	}

	private function structureAddresses($emails)
	{
		$addresses = array();

		foreach ($emails as $email)
		{
			$addresses[$email] = array(
				'email' => $email,
				'name_of_recipient' => $email,
				'subscription' => null,
			);
		}

		return $addresses;
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
			if (in_array($address['email'], $sent))
			{
				continue;
			}

			$body = $message;
			$body = str_replace('{name_of_recipient}', $address['name_of_recipient'], $body);

			if ( ! empty($address['subscription']))
			{
				$body = ee()->functions->var_swap($body, $address['subscription']);
			}

			ee()->email->EE_initialize();
			ee()->email->wordwrap = FALSE;
			ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
			ee()->email->to($address['email']);
			ee()->email->reply_to($replyto);
			ee()->email->subject($subject);
			ee()->email->message(entities_to_ascii($body));
			ee()->email->send();

			$sent[] = $address['email'];
		}
	}
}
// END CLASS

// EOF
