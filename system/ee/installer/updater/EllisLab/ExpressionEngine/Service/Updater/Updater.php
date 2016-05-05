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

	public function backupExistingInstallFiles()
	{
		// First backup the contents of system/ee
		$system_backup_dir = $this->getBackupsPath() . 'system_ee/';

		$this->backup(SYSPATH.'ee/', $system_backup_dir);

		// Now, theme folder for each site
		foreach ($this->configs['theme_paths'] as $site_id => $theme_path)
		{
			$theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';
			$theme_backup_dir = $this->getBackupsPath() . 'themes_ee_'.$site_id.'/';

			$this->backup($theme_path, $theme_backup_dir);
		}
	}

	protected function backup($source, $destination)
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
