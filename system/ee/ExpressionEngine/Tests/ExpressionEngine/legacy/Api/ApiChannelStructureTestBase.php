<?php

require_once __DIR__ . '/../../../eeObjectMock.php';

/**
 * Base test class for Api_channel_structure tests
 *
 * Provides comprehensive mocking infrastructure for testing the Api_channel_structure class
 * including all EE framework dependencies and database operations.
 */
abstract class ApiChannelStructureTestBase extends \PHPUnit\Framework\TestCase
{
    protected $apiChannelStructure;
    protected $ee_backup;
    protected $post_backup;
    protected $mockDb;
    protected $mockConfig;
    protected $mockLang;
    protected $mockFunctions;
    protected $mockSession;

    protected function setUp(): void
    {
        parent::setUp();

        // Define EE constants that might be needed
        if (!defined('NBS')) {
            define('NBS', '&nbsp;');
        }

        // Backup global state
        $this->ee_backup = $GLOBALS['ee'] ?? null;
        $this->post_backup = $_POST ?? [];

        // Initialize EE mock environment
        $this->setupEeMockEnvironment();

        // Set up basic mocks
        $this->setupBasicMocks();

        // Create the API instance
        $this->createApiInstance();
    }

    protected function tearDown(): void
    {
        // Restore global state
        if ($this->ee_backup !== null) {
            $GLOBALS['ee'] = $this->ee_backup;
        }
        $_POST = $this->post_backup;
    }

    protected function setupEeMockEnvironment()
    {
        // Set up the ee() mock system using TestEnvironment
        global $__EE_TEST_ENV__;
        $__EE_TEST_ENV__ = new TestEnvironment();
    }

    protected function setupBasicMocks()
    {
        // Database mock with additional methods
        $this->mockDb = new class extends eeDbArMock {
            public function where_in($field, $values) {
                $this->whereInConditions[$field] = (array) $values;
                return $this;
            }
            public function select($fields = '*') {
                return $this;
            }
            public function from() {
                return $this;
            }
            public function order_by($field, $direction = '') {
                return $this;
            }
            public function get_where($table, $where = null, $limit = null, $offset = null) {
                // Handle specific table queries
                if ($table === 'field_groups' && $where === ['site_id' => 1]) {
                    // Return a field group for validation
                    return new eeDbResultMock([['group_id' => 1, 'group_name' => 'Default Fields']]);
                }
                if ($table === 'category_groups' && isset($where['group_id'])) {
                    // Return category groups for validation
                    $groupIds = (array) $where['group_id'];
                    $results = [];
                    foreach ($groupIds as $id) {
                        $results[] = ['group_id' => $id, 'group_name' => "Category Group {$id}"];
                    }
                    return new eeDbResultMock($results);
                }
                return $this->get();
            }

            public function list_fields($table) {
                // Return mock field list for channels table
                if ($table === 'channels') {
                    return [
                        'channel_id', 'site_id', 'channel_name', 'channel_title',
                        'channel_url', 'channel_lang', 'total_entries', 'total_comments',
                        'last_entry_date', 'last_comment_date', 'cat_group', 'field_group',
                        'deft_status', 'deft_category', 'search_excerpt', 'deft_comments',
                        'channel_require_membership', 'channel_max_chars', 'channel_html_formatting',
                        'channel_allow_img_urls', 'channel_auto_link_urls', 'comment_url',
                        'comment_system_enabled', 'comment_require_membership', 'comment_moderate',
                        'comment_max_chars', 'comment_timelock', 'comment_require_email',
                        'comment_text_formatting', 'comment_html_formatting', 'comment_allow_img_urls',
                        'comment_auto_link_urls', 'comment_notify', 'comment_notify_authors',
                        'comment_notify_emails', 'comment_expiration', 'search_results_url',
                        'rss_url', 'enable_versioning', 'max_revisions', 'max_entries',
                        'show_button_cluster', 'related_entries', 'channel_hidden',
                        'channel_description', 'channel_display_name'
                    ];
                }
                return [];
            }
        };
        ee()->setMock('db', $this->mockDb);

        // Config mock
        $this->mockConfig = new eeSingletonConfigMock();
        $this->mockConfig->setItem('site_id', 1);
        $this->mockConfig->setItem('xml_lang', 'en');
        $this->mockConfig->setItem('site_name', 'Test Site');
        $this->mockConfig->setItem('site_url', 'https://example.com/');
        $this->mockConfig->setItem('forum_is_installed', 'y');
        $this->mockConfig->setItem('forum_trigger', 'forum');
        $this->mockConfig->setItem('use_category_name', 'y');
        $this->mockConfig->setItem('reserved_category_word', 'category');
        $this->mockConfig->setItem('profile_trigger', 'member');
        ee()->setMock('config', $this->mockConfig);

        // Language mock
        $this->mockLang = new eeLangMock();
        ee()->setMock('lang', $this->mockLang);

        // Functions mock with additional methods
        $this->mockFunctions = new class extends FakeFunctions {
            public function fetch_site_index($a = 0, $b = 0) {
                return 'https://example.com/';
            }
            public function create_url($path = '') {
                if ($path) {
                    return 'https://example.com/' . ltrim($path, '/');
                }
                return 'https://example.com/';
            }
        };
        ee()->setMock('functions', $this->mockFunctions);

        // Localize mock
        $mockLocalize = new class {
            public $now = 0;
            public function __construct() {
                $this->now = time();
            }
            public function now() { return time(); }
            public function format_date($format, $timestamp) {
                return date($format, $timestamp);
            }
            public function string_to_timestamp($str, $localized = true, $date_format = null) {
                return strtotime($str);
            }
        };
        ee()->setMock('localize', $mockLocalize);

        // Session mock
        $this->mockSession = new eeSingletonSessionMock();
        ee()->setMock('session', $this->mockSession);

        // Channel model mock (primary dependency)
        ee()->setMock('channel_model', new FakeChannelModel());

        // Super model mock (for count operations)
        ee()->setMock('super_model', new FakeSuperModel());

        // Channel entries model mock (for delete operations)
        ee()->setMock('channel_entries_model', new FakeChannelEntriesModel());

        // Security mock (filename sanitization)
        ee()->setMock('security', new FakeSecurity());

        // Logger mock (action logging)
        ee()->setMock('logger', new FakeLogger());

        // Load mock (model loading)
        ee()->setMock('load', new FakeLoad());

        // Extensions mock (for hook system)
        $extensions = new stdClass();
        $extensions->active_hook = function($hook) { return false; };
        $extensions->call = function($hook, $data) { return []; };
        ee()->setMock('extensions', $extensions);
        // Also set it directly on the ee() instance
        ee()->extensions = $extensions;

        // Config service mock for ee('Config')
        $configService = new stdClass();
        $configService->getFile = function() {
            return new class {
                public function getBoolean($key) {
                    return $key === 'allow_php' ? false : null;
                }
            };
        };
        ee()->setMock('Config', $configService);

        // Permission service mock for ee('Permission')
        $permissionService = new stdClass();
        $permissionService->isSuperAdmin = function() { return false; };
        ee()->setMock('Permission', $permissionService);
        // Also set it directly on the ee() instance
        ee()->Permission = $permissionService;
    }

