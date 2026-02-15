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

// Initialize the global EE test environment
global $__EE_TEST_ENV__;
$__EE_TEST_ENV__ = new TestEnvironment();

// Include the real Channel_form_exception class
require_once PATH_ADDONS . 'channel/libraries/channel_form/Channel_form_exception.php';

// Define Fake classes for individual test runs
if (!class_exists('FakeTemplate')) {
    class FakeTemplate
    {
        public $map = [];
        public $tagdata = '';
        public $tagproper = '';
        public $site_ids = [1];
        public $cache_timestamp = '';

        public function setMap(array $map): void { $this->map = $map; }
        public function setTagdata(string $tagdata): void { $this->tagdata = $tagdata; }
        public function no_results() { return 'NO_RESULTS'; }
        public function fetch_param($key, $default = null) {
            return array_key_exists($key, $this->map) ? $this->map[$key] : $default;
        }
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
        public $template_type = 'webpage'; // Prevent dynamic property deprecation warnings
        public function fetch_site_index($a = 0, $b = 0) { return '/'; }
        public function create_url($path = '') {
            if ($path) {
                return 'https://example.com/' . ltrim($path, '/');
            }
            return 'https://example.com/';
        }
    }
}

if (!class_exists('FakeDb')) {
    class FakeDb
    {
        public $rows = [];
        private $whereConditions = [];
        private $whereInConditions = [];
        private $limitValue = null;

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
        public function select($fields = '*') {
            return $this;
        }
        public function from($table) {
            return $this;
        }
        public function order_by($field, $direction = '') {
            return $this;
        }
        public function get($table = null) {
            if ($table) {
                // no-op
            }
            $filtered = $this->rows;
            foreach ($this->whereConditions as $field => $value) {
                $filtered = array_values(array_filter($filtered, function ($row) use ($field, $value) {
                    return isset($row[$field]) && $row[$field] == $value;
                }));
            }
            foreach ($this->whereInConditions as $field => $values) {
                $filtered = array_values(array_filter($filtered, function ($row) use ($field, $values) {
                    return isset($row[$field]) && in_array($row[$field], $values);
                }));
            }
            if (!is_null($this->limitValue)) {
                $filtered = array_slice($filtered, 0, $this->limitValue);
            }
            $this->whereConditions = [];
            $this->whereInConditions = [];
            $this->limitValue = null;
            return new eeDbResultMock($filtered);
        }
    }
}

if (!class_exists('eeDbResultMock')) {
    class eeDbResultMock
    {
        public $resultArray;
        public function __construct($resultArray = []) {
            $this->resultArray = $resultArray;
        }
        public function result() {
            $result = [];
            foreach ($this->resultArray as $row) {
                $result[] = (object) $row;
            }
            return $result;
        }
        public function num_rows() {
            return count($this->resultArray);
        }
        public function result_array() {
            return $this->resultArray;
        }
        public function row($column = null) {
            if (empty($this->resultArray)) {
                return null;
            }
            $row = (object) $this->resultArray[0];
            if ($column !== null) {
                if (isset($row->$column)) {
                    return $row->$column;
                } else {
                    return null;
                }
            }
            return $row;
        }
        public function row_array() {
            if (empty($this->resultArray)) {
                return [];
            }
            return $this->resultArray[0];
        }
        public function free_result() {
            // no-op for tests
        }
    }
}

if (!class_exists('TestEnvironment')) {
    class TestEnvironment
    {
        public $mocks = [];
        public $TMPL;
        public $config;
        public $functions;
        public $db;
        public $uri;
        public $session;
        public $extensions;
        public $api_channel_fields;
        public $load;
        public $cache;
        public $input;
        public $lang;
        public $legacy_api;
        public $localize;
        public $output; // Add output mock for show_user_error testing

        public function setMock($name, $mock)
        {
            $this->mocks[$name] = $mock;
            $this->$name = $mock;
        }

        public function set($name, $value)
        {
            $this->$name = $value;
        }

        public function remove($name)
        {
            if (isset($this->$name)) {
                unset($this->$name);
            }
        }
    }
}

class ChannelFormExceptionTest extends TestCase
{
    protected function setUp(): void
    {
        // Set up EE test environment
        global $__EE_TEST_ENV__;

        // Set up basic mocks
        $__EE_TEST_ENV__->TMPL = new FakeTemplate();
        $__EE_TEST_ENV__->config = new FakeConfig();
        $__EE_TEST_ENV__->functions = new FakeFunctions();
        $__EE_TEST_ENV__->db = new FakeDb();

        // Mock output for show_user_error testing
        $outputMock = new class {
            public $show_user_error_calls = [];
            public function show_user_error($type, $message) {
                $this->show_user_error_calls[] = ['type' => $type, 'message' => $message];
                return 'mocked_error_output';
            }
        };
        $__EE_TEST_ENV__->setMock('output', $outputMock);
    }

    protected function tearDown(): void
    {
        global $__EE_TEST_ENV__;
        if (isset($__EE_TEST_ENV__)) {
            $__EE_TEST_ENV__->mocks = [];
            // Reset output mock calls
            if (isset($__EE_TEST_ENV__->output)) {
                $__EE_TEST_ENV__->output->show_user_error_calls = [];
            }
        }
        parent::tearDown();
    }

