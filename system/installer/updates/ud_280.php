<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.8.0
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
				'_update_extension_quick_tabs',
				'_update_localization_config',
				'_update_member_table',
				'_extract_server_offset_config'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}
		return TRUE;
	}

	// -------------------------------------------------------------------------

	private function _update_extension_quick_tabs()
	{
		$members = ee()->db->select('member_id, quick_tabs')
			->where('quick_tabs IS NOT NULL')
			->like('quick_tabs', 'toggle_extension')
			->get('members')
			->result_array();

		if ( ! empty($members))
		{
			foreach ($members as $index => $member)
			{
				$members[$index]['quick_tabs'] = str_replace('toggle_extension_confirm', 'toggle_all', $members[$index]['quick_tabs']);
				$members[$index]['quick_tabs'] = str_replace('toggle_extension', 'toggle_install', $members[$index]['quick_tabs']);
			}

			ee()->db->update_batch('members', $members, 'member_id');
		}
	}

	// -------------------------------------------------------------------------

	/**
	 * Update Localization Config
	 *
	 * We are adding "date_format" to the config, and changing the value of
	 * "time_format".  We are also making the hidden config "include_seconds"
	 * not hidden.
	 */
	private function _update_localization_config()
	{
		$localization_preferences = array();

		ee()->db->select('site_id, site_system_preferences');
    	$query = ee()->db->get('sites');

    	if ($query->num_rows() > 0)
    	{
			foreach ($query->result_array() as $row)
			{
				$system_prefs = base64_decode($row['site_system_preferences']);
				$system_prefs = unserialize($system_prefs);

				if ($system_prefs['time_format'] == 'us')
				{
					$localization_preferences['date_format'] = '%n/%j/%y';
					$localization_preferences['time_format'] = '12';
				}
				else
				{
					$localization_preferences['date_format'] = '%j-%n-%y';
					$localization_preferences['time_format'] = '24';
				}

				$localization_preferences['include_seconds'] = ee()->config->item('include_seconds') ? ee()->config->item('include_seconds') : 'n';
				ee()->config->update_site_prefs($localization_preferences, 'all');
			}
		}
	}

	// -------------------------------------------------------------------------

	/**
	 * Update Member Table
	 *
	 * Along with the localization config changes we are changing the member
	 * localizaion preferences.  We are now storing the date format as the
	 * actual format, and storing the "include_seconds" preference.
	 *
	 * This will add the new columns, change the default on the "time_format"
	 * column, and update the members based on their old values (and the site's)
	 * value on "include_seconds".
	 */
	private function _update_member_table()
	{
		// Add new columns
		ee()->smartforge->add_column(
			'members',
			array(
				'date_format'    => array(
					'type'       => 'varchar',
					'constraint' => 8,
					'null'       => FALSE,
					'default'    => '%n/%j/%y'
				),
				'include_seconds' => array(
					'type'        => 'char',
					'constraint'  => 1,
					'null'        => FALSE,
					'default'     => 'n'
				)
			),
			'time_format'
		);

		// Modify the default value of time_format
		ee()->smartforge->modify_column(
			'members',
			array(
				'time_format'    => array(
					'name'       => 'time_format',
					'type'       => 'char',
					'constraint' => 2,
					'null'       => FALSE,
					'default'    => '12'
				)
			)
		);

		// Update all the members
		ee()->db->where('time_format', 'us')->update('members', array('date_format' => '%n/%j/%y', 'time_format' => '12'));
		ee()->db->where('time_format', 'eu')->update('members', array('date_format' => '%j-%n-%y', 'time_format' => '24'));
		$include_seconds = ee()->config->item('include_seconds') ? ee()->config->item('include_seconds') : 'n';
		ee()->db->update('members', array('include_seconds' => $include_seconds));
	}

	// --------------------------------------------------------------------

	/**
	 * Make sure server_offset is set in config.php and not in the
	 * exp_sites table because the UI for settings server offset is gone
	 *
	 * Previously, server_offset could be set via the control panel, in
	 * which case the value would get trapped in the site preferences array
	 * with no interface to change since the UI for the setting was removed
	 * in 2.6. This puts it back in config.php and out of the sites table
	 * to help potential confusion if server time appears off but no
	 * apparent setting is causing it.
	 */
	private function _extract_server_offset_config()
	{
		// Get server offset from config.php if it exists
		// (DB prefs aren't loaded yet)
		$server_offset = ee()->config->item('server_offset');

		$sites = ee()->db->select('site_id, site_system_preferences')
			->get('sites')
			->result_array();

		foreach ($sites as $site)
		{
			$prefs = unserialize(base64_decode($site['site_system_preferences']));

			// Don't run the update query if we don't have to
			$update = FALSE;

			// Remove server_offset from site system preferences array
			if (isset($prefs['server_offset']))
			{
				if ($server_offset === FALSE)
				{
					$server_offset = $prefs['server_offset'];
				}

				unset($prefs['server_offset']);

				$update = TRUE;
			}

			if ($update)
			{
				ee()->db->update(
					'sites',
					array('site_system_preferences' => base64_encode(serialize($prefs))),
					array('site_id' => $site['site_id'])
				);
			}
		}

		// Add server_offset back to site preferences, but this time
		// it will end up in config.php because server_offset is no
		// longer in divination
		if ( ! empty($server_offset))
		{
			ee()->config->update_site_prefs(array(
				'server_offset' => $server_offset
			), 'all');
		}
	}
}
/* END CLASS */

/* End of file ud_280.php */
/* Location: ./system/expressionengine/installer/updates/ud_280.php */
