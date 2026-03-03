<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\ChannelSet;

use ExpressionEngine\Service\ChannelSet\Export;
use PHPUnit\Framework\TestCase;

class ExportTest extends TestCase
{
    private $export;

    public function setUp(): void
    {
        $this->export = new Export();
    }

    private function ensureCacheDir(): array
    {
        $cleanupTemp = false;

        if (!defined('PATH_CACHE')) {
            $tempDir = sys_get_temp_dir() . '/ee_test_' . uniqid();
            define('PATH_CACHE', rtrim($tempDir, '/\\') . '/');
            $cleanupTemp = true;
        }

        $cacheDir = rtrim(PATH_CACHE, '/\\') . '/';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        if (!is_dir($cacheDir . 'cset')) {
            mkdir($cacheDir . 'cset', 0777, true);
        }

        return [$cacheDir, $cleanupTemp];
    }

    protected static function call($name, ...$args)
    {
        // If first arg is an Export instance, use it; otherwise create new one
        $export = null;
        if (!empty($args) && $args[0] instanceof Export) {
            $export = array_shift($args);
        }
        
        if ($export === null) {
            $export = new Export();
        }
        
        $method = new \ReflectionMethod($export, $name);
        \TestReflectionHelper::makeMethodAccessible($method);

        array_unshift($args, $export);

        return call_user_func_array(array($method, 'invoke'), $args);
    }

    public function testExportFileFieldSettings()
    {
        $channel_field = new \StdClass();
        $channel_field->field_settings = array(
            'num_existing' => 50,
            'show_existing' => 'y',
            'field_content_type' => 'all',
            'allowed_directories' => 'all'
        );

        $file_field_settings = self::call('exportFileFieldSettings', $channel_field);
        $this->assertEquals(
            $file_field_settings->num_existing,
            $channel_field->field_settings['num_existing']
        );
        $this->assertEquals(
            $file_field_settings->show_existing,
            $channel_field->field_settings['show_existing']
        );
        $this->assertEquals(
            $file_field_settings->field_content_type,
            $channel_field->field_settings['field_content_type']
        );
        $this->assertEquals(
            $file_field_settings->allowed_directories,
            $channel_field->field_settings['allowed_directories']
        );
    }

    public function testExportFileFieldSettingsWithSpecifiedDirectories()
    {
        $channel_field = new \StdClass();
        $channel_field->field_settings = array(
            'num_existing' => 50,
            'show_existing' => 'y',
            'field_content_type' => 'all',
            'allowed_directories' => 1 // ID of upload destination
        );

        // Mock Model service to return upload destination
        $uploadDest = new \StdClass();
        $uploadDest->name = 'test_uploads';
        $uploadDest->adapter = 'local';
        $uploadDest->server_path = '/path/to/uploads';
        $uploadDest->url = '{base_url}/uploads';

        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
        
        $queryMock = $this->getMockBuilder('stdClass')
            ->addMethods(['first'])
            ->getMock();
        $queryMock->method('first')->willReturn($uploadDest);
        
        $modelMock->method('get')->willReturn($queryMock);
        ee()->setMock('Model', $modelMock);

        $file_field_settings = self::call('exportFileFieldSettings', $channel_field);
        
        $this->assertEquals(
            $file_field_settings->num_existing,
            $channel_field->field_settings['num_existing']
        );
        $this->assertEquals(
            $file_field_settings->show_existing,
            $channel_field->field_settings['show_existing']
        );
        $this->assertEquals(
            $file_field_settings->field_content_type,
            $channel_field->field_settings['field_content_type']
        );
        // Should return the upload destination name, not the ID
        $this->assertEquals('test_uploads', $file_field_settings->allowed_directories);
    }

    public function testExportFileFieldSettingsWithNullDestination()
    {
        $channel_field = new \StdClass();
        $channel_field->field_settings = array(
            'allowed_directories' => 999 // Non-existent ID
        );

        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
        
        $queryMock = $this->getMockBuilder('stdClass')
            ->addMethods(['first'])
            ->getMock();
        $queryMock->method('first')->willReturn(null); // Destination not found
        
        $modelMock->method('get')->willReturn($queryMock);
        ee()->setMock('Model', $modelMock);

        $file_field_settings = self::call('exportFileFieldSettings', $channel_field);
        
        // Should return 'all' when destination is null
        $this->assertEquals('all', $file_field_settings->allowed_directories);
    }

