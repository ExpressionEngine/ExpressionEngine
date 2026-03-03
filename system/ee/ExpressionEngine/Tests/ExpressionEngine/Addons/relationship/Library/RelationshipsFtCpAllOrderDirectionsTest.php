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
 * Test Relationships_ft_cp::all_order_directions() method
 */
class RelationshipsFtCpAllOrderDirectionsTest extends RelationshipTestBase
{
    /**
     * Test all_order_directions() returns correct array structure
     */
    public function testAllOrderDirectionsReturnsCorrectStructure()
    {
        $result = $this->relationships_ft_cp->all_order_directions();

        $expected = [
            'asc' => 'rel_ft_order_asc',
            'desc' => 'rel_ft_order_desc'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test all_order_directions() returns array with correct keys
     */
    public function testAllOrderDirectionsHasCorrectKeys()
    {
        $result = $this->relationships_ft_cp->all_order_directions();

        $this->assertArrayHasKey('asc', $result);
        $this->assertArrayHasKey('desc', $result);
        $this->assertCount(2, $result);
    }

    /**
     * Test all_order_directions() returns translated labels
     */
    public function testAllOrderDirectionsReturnsTranslatedLabels()
    {
        $result = $this->relationships_ft_cp->all_order_directions();

        $this->assertEquals('rel_ft_order_asc', $result['asc']);
        $this->assertEquals('rel_ft_order_desc', $result['desc']);
    }

    /**
     * Test all_order_directions() returns consistent results
     */
    public function testAllOrderDirectionsReturnsConsistentResults()
    {
        $result1 = $this->relationships_ft_cp->all_order_directions();
        $result2 = $this->relationships_ft_cp->all_order_directions();

        $this->assertEquals($result1, $result2);
    }
}
