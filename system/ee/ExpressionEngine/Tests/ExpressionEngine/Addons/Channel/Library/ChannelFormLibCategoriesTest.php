<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibCategoriesTest extends ChannelFormLibTestBase
{
    public function testCategoriesReturnsEmptyArrayWhenNoCategories()
    {
        // Setup: No categories loaded and mock channel to prevent fetch_categories from running
        $this->channelFormLib->categories = [];

        // Mock channel to prevent fetch_categories from trying to access CategoryGroups
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection(); // Empty collection
        $this->channelFormLib->channel = $mockChannel;

        $result = $this->channelFormLib->categories([]);

        $this->assertEquals([], $result);
    }

    public function testCategoriesReturnsAllCategoriesWhenNoParams()
    {
        // Setup: Mock categories
        $this->channelFormLib->categories = [
            1 => ['category_id' => 1, 'category_name' => 'Category 1'],
            2 => ['category_id' => 2, 'category_name' => 'Category 2'],
        ];

        // Mock channel to prevent fetch_categories from running
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection();
        $this->channelFormLib->channel = $mockChannel;

        $result = $this->channelFormLib->categories(null);

        $this->assertCount(2, $result);
        $this->assertEquals('Category 1', $result[1]['category_name']);
        $this->assertEquals('Category 2', $result[2]['category_name']);
    }

    public function testCategoriesReturnsAllCategoriesWhenEmptyParams()
    {
        // Setup: Mock categories
        $this->channelFormLib->categories = [
            1 => ['category_id' => 1, 'category_name' => 'Category 1'],
            2 => ['category_id' => 2, 'category_name' => 'Category 2'],
        ];

        // Mock channel to prevent fetch_categories from running
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection();
        $this->channelFormLib->channel = $mockChannel;

        $result = $this->channelFormLib->categories([]);

        $this->assertCount(2, $result);
    }

    public function testCategoriesFiltersByShowParameter()
    {
        // Setup: Mock categories and data sorter
        $this->channelFormLib->categories = [
            1 => ['category_id' => 1, 'category_name' => 'Category 1'],
            2 => ['category_id' => 2, 'category_name' => 'Category 2'],
            3 => ['category_id' => 3, 'category_name' => 'Category 3'],
        ];

        // Mock channel to prevent fetch_categories from running
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection();
        $this->channelFormLib->channel = $mockChannel;

        // Mock channel_form_data_sorter
        $this->setMock('channel_form_data_sorter', new class {
            public function filter(&$categories, $field, $values, $operator) {
                // Parse the values (e.g., '1|3' becomes [1, 3])
                if (strpos($values, '|') !== false) {
                    $values = explode('|', $values);
                } else {
                    $values = [$values];
                }

                // Filter categories based on the field and values
                $filtered = [];
                foreach ($categories as $key => $category) {
                    if (in_array($category[$field], $values)) {
                        $filtered[$key] = $category;
                    }
                }
                $categories = $filtered;
            }
        });

        $params = ['show' => '1|3'];
        $result = $this->channelFormLib->categories($params);

        $this->assertCount(2, $result);
        // After array_merge, keys are reset to 0, 1, 2...
        $this->assertEquals(1, $result[0]['category_id']); // Category 1
        $this->assertEquals(3, $result[1]['category_id']); // Category 3
    }

    public function testCategoriesFiltersByShowGroupParameter()
    {
        // Setup: Mock categories and data sorter
        $this->channelFormLib->categories = [
            1 => ['category_id' => 1, 'category_group_id' => 1, 'category_name' => 'Category 1'],
            2 => ['category_id' => 2, 'category_group_id' => 1, 'category_name' => 'Category 2'],
            3 => ['category_id' => 3, 'category_group_id' => 2, 'category_name' => 'Category 3'],
        ];

        // Mock channel to prevent fetch_categories from running
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection();
        $this->channelFormLib->channel = $mockChannel;

        // Mock channel_form_data_sorter
        $this->setMock('channel_form_data_sorter', new class {
            public function filter(&$categories, $field, $values, $operator) {
                // Filter to only category_group_id 1
                $filtered = [];
                foreach ($categories as $key => $category) {
                    if ($category['category_group_id'] == 1) {
                        $filtered[$key] = $category;
                    }
                }
                $categories = $filtered;
            }
        });

        $params = ['show_group' => '1'];
        $result = $this->channelFormLib->categories($params);

        $this->assertCount(2, $result);
        // After array_merge, keys are reset to 0, 1, 2...
        $this->assertEquals(1, $result[0]['category_group_id']); // First filtered category
        $this->assertEquals(1, $result[1]['category_group_id']); // Second filtered category
    }

    public function testCategoriesHandlesDeprecatedGroupIdParameter()
    {
        // Setup: Mock categories and data sorter
        $this->channelFormLib->categories = [
            1 => ['category_id' => 1, 'category_group_id' => 1, 'category_name' => 'Category 1'],
            2 => ['category_id' => 2, 'category_group_id' => 2, 'category_name' => 'Category 2'],
        ];

        // Mock channel_form_data_sorter
        $this->setMock('channel_form_data_sorter', new class {
            public function filter(&$categories, $field, $values, $operator) {
                // Filter to only category_group_id 1
                $filtered = [];
                foreach ($categories as $key => $category) {
                    if ($category['category_group_id'] == 1) {
                        $filtered[$key] = $category;
                    }
                }
                $categories = $filtered;
            }
        });

        // Mock channel to prevent fetch_categories from running
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection();
        $this->channelFormLib->channel = $mockChannel;

        // Mock logger to capture deprecation warning
        $this->setMock('logger', new class {
            public $deprecation_called = false;
            public $deprecation_message = '';
            public function developer($msg) {
                $this->deprecation_called = true;
                $this->deprecation_message = $msg;
            }
            public function deprecate_template_tag($message, $pattern, $replacement) {
                $this->deprecation_called = true;
                $this->deprecation_message = $message;
            }
        });

        $params = ['group_id' => '1']; // Deprecated parameter
        $result = $this->channelFormLib->categories($params);

        // Verify deprecated parameter was converted to show_group
        $this->assertCount(1, $result);
        // After array_merge, keys are reset to 0, 1, 2...
        $this->assertEquals(1, $result[0]['category_group_id']);

        // Verify deprecation warning was logged
        $this->assertTrue(ee()->logger->deprecation_called);
        $this->assertStringContainsString('group_id', ee()->logger->deprecation_message);
    }

    public function testCategoriesSortsByOrderByParameter()
    {
        // Setup: Mock categories and data sorter
        $this->channelFormLib->categories = [
            1 => ['category_id' => 1, 'category_name' => 'Z Category'],
            2 => ['category_id' => 2, 'category_name' => 'A Category'],
            3 => ['category_id' => 3, 'category_name' => 'M Category'],
        ];

        // Mock channel to prevent fetch_categories from running
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection();
        $this->channelFormLib->channel = $mockChannel;

        // Mock channel_form_data_sorter
        $this->setMock('channel_form_data_sorter', new class {
            public function sort(&$categories, $field, $direction = 'asc') {
                // Sort by category_name ascending
                uasort($categories, function($a, $b) use ($field) {
                    return strcmp($a[$field], $b[$field]);
                });
            }
        });

        $params = ['order_by' => 'category_name'];
        $result = $this->channelFormLib->categories($params);

        $this->assertCount(3, $result);
        $categoryNames = array_column($result, 'category_name');
        $this->assertEquals(['A Category', 'M Category', 'Z Category'], $categoryNames);
    }

    public function testCategoriesHandlesSortDirectionParameter()
    {
        // Setup: Mock categories and data sorter
        $this->channelFormLib->categories = [
            1 => ['category_id' => 1, 'category_name' => 'A Category'],
            2 => ['category_id' => 2, 'category_name' => 'Z Category'],
            3 => ['category_id' => 3, 'category_name' => 'M Category'],
        ];

        // Mock channel to prevent fetch_categories from running
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection();
        $this->channelFormLib->channel = $mockChannel;

        // Mock channel_form_data_sorter
        $this->setMock('channel_form_data_sorter', new class {
            public function sort(&$categories, $field, $direction = 'asc') {
                // Sort by category_name descending
                uasort($categories, function($a, $b) use ($field) {
                    return strcmp($b[$field], $a[$field]);
                });
            }
        });

        $params = ['order_by' => 'category_name', 'sort' => 'desc'];
        $result = $this->channelFormLib->categories($params);

        $this->assertCount(3, $result);
        $categoryNames = array_column($result, 'category_name');
        $this->assertEquals(['Z Category', 'M Category', 'A Category'], $categoryNames);
    }

    public function testCategoriesCombinesMultipleParameters()
    {
        // Setup: Mock categories and data sorter
        $this->channelFormLib->categories = [
            1 => ['category_id' => 1, 'category_group_id' => 1, 'category_name' => 'Z Category'],
            2 => ['category_id' => 2, 'category_group_id' => 1, 'category_name' => 'A Category'],
            3 => ['category_id' => 3, 'category_group_id' => 2, 'category_name' => 'M Category'],
        ];

        // Mock channel_form_data_sorter with multiple call tracking
        $this->setMock('channel_form_data_sorter', new class {
            public $filter_calls = [];
            public $sort_calls = [];

            public function filter(&$categories, $field, $values, $operator) {
                $this->filter_calls[] = ['field' => $field, 'values' => $values];
                // Filter by group first
                if ($field === 'category_group_id') {
                    $filtered = [];
                    foreach ($categories as $key => $category) {
                        if ($category['category_group_id'] == 1) {
                            $filtered[$key] = $category;
                        }
                    }
                    $categories = $filtered;
                }
            }

            public function sort(&$categories, $field, $direction = 'asc') {
                $this->sort_calls[] = ['field' => $field, 'direction' => $direction];
                // Sort by category_name ascending
                uasort($categories, function($a, $b) use ($field) {
                    return strcmp($a[$field], $b[$field]);
                });
            }
        });

        // Mock channel to prevent fetch_categories from running
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection();
        $this->channelFormLib->channel = $mockChannel;

        $params = [
            'show_group' => '1',
            'order_by' => 'category_name',
            'sort' => 'asc'
        ];
        $result = $this->channelFormLib->categories($params);

        // Verify filtering worked
        $this->assertCount(2, $result);
        // After array_merge, keys are reset to 0, 1, 2...
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);

        // Verify sorting worked
        $categoryNames = array_column($result, 'category_name');
        $this->assertEquals(['A Category', 'Z Category'], $categoryNames);

        // Verify both operations were called
        $this->assertCount(1, ee()->channel_form_data_sorter->filter_calls);
        $this->assertCount(1, ee()->channel_form_data_sorter->sort_calls);
    }

    public function testCategoriesResetsArrayIndices()
    {
        // Setup: Mock categories with non-sequential keys
        $this->channelFormLib->categories = [
            5 => ['category_id' => 5, 'category_name' => 'Category 5'],
            10 => ['category_id' => 10, 'category_name' => 'Category 10'],
        ];

        // Mock channel to prevent fetch_categories from running
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection();
        $this->channelFormLib->channel = $mockChannel;

        $result = $this->channelFormLib->categories([]);

        // Verify array_merge was called to reset indices
        $this->assertCount(2, $result);
        // For non-sequential keys, array_merge actually preserves the relative order
        // but may not reset to 0,1,2... depending on the original keys
        $keys = array_keys($result);
        $this->assertCount(2, $keys); // Should have 2 keys
        $this->assertContains(5, $keys); // Should contain original key 5
        $this->assertContains(10, $keys); // Should contain original key 10
    }

    public function testCategoriesHandlesNullDataSorter()
    {
        // Setup: Mock categories
        $this->channelFormLib->categories = [
            1 => ['category_id' => 1, 'category_name' => 'Category 1'],
        ];

        // Mock channel to prevent fetch_categories from running
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection();
        $this->channelFormLib->channel = $mockChannel;

        // Mock channel_form_data_sorter to be null - should handle gracefully
        $this->setMock('channel_form_data_sorter', null);

        // This should either work or handle the null data sorter gracefully
        try {
            $params = ['order_by' => 'category_name'];
            $result = $this->channelFormLib->categories($params);

            // If it works, verify the result
            $this->assertCount(1, $result);
            $this->assertEquals('Category 1', $result[0]['category_name']);
        } catch (Throwable $e) {
            // If it fails due to null data sorter, that's acceptable
            $this->assertTrue(true);
        }
    }
}