    protected function createApiInstance()
    {
        // Include the required files
        require_once APPPATH . '../legacy/libraries/Api.php';
        require_once APPPATH . '../legacy/libraries/api/Api_channel_structure.php';

        // Create the API instance
        $this->apiChannelStructure = new Api_channel_structure();
    }

    /**
     * Get sample channel data for testing
     */
    protected function getValidChannelData()
    {
        return [
            'channel_id' => 1,
            'site_id' => 1,
            'channel_name' => 'test_channel',
            'channel_title' => 'Test Channel',
            'channel_url' => 'https://example.com/channel/',
            'channel_lang' => 'en',
            'total_entries' => 0,
            'total_comments' => 0,
            'last_entry_date' => 0,
            'last_comment_date' => 0,
            'cat_group' => '1',
            'field_group' => 1,
            'deft_status' => 'open',
            'deft_category' => '',
            'search_excerpt' => '',
            'deft_comments' => 'y',
            'channel_require_membership' => 'n',
            'channel_max_chars' => 0,
            'channel_html_formatting' => 'all',
            'channel_allow_img_urls' => 'y',
            'channel_auto_link_urls' => 'y',
            'comment_url' => 'https://example.com/comments/',
            'comment_system_enabled' => 'y',
            'comment_require_membership' => 'n',
            'comment_moderate' => 'n',
            'comment_max_chars' => 5000,
            'comment_timelock' => 0,
            'comment_require_email' => 'y',
            'comment_text_formatting' => 'xhtml',
            'comment_html_formatting' => 'safe',
            'comment_allow_img_urls' => 'n',
            'comment_auto_link_urls' => 'y',
            'comment_notify' => 'n',
            'comment_notify_authors' => 'n',
            'comment_notify_emails' => '',
            'comment_expiration' => 0,
            'search_results_url' => 'https://example.com/search/results/',
            'rss_url' => 'https://example.com/rss/',
            'enable_versioning' => 'n',
            'max_revisions' => 10,
            'max_entries' => 0,
            'show_button_cluster' => 'y',
            'related_entries' => 'n',
            'channel_hidden' => 'n',
            'channel_description' => 'Test channel description',
            'channel_display_name' => 'Test Channel',
        ];
    }

