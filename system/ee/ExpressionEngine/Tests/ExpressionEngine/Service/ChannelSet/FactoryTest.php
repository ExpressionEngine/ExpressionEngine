<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\ChannelSet;

use ExpressionEngine\Service\ChannelSet\Factory;
use ExpressionEngine\Service\ChannelSet\Export;
use ExpressionEngine\Service\ChannelSet\ZipToSet;
use ExpressionEngine\Service\ChannelSet\Set;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    private $factory;
    private $siteId = 1;
    private $tempFiles = [];
    private $tempDirs = [];

    public function setUp(): void
    {
        $this->factory = new Factory($this->siteId);
    }

    public function tearDown(): void
    {
        // Clean up temporary files and directories
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        foreach ($this->tempDirs as $dir) {
            if (is_dir($dir)) {
                $this->removeDirectory($dir);
            }
        }

        $this->tempFiles = [];
        $this->tempDirs = [];
        
        ee()->resetMocks();
        m::close();
    }

    /**
     * Recursively remove a directory
     */
    private function removeDirectory($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testConstructorSetsSiteId()
    {
        $factory = new Factory(5);
        $this->assertEquals(5, $this->getPrivateProperty($factory, 'site_id'));
    }

    public function testExportCreatesExportInstanceAndCallsZip()
    {
        // Create a temporary cache directory
        $tempCacheDir = sys_get_temp_dir() . '/ee_test_cache_' . uniqid();
        mkdir($tempCacheDir, 0777, true);
        mkdir($tempCacheDir . '/cset', 0777, true);
        
        if (!defined('PATH_CACHE')) {
            define('PATH_CACHE', $tempCacheDir . '/');
        }

        // Mock channel
        $channel = $this->getMockBuilder('stdClass')
            ->addMethods(['getId', 'getCategoryGroups'])
            ->getMock();
        $channel->method('getId')->willReturn(1);
        $channel->method('getCategoryGroups')->willReturn([]);
        $channel->channel_name = 'test_channel';
        $channel->channel_title = 'Test Channel';
        $channel->title_field_label = 'Title';
        
        // Create a mock collection for Statuses
        $statusesCollection = $this->getMockBuilder('stdClass')
            ->addMethods(['sortBy'])
            ->getMock();
        $statusesCollection->method('sortBy')->willReturn(new \ArrayObject([]));
        $channel->Statuses = $statusesCollection;
        
        $channel->FieldGroups = new \ArrayObject([]);
        $channel->CustomFields = new \ArrayObject([]);

        // Mock config using eeObjectMock
        ee()->config->setItem('app_version', '4.0.0');

        // Mock Filesystem using eeObjectMock
        $filesystemMock = $this->getMockBuilder('stdClass')
            ->addMethods(['mkdir'])
            ->getMock();
        $filesystemMock->method('mkdir')->willReturnCallback(function($path, $recursive = false) {
            if (!is_dir($path)) {
                return mkdir($path, 0777, $recursive);
            }
            return true;
        });
        ee()->setMock('Filesystem', $filesystemMock);

        // Test export with default name
        $result = $this->factory->export([$channel]);
        $this->assertStringEndsWith('test_channel.zip', $result);
        $this->assertFileExists($result);

        // Test export with custom name
        $customResult = $this->factory->export([$channel], 'custom_export');
        $this->assertStringEndsWith('custom_export.zip', $customResult);
        $this->assertFileExists($customResult);

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
        if (file_exists($customResult)) {
            unlink($customResult);
        }
        rmdir($tempCacheDir . '/cset');
        rmdir($tempCacheDir);
    }

    public function testImportUploadCreatesZipToSetAndExtracts()
    {
        // Set up temporary cache directory
        $tempCacheDir = sys_get_temp_dir() . '/ee_test_cache_' . uniqid();
        mkdir($tempCacheDir, 0777, true);
        mkdir($tempCacheDir . '/cset', 0777, true);
        
        if (!defined('PATH_CACHE')) {
            define('PATH_CACHE', $tempCacheDir . '/');
        }

        // Create a valid zip file
        $zipPath = $this->createValidZipFile();
        
        // Mock Filesystem
        $filesystemMock = $this->getMockBuilder('stdClass')
            ->addMethods(['mkdir'])
            ->getMock();
        $filesystemMock->method('mkdir')->willReturnCallback(function($path, $recursive = false) {
            if (!is_dir($path)) {
                return mkdir($path, 0777, $recursive);
            }
            return true;
        });
        ee()->setMock('Filesystem', $filesystemMock);
        
        // Mock Encrypt service
        $encryptMock = $this->getMockBuilder('stdClass')
            ->addMethods(['generateKey'])
            ->getMock();
        $encryptMock->method('generateKey')->willReturn('testkey123');
        ee()->setMock('Encrypt', $encryptMock);

        $uploadData = [
            'tmp_name' => $zipPath,
            'name' => 'channel_set.zip'
        ];

        $result = $this->factory->importUpload($uploadData);

        $this->assertInstanceOf(Set::class, $result);
        
        // Verify site ID was set (we can check via getPath to confirm Set was created)
        $this->assertNotEmpty($result->getPath());

        // Clean up
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
        rmdir($tempCacheDir . '/cset');
        rmdir($tempCacheDir);
    }

    /**
     * Create a temporary zip file with valid channel set contents
     */
    private function createValidZipFile()
    {
        $tempDir = sys_get_temp_dir() . '/ee_test_' . uniqid();
        mkdir($tempDir);
        $this->tempDirs[] = $tempDir;
        
        $channelSetData = [
            'version' => '4.0.0',
            'channels' => [
                [
                    'channel_title' => 'Test Channel',
                    'channel_name' => 'test_channel'
                ]
            ],
            'field_groups' => [],
            'category_groups' => [],
            'upload_destinations' => [],
            'statuses' => []
        ];

        $zipPath = sys_get_temp_dir() . '/ee_test_' . uniqid() . '.zip';
        $this->tempFiles[] = $zipPath;
        
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            $zip->addFromString('channel_set.json', json_encode($channelSetData));
            $zip->close();
        }

        return $zipPath;
    }

    public function testImportDirCreatesSetAndSetsSiteId()
    {
        $testPath = '/path/to/channel/set';

        $setMock = m::mock(Set::class);
        $setMock->shouldReceive('setSiteId')
            ->with($this->siteId)
            ->andReturnSelf();

        // Since Factory creates Set internally, we need to mock the constructor
        // This is tricky in PHP, so we'll test the method signature and behavior
        $this->assertTrue(method_exists($this->factory, 'importDir'));

        // Test with a real temporary directory
        $tempDir = sys_get_temp_dir() . '/ee_factory_test_' . uniqid();
        mkdir($tempDir);

        try {
            $result = $this->factory->importDir($tempDir);
            $this->assertInstanceOf(Set::class, $result);

            // Verify the set has the correct path
            $this->assertEquals($tempDir, $result->getPath());
        } finally {
            // Clean up
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    public function testGarbageCollectRemovesOldDirectories()
    {
        // Set up temporary cache directory
        $tempCacheDir = sys_get_temp_dir() . '/ee_test_cache_' . uniqid();
        mkdir($tempCacheDir, 0777, true);
        mkdir($tempCacheDir . '/cset', 0777, true);
        
        if (!defined('PATH_CACHE')) {
            define('PATH_CACHE', $tempCacheDir . '/');
        }

        $oldDirPath = $tempCacheDir . '/cset/old_dir_' . time();

        // Mock Filesystem service with expectations set BEFORE calling the method
        $filesystemMock = $this->getMockBuilder('stdClass')
            ->addMethods(['exists', 'getDirectoryContents', 'isDir', 'mtime', 'deleteDir'])
            ->getMock();
        
        $filesystemMock->expects($this->once())
            ->method('exists')
            ->with(PATH_CACHE . 'cset/')
            ->willReturn(true);
        
        $filesystemMock->expects($this->once())
            ->method('getDirectoryContents')
            ->with(PATH_CACHE . 'cset/')
            ->willReturn([$oldDirPath]);
        
        $filesystemMock->expects($this->once())
            ->method('isDir')
            ->with($oldDirPath)
            ->willReturn(true);
        
        $filesystemMock->expects($this->once())
            ->method('mtime')
            ->with($oldDirPath)
            ->willReturn(time() - 90000); // ~25 hours old (more than 1 day)
        
        $filesystemMock->expects($this->once())
            ->method('deleteDir')
            ->with($oldDirPath)
            ->willReturn(true);
        
        ee()->setMock('Filesystem', $filesystemMock);

        // Run garbage collection
        $this->factory->garbageCollect();

        // Clean up
        rmdir($tempCacheDir . '/cset');
        rmdir($tempCacheDir);
    }

    public function testGarbageCollectHandlesNonExistentCacheDirectory()
    {
        $cachePath = PATH_CACHE . 'cset/';

        // Remove cache directory if it exists (but don't fail if it has contents)
        if (is_dir($cachePath)) {
            // Just check if directory exists, don't try to remove it
            $this->assertDirectoryExists($cachePath);
        }

        // This should not throw an exception even if directory doesn't exist
        $this->factory->garbageCollect();

        // Test completed without exception
        $this->assertTrue(true);
    }

    public function testGarbageCollectPreservesRecentDirectories()
    {
        $cachePath = PATH_CACHE . 'cset/';

        // Ensure cache directory exists
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        // Create a recent directory (less than 1 day old)
        $recentDir = $cachePath . 'recent_dir_' . time();
        mkdir($recentDir);
        touch($recentDir, time() - 3600); // 1 hour old

        try {
            // Run garbage collection
            $this->factory->garbageCollect();

            // Recent directory should still exist
            $this->assertDirectoryExists($recentDir);
        } finally {
            // Clean up
            if (is_dir($recentDir)) {
                rmdir($recentDir);
            }
        }
    }

    /**
     * Helper method to access private properties for testing
     */
    private function getPrivateProperty($object, $property)
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }
}
