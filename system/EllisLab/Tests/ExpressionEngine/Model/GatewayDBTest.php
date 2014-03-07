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

		$this->assertEquals(2, $this->getConnection()->getRowCount('teams'), "Updating failed");
	}

	public function testDelete()
	{
		$this->assertEquals(2, $this->getConnection()->getRowCount('teams'), "Pre-Condition");

		$this->gateway->shouldDeferMissing();

		$this->gateway->team_id = 2;
		$this->gateway->delete();

		$this->assertEquals(1, $this->getConnection()->getRowCount('teams'), "Deleting failed");
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

	public function getTableDefinitions()
	{
		$query = "
			CREATE TABLE teams (
				team_id INT(17) PRIMARY KEY,
				name VARCHAR(100) NOT NULL DEFAULT '',
				founded INT(10) NOT NULL DEFAULT 0
			);
		";

		return $query;
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
}