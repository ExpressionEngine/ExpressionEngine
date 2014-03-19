<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
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
				'_update_specialty_templates',
				'_update_extension_quick_tabs',
				'_extract_server_offset_config',
				'_update_template_db_columns',
				'_add_template_routes_config',
				'_clear_cache',
				'_update_config_add_cookie_httponly',
				'_convert_xid_to_csrf',
				'_change_session_timeout_config',
				'_update_localization_config',
				'_update_member_table',
				'_update_session_config_names',
				'_update_config_add_cookie_httponly',
				'_replace_old_search_pagination',
				'_replace_old_specialty_pagination',
				'_update_doc_url'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}
		return TRUE;
	}

	// -------------------------------------------------------------------

	/**
	 * Update Specialty Templates
	 *
	 * Was updated in 2.6 and 2.7, but we need to add the line with {username}.
	 * But only to installations that haven't modified the default.
	 */
	private function _update_specialty_templates()
	{
		ee()->db->where('template_name', 'reset_password_notification');
		ee()->db->delete('specialty_templates');

		$old_data = '{name},

To reset your password, please go to the following page:

{reset_url}

If you do not wish to reset your password, ignore this message. It will expire in 24 hours.

{site_name}
{site_url}';

		$new_data = array(
			'template_data'=>'{name},

To reset your password, please go to the following page:

{reset_url}

Then log in with your username: {username}

If you do not wish to reset your password, ignore this message. It will expire in 24 hours.

{site_name}
{site_url}');

		ee()->db->where('template_name', 'forgot_password_instructions')
			->where('template_data', $old_data)
			->update('specialty_templates', $new_data);

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

	// -------------------------------------------------------------------------

	/**
	 * Add new Template Routes config item
	 *
	 * @access private
	 * @return void
	 */
	private function _add_template_routes_config()
	{
		$sites = ee()->db->select('site_id, site_template_preferences')
			->get('sites')
			->result_array();

		foreach ($sites as $site)
	    {
			$prefs = unserialize(base64_decode($site['site_template_preferences']));
			$prefs['enable_template_routes'] = 'y';

			ee()->db->update(
				'sites',
				array('site_template_preferences' => base64_encode(serialize($prefs))),
				array('site_id' => $site['site_id'])
			);
		}
	}

	// -------------------------------------------------------------------------

	/**
	 * Add new Template Routes table
	 *
	 * @access private
	 * @return void
	 */
	private function _update_template_db_columns()
	{
		ee()->dbforge->add_field(
			array(
				'route_id' => array(
					'type'			 => 'int',
					'constraint'     => 10,
					'null'			 => FALSE,
					'unsigned'		 => TRUE,
					'auto_increment' => TRUE
				),
				'template_id' => array(
					'type'			=> 'int',
					'constraint'    => 10,
					'unsigned'		 => TRUE,
					'null'			=> FALSE
				),
				'route' => array(
					'type'			=> 'varchar',
					'constraint'    => 512,
					'null'			=> TRUE
				),
				'route_parsed' => array(
					'type'			=> 'varchar',
					'constraint'    => 512,
					'null'			=> TRUE
				),
				'route_required' => array(
					'type'			=> 'char',
					'constraint'    => 1,
					'default'		=> 'n',
					'null'		    => FALSE
				)
			)
		);
		ee()->dbforge->add_key('route_id', TRUE);
		ee()->dbforge->add_key('template_id');
		ee()->smartforge->create_table('template_routes');
	}

	// --------------------------------------------------------------------

	/**
	 * Clear the cache, we have a new folder structure for the cache
	 * directory with the introduction of caching drivers
	 */
	private function _clear_cache()
	{
		$cache_path = EE_APPPATH.'cache';

		// Attempt to grab cache_path config if it's set
		if ($path = ee()->config->item('cache_path'))
		{
			$cache_path = ee()->config->item('cache_path');
		}

		ee()->load->helper('file');

		delete_files($cache_path, TRUE, 0, array('.htaccess', 'index.html'));
	}

	// --------------------------------------------------------------------

	/**
	 * Update Config to Add cookie_httponly
	 *
	 * Update the config.php file to add the new cookie_httponly paramter and
	 * set it to default to 'y'.
	 */
	private function _update_config_add_cookie_httponly()
	{
		ee()->config->_update_config(
			array(
				'cookie_httponly' => 'y'
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Update security hashes table and set new config item.
	 *
	 */
	private function _convert_xid_to_csrf()
	{
		// Store old setting
		$secure_forms = ee()->config->item('secure_forms');

		// Remove config item from config file
		ee()->config->_update_config(array(), array('secure_forms' => ''));

		// Remove config item from db
		$msm_config = new MSM_Config();
		$msm_config->remove_config_item('secure_forms');

		// If it was no, we need to set it as disabled
		if ($secure_forms == 'n')
		{
			ee()->config->_update_config(array('disable_csrf_protection' => 'y'));
		}

		// We changed how we access the table, so we'll re-key it to efficiently
		// select on the session id, which is the only column we use now.
		ee()->db->truncate('security_hashes');

		ee()->smartforge->drop_column('security_hashes', 'used');
		ee()->smartforge->drop_key('security_hashes', 'hash');
		ee()->smartforge->add_key('security_hashes', 'session_id');
	}

	// --------------------------------------------------------------------

	/**
	 * Remove session ttl configs in favor of a single "log out when browser
	 * closes" config, which is the only safe change that should be made to
	 * session timeouts. Use remember me for longer sessions.
	 *
	 */
	private function _change_session_timeout_config()
	{
		$cp_ttl = ee()->config->item('cp_session_ttl');
		$u_ttl = ee()->config->item('user_session_ttl');

		// Add the new item if they previously had one expiring on browser close
		if ($cp_ttl === 0 || $cp_ttl === '0' || $u_ttl === 0 || $u_ttl === '0')
		{
			ee()->config->_update_config(array('expire_session_on_browser_close' => 'y'));
		}

		// Remove old items if they existed
		ee()->config->_update_config(
			array(),
			array('cp_session_ttl' => '', 'user_session_ttl' => '')
		);

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
				ee()->config->update_site_prefs($localization_preferences, $row['site_id']);
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
	 * Renames admin_session_type and user_session_type in the site system
	 * preferences and config (if needed)
	 *
	 * @return void
	 **/
	private function _update_session_config_names()
	{
		// First: update the site_system_preferences columns
		$sites = ee()->db->select('site_id, site_system_preferences')
			->get('sites')
			->result_array();

		foreach ($sites as $site)
	    {
			$prefs = unserialize(base64_decode($site['site_system_preferences']));

			// Don't run the update query if we don't have to
			$update = FALSE;

			if (isset($prefs['admin_session_type']))
			{
				$prefs['cp_session_type'] = $prefs['admin_session_type'];
				unset($prefs['admin_session_type']);
				$update = TRUE;
			}

			if (isset($prefs['user_session_type']))
			{
				$prefs['website_session_type'] = $prefs['user_session_type'];
				unset($prefs['user_session_type']);
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

		// Second: update any $config overrides
		$new_config_items = array();
		if (ee()->config->item('admin_session_type') !== FALSE)
		{
			$new_config_items['cp_session_type'] = ee()->config->item('admin_session_type');
		}

		if (ee()->config->item('user_session_type') !== FALSE)
		{
			$new_config_items['website_session_type'] = ee()->config->item('user_session_type');
		}

		$remove_config_items = array(
			'admin_session_type' => '',
			'user_session_type'  => '',
		);

		ee()->config->_update_config($new_config_items, $remove_config_items);
	}

	// --------------------------------------------------------------------

	/**
	 * Replaces old style pagination in search results tags
	 *
	 * @return void
	 **/
	private function _replace_old_search_pagination()
	{
		ee()->load->library('logger');

		$pagination_docs = ee()->config->item('doc_url').'templates/pagination.html';

		// Replace SINGLE {paginate} variable with {pagination_links}
		ee()->logger->deprecate_template_tag(
			'Replaced {exp:search:search_results} pagination loop\'s {paginate} single variable with {pagination_links}. Use <a href="'.$pagination_docs.'">the global pagination style</a> in the future.',
			"/({exp:search:search_results.*?)(?!{paginate}.*?{\/paginate}.*?{\/exp:search:search_results}){paginate}(.*?{\/exp:search:search_results})/is",
			"$1{pagination_links}$2"
		);

		// Replace {page_count} with "Page {current_page} of {total_pages}"
		ee()->logger->deprecate_template_tag(
			'Replaced {exp:search:search_results} pagination loop\'s {page_count} with "Page {current_page} of {total_pages}". Use <a href="'.$pagination_docs.'">the global pagination style</a> in the future.',
			"/({exp:search:search_results(\s.*?)?}(.*?)){page_count}((.*?){\/exp:search:search_results})/is",
			"$1Page {current_page} of {total_pages}$4"
		);

		// Replace {if paginate}...{/if} with {paginate}...{/paginate}
		ee()->logger->deprecate_template_tag(
			'Replaced {exp:search:search_results} pagination loop\'s {if paginate} with {paginate}...{/paginate}. Use <a href="'.$pagination_docs.'">the global pagination style</a> in the future.',
			"/({exp:search:search_results(\s.*?)?}(.*?)){if paginate}(.*){\/if}((.*?){\/exp:search:search_results})/is",
			"$1{paginate}$4{/paginate}$5"
		);
	}

	// -------------------------------------------------------------------------

	/**
	 * Replaces old style pagination in specialty (Wiki, Forum, Profile)
	 * templates
	 * @return void
	 */
	private function _replace_old_specialty_pagination()
	{
		ee()->load->library('logger');

		$pagination_docs = ee()->config->item('doc_url').'templates/pagination.html';

		ee()->logger->deprecate_specialty_template_tag(
			'Replaced subscription pagination templates\' {pagination} with {paginate}{pagination_links}{/paginate}. Use <a href="'.$pagination_docs.'">the global pagination style</a> in the future.',
			"/{pagination}/is",
			"{paginate}{pagination_links}{/paginate}",
			'subscription_pagination.html'
		);

		ee()->logger->deprecate_specialty_template_tag(
			'Replaced specialty templates\' {if paginate} and {if pagination} with {paginate}...{/paginate}. Use <a href="'.$pagination_docs.'">the global pagination style</a> in the future.',
			"/{if (?:paginate|pagination)}(.*?){\/if}/is",
			"{paginate}$1{/paginate}"
		);

		ee()->logger->deprecate_specialty_template_tag(
			'Replaced specialty templates\' {pagination} and {include:pagination_links} with {pagination_links}. Use <a href="'.$pagination_docs.'">the global pagination style</a> in the future.',
			"/{pagination}|{include:pagination_link}/is",
			"{pagination_links}"
		);
	}

	// -------------------------------------------------------------------------

	/**
	 * Update outdated doc_url config item so overview help links are relevant
	 * @return  void
	 */
	private function _update_doc_url()
	{
		if (strpos(ee()->config->item('doc_url'), 'expressionengine.com/user_guide') !== FALSE)
		{
			ee()->config->_update_config(array(
				'doc_url' => 'http://ellislab.com/expressionengine/user-guide/'
			));
		}
	}

}

/* END CLASS */

/* End of file ud_280.php */
/* Location: ./system/expressionengine/installer/updates/ud_280.php */
