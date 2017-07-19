<?php

namespace EllisLab\Addons\Forum\Service;

/**
 * Notifications class for Forum Module
 * Abstracted from mod files, only used for notifications after Spam queue moderation
 */
class Notifications {

	/**
	 * @var object EllisLab\ExpressionEngine\Model\Member
	 */
	protected $member;

	/**
	 * @var array
	 */
	protected $recipients = array();

	/**
	 * @var array
	 */
	protected $admin_recipients = array();

	/**
	 * @var array
	 */
	protected $variables = array();

	/**
	 * Constructor
	 *
	 * @param object $topic EllisLab\Addons\Forum\Model\Topic
	 * @param string $url URL to the forum post
	 * @param object $reply EllisLab\Addons\Forum\Model\Post
	 */
	public function __construct($topic, $url, $reply = NULL)
	{
		if ($reply)
		{
			$member = $reply->Author;
		}
		else
		{
			$member = $topic->Author;
		}

		$this->setupAdminRecipients($topic, $reply);
		$this->setupRecipients($topic, $member);
		$this->setupVariables($topic, $url, $reply);
	}

	/**
	 * Setup Administrator Recipients
	 *
	 * @param  object $topic EllisLab\Addons\Forum\Model\Topic
	 * @param  object $reply EllisLab\Addons\Forum\Model\Post
	 * @return void
	 */
	private function setupAdminRecipients($topic, $reply = NULL)
	{
		// from notification preferences: Board, Forum, and Forum parent ("category" Forum)
		$notify_email_str = '';

		if ($reply)
		{
			$notify_email_str .= $topic->Board->board_notify_emails;
			$notify_email_str .= ','.$topic->Forum->forum_notify_emails;
		}
		else
		{
			$notify_email_str .= $topic->Board->board_notify_emails_topics;
			$notify_email_str .= ','.$topic->Forum->forum_notify_emails_topics;
		}

		$notify_moderators_topics = $topic->Forum->forum_notify_moderators_topics;
		$notify_moderators_replies = $topic->Forum->forum_notify_moderators_replies;

		$category = $topic->Forum->Category;

		if ($category)
		{
			$notify_moderators_topics = $category->forum_notify_moderators_topics OR $notify_moderators_topics;
			$notify_moderators_replies = $category->forum_notify_moderators_replies OR $notify_moderators_replies;

			if ($reply)
			{
				$notify_email_str .= ','.$category->forum_notify_emails;
			}
			else
			{
				$notify_email_str .= ','.$category->forum_notify_emails_topics;
			}
		}

		// moderators
		if (
				($reply && $notify_moderators_replies) OR
				( ! $reply && $notify_moderators_topics)
			)
		{
			// can be member ID or group ID based
			ee()->db->select('email');
			ee()->db->from('members, forum_moderators');
			ee()->db->where('(exp_members.member_id = exp_forum_moderators.mod_member_id OR exp_members.group_id = exp_forum_moderators.mod_group_id)', NULL, FALSE);
			ee()->db->where('exp_forum_moderators.mod_forum_id', $topic->forum_id);

			$query = ee()->db->get();

			if ($query->num_rows() > 0)
			{
				foreach ($query->result() as $row)
				{
					$notify_email_str .= ','.$row->email;
				}
			}
		}

		$addresses = array_unique($this->commaDelimToArray($notify_email_str));
		$addresses = $this->structureAddresses($addresses);

		// Remove Current User Email
		// We don't want to send an admin notification if the person
		// leaving the comment is an admin in the notification list
		unset($addresses[ee()->session->userdata('email')]);

		$this->admin_recipients = $addresses;
	}

	/**
	 * Setup Recipients
	 *
	 * @param  object $topic EllisLab\Addons\Forum\Model\Topic
	 * @param  object $member EllisLab\ExpressionEngine\Model\Member
	 * @return void
	 */
	private function setupRecipients($topic, $member)
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
				in_array($member->member_id, explode('|', $row->ignore_list)))
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

	/**
	 * Setup Variables
	 *
	 * @param  object $topic EllisLab\Addons\Forum\Model\Topic
	 * @param  string $url URL of the post
	 * @param  object $reply EllisLab\Addons\Forum\Model\Post
	 * @return void
	 */
	private function setupVariables($topic, $url, $reply = NULL)
	{
		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'parse_images'   => FALSE,
			'allow_headings' => FALSE,
			'smileys'        => FALSE,
			'word_censor'    => (ee()->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE)
		);

		$body = ($reply) ? $reply->body : $topic->body;
		$body = ee()->typography->parse_type(
			$body,
			array(
				'text_format'   => 'none',
				'html_format'   => 'none',
				'auto_links'    => 'n',
				'allow_img_url' => 'n'
			)
		);

		$action_id  = ee()->functions->fetch_action_id('Forum', 'delete_subscription');

		$this->variables = array(
			'name_of_poster' => ($reply) ? $reply->Author->screen_name : $topic->Author->screen_name,
			'forum_name'     => $topic->Board->board_label,
			'title'          => $topic->title,
			'body'           => $body,
			'topic_id'       => $topic->topic_id,
			'thread_url'     => ee()->input->remove_session_id($url),
			'post_url'       => ($reply) ? $this->getForumUrl($topic)."/viewreply/{$reply->post_id}/" : ee()->input->remove_session_id($url),
			'notification_removal_url' => ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id={subscription}&board_id='.$topic->board_id,
		 );
	}

	/**
	 * Get Forum URL
	 *
	 * @param  object $topic EllisLab\Addons\Forum\Model\Topic
	 * @return string the forum's base URL
	 */
	private function getForumUrl($topic)
	{
		static $url;

		if ($url)
		{
			return $url;
		}

		if (ee()->config->item('use_forum_url') == 'y')
		{
			$url = $topic->Board->board_forum_url;
		}
		else
		{
			$url = ee()->functions->create_url($topic->Board->board_forum_trigger);
		}

		$overrides = ee()->config->get_cached_site_prefs($topic->Board->board_site_id);
		return $url = parse_config_variables($url, $overrides);
	}

	/**
	 * Send User Notification Emails
	 *
	 * @return void
	 */
	public function send_admin_notifications()
	{
		if (empty($this->admin_recipients))
		{
			return;
		}

		$template = ee()->functions->fetch_email_template('admin_notify_forum_post');
		$this->send($template, $this->admin_recipients, ee()->config->item('webmaster_email'));
	}

	/**
	 * Send User Notification Emails
	 *
	 * @return void
	 */
	public function send_user_notifications()
	{
		if (empty($this->recipients))
		{
			return;
		}

		$template = ee()->functions->fetch_email_template('forum_post_notification');
		$this->send($template, $this->recipients, ee()->config->item('webmaster_email'));
	}

	/**
	 * Structure the email addresses
	 * @param  array $emails Array of email addresses
	 * @return array Structured array that will be used by send()
	 */
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

	/**
	 * Send the Emails
	 *
	 * @param  string $template The template to use
	 * @param  array $to The email addresses to send to, formatted by structureAddresses()
	 * @param  string $replyto The email to use for the replyto header
	 * @return void
	 */
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
				$body = ee()->functions->var_swap($body, array('subscription' => $address['subscription']));
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
