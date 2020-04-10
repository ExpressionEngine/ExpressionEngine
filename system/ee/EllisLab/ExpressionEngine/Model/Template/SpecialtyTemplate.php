<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Specialty Templates Model
 */
class SpecialtyTemplate extends Model {

	protected static $_primary_key = 'template_id';
	protected static $_table_name = 'specialty_templates';

	protected static $_hook_id = 'specialty_template';

	protected static $_typed_columns = array(
		'enable_template' => 'boolString'
	);

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'BelongsTo'
		),
		'LastAuthor' => array(
			'type'     => 'BelongsTo',
			'model'    => 'Member',
			'from_key' => 'last_author_id',
			'weak' => TRUE
		),
	);

	protected static $_validation_rules = array(
		'enable_template'  => 'enum[y,n]',
		'template_name'    => 'required',
		'data_title'       => 'required',
		'template_type'    => 'required',
		'template_subtype' => 'required',
		'template_data'    => 'required',
	);

	protected static $_events = array(
		'afterSave',
		'beforeSave',
	);

	protected $template_id;
	protected $site_id;
	protected $enable_template;
	protected $template_name;
	protected $data_title;
	protected $template_type;
	protected $template_subtype;
	protected $template_data;
	protected $template_notes;
	protected $edit_date;
	protected $last_author_id;

	public function getAvailableVariables()
	{
		$vars = array(
			'admin_notify_reg'              => array('name', 'username', 'email', 'site_name', 'control_panel_url'),
			'admin_notify_entry'            => array('channel_name', 'entry_title', 'entry_url', 'comment_url', 'cp_edit_entry_url', 'name', 'email'),
			'admin_notify_comment'          => array('channel_name', 'entry_title', 'entry_id', 'url_title', 'channel_id', 'comment_url_title_auto_path',  'comment_url', 'comment', 'comment_id', 'name', 'url', 'email', 'location', 'unwrap}{delete_link}{/unwrap', 'unwrap}{close_link}{/unwrap', 'unwrap}{approve_link}{/unwrap'),
			'admin_notify_forum_post'       => array('name_of_poster', 'forum_name', 'title', 'body', 'thread_url', 'post_url'),
			'mbr_activation_instructions'   => array('name',  'username', 'email', 'activation_url', 'site_name', 'site_url'),
			'email_changed_notification'    => ['name', 'username', 'site_name', 'site_url'],
			'forgot_password_instructions'  => array('name', 'username', 'reset_url', 'site_name', 'site_url'),
			'password_changed_notification' => ['name', 'username', 'site_name', 'site_url'],
			'decline_member_validation'     => array('name', 'username', 'site_name', 'site_url'),
			'validated_member_notify'       => array('name', 'username', 'email', 'site_name', 'site_url'),
			'comment_notification'          => array('name_of_commenter', 'name_of_recipient', 'channel_name', 'entry_title', 'entry_id', 'url_title', 'channel_id', 'comment_url_title_auto_path', 'comment_url', 'comment', 'notification_removal_url', 'site_name', 'site_url', 'comment_id'),

			'comments_opened_notification'  => array('name_of_recipient', 'channel_name', 'entry_title', 'entry_id', 'url_title', 'channel_id', 'comment_url_title_auto_path', 'comment_url', 'notification_removal_url', 'site_name', 'site_url', 'total_comments_added', 'comments', 'name_of_commenter', 'comment_id', 'comment', '/comments'),

			'forum_post_notification'       => array('name_of_recipient', 'name_of_poster', 'forum_name', 'title', 'thread_url', 'body', 'post_url'),
			'private_message_notification'  => array('sender_name', 'recipient_name','message_subject', 'message_content', 'site_url', 'site_name'),
			'pm_inbox_full'                 => array('sender_name', 'recipient_name', 'pm_storage_limit','site_url', 'site_name'),
			'forum_moderation_notification' => array('name_of_recipient', 'forum_name', 'moderation_action', 'title', 'thread_url'),
			'forum_report_notification'     => array('forum_name', 'reporter_name', 'author', 'body', 'reasons', 'notes', 'post_url')
		);

		return (isset($vars[$this->template_name])) ? $vars[$this->template_name] : array();
	}

	public function onAfterSave()
	{
		ee()->functions->clear_caching('all');
	}

	public function onBeforeSave()
	{
		$this->setProperty('edit_date', ee()->localize->now);
	}
}

// EOF
