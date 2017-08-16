<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

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
		$steps = new ProgressIterator(
			array(
				'emancipateTheFields',
				'addFieldDataFlag',
				'removeMemberHomepageTable',
				'globalizeSave_tmpl_files',
				'nullOutRelationshipChannelDataFields',
				'addSortIndexToChannelTitles',
				'addImageQualityColumn',
				'addSpamModerationPermissions',
				'runSpamModuleUpdate',
				'addPrimaryKeyToFileCategoryTable',
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

	/**
	 * Remove save_tmpl_files from exp_sites
	 * If all sites currently set to no, add a config override
	 */
	private function globalizeSave_tmpl_files()
	{
		// Do we need to override?
		$save_as_file = FALSE;
		$msm_config = new MSM_Config();

		$all_site_ids_query = ee()->db->select('site_id')
			->get('sites')
			->result();

		foreach ($all_site_ids_query as $site)
		{
			$config = ee()->config->site_prefs('', $site->site_id, FALSE);

			// If ANY sites save as file, they all must
			if (isset($config['save_tmpl_files']) && $config['save_tmpl_files'] == 'y')
			{
				$save_as_file = TRUE;
				break;
			}

		}

		ee()->config->remove_config_item(array('save_tmpl_files'));

		if ($save_as_file == FALSE)
		{
			// Add config override
			ee()->config->_update_config(array('save_tmpl_files' => 'n'));
		}
	}

	/**
	 * Relationships started saving as NULL in 3.5.7, normalize all previous
	 * entries to be NULL as well
	 */
	private function nullOutRelationshipChannelDataFields()
	{
		$channel_fields = ee()->db->where('field_type', 'relationship')
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
		if ($spam->hasUpdate())
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
}

// EOF
