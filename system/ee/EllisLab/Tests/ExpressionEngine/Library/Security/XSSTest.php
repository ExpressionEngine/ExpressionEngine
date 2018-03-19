<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

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
			'"><script>alert(\'stored xss\')<%2fscript>' => '">[removed]alert&#40;\'stored xss\'&#41;[removed]',
			'"><a onload=alert(1);>' => '"><a >',
			'"><a/onload=alert(1);>' => '"><a>',
			'"><img onload=alert(1);>' => '"><img >',
			'"><img/onload=alert(1);>' => '"><img>',
			'"><svg onload=alert(1);>' => '"><svg >',
			'"><svg/onload=alert(1);>' => '"><svg>',
			'<x onclick=alert(1) src=a>1</x>' => '<x  src=a>1</x>',
			'<marquee loop=1 width=0 onfinish=confirm(1)//' => '<marquee loop=1 width=0 ',
			"<select autofocus onfocus='confirm(1)'" => '<select autofocus ',

			// RTLO characters, some invisible, all at position some_file_[RTLO]3pm.exe
			'<a href="http://example.com/some_file_‮3pm.exe">http://example.com/some_file_‮3pm.exe</a>' =>
				'<a href="http://example.com/some_file_3pm.exe">http://example.com/some_file_3pm.exe</a>',
			'<a href="http://example.com/some_file_&#8238;3pm.exe">http://example.com/some_file_&#8238;3pm.exe</a>' =>
				'<a href="http://example.com/some_file_3pm.exe">http://example.com/some_file_3pm.exe</a>',
			'<a href="http://example.com/some_file_%E2%80%AE3pm.exe">http://example.com/some_file_%E2%80%AE3pm.exe</a>' =>
				'<a href="http://example.com/some_file_3pm.exe">http://example.com/some_file_3pm.exe</a>',
			'http://example.com/some_file_‮3pm.exe' => 'http://example.com/some_file_3pm.exe',
			'http://example.com/some_file_&#8238;3pm.exe' => 'http://example.com/some_file_3pm.exe',
			'http://example.com/some_file_%E2%80%AE3pm.exe' => 'http://example.com/some_file_3pm.exe',
		);

		foreach ($testArray as $before => $after) {
			$this->assertEquals($after, $this->xss->clean($before));
		}
	}
}
