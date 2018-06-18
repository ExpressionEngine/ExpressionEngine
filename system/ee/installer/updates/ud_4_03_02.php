<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Updater\Version_4_3_2;

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
				'updateFieldFmtOptionForMemberFields',
			]
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function updateFieldFmtOptionForMemberFields()
	{
		// Fields can span Sites and do not need Groups
		ee()->smartforge->modify_column('member_fields', array(
			'm_field_fmt' => array(
				'type'       => 'varchar',
				'constraint' => 40,
			),
		));

	}
}

// EOF
