<?php

namespace EllisLab\Addons\Comment\Service;

class Notifications {

	protected $topic;
	protected $reply;
	protected $member;
	protected $recipients = array();
	protected $variables = array();

	public function __construct($topic, $url, $reply = NULL)
	{
		$this->setupRecipients($topic);
		$this->setupVariables($topic, $url, $reply);

		$this->topic = $topic;
		$this->reply = $reply;

		if ($this->reply)
		{
			$this->member = $this->reply->Author;
		}
		else
		{
			$this->member = $this->topic->Author;
		}
	}

	private function setupRecipients($topic)
	{
		$query = ee()->db->select('s.hash, s.notification_sent, m.member_id, m.email, m.screen_name, m.smart_notifications, m.ignore_list')
			->from('forum_subscriptions AS s')
			->join('members AS m', 'm.member_id = s.member_id', 'left')
			->where('s.topic_id', $topic->topic_id)
			->get();

		// No addresses?  Bail...
		if ($query->num_rows() == 0)
		{
			$this->recipients = array();
			return;
		}

		foreach ($query->result() as $row)
		{
			if ($row->smart_notifications == 'y' AND $row->notification_sent == 'y')
			{
				continue;
			}

			// Don't send notifications if the recipient is ignoring the author
			if ($row->ignore_list != '' &&
				in_array($this->member->member_id, explode('|', $row->ignore_list)))
			{
				continue;
			}

			$this->recipients[$row->email] = array(
				'member_id' => $row->member_id,
				'email' => $row->email,
				'name_of_recipient' => $row->screen_name,
				'subscription' => $row->hash,
			);
		}
	}

	private function setupVariables($topic, $url, $reply = NULL)
	{
		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'parse_images'		=> FALSE,
			'allow_headings'	=> FALSE,
			'smileys'			=> FALSE,
			'word_censor'		=> (ee()->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE)
		);

		$body = ($reply) ? $reply->body : $topic->body;
		$body = ee()->typography->parse_type(
			$body,
			array(
				'text_format'	=> 'none',
				'html_format'	=> 'none',
				'auto_links'	=> 'n',
				'allow_img_url' => 'n'
			)
		);

