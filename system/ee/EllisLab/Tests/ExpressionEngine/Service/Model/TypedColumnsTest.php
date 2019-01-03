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
use EllisLab\ExpressionEngine\Service\Model\Model;
use PHPUnit\Framework\TestCase;

class TypedColumnsTest extends TestCase {

	public function setUp()
	{
		$class = __NAMESPACE__.'\TypedColumnsStub';
		$this->obj = new $class;
	}

	public function tearDown()
	{
		$this->obj = NULL;
	}

	public function testInt()
	{
		$obj = $this->obj;

		$this->assertEquals(0, $obj->integer, 'default value');

		$obj->fill(array('integer' => 5));
		$this->assertEquals(5, $obj->integer);

		$obj->fill(array('integer' => '7'));
		$this->assertEquals(7, $obj->integer);

		$obj->fill(array('integer' => 'nonsense'));
		$this->assertEquals(0, $obj->integer);

		$obj->fill(array('integer' => 5));
		$this->assertEquals(5, $obj->integer);

		$obj->integer = '7';
		$this->assertSame(7, $obj->integer);
		$this->assertSame(array('integer' => 7), $obj->getDirty(), 'storage value');
		$this->assertSame(array('integer' => '7'), $obj->getModified(), 'validation value');

		$obj->integer = 'bogus';
		$this->assertSame(0, $obj->integer);
		$this->assertSame(array('integer' => 0), $obj->getDirty(), 'storage value');
		$this->assertSame(array('integer' => 'bogus'), $obj->getModified(), 'validation value');
	}

	public function testYesNo()
	{
		$obj = $this->obj;

		$this->assertEquals(FALSE, $obj->yesno, 'default value');

		$obj->fill(array('yesno' => 'y'));
		$this->assertTrue($obj->yesno);

		$obj->fill(array('yesno' => 'n'));
		$this->assertFalse($obj->yesno);

		$obj->fill(array('yesno' => TRUE));
		$this->assertTrue($obj->yesno);

		$obj->fill(array('yesno' => FALSE));
		$this->assertFalse($obj->yesno);

		$obj->fill(array('yesno' => 1));
		$this->assertTrue($obj->yesno);

		$obj->fill(array('yesno' => 'nonsense'));
		$this->assertFalse($obj->yesno);

		$obj->fill(array('yesno' => 'y'));
		$obj->yesno = 'n';
		$this->assertFalse($obj->yesno);
		$this->assertSame(array('yesno' => 'n'), $obj->getDirty(), 'storage value');
		$this->assertSame(array('yesno' => 'n'), $obj->getModified(), 'validation value');

		$obj->yesno = TRUE;
		$this->assertTrue($obj->yesno);
		$this->assertSame(array('yesno' => 'y'), $obj->getDirty(), 'storage value');
		$this->assertSame(array('yesno' => TRUE), $obj->getModified(), 'validation value');

		$obj->yesno = 'bogus';
		$this->assertFalse($obj->yesno);
		$this->assertSame(array('yesno' => 'n'), $obj->getDirty(), 'storage value');
		$this->assertSame(array('yesno' => 'bogus'), $obj->getModified(), 'validation value');
	}

	public function testSerialized()
	{
		$obj = $this->obj;

		$bob = array('name' => 'bob');
		$mary = array('name' => 'mary', 'age' => 35);

		$bob_data = serialize($bob);
		$mary_data = serialize($mary);

		$this->assertSame(array(), $obj->native, 'default value');

		$obj->fill(array('native' => $bob_data));
		$this->assertEquals($bob, $obj->native);

		$this->assertSame(array(), $obj->getDirty(), 'storage value');
		$this->assertSame(array(), $obj->getModified(), 'validation value');

		$obj->native = $mary;
		$this->assertEquals($mary, $obj->native);

		$this->assertSame(array('native' => $mary_data), $obj->getDirty(), 'storage value');
		$this->assertSame(array('native' => $mary), $obj->getModified(), 'validation value');
	}
}

class TypedColumnsStub extends Model {

	public static $_typed_columns = array(
		'boolean' => 'boolean',
		'float' => 'float',
		'integer' => 'int',
		'string' => 'string',
		'yesno' => 'yesNo',
		'json' => 'json',
		'native' => 'serialized'
	);

	protected $boolean;
	protected $float;
	protected $integer;
	protected $string;
	protected $yesno;

	protected $json;
	protected $native;
}

// EOF