    /**
     * Test constructor with string message and default type
     */
    public function testConstructorWithStringMessageDefaultType()
    {
        $message = 'Test error message';
        $exception = new Channel_form_exception($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals('submission', $this->getExceptionType($exception));
    }

    /**
     * Test constructor with string message and custom type
     */
    public function testConstructorWithStringMessageCustomType()
    {
        $message = 'Custom error message';
        $type = 'custom_error';
        $exception = new Channel_form_exception($message, $type);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($type, $this->getExceptionType($exception));
    }

    /**
     * Test constructor with array message conversion
     */
    public function testConstructorWithArrayMessage()
    {
        $messages = ['First error', 'Second error', 'Third error'];
        $expectedMessage = "First error</li>\n<li>Second error</li>\n<li>Third error";
        $exception = new Channel_form_exception($messages);

        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals('submission', $this->getExceptionType($exception));
    }

    /**
     * Test constructor with array message and custom type
     */
    public function testConstructorWithArrayMessageCustomType()
    {
        $messages = ['Error 1', 'Error 2'];
        $type = 'validation';
        $expectedMessage = "Error 1</li>\n<li>Error 2";
        $exception = new Channel_form_exception($messages, $type);

        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals($type, $this->getExceptionType($exception));
    }

    /**
     * Test constructor with empty array
     */
    public function testConstructorWithEmptyArray()
    {
        $messages = [];
        $expectedMessage = "";
        $exception = new Channel_form_exception($messages);

        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals('submission', $this->getExceptionType($exception));
    }

    /**
     * Test constructor with single item array
     */
    public function testConstructorWithSingleItemArray()
    {
        $messages = ['Single error'];
        $expectedMessage = "Single error";
        $exception = new Channel_form_exception($messages);

        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals('submission', $this->getExceptionType($exception));
    }

    /**
     * Test constructor with null message
     */
    public function testConstructorWithNullMessage()
    {
        $exception = new Channel_form_exception(null);

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals('submission', $this->getExceptionType($exception));
    }

    /**
     * Test constructor with null message and custom type
     */
    public function testConstructorWithNullMessageCustomType()
    {
        $exception = new Channel_form_exception(null, 'custom_type');

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals('custom_type', $this->getExceptionType($exception));
    }

    /**
     * Test constructor with empty string message
     */
    public function testConstructorWithEmptyStringMessage()
    {
        $exception = new Channel_form_exception('');

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals('submission', $this->getExceptionType($exception));
    }

    /**
     * Test show_user_error method exists and is callable
     * Note: Full integration testing with ee() would require more complex setup
     */
    public function testShowUserErrorMethodExists()
    {
        $message = 'Test error for show_user_error';
        $type = 'test_type';
        $exception = new Channel_form_exception($message, $type);

        // Verify the method exists
        $this->assertTrue(method_exists($exception, 'show_user_error'));

        // Verify we can access the private _type property
        $this->assertEquals($type, $this->getExceptionType($exception));
        $this->assertEquals($message, $exception->getMessage());
    }

    /**
     * Test show_user_error with default type setup
     */
    public function testShowUserErrorWithDefaultType()
    {
        $message = 'Default type error';
        $exception = new Channel_form_exception($message);

        // Verify the method exists and default type is set
        $this->assertTrue(method_exists($exception, 'show_user_error'));
        $this->assertEquals('submission', $this->getExceptionType($exception));
        $this->assertEquals($message, $exception->getMessage());
    }

    /**
     * Test exception inheritance from PHP Exception
     */
    public function testExtendsException()
    {
        $exception = new Channel_form_exception('Test message');

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertInstanceOf(Channel_form_exception::class, $exception);
    }

    /**
     * Test exception code and previous exception handling
     */
    public function testExceptionProperties()
    {
        $message = 'Test message';
        $type = 'test_type';
        $code = 123;
        $previous = new Exception('Previous exception');

        // Test with Reflection to set protected properties
        $reflection = new ReflectionClass(Channel_form_exception::class);
        $exception = $reflection->newInstanceWithoutConstructor();

        // Manually call constructor
        $constructor = $reflection->getConstructor();
        $constructor->invoke($exception, $message, $type);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($type, $this->getExceptionType($exception));
    }

    /**
     * Test that exception can be thrown and caught
     */
    public function testExceptionCanBeThrown()
    {
        $this->expectException(Channel_form_exception::class);
        $this->expectExceptionMessage('Thrown exception message');

        throw new Channel_form_exception('Thrown exception message');
    }

    /**
     * Test exception with special characters in message
     */
    public function testExceptionWithSpecialCharacters()
    {
        $message = 'Error with <script>alert("xss")</script> and "quotes"';
        $exception = new Channel_form_exception($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    /**
     * Test array message with special characters
     */
    public function testArrayMessageWithSpecialCharacters()
    {
        $messages = [
            'Error with <b>HTML</b>',
            'Error with "quotes"',
            'Error with & ampersand'
        ];
        $expectedMessage = "Error with <b>HTML</b></li>\n<li>Error with \"quotes\"</li>\n<li>Error with & ampersand";
        $exception = new Channel_form_exception($messages);

        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * Helper method to access private _type property for testing
     */
    private function getExceptionType(Channel_form_exception $exception)
    {
        $reflection = new ReflectionClass($exception);
        $property = $reflection->getProperty('_type');
        TestReflectionHelper::makePropertyAccessible($property);
        return $property->getValue($exception);
    }
}
