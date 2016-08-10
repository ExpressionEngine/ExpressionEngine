<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.1.0
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
 * @link		https://ellislab.com
 */
class Updater {

	public $version_suffix = '';
	public $errors = array();

	/**
	 * Do Update
	 *
	 * @return bool Success?
	 */
	public function do_update()
	{
		ee()->load->dbforge();

		$steps = new ProgressIterator(
			array(
				'move_avatars',
				'update_member_data_column_names',
				'add_snippet_edit_date',
				'add_global_variable_edit_date',
				'update_collation_config',
				'fix_table_collations',
				'ensure_upload_directories_are_correct',
				'add_channel_max_entries_columns',
				'synchronize_layouts',
				'template_routes_remove_empty'
			)
		);

		foreach ($steps as $k => $v)
		{
			try
			{
				$this->$v();
			}
			catch (Exception $e)
			{
				$this->errors[] = $e->getMessage();
			}
		}

		return empty($this->errors);
	}

	/**
	 * Fields created in 3.0 were missing the 'm_' prefix on their data columns,
	 * so we need to add the prefix back
	 */
	private function update_member_data_column_names()
	{
		$member_data_columns = ee()->db->list_fields('member_data');

		$columns_to_modify = array();
		foreach ($member_data_columns as $column)
		{
			if ($column == 'member_id' OR 						// Don't rename the primary key
				substr($column, 0, 2) == 'm_' OR 				// or if it already has the prefix
				in_array('m_'.$column, $member_data_columns)) 	// or if the prefixed column already exists (?!)
			{
				continue;
			}

			$columns_to_modify[$column] = array(
				'name' => 'm_'.$column,
				'type' => (strpos($column, 'field_ft_') !== FALSE) ? 'tinytext' : 'text'
			);
		}

		ee()->smartforge->modify_column('member_data', $columns_to_modify);
	}

	/**
	 * Add snippet edit dates so that we know when files are stale
	 */
	private function add_snippet_edit_date()
	{
		ee()->smartforge->add_column(
			'snippets',
			array(
				'edit_date'        => array(
					'type'         => 'int',
					'constraint'   => 10,
					'null'         => FALSE,
					'default'      => 0
				),
			)
		);

		if (ee()->config->item('save_tmpl_files') == 'y')
		{
			$snippets = ee('Model')->get('Snippet')->all();
			$snippets->save();
		}
	}

	/**
	 * Add global variable edit dates so that we know when files are stale
	 */
	private function add_global_variable_edit_date()
	{
		ee()->smartforge->add_column(
			'global_variables',
			array(
				'edit_date'        => array(
					'type'         => 'int',
					'constraint'   => 10,
					'null'         => FALSE,
					'default'      => 0
				),
			)
		);

		if (ee()->config->item('save_tmpl_files') == 'y')
		{
			$variables = ee('Model')->get('GlobalVariable')->all();
			$variables->save();
		}
	}

