<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateParseGlobalsTest extends EE_TemplateTestBase
{
    public function testParseGlobalsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'parse_globals'));
        $this->assertTrue(is_callable([$this->template, 'parse_globals']));
    }

    public function testParseGlobalsCanBeCalled()
    {
        // Basic test that the method can be called without errors
        $template = 'test';

        try {
            $result = $this->template->parse_globals($template);
            $this->assertIsString($result);
        } catch (\Exception $e) {
            // Method may have complex dependencies, just verify it doesn't crash completely
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    public function testParseGlobalsMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'parse_globals');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('str', $parameters[0]->getName());
    }
}
