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
use PHPUnit\Framework\TestCase;


class OneToOneModelTest extends TestCase {

	public function setUp()
	{
		$this->parentClass = __NAMESPACE__.'\OneToOneParent';
		$this->childClass = __NAMESPACE__.'\OneToOneChild';

		$this->has_one_relation = m::mock('EllisLab\ExpressionEngine\Service\Model\Relation\HasOne');
		$this->belongs_to_relation = m::mock('EllisLab\ExpressionEngine\Service\Model\Relation\BelongsTo');
	}

	public function tearDown()
	{
		$this->has_one_relation = NULL;
		$this->belongs_to_relation = NULL;
	}

	public function testFillParentWithChild()
	{
		$parent = new $this->parentClass;
		$child = new $this->childClass;

		$parent->setId(5);

		$assoc = $this->addAssociation(
			$parent,
			array($this->has_one_relation, 'FillChild'),
			array($this->belongs_to_relation, 'FillParent')
		);

		$this->addAssociation(
			$child,
			array($this->belongs_to_relation, 'FillParent')
		);

		$this->assertNull($parent->FillChild);
		$this->assertNull($child->FillParent);

		$assoc->fill($child);

		// check that both were filled
		$this->assertSame($parent->FillChild, $child);

		// check that the key was linked
		$this->assertEquals(5, $child->parent_id);

		// it's a fill, so nothing should be marked as dirty
		$this->assertEquals(array(), $child->getDirty());
	}

	public function testFillChildWithParent()
	{
		$parent = new $this->parentClass;
		$child = new $this->childClass;

		$parent->setId(5);

		$assoc = $this->addAssociation(
			$child,
			array($this->belongs_to_relation, 'FillParent'),
			array($this->has_one_relation, 'FillChild')
		);

		$this->addAssociation(
			$parent,
			array($this->has_one_relation, 'FillChild')
		);

		$this->assertNull($child->FillParent);
		$this->assertNull($parent->FillChild);

		$assoc->fill($parent);

		// check that both were filled
		$this->assertSame($child->FillParent, $parent);

		// check that the key was linked
		$this->assertEquals(5, $child->parent_id);

		// it's a fill, so nothing should be marked as dirty
		$this->assertEquals(array(), $child->getDirty());
	}

