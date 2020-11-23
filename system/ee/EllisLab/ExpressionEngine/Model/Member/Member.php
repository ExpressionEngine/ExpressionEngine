<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Member;

use DateTimeZone;
use EllisLab\ExpressionEngine\Model\Content\ContentModel;
use EllisLab\ExpressionEngine\Model\Member\Display\MemberFieldLayout;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutInterface;

/**
 * Member
 *
 * A member of the website.  Represents the user functionality
 * provided by the Member module.  This is a single user of
 * the website.
 */
class Member extends ContentModel {

	protected static $_primary_key = 'member_id';
	protected static $_table_name = 'members';
	protected static $_gateway_names = array('MemberGateway', 'MemberFieldDataGateway');

	protected static $_hook_id = 'member';

	protected static $_typed_columns = array(
		'cp_homepage_channel' => 'json'
	);

	protected static $_relationships = array(
		'MemberGroup' => array(
			'type' => 'belongsTo'
		),
		'HTMLButtons' => array(
			'type' => 'hasMany',
			'model' => 'HTMLButton',
			'to_key' => 'member_id'
		),
		'LastAuthoredTemplates' => array(
			'type' => 'hasMany',
			'model' => 'Template',
			'to_key' => 'last_author_id',
			'weak' => TRUE
		),
		'AuthoredChannelEntries' => array(
			'type' => 'hasMany',
			'model' => 'ChannelEntry',
			'to_key' => 'author_id'
		),
		'LastAuthoredSpecialtyTemplates' => array(
			'type' => 'hasMany',
			'model' => 'SpecialtyTemplate',
			'to_key' => 'last_author_id',
			'weak' => TRUE
		),
		'UploadedFiles' => array(
			'type' => 'hasMany',
			'model' => 'File',
			'to_key' => 'uploaded_by_member_id',
			'weak' => TRUE
		),
		'ModifiedFiles' => array(
			'type' => 'hasMany',
			'model' => 'File',
			'to_key' => 'modified_by_member_id',
			'weak' => TRUE
		),
		'VersionedChannelEntries' => array(
			'type' => 'hasMany',
			'model' => 'ChannelEntryVersion',
			'to_key' => 'author_id'
		),
		'EmailConsoleCaches' => array(
			'type' => 'hasMany',
			'model' => 'EmailConsoleCache'
		),
		'SearchLogs' => array(
			'type' => 'hasMany',
			'model' => 'SearchLog'
		),
		'CpLogs' => array(
			'type' => 'hasMany',
			'model' => 'CpLog'
		),
		'ChannelEntryAutosaves' => array(
			'type' => 'hasMany',
			'model' => 'ChannelEntryAutosave',
			'to_key' => 'author_id'
		),
		'Comments' => array(
			'type' => 'hasMany',
			'model' => 'Comment',
			'to_key' => 'author_id'
		),
		'TemplateRevisions' => array(
			'type' => 'hasMany',
			'model' => 'RevisionTracker',
			'to_key' => 'item_author_id',
			'weak' => TRUE
		),
		'SiteStatsIfLastMember' => array(
			'type' => 'hasOne',
			'model' => 'Stats',
			'to_key' => 'recent_member_id',
			'weak' => TRUE
		),
		'CommentSubscriptions' => array(
			'type' => 'hasMany',
			'model' => 'CommentSubscription'
		),
		'NewsView' => array(
			'type' => 'hasOne',
			'model' => 'MemberNewsView'
		),
		'AuthoredConsentRequests' => array(
			'type' => 'hasMany',
			'model' => 'ConsentRequestVersion',
			'to_key' => 'author_id',
			'weak' => TRUE
		),
		'ConsentAuditLogs' => array(
			'type' => 'hasMany',
			'model' => 'ConsentAuditLog'
		),
		'Consents' => array(
			'type' => 'hasMany',
			'model' => 'Consent'
		),
		'SentMessages' => [
			'type' => 'hasMany',
			'model' => 'Message',
			'to_key' => 'sender_id'
		],
		'SentMessageReceipts' => [
			'type' => 'hasMany',
			'model' => 'MessageCopy',
			'to_key' => 'sender_id'
		],
		'SentAttachments' => [
			'type' => 'hasMany',
			'model' => 'MessageAttachment',
			'to_key' => 'sender_id'
		],
		'ReceivedMessages' => [
			'type' => 'hasAndBelongsToMany',
			'model' => 'Message',
			'pivot' => [
				'table' => 'message_copies',
				'left' => 'recipient_id',
				'right' => 'message_id'
			]
		],
		'ReceivedMessageReceipts' => [
			'type' => 'hasMany',
			'model' => 'MessageCopy',
			'to_key' => 'recipient_id'
		],
		'MessageFolders' => [
			'type' => 'hasOne',
			'model' => 'MessageFolder'
		],
		'ListedMembers' => [
			'type' => 'hasMany',
			'model' => 'ListedMember'
		],
		'ListedByMembers' => [
			'type' => 'hasMany',
			'model' => 'ListedMember',
			'to_key' => 'listed_member'
		],
		'RememberMe' => [
			'type' => 'hasMany'
		],
		'Session' => [
			'type' => 'hasMany'
		],
		'Online' => [
			'type' => 'hasMany',
			'model' => 'OnlineMember'
		]
	);

