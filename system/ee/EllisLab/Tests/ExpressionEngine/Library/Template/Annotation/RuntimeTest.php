<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Library\Template\Annotation;

use EllisLab\ExpressionEngine\Library\Template\Annotation\Runtime as RuntimeAnnotation;
use PHPUnit\Framework\TestCase;

class RuntimeTest extends TestCase {

	public function testCreatesUniqueComments()
	{
		$anno = new RuntimeAnnotation;

		$c1 = $anno->create();
		$c2 = $anno->create();
		$c3 = $anno->create();

		$this->assertFalse($c1 == $c2);
		$this->assertFalse($c2 == $c3);
		$this->assertFalse($c3 == $c1);
	}

	public function testAlwaysReturnsData()
	{
		$anno = new RuntimeAnnotation;

		$comment = $anno->create();

		$d1 = $anno->read($comment);
		$d2 = $anno->read($comment);
		$d3 = $anno->read($comment);

		$this->assertSame($d1, $d2);
		$this->assertSame($d2, $d3);
		$this->assertSame($d3, $d1);
	}

	public function testReturnsCorrectInstance()
	{
		$anno = new RuntimeAnnotation;

		$comment = $anno->create();
		$comment2 = $anno->create();

		$d1 = $anno->read($comment);
		$d2 = $anno->read($comment2);
		$d3 = $anno->read($comment);

		$this->assertNotSame($d1, $d2);
		$this->assertNotSame($d2, $d3);
		$this->assertSame($d3, $d1);
	}

	public function testReturnsCorrectDefaultData()
	{
		$anno = new RuntimeAnnotation;

		$comment = $anno->create(array('year' => '1602'));
		$data = $anno->read($comment);

		$this->assertEquals('1602', $data->year);
	}

	public function testDataMutable()
	{
		$anno = new RuntimeAnnotation;

		$comment = $anno->create(array('year' => '1602'));

		// make sure the initial is set
		$data = $anno->read($comment);
		$this->assertEquals('1602', $data->year);

		// change and regrab the instance from the comment
		$data->year = 1503;
		$data = $anno->read($comment);
		$this->assertEquals(1503, $data->year);
	}

	public function testDataNotShared()
	{
		$anno1 = new RuntimeAnnotation;
		$anno2 = new RuntimeAnnotation;

		$data = array(array('year' => '1602'));

		$comment1 = $anno1->create($data);
		$comment2 = $anno2->create($data);


		$this->assertNull($anno1->read($comment2));
		$this->assertNull($anno2->read($comment1));
	}

	public function testSharedStore()
	{
		$anno1 = new RuntimeAnnotation;
		$anno2 = new RuntimeAnnotation;

		$anno1->useSharedStore();
		$anno2->useSharedStore();

		$data = array(array('year' => '1602'));

		$comment1 = $anno1->create($data);
		$comment2 = $anno2->create($data);

		$this->assertSame($anno1->read($comment1), $anno2->read($comment1));
		$this->assertSame($anno1->read($comment2), $anno2->read($comment2));
	}
}
