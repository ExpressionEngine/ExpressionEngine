<?php

use ExpressionEngine\Library\Security\XSS;
use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
require_once BASEPATH . 'helpers/xss_helper.php';
require_once PATH_ADDONS . 'moblog/mod.moblog.php';

class MoblogAttachmentUploadTest extends TestCase
{
    private $filemanager;
    private $moblog;
    private $security;
    private $upload;

    protected function setUp(): void
    {
        parent::setUp();

        ee()->resetMocks();

        $this->filemanager = new MoblogAttachmentFilemanagerMock();
        $this->moblog = new Moblog();
        $this->security = new XSS();
        $this->upload = new MoblogAttachmentUploadMock();

        $this->moblog->moblog_array['moblog_upload_directory'] = '7';
        $this->moblog->newline = "\n";

        ee()->setMock('config', new MoblogAttachmentConfigMock());
        ee()->setMock('filemanager', $this->filemanager);
        ee()->setMock('load', new MoblogAttachmentLoadMock());
        ee()->setMock('Permission', new MoblogAttachmentPermissionMock());
        ee()->setMock('Security/XSS', $this->security);
        ee()->setMock('upload', $this->upload);
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();

        parent::tearDown();
    }

    public function testTextAttachmentContentIsXssCleanedBeforeUpload()
    {
        $attachment = implode("\n", array(
            'Content-Type: application/octet-stream; name="moblog-attachment.txt"',
            'Content-Transfer-Encoding: base64',
            '',
            base64_encode('"><script>alert(\'stored xss\')</script>')
        ));

        $result = $this->invokeProcessAttachment($attachment);

        $this->assertTrue($result);
        $this->assertCount(1, $this->upload->rawUploadCalls);
        $this->assertStringNotContainsString('<script', $this->upload->rawUploadCalls[0][1]);
        $this->assertStringContainsString('[removed]', $this->upload->rawUploadCalls[0][1]);
        $this->assertCount(1, $this->filemanager->saveFileCalls);
        $this->assertSame('/tmp/moblog-attachment.txt', $this->filemanager->saveFileCalls[0][0]);
        $this->assertSame('7', $this->filemanager->saveFileCalls[0][1]);
    }

    public function testImageAttachmentWithXssContentIsRejected()
    {
        $attachment = implode("\n", array(
            'Content-Type: image/jpeg; name="moblog-attachment.jpg"',
            'Content-Transfer-Encoding: base64',
            '',
            base64_encode('GIF89a<script>alert(1)</script>')
        ));

        $result = $this->invokeProcessAttachment($attachment, 'image', 'jpeg');

        $this->assertFalse($result);
        $this->assertCount(0, $this->upload->rawUploadCalls);
        $this->assertCount(0, $this->filemanager->saveFileCalls);
        $this->assertContains('error_writing_attachment', $this->moblog->message_array);
    }

    private function invokeProcessAttachment($attachment, $type = 'application', $subtype = 'octet-stream')
    {
        $method = new ReflectionMethod($this->moblog, '_process_attachment');
        TestReflectionHelper::makeMethodAccessible($method);

        return $method->invoke($this->moblog, $attachment, $type, $subtype);
    }
}

class MoblogAttachmentConfigMock
{
    public function item($key)
    {
        $items = array(
            'xss_clean_uploads' => 'y',
            'xss_clean_member_exception' => false,
            'xss_clean_member_group_exception' => false,
        );

        return array_key_exists($key, $items) ? $items[$key] : null;
    }
}

class MoblogAttachmentFilemanagerMock
{
    public $saveFileCalls = array();

    public function clean_filename($filename, $uploadDirId, $parameters = array())
    {
        return '/tmp/' . $filename;
    }

    public function save_file($filePath, $uploadDirId, $metadata)
    {
        $this->saveFileCalls[] = array($filePath, $uploadDirId, $metadata);

        return array('status' => true);
    }

    public function xss_clean_off()
    {
        return;
    }
}

class MoblogAttachmentLoadMock
{
    public function helper()
    {
        return;
    }

    public function library()
    {
        return;
    }
}

class MoblogAttachmentPermissionMock
{
    public function isSuperAdmin()
    {
        return false;
    }

    public function hasAnyRole()
    {
        return false;
    }
}

class MoblogAttachmentUploadMock
{
    public $file_name = 'moblog-attachment.txt';
    public $rawUploadCalls = array();
    public $upload_path = '/tmp/';

    public function display_errors()
    {
        return 'upload error';
    }

    public function raw_upload($filename, $fileCode)
    {
        $this->rawUploadCalls[] = array($filename, $fileCode);

        return true;
    }
}