	protected static $_field_data = array(
		'field_model'     => 'MemberField',
		'structure_model' => 'MemberGroup',
	);

	protected static $_validation_rules = array(
		'group_id'        => 'required|isNatural|validateGroupId',
		'username'        => 'required|unique|validateUsername',
		'screen_name'     => 'validateScreenName',
		'email'           => 'required|email|uniqueEmail|validateEmail',
		'password'        => 'required|validatePassword',
		'timezone'        => 'validateTimezone',
		'date_format'     => 'validateDateFormat',
		'time_format'     => 'enum[12,24]',
		'include_seconds' => 'enum[y,n]',
	);

	protected static $_events = array(
		'afterUpdate',
		'beforeDelete',
		'afterBulkDelete',
		'beforeInsert',
	);

	// Properties
	protected $member_id;
	protected $group_id;
	protected $username;
	protected $screen_name;
	protected $password;
	protected $salt;
	protected $unique_id;
	protected $crypt_key;
	protected $authcode;
	protected $email;
	protected $signature;
	protected $avatar_filename;
	protected $avatar_width;
	protected $avatar_height;
	protected $photo_filename;
	protected $photo_width;
	protected $photo_height;
	protected $sig_img_filename;
	protected $sig_img_width;
	protected $sig_img_height;
	protected $ignore_list;
	protected $private_messages;
	protected $accept_messages;
	protected $last_view_bulletins;
	protected $last_bulletin_date;
	protected $ip_address;
	protected $join_date;
	protected $last_visit;
	protected $last_activity;
	protected $total_entries;
	protected $total_comments;
	protected $total_forum_topics;
	protected $total_forum_posts;
	protected $last_entry_date;
	protected $last_comment_date;
	protected $last_forum_post_date;
	protected $last_email_date;
	protected $in_authorlist;
	protected $accept_admin_email;
	protected $accept_user_email;
	protected $notify_by_default;
	protected $notify_of_pm;
	protected $display_avatars;
	protected $display_signatures;
	protected $parse_smileys;
	protected $smart_notifications;
	protected $language;
	protected $timezone;
	protected $time_format;
	protected $date_format;
	protected $include_seconds;
	protected $profile_theme;
	protected $forum_theme;
	protected $tracker;
	protected $template_size;
	protected $notepad;
	protected $notepad_size;
	protected $bookmarklets;
	protected $quick_links;
	protected $quick_tabs;
	protected $show_sidebar;
	protected $pmember_id;
	protected $rte_enabled;
	protected $rte_toolset_id;
	protected $cp_homepage;
	protected $cp_homepage_channel;
	protected $cp_homepage_custom;

