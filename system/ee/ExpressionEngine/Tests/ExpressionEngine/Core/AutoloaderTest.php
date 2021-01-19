<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Core;

use ExpressionEngine\AutoloaderTest as TestAlias;
use ExpressionEngine\Core\Autoloader as Autoloader;
use PHPUnit\Framework\TestCase;

class AutoloaderTest extends TestCase
{
    private $autoloader;

    protected function setUp() : void
    {
        $this->autoloader = new Autoloader();

        // The testsuite autoloader technically handles the full ExpressionEngine
        // namespace, but we can take advantage of its simplicity and the fact
        // that it fails silently.
        // By missmatching the prefix and path name we can guarantee a silent
        // failure on the testsuite loader, thereby isolating the test to the
        // main autoloader.

        $this->autoloader->addPrefix('ExpressionEngine\AutoloaderTest', __DIR__.'/AutoloaderFixture');
    }

    protected function tearDown() : void
    {
        $this->autoloader = null;
    }

    public function testLoadClass()
    {
        $this->autoloader->loadClass('ExpressionEngine\AutoloaderTest\TestFileOne');
        $this->assertTrue(class_exists('\TestFileOne'), 'loadClass(): file without namespacing');

        $this->autoloader->loadClass('ExpressionEngine\AutoloaderTest\TestFileTwo');
        $this->assertTrue(class_exists('\ExpressionEngine\AutoloaderTest\TestFileTwo'), 'class file with namespacing');
    }

    public function testRegister()
    {
        $this->autoloader->register();
        $test = new \ExpressionEngine\AutoloaderTest\TestFileThree();
        $this->autoloader->unregister();

        $this->assertInstanceOf('ExpressionEngine\AutoloaderTest\TestFileThree', $test);
    }

    public function testLoadClassHandlesAutomaticallyResolvedAlias()
    {
        $this->autoloader->register();
        $test = new TestAlias\TestFileFour();
        $this->autoloader->unregister();

        $this->assertInstanceOf('ExpressionEngine\AutoloaderTest\TestFileFour', $test);
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
