<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_3_5_7;

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
				'modifyChannelDataRelationshipFields',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Make sure all relationship fields are of type VARCHAR, older fields may
	 * be of type INT and may complain when entries are added with no value
	 * specified
	 */
	protected function modifyChannelDataRelationshipFields()
	{
		// Get all relationship fields
		$channel_fields = ee()->db->where('field_type', 'relationship')
			->get('channel_fields');

		foreach ($channel_fields->result_array() as $field)
		{
			$field_name = 'field_id_'.$field['field_id'];

			ee()->smartforge->modify_column(
				'channel_data',
				array(
					$field_name => array(
						'name'       => $field_name,
						'type'       => 'VARCHAR',
						'constraint' => 8,
						'null'       => TRUE
					)
				)
			);
		}
	}
}

// EOF
