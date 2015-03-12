<?php

namespace EllisLab\Test\ExpressionEngine\Service\Model\Association;

use Mockery as m;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Service\Model\Association\HasMany;

class HasManyTest extends \PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testStartsUnloaded()
	{
		$parent = $this->newModelMock();

		$has_many = new HasMany($parent);
		$this->assertFalse($has_many->isLoaded());
	}

	public function testMarkAsLoaded()
	{
		$parent = $this->newModelMock();

		$has_many = new HasMany($parent);
		$this->assertFalse($has_many->isLoaded());

		$has_many->markAsLoaded();
		$this->assertTrue($has_many->isLoaded());
	}

	public function testReloadInitiatesLazyQuery()
	{
		$builder = m::mock('EllisLab\ExpressionEngine\Service\Model\Query\Builder');
		$relation = m::mock('EllisLab\ExpressionEngine\Service\Model\Relation\HasMany');
		$frontend = m::mock('EllisLab\ExpressionEngine\Service\Model\Frontend');

		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$relation->shouldReceive('getTargetModel')->once()->andReturn('ModelStub');
		$frontend->shouldReceive('get')->with('ModelStub')->once()->andReturn($builder);
		$builder->shouldReceive('setLazyConstraint')->once()->with($relation, $parent)->andReturn($builder);
		$builder->shouldReceive('all')->andReturn(array($child));

		// todo this is incorrect, but the correct behavior is that we
		$relation->shouldReceive('getInverse')->once()->andReturn(new \StdClass);

		$has_many = new HasMany($parent);
		$has_many->setRelation($relation);
		$has_many->setFrontend($frontend);

		$this->assertFalse($has_many->isLoaded());

		$has_many->get();

		$this->assertTrue($has_many->isLoaded());

		$loaded = $has_many->get();
		$this->assertSame($child, $loaded[0]);

	}

	public function testCreateFromExisting()
	{
		$relation = m::mock('EllisLab\ExpressionEngine\Service\Model\Relations\HasMany');

		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$child->shouldReceive('save')->once();
		$relation->shouldReceive('linkIds')->with($parent, $child);

		$has_many = new HasMany($parent);
		$has_many->markAsLoaded();
		$has_many->setRelation($relation);

		// pre-conditions
		$this->assertEmpty($has_many->get());

		$has_many->create($child);
		$retrieved = $has_many->get();

		// post-conditions
		$this->assertTrue($retrieved instanceOf Collection);
		$this->assertTrue(count($retrieved) == 1);
		$this->assertSame($child, $retrieved[0]);
	}

	public function testCreateFromArray()
	{
		$relation = m::mock('EllisLab\ExpressionEngine\Service\Model\Relations\HasMany');
		$frontend = m::mock('EllisLab\ExpressionEngine\Service\Model\Frontend');

		$child_type = 'AwesomeChild';
		$child_data = array(
			'name' => 'Dexter',
			'age' => 30
		);

		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$child->shouldReceive('save')->once();
		$frontend->shouldReceive('make')->once()->with($child_type, $child_data)->andReturn($child);
		$relation->shouldReceive('linkIds')->once()->with($parent, $child);

		$has_many = new HasMany($parent, $child_type);

		$has_many->markAsLoaded();
		$has_many->setFrontend($frontend);
		$has_many->setRelation($relation);

		// pre-conditions
		$this->assertEmpty($has_many->get());

		$has_many->create($child_data);
		$retrieved = $has_many->get();

		// post-conditions
		$this->assertTrue($retrieved instanceOf Collection);
		$this->assertTrue(count($retrieved) == 1);
		$this->assertSame($child, $retrieved[0]);
	}

	public function testDeleteUnknown()
	{
		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$has_many = new HasMany($parent);
		$has_many->markAsLoaded();

		// pre-conditions
		$this->assertEmpty($has_many->get());

		$has_many->delete($child);

		// post-conditions
		$this->assertEmpty($has_many->get());
	}

	public function testDeleteExisting()
	{
		$relation = m::mock('EllisLab\ExpressionEngine\Service\Model\Relations\HasMany');

		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$child->shouldReceive('delete')->once();
		$relation->shouldReceive('unlinkIds')->once()->with($parent, $child);

		$has_many = new HasMany($parent);
		$has_many->markAsLoaded();
		$has_many->fill(array($child));
		$has_many->setRelation($relation);

		// pre-conditions
		$retrieved = $has_many->get();

		$this->assertTrue($retrieved instanceOf Collection);
		$this->assertTrue(count($retrieved) == 1);
		$this->assertSame($child, $retrieved[0]);

		$has_many->delete($child);

		// post-conditions
		$this->assertEmpty($has_many->get());
	}

	/**
	 * @expectedException LogicException
	 * @expectedExceptionMessage Cannot set(), did you mean create()?
	 */
	public function testCannotSet()
	{
		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$has_many = new HasMany($parent);
		$has_many->set($child);
	}

	/**
	 * @expectedException LogicException
	 * @expectedExceptionMessage Cannot add(), did you mean create()?
	 */
	public function testCannotAdd()
	{
		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$has_many = new HasMany($parent);
		$has_many->add($child);
	}

	/**
	 * @expectedException LogicException
	 * @expectedExceptionMessage Cannot remove(), did you mean delete()?
	 */
	public function testCannotRemove()
	{
		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$has_many = new HasMany($parent);
		$has_many->remove($child);
	}

	protected function newModelMock()
	{
		return m::mock('EllisLab\ExpressionEngine\Service\Model\Model');
	}
}