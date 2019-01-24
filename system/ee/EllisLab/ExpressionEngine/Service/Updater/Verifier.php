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

use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * Updater file verifier
 *
 * Given a path to files and a hashmap of hashes for those files, verifies the
 * hashes of the files on disk matches the hashmap
 */
class Verifier {

	protected $filesystem = NULL;

	/**
	 * Constructor
	 *
	 * @param	Filesystem	$filesystem	Filesystem library object
	 */
	public function __construct(Filesystem $filesystem)
	{
		$this->filesystem = $filesystem;
	}

	/**
	 * Given a path to a directory and a manifest of its files' respective hashes,
	 * makes sure those hashes match the files on the filesystem
	 *
	 * @param	string	$path		Path to directory to check
	 * @param	string	$hash_path	Path to location of hash manifest file
	 * @param	string	$subpath	Optional subpath inside hashmap to limit verification to that path
	 * @param	array	$exclusions	Array of any paths to exlude when verifying
	 */
	public function verifyPath($path, $hash_path, $subpath = '', Array $exclusions = [])
	{
		$hashmap = json_decode($this->filesystem->read($hash_path), TRUE);
		$subpath = ltrim($subpath, '/');

		$missing_files = [];
		$corrupt_files = [];

		foreach ($hashmap as $file_path => $hash)
		{
			// If a subpath was specified but the current file is not in that path, skip it
			if ( ! empty($subpath) && substr($file_path, 0, strlen($subpath)) !== $subpath)
			{
				continue;
			}

			// Skip paths we don't want to verify
			foreach ($exclusions as $exclude)
			{
				if (substr($file_path, 0, strlen($exclude)) === $exclude)
				{
					continue 2;
				}
			}

			// Absolute server path to the file in question
			if (empty($subpath))
			{
				$absolute_file_path = $path . DIRECTORY_SEPARATOR . $file_path;
			}
			else
			{
				$absolute_file_path = $path . str_replace($subpath, '', $file_path);
			}

			// Does the file even exist?
			if ( ! $this->filesystem->exists($absolute_file_path))
			{
				$missing_files[] = $file_path;
			}
			// If so, does it have integrity?
			else if ($this->filesystem->hashFile('sha384', $absolute_file_path) !== $hash)
			{
				$corrupt_files[] = $file_path;
			}
		}

		if ( ! empty($missing_files))
		{
			throw new UpdaterException(
				sprintf(
					lang('could_not_find_files'),
					implode("\n", $missing_files)
				),
			9);
		}

		if ( ! empty($corrupt_files))
		{
			throw new UpdaterException(
				sprintf(
					lang('could_not_verify_file_integrity'),
					implode("\n", $corrupt_files)
				),
			10);
		}

		return TRUE;
	}
}

// EOF
