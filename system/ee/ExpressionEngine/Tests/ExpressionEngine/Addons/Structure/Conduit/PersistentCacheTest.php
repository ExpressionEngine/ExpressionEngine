<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/Conduit/StaticCache.php';
require_once __DIR__ . '/../../../../../Addons/structure/Conduit/PersistentCache.php';

use ExpressionEngine\Structure\Conduit\StaticCache;
use ExpressionEngine\Structure\Conduit\PersistentCache;

if (!defined('DIR_WRITE_MODE')) {
    define('DIR_WRITE_MODE', 0777);
}
if (!defined('FILE_WRITE_MODE')) {
    define('FILE_WRITE_MODE', 0666);
}

if (!function_exists('read_file')) {
    function read_file($path)
    {
        $data = @file_get_contents($path);
        return ($data === false) ? false : $data;
    }
}

if (!function_exists('write_file')) {
    function write_file($path, $data)
    {
        global $__structure_force_write_file_failure;
        if (!empty($__structure_force_write_file_failure)) {
            return false;
        }

        return @file_put_contents($path, $data) !== false;
    }
}

class PersistentCacheTest extends StructureTestBase
{
    private $tmpRoot;
    private $origCachePath;
    private $origModulePath;

    protected function setUp(): void
    {
        parent::setUp();

        $rc = new ReflectionClass(PersistentCache::class);
        $cachePath = $rc->getProperty('cache_path');
        $modulePath = $rc->getProperty('module_cache_path');
        \TestReflectionHelper::makePropertyAccessible($cachePath);
        \TestReflectionHelper::makePropertyAccessible($modulePath);

        $this->origCachePath = $cachePath->getValue();
        $this->origModulePath = $modulePath->getValue();

        $this->tmpRoot = sys_get_temp_dir() . '/structure-persistent-cache-tests-' . uniqid('', true);
        @mkdir($this->tmpRoot, 0777, true);

        $cachePath->setValue($this->tmpRoot . '/cache');
        $modulePath->setValue($this->tmpRoot . '/cache/structure/');

        StaticCache::clear();
        global $__structure_force_write_file_failure;
        $__structure_force_write_file_failure = false;
    }

    protected function tearDown(): void
    {
        $rc = new ReflectionClass(PersistentCache::class);
        $cachePath = $rc->getProperty('cache_path');
        $modulePath = $rc->getProperty('module_cache_path');
        \TestReflectionHelper::makePropertyAccessible($cachePath);
        \TestReflectionHelper::makePropertyAccessible($modulePath);
        $cachePath->setValue($this->origCachePath);
        $modulePath->setValue($this->origModulePath);

        $this->deleteRecursively($this->tmpRoot);

        StaticCache::clear();
        global $__structure_force_write_file_failure;
        $__structure_force_write_file_failure = false;

        parent::tearDown();
    }

    public function testSetAndGetRoundTripsDataThroughFilesystem()
    {
        $this->assertTrue(PersistentCache::set('alpha', ['n' => 1]));
        $this->assertTrue(PersistentCache::has('alpha'));

        StaticCache::clear();
        $this->assertSame(['n' => 1], PersistentCache::get('alpha'));
    }

    public function testGetReturnsFalseWhenCacheFileIsMissing()
    {
        StaticCache::clear();
        $this->assertFalse(PersistentCache::get('does-not-exist'));
    }

    public function testGetReturnsPreloadedStaticCacheValue()
    {
        StaticCache::set('raw-key', ['from' => 'static'], true);

        $this->assertSame(['from' => 'static'], PersistentCache::get('raw-key'));
    }

    public function testSetReturnsFalseWhenWriteFails()
    {
        global $__structure_force_write_file_failure;
        $__structure_force_write_file_failure = true;

        $this->assertFalse(PersistentCache::set('cannot-write', ['x' => 1]));
    }

    public function testDeleteRemovesPersistentAndStaticCacheEntries()
    {
        PersistentCache::set('to-delete', ['x' => 2]);
        $this->assertTrue(PersistentCache::has('to-delete'));

        $this->assertTrue(PersistentCache::delete('to-delete'));
        $this->assertFalse(PersistentCache::has('to-delete'));
        $this->assertFalse(PersistentCache::get('to-delete'));
    }

