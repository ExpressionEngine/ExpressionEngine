<?php
 /**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/RelationshipTestBase.php';

use Mockery as m;

/**
 * Test Relationships_ft_cp::buildNestedCategoryArray() private method
 */
class RelationshipsFtCpBuildNestedCategoryArrayTest extends RelationshipTestBase
{
    /**
     * Test buildNestedCategoryArray() with categories that have children
     */
    public function testBuildNestedCategoryArrayWithChildren()
    {
        $categories = $this->createMockCategoriesWithChildren();

        $result = $this->invokeBuildNestedCategoryArray($categories);

        // Should return nested structure
        $this->assertArrayHasKey(1, $result);
        $this->assertEquals('Parent Category', $result[1]['name']);
        $this->assertArrayHasKey('children', $result[1]);

        // Check children
        $children = $result[1]['children'];
        $this->assertArrayHasKey(2, $children);
        $this->assertEquals('Child Category', $children[2]);
    }

    /**
     * Test buildNestedCategoryArray() with categories that have no children
     */
    public function testBuildNestedCategoryArrayWithNoChildren()
    {
        $categories = [
            $this->createMockCategory(1, 'Category 1', 0),
            $this->createMockCategory(2, 'Category 2', 0),
        ];

        $result = $this->invokeBuildNestedCategoryArray($categories);

        // Should return flat structure
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertEquals('Category 1', $result[1]);
        $this->assertEquals('Category 2', $result[2]);
    }

    /**
     * Test buildNestedCategoryArray() with empty array
     */
    public function testBuildNestedCategoryArrayWithEmptyArray()
    {
        $result = $this->invokeBuildNestedCategoryArray([]);

        $this->assertEmpty($result);
        $this->assertIsArray($result);
    }

    /**
     * Test buildNestedCategoryArray() with mixed hierarchy (some with children, some without)
     */
    public function testBuildNestedCategoryArrayWithMixedHierarchy()
    {
        // Create mixed structure: one parent with child, one standalone category
        $child = $this->createMockCategory(2, 'Child Category', 1);
        $parent = $this->createMockCategory(1, 'Parent Category', 0, [$child]);

        $standalone = $this->createMockCategory(3, 'Standalone Category', 0);

        $categories = [$parent, $standalone];

        $result = $this->invokeBuildNestedCategoryArray($categories);

        // Should have both nested and flat entries
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(3, $result);

        // Parent should be nested
        $this->assertEquals('Parent Category', $result[1]['name']);
        $this->assertArrayHasKey('children', $result[1]);
        $this->assertArrayHasKey(2, $result[1]['children']);
        $this->assertEquals('Child Category', $result[1]['children'][2]);

        // Standalone should be flat
        $this->assertEquals('Standalone Category', $result[3]);
    }

    /**
     * Test buildNestedCategoryArray() with deeply nested categories
     */
    public function testBuildNestedCategoryArrayWithDeepNesting()
    {
        // Create 3-level nesting
        $grandchild = $this->createMockCategory(3, 'Grandchild Category', 2);
        $child = $this->createMockCategory(2, 'Child Category', 1, [$grandchild]);
        $parent = $this->createMockCategory(1, 'Parent Category', 0, [$child]);

        $categories = [$parent];

        $result = $this->invokeBuildNestedCategoryArray($categories);

        // Verify 3-level nesting
        $this->assertArrayHasKey(1, $result);
        $this->assertEquals('Parent Category', $result[1]['name']);

        $level2 = $result[1]['children'];
        $this->assertArrayHasKey(2, $level2);
        $this->assertEquals('Child Category', $level2[2]['name']);

        $level3 = $level2[2]['children'];
        $this->assertArrayHasKey(3, $level3);
        $this->assertEquals('Grandchild Category', $level3[3]);
    }

    /**
     * Test buildNestedCategoryArray() with multiple children per parent
     */
    public function testBuildNestedCategoryArrayWithMultipleChildren()
    {
        // Create parent with multiple children
        $child1 = $this->createMockCategory(2, 'Child 1', 1);
        $child2 = $this->createMockCategory(3, 'Child 2', 1);
        $parent = $this->createMockCategory(1, 'Parent Category', 0, [$child1, $child2]);

        $categories = [$parent];

        $result = $this->invokeBuildNestedCategoryArray($categories);

        $children = $result[1]['children'];

        // Should have both children
        $this->assertArrayHasKey(2, $children);
        $this->assertArrayHasKey(3, $children);
        $this->assertEquals('Child 1', $children[2]);
        $this->assertEquals('Child 2', $children[3]);
    }

    /**
     * Helper to invoke the private buildNestedCategoryArray method
     */
    private function invokeBuildNestedCategoryArray($categories)
    {
        $reflection = new ReflectionClass($this->relationships_ft_cp);
        $method = $reflection->getMethod('buildNestedCategoryArray');
        $method->setAccessible(true);

        return $method->invoke($this->relationships_ft_cp, $categories);
    }

    /**
     * Helper to create mock categories with children
     */
    private function createMockCategoriesWithChildren()
    {
        $child = $this->createMockCategory(2, 'Child Category', 1);
        $parent = $this->createMockCategory(1, 'Parent Category', 0, [$child]);

        return [$parent];
    }

    /**
     * Helper to create a mock category
     */
    private function createMockCategory($id, $name, $parentId, $children = [])
    {
        $mockCategory = m::mock('stdClass');
        $mockCategory->cat_id = $id;
        $mockCategory->cat_name = $name;
        $mockCategory->parent_id = $parentId;
        $mockCategory->Children = $children; // Array of children

        return $mockCategory;
    }
}