	/**
	 * Generate unique ID and crypt key for new members
	 */
	public function onBeforeInsert()
	{
		$this->setProperty('unique_id', ee('Encrypt')->generateKey());
		$this->setProperty('crypt_key', ee('Encrypt')->generateKey());
	}

	/**
	 * Log email and password changes
	 */
	public function onAfterUpdate($changed)
	{
		parent::onAfterUpdate($changed);

		if (REQ == 'CP')
		{
			if (isset($changed['password']))
			{
				// Did the hash length change? Then the algorithm changed
				$password_change_type = (strlen($changed['password']) != strlen($this->password)) ? 'member_hash_algo_changed' : 'member_changed_password';

				ee()->logger->log_action(sprintf(
					lang($password_change_type),
					$this->username,
					$this->member_id
				));
			}

			if (isset($changed['email']) && ! $this->isAnonymized())
			{
				ee()->logger->log_action(sprintf(
					lang('member_changed_email'),
					$this->username,
					$this->member_id,
					$changed['email'],
					$this->email
				));
			}

			if (isset($changed['group_id']))
			{
				ee()->logger->log_action(sprintf(
					lang('member_changed_member_group'),
					$this->MemberGroup->group_title,
					$this->username,
					$this->member_id
				));

				ee()->session->set_cache(__CLASS__, "getStructure({$this->group_id})", NULL);
			}
		}

		if (isset($changed['email']))
		{
			// this operation could be expensive on models so use a direct MySQL UPDATE query
			ee('db')->update('comments', ['email' => $this->email], ['author_id' => $this->member_id]);

			// email the original email address telling them of the change
			$this->notifyOfChanges('email_changed_notification', $changed['email']);
		}

		if (isset($changed['password']))
		{
			if (strlen($changed['password']) == strlen($this->password))
			{
				// email the current email address telling them their password changed
				$this->notifyOfChanges('password_changed_notification', $this->email);
			}
		}

		if (isset($changed['screen_name']))
		{
			// these operations could be expensive on models so use a direct MySQL UPDATE query
			ee('db')->update('comments', ['name' => $this->screen_name], ['author_id' => $this->member_id]);

			if (ee()->config->item('forum_is_installed') == 'y')
			{
				ee('db')->update('forums', ['forum_last_post_author' => $this->screen_name], ['forum_last_post_author_id' => $this->member_id]);
				ee('db')->update('forum_moderators', ['mod_member_name' => $this->screen_name], ['mod_member_id' => $this->member_id]);
			}
		}

		// invalidate reset codes if the user's email or password is changed
		if (isset($changed['email']) OR isset($changed['password']))
		{
			$this->getModelFacade()->get('ResetPassword')
				->filter('member_id', $this->member_id)
				->delete();
		}
	}

	/**
	 * Notify of Changes
	 *
	 * @param  string $type Specialty template type
	 * @param  string $to   email address to send the notification to
	 * @return void
	 */
	private function notifyOfChanges($type, $to)
	{
		$vars = [
			'name'		=> $this->screen_name,
			'username'  => $this->username,
			'site_name'	=> ee()->config->item('site_name'),
			'site_url'	=> ee()->config->item('site_url')
		];

		$template = ee()->functions->fetch_email_template($type);
		$subject = $template['title'];
		$message = $template['data'];

		foreach ($vars as $var => $value)
		{
			$subject = str_replace('{'.$var.'}', $value, $subject);
			$message = str_replace('{'.$var.'}', $value, $message);
		}

		ee()->load->library('email');
		ee()->email->wordwrap = true;
		ee()->email->mailtype = ee()->config->item('mail_format');
		ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
		ee()->email->to($to);
		ee()->email->subject($subject);
		ee()->email->message($message);
		ee()->email->send();
	}

