<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\ChannelSet;

use EllisLab\ExpressionEngine\Service\File\Directory;

/**
 * Channel Set Service Factory
 */
class Factory {

	/**
	 * @var Int Site id used for import
	 */
	private $site_id;

	/**
	 * @param Int $site_id Current site
	 */
	public function __construct($site_id)
	{
		$this->site_id = $site_id;
	}

	/**
	 * Create a zip for a channel
	 *
	 * @param Array $channels Array/collection of channels
	 * @return String Path to the zip file
	 */
	public function export($channels)
	{
		$export = new Export();
		return $export->zip($channels);
	}

	/**
	 * Create a set object from the contents of an item in the $_FILES array
	 *
	 * @param Array $upload Element in the $_FILES array
	 * @return Set Channel set object
	 */
	public function importUpload(array $upload)
	{
		$location = $upload['tmp_name'];
		$name = $upload['name'];

		$extractor = new ZipToSet($location);

		$set = $extractor->extractAs($name);
		$set->setSiteId($this->site_id);

		return $set;
	}

	/**
	 * Create a set object from a directory
	 *
	 * @param String $dir Path to the channel set directory
	 * @return Set Channel set object
	 */
	public function importDir($dir)
	{
		$set = new Set($dir);
		$set->setSiteId($this->site_id);

		return $set;
	}

	/**
	 * Removes extracted channel set directories that have been sitting around
	 * for more than one day
	 */
	public function garbageCollect()
	{
		$path = PATH_CACHE.'cset/';

		if (ee('Filesystem')->exists($path))
		{
			foreach (ee('Filesystem')->getDirectoryContents($path) as $cset)
			{
				if (ee('Filesystem')->isDir($cset) &&
					ee('Filesystem')->mtime($cset) < time() - 86400)
				{
					ee('Filesystem')->deleteDir($cset);
				}
			}
		}
	}
}
