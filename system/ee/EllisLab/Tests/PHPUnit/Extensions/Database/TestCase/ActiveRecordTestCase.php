<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\PHPUnit\Extensions\Database\TestCase;

use EllisLab\Tests\PHPUnit\Extensions\Database\DataSet\ArrayDataSet;


abstract class ActiveRecordTestCase extends \PHPUnit_Extensions_Database_TestCase {

	private $pdo;
	private $active_record;

	public function __construct($name = NULL, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->active_record = $this->getCIDBConnection();
		$this->pdo = $this->active_record->conn_id;

		$queries = (array) $this->getTableDefinitions();

		array_map(array($this->pdo, 'query'), $queries);
	}

	/**
	 * Can be implemented to create tables for this test.
	 */
	public function getTableDefinitions()
	{
		return array();
	}

	/**
	 * Helper to avoid typing that long ArrayDataSet namespace all the time
	 * and to get parity with DBUnit's create***DataSet functions.
	 */
	public function createArrayDataSet(array $data)
	{
		return new ArrayDataSet($data);
	}

	/**
	 * Get the CI active record object
	 */
	public function getActiveRecord()
	{
		return $this->active_record;
	}

	/**
	 * PHPUnit Boilerplate
	 */
	public function getConnection()
	{
		return $this->createDefaultDBConnection($this->pdo, 'sqlite');
	}

	/**
	 * Helper function to set up all of the CI db dependencies
	 */
	public function getCIDBConnection()
	{
		require_once BASEPATH.'database/DB_driver.php';
		require_once BASEPATH.'database/DB_active_rec.php';

		if ( ! class_exists('CI_DB'))
		{
			class_alias('CI_DB_active_record', 'CI_DB');
		}

		require_once BASEPATH.'database/drivers/pdo/pdo_driver.php';
		require_once BASEPATH.'database/drivers/pdo/subdrivers/pdo_sqlite_driver.php';

		$db = new \CI_DB_pdo_sqlite_driver(array(
			'dsn' => 'sqlite::memory:'
		));

		$db->initialize();
		return $db;
	}
}