    public function testDeleteReturnsFalseWhenCheckPathFails()
    {
        $fileAsPath = $this->tmpRoot . '/delete-file-as-path';
        @file_put_contents($fileAsPath, 'x');

        $rc = new ReflectionClass(PersistentCache::class);
        $cachePath = $rc->getProperty('cache_path');
        $modulePath = $rc->getProperty('module_cache_path');
        \TestReflectionHelper::makePropertyAccessible($cachePath);
        \TestReflectionHelper::makePropertyAccessible($modulePath);
        $cachePath->setValue($fileAsPath);
        $modulePath->setValue($fileAsPath . '/structure/');

        $this->assertFalse(PersistentCache::delete('not-possible'));
    }

    public function testClearRemovesAllModuleCacheFiles()
    {
        PersistentCache::set('one', ['a' => 1]);
        PersistentCache::set('two', ['b' => 2]);

        $this->assertTrue(PersistentCache::clear());
        $this->assertFalse(PersistentCache::has('one'));
        $this->assertFalse(PersistentCache::has('two'));
    }

    public function testCheckPathFailureReturnsFalseForSetAndClear()
    {
        $fileAsPath = $this->tmpRoot . '/file-as-path';
        @file_put_contents($fileAsPath, 'x');

        $rc = new ReflectionClass(PersistentCache::class);
        $cachePath = $rc->getProperty('cache_path');
        $modulePath = $rc->getProperty('module_cache_path');
        \TestReflectionHelper::makePropertyAccessible($cachePath);
        \TestReflectionHelper::makePropertyAccessible($modulePath);

        $cachePath->setValue($fileAsPath);
        $modulePath->setValue($fileAsPath . '/structure/');

        $this->assertFalse(PersistentCache::set('k', ['v' => 1]));
        $this->assertFalse(PersistentCache::clear());
    }

    public function testSetReturnsFalseWhenBaseCacheDirectoryIsNotWritable()
    {
        $base = $this->tmpRoot . '/readonly-base';
        @mkdir($base, 0555, true);

        $rc = new ReflectionClass(PersistentCache::class);
        $cachePath = $rc->getProperty('cache_path');
        $modulePath = $rc->getProperty('module_cache_path');
        \TestReflectionHelper::makePropertyAccessible($cachePath);
        \TestReflectionHelper::makePropertyAccessible($modulePath);
        $cachePath->setValue($base);
        $modulePath->setValue($base . '/structure/');

        $this->assertFalse(PersistentCache::set('rw-fail', ['v' => 1]));
        @chmod($base, 0777);
    }

    public function testSetReturnsFalseWhenModuleDirectoryCannotBeCreated()
    {
        $base = $this->tmpRoot . '/module-create-fail';
        @mkdir($base . '/cache', 0777, true);
        @file_put_contents($base . '/cache/not-a-dir', 'x');

        $rc = new ReflectionClass(PersistentCache::class);
        $cachePath = $rc->getProperty('cache_path');
        $modulePath = $rc->getProperty('module_cache_path');
        \TestReflectionHelper::makePropertyAccessible($cachePath);
        \TestReflectionHelper::makePropertyAccessible($modulePath);
        $cachePath->setValue($base . '/cache');
        $modulePath->setValue($base . '/cache/not-a-dir/structure/');

        $this->assertFalse(PersistentCache::set('module-create-fail', ['v' => 1]));
    }

    public function testSetReturnsFalseWhenModuleDirectoryIsNotWritable()
    {
        $base = $this->tmpRoot . '/module-readonly';
        @mkdir($base . '/cache/structure', 0777, true);
        @chmod($base . '/cache/structure', 0555);

        $rc = new ReflectionClass(PersistentCache::class);
        $cachePath = $rc->getProperty('cache_path');
        $modulePath = $rc->getProperty('module_cache_path');
        \TestReflectionHelper::makePropertyAccessible($cachePath);
        \TestReflectionHelper::makePropertyAccessible($modulePath);
        $cachePath->setValue($base . '/cache');
        $modulePath->setValue($base . '/cache/structure/');

        $this->assertFalse(PersistentCache::set('module-rw-fail', ['v' => 1]));
        @chmod($base . '/cache/structure', 0777);
    }

    private function deleteRecursively($path): void
    {
        if (empty($path) || !file_exists($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            @chmod($path, 0777);
            @unlink($path);
            return;
        }

        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $this->deleteRecursively($path . '/' . $item);
        }

        @chmod($path, 0777);
        @rmdir($path);
    }
}
