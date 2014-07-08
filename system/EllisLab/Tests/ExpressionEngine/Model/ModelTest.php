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

	protected static $_table_name = 'teams';
	protected static $_primary_key = 'team_id';

	public $the_id;
	public $another_id;
	public $title;
	public $description;
}

class TestModel extends \EllisLab\ExpressionEngine\Model\Model {

	protected static $_primary_key	= 'the_id';
	protected static $_gateway_names = array('GatewayStub');

	protected $the_id;
	protected $another_id;
	protected $title;
	protected $description;

}
