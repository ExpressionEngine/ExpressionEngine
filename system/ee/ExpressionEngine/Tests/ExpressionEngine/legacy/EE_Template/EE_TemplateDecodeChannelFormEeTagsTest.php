<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateDecodeChannelFormEeTagsTest extends EE_TemplateTestBase
{
    private $reflectionMethod;

    public function setUp(): void
    {
        parent::setUp();

        // Get the private method using reflection
        $this->reflectionMethod = new \ReflectionMethod($this->template, 'decode_channel_form_ee_tags');
        $this->reflectionMethod->setAccessible(true);
    }

    public function testDecodeChannelFormEeTagsBasicReplacement()
    {
        $input = 'Some text {!-- ra:abc123 --} and CFORM-ENCODE-LEFT-BRACKETexp:channel:entriesCFORM-ENCODE-RIGHT-BRACKET more text';
        $expected = 'Some text {!-- ra:abc123 --} and {exp:channel:entries} more text';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $this->assertEquals($expected, $result);
    }

    public function testDecodeChannelFormEeTagsMultipleReplacements()
    {
        $input = 'CFORM-ENCODE-LEFT-BRACKETexp:channel:entriesCFORM-ENCODE-RIGHT-BRACKET and CFORM-ENCODE-LEFT-BRACKETexp:member:loginCFORM-ENCODE-RIGHT-BRACKET';
        $expected = '{exp:channel:entries} and {exp:member:login}';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $this->assertEquals($expected, $result);
    }

    public function testDecodeChannelFormEeTagsNoEncodedTags()
    {
        $input = 'Normal template content {exp:channel:entries} without encoded tags';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // Should return unchanged
        $this->assertEquals($input, $result);
    }

    public function testDecodeChannelFormEeTagsEmptyString()
    {
        $input = '';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $this->assertEquals('', $result);
    }

    public function testDecodeChannelFormEeTagsEmptyStringInput()
    {
        $input = '';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // str_replace with empty string returns an empty string
        $this->assertEquals('', $result);
    }

    public function testDecodeChannelFormEeTagsComplexTemplate()
    {
        $input = <<<TEMPLATE
{layout="site/layout"}
CFORM-ENCODE-LEFT-BRACKETexp:channel:entries channel="news" limit="10"CFORM-ENCODE-RIGHT-BRACKET
    <h2>{title}</h2>
    {summary}
    CFORM-ENCODE-LEFT-BRACKETexp:channel:categoriesCFORM-ENCODE-RIGHT-BRACKET
        <a href="{path}">{category_name}</a>
    CFORM-ENCODE-LEFT-BRACKET/exp:channel:categoriesCFORM-ENCODE-RIGHT-BRACKET
{/exp:channel:entries}
TEMPLATE;

        $expected = <<<TEMPLATE
{layout="site/layout"}
{exp:channel:entries channel="news" limit="10"}
    <h2>{title}</h2>
    {summary}
    {exp:channel:categories}
        <a href="{path}">{category_name}</a>
    {/exp:channel:categories}
{/exp:channel:entries}
TEMPLATE;

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $this->assertEquals($expected, $result);
    }

    public function testDecodeChannelFormEeTagsPartialMatches()
    {
        $input = 'Text with CFORM-ENCODE-LEFT but not complete and CFORM-ENCODE-RIGHT also incomplete';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // Should not replace partial matches
        $this->assertEquals($input, $result);
        $this->assertStringContainsString('CFORM-ENCODE-LEFT', $result);
        $this->assertStringContainsString('CFORM-ENCODE-RIGHT', $result);
    }

    public function testDecodeChannelFormEeTagsWithSpecialCharacters()
    {
        $input = 'Template with CFORM-ENCODE-LEFT-BRACKETexp:search:results search="test & query"CFORM-ENCODE-RIGHT-BRACKET and symbols <>&"';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $expected = 'Template with {exp:search:results search="test & query"} and symbols <>&"';
        $this->assertEquals($expected, $result);
    }

    public function testDecodeChannelFormEeTagsWithLineBreaks()
    {
        $input = "Line 1\nCFORM-ENCODE-LEFT-BRACKETexp:channel:entriesCFORM-ENCODE-RIGHT-BRACKET\nLine 3";

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $expected = "Line 1\n{exp:channel:entries}\nLine 3";
        $this->assertEquals($expected, $result);
    }

    public function testDecodeChannelFormEeTagsWithUnicode()
    {
        $input = 'Template with CFORM-ENCODE-LEFT-BRACKETexp:channel:entries channel="测试"CFORM-ENCODE-RIGHT-BRACKET and emoji 🚀';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $expected = 'Template with {exp:channel:entries channel="测试"} and emoji 🚀';
        $this->assertEquals($expected, $result);
    }

    public function testDecodeChannelFormEeTagsNestedTags()
    {
        $input = 'CFORM-ENCODE-LEFT-BRACKETexp:channel:entriesCFORM-ENCODE-RIGHT-BRACKETCFORM-ENCODE-LEFT-BRACKETexp:member:loginCFORM-ENCODE-RIGHT-BRACKETCFORM-ENCODE-LEFT-BRACKET/exp:channel:entriesCFORM-ENCODE-RIGHT-BRACKET';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $expected = '{exp:channel:entries}{exp:member:login}{/exp:channel:entries}';
        $this->assertEquals($expected, $result);
    }

    public function testDecodeChannelFormEeTagsPerformance()
    {
        // Create a large template with many encoded tags
        $parts = [];
        for ($i = 0; $i < 100; $i++) {
            $parts[] = "CFORM-ENCODE-LEFT-BRACKETexp:channel:entries entry_id=\"$i\"CFORM-ENCODE-RIGHT-BRACKET";
            $parts[] = "<h2>Title $i</h2>";
        }
        $input = implode("\n", $parts);

        $startTime = microtime(true);
        $result = $this->reflectionMethod->invoke($this->template, $input);
        $endTime = microtime(true);

        // Should complete quickly (< 0.1 seconds)
        $this->assertLessThan(0.1, $endTime - $startTime);

        // Should have replaced all encoded tags
        $this->assertStringNotContainsString('CFORM-ENCODE-LEFT-BRACKET', $result);
        $this->assertStringNotContainsString('CFORM-ENCODE-RIGHT-BRACKET', $result);
        $this->assertStringContainsString('{exp:channel:entries entry_id="0"}', $result);
        $this->assertStringContainsString('{exp:channel:entries entry_id="99"}', $result);
    }

    public function testDecodeChannelFormEeTagsReturnsString()
    {
        $input = 'Simple CFORM-ENCODE-LEFT-BRACKETtestCFORM-ENCODE-RIGHT-BRACKET string';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $this->assertIsString($result);
        $this->assertEquals('Simple {test} string', $result);
    }


    public function testDecodeChannelFormEeTagsMalformedTags()
    {
        // Test with malformed encoded tags
        $inputs = [
            'CFORM-ENCODE-LEFT-BRACKETmissing-right-bracket',
            'missing-leftCFORM-ENCODE-RIGHT-BRACKET',
            'CFORM-ENCODE-LEFT-BRACKETCFORM-ENCODE-RIGHT-BRACKET', // Empty tag
            'CFORM-ENCODE-LEFT-BRACKETincomplete-tag',
            'incompleteCFORM-ENCODE-RIGHT-BRACKET',
            'CFORM-ENCODE-LEFT-BRACKETtag with spacesCFORM-ENCODE-RIGHT-BRACKET',
            'CFORM-ENCODE-LEFT-BRACKETtag' . "\n" . 'CFORM-ENCODE-RIGHT-BRACKET', // With newline
        ];

        foreach ($inputs as $input) {
            $result = $this->reflectionMethod->invoke($this->template, $input);

            // Should handle malformed tags gracefully (str_replace should still work)
            $this->assertIsString($result);

            // Should not crash or throw exceptions
            // Malformed tags should either be replaced or left as-is
        }
    }

    public function testDecodeChannelFormEeTagsIncompleteTags()
    {
        // Test with incomplete encoded tags (missing parts)
        $inputs = [
            'CFORM-ENCODE-LEFT-BRACKET', // Only left part
            'CFORM-ENCODE-RIGHT-BRACKET', // Only right part
            'CFORM-ENCODE-', // Partial constant
            '-ENCODE-LEFT-BRACKET', // Missing start
            'CFORM-ENCODE-LEFT-BRACKETincomplete', // No closing
            'incompleteCFORM-ENCODE-RIGHT-BRACKET', // No opening
        ];

        foreach ($inputs as $input) {
            $result = $this->reflectionMethod->invoke($this->template, $input);

            // Should handle incomplete tags gracefully
            $this->assertIsString($result);
            // Incomplete tags should be left as-is or partially replaced
        }
    }

    public function testDecodeChannelFormEeTagsConflictingEncodings()
    {
        // Test with conflicting or overlapping encodings
        $input = 'CFORM-ENCODE-LEFT-BRACKETexp:channelCFORM-ENCODE-LEFT-BRACKET:entriesCFORM-ENCODE-RIGHT-BRACKETCFORM-ENCODE-RIGHT-BRACKET';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // Should handle nested/conflicting encodings
        $this->assertIsString($result);
        $this->assertStringNotContainsString('CFORM-ENCODE-LEFT-BRACKET', $result);
        $this->assertStringNotContainsString('CFORM-ENCODE-RIGHT-BRACKET', $result);
    }

    public function testDecodeChannelFormEeTagsWithNullBytes()
    {
        // Test with null bytes in the input
        $input = 'Before CFORM-ENCODE-LEFT-BRACKETexp:channel:entries' . "\x00" . 'CFORM-ENCODE-RIGHT-BRACKET after';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // Should handle null bytes gracefully
        $this->assertIsString($result);
        $this->assertStringContainsString('{exp:channel:entries', $result);
        $this->assertStringContainsString('}', $result);
    }
}
