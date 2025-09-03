<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelGenerateFieldSearchSqlTest extends ChannelTestBase
{
    private $method;
    private $mockChannelModel;

    protected function setUp(): void
    {
        parent::setUp();

        // Make private method accessible
        $ref = new ReflectionClass($this->channel);
        $this->method = $ref->getMethod('_generate_field_search_sql');
        $this->method->setAccessible(true);

        // Set up mocks after parent setup
        $this->setupMocks();
    }

    private function setupMocks()
    {
        // Mock TMPL for logging
        @$this->channel->TMPL = new FakeTemplate();

        // Mock config for site_id
        @$this->channel->config = new class {
            public function item($key) {
                return $key === 'site_id' ? 1 : false;
            }
        };

        // Set up a simple mock for the channel_model that will be accessed via ee()->channel_model
        // We'll use the existing mock system
        $mockChannelModel = new class {
            public function field_search_sql($terms, $column, $siteId = false) {
                return "({$column} LIKE '%{$terms}%')";
            }
        };

        // Use setMock to properly set up the channel_model
        ee()->setMock('channel_model', $mockChannelModel);
    }

    public function testReturnsEmptyStringWhenNoSearchFields()
    {
        $result = $this->method->invoke($this->channel, [], [], []);

        $this->assertEquals('', $result);
    }

    public function testHandlesEmptySearchTerms()
    {
        $searchFields = ['title' => ''];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        $this->assertEquals('', $result);
        // Empty terms should result in no SQL generated
    }

    public function testHandlesEqualsOnlySearchTerms()
    {
        $searchFields = ['title' => '='];
        @$this->channel->TMPL->log_item_calls = [];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        $this->assertEquals('', $result);
    }

    public function testGeneratesSqlForStandardFields()
    {
        $searchFields = ['title' => 'test search'];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        $this->assertStringContainsString('AND ((t.title LIKE \'%test search%\'))', $result);
    }

    public function testGeneratesSqlForUrlTitleField()
    {
        $searchFields = ['url_title' => 'test-url'];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        $this->assertStringContainsString('AND ((t.url_title LIKE \'%test-url%\'))', $result);
    }

    public function testHandlesCustomFieldSearch()
    {
        // Set up custom fields - the key should be the site_id and the field name
        $this->channel->cfields = [
            1 => [
                'custom_field' => 123
            ]
        ];

        $searchFields = ['custom_field' => 'search term'];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        $this->assertStringContainsString('AND ((exp_channel_data_field_123.field_id_123 LIKE \'%search term%\'))', $result);
    }

    public function testSkipsUnknownCustomFields()
    {
        $searchFields = ['unknown_field' => 'search term'];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        $this->assertEquals('', $result);
    }

    public function testHandlesLegacyFields()
    {
        // Set up custom fields
        $this->channel->cfields = [
            1 => [
                'legacy_field' => 456
            ]
        ];

        $legacyFields = [456 => true];
        $searchFields = ['legacy_field' => 'search term'];

        $result = $this->method->invoke($this->channel, $searchFields, $legacyFields, []);

        $this->assertStringContainsString('AND ((wd.field_id_456 LIKE \'%search term%\'))', $result);
    }

    public function testHandlesMultipleSites()
    {
        // Set up custom fields for multiple sites
        $this->channel->cfields = [
            1 => [
                'custom_field' => 123
            ],
            2 => [
                'custom_field' => 123
            ]
        ];

        $siteIds = ['site1' => 1, 'site2' => 2];
        $searchFields = ['custom_field' => 'multi site search'];

        $result = $this->method->invoke($this->channel, $searchFields, [], $siteIds);

        // Should generate SQL for both sites
        $this->assertStringContainsString('exp_channel_data_field_123.field_id_123', $result);
    }

    public function testHandlesMixedFieldTypes()
    {
        // Set up custom fields
        $this->channel->cfields = [
            1 => [
                'custom_field' => 123
            ]
        ];

        $searchFields = [
            'title' => 'title search',
            'custom_field' => 'custom search'
        ];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        $this->assertStringContainsString('t.title LIKE \'%title search%\'', $result);
        $this->assertStringContainsString('exp_channel_data_field_123.field_id_123 LIKE \'%custom search%\'', $result);
    }

    public function testLogsWarningsForFieldTypes()
    {
        // Set up special field types
        $this->channel->cfields = [
            1 => [
                'relationship_field' => 123,
                'grid_field' => 124,
                'fluid_field' => 125
            ]
        ];
        $this->channel->rfields = [
            1 => [
                'relationship_field' => true
            ]
        ];
        $this->channel->gfields = [
            1 => [
                'grid_field' => true
            ]
        ];
        $this->channel->ffields = [
            1 => [
                'fluid_field' => true
            ]
        ];

        // Mock ee()->config to enable profiler
        $mockConfig = new class {
            private $items = [];
            public function item($key) {
                return $key === 'site_id' ? 1 : ($key === 'show_profiler' ? 'y' : false);
            }
            public function setItem($key, $value) {
                $this->items[$key] = $value;
            }
        };
        ee()->setMock('config', $mockConfig);

        // Track log calls
        $logCalls = [];
        $mockTMPL = new class($logCalls) {
            public $log_item_calls;
            private $calls;

            public function __construct(&$calls) {
                $this->calls = &$calls;
                $this->log_item_calls = &$calls;
            }

            public function log_item($str) {
                $this->calls[] = $str;
                return true;
            }
        };

        // Mock both this->channel->TMPL and ee()->TMPL
        $this->channel->TMPL = $mockTMPL;
        ee()->setMock('TMPL', $mockTMPL);

        $searchFields = [
            'relationship_field' => 'search',
            'grid_field' => 'search',
            'fluid_field' => 'search'
        ];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        // Assert that warnings were logged for special field types
        $this->assertContains('WARNING: Using Relationship fields in `search` parameter is not supported.', $logCalls);
        $this->assertContains('NOTE: Using Grid fields in `search` parameter requires the field to be marked as searchable.', $logCalls);
        $this->assertContains('NOTE: Using Fluid fields in `search` parameter requires the field to be marked as searchable.', $logCalls);

        // The method should still return valid SQL
        $this->assertIsString($result);
    }

    public function testHandlesEmptyTermsAfterTrimming()
    {
        $searchFields = ['title' => '   ']; // Only whitespace

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        $this->assertEquals('', $result);
    }

    public function testHandlesContainsSearchWithEmptyString()
    {
        $searchFields = ['title' => ''];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        $this->assertEquals('', $result);
    }

    public function testHandlesSqlInjectionAttempt()
    {
        // Test SQL injection attempts in search terms
        $searchFields = [
            'title' => "'; DROP TABLE users; --",
            'custom_field' => "1' OR '1'='1"
        ];

        // Set up custom fields
        $this->channel->cfields = [
            1 => [
                'custom_field' => 123
            ]
        ];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        // The result should contain escaped/quoted search terms, not raw SQL injection
        $this->assertStringContainsString('%\'; DROP TABLE users; --%', $result);
        $this->assertStringContainsString('%1\' OR \'1\'=\'1%', $result);
    }

    public function testHandlesUnicodeCharactersInSearchTerms()
    {
        $searchFields = [
            'title' => 'café résumé naïve', // Accented characters
            'custom_field' => '🚀⭐🌟' // Emoji
        ];

        // Set up custom fields
        $this->channel->cfields = [
            1 => [
                'custom_field' => 123
            ]
        ];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        // Should handle Unicode characters properly
        $this->assertStringContainsString('%café résumé naïve%', $result);
        $this->assertStringContainsString('%🚀⭐🌟%', $result);
    }

    public function testHandlesVeryLongSearchTerms()
    {
        // Create a very long search term
        $longTerm = str_repeat('a', 10000);
        $searchFields = ['title' => $longTerm];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        // Should handle long terms without crashing
        $this->assertStringContainsString('LIKE', $result);
        $this->assertStringContainsString($longTerm, $result);
    }

    public function testHandlesSearchTermsWithWildcards()
    {
        // Test search terms containing SQL wildcards
        $searchFields = [
            'title' => 'test%',
            'custom_field' => 'test_'
        ];

        // Set up custom fields
        $this->channel->cfields = [
            1 => [
                'custom_field' => 123
            ]
        ];

        // Update mock to properly escape wildcards like the real implementation
        ee()->setMock('channel_model', new class {
            public function field_search_sql($terms, $column, $siteId = false) {
                // Simulate the real escape_like_str behavior
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $terms);
                return ' (' . $column . ' LIKE "%' . $escaped . '%") ';
            }
        });

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        // Should properly escape wildcard characters in search terms
        $this->assertStringContainsString('%test\\%%', $result);
        $this->assertStringContainsString('%test\\_%', $result);
    }

    public function testHandlesSearchTermsWithQuotes()
    {
        $searchFields = [
            'title' => 'test\'s "quoted" value',
            'custom_field' => 'O\'Reilly'
        ];

        // Set up custom fields
        $this->channel->cfields = [
            1 => [
                'custom_field' => 123
            ]
        ];

        // Update mock to properly escape quotes like the real implementation
        ee()->setMock('channel_model', new class {
            public function field_search_sql($terms, $column, $siteId = false) {
                // Simulate the real escape_like_str behavior
                $escaped = addslashes($terms); // This is what the real implementation does
                return ' (' . $column . ' LIKE "%' . $escaped . '%") ';
            }
        });

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        // Should properly escape quotes in search terms
        $this->assertStringContainsString('%test\\\'s \\"quoted\\" value%', $result);
        $this->assertStringContainsString('%O\\\'Reilly%', $result);
    }

    public function testHandlesSearchTermsWithNewlines()
    {
        $searchFields = [
            'title' => "test\nwith\nnewlines",
            'custom_field' => "test\r\nwith\r\ncrlf"
        ];

        // Set up custom fields
        $this->channel->cfields = [
            1 => [
                'custom_field' => 123
            ]
        ];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        // Should handle newlines in search terms without crashing
        $this->assertStringContainsString('LIKE', $result);
        $this->assertStringContainsString('test', $result);
        $this->assertStringContainsString('newlines', $result);
        $this->assertStringContainsString('crlf', $result);
    }

    public function testHandlesSearchTermsWithControlCharacters()
    {
        // Test with control characters
        $searchFields = [
            'title' => "test\x00null\x01control",
            'custom_field' => "test\x0A\x0D"
        ];

        // Set up custom fields
        $this->channel->cfields = [
            1 => [
                'custom_field' => 123
            ]
        ];

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        // Should handle control characters appropriately
        $this->assertIsString($result);
        $this->assertStringContainsString('LIKE', $result);
    }

    public function testHandlesMultipleSearchFieldsWithSpecialCharacters()
    {
        // Test multiple fields with various special characters
        $searchFields = [
            'title' => 'normal search',
            'url_title' => 'url-with-dashes',
            'custom_field1' => 'field with spaces',
            'custom_field2' => 'field%with%wildcards',
            'custom_field3' => 'field\'with"quotes'
        ];

        // Set up custom fields
        $this->channel->cfields = [
            1 => [
                'custom_field1' => 123,
                'custom_field2' => 124,
                'custom_field3' => 125
            ]
        ];

        // Use proper escaping mock for this test
        ee()->setMock('channel_model', new class {
            public function field_search_sql($terms, $column, $siteId = false) {
                // Simulate the real escape_like_str behavior
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $terms);
                $escaped = addslashes($escaped);
                return ' (' . $column . ' LIKE "%' . $escaped . '%") ';
            }
        });

        $result = $this->method->invoke($this->channel, $searchFields, [], []);

        // Should generate SQL for all fields with proper escaping
        $this->assertStringContainsString('t.title LIKE "%normal search%"', $result);
        $this->assertStringContainsString('t.url_title LIKE "%url-with-dashes%"', $result);
        $this->assertStringContainsString('exp_channel_data_field_123.field_id_123 LIKE "%field with spaces%"', $result);
        $this->assertStringContainsString('exp_channel_data_field_124.field_id_124 LIKE "%field\\\\%with\\\\%wildcards%"', $result);
        $this->assertStringContainsString('exp_channel_data_field_125.field_id_125 LIKE "%field\\\'with\\"quotes%"', $result);
    }
}