    public function testExportStatus()
    {
        $status = new \StdClass();
        $status->status = 'draft';
        $status->highlight = '#ff0000';

        $result = self::call('exportStatus', $status);

        $this->assertEquals('draft', $result->name);
        $this->assertEquals('#ff0000', $result->highlight);
    }

    public function testExportCategory()
    {
        $category = $this->getMockBuilder('stdClass')
            ->addMethods(['getCustomFields'])
            ->getMock();
        
        $category->cat_name = 'Test Category';
        $category->cat_url_title = 'test-category';
        $category->cat_description = 'Test description';
        $category->cat_order = 5;

        // Mock custom fields
        $customField = $this->getMockBuilder('stdClass')
            ->addMethods(['getShortName', 'getData'])
            ->getMock();
        $customField->method('getShortName')->willReturn('custom_field');
        $customField->method('getData')->willReturn('custom_value');

        $category->method('getCustomFields')->willReturn([$customField]);

        $result = self::call('exportCategory', $category);

        $this->assertEquals('Test Category', $result->cat_name);
        $this->assertEquals('test-category', $result->cat_url_title);
        $this->assertEquals('Test description', $result->cat_description);
        $this->assertEquals(5, $result->cat_order);
        $this->assertEquals('custom_value', $result->custom_field);
    }

    public function testExportCategoryGroup()
    {
        $group = $this->getMockBuilder('stdClass')
            ->addMethods(['getId'])
            ->getMock();
        $group->method('getId')->willReturn(1);
        $group->group_name = 'test_group';
        $group->sort_order = 'a';

        // Mock categories
        $category1 = $this->getMockBuilder('stdClass')
            ->addMethods(['getCustomFields'])
            ->getMock();
        $category1->cat_name = 'Cat 1';
        $category1->cat_url_title = 'cat-1';
        $category1->cat_description = '';
        $category1->cat_order = 1;
        $category1->method('getCustomFields')->willReturn([]);

        $category2 = $this->getMockBuilder('stdClass')
            ->addMethods(['getCustomFields'])
            ->getMock();
        $category2->cat_name = 'Cat 2';
        $category2->cat_url_title = 'cat-2';
        $category2->cat_description = '';
        $category2->cat_order = 2;
        $category2->method('getCustomFields')->willReturn([]);

        $group->Categories = [$category1, $category2];
        $group->CategoryFields = [];

        $result = self::call('exportCategoryGroup', $group);

        $this->assertEquals('test_group', $result->name);
        $this->assertEquals('a', $result->sort_order);
        $this->assertCount(2, $result->categories);
        $this->assertEquals('Cat 1', $result->categories[0]->cat_name);
        $this->assertEquals('Cat 2', $result->categories[1]->cat_name);
    }

