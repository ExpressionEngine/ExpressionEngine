<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Model\Association\Tracker;

use Mockery as m;
use EllisLab\ExpressionEngine\Service\Model\Association\Tracker\Immediate;

class ImmediateTest extends \PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testAddSaves()
	{
		$model = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');
		$model->shouldReceive('save')->once();

		$tracker = new Immediate();

		$tracker->add($model);
	}

	public function testRemoveDeletes()
	{
		$model = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');
		$model->shouldReceive('delete')->once();

		$tracker = new Immediate();

		$tracker->remove($model);
	}

	public function testAddedAlwaysBlank()
	{
		$model = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');
		$model->shouldReceive('save')->once();

		$tracker = new Immediate();

		$this->assertEquals(array(), $tracker->getAdded());

		$tracker->add($model);

		$this->assertEquals(array(), $tracker->getAdded());
	}

	public function testRemovedAlwaysBlank()
	{
		$model = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');
		$model->shouldReceive('save')->once();
		$model->shouldReceive('delete')->once();

		$tracker = new Immediate();

		$this->assertEquals(array(), $tracker->getRemoved());

		$tracker->add($model);

		$this->assertEquals(array(), $tracker->getRemoved());

		$tracker->remove($model);

		$this->assertEquals(array(), $tracker->getRemoved());
	}
}