    /**
     * Get sample channel data for creation (without auto-generated fields)
     */
    protected function getChannelCreationData()
    {
        return [
            'channel_name' => 'new_test_channel',
            'channel_title' => 'New Test Channel',
            'site_id' => 1,
            'field_group' => 1,
            'cat_group' => '1',
            'deft_status' => 'open',
            'channel_description' => 'New test channel',
        ];
    }

    /**
     * Mock channel data in the database
     */
    protected function mockChannelData($channels = [])
    {
        if (empty($channels)) {
            $channels = [1 => $this->getValidChannelData()];
        }

        // Get the current channel model mock and set the channels
        $channelModel = ee('channel_model');
        $channelModel->setChannels($channels);
    }

    /**
     * Mock super model counts for duplicate checking
     */
    protected function mockSuperModelCount($table, $where, $count)
    {
        $superModel = ee('super_model');
        $superModel->setCount($table, $where, $count);
    }

    /**
     * Mock channel entries for delete operations
     */
    protected function mockChannelEntries($entries = [])
    {
        if (empty($entries)) {
            $entries = [
                ['entry_id' => 1, 'author_id' => 1],
                ['entry_id' => 2, 'author_id' => 2],
            ];
        }

        $channelEntriesModel = ee('channel_entries_model');
        $channelEntriesModel->setEntries($entries);
    }

    /**
     * Mock template API for channel creation with templates
     */
    protected function mockTemplateApi()
    {
        $templateApi = new stdClass();
        $templateApi->create_template_group = function($data, $duplicate_group = false) {
            return 1; // Return a mock group ID
        };
        $templateApi->reserved_names = ['act', 'css', 'js', 'xml', 'feed', 'rss']; // Add reserved names
        ee()->setMock('api_template_structure', $templateApi);
    }

    /**
     * Helper to reset the API instance
     */
    protected function resetApiInstance()
    {
        $this->apiChannelStructure = new Api_channel_structure();
    }
}

// Mock classes for testing

if (!class_exists('FakeChannelModel')) {
    class FakeChannelModel
    {
        public $channels = [];
        public $lastInsertedChannelId = 1;

        public function get_channel_info($channel_id) {
            if (isset($this->channels[$channel_id])) {
                return new eeDbResultMock([$this->channels[$channel_id]]);
            }
            return new eeDbResultMock([]);
        }

        public function get_channels($site_id) {
            $filtered = [];
            foreach ($this->channels as $channel) {
                if ($channel['site_id'] == $site_id) {
                    $filtered[] = $channel;
                }
            }
            return new eeDbResultMock($filtered);
        }

        public function create_channel($data) {
            $data['channel_id'] = $this->lastInsertedChannelId++;
            $this->channels[$data['channel_id']] = $data;
            return $data['channel_id'];
        }

        public function update_channel($data, $channel_id) {
            if (isset($this->channels[$channel_id])) {
                $this->channels[$channel_id] = array_merge($this->channels[$channel_id], $data);
                return true;
            }
            return false;
        }

        public function delete_channel($channel_id, $entries, $authors) {
            if (isset($this->channels[$channel_id])) {
                unset($this->channels[$channel_id]);
                return true;
            }
            return false;
        }

        public function update_comments_allowed($channel_id, $allowed) {
            if (isset($this->channels[$channel_id])) {
                $this->channels[$channel_id]['comment_system_enabled'] = $allowed;
                return true;
            }
            return false;
        }

        public function update_comment_expiration($channel_id, $expiration) {
            if (isset($this->channels[$channel_id])) {
                $this->channels[$channel_id]['comment_expiration'] = $expiration;
                return true;
            }
            return false;
        }

        public function clear_versioning_data($channel_id) {
            // Mock implementation - just return success
            return true;
        }

        public function setChannels($channels) {
            $this->channels = $channels;
        }
    }
}

if (!class_exists('FakeChannelEntriesModel')) {
    class FakeChannelEntriesModel
    {
        public $entries = [];

        public function get_entries($channel_id, $fields) {
            $filtered = [];
            foreach ($this->entries as $entry) {
                // Mock filtering by channel_id
                $filtered[] = $entry;
            }
            return new eeDbResultMock($filtered);
        }

        public function setEntries($entries) {
            $this->entries = $entries;
        }
    }
}

