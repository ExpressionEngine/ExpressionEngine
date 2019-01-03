<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Service;

use EllisLab\ExpressionEngine\Service\Filter\Perpage;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class PerpageTest extends TestCase {

	public function tearDown()
	{
		unset($_POST['perpage']);
		unset($_GET['perpage']);
	}

	public function testDefault()
	{
		$filter = new Perpage(123);
		$this->assertEquals(25, $filter->value(), 'The value is 25 by default.');
		$this->assertTrue($filter->isValid(), 'The default is valid');

		$vf = m::mock('EllisLab\ExpressionEngine\Service\View\ViewFactory');
		$url = m::mock('EllisLab\ExpressionEngine\Library\CP\URL');

		$vf->shouldReceive('make->render');
		$url->shouldReceive('setQueryStringVariable');
		$url->shouldReceive('compile')->andReturn('foo', 'bar', 'baz', 'whatthefox', 'gibberish', 'everything');
		$filter->render($vf, $url);
	}

	public function testPOST()
	{
		$_POST['perpage'] = 23;
		$filter = new Perpage(123);
		$this->assertEquals(23, $filter->value(), 'The value reflects the POSTed value');
		$this->assertTrue($filter->isValid(), 'POSTing a number is valid');
	}

	public function testGET()
	{
		$_GET['perpage'] = 23;
		$filter = new Perpage(123);
		$this->assertEquals(23, $filter->value(), 'The value reflects the GETed value');
		$this->assertTrue($filter->isValid(), 'GETing a number is valid');
	}

	public function testPOSTOverGET()
	{
		$_POST['perpage'] = 23;
		$_GET['perpage'] = 32;
		$filter = new Perpage(123);
		$this->assertEquals(23, $filter->value(), 'Use POST over GET');
	}

	// Use GET when POST is present but "empty"
	public function testGETWhenPOSTIsEmpty()
	{
		$_POST['perpage'] = '';
		$_GET['perpage'] = 32;
		$filter = new Perpage(123);
		$this->assertEquals(32, $filter->value(), 'Use GET when POST is an empty string');

		$_POST['perpage'] = NULL;
		$_GET['perpage'] = 32;
		$filter = new Perpage(123);
		$this->assertEquals(32, $filter->value(), 'Use GET when POST is NULL');

		$_POST['perpage'] = 0;
		$_GET['perpage'] = 32;
		$filter = new Perpage(123);
		$this->assertEquals(32, $filter->value(), 'Use GET when POST is 0');

		$_POST['perpage'] = "0";
		$_GET['perpage'] = 32;
		$filter = new Perpage(123);
		$this->assertEquals(32, $filter->value(), 'Use GET when POST is "0"');
	}

	/**
	 * @dataProvider validityDataProvider
	 */
	public function testValdity($submitted, $valid)
	{
		$_POST['perpage'] = $submitted;
		$filter = new Perpage(123);
		if ($valid)
		{
			$this->assertTrue($filter->isValid(), '"' . $submitted . '" is valid');
		}
		else
		{
			$this->assertFalse($filter->isValid(), '"' . $submitted . '" is invalid');
		}

		unset($_POST['perpage']);
		$_GET['perpage'] = $submitted;
		$filter = new Perpage(123);
		if ($valid)
		{
			$this->assertTrue($filter->isValid(), '"' . $submitted . '" is valid');
		}
		else
		{
			$this->assertFalse($filter->isValid(), '"' . $submitted . '" is invalid');
		}
	}

	public function validityDataProvider()
	{
		return array(
			array("42", TRUE),
		    array(1337, TRUE),
		    array(0x539, TRUE), // Converted to 1337
		    array(02471, TRUE), // Converted to 1337
		    // array(0b10100111001, TRUE), // Converted to 1337 PHP 5.4.0 or greater
		    array(1337e0, TRUE), // Converted to 1337
			array(9.1, TRUE),   // Coerced to 9
			array("foo", TRUE), // Uses the default
			array(-1, FALSE)
		);
	}

	public function testNoNumberSubmission()
	{
		$_POST['perpage'] = "abracadabra!";
		$filter = new Perpage(123);
		$this->assertEquals(25, $filter->value(), 'Submitting a non number will fall back to the default.');
		$this->assertTrue($filter->isValid(), 'The default is valid');
	}

}
