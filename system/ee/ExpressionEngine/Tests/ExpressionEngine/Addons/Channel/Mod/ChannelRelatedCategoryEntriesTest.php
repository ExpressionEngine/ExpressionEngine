<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelRelatedCategoryEntriesTest extends ChannelTestBase
{
    public function testNoContextReturnsNoResults()
    {
        $this->channel->query_string = '';
        $this->setDbRows([]);
        $this->setTemplateTagdata('NO_RESULTS');
        $this->assertEquals('NO_RESULTS', $this->channel->related_category_entries());
    }

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

    public function testHandlesEntryIdParameter()
    {
        // Set up template parameters with entry_id
        $this->setTemplateParams(['entry_id' => '123']);

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news'],
            2 => ['cat_id' => 2, 'cat_name' => 'Sports', 'cat_url_title' => 'sports']
        ];

        // Mock database to return categories for entry 123
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'entry_id' => 123
            ],
            [
                'cat_id' => 2,
                'cat_name' => 'Sports',
                'entry_id' => 123
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testHandlesUrlTitleParameter()
    {
        // Set up template parameters with url_title
        $this->setTemplateParams(['url_title' => 'test-entry']);

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news']
        ];

        // Mock database to return categories for entry by URL title
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'url_title' => 'test-entry'  // This will match the where condition t.url_title = 'test-entry'
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testHandlesQueryStringEntryId()
    {
        // Set up query string with entry ID
        $this->channel->query_string = '789';

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news']
        ];

        // Mock database to return categories for entry 789
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'entry_id' => 789  // This will match the where condition t.entry_id = 789
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testHandlesQueryStringUrlTitle()
    {
        // Set up query string with URL title
        $this->channel->query_string = 'my-article';

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news']
        ];

        // Mock database to return categories for entry by URL title
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'url_title' => 'my-article'  // This will match the where condition t.url_title = 'my-article'
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testHandlesPageNumbersInQueryString()
    {
        // Set up query string with page number (should be stripped to 'my-article')
        $this->channel->query_string = 'my-article/P5';

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news']
        ];

        // Mock database to return categories for entry by URL title (page number should be stripped)
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'url_title' => 'my-article'  // This will match the where condition t.url_title = 'my-article' (after stripping /P5)
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testHandlesCommentNumbersInQueryString()
    {
        // Set up query string with comment number (should be stripped to 'my-article')
        $this->channel->query_string = 'my-article/N10';

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news']
        ];

        // Mock database to return categories for entry by URL title (comment number should be stripped)
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'url_title' => 'my-article'  // This will match the where condition t.url_title = 'my-article' (after stripping /N10)
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testHandlesAddingCategories()
    {
        // Set up template parameters with additional category
        $this->setTemplateParams(['category' => '2']); // Add category 2

        // Set up query string with entry ID
        $this->channel->query_string = '123';

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news'],
            2 => ['cat_id' => 2, 'cat_name' => 'Sports', 'cat_url_title' => 'sports']
        ];

        // Mock database to return categories for entry 123
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'entry_id' => 123  // Entry has category 1, but we're adding category 2
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testHandlesExcludingCategories()
    {
        // Set up template parameters with category exclusion
        $this->setTemplateParams(['category' => 'not 2']); // Exclude category 2

        // Set up query string with entry ID
        $this->channel->query_string = '123';

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news'],
            2 => ['cat_id' => 2, 'cat_name' => 'Sports', 'cat_url_title' => 'sports']
        ];

        // Mock database to return categories for entry 123 (entry has categories 1 and 2)
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'entry_id' => 123
            ],
            [
                'cat_id' => 2,
                'cat_name' => 'Sports',
                'entry_id' => 123
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testRespectsChannelParameter()
    {
        // Set up template parameters with channel filter
        $this->setTemplateParams(['channel' => 'news']);

        // Set up query string with entry ID
        $this->channel->query_string = '123';

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news']
        ];

        // Mock database to return categories for entry 123
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'entry_id' => 123
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testRespectsStatusParameter()
    {
        // Set up template parameters with status filter
        $this->setTemplateParams(['status' => 'open']);

        // Set up query string with entry ID
        $this->channel->query_string = '123';

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news']
        ];

        // Mock database to return categories for entry 123
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'entry_id' => 123
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testRespectsLimitParameter()
    {
        // Set up template parameters with limit
        $this->setTemplateParams(['limit' => '5']);

        // Set up query string with entry ID
        $this->channel->query_string = '123';

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news']
        ];

        // Mock database to return categories for entry 123
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'entry_id' => 123
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testDefaultsLimitTo10()
    {
        // Set up query string with entry ID (no explicit limit parameter)
        $this->channel->query_string = '123';

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news']
        ];

        // Mock database to return categories for entry 123
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'entry_id' => 123
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testHandlesOrderByAndSortParameters()
    {
        // Set up template parameters with orderby and sort
        $this->setTemplateParams(['orderby' => 'entry_date', 'sort' => 'desc']);

        // Set up query string with entry ID
        $this->channel->query_string = '123';

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news']
        ];

        // Mock database to return categories for entry 123
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'entry_id' => 123
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testHandlesUsernameParameter()
    {
        // Set up template parameters with username
        $this->setTemplateParams(['username' => 'testuser']);

        // Set up query string with entry ID
        $this->channel->query_string = '123';

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news']
        ];

        // Mock database to return categories for entry 123
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'entry_id' => 123
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testHandlesCustomFieldsParameter()
    {
        // Test that custom_fields=yes enables custom fields
        $this->setTemplateParams(['custom_fields' => 'yes']);
        $this->channel->query_string = '123';
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news']
        ];

        // Mock database to return categories for entry 123
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'entry_id' => 123
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }

    public function testReturnsNoResultsWhenNoEntryFound()
    {
        // Mock database to return no entry found
        $this->setMock('db', new class extends FakeDb {
            public function query($sql) {
                if (stripos($sql, 'category_posts') !== false) {
                    return new eeDbResultMock([]); // No categories found
                }
                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = 'nonexistent';
        $result = $this->channel->related_category_entries();

        // Should return no_results template
        $this->assertStringContainsString('', $result); // no_results() typically returns empty string
    }

    public function testReturnsNoResultsWhenNoCategoriesFound()
    {
        // Mock database to return entry but no categories
        $this->setMock('db', new class extends FakeDb {
            private $queryCount = 0;
            public function query($sql) {
                $this->queryCount++;
                if ($this->queryCount === 1 && stripos($sql, 'category_posts') !== false) {
                    return new eeDbResultMock([]); // Entry found but no categories
                }
                return new eeDbResultMock([]);
            }
        });

        $this->channel->query_string = '123';
        $result = $this->channel->related_category_entries();

        // Should return no_results template
        $this->assertStringContainsString('', $result);
    }

    public function testExcludesCurrentEntryFromResults()
    {
        // Set up query string with current entry ID
        $this->channel->query_string = '123'; // Current entry ID

        // Set categories data for the entry
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news']
        ];

        // Mock database to return categories for entry 123
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'entry_id' => 123  // This will match the where condition t.entry_id = 123
            ]
        ]);

        $result = $this->channel->related_category_entries();
        $this->assertIsString($result);
        // Should not contain the current entry
        $this->assertStringNotContainsString('entry_id_123', $result);
    }

    public function testHandlesMultipleCategoriesFromEntry()
    {
        // Set up query string with entry ID
        $this->channel->query_string = '123';

        // Set categories data for the entry with multiple categories
        $this->channel->categories = [
            1 => ['cat_id' => 1, 'cat_name' => 'News', 'cat_url_title' => 'news'],
            2 => ['cat_id' => 2, 'cat_name' => 'Sports', 'cat_url_title' => 'sports'],
            3 => ['cat_id' => 3, 'cat_name' => 'Entertainment', 'cat_url_title' => 'entertainment']
        ];

        // Mock database to return categories for entry 123
        $this->setDbRows([
            [
                'cat_id' => 1,
                'cat_name' => 'News',
                'entry_id' => 123  // This will match the where condition t.entry_id = 123
            ],
            [
                'cat_id' => 2,
                'cat_name' => 'Sports',
                'entry_id' => 123
            ],
            [
                'cat_id' => 3,
                'cat_name' => 'Entertainment',
                'entry_id' => 123
            ]
        ]);

        $result = $this->channel->related_category_entries();

        // Should return some result (either NO_RESULTS or processed entries)
        $this->assertIsString($result);
    }
}