    public function testExportFieldGroup()
    {
        $group = $this->getMockBuilder('stdClass')
            ->addMethods(['getId'])
            ->getMock();
        $group->method('getId')->willReturn(10);
        $group->group_name = 'test_field_group';

        // Mock fields with all required properties
        $field1 = $this->getMockBuilder('stdClass')
            ->addMethods(['getId', 'hasProperty'])
            ->getMock();
        $field1->method('getId')->willReturn(1);
        $field1->method('hasProperty')->willReturn(false);
        $field1->field_name = 'field1';
        $field1->field_type = 'text';
        $field1->field_label = 'Field 1';
        $field1->field_order = 1;
        $field1->field_required = false;
        $field1->field_show_fmt = true;
        $field1->field_list_items = '';
        $field1->field_maxl = 0;
        $field1->field_text_direction = 'ltr';
        $field1->field_settings = [];

        $field2 = $this->getMockBuilder('stdClass')
            ->addMethods(['getId', 'hasProperty'])
            ->getMock();
        $field2->method('getId')->willReturn(2);
        $field2->method('hasProperty')->willReturn(false);
        $field2->field_name = 'field2';
        $field2->field_type = 'textarea';
        $field2->field_label = 'Field 2';
        $field2->field_order = 2;
        $field2->field_required = false;
        $field2->field_show_fmt = true;
        $field2->field_list_items = '';
        $field2->field_maxl = 0;
        $field2->field_text_direction = 'ltr';
        $field2->field_settings = [];
        $field2->field_ta_rows = 10;

        $group->ChannelFields = [$field1, $field2];

        // Create a mock Export instance with zip property
        $export = new Export();
        $zipMock = $this->getMockBuilder('ZipArchive')
            ->getMock();
        $zipMock->method('addFromString')->willReturn(true);
        
        $reflection = new \ReflectionClass($export);
        $zipProp = $reflection->getProperty('zip');
        $zipProp->setAccessible(true);
        $zipProp->setValue($export, $zipMock);

        $result = self::call('exportFieldGroup', $export, $group);

        $this->assertEquals('test_field_group', $result);
        
        // Verify fields were exported (check private fields array)
        $fieldsProp = $reflection->getProperty('fields');
        $fieldsProp->setAccessible(true);
        $fields = $fieldsProp->getValue($export);
        $this->assertArrayHasKey(1, $fields);
        $this->assertArrayHasKey(2, $fields);
    }

    public function testExportFieldGroupSkipsAlreadyExported()
    {
        $group = $this->getMockBuilder('stdClass')
            ->addMethods(['getId'])
            ->getMock();
        $group->method('getId')->willReturn(10);
        $group->group_name = 'test_group';
        $group->ChannelFields = [];

        $export = new Export();
        $reflection = new \ReflectionClass($export);
        
        // Pre-populate field_groups to simulate already exported
        $fieldGroupsProp = $reflection->getProperty('field_groups');
        $fieldGroupsProp->setAccessible(true);
        $fieldGroupsProp->setValue($export, [10 => new \StdClass()]);

        $result = self::call('exportFieldGroup', $export, $group);

        // Should return early, result should be null/empty
        $this->assertNull($result);
    }

    public function testExportRelationshipField()
    {
        $field = $this->getMockBuilder('stdClass')
            ->getMock();
        $field->field_settings = [
            'expired' => true,
            'future' => false,
            'allow_multiple' => true,
            'limit' => 50,
            'order_field' => 'entry_date',
            'order_dir' => 'desc',
            'channels' => [1, 2]
        ];

        // Pre-populate channels in export
        $export = new Export();
        $reflection = new \ReflectionClass($export);
        $channelsProp = $reflection->getProperty('channels');
        $channelsProp->setAccessible(true);
        $channelsProp->setValue($export, [
            1 => (object)['channel_title' => 'Channel One'],
            2 => (object)['channel_title' => 'Channel Two']
        ]);

        // Mock Model service for exportRelatedChannels (which may be called)
        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
        
        $queryMock = $this->getMockBuilder('stdClass')
            ->addMethods(['all'])
            ->getMock();
        $queryMock->method('all')->willReturn([]);
        
        $modelMock->method('get')->willReturn($queryMock);
        ee()->setMock('Model', $modelMock);

        $result = self::call('exportRelationshipField', $export, $field);

        $this->assertEquals('y', $result->expired);
        $this->assertObjectNotHasProperty('future', $result); // false values are not included
        $this->assertEquals('y', $result->allow_multiple);
        $this->assertEquals(50, $result->limit);
        $this->assertEquals('entry_date', $result->order_field);
        $this->assertEquals('desc', $result->order_dir);
        $this->assertArrayHasKey('channels', (array)$result);
        $this->assertContains('Channel One', $result->channels);
        $this->assertContains('Channel Two', $result->channels);
    }

    public function testExportRelationshipFieldWithDefaults()
    {
        $field = $this->getMockBuilder('stdClass')
            ->getMock();
        $field->field_settings = [
            'expired' => false,
            'future' => false,
            'allow_multiple' => false,
            'limit' => 100,
            'order_field' => 'title',
            'order_dir' => 'asc'
        ];

        $export = new Export();
        $result = self::call('exportRelationshipField', $export, $field);

        // Default values should not be included
        $this->assertObjectNotHasProperty('expired', $result);
        $this->assertObjectNotHasProperty('future', $result);
        $this->assertEquals('n', $result->allow_multiple);
        $this->assertObjectNotHasProperty('limit', $result);
        $this->assertObjectNotHasProperty('order_field', $result);
        $this->assertObjectNotHasProperty('order_dir', $result);
    }

