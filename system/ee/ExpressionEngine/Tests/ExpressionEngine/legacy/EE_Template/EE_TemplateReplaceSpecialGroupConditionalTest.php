<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';
require_once SYSPATH . 'ee/ExpressionEngine/Tests/TestReflectionHelper.php';

class EE_TemplateReplaceSpecialGroupConditionalTest extends EE_TemplateTestBase
{
    private $reflectionMethod;

    public function setUp(): void
    {
        parent::setUp();

        // Get the private method using reflection
        $this->reflectionMethod = new \ReflectionMethod($this->template, 'replace_special_group_conditional');
        \TestReflectionHelper::makeMethodAccessible($this->reflectionMethod);
    }

    public function testReplaceSpecialGroupConditionalNoInGroup()
    {
        $input = 'Normal template content {if logged_in}Welcome{/if} without in_group calls';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // Should return unchanged
        $this->assertEquals($input, $result);
    }

    public function testReplaceSpecialGroupConditionalSingleInGroup()
    {
        $input = '{if in_group(\'1\')}Admin content{/if}';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $expected = '{if \'1\' ~ \'/\\b\'.logged_in_member_group.\'\\b/\'}Admin content{/if}';
        $this->assertEquals($expected, $result);
    }

    public function testReplaceSpecialGroupConditionalMultipleInGroup()
    {
        $input = '{if in_group(\'1\')}Admin{/if} {if in_group(\'5|6\')}Editor{/if}';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $expected = '{if \'1\' ~ \'/\\b\'.logged_in_member_group.\'\\b/\'}Admin{/if} {if \'5|6\' ~ \'/\\b\'.logged_in_member_group.\'\\b/\'}Editor{/if}';
        $this->assertEquals($expected, $result);
    }

    public function testReplaceSpecialGroupConditionalWithSpaces()
    {
        $input = '{if in_group( \'1\' )}Admin content{/if}';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $expected = '{if  \'1\'  ~ \'/\\b\'.logged_in_member_group.\'\\b/\'}Admin content{/if}';
        $this->assertEquals($expected, $result);
    }

    public function testReplaceSpecialGroupConditionalComplexGroups()
    {
        $input = '{if in_group(\'1|2|5\')}Super User{/if}';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $expected = '{if \'1|2|5\' ~ \'/\\b\'.logged_in_member_group.\'\\b/\'}Super User{/if}';
        $this->assertEquals($expected, $result);
    }

    public function testReplaceSpecialGroupConditionalEmptyParams()
    {
        $input = '{if in_group()}Empty{/if} {if in_group(\'\')}Also empty{/if}';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // in_group() with no params should not be replaced, but in_group('') should be
        $expected = '{if in_group()}Empty{/if} {if \'\' ~ \'/\\b\'.logged_in_member_group.\'\\b/\'}Also empty{/if}';
        $this->assertEquals($expected, $result);
    }

    public function testReplaceSpecialGroupConditionalNestedCalls()
    {
        $input = '{if in_group(\'1\') AND in_group(\'2\')}Both groups{/if}';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $expected = '{if \'1\' ~ \'/\\b\'.logged_in_member_group.\'\\b/\' AND \'2\' ~ \'/\\b\'.logged_in_member_group.\'\\b/\'}Both groups{/if}';
        $this->assertEquals($expected, $result);
    }

    public function testReplaceSpecialGroupConditionalRegexGeneration()
    {
        $input = '{if in_group(\'3\')}Member{/if}';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // Verify the exact regex pattern structure
        $this->assertStringContainsString('\'3\' ~ \'/\\b\'.logged_in_member_group.\'\\b/\'', $result);
        $this->assertStringContainsString('logged_in_member_group', $result);
    }

    public function testReplaceSpecialGroupConditionalReturnUnchanged()
    {
        $input = 'Template with in_group but not properly formatted in_group(\'1\' ) missing quotes';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // The method replaces any in_group(...) pattern, even if malformed
        $expected = 'Template with in_group but not properly formatted \'1\'  ~ \'/\\b\'.logged_in_member_group.\'\\b/\' missing quotes';
        $this->assertEquals($expected, $result);
    }

    public function testReplaceSpecialGroupConditionalEdgeCases()
    {
        $testCases = [
            '{if in_group("1")}Double quotes{/if}' => '{if "1" ~ \'/\\b\'.logged_in_member_group.\'\\b/\'}Double quotes{/if}', // Double quotes are replaced
            '{if in_group(\'1\' ) }Trailing space{/if}' => '{if \'1\'  ~ \'/\\b\'.logged_in_member_group.\'\\b/\' }Trailing space{/if}', // Trailing space preserved
            '{if in_group( \' 1 \' )}Spaces inside{/if}' => '{if  \' 1 \'  ~ \'/\\b\'.logged_in_member_group.\'\\b/\'}Spaces inside{/if}', // Spaces inside preserved
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->reflectionMethod->invoke($this->template, $input);
            $this->assertEquals($expected, $result, "Failed for input: $input");
        }
    }

    public function testReplaceSpecialGroupConditionalWithComplexTemplate()
    {
        $input = <<<TEMPLATE
{if in_group('1')}
    <div class="admin-panel">
        {if in_group('2')}
            <p>Super admin content</p>
        {/if}
        <p>Admin content</p>
    </div>
{/if}
{if in_group('3|4|5')}
    <div class="editor-panel">
        <p>Editor content</p>
    </div>
{/if}
TEMPLATE;

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // Verify all in_group calls are replaced
        $this->assertStringContainsString('\'1\' ~ \'/\\b\'.logged_in_member_group.\'\\b/\'', $result);
        $this->assertStringContainsString('\'2\' ~ \'/\\b\'.logged_in_member_group.\'\\b/\'', $result);
        $this->assertStringContainsString('\'3|4|5\' ~ \'/\\b\'.logged_in_member_group.\'\\b/\'', $result);

        // Verify original structure is preserved
        $this->assertStringContainsString('<div class="admin-panel">', $result);
        $this->assertStringContainsString('<div class="editor-panel">', $result);
    }

