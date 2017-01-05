<?php

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.4.5
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Relationship Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class EntryList {

	// Cache variables
	protected $channels = array();
	protected $entries = array();
	protected $children = array();

	public function query($settings, $selected = array())
	{
		$channels = array();
		$limit_channels = $settings['channels'];
		$limit_categories = $settings['categories'];
		$limit_statuses = $settings['statuses'];
		$limit_authors = $settings['authors'];
		$limit = $settings['limit'];
		$show_expired = (bool) $settings['expired'];
		$show_future = (bool) $settings['future'];
		$order_field = $settings['order_field'];
		$order_dir = $settings['order_dir'];
		$entry_id = $settings['entry_id'];
		$search = isset($settings['search']) ? $settings['search'] : NULL;
		$channel_id = isset($settings['channel_id']) ? $settings['channel_id'] : NULL;

		// Create a cache ID based on the query criteria for this field so fields
		// with similar entry listings can share data that's already been queried
		$cache_id = md5(serialize(compact('limit_channels', 'limit_categories', 'limit_statuses',
			'limit_authors', 'limit', 'show_expired', 'show_future', 'order_field', 'order_dir')));

		// Bug 19321, old fields use date
		if ($order_field == 'date')
		{
			$order_field = 'entry_date';
		}

		$entries = ee('Model')->get('ChannelEntry')
			->with('Channel')
			->fields('Channel.*', 'entry_id', 'title', 'channel_id')
			->order($order_field, $order_dir);

		if ( ! empty($search))
		{
			$entries->search('title', '"'.$search.'"');
		}

		if ( ! empty($channel_id) && is_numeric($channel_id))
		{
			$entries->filter('channel_id', $channel_id);
		}

		// -------------------------------------------
		// 'relationships_display_field_options' hook.
		//  - Allow developers to add additional filters to the entries that populate the field options
		//
		if (ee()->extensions->active_hook('relationships_display_field_options') === TRUE)
		{
			ee()->extensions->call(
				'relationships_display_field_options',
				$entries,
				$settings['field_id'],
				$settings
			);
		}

		if (count($limit_channels))
		{
			$entries->filter('channel_id', 'IN', $limit_channels);
		}

		if (count($limit_categories))
		{
			$entries->with('Categories')
				->filter('Categories.cat_id', 'IN', $limit_categories);
		}

		if (count($limit_statuses))
		{
			$limit_statuses = str_replace(
				array('Open', 'Closed'),
				array('open', 'closed'),
				$limit_statuses
			);

			$entries->filter('status', 'IN', $limit_statuses);
		}

		if (count($limit_authors))
		{
			$groups = array();
			$members = array();

			foreach ($limit_authors as $author)
			{
				switch ($author[0])
				{
					case 'g': $groups[] = substr($author, 2);
						break;
					case 'm': $members[] = substr($author, 2);
						break;
				}
			}

			$entries->with('Author');

			if (count($members) && count($groups))
			{
				$entries->filterGroup()
					->filter('author_id', 'IN', implode(', ', $members))
					->orFilter('Author.group_id', 'IN', implode(', ', $groups))
					->endFilterGroup();
			}
			else
			{
				if (count($members))
				{
					$entries->filter('author_id', 'IN', implode(', ', $members));
				}

				if (count($groups))
				{
					$entries->filter('Author.group_id', 'IN', implode(', ', $groups));
				}
			}
		}

		// Limit times
		$now = ee()->localize->now;

		if ( ! $show_future)
		{
			$entries->filter('entry_date', '<', $now);
		}

		if ( ! $show_expired)
		{
			$entries->filterGroup()
				->filter('expiration_date', 0)
				->orFilter('expiration_date', '>', $now)
				->endFilterGroup();
		}

		if ($entry_id)
		{
			$entries->filter('entry_id', '!=', $entry_id);
		}

		if ($limit)
		{
			$entries->limit($limit);
		}

		// If we've got a limit and selected entries, we need to run the query
		// twice. Once without those entries and then separately with only those
		// entries.

		if (count($selected) && $limit)
		{
			$selected_entries = clone $entries;

			$entries = $entries->filter('entry_id', 'NOT IN', $selected)->all();

			$selected_entries->limit(count($selected))
				->filter('entry_id', 'IN', $selected)
				->all()
				->map(function($entry) use(&$entries) { $entries[] = $entry; });

			$entries = $entries->sortBy($order_field);
		}
		else
		{
			// Don't query if we have this same query in the cache
			if (isset($this->entries[$cache_id]))
			{
				$entries = $this->entries[$cache_id];
			}
			else
			{
				$this->entries[$cache_id] = $entries = $entries->all();
			}
		}

		return $entries;
	}
}

// EOF
