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
 * Test Relationships_ft_cp::buildCategoryList() private method
 */
class RelationshipsFtCpBuildNestedCategoryArrayTest extends RelationshipTestBase
{
    /**
     * Test buildCategoryList() with categories that have children
     */
    public function testBuildNestedCategoryArrayWithChildren()
    {
        // Create test data - categories array and hierarchy
        $categories = $this->createCategoriesArray();
        $hierarchy = $this->createHierarchyFromCategories($categories);

        $result = $this->invokeBuildCategoryList(0, $hierarchy, $categories);

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
     * Test buildCategoryList() with categories that have no children
     */
    public function testBuildNestedCategoryArrayWithNoChildren()
    {
        // Create test data for flat categories
        $categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'Category 1', 'parent_id' => 0],
            2 => ['cat_id' => 2, 'cat_name' => 'Category 2', 'parent_id' => 0],
        ];
        $hierarchy = [
            0 => [1, 2] // parent_id 0 has children 1 and 2
        ];

        $result = $this->invokeBuildCategoryList(0, $hierarchy, $categories);

        // Should return flat structure
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertEquals('Category 1', $result[1]);
        $this->assertEquals('Category 2', $result[2]);
    }

    /**
     * Test buildCategoryList() with empty array
     */
    public function testBuildNestedCategoryArrayWithEmptyArray()
    {
        $result = $this->invokeBuildCategoryList(0, [], []);

        $this->assertEmpty($result);
        $this->assertIsArray($result);
    }

    /**
     * Test buildCategoryList() with mixed hierarchy (some with children, some without)
     */
    public function testBuildNestedCategoryArrayWithMixedHierarchy()
    {
        // Create mixed structure: one parent with child, one standalone category
        $categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'Parent Category', 'parent_id' => 0],
            2 => ['cat_id' => 2, 'cat_name' => 'Child Category', 'parent_id' => 1],
            3 => ['cat_id' => 3, 'cat_name' => 'Standalone Category', 'parent_id' => 0],
        ];
        $hierarchy = [
            0 => [1, 3], // parent_id 0 has children 1 and 3
            1 => [2]     // parent_id 1 has child 2
        ];

        $result = $this->invokeBuildCategoryList(0, $hierarchy, $categories);

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
     * Test buildCategoryList() with deeply nested categories
     */
    public function testBuildNestedCategoryArrayWithDeepNesting()
    {
        // Create 3-level nesting
        $categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'Parent Category', 'parent_id' => 0],
            2 => ['cat_id' => 2, 'cat_name' => 'Child Category', 'parent_id' => 1],
            3 => ['cat_id' => 3, 'cat_name' => 'Grandchild Category', 'parent_id' => 2],
        ];
        $hierarchy = [
            0 => [1],     // parent_id 0 has child 1
            1 => [2],     // parent_id 1 has child 2
            2 => [3]      // parent_id 2 has child 3
        ];

        $result = $this->invokeBuildCategoryList(0, $hierarchy, $categories);

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
     * Test buildCategoryList() with multiple children per parent
     */
    public function testBuildNestedCategoryArrayWithMultipleChildren()
    {
        // Create parent with multiple children
        $categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'Parent Category', 'parent_id' => 0],
            2 => ['cat_id' => 2, 'cat_name' => 'Child 1', 'parent_id' => 1],
            3 => ['cat_id' => 3, 'cat_name' => 'Child 2', 'parent_id' => 1],
        ];
        $hierarchy = [
            0 => [1],     // parent_id 0 has child 1
            1 => [2, 3]   // parent_id 1 has children 2 and 3
        ];

        $result = $this->invokeBuildCategoryList(0, $hierarchy, $categories);

        $children = $result[1]['children'];

        // Should have both children
        $this->assertArrayHasKey(2, $children);
        $this->assertArrayHasKey(3, $children);
        $this->assertEquals('Child 1', $children[2]);
        $this->assertEquals('Child 2', $children[3]);
    }

    /**
     * Helper to invoke the private buildCategoryList method
     */
    private function invokeBuildCategoryList($parentId, $hierarchy, $categories)
    {
        $reflection = new ReflectionClass($this->relationships_ft_cp);
        $method = $reflection->getMethod('buildCategoryList');
        $method->setAccessible(true);

        return $method->invoke($this->relationships_ft_cp, $parentId, $hierarchy, $categories);
    }

    /**
     * Helper to create test categories array
     */
    private function createCategoriesArray()
    {
        return [
            1 => ['cat_id' => 1, 'cat_name' => 'Parent Category', 'parent_id' => 0],
            2 => ['cat_id' => 2, 'cat_name' => 'Child Category', 'parent_id' => 1],
        ];
    }

    /**
     * Helper to create hierarchy from categories
     */
    private function createHierarchyFromCategories($categories)
    {
        $hierarchy = [];
        foreach ($categories as $category) {
            $parentId = $category['parent_id'];
            if (!isset($hierarchy[$parentId])) {
                $hierarchy[$parentId] = [];
            }
            $hierarchy[$parentId][] = $category['cat_id'];
        }
        return $hierarchy;
    }
}
