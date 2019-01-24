<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Database\DBConfig;

/**
 * Loads and provides access to the requirements checker located in the
 * installer directory
 */
class RequirementsCheckerLoader {

	private $filesystem = NULL;
	private $requirements = NULL;
	private $path = '';

	/**
	 * Constructor
	 *
	 * @param	Filesystem	$filesystem	Filesystem object instance
	 */
	public function __construct($filesystem)
	{
		$this->filesystem = $filesystem;
		$this->path = SYSPATH . '/ee/installer/updater/EllisLab/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php';
	}

	/**
	 * Set an alternnate RequirementsChecker path, such as in an extracted
	 * automatic update archive
	 *
	 * @param	string	$path	Path to RequirementsChecker class file
	 */
	public function setClassPath($path)
	{
		$this->path = $path;
	}

	/**
	 * Attempts to load the requirements checker file and runs the checker
	 *
	 * @return	mixed	TRUE if good, or array of failed Requirement objects
	 */
	public function check()
	{
		if ( ! $this->filesystem->exists($this->path))
		{
			throw new UpdaterException('Could not find RequirementsChecker file.', 13);
		}

		require_once($this->path);

		if ( ! class_exists('RequirementsChecker'))
		{
			throw new UpdaterException('Could not load RequirementsChecker class.', 14);
		}

		$config = ee('Config')->getFile();
		$db_config = new DBConfig($config);

		$this->requirements = new \RequirementsChecker($db_config->getGroupConfig());

		return $this->requirements->check();
	}
}

// EOF
