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
 * Emoji Module update class
 */
class Emoji_upd {

	public $version;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$addon = ee('Addon')->get('emoji');
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
			'module_name' => 'Emoji',
			'module_version' => $this->version,
			'has_cp_backend' => FALSE,
			'has_publish_fields' => FALSE,
		])->save();

		return TRUE;
	}

	/**
	 * Module Uninstaller
	 *
	 * @return	bool
	 */
	function uninstall()
	{
		ee('Model')->get('Module')->filter('module_name', 'Emoji')->delete();
		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @return	bool
	 */
	public function update($current='')
	{
		return TRUE;
	}
}
// END CLASS

// EOF
