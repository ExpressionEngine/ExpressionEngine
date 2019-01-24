<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Service\Model;

use Mockery as m;
use EllisLab\ExpressionEngine\Service\Model\Association;
use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Collection;
use PHPUnit\Framework\TestCase;

class OneToManyModelTest extends TestCase {

	public function setUp()
	{
		$this->parentClass = __NAMESPACE__.'\OneToManyParent';
		$this->childClass = __NAMESPACE__.'\OneToManyChild';

		$this->has_many_relation = m::mock('EllisLab\ExpressionEngine\Service\Model\Relation\HasMany');
		$this->belongs_to_relation = m::mock('EllisLab\ExpressionEngine\Service\Model\Relation\BelongsTo');
	}

	public function tearDown()
	{
		$this->has_many_relation = NULL;
		$this->belongs_to_relation = NULL;
	}

	public function testFillParentWithChildren()
	{
		$parent = new $this->parentClass;
		$child1 = new $this->childClass;
		$child2 = new $this->childClass;

		$collection = new Collection(array($child1, $child2));

		$parent->setId(5);

		$assoc = $this->addAssociation(
			$parent,
			array($this->has_many_relation, 'FillChild'),
			array($this->belongs_to_relation, 'FillParent')
		);

		$this->addAssociation(
			$child1,
			array($this->belongs_to_relation, 'FillParent')
		);

		$this->addAssociation(
			$child2,
			array($this->belongs_to_relation, 'FillParent')
		);

		$this->assertEquals(0, count($parent->FillChild));
		$this->assertNull($child1->FillParent);
		$this->assertNull($child2->FillParent);
		$this->assertNull($child1->parent_id);
		$this->assertNull($child2->parent_id);

		$assoc->fill($collection);

		// check that the relationship exists
		$this->assertSame($parent->FillChild, $collection);

		// it's a fill, so nothing should be marked as dirty
		$this->assertEquals(array(), $parent->getDirty());
		$this->assertEquals(array(), $child1->getDirty());
		$this->assertEquals(array(), $child2->getDirty());
	}

