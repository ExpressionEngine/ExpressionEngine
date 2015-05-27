<?php

namespace EllisLab\Test\ExpressionEngine\Service\Model\Relation;

use Mockery as m;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Relation\HasMany;

class HasManyTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->from = m::mock('EllisLab\\ExpressionEngine\\Service\\Model\\MetaDataReader');
		$this->to = m::mock('EllisLab\\ExpressionEngine\\Service\\Model\\MetaDataReader');

		$this->from->shouldReceive('getTableForField')->with('parent_id')->andReturn('parent_table');
		$this->to->shouldReceive('getTableForField')->with('parent_id')->andReturn('child_table');

		$options = array(
			'model' => 'Child',
			'from_key' => 'parent_id',
			'to_key' => 'parent_id',

			'from_primary_key' => 'parent_id',
			'to_primary_key' => 'child_id'
		);

		$this->relation = new HasMany($this->from, $this->to, 'test', $options);
	}

	public function tearDown()
	{
		$this->from = NULL;
		$this->to = NULL;
		$this->relation = NULL;

		m::close();
	}

	public function testGetSourceModel()
	{
		$this->from->shouldReceive('getName')->andReturn('Parent');
		$name = $this->relation->getSourceModel();

		$this->assertEquals('Parent', $name);
	}

	public function testGetTargetModel()
	{
		$this->to->shouldReceive('getName')->andReturn('Child');
		$name = $this->relation->getTargetModel();

		$this->assertEquals('Child', $name);
	}

	public function testCreateAssociation()
	{
		$parent = $this->newModelMock();

		$association = $this->relation->createAssociation($parent);

		$this->assertInstanceOf(
			'EllisLab\ExpressionEngine\Service\Model\Association\HasMany',
			$association
		);
	}

	public function testLinkIds()
	{
		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$parent->shouldReceive('hasProperty')->with('parent_id')->andReturn(TRUE);
		$parent->shouldReceive('getProperty')->with('parent_id')->andReturn(8);

		$child->shouldReceive('hasProperty')->with('parent_id')->andReturn(TRUE);
		$child->shouldReceive('setProperty')->with('parent_id', 8);

		$this->relation->linkIds($parent, $child);
	}

	public function testUnlinkIds()
	{
		$parent = $this->newModelMock();
		$child = $this->newModelMock();

		$child->shouldReceive('hasProperty')->with('parent_id')->andReturn(TRUE);
		$child->shouldReceive('setProperty')->with('parent_id', 8);

		$child->parent_id = 8;

		$child->shouldReceive('hasProperty')->with('parent_id')->andReturn(TRUE);
		$child->shouldReceive('setProperty')->with('parent_id', NULL);

		$this->relation->unLinkIds($parent, $child);
	}

	protected function newModelMock()
	{
		return m::mock('EllisLab\ExpressionEngine\Service\Model\Model');
	}

}