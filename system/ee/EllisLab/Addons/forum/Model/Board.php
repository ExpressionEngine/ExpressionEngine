<?php

namespace EllisLab\Addons\Forum\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Board Model for the Forum
 *
 * A model representing a board in the Forum.
 *
 * @package		ExpressionEngine
 * @subpackage	Forum Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Board extends Model {

	protected static $_primary_key = 'board_id';
	protected static $_table_name = 'forum_boards';

	protected static $_typed_columns = array(
		'board_enabled'              => 'boolString',
		'board_site_id'              => 'int',
		'board_alias_id'             => 'int',
		'board_allow_php'            => 'boolString',
		'board_install_date'         => 'timestamp',
		'board_topics_perpage'       => 'int',
		'board_posts_perpage'        => 'int',
		'board_hot_topic'            => 'int',
		'board_max_post_chars'       => 'int',
		'board_post_timelock'        => 'int',
		'board_display_edit_date'    => 'boolString',
		'board_allow_img_urls'       => 'boolString',
		'board_auto_link_urls'       => 'boolString',
		'board_max_attach_perpost'   => 'int',
		'board_max_attach_size'      => 'int',
		'board_max_width'            => 'int',
		'board_max_height'           => 'int',
		'board_use_img_thumbs'       => 'boolString',
		'board_thumb_width'          => 'int',
		'board_thumb_height'         => 'int',
		'board_forum_permissions'    => 'serialized',
		'board_use_deft_permissions' => 'boolString',
		'board_recent_poster_id'     => 'int',
		'board_enable_rss'           => 'boolString',
		'board_use_http_auth'        => 'boolString',
	);

	protected static $_relationships = array(
		'Administrators' => array(
			'type'  => 'hasMany',
			'model' => 'Administrator'
		),
		'Attachments' => array(
			'type'  => 'hasMany',
			'model' => 'Attachment'
		),
		'Categories' => array(
			'type'  => 'hasMany',
			'model' => 'Forum'
		),
		'Forums' => array(
			'type'  => 'hasMany',
			'model' => 'Forum'
		),
		'Moderators' => array(
			'type'   => 'hasMany',
			'model'  => 'Moderator',
		),
		'Posts' => array(
			'type'  => 'hasMany',
			'model' => 'Post'
		),
		'Searches' => array(
			'type'  => 'hasMany',
			'model' => 'Search'
		),
		'Site' => array(
			'type'     => 'belongsTo',
			'model'    => 'ee:Site',
			'from_key' => 'board_site_id',
			'to_key'   => 'site_id',
			'inverse' => array(
				'name' => 'Board',
				'type' => 'hasMany'
			)
		),
		'Topics' => array(
			'type'  => 'hasMany',
			'model' => 'Topic'
		),
	);

	protected static $_validation_rules = array(
		'board_label'                => 'required',
		'board_name'                 => 'required|unique|alphaDash',
		'board_enabled'              => 'enum[y,n]',
		'board_forum_trigger'        => 'required|unique[board_site_id]|alphaDash|validateForumTrigger[board_site_id]',
		'board_allow_php'            => 'enum[y,n]',
		'board_php_stage'            => 'enum[i,o]',
		'board_forum_url'            => 'required',
		'board_default_theme'        => 'required',
		'board_upload_path'          => 'writable|validateUploadPath',
		'board_topic_order'          => 'enum[r,a,d]',
		'board_post_order'           => 'enum[a,d]',
		'board_display_edit_date'    => 'enum[y,n]',
		'board_allow_img_urls'       => 'enum[y,n]',
		'board_auto_link_urls'       => 'enum[y,n]',
		'board_use_img_thumbs'       => 'enum[y,n]',
		'board_forum_permissions'    => 'required',
		'board_use_deft_permissions' => 'enum[y,n]',
		'board_enable_rss'           => 'enum[y,n]',
		'board_use_http_auth'        => 'enum[y,n]',
	);

	protected static $_events = array(
		'beforeInsert',
		'afterSave',
		'afterDelete',
	);

	protected $board_id;
	protected $board_label;
	protected $board_name;
	protected $board_enabled;
	protected $board_forum_trigger;
	protected $board_site_id;
	protected $board_alias_id;
	protected $board_allow_php;
	protected $board_php_stage;
	protected $board_install_date;
	protected $board_forum_url;
	protected $board_default_theme;
	protected $board_upload_path;
	protected $board_topics_perpage;
	protected $board_posts_perpage;
	protected $board_topic_order;
	protected $board_post_order;
	protected $board_hot_topic;
	protected $board_max_post_chars;
	protected $board_post_timelock;
	protected $board_display_edit_date;
	protected $board_text_formatting;
	protected $board_html_formatting;
	protected $board_allow_img_urls;
	protected $board_auto_link_urls;
	protected $board_notify_emails;
	protected $board_notify_emails_topics;
	protected $board_max_attach_perpost;
	protected $board_max_attach_size;
	protected $board_max_width;
	protected $board_max_height;
	protected $board_attach_types;
	protected $board_use_img_thumbs;
	protected $board_thumb_width;
	protected $board_thumb_height;
	protected $board_forum_permissions;
	protected $board_use_deft_permissions;
	protected $board_recent_poster_id;
	protected $board_recent_poster;
	protected $board_enable_rss;
	protected $board_use_http_auth;

	/**
	 * Parses URL properties for any config variables
	 *
	 * @param str $name The name of the property to fetch
	 * @return mixed The value of the property
	 */
	public function __get($name)
	{
		$value = parent::__get($name);

		if ($name == 'board_forum_url' OR $name == 'board_upload_path')
		{
			$value = $this->parseConfigVars($value);
		}

		return $value;
	}

	public function validateUploadPath($key, $value, $params, $rule)
	{
		if ($value != '')
		{
			$value = $this->parseConfigVars($value);

			if ( ! @is_dir($value))
			{
				return 'invalid_upload_path';
			}
		}

		return TRUE;
	}

	/**
	 * URLs and paths may have a {base_url} or {base_path} in them, so we need
	 * to parse those but also take into account when a forum board belongs to
	 * another site
	 *
	 * @param string $value Value of property to parse
	 * @return string Upload path or URL with variables parsed
	 */
	private function parseConfigVars($value)
	{
		$overrides = array();

		if ($this->getProperty('board_site_id') != ee()->config->item('site_id'))
		{
			$overrides = ee()->config->get_cached_site_prefs($this->getProperty('board_site_id'));
		}

		return parse_config_variables($value, $overrides);
	}

	public function validateForumTrigger($key, $value, $params, $rule)
	{
		$field = $params[0];
		if ( ! $this->getProperty($field))
		{
			$rule->skip();
		}

		$count = $this->getFrontend()->get('TemplateGroup')
			->filter('group_name', $value)
			// ¯\_(ツ)_/¯ I'm not sure != makes sense, but it's what was
			// in 2.x so...
			->filter('site_id', '!=', $this->getProperty($field))
			->count();

		if ($count > 0)
		{
			return 'forum_trigger_unavailable';
		}

		return TRUE;
	}

	public function onBeforeInsert()
	{
		if ( ! $this->board_install_date)
		{
			$this->board_install_date = ee()->localize->now;
		}
	}

	public function onAfterSave()
	{
		$this->updateTriggers();
	}

	public function onAfterDelete()
	{
		$this->updateTriggers();
	}

	private function updateTriggers()
	{
		$model = $this->getFrontend();

		$sites = $model->get('ee:Site')->all();
		$boards = $model->get('forum:Board')
			->fields('board_forum_trigger', 'board_site_id')
			->all();

		foreach ($sites as $site)
		{
			$triggers = $boards->filter('board_site_id', $site->site_id)
				->pluck('board_forum_trigger');

			$site->site_system_preferences->forum_trigger = implode('|', $triggers);
			$site->save();
		}
	}

	public function set__board_forum_url($url)
	{
		$this->setRawProperty('board_forum_url', $this->addTrailingSlash($url));
	}

	public function set__board_upload_path($path)
	{
		$this->setRawProperty('board_upload_path', $this->addTrailingSlash($path));
	}

	private function addTrailingSlash($value)
	{
		if (isset($value)
			&& $value != ''
			&& substr($value, -1) != '/')
		{
			$value .= '/';
		}

		return $value;
	}

	public function getPermission($key)
	{
		$permissions = $this->getProperty('board_forum_permissions');

		if ( ! isset($permissions[$key]))
		{
			return array();
		}

		return explode('|', $permissions[$key]);
	}

	public function setPermission($key, $value)
	{
		$permissions = $this->getProperty('board_forum_permissions');

		if (is_array($value))
		{
			$value = implode('|', $value);
		}

		$permissions[$key] = $value;

		$this->setProperty('board_forum_permissions', $permissions);
	}

}

// EOF
