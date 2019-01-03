<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\Forum\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Forum Model for the Forum
 *
 * A model representing a forum in the Forum.
 */
class Forum extends Model {

	protected static $_primary_key = 'forum_id';
	protected static $_table_name = 'forums';

	protected static $_typed_columns = array(
		'forum_id'                        => 'int',
		'board_id'                        => 'int',
		'forum_is_cat'                    => 'boolString',
		'forum_parent'                    => 'int',
		'forum_order'                     => 'int',
		'forum_total_topics'              => 'int',
		'forum_total_posts'               => 'int',
		'forum_last_post_id'              => 'int',
		'forum_last_post_date'            => 'timestamp',
		'forum_last_post_author_id'       => 'int',
		'forum_permissions'               => 'serialized',
		'forum_topics_perpage'            => 'int',
		'forum_posts_perpage'             => 'int',
		'forum_hot_topic'                 => 'int',
		'forum_max_post_chars'            => 'int',
		'forum_post_timelock'             => 'int',
		'forum_display_edit_date'         => 'boolString',
		'forum_allow_img_urls'            => 'boolString',
		'forum_auto_link_urls'            => 'boolString',
		'forum_notify_moderators_topics'  => 'boolString',
		'forum_notify_moderators_replies' => 'boolString',
		'forum_enable_rss'                => 'boolString',
		'forum_use_http_auth'             => 'boolString',
	);

	protected static $_relationships = array(
		'Board' => array(
			'type' => 'belongsTo'
		),
		'Category' => array(
			'type'     => 'belongsTo',
			'model'    => 'Forum',
			'from_key' => 'forum_parent',
			'to_key'   => 'forum_id'
		),
		'Forums' => array(
			'type'     => 'hasMany',
			'model'    => 'Forum',
			'from_key' => 'forum_id',
			'to_key'   => 'forum_parent'
		),
		'LastPost' => array(
			'type'     => 'hasOne',
			'model'    => 'Post',
			'from_key' => 'forum_last_post_id',
			'to_key'   => 'post_id',
			'weak'     => TRUE
		),
		'LastPostAuthor' => array(
			'type'     => 'belongsTo',
			'from_key' => 'forum_last_post_author_id',
			'to_key'   => 'member_id',
			'model'    => 'ee:Member',
			'weak'     => TRUE,
			'inverse' => array(
				'name' => 'Forum',
				'type' => 'hasMany',
				'weak' => TRUE
			)
		),
		'Moderators' => array(
			'type'   => 'hasMany',
			'model'  => 'Moderator',
			'to_key' => 'mod_forum_id'
		),
		'Posts' => array(
			'type'  => 'hasMany',
			'model' => 'Post'
		),
		'Topics' => array(
			'type'  => 'hasMany',
			'model' => 'Topic'
		),
	);

	protected static $_validation_rules = array(
		'forum_name'                      => 'required',
		'forum_is_cat'                    => 'enum[y,n]',
		'forum_status'                    => 'enum[o,c,a]',
		'forum_last_post_type'            => 'enum[p,a]',
		'forum_permissions'               => 'required',
		'forum_topic_order'               => 'enum[r,a,d]',
		'forum_post_order'                => 'enum[a,d]',
		'forum_hot_topic'                 => 'required',
		'forum_max_post_chars'            => 'required',
		'forum_display_edit_date'         => 'enum[y,n]',
		'forum_allow_img_urls'            => 'enum[y,n]',
		'forum_auto_link_urls'            => 'enum[y,n]',
		'forum_notify_moderators_topics'  => 'enum[y,n]',
		'forum_notify_moderators_replies' => 'enum[y,n]',
		'forum_enable_rss'                => 'enum[y,n]',
		'forum_use_http_auth'             => 'enum[y,n]',
	);

	protected static $_events = array(
		'beforeInsert',
	);

	protected $forum_id;
	protected $board_id;
	protected $forum_name;
	protected $forum_description;
	protected $forum_is_cat;
	protected $forum_parent;
	protected $forum_order;
	protected $forum_status;
	protected $forum_total_topics;
	protected $forum_total_posts;
	protected $forum_last_post_id;
	protected $forum_last_post_type;
	protected $forum_last_post_title;
	protected $forum_last_post_date;
	protected $forum_last_post_author_id;
	protected $forum_last_post_author;
	protected $forum_permissions;
	protected $forum_topics_perpage;
	protected $forum_posts_perpage;
	protected $forum_topic_order;
	protected $forum_post_order;
	protected $forum_hot_topic;
	protected $forum_max_post_chars;
	protected $forum_post_timelock;
	protected $forum_display_edit_date;
	protected $forum_text_formatting;
	protected $forum_html_formatting;
	protected $forum_allow_img_urls;
	protected $forum_auto_link_urls;
	protected $forum_notify_moderators_topics;
	protected $forum_notify_moderators_replies;
	protected $forum_notify_emails;
	protected $forum_notify_emails_topics;
	protected $forum_enable_rss;
	protected $forum_use_http_auth;

	public function getPermission($key)
	{
		$permissions = $this->getProperty('forum_permissions');

		if ( ! isset($permissions[$key]))
		{
			return array();
		}

		return explode('|', $permissions[$key]);
	}

	public function setPermission($key, $value)
	{
		$permissions = $this->getProperty('forum_permissions');

		if (is_array($value))
		{
			$value = implode('|', $value);
		}

		$permissions[$key] = $value;

		$this->setProperty('forum_permissions', $permissions);
	}

	public function onBeforeInsert()
	{
		$model = $this->getFrontend();

		$last_forum = $model->get('forum:Forum')
			->fields('forum_order')
			->order('forum_order', 'desc');

		// if it's a new category, just tack it on
		if ($this->getProperty('forum_is_cat'))
		{
			if ( ! $last_forum->first())
			{
				$this->setProperty('forum_order', 1);
				return;
			}

			$this->setProperty('forum_order', $last_forum->first()->forum_order + 1);
			return;
		}

		// - get the last forum in this category, if one exists
		$last = $last_forum->filter('forum_parent', $this->getProperty('forum_parent'))
			->first();

		if ($last)
		{
			// set this forum's order to one more, to be last in the category
			$order = $last->forum_order + 1;
		}
		else
		{
			// there weren't any forums in this category yet, so set to one higher
			// than the category itself
			$order = $model->get('forum:Forum')
				->fields('forum_order')
				->filter('forum_id', $this->getProperty('forum_parent'))
				->first()
				->forum_order
				+ 1;
		}

		$this->setProperty('forum_order', $order);

		// - increment any categories and forums that follow the one we are inserting
		$updates = $model->get('forum:Forum')
			->filter('forum_order', '>=', $order)
			->filter('forum_id', '!=', $this->getId())
			->order('forum_order', 'asc')
			->all();

		foreach ($updates as $update)
		{
			$update->setProperty('forum_order', $update->forum_order + 1);
		}

		$updates->save();

	}
}

// EOF
