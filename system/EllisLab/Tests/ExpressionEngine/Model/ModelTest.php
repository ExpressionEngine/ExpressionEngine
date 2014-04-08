<?php
namespace EllisLab\Tests\ExpressionEngine\Model;

use Mockery as m;

class ModelTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->mb = m::mock('EllisLab\ExpressionEngine\Model\ModelBuilder');
		$this->as = m::mock('EllisLab\ExpressionEngine\Core\AliasService');
		$this->gateway = m::mock(__NAMESPACE__.'\\GatewayStub');
	}

	public function testConstructor()
	{
		$model = new TestModel($this->mb, $this->as);
	}

	public function testSaveNew()
	{
		$this->as->shouldReceive('getRegisteredClass')->with('GatewayStub')->andReturn(__NAMESPACE__.'\\GatewayStub');
		$this->mb->shouldReceive('makeGateway')->with('GatewayStub')->andReturn($this->gateway);

		$this->gateway->shouldReceive('setDirty')->with('title');

		$model = new TestModel($this->mb, $this->as);
		$model->title = 'The Template';
		$model->save();
		//new Model();
	}
}

class GatewayStub extends \EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway {

	protected static $meta = array(
		'table_name' => 'teams',
		'primary_key' => 'team_id'
	);

	public $the_id;
	public $another_id;
	public $title;
	public $description;
}

class TestModel extends \EllisLab\ExpressionEngine\Model\Model {

	protected static $_meta = array(
		'primary_key'	=> 'the_id',
	//	'cascade'		=> 'Templates',
		'gateway_names' => array('GatewayStub'),
		'key_map'		=> array(
			'the_id'		=> 'GatewayStub',
			'another_id'	=> 'GatewayStub'
		),
	);

	protected $the_id;
	protected $another_id;
	protected $title;
	protected $description;

}