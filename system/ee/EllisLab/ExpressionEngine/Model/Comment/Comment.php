<?php

namespace EllisLab\ExpressionEngine\Model\Comment;

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
 * ExpressionEngine Comment Model
 *
 * A model representing a comment on a Channel entry.
 *
 * @package		ExpressionEngine
 * @subpackage	Comment Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Comment extends Model {

	protected static $_primary_key = 'comment_id';
	protected static $_table_name = 'comments';

	protected static $_hook_id = 'comment';

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'BelongsTo'
		),
		'Entry' => array(
			'type' => 'BelongsTo',
			'model' => 'ChannelEntry'
		),
		'Channel' => array(
			'type' => 'BelongsTo'
		),
		'Author' => array(
			'type' => 'BelongsTo',
			'model' => 'Member',
			'from_key' => 'author_id',
			'to_key' => 'member_id'
		)
	);

	protected static $_validation_rules = array(
		'site_id'    => 'required|isNatural',
		'entry_id'   => 'required|isNatural',
		'channel_id' => 'required|isNatural',
		'author_id'  => 'required|isNatural',
		'status'     => 'enum[o,c,p,s]',
		'ip_address' => 'ip_address',
		'comment'    => 'required',
	);

	protected static $_events = array(
		'afterInsert',
		'afterDelete',
		'afterSave',
	);

	protected $comment_id;
	protected $site_id;
	protected $entry_id;
	protected $channel_id;
	protected $author_id;
	protected $status;
	protected $name;
	protected $email;
	protected $url;
	protected $location;
	protected $ip_address;
	protected $comment_date;
	protected $edit_date;
	protected $comment;

	public function onAfterInsert()
	{
		if ($this->Author)
		{
			$this->Author->updateAuthorStats();
		}

		$this->updateCommentStats();
	}

	public function onAfterDelete()
	{
		if ($this->Author)
		{
		// store the author and dissociate. otherwise saving the author will
		// attempt to save this entry to ensure relationship integrity.
		// TODO make sure everything is already dissociated when we hit this
		$last_author = $this->Author;
		$this->Author = NULL;

		$last_author->updateAuthorStats();
		}

		$this->updateCommentStats();
		ee()->functions->clear_caching('all');
	}

	public function onAfterSave()
	{
		ee()->functions->clear_caching('all');
	}

	private function updateCommentStats()
	{
		$site_id = ($this->site_id) ?: ee()->config->item('site_id');
		$now = ee()->localize->now;

		$comments = $this->getFrontend()->get('Comment')
			->filter('site_id', $site_id);

		$total_comments = $comments->count();

		$last_comment = $comments->filter('status', 'o')
			->fields('comment_date')
			->order('comment_date', 'desc')
			->first();

		$last_comment_date = ($last_comment) ? $last_comment->comment_date : 0;

		$stats = $this->getFrontend()->get('Stats')
			->filter('site_id', $site_id)
			->first();

		$stats->total_comments = $total_comments;
		$stats->last_comment_date = $last_comment_date;
		$stats->save();

		// Update comment count for the entry
		$total_entry_comments = $comments->filter('entry_id', $this->entry_id)->count();

		// entry won't exist if we deleted comments because we deleted the entry
		if ($this->Entry)
		{
			$this->Entry->comment_total = $total_entry_comments;
			$this->Entry->save();
		}
	}

}

// EOF
