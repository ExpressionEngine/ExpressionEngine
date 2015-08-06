<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Profiler;

use EllisLab\ExpressionEngine\Service\Profiler\Profiler;

class ProfilerTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->profiler = new Profiler();
	}

	public function tearDown()
	{
		$this->profiler = NULL;
	}
}