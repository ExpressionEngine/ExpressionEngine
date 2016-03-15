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
 * ExpressionEngine Post Model for the Forum
 *
 * A model representing a post in the Forum.
 *
 * @package		ExpressionEngine
 * @subpackage	Forum Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Post extends Model {

	protected static $_primary_key = 'post_id';
	protected static $_table_name = 'forum_posts';

	protected static $_typed_columns = array(
		'topic_id'         => 'int',
		'forum_id'         => 'int',
		'board_id'         => 'int',
		'author_id'        => 'int',
		'post_date'        => 'timestamp',
		'post_edit_date'   => 'timestamp',
		'post_edit_author' => 'int',
		'notify'           => 'boolString',
		'parse_smileys'    => 'boolString',
	);

	protected static $_relationships = array(
		'Attachments' => array(
			'type'  => 'hasMany',
			'model' => 'Attachment'
		),
		'Author' => array(
			'type'     => 'belongsTo',
			'model'    => 'ee:Member',
			'from_key' => 'author_id',
			'weak'     => TRUE,
			'inverse' => array(
				'name' => 'Post',
				'type' => 'hasMany'
			)
		),
		'Board' => array(
			'type' => 'belongsTo'
		),
		'EditAuthor' => array(
			'type'     => 'belongsTo',
			'from_key' => 'post_edit_author',
			'to_key'   => 'member_id',
			'model'    => 'ee:Member',
			'weak'     => TRUE,
			'inverse' => array(
				'name' => 'Post',
				'type' => 'hasMany'
			)
		),
		'Forum' => array(
			'type' => 'belongsTo'
		),
		'ForumLastPost' => array(
			'type'     => 'belongsTo',
			'from_key' => 'post_id',
			'to_key'   => 'forum_last_post_id',
			'model'    => 'Forum',
		),
		'Topic' => array(
			'type' => 'belongsTo'
		),
		'TopicLastPost' => array(
			'type'     => 'belongsTo',
			'from_key' => 'post_id',
			'to_key'   => 'last_post_id',
			'model'    => 'Topic',
		),
	);

	protected static $_validation_rules = array(
		'topic_id'         => 'boolString',
		'forum_id'         => 'boolString',
		'ip_address'       => 'boolString|ipAddress',
		'body'             => 'boolString',
		'post_date'        => 'boolString',
		'notify'           => 'enum[y,n]',
		'parse_smileys'    => 'enum[y,n]',
	);

	protected static $_events = array(
		'afterInsert',
		'beforeDelete',
	);

	protected $post_id;
	protected $topic_id;
	protected $forum_id;
	protected $board_id;
	protected $author_id;
	protected $ip_address;
	protected $body;
	protected $post_date;
	protected $post_edit_date;
	protected $post_edit_author;
	protected $notify;
	protected $parse_smileys;

	public function onAfterInsert()
	{
		$this->Forum->forum_total_posts++;
		$this->Forum->save();

		$this->Author->total_forum_posts++;
		$this->Author->save();
	}

	public function onAfterDelete()
	{
		$this->Forum->forum_total_posts--;
		$this->Forum->save();

		$this->Author->total_forum_posts--;
		$this->Author->save();
	}

}

// EOF
