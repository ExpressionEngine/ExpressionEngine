<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Updater\Version_5_0_0;

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
			[
				'addTheMemberToGroupPivotTable',
			]
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function addTheMemberToGroupPivotTable()
	{
		if (ee()->db->table_exists('members_member_groups'))
		{
			return;
		}

		// Add the Many-to-Many tables
		ee()->dbforge->add_field(
			array(
				'member_id' => array(
					'type'       => 'int',
					'constraint' => 10,
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
		ee()->dbforge->add_key(array('member_id', 'group_id'), TRUE);
		ee()->smartforge->create_table('members_member_groups');
	}
}

// EOF
