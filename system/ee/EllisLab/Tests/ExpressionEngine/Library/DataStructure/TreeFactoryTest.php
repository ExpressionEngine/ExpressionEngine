<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Library\DataStructure\Tree;

use EllisLab\ExpressionEngine\Library\DataStructure\Tree\TreeFactory;
use PHPUnit\Framework\TestCase;

class TreeFactoryTest extends TestCase {

	public function setUp()
	{
		$this->tf = new TreeFactory();
	}

	public function exampleTreeData()
	{
		// single level tree
		$flat = array(
			array('id' => 5, 'parent_id' => 0, 'name' => 'tom'),
			array('id' => 6, 'parent_id' => 0, 'name' => 'dick'),
			array('id' => 7, 'parent_id' => 0, 'name' => 'harry')
		);

		// single long branch
		$one_branch = array(
			array('id' => 5, 'parent_id' => 0, 'name' => 'tom'),
			array('id' => 6, 'parent_id' => 5, 'name' => 'dick'),
			array('id' => 7, 'parent_id' => 6, 'name' => 'harry')
		);

		return array(
			array($flat, 'Single level tree'),
			array($one_branch, 'Long branch of single children')
		);
	}

	/**
	 * @dataProvider exampleTreeData
	 */
	public function testFromList($data, $msg)
	{
		$tf = new TreeFactory();
		$tf->fromList($data);

		$this->markTestIncomplete('not yet implemented');

	}

	/**
	 * @dataProvider exampleTreeData
	 */
	public function testToList($data, $msg)
	{
		$tf = new TreeFactory();
		$t = $tf->fromList($data);

		$this->markTestIncomplete('not yet implemented');


		//$this->assertEquals(, $tf->toList($t));
	}
}
