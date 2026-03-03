<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

class EE_TemplateSwapVarPairsTest extends EE_TemplateTestBase
{
    public function testSwapVarPairsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'swap_var_pairs'));
        $this->assertTrue(is_callable([$this->template, 'swap_var_pairs']));
    }

    public function testSwapVarPairsBasicExtraction()
    {
        $source = 'Before {variable}content here{/variable} After';
        $result = $this->template->swap_var_pairs('variable', 'variable', $source);

        $this->assertEquals('Before content here After', $result);
    }

    public function testSwapVarPairsMultipleLines()
    {
        $source = 'Start {block}
Line 1
Line 2
{/block} End';
        $result = $this->template->swap_var_pairs('block', 'block', $source);

        // The method extracts content between tags, preserving internal formatting
        $this->assertStringContainsString('Start', $result);
        $this->assertStringContainsString('Line 1', $result);
        $this->assertStringContainsString('Line 2', $result);
        $this->assertStringContainsString('End', $result);
        $this->assertStringNotContainsString('{block}', $result);
        $this->assertStringNotContainsString('{/block}', $result);
    }

    public function testSwapVarPairsMultiplePairs()
    {
        $source = '{item}First{/item} {item}Second{/item}';
        $result = $this->template->swap_var_pairs('item', 'item', $source);

        $this->assertEquals('First Second', $result);
    }

    public function testSwapVarPairsNoMatches()
    {
        $source = 'No variable pairs here';
        $result = $this->template->swap_var_pairs('variable', 'variable', $source);

        $this->assertEquals('No variable pairs here', $result);
    }

    public function testSwapVarPairsDifferentOpenClose()
    {
        $source = 'Before {start}content{/end} After';
        $result = $this->template->swap_var_pairs('start', 'end', $source);

        $this->assertEquals('Before content After', $result);
    }

    public function testSwapVarPairsSpecialCharacters()
    {
        $source = 'Data: {data_1}Special content{/data_1}';
        $result = $this->template->swap_var_pairs('data_1', 'data_1', $source);

        $this->assertEquals('Data: Special content', $result);
    }

    public function testSwapVarPairsDeeplyNested()
    {
        // Test deeply nested pairs (regex recursion limits)
        $nested = str_repeat('{level}', 5) . 'content' . str_repeat('{/level}', 5);
        $result = $this->template->swap_var_pairs('level', 'level', $nested);

        // Should extract content from the outermost pair
        $this->assertEquals(str_repeat('{level}', 4) . 'content' . str_repeat('{/level}', 4), $result);
    }


    public function testSwapVarPairsEmptyContent()
    {
        // Test pairs with zero-length content
        $source = '{var}{/var}next{var}content{/var}';
        $result = $this->template->swap_var_pairs('var', 'var', $source);

        $this->assertEquals('nextcontent', $result);
    }

    public function testSwapVarPairsLargeContent()
    {
        // Test performance with large extracted content
        $largeContent = str_repeat('x', 10000);
        $source = "{var}{$largeContent}{/var}";
        $result = $this->template->swap_var_pairs('var', 'var', $source);

        $this->assertEquals($largeContent, $result);
    }

    public function testSwapVarPairsRegexBacktrackingProtection()
    {
        // Test inputs that could cause catastrophic regex backtracking
        // This is critical for security - malicious inputs shouldn't cause DoS

        // Evil input: many opening braces without matching closing braces
        $evilInput = str_repeat('{var', 100) . 'content' . str_repeat('{/var', 100);

        // Should complete quickly without hanging
        $start = microtime(true);
        $result = $this->template->swap_var_pairs('var', 'var', $evilInput);
        $duration = microtime(true) - $start;

        // Should complete in less than 0.1 seconds (reasonable for this input size)
        $this->assertLessThan(0.1, $duration, 'Regex should not cause excessive backtracking');
        $this->assertIsString($result);
    }

    public function testSwapVarPairsRegexQuantifierStress()
    {
        // Test with nested quantifiers that could cause regex engine stress
        $stressInput = '{outer}' . str_repeat('{inner}nested', 20) . 'content' . str_repeat('{/inner}', 20) . '{/outer}';

        $start = microtime(true);
        $result = $this->template->swap_var_pairs('outer', 'outer', $stressInput);
        $duration = microtime(true) - $start;

        // Should handle nested structures efficiently
        $this->assertLessThan(0.05, $duration, 'Should handle nested quantifiers efficiently');
        $this->assertIsString($result);
    }

    public function testSwapVarPairsExtremeNesting()
    {
        // Test with very deep nesting (50+ levels) to stress recursion limits
        $deeplyNested = '';
        for ($i = 1; $i <= 50; $i++) {
            $deeplyNested .= "{level{$i}}";
        }
        $deeplyNested .= 'deep content';
        for ($i = 50; $i > 0; $i--) {
            $deeplyNested .= "{/level{$i}}";
        }

        $result = $this->template->swap_var_pairs('level1', 'level1', $deeplyNested);

        // Should handle extreme nesting without stack overflow or performance issues
        $this->assertIsString($result);
        // The result should be the nested structure minus the outermost level
        // It should start with {level2} and end with {/level2} (reverse order of closing tags)
        $this->assertStringStartsWith('{level2}', $result);
        $this->assertStringEndsWith('{/level2}', $result);
        // And should contain the deep content
        $this->assertStringContainsString('deep content', $result);
    }

    public function testSwapVarPairsMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'swap_var_pairs');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('open', $parameters[0]->getName());
        $this->assertEquals('close', $parameters[1]->getName());
        $this->assertEquals('source', $parameters[2]->getName());
    }
}