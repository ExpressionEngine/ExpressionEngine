<?php

namespace EllisLab\Tests\ExpressionEngine\Model\Integration;

use Mockery as m;
use ReflectionObject;
use EllisLab\Tests\PHPUnit\Extensions\Database\DataSet\ArrayDataSet;


class GatewayDBTest extends \PHPUnit_Extensions_Database_TestCase {

	protected $pdo;

	public function __construct($name = NULL, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->database = $this->getCIDBConnection();
		$this->pdo = $this->database->conn_id;

		$query = "
			CREATE TABLE teams (
				team_id INT(17) PRIMARY KEY,
				name VARCHAR(100) NOT NULL DEFAULT '',
				founded INT(10) NOT NULL DEFAULT 0
			);
		";

		$this->pdo->query($query);
	}

	public function setUp()
	{
		parent::setUp();

		$this->di = m::mock('EllisLab\ExpressionEngine\Core\Dependencies');
		$this->gateway = m::mock('EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway', array($this->di, array()));

		$this->setGatewayProperty('db', $this->database);
		$this->setGatewayProperty('meta', array(
			'table_name' => 'teams',
			'primary_key' => 'team_id'
		));
	}

	public function testSaveNew()
	{
		$this->assertEquals(2, $this->getConnection()->getRowCount('teams'), "Pre-Condition");

		$this->gateway->shouldDeferMissing();

		$this->gateway->name = 'Visible Ninjas';
		$this->gateway->setDirty('name');
		$this->gateway->save();

		$this->assertEquals(3, $this->getConnection()->getRowCount('teams'), "Inserting failed");
	}

	public function testUpdateExisting()
	{
		$this->assertEquals(2, $this->getConnection()->getRowCount('teams'), "Pre-Condition");

		$this->gateway->shouldDeferMissing();

		$this->gateway->team_id = 2;
		$this->gateway->name = 'Visible Ninjas';
		$this->gateway->setDirty('name');
		$this->gateway->save();

		$this->assertEquals(2, $this->getConnection()->getRowCount('teams'), "Inserting failed");
	}

	public function setGatewayProperty($name, $value)
	{
		$reflected = new \ReflectionObject($this->gateway);

		$prop = $reflected->getProperty($name);
		$prop->setAccessible(TRUE);
		$prop->setValue($this->gateway, $value);
	}

	public function tearDown()
	{
		parent::tearDown();
		$this->gateway = NULL;
	}

	public function getCIDBConnection()
	{
		require_once BASEPATH.'database/DB_Driver.php';
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

	public function getConnection()
	{
		return $this->createDefaultDBConnection($this->pdo, 'sqlite');
	}

	public function getDataSet()
	{
		return new ArrayDataSet(array(
			'teams' => array(
				array('team_id' => 1, 'name' => 'Nearsighted Astronomers', 'founded' => 1608),
				array('team_id' => 2, 'name' => 'Farsighted Typesetters', 'founded' => 1450),
			)
		));
	}
}