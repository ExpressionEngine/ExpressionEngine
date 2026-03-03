<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Model;

use PHPUnit\Framework\TestCase;

class FileSyncedModelTest extends TestCase
{
    public function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testOnAfterLoadReturnsWhenPathIsMissing()
    {
        $model = new FileSyncedModelStub();
        $model->path = null;

        $model->onAfterLoad();

        $this->assertSame(0, $model->save_calls);
    }

    public function testOnAfterLoadWritesFileWhenMissing()
    {
        $filesystem = new FileSyncedModelFilesystemStub();
        $filesystem->exists_map['/tmp/template.html'] = false;
        $filesystem->exists_map['/tmp'] = true;
        $filesystem->writable_map['/tmp'] = true;
        ee()->setMock('Filesystem', $filesystem);

        $model = new FileSyncedModelStub();
        $model->path = '/tmp/template.html';
        $model->serialized_data = 'serialized-payload';

        $model->onAfterLoad();

        $this->assertCount(1, $filesystem->writes);
        $this->assertSame('/tmp/template.html', $filesystem->writes[0]['path']);
        $this->assertSame('serialized-payload', $filesystem->writes[0]['data']);
        $this->assertTrue($filesystem->writes[0]['overwrite']);
        $this->assertSame(0, $model->save_calls);
    }

    public function testOnAfterLoadSyncsFromNewerFileAndSkipsNextWrite()
    {
        $filesystem = new FileSyncedModelFilesystemStub();
        $filesystem->exists_map['/tmp/template.html'] = true;
        $filesystem->mtime_map['/tmp/template.html'] = 20;
        $filesystem->read_map['/tmp/template.html'] = 'disk-data';
        ee()->setMock('Filesystem', $filesystem);

        $model = new FileSyncedModelStub();
        $model->path = '/tmp/template.html';
        $model->modification_time = 10;

        $model->onAfterLoad();

        $this->assertSame('disk-data', $model->unserialized_data);
        $this->assertSame(20, $model->modification_time);
        $this->assertTrue($model->isSkipNextWrite());
        $this->assertSame(1, $model->save_calls);
    }

    public function testOnAfterSaveSkipsThenWrites()
    {
        $filesystem = new FileSyncedModelFilesystemStub();
        $filesystem->exists_map['/tmp'] = true;
        $filesystem->writable_map['/tmp'] = true;
        ee()->setMock('Filesystem', $filesystem);

        $model = new FileSyncedModelStub();
        $model->path = '/tmp/template.html';
        $model->serialized_data = 'serialized-payload';
        $model->setSkipNextWrite(true);

        $model->onAfterSave();
        $this->assertFalse($model->isSkipNextWrite(), 'skip flag should be reset');

        $model->onAfterSave();
        $this->assertCount(1, $filesystem->writes);
    }

    public function testOnAfterUpdateAndOnAfterDeleteRemoveFilesWhenNeeded()
    {
        $filesystem = new FileSyncedModelFilesystemStub();
        $filesystem->exists_map['/tmp/old.html'] = true;
        $filesystem->exists_map['/tmp/new.html'] = true;
        ee()->setMock('Filesystem', $filesystem);

        $model = new FileSyncedModelStub();
        $model->path = '/tmp/new.html';

        $model->onAfterUpdate(array('path' => '/tmp/old.html'));
        $model->onAfterDelete();

        $this->assertSame(array('/tmp/old.html', '/tmp/new.html'), $filesystem->deleted);
    }

    public function testWriteToFileRequiresWritableExistingDirectory()
    {
        $filesystem = new FileSyncedModelFilesystemStub();
        $filesystem->exists_map['/tmp'] = false;
        $filesystem->writable_map['/tmp'] = false;
        ee()->setMock('Filesystem', $filesystem);

        $model = new FileSyncedModelStub();
        $model->path = '/tmp/template.html';
        $model->serialized_data = 'payload';

        // no path
        $model->path = null;
        $model->callWriteToFile();
        $this->assertCount(0, $filesystem->writes);

        $model->path = '/tmp/template.html';
        // directory missing
        $model->callWriteToFile();
        $this->assertCount(0, $filesystem->writes);

        // directory exists but is not writable
        $filesystem->exists_map['/tmp'] = true;
        $model->callWriteToFile();
        $this->assertCount(0, $filesystem->writes);
    }
}

class FileSyncedModelStub extends \ExpressionEngine\Service\Model\FileSyncedModel
{
    public $path;
    public $modification_time = 0;
    public $serialized_data = '';
    public $unserialized_data;
    public $save_calls = 0;

    public function getFilePath()
    {
        return $this->path;
    }

    public function getModificationTime()
    {
        return $this->modification_time;
    }

    public function setModificationTime($mtime)
    {
        $this->modification_time = $mtime;
    }

    protected function getPreviousFilePath($previous)
    {
        return isset($previous['path']) ? $previous['path'] : null;
    }

    protected function serializeFileData()
    {
        return $this->serialized_data;
    }

    protected function unserializeFileData($str)
    {
        $this->unserialized_data = $str;
    }

    public function save()
    {
        $this->save_calls++;

        return $this;
    }

    public function setSkipNextWrite($value)
    {
        $this->_skip_next_write = $value;
    }

    public function isSkipNextWrite()
    {
        return $this->_skip_next_write;
    }

    public function callWriteToFile()
    {
        $this->writeToFile();
    }
}

class FileSyncedModelFilesystemStub
{
    public $exists_map = array();
    public $mtime_map = array();
    public $read_map = array();
    public $writable_map = array();
    public $writes = array();
    public $deleted = array();

    public function exists($path)
    {
        return isset($this->exists_map[$path]) ? $this->exists_map[$path] : false;
    }

    public function mtime($path)
    {
        return isset($this->mtime_map[$path]) ? $this->mtime_map[$path] : 0;
    }

    public function read($path)
    {
        return isset($this->read_map[$path]) ? $this->read_map[$path] : '';
    }

    public function dirname($path)
    {
        return dirname($path);
    }

    public function isWritable($path)
    {
        return isset($this->writable_map[$path]) ? $this->writable_map[$path] : false;
    }

    public function write($path, $data, $overwrite)
    {
        $this->writes[] = array(
            'path' => $path,
            'data' => $data,
            'overwrite' => $overwrite,
        );
    }

    public function delete($path)
    {
        $this->deleted[] = $path;
    }
}

// EOF
