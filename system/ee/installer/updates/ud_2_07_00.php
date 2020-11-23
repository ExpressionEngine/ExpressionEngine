<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_2_7_0;

/**
 * Update
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

		$steps = new \ProgressIterator(
			array(
				'_update_specialty_templates',
				'_update_actions_table',
				'_drop_pings',
				'_drop_updated_sites',
				'_update_localization_preferences',
				'_field_formatting_additions',
				'_add_xid_used_flag',
				'_update_relationship_tags_in_snippets',
				'_rename_safecracker_db',
				'_rename_safecracker_tags',
				'_consolidate_file_fields',
				'_update_relationships_for_grid',
				'_create_content_types_table',
				'_install_grid',
				'_modify_channel_data_default_fields',
				'_modify_category_data_fields',
				'_clear_dev_log',
				'_clean_quick_tabs',
				'_decode_rte_specialchars',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}
		return TRUE;
	}

	/**
	 * Update Specialty Templates
	 *
	 * Was updated in 2.6, but new installs got the old template
	 */
	private function _update_specialty_templates()
	{
		ee()->db->where('template_name', 'reset_password_notification');
		ee()->db->delete('specialty_templates');

		$data = array(
			'template_data'=>'{name},

To reset your password, please go to the following page:

{reset_url}

If you do not wish to reset your password, ignore this message. It will expire in 24 hours.

{site_name}
{site_url}');

		ee()->db->where('template_name', 'forgot_password_instructions')
			->update('specialty_templates', $data);

	}

	/**
	 * Update the Actions Table
	 *
	 * Required for the changes to the reset password flow.  Removed
	 * one old action and added two new ones.
	 */
	private function _update_actions_table()
	{
		// Update two old actions that we no longer need to be actions
		// with the names of the new methods.

		// For this one, the method was renamed.  It still mostly does
		// the same thing and needs to be an action.
		ee()->db->where('method', 'retrieve_password')
			->update('actions', array('method'=>'send_reset_token'));
		// For this one the method still exists, but is now a form.  It needs
		// to be renamed to the new processing method.
		ee()->db->where('method', 'reset_password')
			->update('actions', array('method'=>'process_reset_password'));

		// Add the csrf_exempt field
		ee()->smartforge->add_column(
			'actions',
			array(
				'csrf_exempt' => array(
					'type'			=> 'tinyint',
					'constraint'	=> 1,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				)
			)
		);
	}

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

	/**
	 * Insert markdown as a formatting option
	 * @return boolean TRUE if successful
	 */
	private function _field_formatting_additions()
	{
		$markdown_installed = ee()->db->get_where(
			'field_formatting',
			array('field_fmt' => 'markdown')
		);

		// Skip if this step has already run
		if ($markdown_installed->num_rows() > 0)
		{
			return;
		}

		$fields = $this->_get_field_formatting_ids(
			'xhtml',
			$this->_get_field_formatting_ids('markdown')
		);

		$data = array();
		foreach ($fields as $field_id)
		{
			$data[] = array(
				'field_id'	=> $field_id,
				'field_fmt'	=> 'markdown'
			);
		}

		if ( ! empty($data))
		{
			ee()->db->insert_batch('field_formatting', $data);
		}

		return TRUE;
	}

	/**
	 * Retrieve field_ids that match the $field_fmt
	 * @param  string $field_fmt The name of the field format
	 * @param  array  $exclude   Optional array of field ids to exclude
	 * @return array             Array containing field ids
	 */
	private function _get_field_formatting_ids($field_fmt, $exclude = array())
	{
		$ids = array();
		$fields = ee()->db->select('field_id')
			->get_where(
				'field_formatting',
				array('field_fmt' => $field_fmt)
			)
			->result_array();

		foreach ($fields as $row)
		{
			if (empty($exlude) OR ! in_array($row['field_id'], $exclude))
			{
				$ids[] = $row['field_id'];
			}
		}

		return $ids;
	}

	/**
	 * Add a used flag to xids to allow for back button usage without
	 * sacrificing existing cross site request forgery security.
	 */
	private function _add_xid_used_flag()
	{
		ee()->smartforge->add_column(
			'security_hashes',
			array(
				'used' => array(
					'type'			=> 'tinyint',
					'constraint'	=> 1,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				)
			)
		);
	}

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
			$settings = array_filter($settings);

			$valid_keys = array(
				'override_status',
				'allow_guests',
				'logged_out_member_id',
				'require_captcha'
			);

			// Settings all have their separate arrays, so we need to invert the
			// grouping to group by site_id and channel_id rather than by setting
			// name.
			$grouped_settings = array();

			foreach ($settings as $setting_name => $sites)
			{
				// Old versions of safecracker have other keys such as license_key.
				// We aren't interested in those.
				if ( ! in_array($setting_name, $valid_keys))
				{
					continue;
				}

				foreach ($sites as $site_id => $channels)
				{
					if ( ! isset($grouped_settings[$site_id]))
					{
						$grouped_settings[$site_id] = array();
					}

					$channels = array_filter($channels);

					foreach ($channels as $channel_id => $value)
					{
						if ( ! isset($grouped_settings[$site_id][$channel_id]))
						{
							$grouped_settings[$site_id][$channel_id] = array();
						}

						switch ($setting_name)
						{
							case 'allow_guests':
								$setting_name = 'allow_guest_posts';
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
								continue 2; // unknown setting name
						}

						$grouped_settings[$site_id][$channel_id][$setting_name] = $value;
					}
				}
			}

			// Now flatten that into a usable set of db rows
			$db_settings = array();
			$default_settings = array(
				'default_status'	=> 'closed',
				'require_captcha'	=> 'n',
				'allow_guest_posts'	=> 'n',
				'default_author'	=> 0,
			);

			foreach ($grouped_settings as $site_id => $channels)
			{
				foreach ($channels as $channel_id => $settings)
				{
					$db_settings[] = array_merge(
						$default_settings,
						$settings,
						compact('site_id', 'channel_id')
					);
				}
			}

			if ( ! empty($db_settings))
			{
				// and put them into the new table
				ee()->db->insert_batch('channel_form_settings', $db_settings);
			}
		}

		// drop the extension
		ee()->db->delete('extensions', array('class' => 'Safecracker_ext'));
	}

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
		ee()->remove('functions');
		require_once(APPPATH . 'libraries/Functions.php');
		ee()->set('functions', new \Installer_Functions());

		ee()->remove('extensions');
		require_once(APPPATH . 'libraries/Extensions.php');
		ee()->set('extensions', new \Installer_Extensions());

		ee()->remove('addons');
		require_once(APPPATH . 'libraries/Addons.php');
		ee()->set('addons', new \Installer_Addons());

		$installer_config = ee()->config;
		ee()->remove('config');
		ee()->set('config', new \MSM_Config());

		// We need to figure out which template to load.
		// Need to check the edit date.
		$templates = ee()->template_model->fetch_last_edit(array(), TRUE);

		foreach($templates as $template)
		{
			// If there aren't any old tags, then we don't need to continue.
			if (strpos($template->template_data, LD.'exp:channel:entry_form') === FALSE
				&& strpos($template->template_data, LD.'exp:safecracker') === FALSE)
			{
				continue;
			}

			// Find and replace the pairs
			$template->template_data = str_replace(
				array(LD.'exp:channel:entry_form', LD.'/exp:channel:entry_form', LD.'exp:safecracker',      LD.'/exp:safecracker'),
				array(LD.'exp:channel:form',       LD.'/exp:channel:form',       LD.'exp:channel:form', LD.'/exp:channel:form'),
				$template->template_data
			);

			// Rename the css path
			$template->template_data = str_replace(
				'css/_ee_saef_css',
				'css/_ee_channel_form_css',
				$template->template_data
			);

			// Fix the custom_field loop conditional
			$template->template_data = str_replace(
				LD.'if safecracker_file'.RD,
				LD.'if file'.RD,
				$template->template_data
			);

			// Replace {safecracker_head}
			$template->template_data = str_replace(
				LD.'safecracker_head'.RD,
				LD.'channel_form_assets'.RD,
				$template->template_data
			);

			// Replace safecracker_head= parameter
			$template->template_data = preg_replace(
				'/safecracker_head(\s*)=/is',
				'include_assets$1=',
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

		ee()->remove('config');
		ee()->set('config', $installer_config);
	}


	/**
	 * Combine the native file field with the safecracker file field.
	 *
	 * Merges the settings of both to create a unified file experience
	 * using the safecracker approach on the frontend and a variation
	 * of the native field on the backend (depending on settings).
	 *
	 * @return void
	 */
	protected function _consolidate_file_fields()
	{
		$sc_fields = ee()->db
			->select('field_id, field_type, field_settings')
			->where('field_type', 'safecracker_file')
			->get('channel_fields')
			->result_array();

		if (count($sc_fields))
		{
			foreach ($sc_fields as &$field)
			{
				$field['field_type'] = 'file';

				$settings = unserialize(base64_decode($field['field_settings']));

				if ( ! $settings)
				{
					$settings = array();
				}

				foreach (array_keys($settings) as $key)
				{
					$new_key = str_replace(
						array('file_field_', 'safecracker_'),
						'',
						$key
					);

					switch ($new_key)
					{
						case 'show_existing': $settings[$key] = ((bool) $settings[$key]) ? 'y': 'n';
							break;
						case 'upload_dir':    $new_key = 'allowed_directories';
							break;
					}

					$settings[$new_key] = $settings[$key];
					unset($settings[$key]);
				}

				$field['field_settings'] = base64_encode(serialize($settings));
			}

			ee()->db->update_batch('channel_fields', $sc_fields, 'field_id');
		}

		ee()->db->delete('fieldtypes', array('name' => 'safecracker_file'));
	}


	/**
	 * Add the new columns for relationships in a grid
	 *
	 * @return void
	 */
	protected function _update_relationships_for_grid()
	{
		ee()->smartforge->add_column(
			'relationships',
			array(
				'grid_field_id' => array(
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				),
				'grid_col_id' => array(
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				),
				'grid_row_id' => array(
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				)
			)
		);

		ee()->smartforge->add_key('relationships', 'grid_row_id');
	}

	/**
	 * Add the new columns for relationships in a grid
	 *
	 * @return void
	 */
	protected function _install_grid()
	{
		$grid_installed = ee()->db->get_where('fieldtypes', array('name' => 'grid'));

		if ($grid_installed->num_rows() == 0)
		{
			ee()->db->insert('fieldtypes',
				array(
					'name'					=> 'grid',
					'version'				=> '1.0',
					'settings'				=> 'YTowOnt9',
					'has_global_settings'	=> 'n',
				)
			);

			ee()->db->insert('content_types', array('name' => 'grid'));
		}

		$columns = array(
			'col_id' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'field_id' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE
			),
			'content_type' => array(
				'type'				=> 'varchar',
				'constraint'		=> 50
			),
			'col_order' => array(
				'type'				=> 'int',
				'constraint'		=> 3,
				'unsigned'			=> TRUE
			),
			'col_type' => array(
				'type'				=> 'varchar',
				'constraint'		=> 50
			),
			'col_label' => array(
				'type'				=> 'varchar',
				'constraint'		=> 50
			),
			'col_name' => array(
				'type'				=> 'varchar',
				'constraint'		=> 32
			),
			'col_instructions' => array(
				'type'				=> 'text'
			),
			'col_required' => array(
				'type'				=> 'char',
				'constraint'		=> 1
			),
			'col_search' => array(
				'type'				=> 'char',
				'constraint'		=> 1
			),
			'col_width' => array(
				'type'				=> 'int',
				'constraint'		=> 3,
				'unsigned'			=> TRUE
			),
			'col_settings' => array(
				'type'				=> 'text'
			)
		);

		ee()->load->dbforge();
		ee()->dbforge->add_field($columns);
		ee()->dbforge->add_key('col_id', TRUE);
		ee()->dbforge->add_key('field_id');
		ee()->dbforge->add_key('content_type');
		ee()->smartforge->create_table('grid_columns');
	}

	/**
	 * Update Relationship Tags in Snippets, Missed in Previous Update
	 *
	 * 	Pulls snippets from the database, examines them for any relationship tags,
	 * updates them and then saves them back to the database.
	 *
	 * @return void
	 */
	protected function _update_relationship_tags_in_snippets()
	{
		if ( ! class_exists('Installer_Template'))
		{
			require_once(APPPATH . 'libraries/Template.php');
		}
		ee()->remove('template');
		ee()->set('template', new \Installer_Template());

		ee()->load->model('snippet_model');
		$snippets = ee()->snippet_model->fetch();

		foreach($snippets as $snippet)
		{
			// If there aren't any related entries tags, then we don't need to continue.
			if (strpos($snippet->snippet_contents, 'related_entries') === FALSE
				&& strpos($snippet->snippet_contents, 'reverse_related_entries') === FALSE)
			{
				continue;
			}

			$snippet->snippet_contents = ee()->template->replace_related_entries_tags($snippet->snippet_contents);
			ee()->snippet_model->save($snippet);
		}
	}

	/**
	 * Add the new content types table
	 *
	 * @return void
	 */
	protected function _create_content_types_table()
	{
		$columns = array(
			'content_type_id' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'name' => array(
				'type'				=> 'varchar',
				'constraint'		=> 50
			)
		);

		ee()->load->dbforge();
		ee()->dbforge->add_field($columns);
		ee()->dbforge->add_key('content_type_id', TRUE);
		ee()->dbforge->add_key('name');
		ee()->smartforge->create_table('content_types');

		$channel_installed = ee()->db->get_where('content_types', array('name' => 'channel'));

		if ($channel_installed->num_rows() == 0)
		{
			// we always need to have this one
			ee()->db->insert('content_types', array('name' => 'channel'));
		}
	}

	/**
	 * Modify custom fields in exp_channel_data
	 *
	 * Possible mix of column types with regard to allowing NULL due to a bug
	 * in MSM.  Modifying to make sure the core EE default text type fields
	 * all allow NULL for consistency.
	 */
	protected function _modify_channel_data_default_fields()
	{
		// Get text type fields
		ee()->db->where_in('field_type', array('text', 'textarea', 'checkboxes', 'multi_select', 'radio', 'select', 'file'));

		$channel_fields = ee()->db->get('channel_fields');

		$channel_fields_info = ee()->db->query('DESCRIBE `exp_channel_data`;')->result_array();

		$column_types = array();
		foreach ($channel_fields_info as $column)
		{
			$column_types[$column['Field']] = $column['Type'];
		}

		foreach ($channel_fields->result_array() as $field)
		{
			if ($field['field_type'] == 'text')
			{
				$is_text = $this->_text_field_check($field['field_settings']);

				if ( ! $is_text)
				{
					continue;
				}
			}

			$field_name = 'field_id_'.$field['field_id'];

			ee()->smartforge->modify_column(
				'channel_data',
				array(
					$field_name => array(
						'name' 			=> $field_name,
						'type' 			=> isset($column_types[$field_name]) ? $column_types[$field_name] : 'text',
						'null' 			=> TRUE
					)
				)
			);
		}
	}

	/**
	 * Helper to check field setting content type for text fields
	 */
	protected function _text_field_check($data)
	{
		$settings = unserialize(base64_decode($data));

		$is_text = TRUE;

		if (isset($settings['field_content_type']) && $settings['field_content_type'] !== 'all')
		{
			$is_text = FALSE;
		}

		return $is_text;
	}


	/**
	 * Modify custom fields in exp_category_data
	 *
	 * Possible mix of column types with regard to allowing NULL due to a bug
	 * in MSM.  Modifying to make sure they all allow NULL for consistency.
	 */
	protected function _modify_category_data_fields()
	{
		// Get all fields

		$cat_fields = ee()->db->get('category_fields');

		foreach ($cat_fields->result_array() as $field)
		{
			$field_name = 'field_id_'.$field['field_id'];

			ee()->smartforge->modify_column(
				'category_field_data',
				array(
					$field_name => array(
						'name' 			=> $field_name,
						'type' 			=> 'text',
						'null' 			=> TRUE
					)
				)
			);
		}
	}

	/**
	 * Clear the developer log and add a hash column
	 *
	 * @return void
	 */
	protected function _clear_dev_log()
	{
		ee()->db->truncate('developer_log');

		ee()->smartforge->add_column(
			'developer_log',
			array(
				'hash' => array(
					'type'			=> 'char',
					'constraint'	=> 32,
					'null'			=> FALSE
				)
			)
		);
	}

	/**
	 * Clean up the quick tab links so they no longer have index.php and session
	 * ID in them
	 * @return void
	 */
	protected function _clean_quick_tabs()
	{
		$members = ee()->db->select('member_id, quick_tabs')
			->where('quick_tabs IS NOT NULL')
			->like('quick_tabs', '.php')
			->get('members')
			->result_array();

		if ( ! empty($members))
		{
			foreach ($members as $index => $member)
			{
				$members[$index]['quick_tabs'] = $this->_clean_quick_tab_links($member['quick_tabs']);
			}

			ee()->db->update_batch('members', $members, 'member_id');
		}
	}

	/**
	 * Remove the index.php and Session ID from quick tabs
	 * @param  string $string Quick Tab string
	 * @return string         Cleaned up quick tab string
	 */
	private function _clean_quick_tab_links($string)
	{
		// Each string is comprised of multiple links broken up by newlines
		$lines = explode("\n", $string);

		foreach ($lines as $index => $line)
		{
			// Each link is three parts, the first being the name (which is
			// where we're concerned about XSS cleaning), the link, the order
			$links = explode('|', $line);
			$links[1] = substr($links[1], stripos($links[1], 'C='));
			$lines[$index] = implode('|', $links);
		}

		return implode("\n", $lines);
	}

	/**
	 * Fix how RTE contents were stored by running htmlspecialcharacters_decode
	 * on all RTE fields
	 * @return void
	 */
	private function _decode_rte_specialchars()
	{
		// Get list of all RTE fields
		$fields = ee()->db->select('field_id')
			->get_where(
				'channel_fields',
				array('field_type' => 'rte')
			)
			->result_array();

		// Bail if there are no RTE fields to decode
		if (empty($fields))
		{
			return;
		}

		// Get the actual channel data
		foreach ($fields as $field)
		{
			$column = 'field_id_'.$field['field_id'];
			ee()->db->select($column);
			ee()->db->or_where("({$column} IS NOT NULL AND {$column} != '')");
		}
		$data = ee()->db->select('entry_id')
			->get('channel_data')
			->result_array();

		if ( ! empty($data))
		{
			// Clean it up
			foreach ($data as &$row)
			{
				foreach ($row as &$column)
				{
					if ( ! empty($column))
					{
						$column = htmlspecialchars_decode($column, ENT_QUOTES);
					}
				}
			}

			// Put it all back
			ee()->db->update_batch('channel_data', $data, 'entry_id');
		}
	}
}
/* END CLASS */

// EOF
