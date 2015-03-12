<?php

namespace EllisLab\Test\ExpressionEngine\Service\Model\Association;

use Mockery as m;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Service\Model\Association\BelongsTo;

class BelongsToTest extends \PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testStartsUnloaded()
	{
		$parent = $this->newModelMock();

		$belongs = new BelongsTo($parent);
		$this->assertFalse($belongs->isLoaded());
	}

	public function testMarkAsLoaded()
	{
		$parent = $this->newModelMock();

		$belongs = new BelongsTo($parent);
		$this->assertFalse($belongs->isLoaded());

		$belongs->markAsLoaded();
		$this->assertTrue($belongs->isLoaded());
	}

	public function testReloadInitiatesLazyQuery()
	{
		$builder = m::mock('EllisLab\ExpressionEngine\Service\Model\Query\Builder');
		$relation = m::mock('EllisLab\ExpressionEngine\Service\Model\Relation\BelongsTo');
		$frontend = m::mock('EllisLab\ExpressionEngine\Service\Model\Frontend');

		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$relation->shouldReceive('getTargetModel')->once()->andReturn('ModelStub');
		$frontend->shouldReceive('get')->once()->with('ModelStub')->andReturn($builder);
		$builder->shouldReceive('setLazyConstraint')->once()->with($relation, $parent)->andReturn($builder);
		$builder->shouldReceive('all')->once()->andReturn(array($child));

		$relation->shouldReceive('linkIds')->with($parent, $child);
		$relation->shouldReceive('linkIds')->with($parent, $child);

		// todo this is incorrect, but the correct behavior is that we
		$relation->shouldReceive('getInverse')->once()->andReturn(new \StdClass);

		$belongs = new BelongsTo($parent);
		$belongs->setRelation($relation);
		$belongs->setFrontend($frontend);

		$this->assertFalse($belongs->isLoaded());

		$belongs->get();

		$this->assertTrue($belongs->isLoaded());

		$loaded = $belongs->get();
		$this->assertSame($child, $loaded);

	}

	public function testCreateFromExisting()
	{
		$relation = m::mock('EllisLab\ExpressionEngine\Service\Model\Relations\BelongsTo');

		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$relation->shouldReceive('linkIds')->once()->with($parent, $child);

		$belongs = new BelongsTo($parent);
		$belongs->markAsLoaded();
		$belongs->setRelation($relation);

		// pre-conditions
		$this->assertEmpty($belongs->get());

		$belongs->set($child);
		$retrieved = $belongs->get();

		// post-conditions
		$this->assertTrue($retrieved instanceOf Model);
		$this->assertSame($child, $retrieved);
	}

	public function testRemoveUnknown()
	{
		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$belongs = new BelongsTo($parent);
		$belongs->markAsLoaded();

		// pre-conditions
		$this->assertEmpty($belongs->get());

		$belongs->remove($child);

		// post-conditions
		$this->assertEmpty($belongs->get());
	}

	public function testRemoveExisting()
	{
		$relation = m::mock('EllisLab\ExpressionEngine\Service\Model\Relations\BelongsTo');

		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$relation->shouldReceive('unlinkIds')->once()->with($parent, $child);

		$belongs = new BelongsTo($parent);
		$belongs->setRelation($relation);
		$belongs->fill(array($child));

		// pre-conditions
		$this->assertTrue($belongs->isLoaded());

		$retrieved = $belongs->get();

		$this->assertTrue($retrieved instanceOf Model);
		$this->assertSame($child, $retrieved);

		$belongs->remove($child);

		// post-conditions
		$this->assertEmpty($belongs->get());
	}

	/**
	 * @expectedException LogicException
	 * @expectedExceptionMessage Cannot create(), did you mean set()?
	 */
	public function testCannotCreate()
	{
		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$belongs = new BelongsTo($parent);
		$belongs->create($child);
	}

	/**
	 * @expectedException LogicException
	 * @expectedExceptionMessage Cannot delete(), did you mean remove()?
	 */
	public function testCannotDelete()
	{
		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$belongs = new BelongsTo($parent);
		$belongs->delete($child);
	}

	protected function newModelMock()
	{
		return m::mock('EllisLab\ExpressionEngine\Service\Model\Model');
	}
}