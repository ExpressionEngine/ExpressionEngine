<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

class EE_TemplateDeleteVarPairsTest extends EE_TemplateTestBase
{
    public function testDeleteVarPairsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'delete_var_pairs'));
        $this->assertTrue(is_callable([$this->template, 'delete_var_pairs']));
    }

    public function testDeleteVarPairsCompleteRemoval()
    {
        $source = 'Before {variable}content{/variable} After';
        $result = $this->template->delete_var_pairs('variable', 'variable', $source);

        $this->assertEquals('Before  After', $result);
    }

    public function testDeleteVarPairsMultiplePairs()
    {
        $source = 'Start {item}First{/item} middle {item}Second{/item} end';
        $result = $this->template->delete_var_pairs('item', 'item', $source);

        $this->assertEquals('Start  middle  end', $result);
    }

    public function testDeleteVarPairsNoMatches()
    {
        $source = 'No variable pairs here at all';
        $result = $this->template->delete_var_pairs('variable', 'variable', $source);

        $this->assertEquals('No variable pairs here at all', $result);
    }

    public function testDeleteVarPairsDifferentOpenClose()
    {
        $source = 'Text {start}remove this{/end} more text';
        $result = $this->template->delete_var_pairs('start', 'end', $source);

        $this->assertEquals('Text  more text', $result);
    }

    public function testDeleteVarPairsMixedContent()
    {
        $source = 'Keep this {remove}but not this{/remove} and keep this';
        $result = $this->template->delete_var_pairs('remove', 'remove', $source);

        $this->assertEquals('Keep this  and keep this', $result);
    }

    public function testDeleteVarPairsNestedRemoval()
    {
        // Test nested pair removal
        $source = '{outer}{inner}content{/inner}more{/outer}';
        $result = $this->template->delete_var_pairs('outer', 'outer', $source);

        // Should remove the entire outer pair including nested content
        $this->assertEquals('', $result);
    }

    public function testDeleteVarPairsLargeDeletion()
    {
        // Test memory usage with large content being deleted
        $largeDelete = str_repeat('x', 50000);
        $source = "before{var}{$largeDelete}{/var}after";
        $result = $this->template->delete_var_pairs('var', 'var', $source);

        $this->assertEquals('beforeafter', $result);
    }

    public function testDeleteVarPairsMultipleIdenticalPairs()
    {
        // Test all instances are removed
        $source = '{del}1{/del}keep{del}2{/del}keep{del}3{/del}';
        $result = $this->template->delete_var_pairs('del', 'del', $source);

        $this->assertEquals('keepkeep', $result);
    }

    public function testDeleteVarPairsRegexBacktrackingProtection()
    {
        // Test protection against regex backtracking DoS attacks
        $evilInput = str_repeat('{evil', 50) . 'content' . str_repeat('{/evil', 50);

        $start = microtime(true);
        $result = $this->template->delete_var_pairs('evil', 'evil', $evilInput);
        $duration = microtime(true) - $start;

        // Should complete quickly without excessive backtracking
        $this->assertLessThan(0.1, $duration, 'Delete should not cause excessive regex backtracking');
        $this->assertIsString($result);
    }

    public function testDeleteVarPairsMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'delete_var_pairs');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('open', $parameters[0]->getName());
        $this->assertEquals('close', $parameters[1]->getName());
        $this->assertEquals('source', $parameters[2]->getName());
    }
}