<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Core;

use EllisLab\AutoloaderTest as TestAlias;
use EllisLab\ExpressionEngine\Core\Autoloader as Autoloader;
use PHPUnit\Framework\TestCase;

class AutoloaderTest extends TestCase {

	private $autoloader;

	protected function setUp()
	{
		$this->autoloader = new Autoloader();

		// The testsuite autoloader technically handles the full EllisLab
		// namespace, but we can take advantage of its simplicity and the fact
		// that it fails silently.
		// By missmatching the prefix and path name we can guarantee a silent
		// failure on the testsuite loader, thereby isolating the test to the
		// main autoloader.

		$this->autoloader->addPrefix('EllisLab\AutoloaderTest', __DIR__.'/AutoloaderFixture');
	}

	protected function tearDown()
	{
		$this->autoloader = NULL;
	}

	public function testLoadClass()
	{
		$this->autoloader->loadClass('EllisLab\AutoloaderTest\TestFileOne');
		$this->assertTrue(class_exists('\TestFileOne'), 'loadClass(): file without namespacing');

		$this->autoloader->loadClass('EllisLab\AutoloaderTest\TestFileTwo');
		$this->assertTrue(class_exists('\EllisLab\AutoloaderTest\TestFileTwo'), 'class file with namespacing');
	}

	public function testRegister()
	{
		$this->autoloader->register();
		$test = new \EllisLab\AutoloaderTest\TestFileThree();
		$this->autoloader->unregister();

		$this->assertInstanceOf('EllisLab\AutoloaderTest\TestFileThree', $test);
	}

	public function testLoadClassHandlesAutomaticallyResolvedAlias()
	{
		$this->autoloader->register();
		$test = new TestAlias\TestFileFour();
		$this->autoloader->unregister();

		$this->assertInstanceOf('EllisLab\AutoloaderTest\TestFileFour', $test);
	}

	public function testSingleton()
	{
		$one = Autoloader::getInstance();
		$two = Autoloader::getInstance();
		$three = Autoloader::getInstance();

		$this->assertSame($one, $two);
		$this->assertSame($two, $three);
		$this->assertSame($one, $three);
	}
}

// EOF
