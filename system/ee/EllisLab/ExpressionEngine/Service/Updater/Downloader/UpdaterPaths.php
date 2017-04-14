<?php

namespace EllisLab\ExpressionEngine\Service\Updater\Downloader;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016=7, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Updater Steppable Trait
 *
 * @package		ExpressionEngine
 * @subpackage	Updater
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
trait UpdaterPaths {

	protected $filename = 'ExpressionEngine.zip';
	protected $extracted_folder = 'ExpressionEngine';

	/**
	 * Constructs and returns the path to the downloaded zip archive
	 *
	 * @return	string	Path to downloaded zip archive
	 */
	protected function getArchiveFilePath()
	{
		return $this->path() . $this->filename;
	}

	/**
	 * Constructs and returns the path to the extracted archive path
	 *
	 * @return	string	Path to extracted archive
	 */
	protected function getExtractedArchivePath()
	{
		return $this->path() . $this->extracted_folder;
	}

	/**
	 * Optionally creates and returns the path in which we will be working with
	 * our files
	 *
	 * @return	string	Path to folder in the cache folder for working with updates
	 */
	protected function path()
	{
		$cache_path = PATH_CACHE . 'ee_update/';

		if ( ! is_dir($cache_path))
		{
			$this->filesystem->mkDir($cache_path);
		}

		return $cache_path;
	}
}
// EOF