	/*
 	 * Move the default avatars into a subdirectory
 	 * @return void
 	 */
	private function move_avatars()
	{
		$avatar_paths = array();
		$avatar_path = ee()->config->item('avatar_path');

		// config file has precedence, otherwise do the per-site ones
		if ( ! $avatar_path)
		{
			$site_prefs = ee('Model')->get('Site')->all()->indexBy('site_id');

			foreach ($site_prefs as $site_id => $site)
			{
				$avatar_path = $site->site_member_preferences->avatar_path;
				$avatar_path = realpath($avatar_path);

				if ( ! empty($avatar_path))
				{
					$avatar_paths[] = $avatar_path;
				}
			}
		}
		else
		{
			$avatar_path = realpath($avatar_path);

			// Does the path exist?
			if (empty($avatar_path))
			{
				throw new UpdaterException_3_1_0('Please correct the avatar path in your config file.');
			}

			$avatar_paths[] = $avatar_path;
		}

		foreach ($avatar_paths as $avatar_path)
		{
			// Check that we haven't already done this
			if (file_exists($avatar_path.'/default/'))
			{
				return TRUE;
			}

			if ( ! file_exists($avatar_path))
			{
				throw new UpdaterException_3_1_0("Please correct the avatar path in your config file.");
			}

			// Check to see if the directory is writable
			if ( ! is_writable($avatar_path))
			{
				if ( ! @chmod($avatar_path, DIR_WRITE_MODE))
				{
					throw new UpdaterException_3_1_0("Please correct the permissions on your avatar directory.");
				}
			}

			// Create the default directory
			if ( ! mkdir($avatar_path.'/default/', DIR_WRITE_MODE))
			{
				throw new UpdaterException_3_1_0("Please correct the permissions on your avatar directory.");
			}

			// Copy over the index.html
			if ( ! copy($avatar_path.'/index.html', $avatar_path.'/default/index.html'))
			{
				throw new UpdaterException_3_1_0("Please correct the permissions on your avatar directory.");
			}

			$default_avatars = array(
				'avatar_tree_hugger_color.png',
				'bad_fur_day.jpg',
				'big_horns.jpg',
				'eat_it_up.jpg',
				'ee_paint.jpg',
				'expression_radar.jpg',
				'flying_high.jpg',
				'hair.png',
				'hanging_out.jpg',
				'hello_prey.jpg',
				'light_blur.jpg',
				'ninjagirl.png',
				'procotopus.png',
				'sneak_squirrel.jpg',
				'zombie_bunny.png'
			);
			foreach ($default_avatars as $filename)
			{
				if (file_exists($avatar_path.'/'.$filename)
					&& ! rename($avatar_path.'/'.$filename, $avatar_path.'/default/'.$filename))
				{
					throw new UpdaterException_3_1_0("Please correct the permissions on your avatar directory.");
				}
			}
		}
	}

	private function update_collation_config()
	{
		$db_config = ee()->config->item('database');
		$config = $db_config['expressionengine'];

		if (isset($config['dbcollat']) && $config['dbcollat'] == 'utf8_general_ci')
		{
			$config['dbcollat'] = 'utf8_unicode_ci';
			ee()->config->_update_dbconfig($config);
		}
	}

	private function fix_table_collations()
	{
		$tables = ee()->db->list_tables(TRUE);

		foreach ($tables as $table)
		{
			$status = ee()->db->query("SHOW TABLE STATUS LIKE '$table'");

			if ($status->num_rows() != 1 || $status->row('Collation') == 'utf8_unicode_ci')
			{
				continue;
			}

			ee()->db->query("ALTER TABLE $table CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci" );
		}

	}

	private function ensure_upload_directories_are_correct()
	{
		$site_prefs = ee('Model')->get('Site')->all()->indexBy('site_id');

		foreach ($site_prefs as $site_id => $prefs)
		{
			$member_prefs = $prefs->site_member_preferences;
			$member_directories = array();

			$member_directories['Avatars'] = array(
				'server_path' => $member_prefs->avatar_path,
				'url' => $member_prefs->avatar_url,
				'allowed_types' => 'img',
				'max_width' => $member_prefs->avatar_max_width,
				'max_height' => $member_prefs->avatar_max_height,
				'max_size' => $member_prefs->avatar_max_kb,
			);

			$member_directories['Default Avatars'] = array(
				'server_path' => rtrim($member_prefs->avatar_path, '/').'/default/',
				'url' => rtrim($member_prefs->avatar_url, '/').'/default/',
				'allowed_types' => 'img',
				'max_width' => $member_prefs->avatar_max_width,
				'max_height' => $member_prefs->avatar_max_height,
				'max_size' => $member_prefs->avatar_max_kb,
			);

			$member_directories['Member Photos'] = array(
				'server_path' => $member_prefs->photo_path,
				'url' => $member_prefs->photo_url,
				'allowed_types' => 'img',
				'max_width' => $member_prefs->photo_max_width,
				'max_height' => $member_prefs->photo_max_height,
				'max_size' => $member_prefs->photo_max_kb,
			);

			$member_directories['Signature Attachments'] = array(
				'server_path' => $member_prefs->sig_img_path,
				'url' => $member_prefs->sig_img_url,
				'allowed_types' => 'img',
				'max_width' => $member_prefs->sig_img_max_width,
				'max_height' => $member_prefs->sig_img_max_height,
				'max_size' => $member_prefs->sig_img_max_kb,
			);

			$member_directories['PM Attachments'] = array(
				'server_path' => $member_prefs->prv_msg_upload_path,
				'url' => str_replace('avatars', 'pm_attachments', $member_prefs->avatar_url),
				'allowed_types' => 'img',
				'max_size' => $member_prefs->prv_msg_attach_maxsize
			);

			$existing = ee('Model')->get('UploadDestination')
				->fields('name')
				->filter('name', 'IN', array_keys($member_directories))
				->filter('site_id', $site_id)
				->all()
				->pluck('name');

			foreach ($existing as $name)
			{
				unset($member_directories[$name]);
			}

			foreach ($member_directories as $name => $data)
			{
				$dir = ee('Model')->make('UploadDestination', $data);
				$dir->site_id = $site_id;
				$dir->name = $name;
				$dir->removeNoAccess();
				$dir->module_id = 1; // this is a terribly named column - should be called `hidden`
				$dir->save();
			}
		}
	}