	public function testSetParentWithChild()
	{
		$parent = new $this->parentClass;
		$child = new $this->childClass;

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_one_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_one_relation, 'SetChild')
		);

		$this->assertNull($parent->SetChild);
		$this->assertNull($child->SetParent);

		$parent->SetChild = $child;

		// check that both were filled
		$this->assertSame($child, $parent->SetChild);
		$this->assertSame($parent, $child->SetParent);

		// check that the key was linked
		$this->assertEquals(5, $child->parent_id);

		// setting should mark the foreign key as dirty
		$this->assertEquals(array('parent_id' => 5), $child->getDirty());
	}

	public function testSetChildWithParent()
	{
		$parent = new $this->parentClass;
		$child = new $this->childClass;

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_one_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_one_relation, 'SetChild')
		);

		$this->assertNull($parent->SetChild);
		$this->assertNull($child->SetParent);

		$child->SetParent = $parent;

		// check that both were filled
		$this->assertSame($child, $parent->SetChild);
		$this->assertSame($parent, $child->SetParent);

		// check that the key was linked
		$this->assertEquals(5, $child->parent_id);

		// setting should mark the foreign key as dirty
		$this->assertEquals(array('parent_id' => 5), $child->getDirty());
	}

	public function testSetParentWithChildShouldDisassociateExisting()
	{
		$parent = new $this->parentClass;
		$child = new $this->childClass;
		$new_child = new $this->childClass;

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_one_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_one_relation, 'SetChild')
		);

		$this->addAssociation(
			$new_child,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_one_relation, 'SetChild')
		);

		$parent->SetChild = $child;

		$this->assertEquals(5, $child->parent_id);
		$this->assertEquals(NULL, $new_child->parent_id);
		$this->assertSame($child, $parent->SetChild);
		$this->assertSame($parent, $child->SetParent);
		$this->assertSame(NULL, $new_child->SetParent);

		$this->assertEquals(array('parent_id' => 5), $child->getDirty());
		$this->assertEquals(array(), $new_child->getDirty());

		$parent->SetChild = $new_child;

		$this->assertEquals(NULL, $child->parent_id);
		$this->assertEquals(5, $new_child->parent_id);
		$this->assertSame($new_child, $parent->SetChild);
		$this->assertSame($parent, $new_child->SetParent);
		$this->assertSame(NULL, $child->SetParent);

		$this->assertEquals(array(), $child->getDirty());
		$this->assertEquals(array('parent_id' => 5), $new_child->getDirty());
	}

	public function testSetChildWithParentShouldDisassociateExisting()
	{
		$parent = new $this->parentClass;
		$child = new $this->childClass;
		$new_child = new $this->childClass;

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_one_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_one_relation, 'SetChild')
		);

		$this->addAssociation(
			$new_child,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_one_relation, 'SetChild')
		);

		$this->assertNull($parent->SetChild);
		$this->assertNull($child->SetParent);

		$child->SetParent = $parent;

		$this->assertEquals(5, $child->parent_id);
		$this->assertEquals(NULL, $new_child->parent_id);
		$this->assertSame($child, $parent->SetChild);
		$this->assertSame($parent, $child->SetParent);
		$this->assertSame(NULL, $new_child->SetParent);

		$this->assertEquals(array('parent_id' => 5), $child->getDirty());
		$this->assertEquals(array(), $new_child->getDirty());

		$new_child->SetParent = $parent;

		$this->assertEquals(NULL, $child->parent_id);
		$this->assertEquals(5, $new_child->parent_id);
		$this->assertSame($new_child, $parent->SetChild);
		$this->assertSame($parent, $new_child->SetParent);
		$this->assertSame(NULL, $child->SetParent);

		$this->assertEquals(array(), $child->getDirty());
		$this->assertEquals(array('parent_id' => 5), $new_child->getDirty());
	}

	public function testMoveChildToDifferentParent()
	{
		$parent = new $this->parentClass;
		$new_parent = new $this->parentClass;
		$child = new $this->childClass;

		$parent->setId(5);
		$new_parent->setId(10);

		$assoc = $this->addAssociation(
			$parent,
			array($this->has_one_relation, 'SetChild')
			// we fill this so the reverse is not important
		);

		$this->addAssociation(
			$new_parent,
			array($this->has_one_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_one_relation, 'SetChild')
		);

		$this->assertNull($parent->SetChild);
		$this->assertNull($new_parent->SetChild);
		$this->assertNull($child->SetParent);

		$assoc->fill($child);

		$this->assertEquals(5, $child->parent_id);
		$this->assertNull($new_parent->SetChild);
		$this->assertSame($child, $parent->SetChild);
		$this->assertEquals(array(), $child->getDirty());

		$new_parent->SetChild = $child;

		$this->assertEquals(10, $child->parent_id);
		$this->assertEquals($new_parent, $child->SetParent);
		$this->assertSame($child, $new_parent->SetChild);
		$this->assertEquals(array('parent_id' => 10), $child->getDirty());
	}

	public function testSetParentWithChildToNull()
	{
		$parent = new $this->parentClass;
		$child = new $this->childClass;

		$parent->setId(5);

		$assoc = $this->addAssociation(
			$parent,
			array($this->has_one_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_one_relation, 'SetChild')
		);

		$assoc->fill($child);

		$this->assertEquals(5, $child->parent_id);
		$this->assertSame($child, $parent->SetChild);

		$this->assertEquals(array(), $child->getDirty());

		$parent->SetChild = NULL;

		$this->assertEquals(NULL, $child->parent_id);
		$this->assertSame(NULL, $parent->SetChild);
		$this->assertSame(NULL, $child->SetParent);

		$this->assertEquals(array('parent_id' => NULL), $child->getDirty());
	}

	public function testSetParentWithChildAndBackToNull()
	{
		$parent = new $this->parentClass;
		$child = new $this->childClass;

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_one_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_one_relation, 'SetChild')
		);

		$parent->SetChild = $child;

		$this->assertEquals(5, $child->parent_id);
		$this->assertSame($child, $parent->SetChild);
		$this->assertSame($parent, $child->SetParent);

		$this->assertEquals(array('parent_id' => 5), $child->getDirty());

		$parent->SetChild = NULL;

		$this->assertEquals(NULL, $child->parent_id);
		$this->assertSame(NULL, $parent->SetChild);
		$this->assertSame(NULL, $child->SetParent);

		$this->assertEquals(array(), $child->getDirty());
	}

	public function testSetChildWithParentToNull()
	{
		$parent = new $this->parentClass;
		$child = new $this->childClass;

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_one_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$assoc = $this->addAssociation(
			$child,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_one_relation, 'SetChild')
		);

		$assoc->fill($parent);

		$this->assertEquals(5, $child->parent_id);
		$this->assertSame($parent, $child->SetParent);

		$this->assertEquals(array(), $child->getDirty());

		$child->SetParent = NULL;

		$this->assertEquals(NULL, $child->parent_id);
		$this->assertSame(NULL, $parent->SetChild);
		$this->assertSame(NULL, $child->SetParent);

		$this->assertEquals(array('parent_id' => NULL), $child->getDirty());
	}

	public function testSetChildWithParentAndBackToNull()
	{
		$parent = new $this->parentClass;
		$child = new $this->childClass;

		$parent->setId(5);

		$this->addAssociation(
			$parent,
			array($this->has_one_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_one_relation, 'SetChild')
		);

		$child->SetParent = $parent;

		$this->assertEquals(5, $child->parent_id);
		$this->assertSame($child, $parent->SetChild);
		$this->assertSame($parent, $child->SetParent);

		$this->assertEquals(array('parent_id' => 5), $child->getDirty());

		$child->SetParent = NULL;

		$this->assertEquals(NULL, $child->parent_id);
		$this->assertSame(NULL, $parent->SetChild);
		$this->assertSame(NULL, $child->SetParent);

		$this->assertEquals(array(), $child->getDirty());
	}

	public function testSetChildOnUnsavedParentAndPropagateIdOn()
	{
		$parent = new $this->parentClass;
		$child = new $this->childClass;

		$this->addAssociation(
			$parent,
			array($this->has_one_relation, 'SetChild'),
			array($this->belongs_to_relation, 'SetParent')
		);

		$this->addAssociation(
			$child,
			array($this->belongs_to_relation, 'SetParent'),
			array($this->has_one_relation, 'SetChild')
		);

		$parent->SetChild = $child;

		$this->assertEquals(NULL, $child->parent_id);
		$this->assertSame($child, $parent->SetChild);
		$this->assertSame($parent, $child->SetParent);
		$this->assertEquals(array(), $child->getDirty());

		$parent->setId(5);

		$this->assertEquals(5, $child->parent_id);
		$this->assertEquals(array('parent_id' => 5), $child->getDirty());
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

class OneToOneParent extends Model {

	protected static $_primary_key = 'parent_id';

	protected $parent_id;

}

class OneToOneChild extends Model {

	protected static $_primary_key = 'child_id';

	protected $child_id;
	protected $parent_id;

}

// EOF