	/**
	 * Zero-out member ID data in associated models
	 */
	public function onBeforeDelete()
	{
		parent::onBeforeDelete();

		$this->UploadedFiles->uploaded_by_member_id = 0;
		$this->UploadedFiles->save();

		$this->ModifiedFiles->modified_by_member_id = 0;
		$this->ModifiedFiles->save();

		$this->LastAuthoredSpecialtyTemplates->last_author_id = 0;
		$this->LastAuthoredSpecialtyTemplates->save();

		$this->LastAuthoredTemplates->last_author_id = 0;
		$this->LastAuthoredTemplates->save();

		$this->TemplateRevisions->item_author_id = 0;
		$this->TemplateRevisions->save();
	}

	public static function onAfterBulkDelete()
	{
		ee()->stats->update_member_stats();

		// Quick and dirty private message count update; due to the order of
		// events, we can't seem to reliably do this in model delete events
		// Copied from Stats controller
		$member_message_count = ee()->db->query('SELECT COUNT(*) AS count, recipient_id FROM exp_message_copies WHERE message_read = "n" GROUP BY recipient_id ORDER BY count DESC');

		$pm_count = [];
		foreach ($member_message_count->result() as $row)
		{
			$pm_count[] = [
				'member_id' => $row->recipient_id,
				'private_messages' => $row->count
			];
		}

		if ( ! empty($pm_count))
		{
			ee()->db->update_batch('members', $pm_count, 'member_id');
		}
	}

	/**
	 * Gets the member's name
	 *
	 * @return string The member's name
	 */
	public function getMemberName()
	{
		return $this->screen_name ?: $this->username;
	}

	/**
	 * Gets the HTML buttons for a given site id for this member. Falls back
	 * to the site's defined HTML buttons
	 *
	 * @param int $site_id The site ID
	 * @return EllisLab\ExpressionEngine\Library\Data\Collection A collection of HTMLButton entities
	 */
	public function getHTMLButtonsForSite($site_id)
	{
		$buttons = $this->getModelFacade()->get('HTMLButton')
			->filter('site_id', $site_id)
			->filter('member_id', $this->member_id)
			->order('tag_order')
			->all();

		if ( ! $buttons->count())
		{
			$buttons = $this->getModelFacade()->get('HTMLButton')
				->filter('site_id', $site_id)
				->filter('member_id', 0)
				->order('tag_order')
				->all();
		}

		return $buttons;
	}

	/**
	 * Updates the author's total_entries and total_comments stats based on
	 * the ChannelEntry and Comment counts.
	 *
	 * @return void
	 */
	public function updateAuthorStats()
	{

		if(ee()->config->item('ignore_entry_stats') == 'y') {
			return;
		}

		// open, non-expired entries only
		$entries = $this->getModelFacade()->get('ChannelEntry')
			->filter('author_id', $this->member_id)
			->filter('status', '!=', 'closed')
			->filterGroup()
				->filter('expiration_date', 0)
				->orFilter('expiration_date', '>', ee()->localize->now)
			->endFilterGroup()
			->fields('entry_date');

		$total_entries = $entries->count();

		$recent_entry = $entries->order('entry_date', 'desc')
			->first();

		$last_entry_date = ($recent_entry) ? $recent_entry->entry_date : 0;

		// open comments only
		$comments = $this->getModelFacade()->get('Comment')
			->filter('author_id', $this->member_id)
			->filter('status', 'o')
			->fields('comment_date');

		$total_comments = $comments->count();

		$recent_comment = $comments->order('comment_date', 'desc')
			->first();

		$last_comment_date = ($recent_comment) ? $recent_comment->comment_date : 0;

		$this->setProperty('last_comment_date', $last_comment_date);
		$this->setProperty('last_entry_date', $last_entry_date);
		$this->setProperty('total_comments', $total_comments);
		$this->setProperty('total_entries', $total_entries);

		$this->save();
	}

