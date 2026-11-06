<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

require_once SYSPATH . 'ee/legacy/libraries/Filemanager.php';
require_once SYSPATH . 'ee/legacy/helpers/xss_helper.php';

use PHPUnit\Framework\TestCase;

class FilemanagerDirectoryTypesTest extends TestCase
{
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    private function saveFile(array $types, bool $passesXss = true, bool $safeMime = true): array
    {
        ee()->resetMocks();
        ee()->setMock('Permission', new class {
            public function isSuperAdmin() { return false; }
        });
        ee()->setMock('config', new class {
            public function item($key) { return false; }
        });
        ee()->setMock('Security/XSS', new class($passesXss) {
            private $passes;
            public function __construct($passes) { $this->passes = $passes; }
            public function clean($path, $isImage) { return $this->passes; }
        });
        ee()->setMock('MimeType', new class($safeMime) {
            private $safe;
            public function __construct($safe) { $this->safe = $safe; }
            public function isSafeForUpload($mime) { return $this->safe; }
            public function isOfKind($mime, $kind) { return $kind === 'img'; }
            public function isImage($mime) { return true; }
        });
        $subject = new class($types) extends \Filemanager {
            private $types;
            public function __construct($types) { $this->types = $types; }
            public function fetch_upload_dir_prefs($id, $ignore_site_id = false)
            {
                return ['allowed_types' => $this->types, 'name' => 'Documents'];
            }
            public function is_editable_image($path, $mime = null)
            {
                throw new \RuntimeException('Passed upload checks');
            }
        };
        return $subject->save_file('test.png', ['id' => 1, 'name' => 'Documents'], ['mime_type' => 'image/png'], false);
    }

    public function testDirectoryTypeRejectionHasItsOwnMessage(): void
    {
        $this->assertSame(['status' => false, 'message' => 'upload_directory_type_not_allowed'], $this->saveFile(['doc']));
    }

    public function testXssRejectionRemainsASecurityFailure(): void
    {
        $this->assertSame(['status' => false, 'message' => 'security_failure'], $this->saveFile(['img'], false));
    }

    public function testUnsafeMimeInUnrestrictedDirectoryRemainsASecurityFailure(): void
    {
        $this->assertSame(['status' => false, 'message' => 'security_failure'], $this->saveFile(['all'], true, false));
    }

    /** @dataProvider imageCompatibleTypes */
    public function testCompatibleTypesStillPassUploadChecks(array $types): void
    {
        $this->expectExceptionMessage('Passed upload checks');
        $this->saveFile($types);
    }

    public function imageCompatibleTypes(): array
    {
        return [[['img']], [['doc', 'img']], [['all']]];
    }
}
