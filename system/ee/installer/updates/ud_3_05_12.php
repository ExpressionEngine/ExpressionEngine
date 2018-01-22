<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Updater\Version_3_5_12;

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
				'alterUsernameFields'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}
		return TRUE;
	}

	private function alterUsernameFields()
	{
		$fields = array(
			'username'	=> array('type' => 'text',	'constraint' => '75',	'null' => FALSE)
		);
			ee()->smartforge->modify_column('cp_log', $fields);
			ee()->smartforge->modify_column('password_lockout', $fields);
	}
}
// EOF