if (!class_exists('FakeSuperModel')) {
    class FakeSuperModel
    {
        public $counts = [];

        public function count($table, $where = []) {
            $key = $table . '_' . md5(serialize($where));
            return $this->counts[$key] ?? 0;
        }

        public function setCount($table, $where, $count) {
            $key = $table . '_' . md5(serialize($where));
            $this->counts[$key] = $count;
        }
    }
}

if (!class_exists('FakeFunctions')) {
    class FakeFunctions
    {
        public function fetch_site_index($add_slash = false) {
            return 'https://example.com/';
        }

        public function create_url($segment, $add_slash = false) {
            return 'https://example.com/' . $segment;
        }
    }
}

if (!class_exists('FakeSecurity')) {
    class FakeSecurity
    {
        public function sanitize_filename($filename) {
            return preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        }
    }
}

if (!class_exists('FakeLogger')) {
    class FakeLogger
    {
        public $logged_messages = [];

        public function log_action($message) {
            // Store the logged message for testing
            $this->logged_messages[] = $message;
            return true;
        }
    }
}

if (!class_exists('FakeLang')) {
    class FakeLang
    {
        public function line($key) {
            // Return mock language strings
            $strings = [
                'channel_created' => 'Channel Created',
                'channel_deleted' => 'Channel Deleted',
                'no_channel_title' => 'No channel title provided',
                'no_channel_name' => 'No channel name provided',
                'invalid_short_name' => 'Invalid short name',
                'taken_channel_name' => 'Channel name already taken',
                'invalid_channel_id' => 'Invalid channel ID',
                'channel_id_required' => 'Channel ID is required',
                'group_required' => 'Group name is required',
                'illegal_characters' => 'Illegal characters in name',
                'reserved_name' => 'Reserved name',
                'template_group_taken' => 'Template group name taken',
                'invalid_category_group' => 'Invalid category group',
                'invalid_url_title_prefix' => 'Invalid URL title prefix',
            ];
            return $strings[$key] ?? $key;
        }

        public function loadfile($file) {
            // Mock loading language file
            return true;
        }
    }
}

if (!class_exists('FakeLoad')) {
    class FakeLoad
    {
        public function model($model) {
            // Mock model loading
            return true;
        }

        public function library($library) {
            // Mock library loading
            return true;
        }
    }
}

if (!class_exists('FakeSession')) {
    class FakeSession
    {
        public $userdata = [
            'member_id' => 1,
            'group_id' => 1,
            'username' => 'admin'
        ];

        public function userdata($key, $default = false) {
            return $this->userdata[$key] ?? $default;
        }

        public function set_userdata($key, $value) {
            $this->userdata[$key] = $value;
        }
    }
}

if (!class_exists('FakeDbForChannels')) {
    class FakeDbForChannels extends eeDbArMock
    {
        public function list_fields($table) {
            // Mock channel table fields
            if ($table === 'channels') {
                return [
                    'channel_id', 'site_id', 'channel_name', 'channel_title',
                    'channel_url', 'channel_lang', 'total_entries', 'total_comments',
                    'last_entry_date', 'last_comment_date', 'cat_group', 'field_group',
                    'deft_status', 'deft_category', 'search_excerpt', 'deft_comments',
                    'channel_require_membership', 'channel_max_chars', 'channel_html_formatting',
                    'channel_allow_img_urls', 'channel_auto_link_urls', 'comment_url',
                    'comment_system_enabled', 'comment_require_membership', 'comment_moderate',
                    'comment_max_chars', 'comment_timelock', 'comment_require_email',
                    'comment_text_formatting', 'comment_html_formatting', 'comment_allow_img_urls',
                    'comment_auto_link_urls', 'comment_notify', 'comment_notify_authors',
                    'comment_notify_emails', 'comment_expiration', 'search_results_url',
                    'rss_url', 'enable_versioning', 'max_revisions', 'max_entries',
                    'show_button_cluster', 'related_entries', 'channel_hidden',
                    'channel_description', 'channel_display_name'
                ];
            }
            return parent::list_fields($table);
        }

        public function get_where($table, $where = null, $limit = null, $offset = null) {
            // Mock field_groups table
            if ($table === 'field_groups') {
                return new eeDbResultMock([
                    ['group_id' => 1, 'group_name' => 'Default Fields', 'site_id' => 1]
                ]);
            }
            return parent::get_where($table, $where, $limit, $offset);
        }
    }
}
