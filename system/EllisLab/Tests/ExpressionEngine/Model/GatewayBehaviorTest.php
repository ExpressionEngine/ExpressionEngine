<?php
namespace EllisLab\Tests\ExpressionEngine\Model;

use Mockery as m;
use ReflectionObject;
use EllisLab\Tests\PHPUnit\Extensions\NoopDatabase\NoopQueryBuilder;

class GatewayBehaviorTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->di = m::mock('EllisLab\ExpressionEngine\Core\Dependencies');
		$this->gateway = m::mock('EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway', array($this->di, array()));

		$this->gateway->shouldDeferMissing();

		$this->setGatewayProperty('meta', array(
			'table_name' => 'dummy',
			'primary_key' => 'the_id'
		));
	}

	public function testGetMetadata()
	{
		$data = array(
			'table_name' => 'dummy',
			'primary_key' => 'the_id'
		);

		// conveniently also serves as a test for setGatewayProperty(),
		// so we know the rest of the tests in this class work are ok.
		$this->assertEquals($data, $this->gateway->getMetaData());
	}

	public function testSaveDoesNotHitDBWhenClean()
	{
		$database = $this->noopDatabase();

		$database->shouldReceive('insert')->never();
		$database->shouldReceive('update')->never();
		$database->shouldReceive('delete')->never();

		$this->gateway->save();
	}

	public function testSaveNewCallsInsert()
	{
		$database = $this->noopDatabase();

		$database->shouldReceive('update')->never();
		$database->shouldReceive('insert')->with('dummy', array('key' => 'test'))->once();

		$this->gateway->key = 'test';
		$this->gateway->setDirty('key');
		$this->gateway->save();
	}

	public function testSaveExistingCallsUpdateWhere()
	{
		$database = $this->noopDatabase();

		$database->shouldReceive('insert')->never();
		$database->shouldReceive('where')->with('the_id', 5)->once();
		$database->shouldReceive('update')->with('dummy', array('key' => 'test'))->once();

		$this->gateway->the_id = 5;
		$this->gateway->key = 'test';
		$this->gateway->setDirty('key');
		$this->gateway->save();
	}

	public function testDeleteWithoutIDThrowsException()
	{
		$this->markTestIncomplete('Specified Exception has no Implementation.');
	}

	// END TESTS

	protected function setGatewayProperty($name, $value)
	{
		$reflected = new ReflectionObject($this->gateway);

		$prop = $reflected->getProperty($name);
		$prop->setAccessible(TRUE);
		$prop->setValue($this->gateway, $value);
	}

	protected function noopDatabase()
	{
		$db = NoopQueryBuilder::getMock($this);
		$this->setGatewayProperty('db', $db);
		return $db;
	}

	public function tearDown()
	{
		$this->gateway = NULL;
	}
}