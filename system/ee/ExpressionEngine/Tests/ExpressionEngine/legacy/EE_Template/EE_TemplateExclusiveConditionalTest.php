<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

/**
 * Comprehensive tests for EE_Template::exclusive_conditional() method
 */
class EE_TemplateExclusiveConditionalTest extends EE_TemplateTestBase
{
    /**
     * Test exclusive_conditional method exists
     */
    public function testExclusiveConditionalMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'exclusive_conditional'));
        $this->assertTrue(is_callable([$this->template, 'exclusive_conditional']));
    }

    /**
     * Test exclusive_conditional with simple condition
     */
    public function testExclusiveConditionalReturnsContentWhenConditionExists()
    {
        // Set up TMPL mock
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = '{if success}Success message{/if}';

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'success', [['success' => true]]);

        $this->assertEquals('Success message', $result);
    }

    /**
     * Test exclusive_conditional returns empty when condition doesn't exist
     */
    public function testExclusiveConditionalReturnsEmptyWhenNoCondition()
    {
        // Set up TMPL mock
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = 'No condition here';

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'missing', []);

        $this->assertEquals('', $result);
    }

    /**
     * Test exclusive_conditional with nested conditionals
     */
    public function testExclusiveConditionalWithNestedConditionals()
    {
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = '{if success}{if logged_in}Nested success{/if}{/if}';

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'success', [['success' => true, 'logged_in' => true]]);

        $this->assertEquals('{if logged_in}Nested success{/if}', $result);
    }

    /**
     * Test exclusive_conditional with multiple conditions
     */
    public function testExclusiveConditionalWithMultipleConditions()
    {
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = '{if first}First condition{/if}{if second}Second condition{/if}';

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'second', [['first' => false, 'second' => true]]);

        $this->assertEquals('Second condition', $result);
    }

    /**
     * Test exclusive_conditional with empty variables array
     */
    public function testExclusiveConditionalWithEmptyVars()
    {
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = 'No conditionals here';

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'success', []);

        $this->assertEquals('', $result);
    }

    /**
     * Test exclusive_conditional with invalid condition name
     */
    public function testExclusiveConditionalWithInvalidCondition()
    {
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = '{if valid}Valid condition{/if}';

        $result = $this->template->exclusive_conditional($this->template->tagdata, '', [['valid' => true]]);

        $this->assertEquals('', $result);
    }

    /**
     * Test exclusive_conditional with else conditions
     */
    public function testExclusiveConditionalWithElseConditions()
    {
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = '{if failure}Failure case{if:else}Success case{/if}';

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'failure', [['failure' => false]]);

        // The exclusive_conditional method extracts everything between {if condition} and {/if}
        // regardless of whether the condition evaluates to true or false
        $this->assertEquals('Failure case{if:else}Success case', $result);
    }

    /**
     * Test exclusive_conditional with complex conditional logic
     */
    public function testExclusiveConditionalWithComplexConditionalLogic()
    {
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = '{if admin}Admin access{/if}{if user}User access{/if}';

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'user', [['admin' => false, 'user' => true]]);

        $this->assertEquals('User access', $result);
    }

    /**
     * Test exclusive_conditional with single variable row
     */
    public function testExclusiveConditionalWithSingleVariableRow()
    {
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = '{if featured}Featured content{/if}';

        $variables = [['featured' => true, 'title' => 'Test Item']];

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'featured', $variables);

        $this->assertEquals('Featured content', $result);
    }

    /**
     * Test exclusive_conditional when conditional is not found in template
     */
    public function testExclusiveConditionalWhenConditionalNotFound()
    {
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = 'No conditionals here';

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'nonexistent', [['some_var' => 'value']]);

        $this->assertEquals('', $result);
    }

    /**
     * Test exclusive_conditional extracts only the matched conditional content
     */
    public function testExclusiveConditionalExtractsMatchedConditional()
    {
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = '{if success}Success content{/if}';

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'success', [['success' => true]]);

        $this->assertEquals('Success content', $result);
    }

    /**
     * Test exclusive_conditional with simple conditional name
     */
    public function testExclusiveConditionalWithSimpleConditional()
    {
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = '{if has_items}Has items{/if}';

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'has_items', [['has_items' => true]]);

        $this->assertEquals('Has items', $result);
    }

    /**
     * Test exclusive_conditional with boolean false value
     */
    public function testExclusiveConditionalWithFalseValue()
    {
        ee()->setMock('TMPL', $this->template);
        $this->template->tagdata = '{if is_empty}Is empty{/if}';

        $result = $this->template->exclusive_conditional($this->template->tagdata, 'is_empty', [['is_empty' => false]]);

        // Method extracts content regardless of variable value - just demonstrates the extraction works
        $this->assertEquals('Is empty', $result);
    }
}
