<?php

namespace EllisLab\Tests\ExpressionEngine\Model\Integration;

use Mockery as m;
use ReflectionObject;

use EllisLab\Tests\PHPUnit\Extensions\Database\TestCase\ActiveRecordTestCase;

class GatewayDBTest extends ActiveRecordTestCase {

	public function setUp()
	{
		parent::setUp();

		$this->connection = $this->getConnection();
		$this->db = $this->getActiveRecord();
		$this->di = m::mock('EllisLab\ExpressionEngine\Core\Dependencies');
	}

	public function testSaveNew()
	{
		$this->assertEquals(2, $this->connection->getRowCount('teams'), "Pre-Condition");

		$gateway = new TestGateway($this->di);
		$gateway->setConnection($this->db);

		$gateway->name = 'Visible Ninjas';
		$gateway->founded = 2014;
		$gateway->setDirty('name');
		$gateway->setDirty('founded');
		$gateway->save();

		$this->assertEquals(3, $this->connection->getRowCount('teams'), "Inserting failed");
		$this->assertDataSetsEqual($this->getPostInsertDataSet(), $this->connection->createDataSet());
	}

	public function testSaveNewFromConstructor()
	{
		$this->assertEquals(2, $this->connection->getRowCount('teams'), "Pre-Condition");

		$gateway = new TestGateway($this->di, array(
			'name' => 'Visible Ninjas',
			'founded' => 2014
		));
		$gateway->setConnection($this->db);

		$gateway->setDirty('name');
		$gateway->setDirty('founded');
		$gateway->save();

		$this->assertEquals(3, $this->connection->getRowCount('teams'), "Inserting failed");
		$this->assertDataSetsEqual($this->getPostInsertDataSet(), $this->connection->createDataSet());
	}

	public function testUpdateExisting()
	{
		$this->assertEquals(2, $this->connection->getRowCount('teams'), "Pre-Condition");

		$gateway = new TestGateway($this->di);
		$gateway->setConnection($this->db);

		$gateway->team_id = 2;
		$gateway->name = 'Visible Ninjas';
		$gateway->setDirty('name');
		$gateway->save();

		$this->assertEquals(2, $this->connection->getRowCount('teams'), "Updating failed");
		$this->assertDataSetsEqual($this->getPostUpdateDataSet(), $this->connection->createDataSet());
	}

	public function testDelete()
	{
		$this->assertEquals(2, $this->connection->getRowCount('teams'), "Pre-Condition");

		$gateway = new TestGateway($this->di);
		$gateway->setConnection($this->db);

		$gateway->team_id = 2;
		$gateway->delete();

		$this->assertEquals(1, $this->connection->getRowCount('teams'), "Deleting failed");
		$this->assertDataSetsEqual($this->getPostDeleteDataSet(), $this->connection->createDataSet());
	}

	// END TESTS

	public function getTableDefinitions()
	{
		return TestGateway::tableSchema();
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
}


class TestGateway extends \EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway {

	protected static $meta = array(
		'table_name' => 'teams',
		'primary_key' => 'team_id'
	);

	public $team_id;
	public $founded;
	public $name;

	public static function tableSchema()
	{
		return "
			CREATE TABLE teams (
				team_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
				name VARCHAR(100) NOT NULL DEFAULT '',
				founded INT(10) NOT NULL DEFAULT 0
			);
		";
	}
}