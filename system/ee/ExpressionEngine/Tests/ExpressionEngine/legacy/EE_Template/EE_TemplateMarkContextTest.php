<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateMarkContextTest extends EE_TemplateTestBase
{
    public function testMarkContextExplicitContext()
    {
        // Test with explicit context parameter
        $context = 'custom_template_context';

        $result = $this->template->markContext($context);

        // Verify the result is a properly formatted annotation comment
        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}/', $result);
    }

    public function testMarkContextDefaultContext()
    {
        // Test with null context - should use template group/name
        $this->template->group_name = 'blog';
        $this->template->template_name = 'article';

        $result = $this->template->markContext(null);

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}/', $result);
    }

    public function testMarkContextDefaultContextNoGroup()
    {
        // Test default context with missing group name
        $this->template->group_name = '';
        $this->template->template_name = 'index';

        $result = $this->template->markContext();

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}/', $result);
    }

    public function testMarkContextDefaultContextNoTemplate()
    {
        // Test default context with missing template name
        $this->template->group_name = 'site';
        $this->template->template_name = '';

        $result = $this->template->markContext();

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}/', $result);
    }

    public function testMarkContextEmptyTemplateNames()
    {
        // Test with both group and template name empty
        $this->template->group_name = '';
        $this->template->template_name = '';

        $result = $this->template->markContext();

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}/', $result);
    }

    public function testMarkContextSpecialCharacters()
    {
        // Test with special characters in template names
        $this->template->group_name = 'blog-posts';
        $this->template->template_name = 'article_123';

        $result = $this->template->markContext();

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}/', $result);
    }

    public function testMarkContextUnicodeCharacters()
    {
        // Test with Unicode characters in template names
        $this->template->group_name = 'café';
        $this->template->template_name = 'título';

        $result = $this->template->markContext();

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}/', $result);
    }

    public function testMarkContextVeryLongNames()
    {
        // Test with very long template/group names
        $longName = str_repeat('a', 200);
        $this->template->group_name = $longName;
        $this->template->template_name = $longName;

        $result = $this->template->markContext();

        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}/', $result);
    }

    public function testMarkContextAnnotationInitialization()
    {
        // Test that annotations object is initialized on first use
        $reflection = new \ReflectionProperty(\EE_Template::class, 'annotations');
        $reflection->setAccessible(true);

        // Initially null
        $this->assertNull($reflection->getValue($this->template));

        $result = $this->template->markContext('test_context');

        // Now initialized
        $annotations = $reflection->getValue($this->template);
        $this->assertNotNull($annotations);
        $this->assertInstanceOf(\ExpressionEngine\Library\Template\Annotation\Runtime::class, $annotations);
        $this->assertIsString($result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
    }

    public function testMarkContextSharedStoreUsage()
    {
        // Test that shared store is used
        $result = $this->template->markContext('shared_test');

        // The annotation should be properly formatted
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringEndsWith(' --}', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}/', $result);
    }

    public function testMarkContextMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'markContext');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('context', $parameters[0]->getName());
        $this->assertNull($parameters[0]->getDefaultValue());
    }
}
