<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

class EE_TemplateFetchDataBetweenVarPairsTest extends EE_TemplateTestBase
{
    public function testFetchDataBetweenVarPairsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'fetch_data_between_var_pairs'));
        $this->assertTrue(is_callable([$this->template, 'fetch_data_between_var_pairs']));
    }

    public function testFetchDataBetweenVarPairsValidPair()
    {
        $str = 'Before {variable}content here{/variable} After';
        $result = $this->template->fetch_data_between_var_pairs($str, 'variable');

        $this->assertEquals('content here', $result);
    }

    public function testFetchDataBetweenVarPairsMultipleLines()
    {
        $str = 'Start {block}
Line 1
Line 2
{/block} End';
        $result = $this->template->fetch_data_between_var_pairs($str, 'block');

        $expected = '
Line 1
Line 2
';
        $this->assertEquals($expected, $result);
    }

    public function testFetchDataBetweenVarPairsEmptyString()
    {
        $result = $this->template->fetch_data_between_var_pairs('', 'variable');

        $this->assertNull($result);
    }

    public function testFetchDataBetweenVarPairsEmptyVariable()
    {
        $str = 'Some content {variable}content{/variable}';
        $result = $this->template->fetch_data_between_var_pairs($str, '');

        $this->assertNull($result);
    }

    public function testFetchDataBetweenVarPairsNoMatches()
    {
        $str = 'No variable pairs here';
        $result = $this->template->fetch_data_between_var_pairs($str, 'variable');

        $this->assertNull($result);
    }

    public function testFetchDataBetweenVarPairsMultiplePairs()
    {
        $str = '{item}First{/item} {item}Second{/item}';
        $result = $this->template->fetch_data_between_var_pairs($str, 'item');

        // Should return first match only
        $this->assertEquals('First', $result);
    }

    public function testFetchDataBetweenVarPairsMultipleSameName()
    {
        // Test multiple pairs with same name - should return first match
        $str = '{item}first{/item}middle{item}second{/item}';
        $result = $this->template->fetch_data_between_var_pairs($str, 'item');

        $this->assertEquals('first', $result);
    }

    public function testFetchDataBetweenVarPairsEmptyContent()
    {
        // Test pairs with zero-length content
        $str = '{var}{/var}next{var}content{/var}';
        $result = $this->template->fetch_data_between_var_pairs($str, 'var');

        // Should return empty string for first pair
        $this->assertEquals('', $result);
    }

    public function testFetchDataBetweenVarPairsVeryLargeContent()
    {
        // Test memory handling with large extracted content
        $hugeContent = str_repeat('x', 25000);
        $str = "{var}{$hugeContent}{/var}";
        $result = $this->template->fetch_data_between_var_pairs($str, 'var');

        $this->assertEquals($hugeContent, $result);
    }

    public function testFetchDataBetweenVarPairsRegexBacktrackingProtection()
    {
        // Test protection against regex backtracking attacks
        $evilInput = str_repeat('{test', 30) . 'content' . str_repeat('{/test', 30);

        $start = microtime(true);
        $result = $this->template->fetch_data_between_var_pairs($evilInput, 'test');
        $duration = microtime(true) - $start;

        // Should complete quickly and return null (no valid pairs)
        $this->assertLessThan(0.05, $duration, 'Should not cause excessive regex backtracking');
        $this->assertNull($result);
    }

    public function testFetchDataBetweenVarPairsMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'fetch_data_between_var_pairs');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('str', $parameters[0]->getName());
        $this->assertEquals('variable', $parameters[1]->getName());
    }
}