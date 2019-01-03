<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Logger;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * Generic class to append line items to a file
 */
class File {

	protected $file_path = NULL;
	protected $filesystem = NULL;

	/**
	 * Constructor
	 *
	 * @param	string		$file_path	Path to log file
	 * @param	Filesystem	$filesystem	Filesystem library object
	 */
	public function __construct($file_path, Filesystem $filesystem)
	{
		$this->file_path = $file_path;
		$this->filesystem = $filesystem;

		$log_path = $this->filesystem->dirname($this->file_path);

		if ( ! $this->filesystem->exists($log_path) && ! $this->filesystem->mkdir($log_path))
		{
			throw new \Exception('Log file path does not exist: ' . $log_path, 1);
		}
		if ( ! $this->filesystem->isWritable($log_path))
		{
			throw new \Exception('Log file path not writable: ' . $log_path, 2);
		}
		if ($this->filesystem->exists($this->file_path) && ! $this->filesystem->isWritable($this->file_path))
		{
			throw new \Exception('Log file not writable: ' . $this->file_path, 3);
		}
	}

	/**
	 * Writes log message to file
	 *
	 * @param	string		$message	Message to log
	 */
	public function log($message)
	{
		$message .= "\n";

		$this->filesystem->append($this->file_path, $message);
	}

	/**
	 * Clears out the log file
	 */
	public function truncate()
	{
		$this->filesystem->write($this->file_path, '', TRUE);
	}

	/**
	 * Deletes log file
	 */
	public function delete()
	{
		if ( ! $this->filesystem->exists($this->file_path))
		{
			$this->filesystem->delete($this->file_path);
		}
	}
}

// EOF
