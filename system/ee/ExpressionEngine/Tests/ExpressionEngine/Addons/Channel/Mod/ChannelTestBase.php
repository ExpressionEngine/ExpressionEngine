<?php

use PHPUnit\Framework\TestCase;

// Bootstrap minimal EE environment so we can include the real class
if (!defined('APP_VER')) {
    define('APP_VER', '7.5.14');
}
if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
// Determine addons path relative to this file when core bootstrap is not used
$__addons = (defined('SYSPATH') ? (SYSPATH . 'ee/ExpressionEngine/Addons/') : (__DIR__ . '/../../../../Addons/'));
if (!defined('PATH_ADDONS')) {
    define('PATH_ADDONS', $__addons);
}
if (!defined('PATH_PRO_ADDONS')) {
    define('PATH_PRO_ADDONS', PATH_ADDONS);
}
if (!defined('PATH_MOD')) {
    define('PATH_MOD', PATH_ADDONS);
}
if (!defined('REQ')) {
    define('REQ', 'CP');
}

// Provide a global ee() accessor backed by a mutable stdClass
if (!function_exists('ee')) {
    function ee($mock = '') {
        global $__EE_TEST_ENV__;
        if ($mock && isset($__EE_TEST_ENV__->mocks[$mock])) {
            return $__EE_TEST_ENV__->mocks[$mock];
        }
        return $__EE_TEST_ENV__;
    }
}

// Include the real Channel class (and its requires)
require_once rtrim(PATH_ADDONS, '/') . '/channel/mod.channel.php';



abstract class ChannelTestBase extends TestCase
{
    protected $channel;

