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
        $this->channel->TMPL = new FakeTemplate();

        // Mock config for site_id
        $this->channel->config = new class {
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
        $this->channel->TMPL->log_item_calls = [];

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
}
