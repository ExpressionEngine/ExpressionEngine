<?php

use PHPUnit\Framework\TestCase;

// Bootstrap minimal EE environment so we can include the real class
if (!defined('APP_VER')) {
    define('APP_VER', '7.5.14');
}
if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../../../../../../');
}
if (!defined('APPPATH')) {
    define('APPPATH', BASEPATH . 'ee/');
}

// Determine addons path relative to this file
$__addons = __DIR__ . '/../../../../../Addons/';
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

// Include the real Channel_form_lib class
require_once PATH_ADDONS . 'channel/libraries/channel_form/Channel_form_lib.php';

// Define Fake classes for individual test runs
if (!class_exists('FakeTemplate')) {
    class FakeTemplate
    {
        public $map = [];
        public $tagdata = '';
        public $tagproper = '';
        public $site_ids = [1];
        public $cache_timestamp = '';
        public $var_single = [];

        public function setMap(array $map): void { $this->map = $map; }
        public function setTagdata(string $tagdata): void { $this->tagdata = $tagdata; }
        public function no_results() { return 'NO_RESULTS'; }
        public function fetch_param($key, $default = null) {
            return array_key_exists($key, $this->map) ? $this->map[$key] : $default;
        }
        public function set_data($data) { /* no-op */ }
        public function parse_variables($tagdata, $vars) {
            // Simple variable replacement for testing
            $result = $tagdata;
            foreach ($vars as $var) {
                if (isset($var['prev']) && is_array($var['prev'])) {
                    foreach ($var['prev'] as $prev) {
                        $result = str_replace('{prev_title}', $prev['title'], $result);
                        $result = str_replace('{prev_url}', $prev['url'], $result);
                        $result = str_replace('{prev_entry_id}', $prev['entry_id'], $result);
                    }
                }
                if (isset($var['next']) && is_array($var['next'])) {
                    foreach ($var['next'] as $next) {
                        $result = str_replace('{next_title}', $next['title'], $result);
                        $result = str_replace('{next_url}', $next['url'], $result);
                        $result = str_replace('{next_entry_id}', $next['entry_id'], $result);
                    }
                }
            }
            return $result;
        }
        public function log_item($str) { return true; }
        public function swap_var_single($variable, $replacement, $source) {
            return str_replace('{' . $variable . '}', $replacement, $source);
        }
        public function delete_var_pairs($opening, $closing, $source) {
            $pattern = '/' . preg_quote($opening, '/') . '.*?' . preg_quote($closing, '/') . '/s';
            return preg_replace($pattern, '', $source);
        }
        public function swap_var_pairs($opening, $closing, $source) { return $source; }
        public $var_pair = [];
    }
}

if (!class_exists('FakeConfig')) {
    class FakeConfig
    {
        public $items = [];
        public function item($key) { return array_key_exists($key, $this->items) ? $this->items[$key] : null; }
    }
}

if (!class_exists('FakeFunctions')) {
    class FakeFunctions
    {
        public function fetch_site_index($a = 0, $b = 0) { return '/'; }
        public function create_url($path = '') {
            if ($path) {
                return 'https://example.com/' . ltrim($path, '/');
            }
            return 'https://example.com/';
        }
        public function assign_parameters($param_string) {
            $params = [];
            $param_string = trim($param_string);
            if ($param_string === '') {
                return $params;
            }
            preg_match_all('/(\w+)\s*=\s*"([^"]*)"/', $param_string, $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                $params[$m[1]] = $m[2];
            }
            return $params;
        }
        public function prep_conditionals($str, $vars = []) { return $str; }
        public function sql_andor_string($str, $field) {
            if (strpos($str, '|') !== false) {
                $parts = explode('|', $str);
                $conditions = [];
                foreach ($parts as $part) {
                    $conditions[] = "$field = '$part'";
                }
                return ' AND (' . implode(' OR ', $conditions) . ')';
            }
            return " AND $field = '$str'";
        }
        public function extract_path($variable) {
            if (strpos($variable, ':') !== false) {
                return str_replace('path:', '', $variable);
            }
            return $variable;
        }
        public function trim_slashes($str) { return trim($str, '/'); }
    }
}

