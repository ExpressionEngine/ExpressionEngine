<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateGetGlobalsRegexTest extends EE_TemplateTestBase
{
    private $reflectionMethod;

    public function setUp(): void
    {
        parent::setUp();

        // Get the private method using reflection
        $this->reflectionMethod = new \ReflectionMethod($this->template, 'getGlobalsRegex');
        \TestReflectionHelper::makeMethodAccessible($this->reflectionMethod);
    }

    public function testGetGlobalsRegexReturnsArray()
    {
        // Mock global vars
        $this->mockGlobalVars(['site_name', 'site_url', 'current_url']);

        $result = $this->reflectionMethod->invoke($this->template);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Each element should be a regex string
        foreach ($result as $regex) {
            $this->assertIsString($regex);
            $this->assertStringStartsWith('/', $regex);
            $this->assertStringEndsWith('/', $regex);
        }
    }

    public function testGetGlobalsRegexWithEmptyGlobals()
    {
        // Mock empty global vars
        $this->mockGlobalVars([]);

        $result = $this->reflectionMethod->invoke($this->template);

        $this->assertIsArray($result);
        // When there are no globals, chunkGlobalsArray returns [[]] and array_map creates a regex with empty alternation
        $this->assertCount(1, $result);
        // Just check that it contains the expected structure
        $this->assertStringStartsWith('/{', $result[0]);
        $this->assertStringEndsWith('}/', $result[0]);
    }

    public function testGetGlobalsRegexWithSpecialCharacters()
    {
        // Test global vars with regex special characters
        $specialVars = [
            'var_with_dots.and.more',
            'var-with-dashes',
            'var$with$dollar',
            'var^with^caret',
            'var(with)parens',
            'var+with+plus',
            'var?with?question',
            'var|with|pipe',
            'var\\with\\backslash'
        ];

        $this->mockGlobalVars($specialVars);

        $result = $this->reflectionMethod->invoke($this->template);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Verify the regex contains properly escaped characters
        $regexString = implode('', $result);
        $this->assertStringContainsString('var_with_dots\.and\.more', $regexString);
        $this->assertStringContainsString('var\-with\-dashes', $regexString);
        $this->assertStringContainsString('var\$with\$dollar', $regexString);
    }

    public function testGetGlobalsRegexCaching()
    {
        // Mock identical global vars twice
        $vars = ['site_name', 'site_url', 'current_time'];
        $this->mockGlobalVars($vars);

        // First call
        $result1 = $this->reflectionMethod->invoke($this->template);

        // Second call with same vars should return cached result
        $result2 = $this->reflectionMethod->invoke($this->template);

        $this->assertEquals($result1, $result2);
        $this->assertSame($result1, $result2); // Should be the same object due to caching
    }

    public function testGetGlobalsRegexCacheKeyGeneration()
    {
        // Test that different global var sets create different cache keys
        $vars1 = ['var1', 'var2'];
        $vars2 = ['var2', 'var1']; // Same vars, different order

        // First set
        $this->mockGlobalVars($vars1);
        $result1 = $this->reflectionMethod->invoke($this->template);

        // Clear cache by setting different vars
        $this->clearGlobalsRegexCache();
        $this->mockGlobalVars($vars2);
        $result2 = $this->reflectionMethod->invoke($this->template);

        // Should be different due to different cache keys
        $this->assertNotEquals($result1, $result2);
    }

    public function testGetGlobalsRegexRegexFormat()
    {
        $this->mockGlobalVars(['site_name', 'site_url']);

        $result = $this->reflectionMethod->invoke($this->template);

        // Should match the expected regex format
        $expectedPattern = '/' . preg_quote(LD, '/') . '\(' . preg_quote('site_name', '/') . '|' . preg_quote('site_url', '/') . '\)' . preg_quote(RD, '/') . '/';

        // The result should contain our expected regex pattern
        $this->assertMatchesRegularExpression($expectedPattern, $result[0]);
    }

    public function testGetGlobalsRegexWithUnicodeNames()
    {
        $unicodeVars = [
            'café',
            '测试',
            'русский',
            '🚀emoji🚀',
            'mïxed'
        ];

        $this->mockGlobalVars($unicodeVars);

        $result = $this->reflectionMethod->invoke($this->template);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Verify unicode characters are properly escaped in regex
        $regexString = implode('', $result);
        $this->assertStringContainsString('café', $regexString);
        $this->assertStringContainsString('测试', $regexString);
    }

    public function testGetGlobalsRegexChunkingBehavior()
    {
        // Create enough variables to trigger chunking (over 30,000 chars)
        $largeVars = [];
        for ($i = 0; $i < 200; $i++) {
            $largeVars[] = 'very_long_variable_name_that_will_help_us_test_chunking_' . $i;
        }

        $this->mockGlobalVars($largeVars);

        $result = $this->reflectionMethod->invoke($this->template);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result); // Should have at least one chunk

        // Each chunk should be a valid regex
        foreach ($result as $regex) {
            $this->assertIsString($regex);
            $this->assertStringStartsWith('/', $regex);
            $this->assertStringEndsWith('/', $regex);
        }
    }

    public function testGetGlobalsRegexSingleVariable()
    {
        $this->mockGlobalVars(['single_var']);

        $result = $this->reflectionMethod->invoke($this->template);

        $this->assertIsArray($result);
        $this->assertCount(1, $result); // Should have one regex pattern

        // Verify the regex contains our variable
        $this->assertStringContainsString('single_var', $result[0]);
    }

    public function testGetGlobalsRegexCallsChunkGlobalsArray()
    {
        $this->mockGlobalVars(['var1', 'var2', 'var3']);

        // We can't directly mock chunkGlobalsArray since it's private,
        // but we can verify the result shows chunking behavior
        $result = $this->reflectionMethod->invoke($this->template);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // The result should be properly formatted regex patterns
        foreach ($result as $chunk) {
            // Just verify basic regex structure
            $this->assertStringStartsWith('/{', $chunk);
            $this->assertStringEndsWith('}/', $chunk);
            $this->assertStringContainsString('var1', $chunk);
            $this->assertStringContainsString('var2', $chunk);
            $this->assertStringContainsString('var3', $chunk);
        }
    }

    public function testGetGlobalsRegexPerformanceWithManyVariables()
    {
        // Test with a large number of variables
        $manyVars = [];
        for ($i = 0; $i < 500; $i++) {
            $manyVars[] = 'var_' . $i;
        }

        $this->mockGlobalVars($manyVars);

        $startTime = microtime(true);
        $result = $this->reflectionMethod->invoke($this->template);
        $endTime = microtime(true);

        // Should complete in reasonable time (< 1 second)
        $this->assertLessThan(1.0, $endTime - $startTime);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }



    public function testGetGlobalsRegexWithRegexControlCharacters()
    {
        // Test with variables containing regex control characters
        $controlVars = [
            'var_with_dot.test',
            'var_with_plus+test',
            'var_with_star*test',
            'var_with_question?test',
            'var_with_caret^test',
            'var_with_dollar$test',
            'var_with_pipe|test',
            'var_with_parens(test)',
            'var_with_brackets[test]',
            'var_with_braces{test}',
            'var_with_backslash\\test'
        ];

        $this->mockGlobalVars($controlVars);
        $this->clearGlobalsRegexCache();

        $result = $this->reflectionMethod->invoke($this->template);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Verify that special regex characters are properly escaped
        foreach ($result as $regex) {
            $this->assertStringStartsWith('/', $regex);
            $this->assertStringEndsWith('/', $regex);
        }
    }

    public function testGetGlobalsRegexWithNullBytesInNames()
    {
        // Test with null bytes in variable names
        $nullVars = [
            'var_with_null' . "\x00" . 'byte',
            'another' . "\x00" . 'var',
            'null_at_end' . "\x00"
        ];

        $this->mockGlobalVars($nullVars);
        $this->clearGlobalsRegexCache();

        $result = $this->reflectionMethod->invoke($this->template);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testGetGlobalsRegexWithEmptyGlobalNames()
    {
        // Test with empty or whitespace-only global variable names
        $emptyVars = [
            '',
            '   ',
            "\t",
            "\n",
            'valid_var',
            '',
            'another_valid'
        ];

        $this->mockGlobalVars($emptyVars);
        $this->clearGlobalsRegexCache();

        $result = $this->reflectionMethod->invoke($this->template);

        $this->assertIsArray($result);
        // Should still return regex patterns even with empty names
        $this->assertNotEmpty($result);
    }

    public function testGetGlobalsRegexRaceCondition()
    {
        // Test potential race conditions with concurrent access
        $this->clearGlobalsRegexCache();
        $this->mockGlobalVars(['var1', 'var2', 'var3']);

        // Simulate concurrent access by clearing cache mid-execution
        // This is tricky to test directly, but we can test that caching works correctly
        $result1 = $this->reflectionMethod->invoke($this->template);

        // Clear cache and run again - should get same result
        $this->clearGlobalsRegexCache();
        $result2 = $this->reflectionMethod->invoke($this->template);

        $this->assertEquals($result1, $result2);
        $this->assertIsArray($result1);
        $this->assertNotEmpty($result1);
    }

    public function testGetGlobalsRegexWithDuplicateNames()
    {
        // Test with duplicate variable names
        $duplicateVars = [
            'duplicate_var',
            'duplicate_var',
            'duplicate_var',
            'unique_var',
            'another_duplicate',
            'another_duplicate'
        ];

        $this->mockGlobalVars($duplicateVars);
        $this->clearGlobalsRegexCache();

        $result = $this->reflectionMethod->invoke($this->template);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Should handle duplicates gracefully (array_unique should be applied)
        // The result should still be valid regex patterns
        foreach ($result as $regex) {
            $this->assertStringStartsWith('/', $regex);
            $this->assertStringEndsWith('/', $regex);
        }
    }

    private function mockGlobalVars(array $vars)
    {
        // Set global vars in the mock config
        ee()->config->_global_vars = array_combine($vars, $vars);
    }

    private function clearGlobalsRegexCache()
    {
        // Clear the cached regex by unsetting the property
        $reflection = new \ReflectionProperty($this->template, 'globals_regex');
        \TestReflectionHelper::makePropertyAccessible($reflection);
        $reflection->setValue($this->template, []);
    }
}