	/**
	 * Adds the max_entries and total_records column to the exp_channels table
	 * for the new Max Entries feature for Channels
	 *
	 * NOTE: These columns were added in 3.4 but they need to be added here for
	 * folks upgrading from an earlier version because we access the Channel
	 * model below, and a 3.4+ Channel model needs these columns present
	 */
	private function add_channel_max_entries_columns()
	{
		ee()->smartforge->add_column(
			'channels',
			array(
				'max_entries'      => array(
					'type'         => 'int',
					'null'         => FALSE,
					'unsigned'     => TRUE,
					'default'      => 0
				),
			)
		);

		ee()->smartforge->add_column(
			'channels',
			array(
				'total_records'    => array(
					'type'         => 'mediumint',
					'constraint'   => 8,
					'null'         => FALSE,
					'unsigned'     => TRUE,
					'default'      => 0
				),
			),
			'total_entries'
		);
	}

	/**
	 * Fields added after a layout was crated, never made it into the layout.
	 *
	 * @return void
	 */
	private function synchronize_layouts()
	{
		$layouts = ee('Model')->get('ChannelLayout')->all();

		foreach ($layouts as $layout)
		{
			// Account for any new fields that have been added to the channel
			// since the last edit
			$custom_fields = $layout->Channel->CustomFields->getDictionary('field_id', 'field_id');

			foreach ($layout->field_layout as $section)
			{
				foreach ($section['fields'] as $field_info)
				{
					if (strpos($field_info['field'], 'field_id_') == 0)
					{
						$id = str_replace('field_id_', '', $field_info['field']);
						unset($custom_fields[$id]);
					}
				}
			}

			$field_layout = $layout->field_layout;

			foreach ($custom_fields as $id => $val)
			{
				$field_info = array(
					'field'     => 'field_id_' . $id,
					'visible'   => TRUE,
					'collapsed' => FALSE
				);
				$field_layout[0]['fields'][] = $field_info;
			}

			$layout->field_layout = $field_layout;
			$layout->save();
		}
	}

	/**
	 * We were putting all templates into the routes table, regardless of whether
	 * they had a route
	 *
	 * @return void
	 */
	private function template_routes_remove_empty()
	{
		if (ee()->db->table_exists('template_routes'))
		{
			ee()->db->or_where('route IS NULL OR route = ""');
			ee()->db->delete('template_routes');
		}
	}
}

class UpdaterException_3_1_0 extends Exception
{
	function __construct($message)
	{
		parent::__construct($message.' <a href="https://docs.expressionengine.com/v3/installation/version_notes_3.1.0.html">Please see 3.1.0 version notes.</a>');
	}
}

// EOF
