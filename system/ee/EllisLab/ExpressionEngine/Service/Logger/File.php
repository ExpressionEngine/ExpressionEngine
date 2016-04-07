<?php

namespace EllisLab\ExpressionEngine\Service\Logger;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

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
 * ExpressionEngine File Logger class
 *
 * @package		ExpressionEngine
 * @subpackage	Logger
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class File {

	protected $file_path = NULL;
	protected $filesystem = NULL;
	protected $stdout = FALSE;

	/**
	 * Constructor
	 *
	 * @param	string		$file_path	Path to log file
	 * @param	Filesystem	$filesystem	Filesystem library object
	 */
	public function __construct($file_path, Filesystem $filesystem, $stdout = FALSE)
	{
		$this->file_path = $file_path;
		$this->filesystem = $filesystem;
		$this->stdout = $stdout;

		$log_path = $this->filesystem->dirname($this->file_path);

		if ( ! $this->filesystem->exists($log_path) && $this->filesystem->isWritable($log_path))
		{
			$this->filesystem->mkdir($log_path);
		}

		if ( ! $this->filesystem->exists($log_path))
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
	 * Writes log message to file and optionally echos the message if $stdout
	 * class property is set to TRUE
	 *
	 * @param	string		$message	Message to log
	 */
	public function log($message)
	{
		$message .= "\n";

		$this->filesystem->write($this->file_path, $message, FALSE, TRUE);

		if ($this->stdout)
		{
			echo $message;
		}
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
