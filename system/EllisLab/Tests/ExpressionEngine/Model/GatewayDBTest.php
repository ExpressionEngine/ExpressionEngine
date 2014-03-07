<?php

namespace EllisLab\Tests\ExpressionEngine\Model\Integration;

use Mockery as m;
use ReflectionObject;
use EllisLab\Tests\PHPUnit\Extensions\Database\TestCase\ActiveRecordTestCase;

class GatewayDBTest extends ActiveRecordTestCase {

	public function setUp()
	{
		parent::setUp();

		$this->di = m::mock('EllisLab\ExpressionEngine\Core\Dependencies');
		$this->gateway = m::mock('EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway', array($this->di, array()));

		$this->setGatewayProperty('db', $this->getActiveRecord());
		$this->setGatewayProperty('meta', array(
			'table_name' => 'teams',
			'primary_key' => 'team_id'
		));

		$this->connection = $this->getConnection();
	}

	public function testSaveNew()
	{
		$this->assertEquals(2, $this->connection->getRowCount('teams'), "Pre-Condition");

		$this->gateway->shouldDeferMissing();

		$this->gateway->name = 'Visible Ninjas';
		$this->gateway->founded = 2014;
		$this->gateway->setDirty('name');
		$this->gateway->setDirty('founded');
		$this->gateway->save();

		$this->assertEquals(3, $this->connection->getRowCount('teams'), "Inserting failed");
		$this->assertDataSetsEqual($this->getPostInsertDataSet(), $this->connection->createDataSet());
	}

	public function testUpdateExisting()
	{
		$this->assertEquals(2, $this->connection->getRowCount('teams'), "Pre-Condition");

		$this->gateway->shouldDeferMissing();

		$this->gateway->team_id = 2;
		$this->gateway->name = 'Visible Ninjas';
		$this->gateway->setDirty('name');
		$this->gateway->save();

		$this->assertEquals(2, $this->connection->getRowCount('teams'), "Updating failed");
		$this->assertDataSetsEqual($this->getPostUpdateDataSet(), $this->connection->createDataSet());
	}

	public function testDelete()
	{
		$this->assertEquals(2, $this->connection->getRowCount('teams'), "Pre-Condition");

		$this->gateway->shouldDeferMissing();

		$this->gateway->team_id = 2;
		$this->gateway->delete();

		$this->assertEquals(1, $this->connection->getRowCount('teams'), "Deleting failed");
		$this->assertDataSetsEqual($this->getPostDeleteDataSet(), $this->connection->createDataSet());
	}

	// END TESTS

	public function getTableDefinitions()
	{
		return "
			CREATE TABLE teams (
				team_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
				name VARCHAR(100) NOT NULL DEFAULT '',
				founded INT(10) NOT NULL DEFAULT 0
			);
		";
	}

	public function getDataSet()
	{
		return $this->createArrayDataSet(array(
			'teams' => array(
				array('team_id' => 1, 'name' => 'Nearsighted Astronomers', 'founded' => 1608),
				array('team_id' => 2, 'name' => 'Farsighted Typesetters', 'founded' => 1450),
			)
		));
	}

	public function getPostDeleteDataSet()
	{
		return $this->createArrayDataSet(array(
			'teams' => array(
				array('team_id' => 1, 'name' => 'Nearsighted Astronomers', 'founded' => 1608)
			)
		));
	}

	public function getPostUpdateDataSet()
	{
		return $this->createArrayDataSet(array(
			'teams' => array(
				array('team_id' => 1, 'name' => 'Nearsighted Astronomers', 'founded' => 1608),
				array('team_id' => 2, 'name' => 'Visible Ninjas', 'founded' => 1450),
			)
		));
	}

	public function getPostInsertDataSet()
	{
		return $this->createArrayDataSet(array(
			'teams' => array(
				array('team_id' => 1, 'name' => 'Nearsighted Astronomers', 'founded' => 1608),
				array('team_id' => 2, 'name' => 'Farsighted Typesetters', 'founded' => 1450),
				array('team_id' => 3, 'name' => 'Visible Ninjas', 'founded' => 2014),
			)
		));
	}

	public function setGatewayProperty($name, $value)
	{
		$reflected = new \ReflectionObject($this->gateway);

		$prop = $reflected->getProperty($name);
		$prop->setAccessible(TRUE);
		$prop->setValue($this->gateway, $value);
	}
}