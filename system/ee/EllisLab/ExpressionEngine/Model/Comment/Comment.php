<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Comment;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Comment Model
 *
 * A model representing a comment on a Channel entry.
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
		'afterUpdate',
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
		// only update stats for open comments
		if ($this->status == 'o')
		{
			$this->updateCommentStats();
		}

		ee()->functions->clear_caching('all');
	}

	public function onAfterUpdate($changed)
	{
		if (isset($changed['status']) && $changed['status'] !== NULL)
		{
			$this->updateCommentStats();
		}
		ee()->functions->clear_caching('all');
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

	private function updateCommentStats()
	{
		$site_id = ($this->site_id) ?: ee()->config->item('site_id');
		$now = ee()->localize->now;

		$comments = $this->getModelFacade()->get('Comment')
			->filter('site_id', $site_id);

		$total_comments = $comments->count();

		$last_comment = $comments->filter('status', 'o')
			->fields('comment_date')
			->order('comment_date', 'desc')
			->first();

		$last_comment_date = ($last_comment) ? $last_comment->comment_date : 0;

		$stats = $this->getModelFacade()->get('Stats')
			->filter('site_id', $site_id)
			->first();

		$stats->total_comments = $total_comments;
		$stats->last_comment_date = $last_comment_date;
		$stats->save();

		// entry won't exist if we deleted comments because we deleted the entry
		if ($this->Entry)
		{
			$entry_comments = $comments->filter('entry_id', $this->entry_id);

			// Update comment count and most recent date for the entry
			$total_entry_comments = $entry_comments->count();
			$recent_comment = $entry_comments->fields('comment_date')
				->order('comment_date', 'desc')
				->first();

			// There are times, espcially when deleting a ChannelEntry, that
			// the related entry object isn't fully loaded, so we'll need
			// to reload it before working on it.
			if (is_null($this->Entry->Channel))
			{
				$this->getAssociation('Entry')->markForReload();
			}

			$this->Entry->comment_total = $total_entry_comments;
			$this->Entry->recent_comment_date = ($recent_comment) ? $recent_comment->comment_date : 0;
			$this->Entry->save();
		}

		// update member stats
		if ($this->Author)
		{
			$this->Author->updateAuthorStats();
		}
	}

}

// EOF