if (!class_exists('FakeDb')) {
    class FakeDb
    {
        public $rows = [];
        private $whereConditions = [];
        private $whereInConditions = [];
        private $limitValue = null;
        private $tableName = null;

        public function setRows(array $rows): void { $this->rows = $rows; }
        public function query($sql) { return new eeDbResultMock($this->rows); }
        public function where($field, $value = null) {
            if ($value === null) {
                $this->whereConditions[] = $field;
            } else {
                $this->whereConditions[$field] = $value;
            }
            return $this;
        }
        public function where_in($field, $values) {
            $this->whereInConditions[$field] = (array) $values;
            return $this;
        }
        public function limit($value) {
            $this->limitValue = $value;
            return $this;
        }
        public function from($table) {
            $this->tableName = $table;
            return $this;
        }
        public function select($columns = '*') { return $this; }
        public function distinct() { return $this; }
        public function order_by($field, $direction = 'ASC') { return $this; }
        public function get($table = null, $limit = null, $offset = null) {
            if ($table) $this->tableName = $table;
            if ($limit) $this->limitValue = $limit;
            return new eeDbResultMock($this->rows);
        }
        public function insert($table, $data = []) { return true; }
        public function update($table, $data = [], $where = []) { return true; }
        public function delete($table, $where = []) { return true; }
        public function insert_id() { return 1; }
        public function affected_rows() { return 1; }
    }
}

if (!class_exists('eeDbResultMock')) {
    class eeDbResultMock
    {
        private $rows;
        public function __construct(array $rows = []) { $this->rows = $rows; }
        public function result_array() { return $this->rows; }
        public function row_array($n = 0) { return isset($this->rows[$n]) ? $this->rows[$n] : []; }
        public function num_rows() { return count($this->rows); }
        public function free_result() { /* no-op */ }
    }
}

abstract class ChannelFormLibTestBase extends TestCase
{
    protected $channelFormLib;

