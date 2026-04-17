<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use PHPUnit\Framework\TestCase;
use Mockery as m;

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

// Define a constant to check if we should use AllowDynamicProperties attribute (PHP 8.0+ only)
if (!defined('USE_ALLOW_DYNAMIC_PROPERTIES')) {
    define('USE_ALLOW_DYNAMIC_PROPERTIES', PHP_VERSION_ID >= 80000);
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
if (!function_exists('bool_config_item')) {
    function bool_config_item($item)
    {
        return ee()->config->item($item) === 'y';
    }
}
if (!defined('CSRF_TOKEN')) {
    define('CSRF_TOKEN', 'test_csrf_token');
}
if (!function_exists('validation_errors')) {
    function validation_errors()
    {
        return '';
    }
}
if (!function_exists('remove_invisible_characters')) {
    function remove_invisible_characters($str, $url_encoded = true)
    {
        return $str;
    }
}
if (!function_exists('get_mimes')) {
    function get_mimes()
    {
        return [];
    }
}
if (!function_exists('set_value')) {
    require_once SYSPATH . 'ee/legacy/helpers/form_helper.php';
}

// Ensure eeObjectMock system works with our mocks

// Include the real Relationships_ft_cp class
require_once PATH_ADDONS . 'relationship/libraries/Relationships_ft_cp.php';

/**
 * Base test class for Relationship fieldtype tests
 */
abstract class RelationshipTestBase extends TestCase
{
    protected $relationships_ft_cp;
    protected $__EE_TEST_ENV__;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize the global test environment
        global $__EE_TEST_ENV__;
        if (!isset($__EE_TEST_ENV__)) {
            $__EE_TEST_ENV__ = new stdClass();
            $__EE_TEST_ENV__->mocks = [];
        }
        $this->__EE_TEST_ENV__ = &$__EE_TEST_ENV__;

        // Set up EE environment mocks
        $this->setupEeEnvironment();

        // Create instance of the class to test
        $this->relationships_ft_cp = new Relationships_ft_cp();
    }

    /**
     * Set a mock for an EE service using eeObjectMock system
     */
    protected function setMock($name, $mock)
    {
        ee()->setMock($name, $mock);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up mocks
        if (method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
        m::close();

        // Reset any cached properties
        $reflection = new ReflectionClass($this->relationships_ft_cp);
        $properties = ['all_authors', 'all_channels', 'all_categories', 'all_statuses'];
        foreach ($properties as $property) {
            if ($reflection->hasProperty($property)) {
                $prop = $reflection->getProperty($property);
                \TestReflectionHelper::makeAccessible($prop);
                $prop->setValue($this->relationships_ft_cp, null);
            }
        }
    }

    /**
     * Set up ExpressionEngine environment mocks
     */
    protected function setupEeEnvironment()
    {
        // Set up basic config with multiple sites disabled by default
        $this->setMock('config', new class {
            public $items = [
                'multiple_sites_enabled' => 'n',
                'site_id' => 1
            ];

            public function item($key, $index = '', $raw_value = false)
            {
                return $this->items[$key] ?? false;
            }
        });

        // Set up language mock for translations
        $this->setMock('lang', new class {
            public function loadfile($file) {
                // Mock loading language files
            }

            public function line($key) {
                // Return translation keys for common relationship terms
                $translations = [
                    'any_channel' => 'Any Channel',
                    'any_category' => 'Any Category',
                    'any_author' => 'Any Author',
                    'any_status' => 'Any Status',
                    'rel_ft_order_title' => 'Title',
                    'rel_ft_order_date' => 'Entry Date',
                    'rel_ft_order_asc' => 'Ascending',
                    'rel_ft_order_desc' => 'Descending',
                    'open' => 'Open',
                    'closed' => 'Closed'
                ];
                return $translations[$key] ?? $key;
            }
        });

        // Set up session mock
        $this->setMock('session', new class {
            public $cache = [];
            public function cache($class, $key) {
                return $this->cache[$class][$key] ?? false;
            }
            public function set_cache($class, $key, $value) {
                $this->cache[$class][$key] = $value;
            }
        });

        // Mock global lang() function
        if (!function_exists('lang')) {
            function lang($key) {
                $translations = [
                    'any_channel' => 'Any Channel',
                    'any_category' => 'Any Category',
                    'any_author' => 'Any Author',
                    'any_status' => 'Any Status',
                    'rel_ft_order_title' => 'Title',
                    'rel_ft_order_date' => 'Entry Date',
                    'rel_ft_order_asc' => 'Ascending',
                    'rel_ft_order_desc' => 'Descending',
                    'open' => 'Open',
                    'closed' => 'Closed'
                ];
                return $translations[$key] ?? $key;
            }
        }

        // Mock Laravel collection helper
        if (!function_exists('collect')) {
            function collect($items = []) {
                return new class($items) implements IteratorAggregate, Countable {
                    private $items;
                    public function __construct($items) {
                        $this->items = is_array($items) ? $items : [$items];
                    }
                    public function all() {
                        return $this->items;
                    }
                    public function getIterator(): Traversable {
                        return new ArrayIterator($this->items);
                    }
                    public function count(): int {
                        return count($this->items);
                    }
                    public function getDictionary($key, $value) {
                        $result = [];
                        foreach ($this->items as $item) {
                            $result[$item->$key] = $item->$value;
                        }
                        return $result;
                    }
                    public function pluck($field) {
                        return array_map(function($item) use ($field) {
                            return $item->$field;
                        }, $this->items);
                    }
                    public function map($callback) {
                        return array_map($callback, $this->items);
                    }
                    public function filter($callback) {
                        return array_filter($this->items, $callback);
                    }
                    public function __get($name) {
                        // Handle relationship access like $collection->Role
                        $result = [];
                        foreach ($this->items as $item) {
                            if (isset($item->$name)) {
                                $result[] = $item->$name;
                            }
                        }
                        return collect($result);
                    }
                };
            }
        }

        // Set up DB mock with dbprefix
        $dbMock = new eeDbArMock();
        $dbMock->dbprefix = 'exp_';
        ee()->setMock('db', $dbMock);

    }

    /**
     * Helper to create mock Channel model collection
     */
    protected function createMockChannelCollection($channels = [])
    {
        $mockCollection = m::mock();
        $mockCollection->shouldReceive('with')->andReturnSelf();
        $mockCollection->shouldReceive('order')->andReturnSelf();
        $mockCollection->shouldReceive('filter')->andReturnSelf();
        $mockCollection->shouldReceive('all')->andReturn(collect($channels));

        return $mockCollection;
    }

    /**
     * Helper to create mock Category model collection
     */
    protected function createMockCategoryCollection($categories = [])
    {
        $mockCollection = m::mock();
        $mockCollection->shouldReceive('with')->andReturnSelf();
        $mockCollection->shouldReceive('fields')->andReturnSelf();
        $mockCollection->shouldReceive('filter')->andReturnSelf();
        $mockCollection->shouldReceive('order')->andReturnSelf();
        $mockCollection->shouldReceive('all')->andReturn(collect($categories));

        return $mockCollection;
    }

    /**
     * Helper to create mock Status model collection
     */
    protected function createMockStatusCollection($statuses = [])
    {
        $mockCollection = m::mock();
        $mockCollection->shouldReceive('order')->andReturnSelf();
        $mockCollection->shouldReceive('all')->andReturn(collect($statuses));

        return $mockCollection;
    }

    /**
     * Helper to create mock Member model collection
     */
    protected function createMockMemberCollection($members = [])
    {
        $mockCollection = m::mock();
        $mockCollection->shouldReceive('with')->andReturnSelf();
        $mockCollection->shouldReceive('filter')->andReturnSelf();
        $mockCollection->shouldReceive('order')->andReturnSelf();
        $mockCollection->shouldReceive('limit')->andReturnSelf();
        $mockCollection->shouldReceive('orFilter')->andReturnSelf();
        $mockCollection->shouldReceive('search')->andReturnSelf();
        $mockCollection->shouldReceive('all')->andReturn($members);

        return $mockCollection;
    }

    /**
     * Helper to create mock RoleSetting model collection
     */
    protected function createMockRoleSettingCollection($roleSettings = [])
    {
        $mockCollection = m::mock();
        $mockCollection->shouldReceive('with')->andReturnSelf();
        $mockCollection->shouldReceive('filter')->andReturnSelf();
        $mockCollection->shouldReceive('order')->andReturnSelf();
        $mockCollection->shouldReceive('all')->andReturn($roleSettings);

        return $mockCollection;
    }

    /**
     * Helper to enable multi-site mode
     */
    protected function enableMultiSite()
    {
        ee()->config->items['multiple_sites_enabled'] = 'y';
    }

    /**
     * Helper to disable multi-site mode
     */
    protected function disableMultiSite()
    {
        ee()->config->items['multiple_sites_enabled'] = 'n';
    }
}
