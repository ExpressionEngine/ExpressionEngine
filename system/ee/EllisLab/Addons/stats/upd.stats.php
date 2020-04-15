<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use EllisLab\ExpressionEngine\Service\Addon\Installer;

/**
 * Stats Module update class
 */
class Stats_upd extends Installer
{

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update($current='')
	{
		if (version_compare($current, $this->version, '=='))
		{
			return FALSE;
		}

		if (version_compare($current, '2.0', '<'))
		{
			ee()->load->dbforge();
			ee()->dbforge->drop_column('stats', 'weblog_id');
		}

		return TRUE;
	}

}
// END CLASS

// EOF