    public function testExportFluidFieldField()
    {
        $field = $this->getMockBuilder('stdClass')
            ->getMock();
        $field->field_settings = [
            'field_channel_fields' => [1, 2]
        ];

        // Mock Model service
        $field1 = $this->getMockBuilder('stdClass')
            ->addMethods(['getId', 'hasProperty'])
            ->getMock();
        $field1->method('getId')->willReturn(1);
        $field1->method('hasProperty')->willReturn(false);
        $field1->field_name = 'field1';
        $field1->field_type = 'text';
        $field1->field_label = 'Field 1';
        $field1->field_order = 1;
        $field1->field_required = false;
        $field1->field_show_fmt = true;
        $field1->field_list_items = '';
        $field1->field_maxl = 0;
        $field1->field_text_direction = 'ltr';
        $field1->field_settings = [];

        $field2 = $this->getMockBuilder('stdClass')
            ->addMethods(['getId', 'hasProperty'])
            ->getMock();
        $field2->method('getId')->willReturn(2);
        $field2->method('hasProperty')->willReturn(false);
        $field2->field_name = 'field2';
        $field2->field_type = 'textarea';
        $field2->field_label = 'Field 2';
        $field2->field_order = 2;
        $field2->field_required = false;
        $field2->field_show_fmt = true;
        $field2->field_list_items = '';
        $field2->field_maxl = 0;
        $field2->field_text_direction = 'ltr';
        $field2->field_settings = [];
        $field2->field_ta_rows = 10;

        $modelMock = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
        
        $queryMock = $this->getMockBuilder('stdClass')
            ->addMethods(['first'])
            ->getMock();
        $queryMock->method('first')->willReturnOnConsecutiveCalls($field1, $field2);
        
        $modelMock->method('get')->willReturn($queryMock);
        ee()->setMock('Model', $modelMock);

        $export = new Export();
        $zipMock = $this->getMockBuilder('ZipArchive')
            ->getMock();
        $zipMock->method('addFromString')->willReturn(true);
        
        $reflection = new \ReflectionClass($export);
        $zipProp = $reflection->getProperty('zip');
        $zipProp->setAccessible(true);
        $zipProp->setValue($export, $zipMock);

        $result = self::call('exportFluidFieldField', $export, $field);

        $this->assertArrayHasKey('field_channel_fields', (array)$result);
        $this->assertContains('field1', $result->field_channel_fields);
        $this->assertContains('field2', $result->field_channel_fields);
    }

    public function testZipCreatesZipFileWithDefaultName()
    {
        [$cacheDir, $cleanupTemp] = $this->ensureCacheDir();

        // Mock channel
        $channel = $this->getMockBuilder('stdClass')
            ->addMethods(['getId', 'getCategoryGroups'])
            ->getMock();
        $channel->method('getId')->willReturn(1);
        $channel->method('getCategoryGroups')->willReturn([]);
        $channel->channel_name = 'test_channel';
        $channel->channel_title = 'Test Channel';
        $channel->title_field_label = 'Title';
        
        // Create a mock collection for Statuses with sortBy method
        $statusesCollection = $this->getMockBuilder('stdClass')
            ->addMethods(['sortBy'])
            ->getMock();
        $statusesCollection->method('sortBy')->willReturn(new \ArrayObject([]));
        $channel->Statuses = $statusesCollection;
        
        $channel->FieldGroups = new \ArrayObject([]);
        $channel->CustomFields = new \ArrayObject([]);

        // Mock config
        ee()->config->setItem('app_version', '4.0.0');

        // Mock Filesystem
        $filesystemMock = $this->getMockBuilder('stdClass')
            ->addMethods(['mkdir'])
            ->getMock();
        $filesystemMock->method('mkdir')->willReturn(true);
        ee()->setMock('Filesystem', $filesystemMock);

        $export = new Export();
        $result = $export->zip([$channel]);

        $this->assertStringEndsWith('test_channel.zip', $result);
        $this->assertFileExists($result);

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
        if ($cleanupTemp) {
            rmdir($cacheDir . 'cset');
            rmdir($cacheDir);
        }
    }