	/**
	 * Returns the URL to use for the homepage for this member, otherwise we'll
	 * use the default of 'homepage'. We prioritize on the Member's preferences
	 * then the groups preferences, falling back to the default.
	 *
	 * @param	int	Optional site ID to get member homepage for, defaults to current site
	 * @return EllisLab\ExpressionEngine\Library\CP\URL The URL
	 */
	public function getCPHomepageURL($site_id = NULL)
	{
		$cp_homepage = NULL;
		$cp_homepage_custom = 'homepage';

		if ( ! $site_id)
		{
			$site_id = ee()->config->item('site_id');
		}

		// Make sure to get the correct site, revert once issue #1285 is fixed
		$member_group = $this->getModelFacade()->get('MemberGroup')
			->filter('group_id', $this->group_id)
			->filter('site_id', $site_id)
			->first();

		if ( ! empty($this->cp_homepage))
		{
			$cp_homepage = $this->cp_homepage;
			$cp_homepage_channel = $this->cp_homepage_channel;
			$cp_homepage_custom = $this->cp_homepage_custom;

			// Site created after setting was saved, no channel setting will be available
			if ($this->cp_homepage == 'publish_form')
			{
				// No channels or bad channel? Can't go to the publish page
				if ( ! isset($cp_homepage_channel[$site_id]))
				{
					$cp_homepage = '';
				}
				else
				{
					$cp_homepage_channel = $cp_homepage_channel[$site_id];
				}
			}
		}
		elseif ( ! empty($member_group->cp_homepage))
		{
			$cp_homepage = $member_group->cp_homepage;
			$cp_homepage_channel = $member_group->cp_homepage_channel;
			$cp_homepage_custom = $member_group->cp_homepage_custom;
		}

		switch ($cp_homepage) {
			case 'entries_edit':
				$url = ee('CP/URL', 'publish/edit');
				break;
			case 'publish_form':
				$url = ee('CP/URL', 'publish/create/'.$cp_homepage_channel);
				break;
			case 'custom':
				$url = ee('CP/URL', $cp_homepage_custom);
				break;
			default:
				$url = ee('CP/URL', 'homepage');
				break;
		}

		return $url;
	}

	/**
	 * A link back to the owning member group object.
	 *
	 * @return	Structure	A link back to the Structure object that defines
	 *						this Content's structure.
	 */
	public function getStructure()
	{
		if ( ! $structure = ee()->session->cache(__CLASS__, "getStructure({$this->group_id})"))
		{
			$structure = $this->MemberGroup;
			ee()->session->set_cache(__CLASS__, "getStructure({$this->group_id})", $structure);
		}

		return $structure;
	}

	/**
	 * Modify the default layout for member fields
	 */
	public function getDisplay(LayoutInterface $layout = NULL)
	{
		$layout = $layout ?: new MemberFieldLayout();

		return parent::getDisplay($layout);
	}

	/**
	 * Ensures the group ID exists and the member has permission to add to the group
	 */
	public function validateGroupId($key, $group_id)
	{
		$member_groups = $this->getModelFacade()->get('MemberGroup');

		if (ee()->session->userdata('group_id') != 1)
		{
			$member_groups->filter('is_locked', 'n');
		}

		if ( ! in_array($group_id, $member_groups->all()->pluck('group_id')))
		{
			return 'invalid_group_id';
		}

		return TRUE;
	}

	/**
	 * Ensures the username doesn't have invalid characters, is the correct length, and isn't banned
	 */
	public function validateUsername($key, $username)
	{
		if (preg_match("/[\|'\"!<>\{\}]/", $username))
		{
			return 'invalid_characters_in_username';
		}

		// Is username min length correct?
		$un_length = ee()->config->item('un_min_len');
		if (strlen($username) < ee()->config->item('un_min_len'))
		{
			return sprintf(lang('username_too_short'), $un_length);
		}

		if (strlen($username) > USERNAME_MAX_LENGTH)
		{
			return 'username_too_long';
		}

		if ($this->isNew())
		{
			// Is username banned?
			if (ee()->session->ban_check('username', $username))
			{
				return 'username_taken';
			}
		}

		return TRUE;
	}

