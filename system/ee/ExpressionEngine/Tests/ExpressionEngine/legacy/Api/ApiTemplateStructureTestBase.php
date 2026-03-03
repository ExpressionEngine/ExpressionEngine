<?php

use PHPUnit\Framework\TestCase;

// Bootstrap minimal EE environment
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

// Include required files
require_once APPPATH . '../legacy/libraries/Api.php';
require_once APPPATH . '../legacy/libraries/api/Api_template_structure.php';

// Note: Using the ee() function from eeObjectMock.php which handles mocking

// Mock classes for testing
if (!class_exists('FakeConfig')) {
    class FakeConfig
    {
        public $items = [];
        public function item($key) { return array_key_exists($key, $this->items) ? $this->items[$key] : null; }
        public function set_item($key, $value) { $this->items[$key] = $value; }
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
        public function result() {
            $result = [];
            foreach ($this->rows as $row) {
                $result[] = (object) $row;
            }
            return $result;
        }
        public function free_result() { /* no-op */ }
    }
}

if (!class_exists('FakeTemplateModel')) {
    class FakeTemplateModel
    {
        public $groups = [];
        public $templates = [];
        public $lastInsertedGroupId = 1;
        public $lastInsertedTemplateId = 1;

        public function get_group_info($group_id) {
            $group_id_int = (int) $group_id;
            return isset($this->groups[$group_id_int]) ? new eeDbResultMock([$this->groups[$group_id_int]]) : new eeDbResultMock([]);
        }

        public function get_templates($site_id, $fields, $where = []) {
            $filtered = [];
            foreach ($this->templates as $template) {
                $matches = true;
                foreach ($where as $key => $value) {
                    if (isset($template[$key]) && $template[$key] != $value) {
                        $matches = false;
                        break;
                    }
                }
                if ($matches) {
                    $filtered[] = $template;
                }
            }
            return new eeDbResultMock($filtered);
        }

        public function create_group($data) {
            $data['group_id'] = $this->lastInsertedGroupId++;
            $this->groups[$data['group_id']] = $data;
            return $data['group_id'];
        }

        public function create_template($data) {
            $data['template_id'] = $this->lastInsertedTemplateId++;
            $this->templates[] = $data;
            return $data['template_id'];
        }

        public function setGroups($groups) {
            $this->groups = $groups;
        }

        public function setTemplates($templates) {
            $this->templates = $templates;
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

if (!class_exists('FakeLegacyApi')) {
    class FakeLegacyApi
    {
        public function is_url_safe($str) {
            return !preg_match('/[^a-zA-Z0-9_\-]/', $str);
        }
    }
}

if (!class_exists('FakeLocalize')) {
    class FakeLocalize
    {
        public $now;

        public function __construct() {
            $this->now = time();
        }

        public function now() {
            return $this->now;
        }

        public function human_time($timestamp = null) {
            return date('Y-m-d H:i', $timestamp ?: $this->now);
        }

        public function string_to_timestamp($string) {
            return strtotime($string);
        }

        public function localize_month($month) {
            return $month;
        }

        public function get_date_format() {
            return '%Y-%m-%d';
        }
    }
}

/**
 * Base test class for Api_template_structure tests
 */
abstract class ApiTemplateStructureTestBase extends TestCase
{
    protected $apiTemplateStructure;
    protected $ee_backup;
    protected $post_backup;

    protected function setUp(): void
    {
        // Backup global state
        $this->ee_backup = $GLOBALS['ee'] ?? null;
        $this->post_backup = $_POST ?? [];

        // Set up basic mocks
        $this->setupBasicMocks();

        // Create the API instance and set its config property
        $this->apiTemplateStructure = new Api_template_structure();
        $this->apiTemplateStructure->config = ee()->config;
    }

    protected function tearDown(): void
    {
        // Restore global state
        if ($this->ee_backup !== null) {
            $GLOBALS['ee'] = $this->ee_backup;
        }

        $_POST = $this->post_backup;

        // Reset mocks
        if (method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
    }

    protected function setupBasicMocks()
    {
        // Config mock
        $config = new FakeConfig();
        $config->items = [
            'site_id' => 1,
            'forum_is_installed' => 'y',
            'forum_trigger' => 'forum',
            'use_category_name' => 'y',
            'reserved_category_word' => 'category',
            'profile_trigger' => 'member',
            'default_template_engine' => 'twig',
            'cp_session_type' => 'c',
            'session_crypt_key' => 'test_key',
        ];
        ee()->setMock('config', $config);

        // Database mock
        ee()->setMock('db', new FakeDb());

        // Template model mock
        ee()->setMock('template_model', new FakeTemplateModel());

        // Super model mock (for duplicate checking)
        ee()->setMock('super_model', new FakeSuperModel());

        // Legacy API mock
        ee()->setMock('legacy_api', new FakeLegacyApi());

        // Localize mock
        ee()->setMock('localize', new FakeLocalize());

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
    }

    /**
     * Get sample template group data for testing
     */
    protected function getValidTemplateGroupData()
    {
        return [
            'group_name' => 'test_group',
            'site_id' => 1,
            'is_site_default' => 'n',
            'group_order' => 1
        ];
    }

    /**
     * Get invalid template group data for testing
     */
    protected function getInvalidTemplateGroupData()
    {
        return [
            'group_name' => '', // Invalid: empty name
            'site_id' => 'invalid', // Invalid: non-numeric
        ];
    }

    /**
     * Get sample template data for testing
     */
    protected function getValidTemplateData()
    {
        return [
            'group_id' => 1,
            'template_name' => 'index',
            'template_data' => '<html><body>Hello World</body></html>',
            'template_type' => 'webpage',
            'template_notes' => '',
            'cache' => 'n',
            'refresh' => 0,
            'no_auth_bounce' => '',
            'php_parse_location' => 'output',
            'allow_php' => 'n',
            'protect_javascript' => 'n',
            'edit_date' => time(),
            'site_id' => 1
        ];
    }

    /**
     * Mock template groups in the database
     */
    protected function mockTemplateGroups($groups = [])
    {
        if (empty($groups)) {
            $groups = [
                1 => [
                    'group_id' => 1,
                    'group_name' => 'test_group',
                    'site_id' => 1,
                    'is_site_default' => 'n',
                    'group_order' => 1
                ]
            ];
        }

        // Get the current template model mock and set the groups
        $templateModel = ee('template_model');
        $templateModel->setGroups($groups);
    }

    /**
     * Mock templates in the database
     */
    protected function mockTemplates($templates = [])
    {
        if (empty($templates)) {
            $templates = [$this->getValidTemplateData()];
        }

        // Get the current template model mock and set the templates
        $templateModel = ee('template_model');
        $templateModel->setTemplates($templates);
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
     * Set up mock for extension hooks
     */
    protected function mockExtensionHook($hookName, $returnValue = [])
    {
        $extensions = ee('extensions');
        $extensions->call = function($hook, $data) use ($hookName, $returnValue) {
            if ($hook === $hookName) {
                return $returnValue;
            }
            return [];
        };
    }

    /**
     * Helper to reset the API instance
     */
    protected function resetApiInstance()
    {
        $this->apiTemplateStructure = new Api_template_structure();
    }
}