    protected function setUp(): void
    {
        // If running under core test bootstrap, use its ee() mock container
        if (function_exists('ee') && ee() !== null && method_exists(ee(), 'setMock')) {
            // Template mock
            $fakeTemplate = new FakeTemplate();
            ee()->setMock('TMPL', $fakeTemplate);

            // Config mock
            ee()->setMock('config', new FakeConfig());
            ee()->config->items = [
                'site_id' => 1,
                'reserved_category_word' => 'category',
                'charset' => 'UTF-8',
                'use_category_name' => 'n',
                'cp_session_type' => 'c',
                'session_crypt_key' => 'test_key',
                'auto_convert_high_ascii' => 'y',
                'word_separator' => 'dash',
                'send_headers' => 'y',
                'use_recaptcha' => 'n',
                'captcha_require_members' => 'y',
            ];

            // Functions mock
            ee()->setMock('functions', new FakeFunctions());

            // DB mock
            ee()->setMock('db', new FakeDb());

            // URI mock
            ee()->setMock('uri', new class {
                public $page_query_string = '';
                public $query_string = '';
                public $uri_string = '';
                public $uri_string_return = '';
                public function uri_string() { return $this->uri_string_return; }
            });

            // Session mock
            ee()->setMock('session', new class {
                public $cache = [];
                public $userdata = ['member_id' => 1, 'group_id' => 1, 'ip_address' => '127.0.0.1'];
                public function userdata($key, $default = false) {
                    return $this->userdata[$key] ?? $default;
                }
                public function set_userdata($key, $value) {
                    $this->userdata[$key] = $value;
                }
                public function cache($class, $key) { return $this->cache[$class][$key] ?? false; }
                public function set_cache($class, $key, $value) { $this->cache[$class][$key] = $value; }
            });

            // Extensions mock
            ee()->setMock('extensions', new class {
                public $hooks = [];
                public $end_script = false;
                public function active_hook($name) { return isset($this->hooks[$name]); }
                public function call($name, ...$args) { return $this->hooks[$name]['return'] ?? null; }
            });

            // API Channel Fields mock
            ee()->setMock('api_channel_fields', new class {
                public $settings = [];
                public $field_types = [];
                public $field_type = '';
                public function set_settings($id, $settings) { $this->settings[$id] = $settings; }
                public function setup_handler($field_type, $return_obj = false) {
                    if (!isset($this->field_types[$field_type])) {
                        $this->field_types[$field_type] = new class {
                            public $settings = [];
                            public function _init($args) {}
                            public function apply($method, $args = []) { return null; }
                        };
                    }
                    return $return_obj ? $this->field_types[$field_type] : false;
                }
                public function apply($method, $args = []) { return null; }
                public function get_global_settings($field_type) { return []; }
                public function fetch_installed_fieldtypes() { return ['text', 'textarea', 'select']; }
                public function include_handler($field_type) {}
            });

            // Legacy API mock
            ee()->setMock('legacy_api', new class {
                public function instantiate($name) {}
            });

            // Lang mock
            ee()->setMock('lang', new class {
                public function load($item) {}
                public function loadfile($name) {}
                public function line($key, $default = '') { return $key ?: $default; }
            });

            // Localize mock
            ee()->setMock('localize', new class {
                public $now = 0;
                public function __construct() { $this->now = time(); }
                public function human_time($timestamp = null) {
                    return date('Y-m-d H:i', $timestamp ?: $this->now);
                }
                public function string_to_timestamp($string) {
                    return strtotime($string);
                }
                public function localize_month($month) { return $month; }
                public function get_date_format() { return '%Y-%m-%d'; }
            });

            // Input mock
            ee()->setMock('input', new class {
                public $post_data = [];
                public function get_post($item, $xss_clean = false) {
                    return $this->post_data[$item] ?? null;
                }
                public function get($item) { return null; }
                public function post($item = null, $xss_clean = false) {
                    if ($item === null) return $this->post_data;
                    return $this->post_data[$item] ?? false;
                }
                public function ip_address() { return '127.0.0.1'; }
            });

            // Form validation mock
            ee()->setMock('form_validation', new class {
                public $_error_array = [];
                public $error_string = '';
                public function set_rules($field, $label, $rules = '') {}
                public function set_message($method, $message) {}
                public function run() { return empty($this->_error_array); }
            });

            // Load mock
            ee()->setMock('load', new class {
                public function helper($name) {}
                public function library($name) {
                    if ($name === 'javascript') {
                        @ee()->javascript = new class {
                            public $output_js = [];
                            public function output($js) {}
                            public function get_global() { return ''; }
                        };
                    }
                    if ($name === 'cp') {
                        ee()->cp = new class {
                            public $js_files = [];
                            public function _get_js_mtime($type, $files) { return time(); }
                            public function get_head() { return []; }
                            public function get_foot() { return []; }
                        };
                    }
                    if ($name === 'api') {
                        ee()->legacy_api = new class {
                            public function instantiate($name) {}
                        };
                    }
                }
                public function model($name) {}
            });

            // JavaScript mock
            ee()->setMock('javascript', new class {
                public $output_js = [];
                public function output($js) {}
                public function get_global() { return ''; }
                public function inline($js) { return "<script>{$js}</script>"; }
            });

            // Router mock
            ee()->setMock('router', new class {
                public function set_class($class) {}
            });

            // Logger mock
            ee()->setMock('logger', new class {
                public function developer($msg) {}
            });

            // jQuery mock
            ee()->setMock('jquery', new class {
                public $jquery_code_for_compile = [];
                public function _compile() {}
            });

            // Filemanager mock
            ee()->setMock('filemanager', new class {
                public function _initialize($config) {}
            });

            // Cache mock
            ee()->setMock('cache', new class {
                public function get($key) { return false; }
                public function save($key, $val, $ttl = 0) { return true; }
            });

            // File field mock
            ee()->setMock('file_field', new class {
                public function validate($name) { return ['value' => $name]; }
                public function parse_field($data) { return ['url' => $data]; }
            });

            // API Channel Categories mock
            ee()->setMock('api_channel_categories', new class {
                public function category_tree($groups, $selected = []) { return []; }
            });

            // Encrypt mock
            ee()->setMock('Encrypt', new class {
                public function encode($data, $key) { return base64_encode($data); }
                public function decode($data, $key) { return base64_decode($data); }
            });

            // Permission mock
            if (!class_exists('ExpressionEngine\Service\Permission\Permission')) {
                class_alias('class@anonymous', 'ExpressionEngine\Service\Permission\Permission');
            }
            ee()->setMock('Permission', new class {
                public function isSuperAdmin() { return false; }
            });

            // Captcha mock
            ee()->setMock('Captcha', new class {
                public function shouldRequireCaptcha() { return false; }
                public function create($word = '', $required = false) { return '<div>captcha</div>'; }
            });

            // Spam mock
            ee()->setMock('Spam', new class {
                public function isSpam($content, $entry_data = []) { return false; }
                public function moderate($type, $entry, $content, $entry_data) {}
            });

            // Output mock
            ee()->setMock('output', new class {
                public function send_ajax_response($msg, $error = false) {}
            });

        } else {
            // Fallback minimal env when running outside core bootstrap
            global $__EE_TEST_ENV__;
            // For high-risk tests, we'll handle the missing TestEnvironment gracefully
            // by providing a minimal mock environment
            $__EE_TEST_ENV__ = new class {
                public $TMPL;
                public $config;
                public $functions;
                public $db;
                public $session;
                public $input;
                public $lang;
                public $load;
                private $mocks = [];

                public function __construct() {
                    $this->TMPL = new FakeTemplate();
                    $this->config = new FakeConfig();
                    $this->functions = new FakeFunctions();
                    $this->db = new FakeDb();
                    $this->session = (object) ['userdata' => ['member_id' => 1, 'ip_address' => '127.0.0.1']];
                    $this->input = new class {
                        public function post($key = null) { return isset($_POST[$key]) ? $_POST[$key] : null; }
                        public function get($key = null) { return isset($_GET[$key]) ? $_GET[$key] : null; }
                    };
                    $this->lang = new class {
                        public function load() {}
                        public function loadfile() {}
                        public function line($key) { return $key; }
                    };
                    $this->load = new class {
                        public function library() {}
                        public function helper() {}
                    };
                }

                public function set($key, $value) {
                    $this->mocks[$key] = $value;
                }

                public function get($key) {
                    return isset($this->mocks[$key]) ? $this->mocks[$key] : null;
                }
            };
        }

        // Create instance of Channel_form_lib for testing
        $this->channelFormLib = new Channel_form_lib();
    }

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

