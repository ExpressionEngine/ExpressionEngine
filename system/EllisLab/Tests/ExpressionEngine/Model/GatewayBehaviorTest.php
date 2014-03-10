<?php
namespace EllisLab\Tests\ExpressionEngine\Model;

use Mockery as m;
use ReflectionObject;
use EllisLab\Tests\PHPUnit\Extensions\NoopDatabase\NoopQueryBuilder;

class GatewayBehaviorTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->di = m::mock('EllisLab\ExpressionEngine\Core\Dependencies');
	}

	public function testGetMetadata()
	{
		$data = array(
			'table_name' => 'dummy',
			'primary_key' => 'the_id'
		);

		$this->assertEquals($data, TestGateway::getMetaData());
	}

	public function testSaveDoesNotHitDBWhenClean()
	{
		$database = $this->noopDatabase();

		$database->shouldReceive('insert')->never();
		$database->shouldReceive('update')->never();
		$database->shouldReceive('delete')->never();

		$gateway = new TestGateway($this->di);
		$gateway->setConnection($database);

		$gateway->save();
	}

	public function testSaveNewCallsInsert()
	{
		$database = $this->noopDatabase();

		$database->shouldReceive('update')->never();
		$database->shouldReceive('insert')->with('dummy', array('key' => 'test'))->once();

		$gateway = new TestGateway($this->di);
		$gateway->setConnection($database);

		$gateway->key = 'test';
		$gateway->setDirty('key');
		$gateway->save();
	}

	public function testSaveExistingCallsUpdateWhere()
	{
		$database = $this->noopDatabase();

		$database->shouldReceive('insert')->never();
		$database->shouldReceive('where')->with('the_id', 5)->once();
		$database->shouldReceive('update')->with('dummy', array('key' => 'test'))->once();

		$gateway = new TestGateway($this->di);
		$gateway->setConnection($database);

		$gateway->the_id = 5;
		$gateway->key = 'test';
		$gateway->setDirty('key');
		$gateway->save();
	}

	public function testConstructorChecksPropertyExists()
	{
		$gateway = new TestGateway($this->di, array(
			'key'	 => 'exists',
			'random' => 'does not'
		));

		$this->assertObjectHasAttribute('key', $gateway);
		$this->assertEquals('exists', $gateway->key);
		$this->assertFalse(isset($gateway->random));
	}

	public function testDeleteWithoutIDThrowsException()
	{
		$this->markTestIncomplete('Specified Exception has no Implementation.');
	}

	// END TESTS

	protected function noopDatabase()
	{
		return NoopQueryBuilder::getMock($this);
	}
}


class TestGateway extends \EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway {

	protected static $meta = array(
		'table_name' => 'dummy',
		'primary_key' => 'the_id'
	);

	public $key;
}