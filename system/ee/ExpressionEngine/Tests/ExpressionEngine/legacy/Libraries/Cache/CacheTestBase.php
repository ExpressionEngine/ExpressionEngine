<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\Cache;

require_once __DIR__ . '/../../../../eeObjectMock.php';

/**
 * Base class for Cache library tests
 */
class CacheTestBase extends \PHPUnit\Framework\TestCase
{
    /**
     * Temp directory for file-based tests
     *
     * @var string
     */
    protected $tempDir;

    /**
     * Set up test environment with EE mocks
     */
    public function setUp(): void
    {
        // Define essential EE constants if not already defined
        $this->defineConstants();

        // Load required Cache library files
        $this->loadCacheLibrary();

        // Set up basic EE mocks
        $this->setupEEMocks();

        // Create temp directory for file tests
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ee_cache_tests_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void
    {
        // Reset EE mocks
        ee()->resetMocks();
        ee()->config->resetConfig();

        // Clean up temp directory
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    /**
     * Set up EE global mocks for cache testing
     */
    protected function setupEEMocks()
    {
        // Mock config with common cache settings
        ee()->config->setItem('base_url', 'https://example.com/');
        ee()->config->setItem('site_short_name', 'default_site');
        ee()->config->setItem('cache_driver', 'file');
        ee()->config->setItem('cache_driver_backup', 'file');

        // Mock input for SERVER_ADDR
        ee()->setMock('input', new class {
            public function server($key) {
                return $key === 'SERVER_ADDR' ? '127.0.0.1' : null;
            }
        });

        // Mock localize with controllable time
        ee()->setMock('localize', new class {
            public $now = 1000000000; // Fixed timestamp for testing
        });

        // Mock logger (no-op from eeObjectMock)
        // Mock load (no-ops from eeObjectMock)

        // Load file helper for file operations
        require_once SYSPATH . 'ee/legacy/helpers/file_helper.php';
    }

    /**
     * Create a Cache instance with specified adapter
     *
     * @param string $adapter The adapter to use ('file', 'dummy', etc.)
     * @return \Cache
     */
    protected function makeCacheWithAdapter(string $adapter): \Cache
    {
        ee()->config->setItem('cache_driver', $adapter);
        return new \Cache();
    }

    /**
     * Create a file driver instance with specified cache path
     *
     * @param string $cachePath The path to use for caching
     * @return \EE_Cache_file
     */
    protected function makeFileDriverAt(string $cachePath): \EE_Cache_file
    {
        $driver = new \EE_Cache_file();
        $this->setProtectedProperty($driver, '_cache_path', $cachePath);
        return $driver;
    }

    /**
     * Set a protected/private property on an object using reflection
     *
     * @param object $object The object to modify
     * @param string $property The property name
     * @param mixed $value The value to set
     */
    protected function setProtectedProperty($object, string $property, $value)
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }

    /**
     * Get a protected/private property from an object using reflection
     *
     * @param object $object The object to read from
     * @param string $property The property name
     * @return mixed The property value
     */
    protected function getProtectedProperty($object, string $property)
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }

    /**
     * Define essential EE constants for testing
     */
    protected function defineConstants()
    {
        if (!defined('SYSPATH')) {
            define('SYSPATH', realpath(__DIR__ . '/../../../../../../system/ee/') . DIRECTORY_SEPARATOR);
        }
        if (!defined('BASEPATH')) {
            define('BASEPATH', realpath(__DIR__ . '/../../../../../../system/ee/legacy/') . DIRECTORY_SEPARATOR);
        }
        if (!defined('APPPATH')) {
            define('APPPATH', realpath(__DIR__ . '/../../../../../../system/user/') . DIRECTORY_SEPARATOR);
        }
        // Use a writable temp directory for cache tests
        if (!defined('PATH_CACHE')) {
            $testCacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ee_test_cache_' . uniqid();
            if (!is_dir($testCacheDir)) {
                mkdir($testCacheDir, 0777, true);
            }
            // Ensure it's writable
            chmod($testCacheDir, 0777);
            define('PATH_CACHE', $testCacheDir . DIRECTORY_SEPARATOR);
        }
        if (!defined('FILE_WRITE_MODE')) {
            define('FILE_WRITE_MODE', 0666);
        }
        if (!defined('DIR_WRITE_MODE')) {
            define('DIR_WRITE_MODE', 0777);
        }
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }
        if (!defined('AJAX_REQUEST')) {
            define('AJAX_REQUEST', false);
        }
        if (!defined('EXIT_UNKNOWN_METHOD')) {
            define('EXIT_UNKNOWN_METHOD', 7);
        }
    }

    /**
     * Load the Cache library and driver files
     */
    protected function loadCacheLibrary()
    {
        // Load EE common functions (defines is_really_writable, etc.)
        $commonPath = SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
        if (file_exists($commonPath)) {
            require_once $commonPath;
        }

        // Load CI Driver base classes first
        $driverPath = BASEPATH . 'libraries/Driver.php';
        if (file_exists($driverPath)) {
            require_once $driverPath;
        }

        // Load array helper that's needed by memcached driver
        $helperPath = BASEPATH . 'helpers/array_helper.php';
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }

        // Load the main Cache library
        $cachePath = BASEPATH . 'libraries/Cache/Cache.php';
        if (file_exists($cachePath)) {
            require_once $cachePath;
        }

        // Load cache driver files
        $drivers = ['Cache_file', 'Cache_dummy', 'Cache_memcached', 'Cache_redis'];
        foreach ($drivers as $driver) {
            $driverPath = BASEPATH . 'libraries/Cache/drivers/' . $driver . '.php';
            if (file_exists($driverPath)) {
                require_once $driverPath;
            }
        }

        // Load file helper for file operations
        $helperPath = BASEPATH . 'helpers/file_helper.php';
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
    }

    /**
     * Recursively remove a directory and all its contents
     *
     * @param string $dir The directory to remove
     */
    protected function removeDirectory(string $dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
