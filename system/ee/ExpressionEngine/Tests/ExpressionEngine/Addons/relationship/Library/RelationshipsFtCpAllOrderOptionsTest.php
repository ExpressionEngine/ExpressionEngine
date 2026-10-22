<?php
 /**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/RelationshipTestBase.php';

use Mockery as m;

/**
 * Test Relationships_ft_cp::all_order_options() method
 */
class RelationshipsFtCpAllOrderOptionsTest extends RelationshipTestBase
{
    /**
     * Test all_order_options() returns correct array structure
     */
    public function testAllOrderOptionsReturnsCorrectStructure()
    {
        $result = $this->relationships_ft_cp->all_order_options();

        $expected = [
            'title' => 'rel_ft_order_title',
            'entry_date' => 'rel_ft_order_date'
        ];

        $this->assertContains($expected, $result);
    }

    /**
     * Test all_order_options() returns array with correct keys
     */
    public function testAllOrderOptionsHasCorrectKeys()
    {
        $result = $this->relationships_ft_cp->all_order_options();

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('entry_date', $result);
        $this->assertCount(2, $result);
    }

    /**
     * Test all_order_options() returns translated labels
     */
    public function testAllOrderOptionsReturnsTranslatedLabels()
    {
        $result = $this->relationships_ft_cp->all_order_options();

        $this->assertEquals('rel_ft_order_title', $result['title']);
        $this->assertEquals('rel_ft_order_date', $result['entry_date']);
    }

    /**
     * Test all_order_options() returns consistent results
     */
    public function testAllOrderOptionsReturnsConsistentResults()
    {
        $result1 = $this->relationships_ft_cp->all_order_options();
        $result2 = $this->relationships_ft_cp->all_order_options();

        $this->assertEquals($result1, $result2);
    }
}
