<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibFetchCategoriesTest extends ChannelFormLibTestBase
{
    public function testFetchCategoriesReturnsEarlyWhenCategoriesAlreadyLoaded()
    {
        // Setup: Categories already loaded
        $this->channelFormLib->categories = [
            1 => ['category_id' => 1, 'category_name' => 'Existing Category']
        ];

        // Mock channel with category groups
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection([(object)['group_id' => 1]]);
        $this->channelFormLib->channel = $mockChannel;

        // Call fetch_categories
        $this->channelFormLib->fetch_categories();

        // Verify categories were not overwritten
        $this->assertCount(1, $this->channelFormLib->categories);
        $this->assertEquals('Existing Category', $this->channelFormLib->categories[1]['category_name']);
    }

    public function testFetchCategoriesReturnsEarlyWhenNoCategoryGroups()
    {
        // Setup: No categories loaded, but no category groups either
        $this->channelFormLib->categories = [];

        // Mock channel with empty category groups
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection(); // Empty collection
        $this->channelFormLib->channel = $mockChannel;

        // Call fetch_categories
        $this->channelFormLib->fetch_categories();

        // Verify categories remain empty
        $this->assertEquals([], $this->channelFormLib->categories);
    }

    public function testFetchCategoriesLoadsCategoriesSuccessfully()
    {
        // Define NBS constant for indentation
        if (!defined('NBS')) {
            define('NBS', '&nbsp;');
        }

        // Setup: Mock dependencies
        $this->channelFormLib->categories = [];

        // Mock channel with category groups
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection([(object)['group_id' => 1]]);
        $this->channelFormLib->channel = $mockChannel;

        // Mock entry for get_selected_cats method
        $mockEntry = $this->createMockEntry(['entry_id' => 123]);
        $mockEntry->Categories = new \ExpressionEngine\Service\Model\Collection([
            (object)['cat_id' => 2]
        ]);
        $this->channelFormLib->entry = $mockEntry;

        // Mock API channel categories
        $this->setMock('api_channel_categories', new class {
            public function category_tree($groups, $selected) {
                return [
                    1 => [1, 'Category 1', 1, 'Group 1', false, 1, '', '', 'Description 1'],
                    2 => [2, 'Category 2', 1, 'Group 1', true, 1, '', '', 'Description 2'], // Selected
                    3 => [3, 'Category 3', 1, 'Group 1', false, 2, '', '', 'Description 3'], // Child category
                ];
            }
        });

        // Mock file field
        $this->setMock('file_field', new class {
            public function parse_field($data) {
                return ['url' => 'http://example.com/image.jpg'];
            }
        });

        // Call fetch_categories
        $this->channelFormLib->fetch_categories();

        // Verify categories were loaded and processed
        $this->assertCount(3, $this->channelFormLib->categories);

        // Check first category
        $this->assertEquals(1, $this->channelFormLib->categories[1]['category_id']);
        $this->assertEquals('Category 1', $this->channelFormLib->categories[1]['category_name']);
        $this->assertEquals('', $this->channelFormLib->categories[1]['selected']);
        $this->assertEquals('', $this->channelFormLib->categories[1]['checked']);

        // Check selected category
        $this->assertEquals(2, $this->channelFormLib->categories[2]['category_id']);
        $this->assertEquals(' selected="selected"', $this->channelFormLib->categories[2]['selected']);
        $this->assertEquals(' checked="checked"', $this->channelFormLib->categories[2]['checked']);

        // Check child category with indentation
        $this->assertEquals(3, $this->channelFormLib->categories[3]['category_id']);
        $this->assertStringContainsString(NBS, $this->channelFormLib->categories[3]['category_name']); // Indented
        $this->assertEquals(2, $this->channelFormLib->categories[3]['category_depth']);
    }

    public function testFetchCategoriesHandlesCategoryImage()
    {
        // Define NBS constant for indentation
        if (!defined('NBS')) {
            define('NBS', '&nbsp;');
        }

        // Setup: Mock dependencies
        $this->channelFormLib->categories = [];

        // Mock channel with category groups
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection([(object)['group_id' => 1]]);
        $this->channelFormLib->channel = $mockChannel;

        // Mock entry for get_selected_cats method
        $mockEntry = $this->createMockEntry(['entry_id' => 123]);
        $mockEntry->Categories = new \ExpressionEngine\Service\Model\Collection([]);
        $this->channelFormLib->entry = $mockEntry;

        // Mock API channel categories with image data
        $this->setMock('api_channel_categories', new class {
            public function category_tree($groups, $selected) {
                return [
                    1 => [1, 'Category 1', 1, 'Group 1', false, 1, '', 'image_data', 'Description 1'],
                ];
            }
        });

        // Mock file field to return image URL
        $this->setMock('file_field', new class {
            public function parse_field($data) {
                return ['url' => 'http://example.com/category-image.jpg'];
            }
        });

        // Call fetch_categories
        $this->channelFormLib->fetch_categories();

        // Verify image was processed
        $this->assertEquals('http://example.com/category-image.jpg', $this->channelFormLib->categories[1]['category_image']);
    }

    public function testFetchCategoriesHandlesNullCategoryImage()
    {
        // Define NBS constant for indentation
        if (!defined('NBS')) {
            define('NBS', '&nbsp;');
        }

        // Setup: Mock dependencies
        $this->channelFormLib->categories = [];

        // Mock channel with category groups
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection([(object)['group_id' => 1]]);
        $this->channelFormLib->channel = $mockChannel;

        // Mock entry for get_selected_cats method
        $mockEntry = $this->createMockEntry(['entry_id' => 123]);
        $mockEntry->Categories = new \ExpressionEngine\Service\Model\Collection([]);
        $this->channelFormLib->entry = $mockEntry;

        // Mock API channel categories with no image
        $this->setMock('api_channel_categories', new class {
            public function category_tree($groups, $selected) {
                return [
                    1 => [1, 'Category 1', 1, 'Group 1', false, 1, '', '', 'Description 1'],
                ];
            }
        });

        // Mock file field to return no URL
        $this->setMock('file_field', new class {
            public function parse_field($data) {
                return []; // No URL in result
            }
        });

        // Call fetch_categories
        $this->channelFormLib->fetch_categories();

        // Verify empty image URL
        $this->assertEquals('', $this->channelFormLib->categories[1]['category_image']);
    }

    public function testFetchCategoriesProcessesMultipleGroups()
    {
        // Define NBS constant for indentation
        if (!defined('NBS')) {
            define('NBS', '&nbsp;');
        }

        // Setup: Mock dependencies
        $this->channelFormLib->categories = [];

        // Mock channel with multiple category groups
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection([
            (object)['group_id' => 1],
            (object)['group_id' => 2]
        ]);
        $this->channelFormLib->channel = $mockChannel;

        // Mock entry for get_selected_cats method
        $mockEntry = $this->createMockEntry(['entry_id' => 123]);
        $mockEntry->Categories = new \ExpressionEngine\Service\Model\Collection([
            (object)['cat_id' => 3]
        ]);
        $this->channelFormLib->entry = $mockEntry;

        // Mock API channel categories
        $this->setMock('api_channel_categories', new class {
            public function category_tree($groups, $selected) {
                return [
                    1 => [1, 'Group 1 Category', 1, 'Group 1', false, 1, '', '', ''],
                    2 => [2, 'Group 2 Category', 2, 'Group 2', false, 1, '', '', ''],
                    3 => [3, 'Selected Category', 1, 'Group 1', true, 1, '', '', ''],
                ];
            }
        });

        // Mock file field
        $this->setMock('file_field', new class {
            public function parse_field($data) {
                return ['url' => ''];
            }
        });

        // Call fetch_categories
        $this->channelFormLib->fetch_categories();

        // Verify all groups were processed
        $this->assertCount(3, $this->channelFormLib->categories);

        // Verify group information is preserved
        $this->assertEquals('Group 1', $this->channelFormLib->categories[1]['category_group']);
        $this->assertEquals('Group 2', $this->channelFormLib->categories[2]['category_group']);
        $this->assertEquals(1, $this->channelFormLib->categories[1]['category_group_id']);
        $this->assertEquals(2, $this->channelFormLib->categories[2]['category_group_id']);
    }

    public function testFetchCategoriesHandlesDeepCategoryHierarchy()
    {
        // Define NBS constant for indentation
        if (!defined('NBS')) {
            define('NBS', '&nbsp;');
        }

        // Setup: Mock dependencies
        $this->channelFormLib->categories = [];

        // Mock channel with category groups
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection([(object)['group_id' => 1]]);
        $this->channelFormLib->channel = $mockChannel;

        // Mock entry for get_selected_cats method
        $mockEntry = $this->createMockEntry(['entry_id' => 123]);
        $mockEntry->Categories = new \ExpressionEngine\Service\Model\Collection([]);
        $this->channelFormLib->entry = $mockEntry;

        // Mock API channel categories with deep hierarchy
        $this->setMock('api_channel_categories', new class {
            public function category_tree($groups, $selected) {
                return [
                    1 => [1, 'Parent Category', 1, 'Group 1', false, 1, '', '', ''],
                    2 => [2, 'Child Category', 1, 'Group 1', false, 2, 1, '', ''], // Child of 1
                    3 => [3, 'Grandchild Category', 1, 'Group 1', false, 3, 2, '', ''], // Child of 2
                ];
            }
        });

        // Mock file field
        $this->setMock('file_field', new class {
            public function parse_field($data) {
                return ['url' => ''];
            }
        });

        // Call fetch_categories
        $this->channelFormLib->fetch_categories();

        // Verify hierarchy was preserved
        $this->assertCount(3, $this->channelFormLib->categories);

        // Parent category
        $this->assertEquals(1, $this->channelFormLib->categories[1]['category_depth']);
        $this->assertEquals('Parent Category', $this->channelFormLib->categories[1]['category_name']); // No indentation

        // Child category (indented once)
        $this->assertEquals(2, $this->channelFormLib->categories[2]['category_depth']);
        $this->assertStringContainsString(NBS, $this->channelFormLib->categories[2]['category_name']); // Indented
        $this->assertEquals(1, $this->channelFormLib->categories[2]['category_parent']);

        // Grandchild category (indented twice)
        $this->assertEquals(3, $this->channelFormLib->categories[3]['category_depth']);
        $this->assertStringContainsString(str_repeat(NBS, 2), $this->channelFormLib->categories[3]['category_name']); // Double indented
        $this->assertEquals(2, $this->channelFormLib->categories[3]['category_parent']);
    }

    public function testFetchCategoriesHandlesEmptyCategoryTree()
    {
        // Setup: Mock dependencies
        $this->channelFormLib->categories = [];

        // Mock channel with category groups
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection([(object)['group_id' => 1]]);
        $this->channelFormLib->channel = $mockChannel;

        // Mock entry for get_selected_cats method
        $mockEntry = $this->createMockEntry(['entry_id' => 123]);
        $mockEntry->Categories = new \ExpressionEngine\Service\Model\Collection([]);
        $this->channelFormLib->entry = $mockEntry;

        // Mock API channel categories returning empty tree
        $this->setMock('api_channel_categories', new class {
            public function category_tree($groups, $selected) {
                return []; // Empty result
            }
        });

        // Call fetch_categories
        $this->channelFormLib->fetch_categories();

        // Verify empty categories array
        $this->assertEquals([], $this->channelFormLib->categories);
    }

    public function testFetchCategoriesHandlesApiFailure()
    {
        // Setup: Mock dependencies
        $this->channelFormLib->categories = [];

        // Mock channel with category groups
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection([(object)['group_id' => 1]]);
        $this->channelFormLib->channel = $mockChannel;

        // Mock entry for get_selected_cats method
        $mockEntry = $this->createMockEntry(['entry_id' => 123]);
        $mockEntry->Categories = new \ExpressionEngine\Service\Model\Collection([]);
        $this->channelFormLib->entry = $mockEntry;

        // Mock API channel categories to throw exception
        $this->setMock('api_channel_categories', new class {
            public function category_tree($groups, $selected) {
                throw new Exception('API Error');
            }
        });

        // Expect graceful handling of API failure
        try {
            $this->channelFormLib->fetch_categories();
            // If no exception, categories should remain empty
            $this->assertEquals([], $this->channelFormLib->categories);
        } catch (Exception $e) {
            // If exception propagates, it should be the API error
            $this->assertEquals('API Error', $e->getMessage());
        }
    }

    public function testFetchCategoriesPassesCorrectParametersToApi()
    {
        // Setup: Mock dependencies
        $this->channelFormLib->categories = [];

        // Mock channel with specific category groups
        $mockChannel = $this->createMockChannel();
        $mockChannel->CategoryGroups = new \ExpressionEngine\Service\Model\Collection([
            (object)['group_id' => 5],
            (object)['group_id' => 10]
        ]);
        $this->channelFormLib->channel = $mockChannel;

        // Mock entry for get_selected_cats method
        $mockEntry = $this->createMockEntry(['entry_id' => 123]);
        $mockEntry->Categories = new \ExpressionEngine\Service\Model\Collection([
            (object)['cat_id' => 15],
            (object)['cat_id' => 20]
        ]);
        $this->channelFormLib->entry = $mockEntry;

        // Mock API channel categories and track parameters
        $this->setMock('api_channel_categories', new class {
            public $received_groups = [];
            public $received_selected = [];

            public function category_tree($groups, $selected) {
                $this->received_groups = $groups;
                $this->received_selected = $selected;
                return [];
            }
        });

        // Call fetch_categories
        $this->channelFormLib->fetch_categories();

        // Verify correct parameters were passed
        $this->assertEquals([5, 10], ee()->api_channel_categories->received_groups);
        $this->assertEquals([15, 20], ee()->api_channel_categories->received_selected);
    }
}
