<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Service\Addon\Installer;

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

		// Add stat sync action
		if (version_compare($current, '2.1', '<'))
		{

			// Create syncing action
			$data = [
				'class'			=> 'Stats',
				'method'		=> 'sync_stats',
				'csrf_exempt'	=> 1,
			];

			ee()->db->insert('actions', $data);

		}

		return TRUE;
	}

}
// END CLASS

// EOF
