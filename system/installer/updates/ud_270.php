<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.7.0
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
				'_drop_pings',
				'_drop_updated_sites',
				'_update_localization_preferences',
				'_rename_safecracker_db',
				'_rename_safecracker_tags'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Drop ping data and columns
	 */
	private function _drop_pings()
	{
		ee()->dbforge->drop_table('entry_ping_status');
		ee()->dbforge->drop_table('ping_servers');

		ee()->smartforge->drop_column('channels', 'ping_return_url');

		ee()->load->library('layout');
		ee()->layout->delete_layout_fields('ping');
	}

	// --------------------------------------------------------------------

	/**
	 * Drop updated sites module data
	 */
	private function _drop_updated_sites()
	{
		$query = ee()->db
			->select('module_id')
			->get_where('modules', array('module_name' => 'Updated_sites'));

		if ($query->num_rows())
		{
			ee()->db->delete('module_member_groups', array('module_id' => $query->row('module_id')));
			ee()->db->delete('modules', array('module_name' => 'Updated_sites'));
			ee()->db->delete('actions', array('class' => 'Updated_sites'));

			ee()->dbforge->drop_table('updated_sites');
			ee()->dbforge->drop_table('updated_site_pings');			
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Remove the default localization member in favor or a site setting
	 * under global localization prefs.
	 */
	private function _update_localization_preferences()
	{
		$query = ee()->db->query("SELECT * FROM exp_sites");

		foreach ($query->result_array() as $row)
		{
			$conf = $row['site_system_preferences'];
			$data = unserialize(base64_decode($conf));

			if (isset($data['server_timezone']))
			{
				if ( ! isset($data['default_site_timezone']) ||
					$data['default_site_timezone'] == '')
				{
					$data['default_site_timezone'] = $data['server_timezone'];
				}

				unset(
					$data['server_timezone'],
					$data['default_site_dst'],
					$data['honor_entry_dst']
				);
			}

			ee()->db->update(
				'sites',
				array('site_system_preferences' => base64_encode(serialize($data))),
				array('site_id' => $row['site_id'])
			);
		}

		ee()->smartforge->drop_column('members', 'localization_is_site_default');

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update safecracker to channel:form and convert old saef's while we're
	 * at it - just in case they upgrade from below 2.0
	 */
	private function _rename_safecracker_db()
	{
		ee()->db->update(
			'actions',
			array('class' => 'Channel'), // set
			array('class' => 'Safecracker') // where
		);

		ee()->db->update(
			'actions',
			array('method' => 'submit_entry'), // set
			array('class' => 'Channel', 'method' => 'insert_new_entry') // where
		);

		// Add the new settings table
		ee()->dbforge->add_field(
			array(
				'channel_form_settings_id' => array('type' => 'int','constraint' => 10,	'unsigned' => TRUE,	'null' => FALSE,	'auto_increment' => TRUE),
				'site_id'			=> array('type' => 'int',		'constraint' => 4,	'unsigned' => TRUE,	'null' => FALSE,	'default' => 0),
				'channel_id'		=> array('type' => 'int',		'constraint' => 6,	'unsigned' => TRUE,	'null' => FALSE,	'default' => 0),
				'default_status'	=> array('type' => 'varchar',	'constraint' => 50,	'null' => FALSE, 	'default' => 'open'),
				'require_captcha'	=> array('type' => 'char',		'constraint' => 1,	'null' => FALSE,	'default' => 'n'),
				'allow_guest_posts'	=> array('type' => 'char',		'constraint' => 1,	'null' => FALSE,	'default' => 'n'),
				'default_author'	=> array('type' => 'int',		'constraint' => 11,	'unsigned' => TRUE,	'null' => FALSE,	'default' => 0),
			)
		);
		
		ee()->dbforge->add_key('channel_form_settings_id', TRUE);
		ee()->dbforge->add_key('site_id');
		ee()->dbforge->add_key('channel_id');
		ee()->smartforge->create_table('channel_form_settings');

		// Grab the settings
		$settings_q = ee()->db
			->select('settings')
			->where('class', 'Safecracker_ext')
			->limit(1)
			->get('extensions');

		if ($settings_q->num_rows() && $settings_q->row('settings'))
		{
			$settings = $settings_q->row('settings');
			$settings = strip_slashes(unserialize($settings));

			// Settings all have their separate arrays, so we need to invert the
			// grouping to group by site_id and channel_id rather than by setting
			// name.
			$grouped_settings = array();

			foreach ($settings as $setting_name => $sites)
			{
				foreach ($sites as $site_id => $channels)
				{
					if ( ! isset($grouped_settings[$site_id]))
					{
						$grouped_settings[$site_id] = array();
					}

					foreach ($channels as $channel_id => $value)
					{
						if ( ! isset($grouped_settings[$site_id][$channel_id]))
						{
							$grouped_settings[$site_id][$channel_id] = array();
						}

						switch ($setting_name)
						{
							case 'allow_guests':
							case 'require_captcha':
								$value = $value ? 'y' : 'n';
								break;
							case 'override_status':
								$setting_name = 'default_status';
								break;
							case 'logged_out_member_id':
								$setting_name = 'default_author';
								break;
							default:
								continue; // unknown setting name
						}

						$grouped_settings[$site_id][$channel_id][$setting_name] = $value;
					}
				}
			}

			// Now flatten that into a usable set of db rows
			$db_settings = array();

			foreach ($grouped_settings as $site_id => $channels)
			{
				foreach ($channels as $channel_id => $settings)
				{
					$db_settings[] = array_merge(
						$settings,
						compact('site_id', 'channel_id')
					);
				}
			}

			// and put them into the new table
			ee()->insert_batch('channel_form_settings', $db_settings);
		}

		// drop the extension
		ee()->db->delete('extensions', array('class' => 'Safecracker_ext'));
	}

	// -------------------------------------------------------------------

	/**
	 * Update all Safecracker Tags in All Templates
	 *
	 * Examine the templates saved in the database and in file.  Search for all
	 * instances of 'safecracker' and 'entry_form' replacing them with the new
	 * {channel:form} tag.
	 *
	 * @return void 
	 */
	protected function _rename_safecracker_tags()
	{
		if ( ! defined('LD')) define('LD', '{');
		if ( ! defined('RD')) define('RD', '}');

		// We're gonna need this to be already loaded.
		require_once(APPPATH . 'libraries/Functions.php');	
		ee()->functions = new Installer_Functions();

		require_once(APPPATH . 'libraries/Extensions.php');
		ee()->extensions = new Installer_Extensions();

		require_once(APPPATH . 'libraries/Addons.php');
		ee()->addons = new Installer_Addons();

		$installer_config = ee()->config;
		ee()->config = new MSM_Config();

		// We need to figure out which template to load.
		// Need to check the edit date.
		ee()->load->model('template_model');
		$templates = ee()->template_model->fetch_last_edit(array(), TRUE);

		foreach($templates as $template)
		{
			// If there aren't any old tags, then we don't need to continue.
			if (strpos($template->template_data, LD.'exp:channel:entry_form') === FALSE 
				&& strpos($template->template_data, LD.'safecracker') === FALSE)
			{
				continue;
			}

			// Find and replace the pairs
			$template->template_data = str_replace(
				array(LD.'exp:channel:entry_form', LD.'/exp:channel:entry_form', LD.'safecracker',      LD.'/safecracker'),
				array(LD.'exp:channel:form',       LD.'/exp:channel:form',       LD.'exp:channel:form', LD.'/exp:channel:form'),
				$template->template_data
			);

			// save the template
			// if saving to file, save the file
			if ($template->loaded_from_file)
			{
				ee()->template_model->save_to_file($template);
			}
			else
			{
				ee()->template_model->save_to_database($template);
			}
		}

		ee()->config = $installer_config;
	}
}	
/* END CLASS */

/* End of file ud_270.php */
/* Location: ./system/expressionengine/installer/updates/ud_270.php */