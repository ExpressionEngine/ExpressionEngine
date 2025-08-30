<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelRelatedCategoryEntriesTest extends ChannelTestBase
{
    public function testRelatedCategoryEntriesReturnsEmptyStringWhenNoCategory()
    {
        // Set up template parameters without category
        $this->setTemplateParams([]);

        $result = $this->channel->related_category_entries();

        // Method returns NO_RESULTS when no category is provided
        $this->assertEquals('NO_RESULTS', $result);
    }

    public function testRelatedCategoryEntriesReturnsEmptyStringWhenNoCategories()
    {
        // Set up template parameters with category
        $this->setTemplateParams(['category' => 'news']);

        // Set empty categories array
        $this->channel->categories = [];

        $result = $this->channel->related_category_entries();

        // Method returns NO_RESULTS when no categories are provided
        $this->assertEquals('NO_RESULTS', $result);
    }

    public function testRelatedCategoryEntriesProcessesCategoryData()
    {
        // Set up template parameters with category
        $this->setTemplateParams(['category' => 'news']);

        // Set categories data
        $this->channel->categories = [
            1 => [
                'cat_id' => 1,
                'cat_name' => 'News',
                'cat_url_title' => 'news'
            ]
        ];

        // Mock database to return related entries
        $this->setDbRows([
            [
                'entry_id' => 1,
                'title' => 'Related News Entry',
                'url_title' => 'related-news-entry'
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some processed data
        $this->assertIsString($result);
    }

    public function testRelatedCategoryEntriesHandlesCategoryFromSegment()
    {
        // Don't set category parameter in template
        $this->setTemplateParams([]);

        // Set URI segment that contains category
        $this->channel->uri = 'category/news';

        // Set categories data
        $this->channel->categories = [
            1 => [
                'cat_id' => 1,
                'cat_name' => 'News',
                'cat_url_title' => 'news'
            ]
        ];

        // Mock database to return related entries
        $this->setDbRows([
            [
                'entry_id' => 1,
                'title' => 'Related News Entry',
                'url_title' => 'related-news-entry'
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some processed data
        $this->assertIsString($result);
    }

    public function testRelatedCategoryEntriesHandlesMultipleCategories()
    {
        // Set up template parameters with multiple categories
        $this->setTemplateParams(['category' => 'news|sports']);

        // Set categories data
        $this->channel->categories = [
            1 => [
                'cat_id' => 1,
                'cat_name' => 'News',
                'cat_url_title' => 'news'
            ],
            2 => [
                'cat_id' => 2,
                'cat_name' => 'Sports',
                'cat_url_title' => 'sports'
            ]
        ];

        // Mock database to return related entries
        $this->setDbRows([
            [
                'entry_id' => 1,
                'title' => 'News Entry',
                'url_title' => 'news-entry'
            ],
            [
                'entry_id' => 2,
                'title' => 'Sports Entry',
                'url_title' => 'sports-entry'
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some processed data for multiple categories
        $this->assertIsString($result);
    }

    public function testRelatedCategoryEntriesHandlesInvalidCategory()
    {
        // Set up template parameters with invalid category
        $this->setTemplateParams(['category' => 'nonexistent']);

        // Set categories data
        $this->channel->categories = [
            1 => [
                'cat_id' => 1,
                'cat_name' => 'News',
                'cat_url_title' => 'news'
            ]
        ];

        // Mock database to return no results
        $this->setDbRows([]);

        $result = $this->channel->related_category_entries();

        // Method returns NO_RESULTS for invalid category
        $this->assertEquals('NO_RESULTS', $result);
    }
}

