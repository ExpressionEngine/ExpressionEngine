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
    /**
     * @dataProvider fileSyncContentsProvider
     */
    public function testFileSyncPreservesSavedContentUntilACompleteNewerRead($saved, $disk, $mtime, $sync)
    {
        $filesystem = new FileSyncedModelFilesystemStub();
        $filesystem->exists_map['/tmp/template.html'] = true;
        $filesystem->mtime_map['/tmp/template.html'] = $mtime;
        $filesystem->read_map['/tmp/template.html'] = $disk;
        ee()->setMock('Filesystem', $filesystem);

        $model = new FileSyncedModelStub();
        $model->path = '/tmp/template.html';
        $model->serialized_data = $saved;
        $model->modification_time = 10;
        $model->onAfterLoad();

        $this->assertSame($sync ? $disk : null, $model->unserialized_data);
        $this->assertSame($sync ? $mtime : 10, $model->modification_time);
        $this->assertSame($sync ? 1 : 0, $model->save_calls);
        $this->assertSame($sync, $model->isSkipNextWrite());
        $this->assertCount(0, $filesystem->writes);
        $this->assertSame($mtime > 10 ? 1 : 0, $filesystem->read_calls);
    }

    public function fileSyncContentsProvider()
    {
        return array(
            'newer empty file preserves content' => array('saved', '', 20, false),
            'newer whitespace preserves content' => array('saved', " \r\n\t ", 20, false),
            'zero is saved content' => array('0', '', 20, false),
            'zero is valid file content' => array('saved', '0', 20, true),
            'normal nonempty edit' => array('saved', 'updated', 20, true),
            'file content is not trimmed' => array('saved', " updated\n", 20, true),
            'empty database and empty file' => array('', '', 20, true),
            'empty database and whitespace file' => array('', " \n", 20, true),
            'whitespace database and empty file' => array(" \n", '', 20, true),
            'null database and empty file' => array(null, '', 20, true),
            'failed read preserves content' => array('saved', false, 20, false),
            'failed read preserves empty row timestamp' => array('', false, 20, false),
            'older file is ignored' => array('saved', 'old', 5, false),
            'equal file is ignored' => array('saved', 'different', 10, false),
            'equal timestamp does not resurrect intentionally empty data' => array('', 'old', 10, false),
        );
    }

    public function rejectedFileReadsProvider()
    {
        return array(
            'empty' => array(''),
            'whitespace' => array(" \r\n\t "),
            'failed' => array(false),
        );
    }

    /**
     * @dataProvider rejectedFileReadsProvider
     */
    public function testCompletedFileInSameSecondIsImportedAfterRejectedRead($rejected_read)
    {
        $filesystem = new FileSyncedModelFilesystemStub();
        $filesystem->exists_map['/tmp/template.html'] = true;
        $filesystem->mtime_map['/tmp/template.html'] = 20;
        $filesystem->read_map['/tmp/template.html'] = $rejected_read;
        ee()->setMock('Filesystem', $filesystem);
        $model = new FileSyncedModelStub();
        $model->path = '/tmp/template.html';
        $model->serialized_data = 'saved';
        $model->modification_time = 10;
        $model->onAfterLoad();
        $this->assertSame(10, $model->modification_time);
        $this->assertSame(0, $model->save_calls);

        $filesystem->read_map['/tmp/template.html'] = 'completed';
        $model->onAfterLoad();
        $this->assertSame('completed', $model->unserialized_data);
        $this->assertSame(20, $model->modification_time);
        $this->assertSame(1, $model->save_calls);
    }

    public function testExplicitEmptySaveStillWritesEmptyFile()
    {
        $filesystem = new FileSyncedModelFilesystemStub();
        $filesystem->exists_map['/tmp'] = true;
        $filesystem->writable_map['/tmp'] = true;
        ee()->setMock('Filesystem', $filesystem);
        $model = new FileSyncedModelStub();
        $model->path = '/tmp/template.html';
        $model->serialized_data = '';
        $model->onAfterSave();
        $this->assertCount(1, $filesystem->writes);
        $this->assertSame('', $filesystem->writes[0]['data']);
    }

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
    public $read_calls = 0;
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
        $this->read_calls++;
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