	/**
	 * Validation callback for screen name
	 */
	public function validateScreenName($key, $screen_name)
	{
		if (preg_match('/[\{\}<>]/', $screen_name))
		{
			return 'disallowed_screen_chars';
		}

		if (strlen($screen_name) > USERNAME_MAX_LENGTH)
		{
			return 'screenname_too_long';
		}

		if ($this->isNew())
		{
			if (ee()->session->ban_check('screen_name', $screen_name))
			{
				return 'screen_name_taken';
			}
		}

		return TRUE;
	}

	/**
	 * Validation callback for email field
	 */
	public function validateEmail($key, $email)
	{
		if (strlen($email) > USERNAME_MAX_LENGTH)
		{
			return 'email_too_long';
		}

		// Is email address banned?
		if (ee()->session->ban_check('email', $email))
		{
			return 'email_taken';
		}

		return TRUE;
	}

	/**
	 * Ensures the group ID exists and the member has permission to add to the group
	 */
	public function validatePassword($key, $password)
	{
		ee()->lang->loadfile('myaccount');

		$pw_length = ee()->config->item('pw_min_len');
		if (strlen($password) < $pw_length)
		{
			return sprintf(lang('password_too_short'), $pw_length);
		}

		// Is password max length correct?
		if (strlen($password) > PASSWORD_MAX_LENGTH)
		{
			return 'password_too_long';
		}

		//  Make UN/PW lowercase for testing
		$lc_user = strtolower($this->username);
		$lc_pass = strtolower($password);
		$nm_pass = strtr($lc_pass, 'elos', '3105');

		if ($lc_user == $lc_pass OR $lc_user == strrev($lc_pass) OR $lc_user == $nm_pass OR $lc_user == strrev($nm_pass))
		{
			return 'password_based_on_username';
		}

		// Are secure passwords required?
		if (bool_config_item('require_secure_passwords'))
		{
			$count = array('uc' => 0, 'lc' => 0, 'num' => 0);

			$pass = preg_quote($password, "/");

			$len = strlen($pass);

			for ($i = 0; $i < $len; $i++)
			{
				$n = substr($pass, $i, 1);

				if (preg_match("/^[[:upper:]]$/", $n))
				{
					$count['uc']++;
				}
				elseif (preg_match("/^[[:lower:]]$/", $n))
				{
					$count['lc']++;
				}
				elseif (preg_match("/^[[:digit:]]$/", $n))
				{
					$count['num']++;
				}
			}

			foreach ($count as $val)
			{
				if ($val == 0)
				{
					return 'not_secure_password';
				}
			}
		}

		// Does password exist in dictionary?
		// TODO: move out of form validation library
		ee()->load->library('form_validation');
		if (ee()->form_validation->_lookup_dictionary_word($lc_pass) == TRUE)
		{
			return 'password_in_dictionary';
		}

		return TRUE;
	}

	/**
	 * Hash and update Password
	 *
	 * 	Validation of $this->password takes the plaintext password. But it then
	 * 	needs to be prepped as a salted hash before it's saved to the database
	 * 	so we never store a plaintext password. It is imperative that this is done
	 * 	BEFORE a call to save() the model, so it's not even ever in the database
	 * 	temporarily or in a MySQL query log, potentially transmitted over HTTP even.
	 *
	 * @param  string $plaintext Plaintext password
	 * @return void
	 */
	public function hashAndUpdatePassword($plaintext)
	{
		ee()->load->library('auth');
		$hashed_password = ee()->auth->hash_password($plaintext);
		$this->setProperty('password', $hashed_password['password']);
		$this->setProperty('salt', $hashed_password['salt']);

		// kill all sessions for this member except for the current one
		$this->getModelFacade()->get('Session')
			->filter('member_id', $this->member_id)
			->filter('session_id', '!=', (string) ee()->session->userdata('session_id'))
			->delete();

		// invalidate any other sessions' remember me cookies
		ee()->remember->delete_others($this->member_id);
	}

	/**
	 * Override ContentModel method to set our field prefix
	 */
	public function getCustomFieldPrefix()
	{
		return 'm_field_id_';
	}

