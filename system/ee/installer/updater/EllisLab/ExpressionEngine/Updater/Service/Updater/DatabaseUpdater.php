<?php

namespace EllisLab\ExpressionEngine\Updater\Service\Updater;

use EllisLab\ExpressionEngine\Updater\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Updater\Service\Updater\Steppable;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Database Updater class
 *
 * @package		ExpressionEngine
 * @subpackage	Updater
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class DatabaseUpdater {
	use Steppable;

	protected $steps = [];

	protected $from_version;
	protected $filesystem;
	protected $update_files_path = SYSPATH . 'ee/installer/updates/';

	/**
	 * Constructor, of course
	 *
	 * @param	string		$from_version	Version we are updating from
	 * @param	Filesystem	$filesystem		Filesystem lib object so we can
	 *   traverse the update files directory
	 */
	public function __construct($from_version, Filesystem $filesystem)
	{
		$this->from_version = $from_version;
		$this->filesystem = $filesystem;

		$this->steps = $this->getSteps();
	}

	/**
	 * Given the version we are updating from, do we even have any update files
	 * to run?
	 *
	 * @return	boolean	Whether or not there are updates to run
	 */
	public function hasUpdatesToRun()
	{
		return ! empty($this->steps);
	}

	/**
	 * Runs a given update file
	 *
	 * @param	string	$filename	Base file name, no path, e.g. 'ud_4_00_00.php'
	 */
	public function runUpdateFile($filename)
	{
		$this->filesystem->include($this->update_files_path . $filename);

		$updater = new \Updater();
		$updater->do_update();
		unset($updater);
	}

	/**
	 * Generates an array of Steppable steps
	 *
	 * @return	array	Array of steps, e.g.
	 *   [
	 *   	'runUpdateFile[ud_4_00_00.php]',
	 *   	...
	 *   ]
	 */
	protected function getSteps()
	{
		$files = $this->filesystem->getDirectoryContents($this->update_files_path);

		$steps = [];
		foreach ($files as $filename)
		{
			$filename = pathinfo($filename);
			$filename = $filename['basename'];

			$file_version = $this->getVersionForFilename($filename);

			if (version_compare($file_version, $this->from_version, '>'))
			{
				$steps[] = sprintf('runUpdateFile[%s]', $filename);
			}
		}

		return $steps;
	}

	/**
	 * Given a base file name, returns a formatted version
	 *
	 * @param	string	$filename	Base file name, e.g. 'ud_4_00_00.php'
	 * @return	string	Formatted version, e.g. '4.0.0'
	 */
	protected function getVersionForFilename($filename)
	{
		if (preg_match('/^ud_0*(\d+)_0*(\d+)_0*(\d+).php$/', $filename, $matches))
		{
			return "{$matches[1]}.{$matches[2]}.{$matches[3]}";
		}
	}
}

// EOF
