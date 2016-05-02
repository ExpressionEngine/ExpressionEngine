<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Admin Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class File_upload_preferences_model extends CI_Model
{
	/**
	 * Get Upload Preferences
	 *
	 * @access	public
	 * @param	int $group_id Member group ID specified when returning allowed upload
	 *		directories only for that member group
	 * @param	int $id Specific ID of upload destination to return
	 * @param	bool $ignore_site_id If TRUE, returns upload destinations for all sites
	 * @return	array	Result array of DB object, possibly merged with custom
	 * 		file upload settings
	 */
	function get_file_upload_preferences($group_id = NULL, $id = NULL, $ignore_site_id = FALSE, $parameters = array())
	{
		// for admins, no specific filtering, just give them everything
		if ($group_id != 1)
		{
			// non admins need to first be checked for restrictions
			// we'll add these into a where_not_in() check below
			$this->db->select('upload_id');
			$no_access = $this->db->get_where('upload_no_access', array('member_group'=>$group_id));

			if ($no_access->num_rows() > 0)
			{
				$denied = array();
				foreach($no_access->result() as $result)
				{
					$denied[] = $result->upload_id;
				}
				$this->db->where_not_in('id', $denied);
			}
		}

		// Is there a specific upload location we're looking for?
		if ( ! empty($id))
		{
			$this->db->where('id', $id);
		}

		$this->db->from('upload_prefs');

		// By default, we will return upload destinations for the current site
		// unless we are to ignore the site ID and return all
		if ( ! $ignore_site_id)
		{
			$this->db->where('site_id', $this->config->item('site_id'));
		}

		// Check for order_by parameters
		if (isset($parameters['order_by']))
		{
			foreach ($parameters['order_by'] as $column => $direction)
			{
				$this->db->order_by($column, $direction);
			}
		}

		$this->db->order_by('name');

		// If we were passed an ID, just return the row
		$result_array = ( ! empty($id)) ? $this->db->get()->row_array() : $this->db->get()->result_array();

		// Has the user set overrides in the upload_preferences config variable?
		if ($this->config->item('upload_preferences') !== FALSE && count($result_array) > 0)
		{
			$upload_preferences = $this->config->item('upload_preferences');

			// If we are dealing with a single row
			if (isset($result_array['id']))
			{
				// If there is an override preference set for this row
				if (isset($upload_preferences[$result_array['id']]))
				{
					$result_array = array_merge($result_array, $upload_preferences[$result_array['id']]);
				}
			}
			else // Multiple upload preference rows returned
			{
				// Loop through our results and see if any items need to be overridden
				foreach ($result_array as &$upload_dir)
				{
					if (isset($upload_preferences[$upload_dir['id']]))
					{
						// Merge the database result with the custom result, custom keys
						// overwriting database keys
						$upload_dir = array_merge($upload_dir, $upload_preferences[$upload_dir['id']]);
					}
				}
			}
		}

		// Use upload destination ID as key for row for easy traversing
		$return_array = ( ! empty($id)) ? $result_array : array();
		if (empty($return_array))
		{
			foreach ($result_array as $row)
			{
				$return_array[$row['id']] = $this->parse_upload_path_vars($row);
			}
		}
		else {
			$return_array = $this->parse_upload_path_vars($return_array);
		}

		return $return_array;
	}

	// --------------------------------------------------------------------

	/**
	 * Upload paths may have a {base_url} or {base_path} in them, so we need
	 * to parse those but also take into account when an upload destination
	 * belongs to another site
	 *
	 * @param array $upload_destination Array of upload destination info
	 * @return array Upload destination array with parsed variables
	 */
	private function parse_upload_path_vars($upload_destination)
	{
		$site_id = $upload_destination['site_id'];
		$overrides = array();

		if ($site_id != ee()->config->item('site_id'))
		{
			$overrides = ee()->config->get_cached_site_prefs($site_id);
		}

		$upload_destination['url'] = parse_config_variables($upload_destination['url'], $overrides);
		$upload_destination['server_path'] = parse_config_variables($upload_destination['server_path'], $overrides);

		return $upload_destination;
	}

	// --------------------------------------------------------------------

	/**
	 * Builds an array suitable for dropdown lists
	 *
	 * @param integer $group_id The group id to get file preferences for
	 * @param integer $id Specific upload directory ID if you just want settings for that
	 * @param array $prefs_array Optional existing array to add the preferences to
	 * @return array Associative array with ids as the keys and names as the values
	 */
	public function get_dropdown_array($group_id = NULL, $id = NULL, $prefs_array = array())
	{
		$prefs = $this->get_file_upload_preferences($group_id, $id);

		if (isset($prefs['id']))
		{
			$prefs = array($prefs);
		}

		foreach ($prefs as $pref)
		{
			$prefs_array[$pref['id']] = $pref['name'];
		}

		return $prefs_array;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Upload Preferences
	 *
	 * @access	public
	 * @param	array	Array of upload directory IDs to delete, or single ID
	 * @return	string
	 */
	function delete_upload_preferences($ids = array())
	{
		if ( ! is_array($ids))
		{
			$ids = array($ids);
		}

		// There are no permission checks- I don't really think there should be

		$this->db->where_in('upload_id', $ids);
		$this->db->delete('upload_no_access');

		// get the name we're going to delete so that we can return it when we're done
		$this->db->select('name');
		$this->db->where_in('id', $ids);
		$deleting = $this->db->get('upload_prefs');

		// Delete the files associated with this preference
		// Note we aren't doing anything to the files/folders yet
		$this->db->where_in('upload_location_id', $ids);
		$this->db->delete('files');

		// ok, now remove the pref
		$this->db->where_in('id', $ids);
		$this->db->delete('upload_prefs');

		return $deleting->row('name');
	}

	// --------------------------------------------------------------------

	/**
	 * Get the category groups for one or more upload directories
	 *
	 * @param array $id Either an array of upload directory IDs or just one
	 * @return array Array of category group IDs
	 */
	public function get_category_groups($id = array())
	{
		if ( ! is_array($id))
		{
			$id = array($id);
		}

		$cat_groups = array();

		$this->db->select('cat_group');
		$this->db->where_in('id', $id);
		$upload_pref_query = $this->db->get('upload_prefs');

		foreach ($upload_pref_query->result() as $upload_pref)
		{
			$cat_groups = array_merge($cat_groups, explode('|', $upload_pref->cat_group));
		}

		return array_unique($cat_groups);
	}

	// -------------------------------------------------------------------------

	/**
	 * Get all file upload paths in a mapped associative array with the keys
	 * being the file upload directory ID and the value being the URL of the
	 * directory.
	 * @return array Associative array containing upload dir IDs and paths
	 *               Array(
	 *                 [1] => http://expressionengine/images/uploads/
	 *                 [3] => http://expressionengine/themes/site/...
	 *                 ...
	 *               )
	 */
	public function get_paths()
	{
		if ( ! ee()->session->cache(__CLASS__, 'paths'))
		{
			$paths = array();
			$upload_prefs = $this->get_file_upload_preferences(NULL, NULL, TRUE);

			if (count($upload_prefs) == 0)
			{
				return $paths;
			}

			foreach ($upload_prefs as $row)
			{
				$paths[$row['id']] = $row['url'];
			}

			ee()->session->set_cache(__CLASS__, 'paths', $paths);
		}

		return ee()->session->cache(__CLASS__, 'paths');
	}
}

// EOF
