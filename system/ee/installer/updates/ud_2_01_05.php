<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_2_1_5;

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

		$steps = array();

		// Kill blogger
		if (ee()->db->table_exists('blogger'))
		{
			$steps[] = '_transfer_blogger';
			$steps[] = '_drop_blogger';
			// remove blogger
		}

		// Add batch dir preference to exp_upload_prefs
		$steps[] = '_do_upload_pref_update';

		// Update category group
		$steps[] = '_do_cat_group_update';

		// Build file-related tables
		$steps[] = '_do_build_file_tables';

		// Permission changes
		$steps[] = '_do_permissions_update';

		// Move field_content_type to the channel_fields settings array
		$steps[] = '_do_custom_field_update';

		// Add a MySQL index or three to help performance
		$steps[] = '_do_add_indexes';

		$steps = new \ProgressIterator($steps);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Transfer Blogger configurations to the metaweblog api
	 *
	 * @return void
	 */
	function _transfer_blogger()
	{
		if ( ! ee()->db->table_exists('metaweblog_api'))
		{
			// Note that in ud_270 we add a csrf_exempt column to the actions table
			// The Metaweblog installer requires that field

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

			require EE_APPPATH.'modules/metaweblog_api/upd.metaweblog_api.php';
			$UPD = new Metaweblog_api_upd();
			$UPD->install();
		}

		$qry = ee()->db->get('blogger');

		foreach ($qry->result() as $row)
		{
			list($channel_id, $custom_field_id) = explode(':', $row->blogger_field_id);

			$qry = ee()->db->select('field_group')
								->where('channel_id', $channel_id)
								->get('channels');

			if ( ! $qry->num_rows())
			{
				// nothing we can do here, that config shouldn't work
				continue;
			}

			$fg_id = $qry->row('field_group');

			$data = array(
				'field_group_id' 		=> $fg_id,
				'content_field_id' 		=> $custom_field_id,
				'metaweblog_pref_name' 	=> $row->blogger_pref_name.'_blogger',
				'metaweblog_parse_type'	=> $row->blogger_parse_type,
			);

			ee()->db->insert('metaweblog_api', $data);
		}
	}

	/**
	 * Drop Blogger Data
	 *
	 * @return void
	 */
	function _drop_blogger()
	{
		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => 'Blogger_api'));

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_groups');

		ee()->db->where('module_name', 'Blogger_api');
		ee()->db->delete('modules');

		ee()->db->where('class', 'Blogger_api');
		ee()->db->delete('actions');

		ee()->dbforge->drop_table('blogger');
	}

	/**
	 * Upload pref table update
	 *
	 * This method adds the batch_location column to the table
	 *
	 * @return void
	 */
	private function _do_upload_pref_update()
	{
		$fields = array(
			'batch_location' => array(
				'type'			=> 'VARCHAR',
				'constraint'	=> 255,
			),
			'cat_group' => array(
				'type'			=> 'VARCHAR',
				'constraint'	=> 255
			)
		);

		ee()->smartforge->add_column('upload_prefs', $fields);

		$fields = array(
			'server_path' => array(
				'name'			=> 'server_path',
				'type'			=> 'VARCHAR',
				'constraint'	=> 255
			),
		);

		ee()->smartforge->modify_column('upload_prefs', $fields);
	}

	/**
	 * Update exp_category_groups
	 *
	 * Add a column for excluding a group from files or channel group assignment
	 *
	 * @return void
	 */
	private function _do_cat_group_update()
	{
		$fields = array(
			'exclude_group' => array(
				'type'			=> 'TINYINT',
				'constraint'	=> 1,
				'null'			=> FALSE,
				'default'		=> 0
			)
		);

		ee()->smartforge->add_column('category_groups', $fields);
	}

	/**
	 * Build the files tables:
	 * 	- watermark
	 * 	- dimensions
	 * 	- categories
	 * 	- files
	 */
	private function _do_build_file_tables()
	{
		$watermark_fields = array(
			'wm_id' => array(
				'type'				=> 'int',
				'constraint'		=> 4,
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'wm_name' => array(
				'type'				=> 'varchar',
				'constraint'		=> 80
			),
			'wm_type' => array(
				'type'				=> 'varchar',
				'constraint'		=> 10,
				'default'			=> 'text'
			),
			'wm_image_path' => array(
				'type'				=> 'varchar',
				'constraint'		=> 100
			),
			'wm_test_image_path' => array(
				'type'				=> 'varchar',
				'constraint'		=> 100
			),
			'wm_use_font' => array(
				'type'				=> 'char',
				'constraint'		=> 1,
				'default'			=> 'y'
			),
			'wm_font' => array(
				'type'				=> 'varchar',
				'constraint'		=> 30
			),
			'wm_font_size' => array(
				'type'				=> 'int',
				'constraint'		=> 3,
				'unsigned'			=> TRUE
			),
			'wm_text' => array(
				'type'				=> 'varchar',
				'constraint'		=> 100
			),
			'wm_vrt_alignment' => array(
				'type'				=> 'varchar',
				'constraint'		=> 10,
				'default'			=> 'top'
			),
			'wm_hor_alignment' => array(
				'type'				=> 'varchar',
				'constraint'		=> 10,
				'default'			=> 'left'
			),
			'wm_padding' => array(
				'type'				=> 'int',
				'constraint'		=> 3,
				'unsigned'			=> TRUE
			),
			'wm_opacity' => array(
				'type'				=> 'int',
				'constraint'		=> 3,
				'unsigned'			=> TRUE
			),
			'wm_x_offset' => array(
				'type'				=> 'int',
				'constraint'		=> 4,
				'unsigned'			=> TRUE
			),
			'wm_y_offset' => array(
				'type'				=> 'int',
				'constraint'		=> 4,
				'unsigned'			=> TRUE
			),
			'wm_x_transp' => array(
				'type'				=> 'int',
				'constraint'		=> 4
			),
			'wm_y_transp' => array(
				'type'				=> 'int',
				'constraint'		=> 4
			),
			'wm_font_color' => array(
				'type'				=> 'varchar',
				'constraint'		=> 7
			),
			'wm_use_drop_shadow' => array(
				'type'				=> 'char',
				'constraint'		=> 1,
				'default'			=> 'y'
			),
			'wm_shadow_distance' => array(
				'type'				=> 'int',
				'constraint'		=> 3,
				'unsigned'			=> TRUE
			),
			'wm_shadow_color' => array(
				'type'				=> 'varchar',
				'constraint'		=> 7
			)
		);

		ee()->dbforge->add_field($watermark_fields);
		ee()->dbforge->add_key('wm_id', TRUE);
		ee()->smartforge->create_table('file_watermarks');

		$dimension_fields = array(
			'id' => array(
				'type' => 'int',
				'constraint' => 10,
				'unsigned' => TRUE,
				'auto_increment' => TRUE
			),
			'upload_location_id' => array(
				'type' => 'int',
				'constraint' => 4,
				'unsigned' => TRUE
			),
			'title' => array(
				'type' => 'varchar',
				'constraint' => 255,
				'default' => ''
			),
			'short_name' => array(
				'type' => 'varchar',
				'constraint' => 255,
				'default' => ''
			),
			'resize_type' => array(
				'type' => 'varchar',
				'constraint' => 50,
				'default' => ''
			),
			'width' => array(
				'type' => 'int',
				'constraint' => 10,
				'default' => 0
			),
			'height' => array(
				'type' => 'int',
				'constraint' => 10,
				'default' => 0
			),
			'watermark_id' => array(
				'type' => 'int',
				'constraint' => 4,
				'unsigned' => TRUE
			)
		);

		ee()->dbforge->add_field($dimension_fields);
		ee()->dbforge->add_key('id', TRUE);
		ee()->dbforge->add_key('upload_location_id');
		ee()->smartforge->create_table('file_dimensions');

		$categories_fields = array(
			'file_id' => array(
				'type'			=> 'int',
				'constraint'	=> 10,
				'unsigned'		=> TRUE
			),
			'cat_id' => array(
				'type'			=> 'int',
				'constraint'	=> 10,
				'unsigned'		=> TRUE
			),
			'sort' => array(
				'type'			=> 'int',
				'constraint'	=> 10,
				'unsigned'		=> TRUE,
				'default'		=> 0
			),
			'is_cover' => array(
				'type'			=> 'char',
				'constraint'	=> 1,
				'default'		=> 'n'
			)
		);

		ee()->dbforge->add_field($categories_fields);
		ee()->dbforge->add_key(array('file_id', 'cat_id'));
		ee()->smartforge->create_table('file_categories');

		$files_fields = array(
			'file_id' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'site_id' => array(
				'type'				=> 'int',
				'constraint'		=> 4,
				'unsigned'			=> TRUE,
				'default'			=> 1
			),
			'title' => array(
				'type'				=> 'varchar',
				'constraint'		=> 255
			),
			'upload_location_id' => array(
				'type'				=> 'int',
				'constraint'		=> 4,
				'unsigned'			=> TRUE,
				'default'			=> 0
			),
			'rel_path' => array(
				'type'				=> 'varchar',
				'constraint'		=> 255
			),
			'status' => array(
				'type'				=> 'char',
				'constraint'		=> 1,
				'default'			=> 'o'
			),
			'mime_type' => array(
				'type'				=> 'varchar',
				'constraint'		=> 255
			),
			'file_name' => array(
				'type'				=> 'varchar',
				'constraint'		=> 255
			),
			'file_size' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'default'			=> 0
			),
			'field_1' => array(
				'type'				=> 'text'
			),
			'field_1_fmt' => array(
				'type'				=> 'tinytext'
			),
			'field_2' => array(
				'type'				=> 'text'
			),
			'field_2_fmt' => array(
				'type'				=> 'tinytext'
			),
			'field_3' => array(
				'type'				=> 'text'
			),
			'field_3_fmt' => array(
				'type'				=> 'tinytext'
			),
			'field_4' => array(
				'type'				=> 'text'
			),
			'field_4_fmt' => array(
				'type'				=> 'tinytext'
			),
			'field_5' => array(
				'type'				=> 'text'
			),
			'field_5_fmt' => array(
				'type'				=> 'tinytext'
			),
			'field_6' => array(
				'type'				=> 'text'
			),
			'field_6_fmt' => array(
				'type'				=> 'tinytext'
			),
			'metadata' => array(
				'type'				=> 'mediumtext',
				'null'				=> TRUE
			),
			'uploaded_by_member_id' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'default'			=> 0
			),
			'upload_date' => array(
				'type'				=> 'int',
				'constraint'		=> 10
			),
			'modified_by_member_id' => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'default'			=> 0
			),
			'modified_date' => array(
				'type' 				=> 'int',
				'constraint'		=> 10
			),
			'file_hw_original' => array(
				'type'				=> 'varchar',
				'constraint'		=> 20
			),
		);

		ee()->dbforge->add_field($files_fields);
		ee()->dbforge->add_key('file_id', TRUE);
		ee()->dbforge->add_key(array('upload_location_id', 'site_id'));
		ee()->smartforge->create_table('files');
	}

	/**
	 * Update exp_member_groups
	 *
	 * Add a column for can_admin_upload_prefs permission
	 *
	 * @return void
	 */
	private function _do_permissions_update()
	{
		$fields = array(
			'can_admin_upload_prefs' 	=> array(
				'type'			=> 'CHAR',
				'constraint'	=> 1,
				'null'			=> FALSE,
				'default'		=> 'n'
			)
		);

		ee()->smartforge->add_column('member_groups', $fields, 'can_admin_channels');
	}

	/**
	 * Update exp_channel_fields
	 *
	 * Move field_content_type into the field_settings array
	 *
	 * @return void
	 */
	private function _do_custom_field_update()
	{
		ee()->db->select('field_id, field_content_type, field_settings');
		ee()->db->where_in('field_type', array('file', 'text'));
		$qry = ee()->db->get('channel_fields');

		foreach ($qry->result() as $row)
		{
			$settings = unserialize(base64_decode($row->field_settings));
			$settings['field_content_type'] = $row->field_content_type;

			$settings = base64_encode(serialize($settings));


			ee()->db->where('field_id', $row->field_id);
			ee()->db->update('channel_fields', array('field_settings' => $settings));
		}
	}

	/**
	 * Add a MySQL index or two
	 */
	private function _do_add_indexes()
	{
		// We do a ton of template lookups based off the template name.  How about indexing on it?
		ee()->smartforge->add_key('templates', 'template_name');

		// Same with the channel_name in exp_channels
		ee()->smartforge->add_key('channels', 'channel_name');

		// and the same for field_type on exp_channel_fields
		ee()->smartforge->add_key('channel_fields', 'field_type');
	}
}
/* END CLASS */

// EOF
