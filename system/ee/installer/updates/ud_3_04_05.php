<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Updater\Version_3_4_5;

/**
 * Update
 */
class Updater {

	var $version_suffix = '';

	public $affected_tables = ['exp_actions', 'exp_modules'];

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