    protected function createMockChannel($properties = [])
    {
        $channel = new class {
            public $channel_id = 1;
            public $channel_name = 'test_channel';
            public $default_entry_title = 'Test Entry';
            public $url_title_prefix = 'test-';
            public $deft_status = 'open';
            public $deft_comments = 'y';
            public $comment_system_enabled = 'y';
            public $comment_expiration = 0;
            public $enable_versioning = 'n';
            public $CategoryGroups = null;
            public $Statuses = null;
            public $ChannelFormSettings = null;

            public function __construct($properties = []) {
                foreach ($properties as $key => $value) {
                    @$this->$key = $value;
                }
            }

            public function getProperty($key) {
                return $this->$key ?? null;
            }

            public function getAllCustomFields() {
                return [];
            }

            public function getId() {
                return $this->channel_id;
            }
        };

        return new $channel($properties);
    }

    protected function createMockEntry($properties = [])
    {
        $entry = new class {
            public $entry_id = 1;
            public $channel_id = 1;
            public $author_id = 1;
            public $title = 'Test Entry';
            public $url_title = 'test-entry';
            public $status = 'open';
            public $Channel = null;
            public $Categories = null;
            public $toArray_called = false;

            public function __construct($properties = []) {
                foreach ($properties as $key => $value) {
                    @$this->$key = $value;
                }
            }

            public function getProperty($key) {
                return $this->$key ?? null;
            }

            public function set($data) {
                foreach ($data as $key => $value) {
                    @$this->$key = $value;
                }
            }

            public function hasCustomField($field) {
                return property_exists($this, $field) || strpos($field, 'field_id_') === 0;
            }

            public function getCustomField($field) {
                return new class {
                    public function getItem($key) {
                        return $key === 'field_label' ? 'Test Field' : '';
                    }
                };
            }

            public function toArray() {
                $this->toArray_called = true;
                return get_object_vars($this);
            }

            public function validate() {
                return new class {
                    public function isValid() { return true; }
                    public function getAllErrors() { return []; }
                };
            }

            public function save() {}

            public function getDisplay() {
                return new class {
                    private $entry;
                    public function __construct() {
                        $this->entry = null;
                    }

                    public function getFields() {
                        // Return empty array by default, can be overridden in tests
                        return [];
                    }
                };
            }
        };

        return new $entry($properties);
    }

    protected function createMockMember($properties = [])
    {
        $member = new #[\AllowDynamicProperties] class {
            public $member_id = 1;
            public $PrimaryRole = null;

            public function __construct($properties = []) {
                foreach ($properties as $key => $value) {
                    @$this->$key = $value;
                }
                if (!$this->PrimaryRole) {
                    $this->PrimaryRole = new class {
                        public function getId() { return 1; }
                    };
                }
            }

            public function getId() {
                return $this->member_id;
            }

            public function getAssignedChannels() {
                return new class {
                    public function pluck($field) { return [1]; }
                };
            }

            public function getAssignedStatuses() {
                return new class {
                    public function indexBy($field) {
                        return new class {
                            public function __isset($key) { return true; }
                            public function offsetGet($key) {
                                return new class {
                                    public function getId() { return 1; }
                                };
                            }
                        };
                    }
                };
            }
        };

        return new $member($properties);
    }

    // Helper methods for accessing protected properties
    protected function getProtectedProperty($property) {
        $reflection = new ReflectionClass($this->channelFormLib);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop;
    }

    protected function setProtectedProperty($property, $value) {
        $prop = $this->getProtectedProperty($property);
        $prop->setValue($this->channelFormLib, $value);
    }

    protected function getProtectedPropertyValue($property) {
        $prop = $this->getProtectedProperty($property);
        return $prop->getValue($this->channelFormLib);
    }
}
