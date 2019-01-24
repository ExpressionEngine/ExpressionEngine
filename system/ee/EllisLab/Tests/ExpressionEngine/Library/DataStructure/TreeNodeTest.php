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

use EllisLab\ExpressionEngine\Library\DataStructure\Tree\TreeNode;
use PHPUnit\Framework\TestCase;

class TreeNodeTest extends TestCase {


	public function validNodeNames()
	{
		$int	= 2;
		$float	= 3.5;
		$string = 'testname';
		$array	= array('a' => 'ok');
		$object = (object) $array;

		return array(
			array($int,    'Integer node name'),
			array($float,  'Float node name'),
			array($string, 'String node name'),
			array($array,  'Array node name'),
			array($object, 'Object node name')
		);
	}

	/**
	 * @dataProvider validNodeNames
	 */
	public function testConstruction($name, $msg)
	{
		// anything goes, ints, strings, arrays, objects, ...
		$node = new TreeNode($name);
		$this->assertEquals($name, $node->getName(), $msg);

		if (is_object($name))
		{
			$this->assertInstanceOf('\StdClass', $node->getName(), $msg);
		}
	}

	public function testPayloadIsEmpty()
	{
		$node = new TreeNode('test');
		$this->assertNull($node->data, '->__construct() with no payload');
		$this->assertNull($node->getData());
		return $node;
	}

	/**
	 * @depends testPayloadIsEmpty
	 */
	public function testPayloadSetter($node)
	{
		$node->data = 'value';
		$this->assertEquals('value', $node->getData());
		return $node;
	}

	/**
	 * @depends testPayloadSetter
	 */
	public function testPayloadGetter($node)
	{
		$result = $node->data;
		$this->assertEquals('value', $result);
	}



	public function testPayloadSetInConstructor()
	{
		$node = new TreeNode('test', 'payload');
		$this->assertEquals('payload', $node->data);
		$this->assertEquals('payload', $node->getData());
	}


	public function testPropertySetWithoutDataArrayError()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$node = new TreeNode('test');
		$node->key = 'value';
	}


	public function testAttemptToGetInvalidData()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$node = new TreeNode('test');
		$test = $node->key;
	}


	public function testArrayPayload()
	{
		$node = new TreeNode('test');
		$node->data = array('a' => 'one', 'b' => 'two', 'c' => 'three');

		$this->assertEquals('two', $node->b);
		$this->assertEquals('three', $node->data['c']);

		$node->b = 'changed';
		$this->assertEquals('changed', $node->b);
	}

	public function testFreeze()
	{
		$this->setExpectedException('\RuntimeException');

		$node = new TreeNode('test');
		$node->data = array('key' => 'value');
		$node->freeze();

		$node->key = 'wontset';
	}


	public function testCloneUnfreezes()
	{
		$node = new TreeNode('test');
		$node->data = array('key' => 'value');
		$node->freeze();

		$node2 = clone $node;
		$node2->key = 'changed'; // should not throw

		$this->assertEquals('changed', $node2->key);
		$this->assertEquals('value', $node->key);
	}

	public function testAdd()
	{
		$parent = new TreeNode('parent');
		$child1 = new TreeNode('child1');
		$child2 = new TreeNode('child2');

		$parent->add($child1);
		$child1->add($child2);
	}

	public function testIsRoot()
	{

	}

	public function testIsLeaf()
	{

	}

	/**
	 * Helper method to create this test three:
	 *
	 *                     parent
	 *                   /        \
	 *               child1      child2
	 *              /     \           \
	 *        subchild1  subchild2     subchild3
	 *                       |
	 *                  subsubchild1
	 */
	protected function setupTestTree()
	{
		$parent = new TreeNode('parent');
		$child1 = new TreeNode('child1');
		$child2 = new TreeNode('child2');
		$subchild1 = new TreeNode('subchild1');
		$subchild2 = new TreeNode('subchild2');
		$subchild3 = new TreeNode('subchild3');
		$subsubchild1 = new TreeNode('subsubchild1');

		$parent->add($child1);
		$parent->add($child2);
		$child1->add($subchild1);
		$child1->add($subchild2);
		$child2->add($subchild3);
		$subchild2->add($subsubchild1);
	}
}

// EOF
