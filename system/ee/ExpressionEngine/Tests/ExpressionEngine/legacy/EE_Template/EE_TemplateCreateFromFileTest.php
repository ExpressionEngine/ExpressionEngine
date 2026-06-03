<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateCreateFromFileTest extends EE_TemplateTestBase
{
    private $createdTemplateRoots = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!defined('PATH_TMPL')) {
            define('PATH_TMPL', sys_get_temp_dir() . '/ee-template-tests/');
        }
    }

    public function testCreateFromFileMethodExists()
    {
        $this->assertTrue(method_exists($this->template, '_create_from_file'));
    }

    public function testCreateFromFileSaveTmplFilesDisabled()
    {
        // Mock config to return 'n' for save_tmpl_files
        $configMock = ee()->config;
        $configMock->items['save_tmpl_files'] = 'n';
        ee()->setMock('config', $configMock);

        $result = $this->template->_create_from_file('test_group', 'test_template');

        $this->assertFalse($result);
    }

    public function testCreateFromFileNameLengthValidation()
    {
        // Mock config to return 'y' for save_tmpl_files
        $configMock = ee()->config;
        $configMock->items['save_tmpl_files'] = 'y';
        ee()->setMock('config', $configMock);

        // Test template name too long
        $longName = str_repeat('a', 51);
        $result = $this->template->_create_from_file('test_group', $longName);
        $this->assertFalse($result);

        // Test group name too long
        $result = $this->template->_create_from_file($longName, 'test_template');
        $this->assertFalse($result);
    }

    public function testCreateFromFileHandlesDbCheckWhenTemplateExists()
    {
        // Mock config
        $configMock = ee()->config;
        $configMock->items['save_tmpl_files'] = 'y';
        $configMock->items['site_id'] = 1;
        ee()->setMock('config', $configMock);

        // Create a proper db mock that supports the chaining methods used
        $dbMock = $this->getMockBuilder(\FakeDb::class)
            ->setMethods(['from', 'join', 'where', 'count_all_results'])
            ->getMock();

        // Set up the mock to return a result with num_rows = 1 (template exists)
        $dbResultMock = $this->getMockBuilder('stdClass')
            ->setMethods(['num_rows'])
            ->getMock();
        $dbResultMock->method('num_rows')->willReturn(1);

        $dbMock->method('from')->willReturnSelf();
        $dbMock->method('join')->willReturnSelf();
        $dbMock->method('where')->willReturnSelf();
        $dbMock->method('count_all_results')->willReturn(1); // Template exists

        ee()->setMock('db', $dbMock);

        $result = $this->template->_create_from_file('existing_group', 'existing_template', true);

        $this->assertFalse($result);
    }

    public function testCreateFromFileHandlesConfigurationChecks()
    {
        // Test that the method properly checks configuration before proceeding
        // This test focuses on the early return conditions

        // Test with save_tmpl_files disabled
        $configMock = ee()->config;
        $configMock->items['save_tmpl_files'] = 'n';
        ee()->setMock('config', $configMock);

        $result = $this->template->_create_from_file('test_group', 'test_template');
        $this->assertFalse($result);

        // Reset config for next test
        $configMock->items['save_tmpl_files'] = 'y';
        ee()->setMock('config', $configMock);

        // Test with long names - should return false immediately
        $longName = str_repeat('a', 51);
        $result = $this->template->_create_from_file($longName, 'test_template');
        $this->assertFalse($result);

        $result = $this->template->_create_from_file('test_group', $longName);
        $this->assertFalse($result);
    }

    public function testCreateFromFileHandlesBasicValidation()
    {
        // Test basic validation that doesn't require complex file system mocking

        // Test with valid config but invalid length (should fail early)
        $configMock = ee()->config;
        $configMock->items['save_tmpl_files'] = 'y';
        ee()->setMock('config', $configMock);

        // Test name length validation
        $longName = str_repeat('a', 51);
        $result = $this->template->_create_from_file($longName, 'test');
        $this->assertFalse($result);

        $result = $this->template->_create_from_file('test', $longName);
        $this->assertFalse($result);

        // Test that empty template defaults to 'index'
        // This is hard to test directly since the method has many dependencies
        // We'll just verify the method exists and is callable
        $this->assertTrue(is_callable([$this->template, '_create_from_file']));
    }

    public function testCreateFromFileMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, '_create_from_file');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('template_group', $parameters[0]->getName());
        $this->assertEquals('template', $parameters[1]->getName());
        $this->assertEquals('db_check', $parameters[2]->getName());
        $this->assertTrue($parameters[2]->isDefaultValueAvailable()); // Has default value of false
    }

    public function testCreateFromFileHandlesBasicParameters()
    {
        // Test that method accepts the expected parameters and handles basic validation

        // Test that method exists and can be called
        $this->assertTrue(is_callable([$this->template, '_create_from_file']));

        // Test parameter validation - long names should fail early
        $configMock = ee()->config;
        $configMock->items['save_tmpl_files'] = 'y';
        ee()->setMock('config', $configMock);

        $longName = str_repeat('a', 51);
        $result = $this->template->_create_from_file($longName, 'test');
        $this->assertFalse($result); // Should fail due to length

        $result = $this->template->_create_from_file('test', $longName);
        $this->assertFalse($result); // Should fail due to length
    }

    public function testCreateFromFileReturnsFalseWhenTemplateGroupDirectoryMissing()
    {
        $this->setBasicCreateFromFileConfig('site_missing_group', 1);
        $this->setLoadAndApiMocks(['.html' => ['type' => 'webpage', 'engine' => null]]);

        $result = $this->template->_create_from_file('missing_group', 'index');

        $this->assertFalse($result);
    }

    public function testCreateFromFileReturnsFalseWhenNoTemplateFileMatchesExtension()
    {
        $siteShortName = 'site_missing_file';
        $this->setBasicCreateFromFileConfig($siteShortName, 1);
        $this->setLoadAndApiMocks(['.html' => ['type' => 'webpage', 'engine' => null]]);

        $groupDir = $this->createTemplateGroupDirectory($siteShortName, 'blog');
        file_put_contents($groupDir . '/different_template.html', 'different');

        $result = $this->template->_create_from_file('blog', 'index');

        $this->assertFalse($result);
    }

    public function testCreateFromFileCreatesTemplateInExistingGroup()
    {
        $siteShortName = 'site_existing_group';
        $this->setBasicCreateFromFileConfig($siteShortName, 1);
        $this->setLoadAndApiMocks(['.html' => ['type' => 'webpage', 'engine' => null]]);

        $groupDir = $this->createTemplateGroupDirectory($siteShortName, 'docs');
        file_put_contents($groupDir . '/index.html', '<h1>Index Template</h1>');

        $dbMock = new \FakeDb();
        $dbMock->setRows([
            ['group_id' => 5, 'group_name' => 'docs', 'site_id' => 1],
        ]);
        ee()->setMock('db', $dbMock);

        ee()->template_model = new class {
            public function create_group($data)
            {
                return 99;
            }
        };
        ee()->localize = new class {
            public $now = 1700000000;
        };
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['clear_caching'])
            ->getMock();
        $functionsMock->expects($this->once())
            ->method('clear_caching')
            ->with('db');
        ee()->setMock('functions', $functionsMock);

        $templateModel = new class {
            public $Roles;
            public function save()
            {
                return true;
            }
            public function getId()
            {
                return 123;
            }
        };
        $roleQuery = new class {
            public function all()
            {
                return ['admin_role'];
            }
        };
        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['make', 'get'])
            ->getMock();
        $modelMock->method('make')->with('Template', $this->isType('array'))->willReturn($templateModel);
        $modelMock->method('get')->with('Role')->willReturn($roleQuery);
        ee()->setMock('Model', $modelMock);

        $result = $this->template->_create_from_file('docs', '', false);

        $this->assertSame(123, $result);
    }

    public function testCreateFromFileReturnsFalseWhenGroupNameIsReserved()
    {
        $siteShortName = 'site_reserved_group';
        $this->setBasicCreateFromFileConfig($siteShortName, 1);
        $this->setLoadAndApiMocks(['.html' => ['type' => 'webpage', 'engine' => null]], true);

        $groupDir = $this->createTemplateGroupDirectory($siteShortName, 'system');
        file_put_contents($groupDir . '/unsafe.html', 'unsafe');

        $result = $this->template->_create_from_file('system', 'unsafe');

        $this->assertFalse($result);
    }

    public function testCreateFromFileReturnsFalseWhenTemplateNameIsNotUrlSafe()
    {
        $siteShortName = 'site_unsafe_template';
        $this->setBasicCreateFromFileConfig($siteShortName, 1);

        $loadMock = $this->getMockBuilder('stdClass')
            ->setMethods(['library', 'model'])
            ->getMock();
        $loadMock->method('library')->willReturn(null);
        $loadMock->method('model')->willReturn(null);
        ee()->setMock('load', $loadMock);

        $templateStructureMock = new class {
            public $reserved_names = ['system', 'templates'];
            public function all_file_extensions()
            {
                return ['.html' => ['type' => 'webpage', 'engine' => null]];
            }
        };
        ee()->setMock('api_template_structure', $templateStructureMock);
        ee()->api_template_structure = $templateStructureMock;

        $groupDir = $this->createTemplateGroupDirectory($siteShortName, 'docs');
        file_put_contents($groupDir . '/bad$name.html', '<h1>Unsafe Template</h1>');

        $result = $this->template->_create_from_file('docs', 'bad$name');

        $this->assertFalse($result);
    }

    public function testCreateFromFileReturnsFalseWhenGroupNameIsNotUrlSafe()
    {
        $siteShortName = 'site_unsafe_group';
        $this->setBasicCreateFromFileConfig($siteShortName, 1);

        $loadMock = $this->getMockBuilder('stdClass')
            ->setMethods(['library', 'model'])
            ->getMock();
        $loadMock->method('library')->willReturn(null);
        $loadMock->method('model')->willReturn(null);
        ee()->setMock('load', $loadMock);

        $templateStructureMock = new class {
            public $reserved_names = ['system', 'templates'];
            public function all_file_extensions()
            {
                return ['.html' => ['type' => 'webpage', 'engine' => null]];
            }
        };
        ee()->setMock('api_template_structure', $templateStructureMock);
        ee()->api_template_structure = $templateStructureMock;

        $groupDir = $this->createTemplateGroupDirectory($siteShortName, 'unsafe$group');
        file_put_contents($groupDir . '/index.html', '<h1>Unsafe Group</h1>');

        $dbMock = new \FakeDb();
        $dbMock->setRows([]);
        ee()->setMock('db', $dbMock);

        $result = $this->template->_create_from_file('unsafe$group', 'index');

        $this->assertFalse($result);
    }

    public function testCreateFromFileCreatesMissingGroupWhenValid()
    {
        $siteShortName = 'site_create_group';
        $this->setBasicCreateFromFileConfig($siteShortName, 1);
        $this->setLoadAndApiMocks(['.html' => ['type' => 'webpage', 'engine' => null]], true);

        $groupDir = $this->createTemplateGroupDirectory($siteShortName, 'new_group');
        file_put_contents($groupDir . '/index.html', '<h1>New Group Template</h1>');

        $dbMock = $this->getMockBuilder(\FakeDb::class)
            ->setMethods(['count_all'])
            ->getMock();
        $dbMock->method('count_all')->with('template_groups')->willReturn(4);
        $dbMock->setRows([]);
        ee()->setMock('db', $dbMock);

        $templateModelService = $this->getMockBuilder('stdClass')
            ->setMethods(['create_group'])
            ->getMock();
        $templateModelService->expects($this->once())
            ->method('create_group')
            ->with($this->callback(function($data) {
                return $data['group_name'] === 'new_group' && $data['group_order'] === 5;
            }))
            ->willReturn(44);
        ee()->template_model = $templateModelService;
        ee()->setMock('template_model', $templateModelService);

        $loadMock = $this->getMockBuilder('stdClass')
            ->setMethods(['library', 'model'])
            ->getMock();
        $loadMock->method('library')->willReturn(null);
        $loadMock->method('model')->willReturnCallback(function() use ($templateModelService) {
            ee()->template_model = $templateModelService;
            return null;
        });
        ee()->setMock('load', $loadMock);

        ee()->localize = new class {
            public $now = 1700000000;
        };

        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['clear_caching'])
            ->getMock();
        $functionsMock->expects($this->once())->method('clear_caching')->with('db');
        ee()->setMock('functions', $functionsMock);

        $templateModel = new class {
            public $Roles;
            public function save()
            {
                return true;
            }
            public function getId()
            {
                return 777;
            }
        };
        $roleQuery = new class {
            public function all()
            {
                return ['admin_role'];
            }
        };
        $modelMock = $this->getMockBuilder('stdClass')
            ->setMethods(['make', 'get'])
            ->getMock();
        $modelMock->method('make')->with('Template', $this->isType('array'))->willReturn($templateModel);
        $modelMock->method('get')->with('Role')->willReturn($roleQuery);
        ee()->setMock('Model', $modelMock);

        $result = $this->template->_create_from_file('new_group', '', false);

        $this->assertSame(777, $result);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdTemplateRoots as $root) {
            $this->removeDirectory($root);
        }
        $this->createdTemplateRoots = [];

        parent::tearDown();
    }

    private function setBasicCreateFromFileConfig($siteShortName, $siteId)
    {
        $configMock = ee()->config;
        $configMock->items['save_tmpl_files'] = 'y';
        $configMock->items['site_short_name'] = $siteShortName;
        $configMock->items['site_id'] = $siteId;
        ee()->setMock('config', $configMock);
    }

    private function setLoadAndApiMocks(array $extensions, $isUrlSafe = true)
    {
        $loadMock = $this->getMockBuilder('stdClass')
            ->setMethods(['library', 'model'])
            ->getMock();
        $loadMock->method('library')->willReturn(null);
        $loadMock->method('model')->willReturn(null);
        ee()->setMock('load', $loadMock);

        $legacyApiMock = $this->getMockBuilder('stdClass')
            ->setMethods(['instantiate', 'is_url_safe'])
            ->getMock();
        $legacyApiMock->method('instantiate')->willReturn(null);
        $legacyApiMock->method('is_url_safe')->willReturn($isUrlSafe);
        ee()->setMock('legacy_api', $legacyApiMock);
        ee()->legacy_api = $legacyApiMock;

        $templateStructureMock = new class($extensions) {
            public $reserved_names = ['system', 'templates'];
            private $extensions;
            public function __construct($extensions)
            {
                $this->extensions = $extensions;
            }
            public function all_file_extensions()
            {
                return $this->extensions;
            }
        };
        ee()->setMock('api_template_structure', $templateStructureMock);
        ee()->api_template_structure = $templateStructureMock;
    }

    private function createTemplateGroupDirectory($siteShortName, $groupName)
    {
        $root = rtrim(PATH_TMPL, '/');
        $siteRoot = $root . '/' . $siteShortName;
        $groupDir = $siteRoot . '/' . $groupName . '.group';

        if (!is_dir($groupDir)) {
            mkdir($groupDir, 0777, true);
        }

        $this->createdTemplateRoots[] = $siteRoot;

        return $groupDir;
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }
}
