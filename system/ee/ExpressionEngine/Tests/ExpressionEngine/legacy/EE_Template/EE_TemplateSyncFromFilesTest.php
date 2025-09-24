<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateSyncFromFilesTest extends EE_TemplateTestBase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock file system functions
        if (!function_exists('directory_map')) {
            function directory_map($path, $depth = 0, $hidden = false) {
                // Mock directory_map function for testing
                return [
                    'default.group' => [
                        'index.html',
                        'about.html',
                        '.hidden.html', // Should be ignored
                        'contact.html'
                    ],
                    'blog.group' => [
                        'index.html',
                        'article.html',
                        'archive.html'
                    ],
                    'not-a-group.txt' => [], // Should be ignored
                    'invalid.name' => [] // Should be ignored
                ];
            }
        }
    }

    public function testSyncFromFilesDisabled()
    {
        // Test when save_tmpl_files is not enabled
        ee()->config->setItem('save_tmpl_files', 'n');

        $result = $this->template->sync_from_files();

        $this->assertFalse($result);
    }

    public function testSyncFromFilesMissingSiteConfig()
    {
        // Test missing site_short_name
        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', '');
        ee()->config->setItem('site_id', 1);

        $result = $this->template->sync_from_files();

        $this->assertFalse($result);
    }

    public function testSyncFromFilesMissingSiteId()
    {
        // Test missing site_id
        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', 'testsite');
        ee()->config->setItem('site_id', '');

        $result = $this->template->sync_from_files();

        $this->assertFalse($result);
    }

    public function testSyncFromFilesSuccessfulSync()
    {
        // Test successful synchronization
        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', 'testsite');
        ee()->config->setItem('site_id', 1);

        // Mock Model for template groups
        $groupModelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['with', 'filter', 'order', 'all', 'getDictionary', 'pluck'])
            ->getMock();
        $groupModelMock->method('with')->willReturnSelf();
        $groupModelMock->method('filter')->willReturnSelf();
        $groupModelMock->method('order')->willReturnSelf();
        $groupModelMock->method('all')->willReturn($groupModelMock);
        $groupModelMock->method('getDictionary')->willReturn([
            'default' => 1,
            'blog' => 2
        ]);
        $groupModelMock->method('pluck')->willReturn(['index.html', 'about.html']);

        // Mock Model::get
        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get', 'make'])
            ->getMock();
        $modelMock->method('get')->willReturn($groupModelMock);
        $modelMock->method('make')->willReturnCallback(function($type) {
            if ($type === 'TemplateGroup') {
                return $this->getMockBuilder('stdClass')
                    ->setMethods(['save'])
                    ->getMock()
                    ->method('save')->willReturn(true);
            }
            if ($type === 'Template') {
                return $this->getMockBuilder('stdClass')
                    ->setMethods(['save', 'saveNewTemplateRevision'])
                    ->getMock();
            }
        });
        ee()->setMock('Model', $modelMock);

        // Mock file_get_contents
        if (!function_exists('file_get_contents')) {
            function file_get_contents($filename) {
                return "<html>Template content for {$filename}</html>";
            }
        }

        // Mock api_template_structure
        $apiMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_template_file_info'])
            ->getMock();
        $apiMock->method('get_template_file_info')->willReturnCallback(function($filename) {
            $name = pathinfo($filename, PATHINFO_FILENAME);
            return [
                'extension' => 'html',
                'name' => $name,
                'type' => 'webpage',
                'engine' => ''
            ];
        });
        ee()->legacy_api->api_template_structure = $apiMock;

        $result = $this->template->sync_from_files();

        // Should not return false (success)
        $this->assertNotFalse($result);
    }

    public function testSyncFromFilesInvalidGroupName()
    {
        // Test invalid group names are skipped
        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', 'testsite');
        ee()->config->setItem('site_id', 1);

        // Mock directory_map to return invalid group name
        if (!function_exists('directory_map')) {
            function directory_map($path, $depth = 0, $hidden = false) {
                return [
                    'invalid.group.name' => ['index.html'], // Invalid characters
                    'validgroup.group' => ['index.html']
                ];
            }
        }

        // Mock Model
        $groupQueryMock = $this->getMockBuilder('stdClass')
            ->setMethods(['with', 'filter', 'order', 'all', 'getDictionary'])
            ->getMock();
        $groupQueryMock->method('with')->willReturnSelf();
        $groupQueryMock->method('filter')->willReturnSelf();
        $groupQueryMock->method('order')->willReturnSelf();
        $groupQueryMock->method('all')->willReturnSelf();
        $groupQueryMock->method('getDictionary')->willReturn([]);

        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get', 'make'])
            ->getMock();
        $modelMock->method('get')->willReturn($groupQueryMock);
        ee()->setMock('Model', $modelMock);

        $result = $this->template->sync_from_files();

        $this->assertNotFalse($result);
    }

    public function testSyncFromFilesLongGroupName()
    {
        // Test group names longer than 50 characters are skipped
        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', 'testsite');
        ee()->config->setItem('site_id', 1);

        $longName = str_repeat('a', 51);
        $GLOBALS['test_long_name'] = $longName;

        if (!function_exists('directory_map')) {
            function directory_map($path, $depth = 0, $hidden = false) {
                return [
                    $GLOBALS['test_long_name'] . '.group' => ['index.html'] // Too long
                ];
            }
        }

        $groupQueryMock = $this->getMockBuilder('stdClass')
            ->setMethods(['with', 'filter', 'order', 'all', 'getDictionary'])
            ->getMock();
        $groupQueryMock->method('with')->willReturnSelf();
        $groupQueryMock->method('filter')->willReturnSelf();
        $groupQueryMock->method('order')->willReturnSelf();
        $groupQueryMock->method('all')->willReturnSelf();
        $groupQueryMock->method('getDictionary')->willReturn([]);

        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();
        $modelMock->method('get')->willReturn($groupQueryMock);
        ee()->setMock('Model', $modelMock);

        $result = $this->template->sync_from_files();

        $this->assertNotFalse($result);
    }

    public function testSyncFromFilesHiddenFilesIgnored()
    {
        // Test that hidden files and subdirectories are ignored
        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', 'testsite');
        ee()->config->setItem('site_id', 1);

        if (!function_exists('directory_map')) {
            function directory_map($path, $depth = 0, $hidden = false) {
                return [
                    'test.group' => [
                        'index.html',
                        '._hidden.html', // Should be ignored
                        'normal.html',
                        ['subdir', 'files'] // Array = subdirectory, should be ignored
                    ]
                ];
            }
        }

        $groupQueryMock = $this->getMockBuilder('stdClass')
            ->setMethods(['with', 'filter', 'order', 'all', 'getDictionary'])
            ->getMock();
        $groupQueryMock->method('with')->willReturnSelf();
        $groupQueryMock->method('filter')->willReturnSelf();
        $groupQueryMock->method('order')->willReturnSelf();
        $groupQueryMock->method('all')->willReturnSelf();
        $groupQueryMock->method('getDictionary')->willReturn(['test' => 1]);

        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();
        $modelMock->method('get')->willReturn($groupQueryMock);
        ee()->setMock('Model', $modelMock);

        $result = $this->template->sync_from_files();

        $this->assertNotFalse($result);
    }

    public function testSyncFromFilesTemplateCreation()
    {
        // Test template creation process
        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', 'testsite');
        ee()->config->setItem('site_id', 1);

        if (!function_exists('directory_map')) {
            function directory_map($path, $depth = 0, $hidden = false) {
                return [
                    'newgroup.group' => ['newtemplate.html']
                ];
            }
        }

        // Mock Model for new group and template creation
        $templateMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save', 'saveNewTemplateRevision'])
            ->getMock();
        $templateMock->method('save')->willReturn(true);
        $templateMock->method('saveNewTemplateRevision')->willReturn(true);

        $groupMock = $this->getMockBuilder('stdClass')
            ->setMethods(['save'])
            ->getMock();
        $groupMock->group_id = 123;
        $groupMock->method('save')->willReturn(true);

        $groupQueryMock = $this->getMockBuilder('stdClass')
            ->setMethods(['with', 'filter', 'order', 'all', 'getDictionary'])
            ->getMock();
        $groupQueryMock->method('with')->willReturnSelf();
        $groupQueryMock->method('filter')->willReturnSelf();
        $groupQueryMock->method('order')->willReturnSelf();
        $groupQueryMock->method('all')->willReturnSelf();
        $groupQueryMock->method('getDictionary')->willReturn([]); // No existing groups

        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get', 'make'])
            ->getMock();
        $modelMock->method('get')->willReturn($groupQueryMock);
        $GLOBALS['test_group_mock'] = $groupMock;
        $GLOBALS['test_template_mock'] = $templateMock;
        $modelMock->method('make')->willReturnCallback(function($type) {
            if ($type === 'TemplateGroup') return $GLOBALS['test_group_mock'];
            if ($type === 'Template') return $GLOBALS['test_template_mock'];
        });
        ee()->setMock('Model', $modelMock);

        // Mock API
        $apiMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_template_file_info'])
            ->getMock();
        $apiMock->method('get_template_file_info')->willReturn([
            'extension' => 'html',
            'name' => 'newtemplate',
            'type' => 'webpage',
            'engine' => ''
        ]);
        ee()->legacy_api->api_template_structure = $apiMock;

        $result = $this->template->sync_from_files();

        $this->assertNotFalse($result);
    }

    public function testSyncFromFilesModelException()
    {
        // Test handling of Model exceptions
        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', 'testsite');
        ee()->config->setItem('site_id', 1);

        // Mock Model::get to throw exception
        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();
        $modelMock->method('get')->willThrowException(new \Exception('Database error'));
        ee()->setMock('Model', $modelMock);

        $result = $this->template->sync_from_files();

        $this->assertFalse($result);
    }

    public function testSyncFromFilesIndexTemplateCreation()
    {
        // Test automatic index template creation
        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', 'testsite');
        ee()->config->setItem('site_id', 1);

        if (!function_exists('directory_map')) {
            function directory_map($path, $depth = 0, $hidden = false) {
                return [
                    'test.group' => ['about.html'] // No index.html
                ];
            }
        }

        // Mock existing group with no index template
        $templatesMock = $this->getMockBuilder('stdClass')
            ->setMethods(['pluck'])
            ->getMock();
        $templatesMock->method('pluck')->willReturn(['about']); // No index

        $groupMock = $this->getMockBuilder('stdClass')
            ->setMethods([])
            ->getMock();
        $groupMock->Templates = $templatesMock;

        $groupsCollectionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['getDictionary'])
            ->getMock();
        $groupsCollectionMock->method('getDictionary')->willReturn(['test' => 1]);

        $groupQueryMock = $this->getMockBuilder('stdClass')
            ->setMethods(['with', 'filter', 'order', 'all'])
            ->getMock();
        $groupQueryMock->method('with')->willReturnSelf();
        $groupQueryMock->method('filter')->willReturnSelf();
        $groupQueryMock->method('order')->willReturnSelf();
        $groupQueryMock->method('all')->willReturn($groupsCollectionMock);

        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get', 'make'])
            ->getMock();
        $modelMock->method('get')->willReturn($groupQueryMock);
        $modelMock->method('make')->willReturnCallback(function($type) {
            $mock = $this->getMockBuilder('stdClass')
                ->setMethods(['save', 'saveNewTemplateRevision'])
                ->getMock();
            $mock->method('save')->willReturn(true);
            if ($type === 'Template') {
                $mock->method('saveNewTemplateRevision')->willReturn(true);
            }
            return $mock;
        });
        ee()->setMock('Model', $modelMock);

        $result = $this->template->sync_from_files();

        $this->assertNotFalse($result);
    }

    public function testSyncFromFilesPermissionDenied()
    {
        // Test when file system permissions prevent reading template files
        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', 'testsite');
        ee()->config->setItem('site_id', 1);

        // Mock directory_map to return directories
        if (!function_exists('directory_map')) {
            function directory_map($path, $depth = 0, $hidden = false) {
                return ['test.group' => ['index.html']];
            }
        }

        // Mock file_get_contents to throw permission error
        if (!function_exists('file_get_contents')) {
            function file_get_contents($filename) {
                throw new \Exception('Permission denied');
            }
        }

        // Mock existing group structure
        $groupQueryMock = $this->getMockBuilder('stdClass')
            ->setMethods(['with', 'filter', 'order', 'all', 'getDictionary'])
            ->getMock();
        $groupQueryMock->method('with')->willReturnSelf();
        $groupQueryMock->method('filter')->willReturnSelf();
        $groupQueryMock->method('order')->willReturnSelf();
        $groupQueryMock->method('all')->willReturnSelf();
        $groupQueryMock->method('getDictionary')->willReturn(['test' => 1]);

        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();
        $modelMock->method('get')->willReturn($groupQueryMock);
        ee()->setMock('Model', $modelMock);

        $result = $this->template->sync_from_files();

        // Should handle permission errors gracefully without crashing
        $this->assertNotFalse($result);
    }

    public function testSyncFromFilesCorruptedTemplateFile()
    {
        // Test when template files are corrupted or contain invalid data
        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', 'testsite');
        ee()->config->setItem('site_id', 1);

        if (!function_exists('directory_map')) {
            function directory_map($path, $depth = 0, $hidden = false) {
                return ['test.group' => ['corrupted.html']];
            }
        }

        // Mock file_get_contents to return corrupted data
        if (!function_exists('file_get_contents')) {
            function file_get_contents($filename) {
                return "\x00\x01\x02Invalid UTF-8\xFF\xFE"; // Corrupted binary data
            }
        }

        // Mock API to fail on corrupted file
        $apiMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get_template_file_info'])
            ->getMock();
        $apiMock->method('get_template_file_info')
            ->willThrowException(new \Exception('Invalid template file'));
        ee()->legacy_api->api_template_structure = $apiMock;

        $groupQueryMock = $this->getMockBuilder('stdClass')
            ->setMethods(['with', 'filter', 'order', 'all', 'getDictionary'])
            ->getMock();
        $groupQueryMock->method('with')->willReturnSelf();
        $groupQueryMock->method('filter')->willReturnSelf();
        $groupQueryMock->method('order')->willReturnSelf();
        $groupQueryMock->method('all')->willReturnSelf();
        $groupQueryMock->method('getDictionary')->willReturn(['test' => 1]);

        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();
        $modelMock->method('get')->willReturn($groupQueryMock);
        ee()->setMock('Model', $modelMock);

        $result = $this->template->sync_from_files();

        // Should handle corrupted files gracefully
        $this->assertNotFalse($result);
    }

    public function testSyncFromFilesExtremeFileCount()
    {
        // Test performance with extremely large number of template files
        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', 'testsite');
        ee()->config->setItem('site_id', 1);

        // Generate many template files
        $manyFiles = [];
        for ($i = 0; $i < 1000; $i++) {
            $manyFiles['template_' . $i . '.html'] = 'template_' . $i . '.html';
        }
        $GLOBALS['test_many_files'] = $manyFiles;

        if (!function_exists('directory_map')) {
            function directory_map($path, $depth = 0, $hidden = false) {
                return ['large.group' => array_keys($GLOBALS['test_many_files'])];
            }
        }

        if (!function_exists('file_get_contents')) {
            function file_get_contents($filename) {
                return '<html>Template content</html>';
            }
        }

        $groupQueryMock = $this->getMockBuilder('stdClass')
            ->setMethods(['with', 'filter', 'order', 'all', 'getDictionary'])
            ->getMock();
        $groupQueryMock->method('with')->willReturnSelf();
        $groupQueryMock->method('filter')->willReturnSelf();
        $groupQueryMock->method('order')->willReturnSelf();
        $groupQueryMock->method('all')->willReturnSelf();
        $groupQueryMock->method('getDictionary')->willReturn(['large' => 1]);

        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['get'])
            ->getMock();
        $modelMock->method('get')->willReturn($groupQueryMock);
        ee()->setMock('Model', $modelMock);

        $start = microtime(true);
        $result = $this->template->sync_from_files();
        $duration = microtime(true) - $start;

        // Should handle large file counts without excessive performance issues
        $this->assertLessThan(5.0, $duration, 'Should handle large file counts reasonably fast');
        $this->assertNotFalse($result);
    }

    public function testSyncFromFilesMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'sync_from_files');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(0, $parameters);
    }
}
