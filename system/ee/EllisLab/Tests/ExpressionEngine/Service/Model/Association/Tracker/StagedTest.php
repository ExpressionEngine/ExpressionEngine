<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Model\Association\Tracker;

use Mockery as m;
use EllisLab\ExpressionEngine\Service\Model\Association\Tracker\Staged;

class StagedTest extends \PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testAdd()
	{
		$model1 = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');
		$model2 = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');

		$tracker = new Staged();

		$tracker->add($model1);
		$tracker->add($model2);

		$this->assertEquals(array($model1, $model2), $tracker->getAdded());
	}

	public function testRemove()
	{
		$model1 = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');
		$model2 = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');

		$tracker = new Staged();

		$tracker->remove($model1);
		$tracker->remove($model2);

		$this->assertEquals(array($model1, $model2), $tracker->getRemoved());
	}

	public function testReset()
	{
		$model1 = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');
		$model2 = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');

		$tracker = new Staged();

		$tracker->add($model1);
		$tracker->remove($model2);

		$this->assertEquals(array($model1), $tracker->getAdded());
		$this->assertEquals(array($model2), $tracker->getRemoved());

		$tracker->reset();

		$this->assertEquals(array(), $tracker->getAdded());
		$this->assertEquals(array(), $tracker->getRemoved());
	}

	public function testFastUndoDoesNotStage()
	{
		$model1 = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');
		$model2 = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');

		$tracker = new Staged();

		$tracker->add($model1);
		$tracker->remove($model2);

		$this->assertEquals(array($model1), $tracker->getAdded());
		$this->assertEquals(array($model2), $tracker->getRemoved());

		$tracker->add($model2);
		$tracker->remove($model1);

		$this->assertEquals(array(), $tracker->getAdded());
		$this->assertEquals(array(), $tracker->getRemoved());
	}

}