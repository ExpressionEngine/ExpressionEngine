<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

require_once __DIR__ . '/../../../eeObjectMock.php';
require_once SYSPATH . 'ee/legacy/helpers/multibyte_helper.php';
require_once SYSPATH . 'ee/legacy/libraries/Upload.php';

use PHPUnit\Framework\TestCase;

class UploadTestFilesystemMock
{
    public function isLocal()
    {
        return true;
    }

    public function exists($path)
    {
        return file_exists($path);
    }

    public function isWritable($path)
    {
        return is_writable($path);
    }

    public function getUniqueFilename($path)
    {
        return $path . '_1';
    }
}

class UploadTestMimeTypeMock
{
    public function ofFile($path)
    {
        return 'text/plain';
    }

    public function fileIsImage($path)
    {
        return false;
    }

    public function fileIsSafeForUpload($path)
    {
        return true;
    }
}

class UploadTest extends TestCase
{
    private $uploadPath;

    public function setUp(): void
    {
        ee()->resetMocks();

        $this->uploadPath = sys_get_temp_dir() . '/ee_upload_test_' . uniqid();
        mkdir($this->uploadPath, 0777, true);

        ee()->setMock('Filesystem', new UploadTestFilesystemMock());
        ee()->setMock('MimeType', new UploadTestMimeTypeMock());
    }

    public function tearDown(): void
    {
        if (isset($_FILES['userfile']['tmp_name']) && file_exists($_FILES['userfile']['tmp_name'])) {
            unlink($_FILES['userfile']['tmp_name']);
        }
        $_FILES = [];

        if ($this->uploadPath && is_dir($this->uploadPath)) {
            foreach (glob($this->uploadPath . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($this->uploadPath);
        }

        ee()->resetMocks();
    }

    /**
     * @dataProvider invalidClientFilenamesProvider
     */
    public function testRawUploadRejectsInvalidClientFilenames($filename)
    {
        $upload = new \EE_Upload([
            'upload_path' => $this->uploadPath,
            'xss_clean' => false,
        ]);

        $this->assertFalse($upload->raw_upload($filename, 'test file'));
        $this->assertSame('upload_invalid_file', $upload->display_errors('', ''));
    }

    public function invalidClientFilenamesProvider()
    {
        return [
            'single backslash' => ["\\"],
            'multiple backslashes' => ["\\\\"],
            'path ending in backslash' => ['folder\\'],
            'single slash' => ['/'],
            'path ending in slash' => ['folder/'],
            'mixed separators' => ['\\/'],
            'single dot' => ['.'],
            'double dot' => ['..'],
            'slash dot' => ['/.'],
            'slash double dot' => ['/..'],
            'dotfile basename' => ['.htaccess'],
            'nested dotfile basename' => ['folder/.env'],
            'blocked web config basename' => ['web.config'],
            'blocked web config trailing dot' => ['web.config.'],
            'nested blocked web config basename' => ['folder/web.config'],
            'control character creates blocked extension' => ["shell.ph\0p"],
        ];
    }

    public function testConfigConstructorInitializesBlockedExtensions()
    {
        $upload = new \EE_Upload([
            'upload_path' => $this->uploadPath,
            'xss_clean' => false,
        ]);

        $this->assertFalse($upload->raw_upload('shell.php', 'test file'));
        $this->assertSame('upload_invalid_file', $upload->display_errors('', ''));
    }

    public function testFileNameOverrideIsValidatedAfterNormalization()
    {
        $upload = new \EE_Upload([
            'upload_path' => $this->uploadPath,
            'file_name' => "shell.ph\0p",
            'xss_clean' => false,
        ]);

        $this->assertFalse($upload->raw_upload('safe.txt', 'test file'));
        $this->assertSame('upload_invalid_file', $upload->display_errors('', ''));
    }

    public function testRawUploadAcceptsNormalFilename()
    {
        $upload = new \EE_Upload([
            'upload_path' => $this->uploadPath,
            'xss_clean' => false,
        ]);

        $this->assertTrue($upload->raw_upload('safe.txt', 'test file'));
        $this->assertSame('safe.txt', $upload->file_name);
        $this->assertFileExists($this->uploadPath . '/safe.txt');
    }
}