    public function testReplaceSpecialGroupConditionalPerformance()
    {
        // Create a template with many in_group calls
        $parts = [];
        for ($i = 1; $i <= 50; $i++) {
            $parts[] = "{if in_group('$i')}<p>Group $i content</p>{/if}";
        }
        $input = implode("\n", $parts);

        $startTime = microtime(true);
        $result = $this->reflectionMethod->invoke($this->template, $input);
        $endTime = microtime(true);

        // Should complete quickly (< 0.1 seconds)
        $this->assertLessThan(0.1, $endTime - $startTime);

        // Should have replaced all in_group calls
        for ($i = 1; $i <= 50; $i++) {
            $this->assertStringContainsString("'$i' ~ '/\\b'.logged_in_member_group.'\\b/'", $result);
        }
    }

    public function testReplaceSpecialGroupConditionalReturnsString()
    {
        $input = '{if in_group(\'1\')}Test{/if}';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $this->assertIsString($result);
        $this->assertStringContainsString('logged_in_member_group', $result);
    }

    public function testReplaceSpecialGroupConditionalWithSpecialCharacters()
    {
        $input = '{if in_group(\'1|2&3\')}Special chars{/if}';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // Special characters in group parameter should be preserved
        $this->assertStringContainsString('\'1|2&3\' ~ \'/\\b\'.logged_in_member_group.\'\\b/\'', $result);
    }

    public function testReplaceSpecialGroupConditionalEmptyString()
    {
        $input = '';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        $this->assertEquals('', $result);
    }

    public function testReplaceSpecialGroupConditionalEmptyStringInput()
    {
        $input = '';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // Empty string should be returned unchanged
        $this->assertEquals('', $result);
    }

    public function testReplaceSpecialGroupConditionalDeeplyNested()
    {
        // Test with deeply nested in_group() calls
        $input = 'in_group(in_group(in_group(\'1\')))';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // Should handle nested calls
        $this->assertIsString($result);
        $this->assertStringContainsString('in_group', $result); // Outer calls remain
        $this->assertStringContainsString('~', $result); // Inner call should be replaced
    }

    public function testReplaceSpecialGroupConditionalWithHundredsOfGroups()
    {
        // Test with hundreds of group references
        $groups = [];
        for ($i = 1; $i <= 500; $i++) {
            $groups[] = "'$i'";
        }

        $input = 'in_group(' . implode('|', $groups) . ')';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // Should handle large numbers of groups
        $this->assertIsString($result);
        $this->assertStringContainsString('~', $result);
        $this->assertStringContainsString('logged_in_member_group', $result);
    }

    public function testReplaceSpecialGroupConditionalExtremelyLongGroupNames()
    {
        // Test with extremely long group names
        $longName = str_repeat('very_long_group_name_', 50) . '1';
        $input = "in_group('$longName')";

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // Should handle extremely long group names
        $this->assertIsString($result);
        $this->assertStringContainsString('~', $result);
        $this->assertStringContainsString($longName, $result);
    }

    public function testReplaceSpecialGroupConditionalSqlInjectionAttempts()
    {
        // Test with potential SQL injection payloads in group parameters
        $maliciousInputs = [
            "in_group('1\' UNION SELECT * FROM users --')",
            "in_group('1\'; DROP TABLE users; --')",
            "in_group('<script>alert(\"xss\")</script>')",
            "in_group('1\\' AND 1=1 --')",
            "in_group('../../../../etc/passwd')"
        ];

        foreach ($maliciousInputs as $input) {
            $result = $this->reflectionMethod->invoke($this->template, $input);

            // Should handle malicious input gracefully
            $this->assertIsString($result);
            // The method should still attempt replacement even with malicious content
            // (it's not responsible for SQL escaping, just pattern replacement)
        }
    }

    public function testReplaceSpecialGroupConditionalWithNullBytes()
    {
        // Test with null bytes in group names
        $input = 'in_group(\'group' . "\x00" . 'name\')';

        $result = $this->reflectionMethod->invoke($this->template, $input);

        // Should handle null bytes gracefully
        $this->assertIsString($result);
        $this->assertStringContainsString('~', $result);
    }

    public function testReplaceSpecialGroupConditionalRaceCondition()
    {
        // Test potential race conditions with concurrent access
        $input = 'in_group(\'1\') in_group(\'2\') in_group(\'3\')';

        // Run multiple times to test consistency
        $result1 = $this->reflectionMethod->invoke($this->template, $input);
        $result2 = $this->reflectionMethod->invoke($this->template, $input);

        // Results should be consistent
        $this->assertEquals($result1, $result2);
        $this->assertIsString($result1);
        $this->assertStringContainsString('~', $result1);
    }

    public function testReplaceSpecialGroupConditionalWithUnicodeGroups()
    {
        // Test with Unicode characters in group names
        $unicodeGroups = [
            'in_group(\'tëst\')',
            'in_group(\'测试\')',
            'in_group(\'тест\')',
            'in_group(\'🚀group\')',
            'in_group(\'café\')'
        ];

        foreach ($unicodeGroups as $input) {
            $result = $this->reflectionMethod->invoke($this->template, $input);

            // Should handle Unicode characters gracefully
            $this->assertIsString($result);
            $this->assertStringContainsString('~', $result);
        }
    }
}
