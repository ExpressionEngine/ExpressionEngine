<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Database\Backup;

use EllisLab\ExpressionEngine\Service\Database;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * Runs an SQL file against the current database
 */
class Restore {

	/**
	 * @var Database\Query Database Query object
	 */
	protected $query;

	/**
	 * @var Filesystem library object
	 */
	protected $filesystem;

	/**
	 * Constructor
	 *
	 * @param	Database\Query	$query		Database query object
	 * @param	Filesystem		$filesystem	Filesytem library object
	 */
	public function __construct(Database\Query $query, Filesystem $filesystem)
	{
		$this->query = $query;
		$this->filesystem = $filesystem;
	}

	/**
	 * Reads an entire file into memory and passes the entire contents to MySQL
	 *
	 * @param	string	$file_path	Server path to SQL file
	 */
	public function restore($file_path)
	{
		$this->query->query(
			$this->filesystem->read($file_path)
		);
	}

	/**
	 * Reads a file line-by-line and runs one query at a time, helpful for large
	 * SQL files; each line must be a full, valid query
	 *
	 * @param	string	$file_path	Server path to SQL file
	 */
	public function restoreLineByLine($file_path)
	{
		$this->filesystem->readLineByLine($file_path, function($line)
		{
			$query = trim($line);

			if ( ! empty($query))
			{
				$this->query->query(trim($line));
			}
		});
	}
}

// EOF
