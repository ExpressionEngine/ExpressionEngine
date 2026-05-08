<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Controllers\Files;

use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once(APPPATH . 'core/Controller.php');
    }

    public function testRoutableMethods()
    {
        $controller_methods = array();

        foreach (get_class_methods('ExpressionEngine\Controller\Files\File') as $method) {
            $method = strtolower($method);
            if (strncmp($method, '_', 1) != 0) {
                $controller_methods[] = $method;
            }
        }

        sort($controller_methods);

        $this->assertEquals(['download', 'getuploadlocationsanddirectoriesdropdownchoices', 'view'], $controller_methods);
    }

    public function testUsableImagePropertiesRequiresReadableDimensions()
    {
        $this->assertTrue($this->hasUsableImageProperties(['width' => 120, 'height' => 80]));
        $this->assertTrue($this->hasUsableImageProperties(['width' => '120', 'height' => '80']));

        $this->assertFalse($this->hasUsableImageProperties(false));
        $this->assertFalse($this->hasUsableImageProperties([]));
        $this->assertFalse($this->hasUsableImageProperties(['width' => 120]));
        $this->assertFalse($this->hasUsableImageProperties(['width' => 120, 'height' => 0]));
        $this->assertFalse($this->hasUsableImageProperties(['width' => 'bad', 'height' => 80]));
    }

    private function hasUsableImageProperties($info)
    {
        $reflection = new \ReflectionClass('ExpressionEngine\Controller\Files\File');
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('hasUsableImageProperties');
        \TestReflectionHelper::makeMethodAccessible($method);

        return $method->invoke($controller, $info);
    }
}