    protected function setUp(): void
    {
        // If running under core test bootstrap, use its ee() mock container
        if (function_exists('ee') && method_exists(ee(), 'setMock')) {
            // Template mock
            $fakeTemplate = new FakeTemplate();
            $fakeTemplate->tagproper = 'channel:entries';
            ee()->setMock('TMPL', $fakeTemplate);

            // Config mock (use provided mock's public items for convenience)
            ee()->setMock('config', new FakeConfig());
            ee()->config->items = [
                'site_id' => 1,
                'reserved_category_word' => 'category',
                'charset' => 'UTF-8',
                'use_category_name' => 'n',
            ];
            // Functions mock
            ee()->setMock('functions', new FakeFunctions());
            // DB mock (core provides eeDbArMock with setRows, but ensure it's present)
            ee()->setMock('db', new FakeDb());
            // URI mock for Channel constructor
            ee()->setMock('uri', new class {
                public $page_query_string = '';
                public $query_string = '';
                public $uri_string = '';
                public function uri_string() { return $this->uri_string; }
            });
            // Cache mock for potential cache calls
            ee()->setMock('cache', new class {
                public function get($key){ return false; }
                public function save($key, $val, $ttl = 0){ return true; }
            });
            // Localize mock for date/time functions
            ee()->setMock('localize', new class {
                public $now = 0;
                public function __construct() { $this->now = time(); }
                public function localize_month($month) { return $month; }
                public function string_to_timestamp($human_string, $localized = true, $date_format = null) {
                    // Simple mock implementation - return current timestamp
                    return time();
                }
            });
            // Session mock with caching API
            ee()->setMock('session', new class {
                public $cache = [];
                public $userdata_values = [];
                public function cache($class, $key) { return $this->cache[$class][$key] ?? false; }
                public function set_cache($class, $key, $value) { $this->cache[$class][$key] = $value; }
                public function userdata($key, $default = false) {
                    return $this->userdata_values[$key] ?? $default;
                }
                public function set_userdata($key, $value) {
                    $this->userdata_values[$key] = $value;
                }
            });
            // Extensions mock
            ee()->setMock('extensions', new class {
                public $hooks = [];
                public function active_hook($name) { return $this->hooks[$name]['active'] ?? false; }
                public function call($name, $arg) { return $this->hooks[$name]['return'] ?? null; }
            });
            // Channel fields API mock
            ee()->setMock('api_channel_fields', new class {
                public $settings = [];
                public $field_type;
                public function set_settings($id, $settings) { $this->settings[$id] = $settings; }
                public function setup_handler($id) { return false; }
                public function apply($method, $args) { return null; }
                public function check_method_exists($method) { return false; }
                public function fetch_custom_channel_fields() {
                    return [
                        'custom_channel_fields' => [],
                        'date_fields' => [],
                        'relationship_fields' => [],
                        'members_fields' => [],
                        'grid_fields' => [],
                        'pair_custom_fields' => [],
                        'fluid_field_fields' => [],
                        'toggle_fields' => []
                    ];
                }
                public function fetch_custom_member_fields() {
                    return [];
                }
                public $custom_member_field_pairs = [];
            });
            // Input mock
            ee()->setMock('input', new class {
                public function get_post($item) { return null; }
                public function get($item) { return null; }
            });
            // Lang mock
            ee()->setMock('lang', new class {
                public function load($item) { return; }
                public function loadfile($name) { return; }
                public function line($key) { return $key; }
            });
            // Pagination mock for Channel constructor
            ee()->setMock('pagination', new class {
                public function create(){
                    return new class {
                        public $per_page = 10;
                        public $total_rows = 0;
                        public $field_pagination = false;
                        public $offset = 0;
                        public $paginate = false;
                    };
                }
            });

            // Loader mock to satisfy add_package_path and library calls
            ee()->setMock('load', new class {
                public function add_package_path($path) { /* no-op */ }
                public function helper($name) {
                    if ($name === 'date') {
                        // Define timezones function for testing
                        if (!function_exists('timezones')) {
                            function timezones() {
                                return [
                                    'UTC' => 0,
                                    'America/New_York' => -5, // Eastern Time UTC-5
                                    'Europe/London' => 0,
                                    'Asia/Tokyo' => 9, // UTC+9
                                    'America/Los_Angeles' => -8, // Pacific Time UTC-8
                                    'America/Chicago' => -6, // Central Time UTC-6
                                ];
                            }
                        }
                    }
                    if ($name === 'segment') {
                        // Load segment helper functions for testing
                        if (!function_exists('parse_category')) {
                            require_once PATH_ADDONS . '../../legacy/helpers/segment_helper.php';
                        }
                    }
                    /* no-op for other helpers */
                }
                public function model($name) {
                    if ($name === 'category_model') {
                        // Mock category model for parse_category function
                        if (!isset(ee()->category_model)) {
                            ee()->setMock('category_model', new class {
                                public function get_category_id($category_name) {
                                    // Return a mock category ID for testing
                                    return 1;
                                }
                            });
                        }
                    }
                    /* no-op for other models */
                }
                public function library($name)
                {
                    if ($name === 'file_field') {
                        $obj = new class { public function parse_string($s){ return $s; } };
                        if (function_exists('ee') && method_exists(ee(), 'setMock')) {
                            ee()->setMock('file_field', $obj);
                        } else {
                            ee()->file_field = $obj;
                        }
                    }
                    if ($name === 'pagination') {
                        @ee()->pagination = new class {
                            public function create(){
                                return new class {
                                    public $per_page = 10;
                                    public $total_rows = 0;
                                    public $field_pagination = false;
                                    public $offset = 0;
                                    public $paginate = false;
                                };
                            }
                        };
                    }
                    if ($name === 'api') {
                        ee()->legacy_api = new class { public function instantiate($name) {} };
                    }
                    if ($name === 'typography') {
                        ee()->typography = new FakeTypography();
                    }
                }
            });
        } else {
            // Fallback minimal env when running outside core bootstrap
            global $__EE_TEST_ENV__;
            $__EE_TEST_ENV__ = new TestEnvironment();
            $__EE_TEST_ENV__->TMPL = new FakeTemplate();
            $__EE_TEST_ENV__->config = new FakeConfig();
            $__EE_TEST_ENV__->functions = new FakeFunctions();
            $__EE_TEST_ENV__->db = new FakeDb();
            $__EE_TEST_ENV__->uri = new class {
                public $page_query_string = '';
                public $query_string = '';
                public $uri_string = '';
                public function uri_string() { return $this->uri_string; }
            };
            $__EE_TEST_ENV__->localize = new class {
                public $now = 0;
                public function __construct() { $this->now = time(); }
                public function localize_month($month) { return $month; }
                public function string_to_timestamp($human_string, $localized = true, $date_format = null) {
                    // Simple mock implementation - return current timestamp
                    return time();
                }
            };
            $__EE_TEST_ENV__->session = new class {
                public $cache = [];
                public $userdata_values = [];
                public function cache($class, $key) { return $this->cache[$class][$key] ?? false; }
                public function set_cache($class, $key, $value) { $this->cache[$class][$key] = $value; }
                public function userdata($key, $default = false) {
                    return $this->userdata_values[$key] ?? $default;
                }
                public function set_userdata($key, $value) {
                    $this->userdata_values[$key] = $value;
                }
            };
            $__EE_TEST_ENV__->lang = new class {
                public function load($item) { return; }
                public function loadfile($name) { return; }
                public function line($key) { return $key; }
            };
            $__EE_TEST_ENV__->extensions = new class {
                public $hooks = [];
                public function active_hook($name) { return $this->hooks[$name]['active'] ?? false; }
                public function call($name, $arg) { return $this->hooks[$name]['return'] ?? null; }
            };
            $__EE_TEST_ENV__->api_channel_fields = new class {
                public $settings = [];
                public function set_settings($id, $settings) { $this->settings[$id] = $settings; }
                public function setup_handler($id) { return false; }
                public function apply($method, $args) { return null; }
                public function check_method_exists($method) { return false; }
                public function fetch_custom_channel_fields() {
                    return [
                        'custom_channel_fields' => [],
                        'date_fields' => [],
                        'relationship_fields' => [],
                        'members_fields' => [],
                        'grid_fields' => [],
                        'pair_custom_fields' => [],
                        'fluid_field_fields' => [],
                        'toggle_fields' => []
                    ];
                }
                public function fetch_custom_member_fields() {
                    return [];
                }
                public $custom_member_field_pairs = [];
            };
            $__EE_TEST_ENV__->load = new class {
                public function add_package_path($path) { /* no-op for tests */ }
                public function helper($name) {
                    if ($name === 'date') {
                        // Define timezones function for testing
                        if (!function_exists('timezones')) {
                            function timezones() {
                                return [
                                    'UTC' => 0,
                                    'America/New_York' => -5, // Eastern Time UTC-5
                                    'Europe/London' => 0,
                                    'Asia/Tokyo' => 9, // UTC+9
                                    'America/Los_Angeles' => -8, // Pacific Time UTC-8
                                    'America/Chicago' => -6, // Central Time UTC-6
                                ];
                            }
                        }
                    }
                    if ($name === 'segment') {
                        // Load segment helper functions for testing
                        if (!function_exists('parse_category')) {
                            require_once PATH_ADDONS . '../../legacy/helpers/segment_helper.php';
                        }
                    }
                    /* no-op for other helpers */
                }
                public function model($name) {
                    if ($name === 'category_model') {
                        // Mock category model for parse_category function
                        if (!isset(ee()->category_model)) {
                            ee()->setMock('category_model', new class {
                                public function get_category_id($category_name) {
                                    // Return a mock category ID for testing
                                    return 1;
                                }
                            });
                        }
                    }
                    /* no-op for other models */
                }
                public function library($name)
                {
                    if ($name === 'file_field') {
                        $obj = new class { public function parse_string($s) { return $s; } };
                        if (function_exists('ee') && method_exists(ee(), 'setMock')) {
                            ee()->setMock('file_field', $obj);
                        } else {
                            ee()->file_field = $obj;
                        }
                        return;
                    }
                    if ($name === 'pagination') {
                        @ee()->pagination = new class {
                            public function create(){ return new class {}; }
                        };
                        return;
                    }
                    if ($name === 'api') {
                        ee()->legacy_api = new class {
                            public function instantiate($name) { /* set up by tests if needed */ }
                        };
                        return;
                    }
                    if ($name === 'typography') {
                        ee()->typography = new FakeTypography();
                        return;
                    }
                }
            };
            $__EE_TEST_ENV__->config->items = [
                'site_id' => 1,
                'reserved_category_word' => 'category',
                'charset' => 'UTF-8',
                'use_category_name' => 'n',
            ];
            $__EE_TEST_ENV__->pagination = new class {
                public function create(){
                    return new class {
                        public $per_page = 10;
                        public $total_rows = 0;
                        public $field_pagination = false;
                        public $offset = 0;
                        public $paginate = false;
                    };
                }
            };
        }

        // Instantiate real class WITHOUT running its constructor to avoid dependencies
        $this->channel = (new ReflectionClass('Channel'))->newInstanceWithoutConstructor();

        // Manually initialize properties that would be set in constructor
        $this->channel->pagination = new class {
            public $per_page = 10;
            public $total_rows = 0;
            public $field_pagination = false;
            public $offset = 0;
            public $paginate = false;
        };
        $this->channel->query_string = '';
        $this->channel->enable = [
            'custom_fields' => false,
            'member_data' => false,
            'category_fields' => false,
            'pagination' => false
        ];

        // Initialize hidden_fields array to prevent undefined property errors
        $this->channel->hidden_fields = [];
        @$this->channel->paginate = false;
        @$this->channel->p_limit = 100;
        @$this->channel->p_page = 0;
        @$this->channel->fixed_order = false;
        $this->channel->sql = '';
        $this->channel->categories = [];
        $this->channel->cfields = [];
        $this->channel->dfields = [];
        $this->channel->rfields = [];
        $this->channel->gfields = [];
        $this->channel->mfields = [];
        $this->channel->mpfields = [];
        $this->channel->ffields = [];
        $this->channel->tfields = [];
        $this->channel->pfields = [];

        // Define missing helper functions
        if (!function_exists('reduce_double_slashes')) {
            function reduce_double_slashes($str) {
                return preg_replace("#([^/:])/+#", "\\1/", $str);
            }
        }
        if (!function_exists('trim_slashes')) {
            function trim_slashes($str) {
                return trim($str, '/');
            }
        }
        if (!function_exists('days_in_month')) {
            function days_in_month($month, $year) {
                // Simple mock implementation of days_in_month function
                return cal_days_in_month(CAL_GREGORIAN, $month, $year);
            }
        }
    }

    // Add tearDown to clean up mocks
    protected function tearDown(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        } else {
            global $__EE_TEST_ENV__;
            if (isset($__EE_TEST_ENV__)) {
                $__EE_TEST_ENV__->mocks = [];
            }
        }
        parent::tearDown();
    }

    protected function setMock(string $name, $mock): void
    {
        if (function_exists('ee') && method_exists(ee(), 'setMock')) {
            ee()->setMock($name, $mock);
            return;
        }
        global $__EE_TEST_ENV__;
        $__EE_TEST_ENV__->mocks[$name] = $mock;
    }

    protected function setTemplateParams(array $map): void
    {
        ee()->TMPL->setMap($map);
    }

    protected function setTemplateTagdata(string $tagdata): void
    {
        ee()->TMPL->setTagdata($tagdata);
    }

    protected function setDbRows(array $rows): void
    {
        ee()->db->setRows($rows);
    }
}




