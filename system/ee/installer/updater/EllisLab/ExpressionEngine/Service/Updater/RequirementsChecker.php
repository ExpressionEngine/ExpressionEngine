<?php

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
 * ExpressionEngine Updater requirements checker class
 *
 * @package		ExpressionEngine
 * @subpackage	Updater
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class RequirementsChecker
{
	private $requirements = [];
	private $minimum_php = '5.4.0';
	private $minimum_mysql = '5.0.3';
	private $db_config = array();

	/**
	 * Constructor
	 *
	 * @param	string	$db_config	Array of DB config info
	 */
	public function __construct($db_config)
	{
		$this->db_config = $db_config;
		$this->setupRequirements();
	}

	/**
	 * Sets the requirements test for ExpressionEngine
	 */
	private function setupRequirements()
	{
		// PHP version
		$this->requirements[] = new Requirement(
			'Your PHP version ('.phpversion().') does not meet the minimum requirement of '.$this->minimum_php.'.',
			version_compare(phpversion(), $this->minimum_php, '>=')
		);

		// MySQL version and PDO
		$mysql = new Requirement('Your MySQL version does not meet the minimum requirement of '.$this->minimum_mysql.'.');
		$this->requirements[] = $mysql->setCallback(function($requirement)
		{
			if ( ! class_exists('PDO'))
			{
				$requirement->setMessage('Your PHP installation does not have <a href="http://php.net/manual/en/book.pdo.php">PDO</a> enabled.');
				return FALSE;
			}

			$hostname = $this->db_config['hostname'];
			$username = $this->db_config['username'];
			$password = $this->db_config['password'];
			$database = $this->db_config['database'];
			$char_set = $this->db_config['char_set'];
			$pconnect = $this->db_config['pconnect'];
			$port     = $this->db_config['port'];

			$dsn = "mysql:dbname={$database};host={$hostname};port={$port};charset={$char_set}";

			$options = array(
				PDO::ATTR_PERSISTENT => $pconnect,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_CASE => PDO::CASE_NATURAL,
				PDO::ATTR_STRINGIFY_FETCHES => FALSE
			);

			$pdo = new PDO(
				$dsn,
				$username,
				$password,
				$options
			);

			if ( ! $pdo)
			{
				throw new Exception('Could not connect to the database using the credentials provided.', 12);
			}

			return (version_compare($pdo->getAttribute(PDO::ATTR_SERVER_VERSION), $this->minimum_mysql, '>=') === TRUE);
		});

		// Memory limit
		$memory_limit = new Requirement('ExpressionEngine requires at least 32MB of memory allocated to PHP.');
		$this->requirements[] = $memory_limit->setCallback(function()
		{
			$memory_limit = @ini_get('memory_limit');
			sscanf($memory_limit, "%d%s", $limit, $unit);

			if (strtolower($unit) == 'm')
			{
				return ($limit >= 32);
			}

			return TRUE;
		});

		// JSON extension
		$this->requirements[] = new Requirement(
			'Your PHP installation does not have the JSON extension enabled.',
			function_exists('json_encode') && function_exists('json_decode')
		);

		// FileInfo extension
		$this->requirements[] = new Requirement(
			'Your PHP installation does not have the FileInfo extension enabled.',
			function_exists('finfo_open')
		);

		// cURL extension
		$this->requirements[] = new Requirement(
			'Your PHP installation does not have the cURL extension enabled.',
			function_exists('curl_version')
		);

		// OpenSSL extension
		$this->requirements[] = new Requirement(
			'Your PHP installation does not have the OpenSSL extension enabled.',
			function_exists('openssl_verify')
		);
	}

	/**
	 * Gathers all the requirements test results and reports TRUE if good,
	 * or returns an array of the failed Requirement objects
	 *
	 * @return	mixed	TRUE if good, or array of failed Requirement objects
	 */
	public function check()
	{
		$failed = [];
		foreach ($this->requirements as $requirement)
		{
			if ( ! $requirement->getResult())
			{
				$failed[] = $requirement;
			}
		}

		return empty($failed) ? TRUE : $failed;
	}
}

class Requirement
{
	private $message;
	private $result;

	/**
	 * Constructor
	 *
	 * @param	string	$message	Message to display if this requirement fails
	 * @return	boolean	$result		Success or failure indicator of requirement test
	 */
	public function __construct($message, $result = FALSE)
	{
		$this->message = $message;
		$this->result = $result;
	}

	/**
	 * Specify a callback to use as the test for this requirement
	 *
	 * @param	Callable	$callback	Closure to use to test this requirement, receives
	 *   the parent Requirement object as an argument and must return a boolean
	 * @return	Requirement	The current Requirement object
	 */
	public function setCallback(Callable $callback)
	{
		$this->result = $callback($this);
		return $this;
	}

	/**
	 * Set a different failure message other than the one set in the constructor,
	 * handy for conditionally setting messages inside a test callback
	 *
	 * @param	string	$message	Message to display if this requirement fails
	 */
	public function setMessage($message)
	{
		$this->message = $message;
	}

	/**
	 * Gets the failure message
	 *
	 * @return	string	Message to display if this requirement fails
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * Gets the result of the requirement test
	 *
	 * @return	boolean	Success or failure indicator of requirement test
	 */
	public function getResult()
	{
		return $this->result;
	}
}

// EOF
