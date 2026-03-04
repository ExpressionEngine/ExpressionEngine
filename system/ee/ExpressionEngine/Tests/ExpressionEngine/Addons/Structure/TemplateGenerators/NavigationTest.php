<?php

require_once __DIR__ . '/../../../../../Addons/structure/TemplateGenerators/Navigation.php';

use ExpressionEngine\Structure\TemplateGenerators\Navigation;
use PHPUnit\Framework\TestCase;

class NavigationTest extends TestCase
{
    public function testGetVariablesReturnsEmptyArray()
    {
        $generator = new Navigation();

        $this->assertSame([], $generator->getVariables());
    }
}
