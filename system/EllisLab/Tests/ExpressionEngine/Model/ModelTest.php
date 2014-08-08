<?php
namespace EllisLab\Tests\ExpressionEngine\Model;

use Mockery as m;

class ModelTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->mb = m::mock('EllisLab\ExpressionEngine\Model\ModelFactory');
		$this->as = m::mock('EllisLab\ExpressionEngine\Core\AliasService');
		$this->gateway = m::mock(__NAMESPACE__.'\\GatewayStub');
		$this->rg = new \EllisLab\ExpressionEngine\Model\Relationship\RelationshipGraph($this->as);
	}

	public function testConstructor()
	{
		$model = new TestModel($this->mb, $this->as);
	}

	public function testPopulateFromDatabase()
	{
		// The data we're going to "recieve" from the database.
		$data = array(
			'the_id' => 1,
			'another_id' => 5,
			'title' => 'The Title',
			'description' => 'The description of this object.'
		);

		// Go ahead and put it on our mock gateway ahead of time.
		foreach ($data as $property => $value)
		{
			$this->gateway->{$property} = $value;
		}

		$this->as->shouldReceive('getRegisteredClass')->with('GatewayStub')->andReturn(__NAMESPACE__.'\\GatewayStub');
		$this->mb->shouldReceive('makeGateway')->with('GatewayStub', $data)->andReturn($this->gateway);
		$model = new TestModel($this->mb, $this->as);
		$model->populateFromDatabase($data);

		foreach($data as $property => $value)
		{
			$this->assertEquals($data[$property], $model->{$property});
		}

	}

	public function testSaveNew()
	{
		$this->as->shouldReceive('getRegisteredClass')->with('GatewayStub')->andReturn(__NAMESPACE__.'\\GatewayStub');
		$this->mb->shouldReceive('makeGateway')->with('GatewayStub')->andReturn($this->gateway);
		$this->mb->shouldReceive('getRelationshipGraph')->andReturn($this->rg);

		$this->gateway->shouldReceive('setDirty')->with('title');
		$this->gateway->shouldReceive('save');

		$model = new TestModel($this->mb, $this->as);
		$model->title = 'The Template';
		$model->save();
		//new Model();
	}
}

class GatewayStub extends \EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway {

	protected static $_table_name = 'the_table';
	protected static $_primary_key = 'the_id';

	protected $the_id;
	protected $another_id;
	protected $title;
	protected $description;
}

class TestModel extends \EllisLab\ExpressionEngine\Model\Model {

	protected static $_primary_key	= 'the_id';
	protected static $_gateway_names = array('GatewayStub');

	protected $the_id;
	protected $another_id;
	protected $title;
	protected $description;

}
