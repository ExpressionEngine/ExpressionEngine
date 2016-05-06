<?php

namespace EllisLab\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Service\Config\File;

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
 * ExpressionEngine Updater class
 *
 * @package		ExpressionEngine
 * @subpackage	Updater
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	protected $filesystem = NULL;
	protected $config = NULL;
	protected $configs = [];

	public function __construct(Filesystem $filesystem, File $config)
	{
		$this->filesystem = $filesystem;
		$this->config = $config;
		$this->configs = $this->parseConfigs();
	}

	public function updateFiles()
	{
		$this->backupExistingInstallFiles();
	}

	/**
	 * Backs up the existing install files
	 */
	public function backupExistingInstallFiles()
	{
		// First backup the contents of system/ee
		$this->move(SYSPATH.'ee/', $this->getBackupsPath() . 'system_ee/');

		// We'll only backup one theme folder, they _should_ all be the same
		// across sites
		$theme_path = array_values($this->configs['theme_paths'])[0];
		$theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

		$this->move($theme_path, $this->getBackupsPath() . 'themes_ee/');
	}

	/**
	 * Moves contents of directories to another directory
	 *
	 * @param	string	$source			Source directory
	 * @param	string	$destination	Destination directory
	 */
	protected function move($source, $destination)
	{
		if ( ! $this->filesystem->exists($destination))
		{
			$this->filesystem->mkDir($destination, FALSE);
		}

		$contents = $this->filesystem->getDirectoryContents($source);

		foreach ($contents as $path)
		{
			// Don't move ourselves when backing up system/ee
			if (substr($path, -7) == 'updater')
			{
				continue;
			}

			$new_path = str_replace($source, $destination, $path);
			$this->filesystem->rename($path, $new_path);
		}
	}

	/**
	 * Constructs and returns the backup path
	 *
	 * @return	string	Path to backups folder
	 */
	protected function getBackupsPath()
	{
		return $this->path() . 'backups/';
	}

	/**
	 * Optionally creates and returns the path in which we will be working with
	 * our files
	 *
	 * @return	string	Path to folder in the cache folder for working with updates
	 */
	protected function path()
	{
		$cache_path = $this->config->get('cache_path');

		if (empty($cache_path))
		{
			$cache_path = SYSPATH.'user'.DIRECTORY_SEPARATOR.'cache/';
		}
		else
		{
			$cache_path = rtrim($cache_path, DIRECTORY_SEPARATOR).'/';
		}

		return $cache_path . 'ee_update/';
	}

	/**
	 * Opens the configs.json file and parses the JSON as an associative array
	 *
	 * @return	array	Associative array of configs
	 */
	protected function parseConfigs()
	{
		$configs_path = $this->path() . 'configs.json';

		if ( ! $this->filesystem->exists($configs_path))
		{
			throw new UpdaterException('Cannot find '. $configs_path, 17);
		}

		return json_decode($this->filesystem->read($configs_path), TRUE);
	}
}

// EOF
