<?php

use PHPUnit\Framework\TestCase;

/**
 * Base class for Api_channel_entries tests
 *
 * Provides shared setup and mocks for testing the Api_channel_entries class
 */
abstract class ChannelApiTestBase extends TestCase
{
    protected $api;
    protected $mockDb;
    protected $mockSession;
    protected $mockConfig;
    protected $mockLang;
    protected $mockFunctions;
    protected $mockApiChannelStructure;
    protected $mockApiChannelCategories;
    protected $mockApiChannelFields;
    protected $mockPermission;

    protected function setUp(): void
    {
        parent::setUp();

        // Include the Api class and Api_channel_entries
        require_once APPPATH . 'libraries/Api.php';
        require_once APPPATH . 'libraries/api/Api_channel_entries.php';

        // Set up the ee() mock system
        global $__EE_TEST_ENV__;
        $__EE_TEST_ENV__ = new TestEnvironment();

        // Initialize basic EE mocks
        $this->setupBasicEeMocks();

        // Initialize API-specific mocks
        $this->setupApiMocks();

        // Create Api_channel_entries instance
        $this->api = new Api_channel_entries();
    }

    protected function setupBasicEeMocks()
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
                return $this->get();
            }
        };
        ee()->setMock('db', $this->mockDb);

        // Session mock
        $this->mockSession = new eeSingletonSessionMock();

        // Extend the mock session with getMember method
        $mockSessionWithMember = new class($this->mockSession) extends eeSingletonSessionMock {
            private $baseSession;

            public function __construct($baseSession) {
                $this->baseSession = $baseSession;
            }

            public function getMember() {
                return new class {
                    public function getAssignedStatuses() {
                        return new class {
                            public function getDictionary($key, $value) {
                                return ['1' => 'open', '2' => 'closed'];
                            }
                        };
                    }
                };
            }

            // Delegate other methods to base session
            public function __call($method, $args) {
                if (method_exists($this->baseSession, $method)) {
                    return call_user_func_array([$this->baseSession, $method], $args);
                }
                return parent::__call($method, $args);
            }
        };

        $this->mockSession = $mockSessionWithMember;
        ee()->setMock('session', $this->mockSession);

        // Config mock
        $this->mockConfig = new eeSingletonConfigMock();
        ee()->setMock('config', $this->mockConfig);

        // Language mock
        $this->mockLang = new eeLangMock();
        ee()->setMock('lang', $this->mockLang);

        // Functions mock with additional methods
        $this->mockFunctions = new class extends FakeFunctions {
            public function fetch_assigned_channels() {
                return [1]; // Return channel ID 1 as assigned by default
            }
            public function clear_caching($type = 'all', $additional = '') {
                return true;
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

        // Input mock
        $mockInput = new class {
            public function ip_address() { return '127.0.0.1'; }
            public function get_post($key) { return null; }
            public function get($key) { return null; }
        };
        ee()->setMock('input', $mockInput);

        // Stats mock
        $mockStats = new class {
            public function update_channel_stats($channel_id) { return true; }
        };
        ee()->setMock('stats', $mockStats);

        // Load mock
        $mockLoad = new class {
            public function model($name) { return null; }
            public function helper($name) { return null; }
            public function library($name) {
                if ($name === 'notifications') {
                    return new class {
                        public function send_admin_notification($email, $channel_id, $entry_id) {
                            return true;
                        }
                    };
                }
            }
        };
        ee()->setMock('load', $mockLoad);
    }

    protected function setupApiMocks()
    {
        // Channel structure API mock
        $this->mockApiChannelStructure = new class {
            public function get_channel_info($channel_id) {
                return new class {
                    public function row($field) {
                        $data = [
                            'channel_url' => 'http://example.com/channel/',
                            'rss_url' => 'http://example.com/rss/',
                            'deft_status' => 'open',
                            'comment_url' => 'http://example.com/comments/',
                            'comment_system_enabled' => 'y',
                            'enable_versioning' => 'n',
                            'max_revisions' => 10,
                            'channel_title' => 'Test Channel',
                            'channel_notify' => 'n',
                            'channel_notify_emails' => ''
                        ];
                        return $data[$field] ?? null;
                    }
                    public function num_rows() { return 1; }
                };
            }
        };
        ee()->setMock('api_channel_structure', $this->mockApiChannelStructure);

        // Channel categories API mock
        $this->mockApiChannelCategories = new class {
            public $cat_parents = [];
            public $assign_cat_parent = false;
            public function initialize($params) { return $this; }
            public function fetch_category_parents($categories) { return $this; }
        };
        ee()->setMock('api_channel_categories', $this->mockApiChannelCategories);

        // Channel fields API mock
        $this->mockApiChannelFields = new class {
            public $settings = [];
            public function fetch_custom_channel_fields() { return []; }
            public function apply($method, $args = []) { return null; }
            public function setup_handler($field_id) { return false; }
            public function get_module_methods($methods, $params) { return false; }
        };
        ee()->setMock('api_channel_fields', $this->mockApiChannelFields);

        // Permission mock
        $this->mockPermission = new class {
            public function isSuperAdmin() { return false; }
            public function has($permission) { return false; }
            public function can($permission) { return false; }
            public function getAssignedChannels() { return [1]; }
        };
        ee()->setMock('Permission', $this->mockPermission);

        // Extensions mock
        $mockExtensions = new class {
            public $end_script = false;
            public function active_hook($hook) { return false; }
            public function call($hook, $params = null) { return null; }
        };
        ee()->setMock('extensions', $mockExtensions);

        // Model mock
        $mockModel = new class {
            public function get($model, $ids) {
                $mockStatuses = new class {
                    public function getDictionary($key, $value) {
                        return ['open' => 'open', 'closed' => 'closed', 'draft' => 'draft'];
                    }
                };

                $mockCustomFields = new class {
                    public function asArray() { return []; }
                };

                $mockChannel = new class($mockStatuses, $mockCustomFields) {
                    public $Statuses;
                    private $customFields;

                    public function __construct($statuses, $customFields) {
                        $this->Statuses = $statuses;
                        $this->customFields = $customFields;
                    }

                    public function getAllCustomFields() {
                        return $this->customFields;
                    }
                };

                return new class($mockChannel) {
                    private $mockChannel;

                    public function __construct($channel) {
                        $this->mockChannel = $channel;
                    }

                    public function delete() { return true; }
                    public function first() { return $this->mockChannel; }
                };
            }
        };
        ee()->setMock('Model', $mockModel);

        // Format service mock
        $mockFormat = new class {
            public function make($type, $content) {
                return new class($content) {
                    private $content;
                    public function __construct($content) { $this->content = $content; }
                    public function urlSlug() {
                        // Simple URL slug implementation for testing
                        $this->content = strip_tags($this->content);
                        $this->content = strtolower($this->content);
                        $this->content = preg_replace('/[^a-z0-9\-_]/', '-', $this->content);
                        $this->content = preg_replace('/-+/', '-', $this->content);
                        $this->content = trim($this->content, '-');
                        return $this->content;
                    }
                    public function compile() {
                        return $this->content;
                    }
                };
            }
        };
        ee()->setMock('Format', $mockFormat);

        // Legacy API mock
        $mockLegacyApi = new class {
            public function instantiate($api_name) {
                if ($api_name === 'channel_structure') {
                    return ee()->api_channel_structure;
                } elseif ($api_name === 'channel_categories') {
                    return ee()->api_channel_categories;
                } elseif ($api_name === 'channel_fields') {
                    return ee()->api_channel_fields;
                }
                return null;
            }
        };
        ee()->legacy_api = $mockLegacyApi;
    }

    protected function tearDown(): void
    {
        // Reset mocks
        if (isset($GLOBALS['__EE_TEST_ENV__'])) {
            $GLOBALS['__EE_TEST_ENV__']->mocks = [];
        }

        parent::tearDown();
    }

    /**
     * Helper method to set up authenticated user session
     */
    protected function setupAuthenticatedUser($member_id = 1, $group_id = 1)
    {
        $this->mockSession->setUserdata('member_id', $member_id);
        $this->mockSession->setUserdata('group_id', $group_id);
    }

    /**
     * Helper method to set up channel permissions
     */
    protected function setupChannelPermissions($channel_id = 1, $can_edit = true, $can_delete = true)
    {
        $this->mockPermission = new class($channel_id, $can_edit, $can_delete) {
            private $channel_id;
            private $can_edit;
            private $can_delete;

            public function __construct($channel_id, $can_edit, $can_delete) {
                $this->channel_id = $channel_id;
                $this->can_edit = $can_edit;
                $this->can_delete = $can_delete;
            }

            public function isSuperAdmin() { return false; }
            public function has($permission) {
                if (strpos($permission, 'can_delete_self_entries_channel_id_' . $this->channel_id) !== false) {
                    return $this->can_delete;
                }
                if (strpos($permission, 'can_delete_all_entries_channel_id_' . $this->channel_id) !== false) {
                    return $this->can_delete;
                }
                if (strpos($permission, 'can_edit_other_entries') !== false) {
                    return $this->can_edit;
                }
                return false;
            }
            public function can($permission) { return $this->has($permission); }
            public function getAssignedChannels() { return [$this->channel_id]; }
        };
        ee()->setMock('Permission', $this->mockPermission);
    }

    /**
     * Helper method to mock channel entries model
     */
    protected function setupChannelEntriesModel($entry_exists = true, $author_id = 1)
    {
        $mockModel = new class($entry_exists, $author_id) {
            private $entry_exists;
            private $author_id;

            public function __construct($entry_exists, $author_id) {
                $this->entry_exists = $entry_exists;
                $this->author_id = $author_id;
            }

            public function get_entry($entry_id) {
                if ($this->entry_exists) {
                    return new class($this->author_id) {
                        private $author_id;
                        public function __construct($author_id) { $this->author_id = $author_id; }
                        public function num_rows() { return 1; }
                        public function row($field) { return $this->author_id; }
                    };
                } else {
                    return new class {
                        public function num_rows() { return 0; }
                        public function row($field) { return null; }
                    };
                }
            }
        };

        // Mock the model loading
        $mockLoad = ee()->load;
        $originalLibrary = $mockLoad->library ?? null;
        $mockLoad->model = function($name) use ($mockModel) {
            if ($name === 'channel_entries_model') {
                return $mockModel;
            }
            return null;
        };
    }

    /**
     * Helper method to create mock entry data
     */
    protected function createMockEntryData($overrides = [])
    {
        return array_merge([
            'channel_id' => 1,
            'site_id' => 1,
            'author_id' => 1,
            'title' => 'Test Entry',
            'url_title' => 'test-entry',
            'entry_date' => time(),
            'edit_date' => time(),
            'expiration_date' => 0,
            'comment_expiration_date' => 0,
            'status' => 'open',
            'allow_comments' => 'y',
            'versioning_enabled' => 'n',
            'cp_call' => false
        ], $overrides);
    }
}
