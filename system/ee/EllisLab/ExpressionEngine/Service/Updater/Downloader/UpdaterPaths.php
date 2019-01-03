<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Updater\Downloader;

/**
 * Trait to make certain commonly used paths in regards to the updater available
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
