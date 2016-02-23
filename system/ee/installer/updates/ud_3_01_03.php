<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.1.3
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		ee()->load->dbforge();

		$steps = new ProgressIterator(
			array(
				'update_memberlist_order_by',
				'update_site_ids_for_categories'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Set the memberlist order_by to the new default
	 *
	 * @return void
	 */
	private function update_memberlist_order_by()
	{
		$msm_config = new MSM_Config();
		$msm_config->update_site_prefs(array('memberlist_order_by' => 'member_id'), 'all');
	}

	/**
	 * Categories saved in other MSM sites did not have the correct site ID
	 * assigned to them, we need to fix those categories
	 *
	 * @return void
	 */
	private function update_site_ids_for_categories()
	{
		// Get all cat groups not in the default site
		$category_groups = ee()->db->select('group_id, site_id')
			->where('site_id !=', 1)
			->get('category_groups')
			->result_array();

		if (empty($category_groups))
		{
			return;
		}

		$group_ids_to_site_ids = array();

		// Create an array of site IDs indexed by group ID
		foreach ($category_groups as $group)
		{
			$group_ids_to_site_ids[$group['group_id']] = $group['site_id'];
		}

		$categories = ee()->db->where_in('group_id', array_keys($group_ids_to_site_ids))
			->get('categories')
			->result_array();

		$cats_to_update = array();
		$cat_field_data_to_update = array();

		foreach ($categories as $category)
		{
			// Does the category need fixing?
			if ($category['site_id'] != $group_ids_to_site_ids[$category['group_id']])
			{
				// Modify the category so it's correct and save it for a batch update
				$category['site_id'] = $group_ids_to_site_ids[$category['group_id']];
				$cats_to_update[] = $category;

				// Category field data table will need updating, too
				$cat_field_data_to_update[] = array(
					'cat_id' => $category['cat_id'],
					'site_id' => $category['site_id']
				);
			}
		}

		if ( ! empty($cats_to_update))
		{
			ee()->db->update_batch('categories', $cats_to_update, 'cat_id');

			ee()->db->update_batch('category_field_data', $cat_field_data_to_update, 'cat_id');
		}
	}
}

// EOF
