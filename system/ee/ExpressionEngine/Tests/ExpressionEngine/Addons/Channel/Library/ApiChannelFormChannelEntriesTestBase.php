<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/ChannelFormLibTestBase.php';

/**
 * Base test class for Api_channel_form_channel_entries tests
 */
abstract class ApiChannelFormChannelEntriesTestBase extends ChannelFormLibTestBase
{
    protected $apiChannelFormChannelEntries;
    protected $channel_form;

    protected function setUp(): void
    {
        parent::setUp();

        // Include the target class
        require_once PATH_ADDONS . 'channel/libraries/api/Api_channel_form_channel_entries.php';

        // Create instance of the class under test
        $this->apiChannelFormChannelEntries = new Api_channel_form_channel_entries();

        // Set up channel_form mock with necessary properties
        $this->setupChannelFormMock();

        // Initialize channel preferences for the API instance
        $this->initializeChannelPreferences();
    }

    protected function setupChannelFormMock()
    {
        // Create channel_form mock
        $channelFormMock = new class {
            public $edit = false;
            public $custom_fields = [];
            public $entry_id = null;
            private $entryMockData = [];

            public function entry($field_name, $default = false)
            {
                // Check if we have mock data for this field
                if (array_key_exists($field_name, $this->entryMockData)) {
                    return $this->entryMockData[$field_name];
                }
                // Default mock behavior - can be overridden in tests
                return $default;
            }

            public function setEntryMock($field_name, $value)
            {
                $this->entryMockData[$field_name] = $value;
            }

            public function clearEntryMocks()
            {
                $this->entryMockData = [];
            }
        };

        // Set the mock in the global ee() singleton
        ee()->setMock('channel_form', $channelFormMock);

        // Also set it as a direct property for convenience
        $this->channel_form = $channelFormMock;

        // Mock API channel categories for parent class
        $this->setupApiChannelCategoriesMock();
    }

    protected function setupApiChannelCategoriesMock()
    {
        // Mock api_channel_categories for parent class
        $apiChannelCategoriesMock = new class {
            public $cat_parents = [];
            public $cat_array = [];
            public $assign_cat_parent = false;

            public function initialize($params = [])
            {
                // Mock initialization - do nothing
            }

            public function fetch_category_parents($categories)
            {
                // Mock category parent fetching
                $this->cat_parents = $categories;
            }
        };

        ee()->setMock('api_channel_categories', $apiChannelCategoriesMock);

        // Mock API channel fields for parent class
        $this->setupApiChannelFieldsMock();
    }

    protected function setupApiChannelFieldsMock()
    {
        // Mock api_channel_fields for parent class
        $apiChannelFieldsMock = new class {
            public $settings = [];
            public $field_types = [];

            public function fetch_custom_channel_fields()
            {
                // Mock fetching custom fields - populate with empty array
                $this->settings = [];
            }

            public function setup_handler($field_id)
            {
                // Mock setup handler
            }

            public function apply($method, $args = [])
            {
                // Mock apply method - return null by default
                return null;
            }
        };

        ee()->setMock('api_channel_fields', $apiChannelFieldsMock);

        // Override the load->library mock to handle API libraries
        $this->setupLoadLibraryMock();

        // Mock text helper functions
        $this->setupTextHelperMock();
    }

    protected function setupLoadLibraryMock()
    {
        $loadMock = new class {
            public function helper($name) {}
            public function library($name) {
                // Handle API library loading
                if ($name === 'api/channel_categories') {
                    // Already mocked above
                } elseif ($name === 'api/channel_fields') {
                    // Already mocked above
                } elseif ($name === 'api/channel_structure') {
                    // Mock channel structure if needed
                    ee()->api_channel_structure = new class {
                        public function get_channel_info($channel_id) {
                            return new class {
                                public function row($field) {
                                    // Return mock channel data with all required fields
                                    $data = [
                                        'channel_url' => '',
                                        'rss_url' => '',
                                        'deft_status' => 'open',
                                        'comment_url' => '',
                                        'comment_system_enabled' => 'y',
                                        'enable_versioning' => 'n',
                                        'max_revisions' => 10,
                                        'channel_title' => 'Test Channel',
                                        'channel_notify' => 'n',
                                        'channel_notify_emails' => '',
                                        'deft_comments' => 'y',
                                        'comment_expiration' => 0
                                    ];
                                    return $data[$field] ?? '';
                                }
                            };
                        }
                    };
                }
            }
            public function model($name) {}
        };

        ee()->setMock('load', $loadMock);
    }

    protected function setupTextHelperMock()
    {
        // Mock text helper functions that the parent class uses
        if (!function_exists('remove_invisible_characters')) {
            function remove_invisible_characters($str) {
                return $str; // Simple mock - just return the string as-is
            }
        }

        if (!function_exists('ascii_to_entities')) {
            function ascii_to_entities($str) {
                return $str; // Simple mock - just return the string as-is
            }
        }
    }



    protected function initializeChannelPreferences()
    {
        // Initialize channel preferences that the parent class expects
        $this->apiChannelFormChannelEntries->c_prefs = [
            'channel_url' => '',
            'rss_url' => '',
            'deft_status' => 'open',
            'comment_url' => '',
            'comment_system_enabled' => 'y',
            'enable_versioning' => 'n',
            'max_revisions' => 10,
            'channel_title' => 'Test Channel',
            'notify_address' => '',
            'deft_comments' => 'y',
            'comment_expiration' => 0
        ];

        // Set channel_id that the API expects
        $this->apiChannelFormChannelEntries->channel_id = 1;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Helper method to set up custom fields for testing
     */
    protected function setupCustomFields(array $fields)
    {
        @$this->channel_form->custom_fields = $fields;
    }

    /**
     * Helper method to set edit mode
     */
    protected function setEditMode($edit = true, $entry_id = 1)
    {
        @$this->channel_form->edit = $edit;
        @$this->channel_form->entry_id = $entry_id;
    }

    /**
     * Helper method to mock database list_fields
     */
    protected function mockDatabaseListFields(array $fields)
    {
        $dbMock = new class($fields) {
            private $fields;

            public function __construct($fields)
            {
                $this->fields = $fields;
            }

            public function list_fields($table)
            {
                return $this->fields;
            }
        };

        ee()->setMock('db', $dbMock);
    }

    /**
     * Helper method to create test data with field values
     */
    protected function createTestData(array $fieldData = [])
    {
        $data = [
            'title' => 'Test Entry',
            'url_title' => 'test-entry',
            'entry_date' => time(),
            'channel_id' => 1
        ];

        // Add field data
        foreach ($fieldData as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Helper method to create custom field definitions
     */
    protected function createCustomField($field_id, $field_name, $field_type = 'text', $isset = true)
    {
        return [
            'field_id' => $field_id,
            'field_name' => $field_name,
            'field_type' => $field_type,
            'isset' => $isset
        ];
    }
}
