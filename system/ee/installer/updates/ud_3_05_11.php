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
		$steps = new \ProgressIterator(
			array(
				'addSpamModerationPermissions',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
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
}

// EOF
