<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateWrapContextAnnotationsTest extends EE_TemplateTestBase
{
    public function testWrapInContextAnnotationsSingleLineNoConditionals()
    {
        // Test single line content without conditionals - should not add annotations
        $content = '{title} - Single line content';
        $var_context = 'variable_context';
        $current_context = 'current_template';

        $result = $this->template->wrapInContextAnnotations($content, $var_context, $current_context);

        $this->assertEquals($content, $result);
    }

    public function testWrapInContextAnnotationsMultiLineNoConditionals()
    {
        // Test multi-line content without conditionals - should add end annotation only
        $content = "{title}\n{subtitle}\n{author}";
        $var_context = 'variable_context';
        $current_context = 'current_template';

        $result = $this->template->wrapInContextAnnotations($content, $var_context, $current_context);

        // Should return content with end annotation only (no conditionals)
        $this->assertStringStartsWith($content, $result);
        $this->assertStringContainsString('{!-- ra:', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}$/', $result);
    }

    public function testWrapInContextAnnotationsHasConditionals()
    {
        // Test content with conditionals - should add both start and end annotations
        $content = "{if logged_in}\nWelcome {username}\n{if:else}\nPlease login\n{/if}";
        $var_context = 'conditional_context';
        $current_context = 'template_context';

        $result = $this->template->wrapInContextAnnotations($content, $var_context, $current_context);

        // Should return content with both start and end annotations (has conditionals)
        $this->assertStringContainsString('{if logged_in}', $result);
        $this->assertStringContainsString('Welcome {username}', $result);
        $this->assertStringContainsString('Please login', $result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringContainsString('{!-- ra:', $result);
        $this->assertEquals(substr_count($result, '{!-- ra:'), 2); // Should have 2 annotations
    }

    public function testWrapInContextAnnotationsComplexConditionals()
    {
        // Test complex nested conditionals
        $content = "{if segment_1 == 'blog'}\n{if logged_in}\n{title}\n{author}\n{/if}\n{if:else}\n{title}\n{/if}\n{/if}";
        $var_context = 'blog_context';
        $current_context = 'page_context';

        $expected = $this->template->markContext($var_context)
            . $content
            . $this->template->markContext($current_context);

        $result = $this->template->wrapInContextAnnotations($content, $var_context, $current_context);

        // Check that result contains expected content and proper annotation format
        $this->assertStringContainsString('{if segment_1 == \'blog\'}', $result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringContainsString('{!-- ra:', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}$/', $result);
    }

    public function testWrapInContextAnnotationsEmptyContent()
    {
        // Test empty content
        $content = '';
        $var_context = 'empty_context';
        $current_context = 'template_context';

        $result = $this->template->wrapInContextAnnotations($content, $var_context, $current_context);

        $this->assertEquals($content, $result);
    }

    public function testWrapInContextAnnotationsNullCurrentContext()
    {
        // Test null current context - should use default template context
        $content = "{if show_sidebar}\n{sidebar}\n{/if}";
        $var_context = 'sidebar_context';
        $current_context = null;

        // Set template metadata for default context
        $this->template->group_name = 'test_group';
        $this->template->template_name = 'test_template';

        $expected = $this->template->markContext($var_context)
            . $content
            . $this->template->markContext($current_context);

        $result = $this->template->wrapInContextAnnotations($content, $var_context, $current_context);

        // Check that result contains expected content and proper annotation format
        $this->assertStringContainsString('{if show_sidebar}', $result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringContainsString('{!-- ra:', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}$/', $result);
    }

    public function testWrapInContextAnnotationsConditionalsInSingleLine()
    {
        // Test conditionals in single line (edge case)
        $content = "{if logged_in}Welcome{/if}";
        $var_context = 'inline_context';
        $current_context = 'template_context';

        $expected = $this->template->markContext($var_context)
            . $content
            . $this->template->markContext($current_context);

        $result = $this->template->wrapInContextAnnotations($content, $var_context, $current_context);

        // Check that result contains expected content and proper annotation format
        $this->assertStringContainsString('{if logged_in}', $result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringContainsString('{!-- ra:', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}$/', $result);
    }

    public function testWrapInContextAnnotationsMalformedConditionals()
    {
        // Test malformed conditionals (missing closing braces, etc.)
        $content = "{if logged_in\n{incomplete_conditional}\n{title}";
        $var_context = 'malformed_context';
        $current_context = 'template_context';

        // Should still detect the presence of {if and add annotations
        $expected = $this->template->markContext($var_context)
            . $content
            . $this->template->markContext($current_context);

        $result = $this->template->wrapInContextAnnotations($content, $var_context, $current_context);

        // Check that result contains expected content and proper annotation format
        $this->assertStringContainsString('{if logged_in', $result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringContainsString('{!-- ra:', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}$/', $result);
    }

    public function testWrapInContextAnnotationsLargeContent()
    {
        // Test performance with large content
        $largeContent = str_repeat("{variable}\n", 1000) . "{if condition}\n" . str_repeat("{content}\n", 1000) . "{/if}";
        $var_context = 'large_context';
        $current_context = 'template_context';

        $expected = $this->template->markContext($var_context)
            . $largeContent
            . $this->template->markContext($current_context);

        $result = $this->template->wrapInContextAnnotations($largeContent, $var_context, $current_context);

        // Check that result contains expected content and proper annotation format
        $this->assertStringContainsString('{if condition}', $result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringContainsString('{!-- ra:', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}$/', $result);
        $this->assertStringContainsString('{if condition}', $result);
    }

    public function testWrapInContextAnnotationsSpecialCharacters()
    {
        // Test with Unicode and special characters
        $content = "🚀 {if user_lang == 'es'}Hola{elseif user_lang == 'fr'}Bonjour{if:else}Hello{/if} 🌟";
        $var_context = 'unicode_context';
        $current_context = 'template_context';

        $expected = $this->template->markContext($var_context)
            . $content
            . $this->template->markContext($current_context);

        $result = $this->template->wrapInContextAnnotations($content, $var_context, $current_context);

        // Check that result contains expected content and proper annotation format
        $this->assertStringContainsString('{if user_lang == \'es\'}', $result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringContainsString('{!-- ra:', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}$/', $result);
        $this->assertStringContainsString('🚀', $result);
        $this->assertStringContainsString('🌟', $result);
    }

    public function testWrapInContextAnnotationsUnicodeNormalization()
    {
        // Test with different Unicode normalization forms
        $content = "café"; // NFC normalized
        $content .= "\u{0065}\u{0301}"; // NFD: e + combining acute accent

        $var_context = 'normalized_context';
        $current_context = 'template_context';

        $result = $this->template->wrapInContextAnnotations($content, $var_context, $current_context);

        // Should handle normalization differences properly
        $this->assertIsString($result);
        $this->assertStringContainsString('café', $result);
    }

    public function testWrapInContextAnnotationsComplexUnicode()
    {
        // Test with complex Unicode characters and emoji
        $content = "🚀 Hello 世界 🌟 {if user_logged_in}👋{/if}";
        $var_context = 'unicode_complex';
        $current_context = 'template_context';

        $expected = $this->template->markContext($var_context)
            . $content
            . $this->template->markContext($current_context);

        $result = $this->template->wrapInContextAnnotations($content, $var_context, $current_context);

        // Check that result contains expected content and proper annotation format
        $this->assertStringContainsString('{if user_logged_in}', $result);
        $this->assertStringStartsWith('{!-- ra:', $result);
        $this->assertStringContainsString('{!-- ra:', $result);
        $this->assertMatchesRegularExpression('/\{!-- ra:[a-f0-9]+ --\}$/', $result);
        $this->assertStringContainsString('🚀', $result);
        $this->assertStringContainsString('世界', $result);
        $this->assertStringContainsString('🌟', $result);
        $this->assertStringContainsString('👋', $result);
    }

    public function testWrapInContextAnnotationsUnicodeBoundaries()
    {
        // Test Unicode character boundaries and edge cases
        $content = "\u{0000}\u{FFFF}\u{10000}\u{10FFFF}"; // Unicode boundaries
        $var_context = 'boundary_test';
        $current_context = 'template_context';

        $result = $this->template->wrapInContextAnnotations($content, $var_context, $current_context);

        // Should handle Unicode boundaries without corruption
        $this->assertIsString($result);
        $this->assertStringContainsString($content, $result);
    }

    public function testWrapInContextAnnotationsMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'wrapInContextAnnotations');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('var_content', $parameters[0]->getName());
        $this->assertEquals('var_context', $parameters[1]->getName());
        $this->assertEquals('current_context', $parameters[2]->getName());
        $this->assertNull($parameters[2]->getDefaultValue());
    }
}
