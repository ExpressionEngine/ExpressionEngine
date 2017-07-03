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
				'removeMemberHomepageTable',
				'globalizeSave_tmpl_files',
				'nullOutRelationshipChannelDataFields',
				'addImageQualityColumn'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
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
					'default'    => '90',
				)
			)
		);
	}

}

// EOF
