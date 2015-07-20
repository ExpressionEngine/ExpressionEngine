<?php

namespace EllisLab\Tests\ExpressionEngine\Library\Security;

use EllisLab\ExpressionEngine\Library\Security\XSS;

require_once SYSPATH.'ee/EllisLab/ExpressionEngine/Boot/boot.common.php';

class XSSTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->xss = new XSS();
	}

	public function tearDown()
	{
		$this->xss = NULL;
	}

	public function testXssClean()
	{
		$testArray = array(
			'"><script>alert(\'stored xss\')<%2fscript>' => '">[removed]alert&#40;\'stored xss\'&#41;[removed]'
		);

		foreach ($testArray as $before => $after) {
			$this->assertEquals($after, $this->xss->clean($before));
		}
	}
}