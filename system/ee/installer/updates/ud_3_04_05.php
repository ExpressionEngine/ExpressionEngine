<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_3_4_5;

/**
 * Update
 */
class Updater {

	var $version_suffix = '';

	public $affected_tables = ['actions', 'modules'];

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new \ProgressIterator(
			array(
				'addRelationshipModule',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function addRelationshipModule()
	{
		$installed = ee()->db->get_where('modules', array('module_name' => 'Relationship'));

		if ($installed->num_rows() > 0)
		{
			return;
		}

		ee()->db->insert(
			'modules',
			array(
				'module_name'        => 'Relationship',
				'module_version'     => '1.0.0',
				'has_cp_backend'     => 'n',
				'has_publish_fields' => 'n'
			)
		);

		ee()->db->insert_batch(
			'actions',
			array(
				array(
					'class'  => 'Relationship',
					'method' => 'entryList'
				)
			)
		);
	}
}

// EOF
