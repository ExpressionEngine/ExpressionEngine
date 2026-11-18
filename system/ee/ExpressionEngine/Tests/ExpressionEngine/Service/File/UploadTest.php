<?php

namespace ExpressionEngine\Tests\ExpressionEngine\Service\File;

require_once __DIR__ . '/../../../eeObjectMock.php';
require_once SYSPATH . 'ee/ExpressionEngine/Service/File/Upload.php';

use ExpressionEngine\Service\File\Upload;
use PHPUnit\Framework\TestCase;

class FileUploadConflictFileMock
{
    public $file_name = 'photo_1.jpg';
    public $title = 'photo_1.jpg';

    public function memberHasAccess($member)
    {
        return true;
    }
}

class FileUploadConflictModelMock
{
    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function get()
    {
        return $this;
    }

    public function with()
    {
        return $this;
    }

    public function first()
    {
        return $this->file;
    }
}

class FileUploadConflictInputMock
{
    private $post;

    public function __construct(array $post)
    {
        $this->post = $post;
    }

    public function post($key)
    {
        return $this->post[$key] ?? null;
    }
}

class FileUploadConflictAlertMock
{
    public $alerts = [];

    public function makeInline($name)
    {
        $alert = new FileUploadConflictAlertRecord($name);
        $this->alerts[] = $alert;

        return $alert;
    }
}

class FileUploadConflictAlertRecord
{
    public $name;
    public $body = [];

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function asIssue()
    {
        return $this;
    }

    public function withTitle($title)
    {
        return $this;
    }

    public function addToBody($body)
    {
        $this->body[] = $body;

        return $this;
    }

    public function now()
    {
        return $this;
    }
}

class UploadTest extends TestCase
{
    public function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * @dataProvider invalidRenameFilenameProvider
     */
    public function testResolveNameConflictRejectsInvalidRenameFilename($filename)
    {
        $file = new FileUploadConflictFileMock();
        $alerts = new FileUploadConflictAlertMock();

        ee()->setMock('Model', new FileUploadConflictModelMock($file));
        ee()->setMock('CP/Alert', $alerts);
        ee()->setMock('input', new FileUploadConflictInputMock([
            'submit' => 'finish',
            'upload_options' => 'rename',
            'original_name' => 'photo.jpg',
            'rename_custom' => $filename,
        ]));

        $result = (new Upload())->resolveNameConflict(123);

        $this->assertFalse($result['success']);
        $this->assertSame($file, $result['params']['file']);
        $this->assertSame('photo.jpg', $result['params']['name']);
        $this->assertSame(['invalid_filename'], $alerts->alerts[0]->body);
    }

    public function invalidRenameFilenameProvider()
    {
        return [
            'single backslash' => ["\\"],
            'path ending in backslash' => ['folder\\'],
            'backslash in filename' => ['blog1\\.j%20pg'],
            'double backslash in filename' => ['blog1\\\\.j%20pg'],
            'path segment with backslash' => ['folder\\photo'],
            'single slash' => ['/'],
            'path ending in slash' => ['folder/'],
            'path segment with slash' => ['folder/photo'],
            'single dot' => ['.'],
            'double dot' => ['..'],
            'dotfile basename' => ['.env'],
        ];
    }
}
