<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_4_0_0;

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
		$steps = new \ProgressIterator(
			array(
				'emancipateTheFields',
				'addFieldDataFlag',
				'removeMemberHomepageTable',
				'moveMemberFields',
				'warnAboutBirthdayTag',
				'globalizeSave_tmpl_files',
				'clearCurrentVersionCache',
				'nullOutRelationshipChannelDataFields',
				'addNewsViewsTable',
				'addSortIndexToChannelTitles',
				'addImageQualityColumn',
				'addSpamModerationPermissions',
				'runSpamModuleUpdate',
				'addPrimaryKeyToFileCategoryTable',
				'addFluidFieldField',
				'addDurationField',
				'addCommentMenuExtension',
				'emancipateStatuses'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function emancipateTheFields()
	{
		if (ee()->db->table_exists('channels_channel_field_groups'))
		{
			return;
		}

		// Fields can span Sites and do not need Groups
		ee()->smartforge->modify_column('channel_fields', array(
			'site_id' => array(
				'type'     => 'int',
				'unsigned' => TRUE,
				'null'     => TRUE,
			),
			'group_id' => array(
				'type'     => 'int',
				'unsigned' => TRUE,
				'null'     => TRUE,
			),
		));

		// Field groups can span Sites
		ee()->smartforge->modify_column('field_groups', array(
			'site_id' => array(
				'type'     => 'int',
				'unsigned' => TRUE,
				'null'     => TRUE,
			),
		));

		// Add the Many-to-Many tables
		ee()->dbforge->add_field(
			array(
				'channel_id' => array(
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				),
				'group_id' => array(
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				)
			)
		);
		ee()->dbforge->add_key(array('channel_id', 'group_id'), TRUE);
		ee()->smartforge->create_table('channels_channel_field_groups');

		ee()->dbforge->add_field(
			array(
				'channel_id' => array(
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				),
				'field_id' => array(
					'type'       => 'int',
					'constraint' => 6,
					'unsigned'   => TRUE,
					'null'       => FALSE
				)
			)
		);
		ee()->dbforge->add_key(array('channel_id', 'field_id'), TRUE);
		ee()->smartforge->create_table('channels_channel_fields');

		ee()->dbforge->add_field(
			array(
				'field_id' => array(
					'type'       => 'int',
					'constraint' => 6,
					'unsigned'   => TRUE,
					'null'       => FALSE
				),
				'group_id' => array(
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				)
			)
		);
		ee()->dbforge->add_key(array('field_id', 'group_id'), TRUE);
		ee()->smartforge->create_table('channel_field_groups_fields');

		// Convert the one-to-one channel to field group assignment to the
		// many-to-many structure
		if (ee()->db->field_exists('field_group', 'channels'))
		{
			$channels = ee()->db->select('channel_id, field_group')
				->where('field_group IS NOT NULL')
				->get('channels')
				->result();

			foreach ($channels as $channel)
			{
				ee()->db->insert('channels_channel_field_groups', array(
					'channel_id' => $channel->channel_id,
					'group_id' => $channel->field_group
				));
			}

			ee()->smartforge->drop_column('channels', 'field_group');
		}

		// Convert the one-to-one field to field group assignment to the
		// many-to-many structure
		if (ee()->db->field_exists('group_id', 'channel_fields'))
		{
			$fields = ee()->db->select('field_id, group_id')
				->get('channel_fields')
				->result();

			foreach ($fields as $field)
			{
				ee()->db->insert('channel_field_groups_fields', array(
					'field_id' => $field->field_id,
					'group_id' => $field->group_id
				));
			}

			ee()->smartforge->drop_column('channel_fields', 'group_id');
		}
	}

	/**
	 * Adds a column to exp_channel_fields, exp_member_fields, and
	 * exp_category_fields tables that indicates if the
	 * data is in the legacy data tables or their own table.
	 */
	private function addFieldDataFlag()
	{
		if ( ! ee()->db->field_exists('legacy_field_data', 'category_fields'))
		{
			ee()->smartforge->add_column(
				'category_fields',
				array(
					'legacy_field_data' => array(
						'type'    => 'CHAR(1)',
						'null'    => FALSE,
						'default' => 'n'
					)
				)
			);
			ee()->db->update('category_fields', array('legacy_field_data' => 'y'));
		}

		if ( ! ee()->db->field_exists('legacy_field_data', 'channel_fields'))
		{
			ee()->smartforge->add_column(
				'channel_fields',
				array(
					'legacy_field_data' => array(
						'type'    => 'CHAR(1)',
						'null'    => FALSE,
						'default' => 'n'
					)
				)
			);
			ee()->db->update('channel_fields', array('legacy_field_data' => 'y'));
		}

		if ( ! ee()->db->field_exists('m_legacy_field_data', 'member_fields'))
		{
			ee()->smartforge->add_column(
				'member_fields',
				array(
					'm_legacy_field_data' => array(
						'type'    => 'CHAR(1)',
						'null'    => FALSE,
						'default' => 'n'
					)
				)
			);
			ee()->db->update('member_fields', array('m_legacy_field_data' => 'y'));
		}
	}

	private function removeMemberHomepageTable()
	{
		ee()->smartforge->drop_table('member_homepage');
	}

	private function moveMemberFields()
	{
		ee()->load->model('member_model');

		// Do we need a preflight

		$fields = array(
			'url' => array(
				'field_label' => 'URL',
				'field_description' => '',
				'field_type' => 'url'
					),
			'location' => array(
				'field_label' => 'Location',
				'field_description' => '',
				'field_type' => 'text'
					),
			'occupation' => array(
				'field_label' => 'Occupation',
				'field_description' => '',
				'field_type' => 'text'
					),
			'interests' => array(
				'field_label' => 'Interests',
				'field_description' => '',
				'field_type' => 'text'
					),
			'aol_im' => array(
				'field_label' => 'AOL IM',
				'field_description' => '',
				'field_type' => 'text'
					),
			'yahoo_im' => array(
				'field_label' => 'Yahoo IM',
				'field_description' => '',
				'field_type' => 'text'
					),
			'msn_im' => array(
				'field_label' => 'MSN IM',
				'field_description' => '',
				'field_type' => 'text'
					),
			'icq' => array(
				'field_label' => 'ICQ',
				'field_description' => '',
				'field_type' => 'text'
					),
			'bio' => array(
				'field_label' => 'Bio',
				'field_description' => '',
				'field_type' => 'textarea'
				),
			'bday_d' => array(),
			'bday_m' => array(),
			'bday_y' => array()
			);

		// Safety check- does field already exist as a custom field
		$existing = ee('Model')->get('MemberField')->fields('m_field_name')->all();
		$map = array();

		if (count($existing) > 0)
		{
			foreach ($existing as $mfield)
			{
				$map[$mfield->m_field_name] = $mfield->field_id;
			}
		}

		$member_columns = ee()->db->list_fields('members');

		$member_table_fields = array();
		$vars = 0;
		foreach ($fields as $field => $data)
		{
			// does field still exist in exp_members
			// if not, there isn't much we can do
			if (in_array($field, $member_columns))
			{
				$member_table_fields[] = $field;
			}
			else
			{
				continue;
			}

			// member field already exists
			if (in_array($field, array_keys($map)))
			{
					continue;
			}

			$vars++;
			ee()->db->select_max($field);
		}

		$make = array();
		if ($vars > 0)
		{
			$query = ee()->db->get('members');
			$make = $query->row_array();

			// Removes all false and null, including 0
			$make = array_filter($make);
		}


		// All fields either exist AND are no longer in exp_members
		// Bail out
		if (empty($member_table_fields) OR empty($make))
		{
			return;
		}

		// If they have any birthday fields, we'll create a birthday variable
		$birthday = FALSE;
		foreach (array('bday_d', 'bday_m', 'bday_y') as $bday)
		{
			if (array_key_exists($bday, $make))
			{
				$fields['birthday'] = array(
					'field_label' => 'Birthday',
					'field_description' => '',
					'field_type' => 'date'
				);

				$make['birthday'] = TRUE;
				$birthday = TRUE;
				break;
			}
		}

		unset($make['bday_y']);
		unset($make['bday_m']);
		unset($make['bday_d']);

		ee()->load->library('api');

		// Create custom fields
		foreach ($make as $name => $val)
		{
			if (in_array($name, array_keys($map)) OR in_array($name, array('bday_d', 'bday_m', 'bday_y')))
			{
				continue;
			}

			$field = ee('Model')->make('MemberField');

			$field->m_field_type = $fields[$name]['field_type'];

			$field->m_field_label = $fields[$name]['field_label'];
			$field->m_field_name = $name;
			$field->m_field_description = $fields[$name]['field_description'];

			$field->save();

			$map[$field->m_field_name] = $field->field_id;
		}


		// Copy custom field data

		// Should work for everything except birthday
		foreach ($make as $field_name => $vals)
		{
			if ($field_name == 'birthday')
			{
				continue;
			}

			// ARGH- how to handle re-inserting
			// If you rerun it, it just inserts again
			// for all but birthday, do a count, skip if it has any?
			if (ee()->db->count_all_results('member_data_field_'.$map[$field_name]) !== 0)
			{
				continue;
			}


			$sql = 'INSERT INTO exp_member_data_field_'.$map[$field_name].' (member_id, m_field_id_'.$map[$field_name].')
                SELECT m.member_id, m.'.$field_name.' FROM exp_members m';

			ee()->db->query($sql);
		}

		$data = [];
		if ($birthday AND ee()->db->count_all_results('member_data_field_'.$map['birthday']) == 0)
		{
			$total_members = ee()->db->count_all_results('members');
			$limit = 5000;
			$offset = 0;

			while ($offset + $limit <= $total_members)
			{
				$data = [];

				$query = ee()->db->select('member_id, bday_d, bday_m, bday_y')
					->limit($limit)
					->offset($offset)
					->get('members');

				foreach ($query->result() as $row)
				{
					if (empty($row->bday_y) AND empty($row->bday_m) AND empty($row->bday_d))
					{
						$r['member_id'] = $row->member_id;
						$r['m_field_id_'.$map['birthday']] = 0;
					}
					else
					{
						$year = ( ! empty($row->bday_y) AND strlen($row->bday_y) == 4) ? $row->bday_y : '1900';
						$month = ( ! empty($row->bday_m)) ? str_pad($row->bday_m, 2,"0", STR_PAD_LEFT) : '01';
						$day = ( ! empty($row->bday_d)) ? str_pad($row->bday_d, 2,"0", STR_PAD_LEFT) : '01';

						$bday_timestamp = ee()->localize->string_to_timestamp($year.'-'.$month.'-'.$day.' 01:00 AM');
						$bday_timestamp = (int) $bday_timestamp;

						// Sorry, people born <= 1901 or >= 2038
						$max_32bit_int = 2147483648;
						if ($bday_timestamp > $max_32bit_int)
						{
							$bday_timestamp = $max_32bit_int;
						}
						elseif ($bday_timestamp < -$max_32bit_int)
						{
							$bday_timestamp = -$max_32bit_int;
						}

						$r['member_id'] = $row->member_id;
						$r['m_field_id_'.$map['birthday']] = $bday_timestamp;

					}
					$data[] = $r;
				}

				ee()->db->insert_batch(
					'member_data_field_'.$map['birthday'], $data
				);

				$offset += $limit;
			}
		}

		// Drop columns from exp_members
		foreach ($fields as $field => $data)
		{
			ee()->smartforge->drop_column('members', $field);
		}
	}

	private function warnAboutBirthdayTag()
	{
		ee()->update_notices->setVersion('4.0');
		ee()->update_notices->header('{birthday} member field variable is now a date type variable');
		ee()->update_notices->item(' Checking for templates to review ...');

		ee()->remove('template');
		require_once(APPPATH . 'libraries/Template.php');
		ee()->set('template', new \Installer_Template());

		$installer_config = ee()->config;
		ee()->remove('config');
		ee()->set('config', new \MSM_Config());

		$templates = ee()->template_model->fetch_last_edit(array(), TRUE);

		$temp_warnings = array();
		$snip_warnings = array();
		$warnings = FALSE;
		$tag = LD.'birthday'.RD;
		foreach ($templates as $template)
		{
			if (strpos($template->template_data, $tag) !== FALSE)
			{
        		$temp_warnings[] = $template->get_group()->group_name.'/'.$template->template_name;
				$warnings = TRUE;
			}
		}

		// Check snippets
		ee()->load->model('snippet_model');
		$snippets = ee()->snippet_model->fetch();

		foreach($snippets as $snippet)
		{
			if (strpos($snippet->snippet_contents, $tag) !== FALSE)
			{
			$snip_warnings[] = $snippet->snippet_name;
				$warnings = TRUE;
			}
		}

		// Output the templates/snippets that have {birthday} in them
		if ($warnings)
		{
			$notice = 'The member profile variable {birthday} has been removed from the default member variables and replaced by
			a date type member custom field variable.  If you are currently using this variable in templates or snippets, you will want to edit it to include
			date formatting parameters.<br><br>';

			if (count($temp_warnings))
			{
				$notice .= 'The following templates contain a {birthday} variable:<br><br>'.implode('<br>', $temp_warnings).'<br><br>';
			}

			if (count($snip_warnings))
			{
				$notice .= 'The following snippets contain a {birthday} variable:<br><br>'.implode('<br>', $snip_warnings).'<br><br>';
			}

			ee()->update_notices->item($notice);
		}
		else
		{
			ee()->update_notices->item('No templates contain the {birthday} variable.');
		}

		ee()->update_notices->item('Done.');

		ee()->remove('config');
		ee()->set('config', $installer_config);
	}


	/**
	 * Remove save_tmpl_files from exp_sites
	 * If all sites currently set to no, add a config override
	 */
	private function globalizeSave_tmpl_files()
	{
		// Do we need to override?
		$save_as_file = FALSE;
		$update_config = FALSE;

		$all_site_ids_query = ee()->db->select('site_id')
			->get('sites')
			->result();

		foreach ($all_site_ids_query as $site)
		{
			$config = ee()->config->site_prefs('', $site->site_id, FALSE);

			// If ANY sites save as file, they all must
			if (isset($config['save_tmpl_files']))
			{
				// Only update config if the key still exists
				$update_config = TRUE;

				if ($config['save_tmpl_files'] == 'y')
				{
					$save_as_file = TRUE;
					break;
				}
			}

		}

		ee()->config->remove_config_item(array('save_tmpl_files'));

		if ($update_config && $save_as_file == FALSE)
		{
			// Add config override
			ee()->config->_update_config(array('save_tmpl_files' => 'n'));
		}
	}

	/**
	 * Clear old current_version cache, version info for 4.0 is in a different
	 * format
	 */
	private function clearCurrentVersionCache()
	{
		ee()->cache->delete('current_version', \Cache::GLOBAL_SCOPE);
	}

	/**
	 * Relationships started saving as NULL in 3.5.7, normalize all previous
	 * entries to be NULL as well
	 */
	private function nullOutRelationshipChannelDataFields()
	{
		$channel_fields = ee()->db->where('field_type', 'relationship')
			->where('legacy_field_data', 'y')
			->get('channel_fields');

		$update = [];

		// Will have to do one query per field since we have to specify a where
		// key and we cannot have the where key and update key be the same in
		// update_batch
		foreach ($channel_fields->result_array() as $field)
		{
			$field_name = 'field_id_'.$field['field_id'];
			ee()->db->update(
				'channel_data',
				[$field_name => NULL],
				[$field_name => '']
			);
		}
	}

	/**
	* Adds member_news_views, see Member\NewsViews model
	*/
	private function addNewsViewsTable()
	{
		ee()->dbforge->add_field(
			array(
				'news_id' => array(
					'type'           => 'int',
					'constraint'     => 10,
					'null'           => FALSE,
					'unsigned'       => TRUE,
					'auto_increment' => TRUE
				),
					'version' => array(
					'type'       => 'varchar',
					'constraint' => 10
				),
					'member_id' => array(
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => FALSE,
					'default'    => 0
				)
			)
		);

		ee()->dbforge->add_key('news_id', TRUE);
		ee()->dbforge->add_key('member_id');
		ee()->smartforge->create_table('member_news_views');
	}

	/**
	 * Adds an index to exp_channel_titles for optimizing our channel entry tags
	 */
	private function addSortIndexToChannelTitles()
	{
		ee()->smartforge->add_key('channel_titles', array('sticky', 'entry_date', 'entry_id'), 'sticky_date_id_idx');
	}

	/**
	 * Adds a new image quality column to the file dimensions table
	 */
	private function addImageQualityColumn()
	{
		ee()->smartforge->add_column(
			'file_dimensions',
			array(
				'quality' => array(
					'type'       => 'tinyint',
					'constraint' => 1,
					'unsigned'   => TRUE,
					'default'    => 90,
				)
			)
		);
	}

	private function addSpamModerationPermissions()
	{
		ee()->smartforge->add_column(
			'member_groups',
			array(
				'can_moderate_spam' => array(
					'type'       => 'CHAR',
					'constraint' => 1,
					'default'    => 'n',
					'null'       => FALSE,
				)
			)
		);

		// Only assume super admins can moderate spam
		ee()->db->update('member_groups', array('can_moderate_spam' => 'y'), array('group_id' => 1));
	}

	private function runSpamModuleUpdate()
	{
		// run the Spam module update
		$spam = ee('Addon')->get('spam');
		if ($spam && $spam->hasUpdate())
		{
			$class = $spam->getInstallerClass();
			$UPD = new $class;

			if ($UPD->update($spam->getInstalledVersion()) !== FALSE)
			{
				$module = ee('Model')->get('Module')
					->filter('module_name', 'Spam')
					->first();

				$module->module_version = $spam->getVersion();
				$module->save();
			}
		}
	}

	/**
	 * Adds a primary key to exp_file_categories
	 */
	private function addPrimaryKeyToFileCategoryTable()
	{
		// First modify the file_id and cat_id columns to not accept NULL values
		ee()->smartforge->modify_column(
			'file_categories',
			array(
				'file_id' => array(
					'name'       => 'file_id',
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => FALSE
				),
				'cat_id' => array(
					'name'       => 'cat_id',
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => FALSE
				)
			)
		);

		// Second remove the file_id index
		ee()->smartforge->drop_key('file_categories', 'file_id');

		// Finally create the primary key
		ee()->smartforge->add_key('file_categories', array('file_id', 'cat_id'), 'PRIMARY');
	}

	/**
	 * New "Fluid Field" Field Type in 4.0.0
	 */
	private function addFluidFieldField()
	{
		if (ee()->db->where('name', 'fluid_field')->get('fieldtypes')->num_rows() > 0)
		{
			return;
		}

		ee()->db->insert('fieldtypes', array(
				'name' => 'fluid_field',
				'version' => '1.0.0',
				'settings' => base64_encode(serialize(array())),
				'has_global_settings' => 'n'
			)
		);

		ee()->db->insert('content_types', array(
			'name' => 'fluid_field'
		));

		ee()->dbforge->add_field(
			array(
				'id' => array(
					'type'           => 'int',
					'constraint'     => 11,
					'unsigned'       => TRUE,
					'null'           => FALSE,
					'auto_increment' => TRUE
				),
				'fluid_field_id' => array(
					'type'       => 'int',
					'constraint' => 11,
					'unsigned'   => TRUE,
					'null'       => FALSE
				),
				'entry_id' => array(
					'type'       => 'int',
					'constraint' => 11,
					'unsigned'   => TRUE,
					'null'       => FALSE
				),
				'field_id' => array(
					'type'       => 'int',
					'constraint' => 11,
					'unsigned'   => TRUE,
					'null'       => FALSE
				),
				'field_data_id' => array(
					'type'       => 'int',
					'constraint' => 11,
					'unsigned'   => TRUE,
					'null'       => FALSE
				),
				'order' => array(
					'type'       => 'int',
					'constraint' => 5,
					'unsigned'   => TRUE,
					'null'       => FALSE,
					'default'    => 0
				)
			)
		);

		ee()->dbforge->add_key('id', TRUE);
		ee()->dbforge->add_key(array('fluid_field_id', 'entry_id'));
		ee()->smartforge->create_table('fluid_field_data');

		ee()->smartforge->add_column(
			'relationships',
			array(
				'fluid_field_data_id' => array(
					'type'       => 'int',
					'constraint' => 10,
					'default'    => 0,
					'unsigned'   => TRUE,
					'null'       => FALSE,
				)
			)
		);

		$sql = "SHOW TABLES FROM " . ee()->db->_escape_char . ee()->db->database . ee()->db->_escape_char . " LIKE '%_grid_field_%'";
		$query = ee()->db->query($sql);

		$tables = [];

		foreach ($query->result_array() as $row)
		{
			$tables[] = array_shift($row);
		}

		$dbprefix = ee()->db->dbprefix;

		foreach ($tables as $table)
		{
			if (strpos($table, $dbprefix) === 0)
			{
				$table = substr($table, strlen($dbprefix));
			}

			ee()->smartforge->add_column(
				$table,
				array(
					'fluid_field_data_id' => array(
						'type'       => 'int',
						'constraint' => 10,
						'default'    => 0,
						'unsigned'   => TRUE,
						'null'       => FALSE,
					)
				)
			);
		}
	}

	/**
	 * New "Duration" Field Type
	 */
	private function addDurationField()
	{
		if (ee()->db->where('name', 'duration')->get('fieldtypes')->num_rows() > 0)
		{
			return;
		}

		ee()->db->insert('fieldtypes', array(
				'name' => 'duration',
				'version' => '1.0.0',
				'settings' => base64_encode(serialize(array())),
				'has_global_settings' => 'n'
			)
		);
	}

	private function addCommentMenuExtension()
	{
		if (ee()->db
			->where('class', 'Comment_ext')
			->where('hook', 'cp_custom_menu')
			->get('extensions')->num_rows() > 0)
		{
			return;
		}

		$data = array(
			'class'		=> 'Comment_ext',
			'method'	=> 'addCommentMenu',
			'hook'		=> 'cp_custom_menu',
			'settings'	=> serialize([]),
			'version'	=> '2.3.3',
			'enabled'	=> 'y'
		);

		ee()->db->insert('extensions', $data);
	}

	/**
	 * Gets rid of status groups, makes statuses install-wide, deletes duplicate
	 * status names, and assigns statuses to channels
	 */
	private function emancipateStatuses()
	{
		if (ee()->db->table_exists('channels_statuses') && ee()->db->field_exists('status_id', 'channel_titles'))
		{
			return;
		}

		$statuses = ee()->db->get('statuses')->result();

		// Here, we'll decide which statuses to keep, we'll just keep the first of
		// a name we come across and delete the rest, keeping all statuses unique
		$keep = [];
		$delete = [];
		foreach ($statuses as $status)
		{
			if (isset($keep[$status->status]))
			{
				$delete[] = $status->status_id;
				continue;
			}

			$keep[$status->status] = $status->status_id;
		}

		$channels_status_groups = ee()->db->select('channel_id, status_group')
			->get('channels')
			->result();

		// Create an easily indexable array to see which statuses need to be
		// re-assigned to a channel
		$statuses_by_group = [];
		foreach ($statuses as $status)
		{
			if ( ! isset($statuses_by_group[$status->group_id]))
			{
				$statuses_by_group[$status->group_id] = [];
			}

			$statuses_by_group[$status->group_id][] = $status;
		}

        // Create a new association of channels to statuses
        $channels_statuses = [];
        foreach ($channels_status_groups as $channel)
        {
            if (isset($statuses_by_group[$channel->status_group]))
            {
                foreach ($statuses_by_group[$channel->status_group] as $status)
                {
                    $status_item = [
                        'channel_id' => $channel->channel_id,
                        'status_id' => $keep[$status->status]
                    ];

                    if ( ! in_array($status, $channels_statuses))
                    {
                        $channels_statuses[] = $status_item;
                    }
                }
            }
            else
            {
                $status_item = [
                    'channel_id' => $channel->channel_id,
                    'status_id' => $keep['open']
                ];

                if ( ! in_array($status, $channels_statuses))
                {
                    $channels_statuses[] = $status_item;
                }

                $status_item = [
                    'channel_id' => $channel->channel_id,
                    'status_id' => $keep['closed']
                ];

                if ( ! in_array($status, $channels_statuses))
                {
                    $channels_statuses[] = $status_item;
                }
            }
        }

		ee()->dbforge->add_field(
			array(
				'channel_id' => array(
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				),
				'status_id' => array(
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				)
			)
		);
		ee()->dbforge->add_key(array('channel_id', 'status_id'), TRUE);
		ee()->smartforge->create_table('channels_statuses');

		if ( ! empty($channels_statuses))
		{
			// truncate in case this is being re-run from an incomplete upgrade
			ee()->db->truncate('channels_statuses');
			ee()->db->insert_batch('channels_statuses', $channels_statuses);
		}

		ee()->smartforge->drop_column('channels', 'status_group');
		ee()->smartforge->drop_column('statuses', 'group_id');
		ee()->smartforge->drop_column('statuses', 'site_id');
		ee()->smartforge->drop_table('status_groups');

		if ( ! empty($delete))
		{
			ee()->db->where_in('status_id', $delete);
			ee()->db->delete('statuses');
		}

		ee()->smartforge->add_column(
			'channel_titles',
			array(
				'status_id' => array(
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				)
			),
			'status'
		);

		// Fill in status_id for any that we have
		$update_batch = [];
		foreach ($keep as $status => $status_id)
		{
			$update_batch[] = [
				'status' => $status,
				'status_id' => $status_id
			];
		}

		if ( ! empty($update_batch))
		{
			ee()->db->update_batch('channel_titles', $update_batch, 'status');
		}
	}
}
// END CLASS

// EOF
