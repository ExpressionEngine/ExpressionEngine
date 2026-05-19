<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

require_once __DIR__ . '/../../../eeObjectMock.php';
require_once SYSPATH . 'ee/legacy/helpers/multibyte_helper.php';
require_once SYSPATH . 'ee/legacy/libraries/Filemanager.php';
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

    public function basename($path)
    {
        return basename($path);
    }

    public function dirname($path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }
}

class UploadTestDirectoryMock
{
    private $filesystem;

    public function __construct($filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getFilesystem()
    {
        return $this->filesystem;
    }
}

class UploadTestSecurityMock
{
    public function sanitize_filename($filename, $relative_path = false)
    {
        return $filename;
    }
}

class FilemanagerCleanHarness extends \Filemanager
{
    private $filesystem;

    public function __construct($filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function fetch_upload_dir_prefs($dir_id, $ignore_site_id = false)
    {
        return [
            'directory' => new UploadTestDirectoryMock($this->filesystem),
            'adapter' => 'local',
            'server_path' => '/var/www/uploads/',
        ];
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
        ee()->setMock('security', new UploadTestSecurityMock());
    }

    public function tearDown(): void
    {
        if (isset($_FILES['userfile']['tmp_name']) && file_exists($_FILES['userfile']['tmp_name'])) {
            unlink($_FILES['userfile']['tmp_name']);
        }
        $_FILES = [];

        if ($this->uploadPath && is_dir($this->uploadPath)) {
            rmdir($this->uploadPath);
        }

        ee()->resetMocks();
    }

    /**
     * @dataProvider invalidFilenamesProvider
     */
    public function testRawUploadRejectsInvalidFilenames($filename)
    {
        $upload = new \EE_Upload([
            'upload_path' => $this->uploadPath,
            'xss_clean' => false,
        ]);

        $this->assertFalse($upload->raw_upload($filename, 'test file'));
        $this->assertSame('upload_invalid_file', $upload->display_errors('', ''));
    }

    public function invalidFilenamesProvider()
    {
        return [
            'single backslash' => ["\\"],
            'multiple backslashes' => ["\\\\"],
            'single slash' => ['/'],
            'mixed separators' => ['\\/'],
            'single dot' => ['.'],
            'double dot' => ['..'],
            'slash dot' => ['/.'],
            'slash double dot' => ['/..'],
            'dotfile' => ['.htaccess'],
            'nested dotfile' => ['folder/.env'],
            'blocked web config' => ['web.config'],
            'blocked web config trailing dot' => ['web.config.'],
            'nested blocked web config' => ['folder/web.config'],
            'control character dotfile' => ["\0.htaccess"],
            'control character web config' => ["\0web.config"],
        ];
    }

    public function testSetFilenameRejectsEmptyFilename()
    {
        $upload = new \EE_Upload([
            'upload_path' => $this->uploadPath,
            'xss_clean' => false,
        ]);

        $this->assertFalse($upload->set_filename($this->uploadPath . '/', ''));
        $this->assertSame('upload_invalid_file', $upload->display_errors('', ''));
    }

    public function testSetFilenameRejectsTraversalLikeDirectoryNames()
    {
        $upload = new \EE_Upload([
            'upload_path' => $this->uploadPath,
            'xss_clean' => false,
        ]);

        $this->assertFalse($upload->set_filename($this->uploadPath . '/', '.'));
        $this->assertSame('upload_invalid_file', $upload->display_errors('', ''));
    }

    public function testFilemanagerCleanSubdirRejectsTraversalSegments()
    {
        $filemanager = new FilemanagerCleanHarness(new UploadTestFilesystemMock());

        $this->assertSame('', $filemanager->clean_subdir_and_filename('../secret.txt', 1));
        $this->assertSame('', $filemanager->clean_subdir_and_filename('sub/../secret.txt', 1));
    }

    public function testFilemanagerCleanFilenameDoesNotPrefixRejectedTraversal()
    {
        $filemanager = new FilemanagerCleanHarness(new UploadTestFilesystemMock());

        $this->assertSame('', $filemanager->clean_filename('../secret.txt', 1));
    }

    public function testFilemanagerCleanSubdirPreservesSafeSubdirectories()
    {
        $filemanager = new FilemanagerCleanHarness(new UploadTestFilesystemMock());

        $this->assertSame('sub/file.txt', $filemanager->clean_subdir_and_filename('sub/file.txt', 1));
    }
}
