<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Member;

/**
 * Member Service
 */
class Member {

	/**
	 * Gets array of members who can be authors
	 *
	 * @param string $search Optional search string to filter members by
	 * @param boolean $limited Limit the list to the default 100? Use FALSE sparingly
	 * @return array ID => Screen name array of authors
	 */
	public function getAuthors($search = NULL, $limited = TRUE)
	{
		// First, get member groups who should be in the list
		$member_groups = ee('Model')->get('MemberGroup')
			->filter('include_in_authorlist', 'y')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		// Then authors who are individually selected to appear in author list
		$authors = ee('Model')->get('Member')
			->fields('username', 'screen_name')
			->filter('in_authorlist', 'y');

		if ($limited)
		{
			$authors->limit(100);
		}

		// Then grab any members that are part of the member groups we found
		if ($member_groups->count())
		{
			$authors->orFilter('group_id', 'IN', $member_groups->pluck('group_id'));
		}

		if ($search)
		{
			$authors->search(
				['screen_name', 'username', 'email', 'member_id'], $search
			);
		}

		$authors->order('screen_name');
		$authors->order('username');

		$author_options = [];
		foreach ($authors->all() as $author)
		{
			$author_options[$author->getId()] = $author->getMemberName();
		}

		return $author_options;
	}

}
// EOF