	/**
	 * Validates the template name checking for illegal characters and
	 * reserved names.
	 */
	public function validateTimezone($key, $value, $params, $rule)
	{
		if ( ! in_array($value, DateTimeZone::listIdentifiers()))
		{
			return 'invalid_timezone';
		}

		return TRUE;
	}

	/**
	 * Validates the template name checking for illegal characters and
	 * reserved names.
	 */
	public function validateDateFormat($key, $value, $params, $rule)
	{
		if ( ! preg_match("#^[a-zA-Z0-9_\.\-%/]+$#i", $value))
		{
			return 'invalid_date_format';
		}

		return TRUE;
	}

	/**
	 * Get the full URL to the Avatar
	 */
	public function getAvatarUrl()
	{
		if ($this->avatar_filename)
		{
			$avatar_url = ee()->config->slash_item('avatar_url');
			$avatar_fs_path = ee()->config->slash_item('avatar_path');

			if (file_exists($avatar_fs_path.'default/'.$this->avatar_filename))
			{
				$avatar_url .= 'default/';
			}

			return $avatar_url.$this->avatar_filename;
		}
		return '';
	}

	/**
	 * Get the full URL to the Signature Image
	 */
	public function getSignatureImageUrl()
	{
		if ($this->sig_img_filename)
		{
			return ee()->config->slash_item('sig_img_url').$this->sig_img_filename;
		}
		return '';
	}

	/**
	 * Anonymize a member record in order to comply with a GDPR Right to Erasure request
	 */
	public function anonymize()
	{
		// ---------------------------------------------------------------
		// 'member_anonymize' hook.
		// - Provides an opportunity for addons to perform anonymization on
		// any personal member data they've collected for a given member
		//
		if (ee()->extensions->active_hook('member_anonymize'))
		{
			ee()->extensions->call('member_anonymize', $this);
		}
		//
		// ---------------------------------------------------------------

		$username = 'anonymous'.$this->getId();
		$email = 'redacted'.$this->getId();
		$ip_address = ee('IpAddress')->anonymize($this->ip_address);

		$this->setProperty('group_id', 2); // Ban member
		$this->setProperty('username', $username);
		$this->setProperty('screen_name', $username);
		$this->setProperty('email', $email);
		$this->setProperty('ip_address', $ip_address);
		$this->setProperty('avatar_filename', '');
		$this->setProperty('sig_img_filename', '');
		$this->setProperty('photo_filename', '');

		foreach	($this->getCustomFields() as $field)
		{
			if ( ! $field->getItem('m_field_exclude_from_anon'))
			{
				$this->setProperty('m_field_id_'.$field->getId(), '');
			}
		}

		$this->save();

		if ($this->Session) $this->Session->delete();
		if ($this->Online) $this->Online->delete();
		if ($this->RememberMe) $this->RememberMe->delete();

		if ($this->CpLogs)
		{
			$this->CpLogs->mapProperty('ip_address', [ee('IpAddress'), 'anonymize']);
			$this->CpLogs->save();
		}

		if ($this->SearchLogs)
		{
			$this->SearchLogs->mapProperty('ip_address', [ee('IpAddress'), 'anonymize']);
			$this->SearchLogs->save();
		}

		if ($this->Comments)
		{
			$this->Comments->name = $username;
			$this->Comments->email = $email;
			$this->Comments->url = $email;
			$this->Comments->mapProperty('ip_address', [ee('IpAddress'), 'anonymize']);
			$this->Comments->save();
		}

		if ($this->AuthoredChannelEntries)
		{
			$this->AuthoredChannelEntries->mapProperty('ip_address', [ee('IpAddress'), 'anonymize']);
			$this->AuthoredChannelEntries->save();
		}

		ee()->logger->log_action(sprintf(
			lang('member_anonymized_member'),
			$this->member_id
		));
	}

	/**
	 * Has this member already been anonymized?
	 */
	public function isAnonymized()
	{
		return (bool) preg_match('/^redacted\d+$/', $this->email);
	}
}

// EOF
