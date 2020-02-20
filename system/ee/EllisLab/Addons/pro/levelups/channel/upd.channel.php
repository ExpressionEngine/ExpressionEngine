<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use EllisLab\Addons\Pro\Components\LiteLoader;

LiteLoader::loadIntoNamespace('channel/upd.channel.php');

/**
 * Channel Module update
 */
class Channel_upd extends Lite\Channel_upd {

	var $version		= '2.1.0';

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */
	function install()
	{

		$data = array(
			'class' => 'Channel',
			'method' => 'single_field_editor'
		);

		ee()->db->insert('actions', $data);

		return TRUE;
	}

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{

		ee()->db->delete('actions', array('class' => 'Channel', 'method' => 'single_field_editor'));

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update()
	{
		return TRUE;
	}

}
// END CLASS

// EOF
