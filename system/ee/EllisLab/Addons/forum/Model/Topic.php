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
 * ExpressionEngine Topic Model for the Forum
 *
 * A model representing a topic in the Forum.
 *
 * @package		ExpressionEngine
 * @subpackage	Forum Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Topic extends Model {

	protected static $_primary_key = 'topic_id';
	protected static $_table_name = 'forum_topics';

	protected static $_typed_columns = array(
		'forum_id'            => 'int',
		'board_id'            => 'int',
		'moved_forum_id'      => 'int',
		'author_id'           => 'int',
		'sticky'              => 'boolString',
		'poll'                => 'boolString',
		'announcement'        => 'boolString',
		'topic_date'          => 'timestamp',
		'topic_edit_date'     => 'timestamp',
		'topic_edit_author'   => 'int',
		'thread_total'        => 'int',
		'thread_views'        => 'int',
		'last_post_date'      => 'timestamp',
		'last_post_author_id' => 'int',
		'last_post_id'        => 'int',
		'notify'              => 'boolString',
		'parse_smileys'       => 'boolString',
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
				'name' => 'Topic',
				'type' => 'hasMany'
			)
		),
		'Board' => array(
			'type' => 'belongsTo'
		),
		'EditAuthor' => array(
			'type'     => 'belongsTo',
			'from_key' => 'topic_edit_author',
			'to_key'   => 'member_id',
			'model'    => 'ee:Member',
			'weak'     => TRUE,
			'inverse' => array(
				'name' => 'Topic',
				'type' => 'hasMany'
			)
		),
		'Forum' => array(
			'type' => 'belongsTo'
		),
		'LastPost' => array(
			'type'     => 'hasOne',
			'model'    => 'Post',
			'from_key' => 'last_post_id',
			'to_key'   => 'post_id',
		),
		'LastPostAuthor' => array(
			'type'     => 'belongsTo',
			'model'    => 'ee:Member',
			'from_key' => 'last_post_author_id',
			'to_key'   => 'member_id',
			'weak'     => TRUE,
			'inverse' => array(
				'name' => 'Topic',
				'type' => 'hasMany',
				'weak' => TRUE
			)
		),
		'Polls' => array(
			'type'  => 'hasMany',
			'model' => 'Poll'
		),
		'PollVotes' => array(
			'type'  => 'hasMany',
			'model' => 'PollVote'
		),
		'Posts' => array(
			'type'  => 'hasMany',
			'model' => 'Post'
		),
	);

	protected static $_validation_rules = array(
		'forum_id'            => 'required',
		'ip_address'          => 'required|ipAddress',
		'title'               => 'required',
		'body'                => 'required',
		'status'              => 'enum[o,c]',
		'sticky'              => 'enum[y,n]',
		'poll'                => 'enum[y,n]',
		'announcement'        => 'enum[y,n]',
		'topic_date'          => 'required',
		'notify'              => 'enum[y,n]',
		'parse_smileys'       => 'enum[y,n]',
	);

	protected static $_events = array(
		'afterInsert',
		'beforeDelete',
	);

	protected $topic_id;
	protected $forum_id;
	protected $board_id;
	protected $moved_forum_id;
	protected $author_id;
	protected $ip_address;
	protected $title;
	protected $body;
	protected $status;
	protected $sticky;
	protected $poll;
	protected $announcement;
	protected $topic_date;
	protected $topic_edit_date;
	protected $topic_edit_author;
	protected $thread_total;
	protected $thread_views;
	protected $last_post_date;
	protected $last_post_author_id;
	protected $last_post_id;
	protected $notify;
	protected $parse_smileys;

	public function onAfterInsert()
	{
		$this->Forum->forum_total_topics++;
		$this->Forum->save();

		$this->Author->total_forum_topics++;
		$this->Author->save();
	}

	public function onAfterDelete()
	{
		$this->Forum->forum_total_topics--;
		$this->Forum->save();

		$this->Author->total_forum_topics--;
		$this->Author->save();
	}

}

// EOF
