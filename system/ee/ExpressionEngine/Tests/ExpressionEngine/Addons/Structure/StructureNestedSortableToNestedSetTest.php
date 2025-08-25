<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureNestedSortableToNestedSetTest extends StructureTestBase
{
	public function testConvertsFlatArrayToNestedSet()
	{
		$input = [
			['id' => 10],
			['id' => 20],
		];

		$result = $this->structure->nestedsortable_to_nestedset($input);

		$this->assertArrayHasKey(10, $result);
		$this->assertArrayHasKey(20, $result);
		$this->assertSame(2, $result[10]['lft']);
		$this->assertSame(3, $result[10]['rgt']);
		$this->assertSame(4, $result[20]['lft']);
		$this->assertSame(5, $result[20]['rgt']);
		$this->assertSame([10], $result[10]['crumb']);
		$this->assertSame([20], $result[20]['crumb']);
	}

	public function testConvertsNestedChildrenMaintainingCrumbsAndPointers()
	{
		$input = [
			[
				'id' => 1,
				'children' => [
					[
						'id' => 2,
						'children' => [
							['id' => 3],
						],
					],
					['id' => 4],
				],
			],
		];

		$result = $this->structure->nestedsortable_to_nestedset($input);

		// Validate left/right pointers for a classic nested set
		$this->assertSame(2, $result[1]['lft']);
		$this->assertSame(9, $result[1]['rgt']);

		$this->assertSame(3, $result[2]['lft']);
		$this->assertSame(6, $result[2]['rgt']);

		$this->assertSame(4, $result[3]['lft']);
		$this->assertSame(5, $result[3]['rgt']);

		$this->assertSame(7, $result[4]['lft']);
		$this->assertSame(8, $result[4]['rgt']);

		// Validate crumbs reflect ancestry chain
		$this->assertSame([1, 2], $result[2]['crumb']);
		$this->assertSame([1, 2, 3], $result[3]['crumb']);
		$this->assertSame([1, 4], $result[4]['crumb']);
	}

	public function testEmptyInputReturnsEmptyArray()
	{
		$input = [];
		$result = $this->structure->nestedsortable_to_nestedset($input);
		$this->assertSame([], $result);
	}

	public function testSingleNodeProducesSequentialPointersAndSelfCrumb()
	{
		$input = [ ['id' => 1] ];
		$result = $this->structure->nestedsortable_to_nestedset($input);
		$this->assertSame(2, $result[1]['lft']);
		$this->assertSame(3, $result[1]['rgt']);
		$this->assertSame([1], $result[1]['crumb']);
	}

	public function testDeeplyNestedTreeSatisfiesNestedSetInvariants()
	{
		$input = [
			['id' => 1, 'children' => [
				['id' => 2, 'children' => [
					['id' => 3],
					['id' => 4],
				]],
				['id' => 5],
			]],
		];

		$result = $this->structure->nestedsortable_to_nestedset($input);

		// Invariants: lft < rgt, (rgt - lft) is odd, and parent range contains children
		foreach ($result as $id => $node) {
			$this->assertLessThan($node['rgt'], $node['lft']);
			$this->assertSame(1, ($node['rgt'] - $node['lft']) % 2);
		}

		// Parent containment checks for known relationships
		$this->assertTrue($result[1]['lft'] < $result[2]['lft'] && $result[1]['rgt'] > $result[2]['rgt']);
		$this->assertTrue($result[2]['lft'] < $result[3]['lft'] && $result[2]['rgt'] > $result[3]['rgt']);
		$this->assertTrue($result[2]['lft'] < $result[4]['lft'] && $result[2]['rgt'] > $result[4]['rgt']);
	}
}