		$this->variables = array(
			'name_of_poster'	=> ee()->session->userdata('screen_name'),
			'forum_name'		=> $topic->Board->board_label,
			'title'				=> $topic->title,
			'body'				=> $body,
			'topic_id'			=> $topic->topic_id,
			'thread_url'		=> ee()->input->remove_session_id($url),
			'post_url'			=> ($reply) ? $this->getForumUrl()."viewreply/{$reply->post_id}/" : ee()->input->remove_session_id($url)
		 );
	}

	function orig()
	{
		// Email Notifications
		$notify_addresses = '';

		if ($this->current_request == 'newtopic')
		{
			$notify_addresses .= ($this->fetch_pref('board_notify_emails_topics') != '') ? ','.$this->fetch_pref('board_notify_emails_topics') : '';
		}
		else
		{
			$notify_addresses .= ($this->fetch_pref('board_notify_emails') != '') ? ','.$this->fetch_pref('board_notify_emails') : '';
		}

		// Fetch forum notification addresses
		if ($this->current_request == 'newtopic')
		{
			$notify_addresses .= ($fdata['forum_notify_emails'] != '') ? ','.$fdata['forum_notify_emails'] : '';
		}
		else
		{
			$notify_addresses .= ($fdata['forum_notify_emails_topics'] != '') ? ','.$fdata['forum_notify_emails_topics'] : '';
		}

		// Category Notification Prefs
		$cmeta = $this->_fetch_forum_metadata($fdata['forum_parent']);

		if (FALSE !== $cmeta)
		{
			if ($this->current_request == 'newtopic')
			{
				if ($cmeta[$fdata['forum_parent']]['forum_notify_emails'] != '')
				{
					$notify_addresses .= ','.$cmeta[$fdata['forum_parent']]['forum_notify_emails'];
				}
			}
			else
			{
				if ($cmeta[$fdata['forum_parent']]['forum_notify_emails_topics'] != '')
				{
					$notify_addresses .= ','.$cmeta[$fdata['forum_parent']]['forum_notify_emails_topics'];
				}
			}
		}

		// Fetch moderator addresses
		if ((isset($fdata['forum_notify_moderators']) && $fdata['forum_notify_moderators'] == 'y') OR
			($this->current_request == 'newtopic' && $cmeta[$fdata['forum_parent']]['forum_notify_moderators_topics'] == 'y') OR
			($this->current_request != 'newtopic' && $cmeta[$fdata['forum_parent']]['forum_notify_moderators_replies'] == 'y')
			)
		{
			ee()->db->select('email');
			ee()->db->from('members, forum_moderators');
			ee()->db->where('(exp_members.member_id = exp_forum_moderators.mod_member_id OR exp_members.group_id =  exp_forum_moderators.mod_group_id)', NULL, FALSE);
			ee()->db->where('exp_forum_moderators.mod_forum_id', $fdata['forum_id']);

			$query = ee()->db->get();

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$notify_addresses .= ','.$row['email'];
				}
			}
		}

		$notify_addresses = str_replace(' ', '', $notify_addresses);

		// Remove Current User Email
		// We don't want to send an admin notification if the person
		// leaving the comment is an admin in the notification list

		if ($notify_addresses != '')
		{
			if (strpos($notify_addresses, ee()->session->userdata('email')) !== FALSE)
			{
				$notify_addresses = str_replace(ee()->session->userdata('email'), "", $notify_addresses);
			}

			// Remove multiple commas
			$notify_addresses = reduce_multiples($notify_addresses, ',', TRUE);
		}

		// Strip duplicate emails
		// And while we're at it, create an array
		if ($notify_addresses != '')
		{
			$notify_addresses = array_unique(explode(",", $notify_addresses));
		}

		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'parse_images' => FALSE
		));

		$query = ee()->db->query("SELECT title FROM exp_forum_topics WHERE topic_id = '".$data['topic_id']."'");

		$title = $query->row('title') ;
		$body = ee()->typography->parse_type($data['body'],
										array(
												'text_format'	=> 'none',
												'html_format'	=> 'none',
												'auto_links'	=> 'n',
												'allow_img_url' => 'n'
											)
									);

		// Send admin notification
		if (is_array($notify_addresses) AND count($notify_addresses) > 0)
		{
			$swap = array(
							'name_of_poster'	=> ee()->session->userdata('screen_name'),
							'forum_name'		=> $this->fetch_pref('board_label'),
							'title'				=> $title,
							'body'				=> $body,
							'topic_id'			=> $data['topic_id'],
							'thread_url'		=> ee()->input->remove_session_id($redirect),
							'post_url'			=> (isset($data['post_id'])) ? $this->forum_path()."viewreply/{$data['post_id']}/" : ee()->input->remove_session_id($redirect)
						 );

			$template = ee()->functions->fetch_email_template('admin_notify_forum_post');
			$email_tit = ee()->functions->var_swap($template['title'], $swap);
			$email_msg = ee()->functions->var_swap($template['data'], $swap);

			// Send email
			ee()->load->library('email');
			ee()->email->wordwrap = TRUE;

			// Load the text helper
			ee()->load->helper('text');

			foreach ($notify_addresses as $val)
			{
				ee()->email->EE_initialize();
				ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
				ee()->email->to($val);
				ee()->email->reply_to($val);
				ee()->email->subject($email_tit);
				ee()->email->message(entities_to_ascii($email_msg));
				ee()->email->send();
			}
		}
	}

	private function getForumUrl($topic)
	{
		static $basepath;

		if ($basepath)
		{
			return $basepath;
		}

		if (ee()->config->item('use_forum_url') == 'y')
		{
			$basepath = $topic->Board->board_forum_url;
		}
		else
		{
			$basepath = ee()->functions->create_url($topic->Board->board_forum_trigger);
		}

		$overrides = ee()->config->get_cached_site_prefs($topic->site_id);
		return $basepath = parse_config_variables($basepath, $overrides);
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
