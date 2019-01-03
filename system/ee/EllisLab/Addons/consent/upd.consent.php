<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Consent Module update class
 */
class Consent_upd {

	function __construct()
	{
		ee()->load->dbforge();
		$addon = ee('Addon')->get('consent');
		$this->version = $addon->getVersion();
	}

	/**
	 * Module Installer
	 *
	 * @return	bool
	 */
	function install()
	{
		ee('Model')->make('Module', [
			'module_name' => 'Consent',
			'module_version' => $this->version,
			'has_cp_backend' => 'n',
		])->save();

		$actions = [
			'grantConsent',
			'submitConsent',
			'withdrawConsent',
		];

		foreach ($actions as $action)
		{
			ee('Model')->make('Action', [
				'class' => 'Consent',
				'method' => $action,
			])->save();
		}

		return TRUE;
	}

	/**
	 * Module Uninstaller
	 *
	 * @return	bool
	 */
	function uninstall()
	{
		$module = ee('Model')->get('Module')
			->filter('module_name', 'Consent')
			->first();

		ee('Model')->get('Action')
			->filter('class', 'Consent')
			->delete();

		ee('db')->where('module_id', $module->module_id)
			->delete('module_member_groups');

		$module->delete();

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @param string $current Currently installed version number
	 * @return	bool
	 */
	public function update($current='')
	{
		return TRUE;
	}
}
// END CLASS

// EOF
