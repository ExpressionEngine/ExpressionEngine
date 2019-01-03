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

use EllisLab\ExpressionEngine\Service\Filter\Site;
use Mockery as m;
use stdClass;
use PHPUnit\Framework\TestCase;

class SiteTest extends TestCase {

	protected $sites = array(
		'1' => "Main",
		'2' => "Site 2"
	);

	public function tearDown()
	{
		unset($_POST['filter_by_site']);
		unset($_GET['filter_by_site']);
	}

	public function testDefault()
	{
		$filter = new Site();
		$this->assertNull($filter->value(), 'The value is NULL by default.');
		$this->assertTrue($filter->isValid(), 'The default is valid');

		$vf = m::mock('EllisLab\ExpressionEngine\Service\View\ViewFactory');
		$url = m::mock('EllisLab\ExpressionEngine\Library\CP\URL');

		// $vf->shouldReceive('make->render');
		// $url->shouldReceive('setQueryStringVariable', 'compile');

		$this->assertEquals('', $filter->render($vf, $url), 'Rendering is bypassed when disabled');
	}

	public function testValidConstructors()
	{
		// Test constructor with bool
		$filter = new Site();
		$this->assertEquals(array(), $filter->getOptions());

		$filter = new Site($this->sites);
		$this->assertEquals($this->sites, $filter->getOptions());
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testInvalidConstructors($array)
	{
		$this->setExpectedException($exception);
		new Site($array);
	}

	public function invalidConstructorProvider()
	{
		return array(
			array("string"),
			array(1),
			array(0x539),
			array(02471),
			array(1337e0),
			array(9.1),
			array(FALSE),
			array(NULL),
			array(new stdClass),
			array(function() {return array();}),
		);
	}

	public function testSetMSMEnabledFalse()
	{
		$vf = m::mock('EllisLab\ExpressionEngine\Service\View\ViewFactory');
		$url = m::mock('EllisLab\ExpressionEngine\Library\CP\URL');

		$filter = new Site();
		$filter->disableMSM();
		$this->assertEquals('', $filter->render($vf, $url), 'Rendering is bypassed when disabled');
	}

	public function testSetMSMEnabledTrue()
	{
		$vf = m::mock('EllisLab\ExpressionEngine\Service\View\ViewFactory');
		$url = m::mock('EllisLab\ExpressionEngine\Library\CP\URL');

		$filter = new Site();
		$filter->enableMSM();

		$vf->shouldReceive('make->render');
		$url->shouldReceive('setQueryStringVariable', 'compile');

		$filter->render($vf, $url);
	}

	public function testPOST()
	{
		$_POST['filter_by_site'] = 1;
		$filter = new Site($this->sites);
		$filter->enableMSM();
		$this->assertEquals(1, $filter->value(), 'The value reflects the POSTed value');
		$this->assertTrue($filter->isValid(), 'POSTing a number is valid');
	}

	public function testGET()
	{
		$_GET['filter_by_site'] = 1;
		$filter = new Site($this->sites);
		$filter->enableMSM();
		$this->assertEquals(1, $filter->value(), 'The value reflects the GETed value');
		$this->assertTrue($filter->isValid(), 'GETing a number is valid');
	}

	public function testPOSTOverGET()
	{
		$_POST['filter_by_site'] = 1;
		$_GET['filter_by_site'] = 2;
		$filter = new Site($this->sites);
		$filter->enableMSM();
		$this->assertEquals(1, $filter->value(), 'Use POST over GET');
	}

	// Use GET when POST is present but "empty"
	public function testGETWhenPOSTIsEmpty()
	{
		$_POST['filter_by_site'] = '';
		$_GET['filter_by_site'] = 2;
		$filter = new Site($this->sites);
		$filter->enableMSM();
		$this->assertEquals(2, $filter->value(), 'Use GET when POST is an empty string');

		$_POST['filter_by_site'] = NULL;
		$_GET['filter_by_site'] = 2;
		$filter = new Site($this->sites);
		$filter->enableMSM();
		$this->assertEquals(2, $filter->value(), 'Use GET when POST is NULL');

		$_POST['filter_by_site'] = 0;
		$_GET['filter_by_site'] = 2;
		$filter = new Site($this->sites);
		$filter->enableMSM();
		$this->assertEquals(2, $filter->value(), 'Use GET when POST is 0');

		$_POST['filter_by_site'] = "0";
		$_GET['filter_by_site'] = 2;
		$filter = new Site($this->sites);
		$filter->enableMSM();
		$this->assertEquals(2, $filter->value(), 'Use GET when POST is "0"');
	}

	/**
	 * @dataProvider validityDataProvider
	 */
	public function testValdity($submitted, $valid)
	{
		$_POST['filter_by_site'] = $submitted;
		$filter = new Site($this->sites);
		$filter->disableMSM();
		$this->assertEquals($submitted, $filter->value());
		$this->assertTrue($filter->isValid(), '"' . $submitted . '" is valid when disabled');

		$filter->enableMSM();
		if ($valid)
		{
			$this->assertTrue($filter->isValid(), '"' . $submitted . '" is valid when enabled');
		}
		else
		{
			$this->assertFalse($filter->isValid(), '"' . $submitted . '" is invalid when enabled');
		}

		unset($_POST['filter_by_site']);
		$_GET['filter_by_site'] = $submitted;
		$filter = new Site($this->sites);
		$filter->disableMSM();
		$this->assertEquals($submitted, $filter->value());
		$this->assertTrue($filter->isValid(), '"' . $submitted . '" is valid when disabled');

		$filter->enableMSM();
		if ($valid)
		{
			$this->assertTrue($filter->isValid(), '"' . $submitted . '" is valid when enabled');
		}
		else
		{
			$this->assertFalse($filter->isValid(), '"' . $submitted . '" is invalid when enabled');
		}
	}

	public function validityDataProvider()
	{
		return array(
			array(1, TRUE),
			array("1", TRUE),
			array(2, TRUE),

			array(0x1, TRUE),
			array(01, TRUE),
			array(1e0, TRUE),

			array(3, FALSE),
			array("string", FALSE),
			array(9.1, FALSE),
			array(FALSE, FALSE),
			array(NULL, FALSE),
		);
	}

}