    public function testZipCreatesZipFileWithCustomName()
    {
        [$cacheDir, $cleanupTemp] = $this->ensureCacheDir();

        // Mock channel
        $channel = $this->getMockBuilder('stdClass')
            ->addMethods(['getId', 'getCategoryGroups'])
            ->getMock();
        $channel->method('getId')->willReturn(1);
        $channel->method('getCategoryGroups')->willReturn([]);
        $channel->channel_name = 'test_channel';
        $channel->channel_title = 'Test Channel';
        $channel->title_field_label = 'Title';
        
        // Create a mock collection for Statuses with sortBy method
        $statusesCollection = $this->getMockBuilder('stdClass')
            ->addMethods(['sortBy'])
            ->getMock();
        $statusesCollection->method('sortBy')->willReturn(new \ArrayObject([]));
        $channel->Statuses = $statusesCollection;
        
        $channel->FieldGroups = new \ArrayObject([]);
        $channel->CustomFields = new \ArrayObject([]);

        // Mock config
        ee()->config->setItem('app_version', '4.0.0');

        // Mock Filesystem
        $filesystemMock = $this->getMockBuilder('stdClass')
            ->addMethods(['mkdir'])
            ->getMock();
        $filesystemMock->method('mkdir')->willReturn(true);
        ee()->setMock('Filesystem', $filesystemMock);

        $export = new Export();
        $result = $export->zip([$channel], 'custom_name');

        $this->assertStringEndsWith('custom_name.zip', $result);
        $this->assertFileExists($result);

        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
        if ($cleanupTemp) {
            rmdir($cacheDir . 'cset');
            rmdir($cacheDir);
        }
    }

    public function testZipIncludesChannelSetJson()
    {
        [$cacheDir, $cleanupTemp] = $this->ensureCacheDir();

        // Mock channel
        $channel = $this->getMockBuilder('stdClass')
            ->addMethods(['getId', 'getCategoryGroups'])
            ->getMock();
        $channel->method('getId')->willReturn(1);
        $channel->method('getCategoryGroups')->willReturn([]);
        $channel->channel_name = 'test_channel';
        $channel->channel_title = 'Test Channel';
        $channel->title_field_label = 'Title';
        
        // Create a mock collection for Statuses with sortBy method
        $statusesCollection = $this->getMockBuilder('stdClass')
            ->addMethods(['sortBy'])
            ->getMock();
        $statusesCollection->method('sortBy')->willReturn(new \ArrayObject([]));
        $channel->Statuses = $statusesCollection;
        
        $channel->FieldGroups = new \ArrayObject([]);
        $channel->CustomFields = new \ArrayObject([]);

        // Mock config
        ee()->config->setItem('app_version', '4.0.0');

        // Mock Filesystem
        $filesystemMock = $this->getMockBuilder('stdClass')
            ->addMethods(['mkdir'])
            ->getMock();
        $filesystemMock->method('mkdir')->willReturn(true);
        ee()->setMock('Filesystem', $filesystemMock);

        $export = new Export();
        $zipPath = $export->zip([$channel], 'test_export');

        // Extract and verify channel_set.json exists
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath));
        
        $jsonContent = $zip->getFromName('channel_set.json');
        $this->assertNotFalse($jsonContent);
        
        $data = json_decode($jsonContent, true);
        $this->assertIsArray($data);
        $this->assertEquals('4.0.0', $data['version']);
        $this->assertArrayHasKey('channels', $data);
        $this->assertArrayHasKey('field_groups', $data);
        $this->assertArrayHasKey('statuses', $data);
        $this->assertArrayHasKey('category_groups', $data);
        $this->assertArrayHasKey('upload_destinations', $data);
        
        $zip->close();

        // Clean up
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
        if ($cleanupTemp) {
            rmdir($cacheDir . 'cset');
            rmdir($cacheDir);
        }
    }

    public function tearDown(): void
    {
        ee()->resetMocks();
    }
}