	public function testSetParentWithChild()
	{
		$parent = new $this->parentClass;
		$child1 = new $this->childClass;
		$child2 = new $this->childClass;

		$collection = new Collection(array($child1, $child2));

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_many_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child1,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->addAssociation(
			$child2,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->assertEquals(0, count($parent->SetChild));
		$this->assertNull($child1->SetParent);
		$this->assertNull($child2->SetParent);

		$parent->SetChild = $collection;

		// check that both were filled
		$this->assertSame($parent, $child1->SetParent);
		$this->assertSame($parent, $child2->SetParent);

		// check that the key was linked
		$this->assertEquals(5, $child1->parent_id);
		$this->assertEquals(5, $child2->parent_id);

		// setting should mark the foreign key as dirty
		$this->assertEquals(array('parent_id' => 5), $child1->getDirty());
		$this->assertEquals(array('parent_id' => 5), $child2->getDirty());
	}

	public function testAddChildrenToBlankParent()
	{
		$parent = new $this->parentClass;
		$child1 = new $this->childClass;
		$child2 = new $this->childClass;

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_many_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child1,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->addAssociation(
			$child2,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->assertEquals(0, count($parent->SetChild));
		$this->assertNull($child1->SetParent);
		$this->assertNull($child2->SetParent);

		$parent->SetChild[] = $child1;
		$parent->SetChild[] = $child2;

		// check that the reverse was set
//		$this->assertSame($parent, $child1->SetParent);
//		$this->assertSame($parent, $child2->SetParent);

		// check that the key was linked
		$this->assertEquals(5, $child1->parent_id);
		$this->assertEquals(5, $child2->parent_id);

		// setting should mark the foreign key as dirty
		$this->assertEquals(array('parent_id' => 5), $child1->getDirty());
		$this->assertEquals(array('parent_id' => 5), $child2->getDirty());
	}

	public function testAddChildToFilledParent()
	{
		$parent = new $this->parentClass;
		$child1 = new $this->childClass;
		$child2 = new $this->childClass;

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_many_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child1,
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child2,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->assertEquals(0, count($parent->SetChild));
		$this->assertNull($child1->SetParent);
		$this->assertNull($child2->SetParent);

		$assoc = $parent->getAssociation('SetChild');

		$assoc->fill(new Collection(array($child1)));
		$parent->SetChild[] = $child2;

		// check that the key was linked
		$this->assertEquals(5, $child1->parent_id);
		$this->assertEquals(5, $child2->parent_id);

		// setting should mark the foreign key as dirty, filling does not
		$this->assertEquals(array(), $child1->getDirty());
		$this->assertEquals(array('parent_id' => 5), $child2->getDirty());
	}

	public function testNullCollection()
	{
		$parent = new $this->parentClass;
		$child1 = new $this->childClass;
		$child2 = new $this->childClass;

		$collection = new Collection(array($child1, $child2));

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_many_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child1,
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child2,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->assertEquals(0, count($parent->SetChild));
		$this->assertNull($child1->SetParent);
		$this->assertNull($child2->SetParent);

		$assoc = $parent->getAssociation('SetChild');

		$assoc->fill($collection);

		// check that both were filled
		$this->assertSame(2, count($parent->SetChild));

		// Null it
		$parent->SetChild = NULL;

		// check that the key was linked
		$this->assertNull($child1->parent_id);
		$this->assertNull($child2->parent_id);

		// null means our foreign key has disappeared
		$this->assertEquals(array('parent_id' => NULL), $child1->getDirty());
		$this->assertEquals(array('parent_id' => NULL), $child2->getDirty());
	}

	public function testAddChildrenToUnsavedParentAndPropagateIdOn()
	{
		$parent = new $this->parentClass;
		$child1 = new $this->childClass;
		$child2 = new $this->childClass;

		$this->addAssociation(
			$parent,
			array($this->has_many_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child1,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->addAssociation(
			$child2,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->assertEquals(0, count($parent->SetChild));
		$this->assertNull($child1->SetParent);
		$this->assertNull($child2->SetParent);

		$parent->SetChild[] = $child1;
		$parent->SetChild[] = $child2;

		// check that both were filled
		$this->assertSame(2, count($parent->SetChild));
		$this->assertSame($parent, $child1->SetParent);
		$this->assertSame($parent, $child2->SetParent);

		$this->assertEquals(array(), $child1->getDirty());
		$this->assertEquals(array(), $child2->getDirty());

		// Now save it
		$parent->setId(10);

		// check that the key was linked
		$this->assertEquals(10, $child1->parent_id);
		$this->assertEquals(10, $child2->parent_id);

		// null means our foreign key has disappeared
		$this->assertEquals(array('parent_id' => 10), $child1->getDirty());
		$this->assertEquals(array('parent_id' => 10), $child2->getDirty());
	}

	public function testRemovingModelDissociates()
	{
		$parent = new $this->parentClass;
		$child1 = new $this->childClass;
		$child2 = new $this->childClass;

		$collection = new Collection(array($child1, $child2));

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_many_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child1,
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child2,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->assertEquals(0, count($parent->SetChild));
		$this->assertNull($child1->SetParent);
		$this->assertNull($child2->SetParent);

		$assoc = $parent->getAssociation('SetChild');

		$assoc->fill($collection);

		// check that both were filled
		$this->assertSame(2, count($parent->SetChild));
		$this->assertEquals(5, $child1->parent_id);
		$this->assertEquals(5, $child2->parent_id);

		// Null it
		$parent->SetChild->remove($child2);

		// check that the key was unlinked
		$this->assertEquals(5, $child1->parent_id);
		$this->assertEquals(NULL, $child2->parent_id);

		// null means our foreign key has disappeared
		$this->assertEquals(array(), $child1->getDirty());
		$this->assertEquals(array('parent_id' => NULL), $child2->getDirty());
	}

	public function testReplaceExistingCollection()
	{
		$parent = new $this->parentClass;
		$child1 = new $this->childClass;
		$child2 = new $this->childClass;
		$child3 = new $this->childClass;
		$child4 = new $this->childClass;

		$collection = new Collection(array($child1, $child2));
		$new_collection = new Collection(array($child3, $child4));

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_many_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child1,
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child2,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->addAssociation(
			$child3,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->addAssociation(
			$child4,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->assertEquals(0, count($parent->SetChild));
		$this->assertNull($child1->SetParent);
		$this->assertNull($child2->SetParent);

		$assoc = $parent->getAssociation('SetChild');
		$assoc->fill($collection);

		$this->assertSame(2, count($parent->SetChild));

		$this->assertEquals(5, $child1->parent_id);
		$this->assertEquals(5, $child2->parent_id);
		$this->assertNull($child3->parent_id);
		$this->assertNull($child4->parent_id);

		$parent->SetChild = $new_collection;

		$this->assertNull($child1->parent_id);
		$this->assertNull($child2->parent_id);
		$this->assertEquals(5, $child3->parent_id);
		$this->assertEquals(5, $child4->parent_id);

		$this->assertEquals(array('parent_id' => NULL), $child1->getDirty());
		$this->assertEquals(array('parent_id' => NULL), $child2->getDirty());
		$this->assertEquals(array('parent_id' => 5), $child3->getDirty());
		$this->assertEquals(array('parent_id' => 5), $child4->getDirty());
	}


	public function testAddingParentToManyChildren()
	{
		$parent = new $this->parentClass;
		$child1 = new $this->childClass;
		$child2 = new $this->childClass;
		$child3 = new $this->childClass;
		$child4 = new $this->childClass;

		$collection = new Collection(array($child1, $child2));

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_many_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child1,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->addAssociation(
			$child2,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->addAssociation(
			$child3,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$this->addAssociation(
			$child4,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_many_relation, 'SetChild')
		);

		$child1->SetParent = $parent;
		$child2->SetParent = $parent;
		$child3->SetParent = $parent;
		$child4->SetParent = $parent;

		$this->assertEquals(4, count($parent->SetChild));
		$this->assertEquals($parent, $child1->SetParent);
		$this->assertEquals($parent, $child2->SetParent);
		$this->assertEquals($parent, $child3->SetParent);
		$this->assertEquals($parent, $child4->SetParent);

		$this->assertEquals(array('parent_id' => 5), $child1->getDirty());
		$this->assertEquals(array('parent_id' => 5), $child2->getDirty());
		$this->assertEquals(array('parent_id' => 5), $child3->getDirty());
		$this->assertEquals(array('parent_id' => 5), $child4->getDirty());
	}

	protected function addAssociation($model, $relation, $inverse = NULL)
	{
		if ($inverse)
		{
			$relation[0]->shouldReceive('getKeys')->atLeast(1)->andReturn(array('parent_id', 'parent_id'));
			$relation[0]->shouldReceive('getInverse')->atLeast(1)->andReturn($inverse[0]);
			$inverse[0]->shouldReceive('getName')->atLeast(1)->andReturn($inverse[1]);
		}

		$relation[0]->shouldDeferMissing();

		$assoc = $relation[0]->createAssociation();
		$assoc->boot($model);

		$model->setAssociation($relation[1], $assoc);
		$assoc->markAsLoaded();

		return $assoc;
	}

}

class OneToManyParent extends Model {

	protected static $_primary_key = 'parent_id';

	protected $parent_id;

}

class OneToManyChild extends Model {

	protected static $_primary_key = 'child_id';

	protected $child_id;
	protected $parent_id;

}

// EOF
