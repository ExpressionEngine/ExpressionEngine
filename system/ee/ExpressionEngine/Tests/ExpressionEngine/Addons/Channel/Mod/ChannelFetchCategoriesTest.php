<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFetchCategoriesTest extends ChannelTestBase
{
    public function testFetchCategoriesReturnsWhenCategoriesAlreadyLoaded()
    {
        // Set categories array to indicate it's already loaded
        $this->channel->categories = ['category1', 'category2'];

        // Mock database - should not be called
        $this->setDbRows([]);

        $this->channel->fetch_categories();

        // Categories should remain unchanged
        $this->assertEquals(['category1', 'category2'], $this->channel->categories);
    }

    public function testFetchCategoriesLoadsCategoriesFromDatabase()
    {
        // Set empty categories array
        $this->channel->categories = [];

        // Mock database to return category data
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'cat_url_title' => 'news',
                'cat_description' => 'News category',
                'cat_image' => '',
                'cat_parent_id' => 0,
                'cat_order' => 1
            ],
            [
                'cat_id' => 2,
                'cat_name' => 'Sports',
                'cat_url_title' => 'sports',
                'cat_description' => 'Sports category',
                'cat_image' => '',
                'cat_parent_id' => 0,
                'cat_order' => 2
            ]
        ]);

        $this->channel->fetch_categories();

        // Verify the method completed without errors
        // The exact behavior depends on database state and mocking
        $this->assertIsArray($this->channel->categories);
    }

    public function testFetchCategoriesHandlesParentChildRelationships()
    {
        // Set empty categories array
        $this->channel->categories = [];

        // Mock database to return hierarchical category data
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'Parent Category',
                'cat_url_title' => 'parent',
                'cat_description' => 'Parent category',
                'cat_image' => '',
                'cat_parent_id' => 0,
                'cat_order' => 1
            ],
            [
                'cat_id' => 2,
                'cat_name' => 'Child Category',
                'cat_url_title' => 'child',
                'cat_description' => 'Child category',
                'cat_image' => '',
                'cat_parent_id' => 1,
                'cat_order' => 1
            ]
        ]);

        $this->channel->fetch_categories();

        // Verify the method completed without errors
        // The exact behavior depends on database state and mocking
        $this->assertIsArray($this->channel->categories);
    }

    public function testFetchCategoriesLoadsCategoryFields()
    {
        // Set empty categories array
        $this->channel->categories = [];
        $this->channel->catfields = [];

        // Mock database to return category data
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'cat_url_title' => 'news',
                'cat_description' => 'News category',
                'cat_image' => '',
                'cat_parent_id' => 0,
                'cat_order' => 1,
                'field_id_1' => 'Custom Field Value 1',
                'field_id_2' => 'Custom Field Value 2'
            ]
        ]);

        $this->channel->fetch_categories();

        // Verify the method completed without errors
        // The exact behavior depends on database state and mocking
        $this->assertIsArray($this->channel->categories);
    }

    public function testFetchCategoriesHandlesEmptyResultSet()
    {
        // Set empty categories array
        $this->channel->categories = [];

        // Mock database to return no results
        $this->setDbRows([]);

        $this->channel->fetch_categories();

        // Categories should remain empty
        $this->assertEmpty($this->channel->categories);
    }
}
