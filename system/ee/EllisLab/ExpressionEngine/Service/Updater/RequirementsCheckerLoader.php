<?php

namespace EllisLab\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Database\DBConfig;

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
 * ExpressionEngine Updater Requirements class
 *
 * @package		ExpressionEngine
 * @subpackage	Updater
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
