<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateProcessTagsDemoAddonClass
{
    public function run()
    {
        return 'DEMO-RESULT';
    }
}

class EE_TemplateProcessTagsModuleClass
{
    public $return_data = 'MODULE-RESULT';

    public function __construct()
    {
    }

    public function Channel()
    {
        return 'legacy-constructor';
    }
}

class EE_TemplateProcessTagsCtorOnlyClass
{
    public $return_data = 'CTOR-ONLY';

    public function __construct()
    {
    }
}

#[\AllowDynamicProperties]
class EE_TemplateProcessTagsInnerTemplateStub
{
    public $template = '';
    public $tag_data = [];
    public $var_single = [];
    public $var_cond = [];
    public $var_pair = [];
    public $plugins = [];
    public $modules = [];
    public $module_data = [];
    public $log = [];
    public $start_microtime = 0;
    public $loop_count = 0;

    public function parse_tags()
    {
        $this->template = 'inner-processed';
    }

    public function process_tags()
    {
        $this->template = 'inner-processed';
    }
}

class EE_TemplateProcessTagsTest extends EE_TemplateTestBase
{
    private function setupProcessTagsServiceMocks($debug = 0): void
    {
        $variablesParser = new class {
            public function extractVariables($str)
            {
                return [
                    'var_single' => ['title'],
                    'var_pair' => [],
                ];
            }
        };
        ee()->setMock('Variables/Parser', $variablesParser);

        $addonMock = new class {
            public function get($name)
            {
                return new class($name) {
                    private $name;

                    public function __construct($name)
                    {
                        $this->name = $name;
                    }

                    public function getFrontendClass()
                    {
                        if ($this->name === 'channel') {
                            return '\\ExpressionEngine\\Tests\\ExpressionEngine\\legacy\\EE_Template\\EE_TemplateProcessTagsModuleClass';
                        }
                        if ($this->name === 'ctoronly') {
                            return '\\ExpressionEngine\\Tests\\ExpressionEngine\\legacy\\EE_Template\\EE_TemplateProcessTagsCtorOnlyClass';
                        }

                        return '\\ExpressionEngine\\Tests\\ExpressionEngine\\legacy\\EE_Template\\EE_TemplateProcessTagsDemoAddonClass';
                    }
                };
            }
        };
        ee()->setMock('Addon', $addonMock);

        $loadMock = new class {
            public $added = [];
            public $removed = [];

            public function add_package_path($path, $view_cascade = false)
            {
                $this->added[] = $path;
            }

            public function remove_package_path($path)
            {
                $this->removed[] = $path;
            }
        };
        ee()->setMock('load', $loadMock);

        $coreMock = new class {
            public $native_plugins = [];
            public $native_modules = ['channel'];
        };
        ee()->setMock('core', $coreMock);

        $configMock = new class extends \FakeConfig {
            public function site_url()
            {
                return 'https://example.com/';
            }
        };
        $configMock->items = [
            'debug' => $debug,
            'site_id' => 1,
            'multiple_sites_enabled' => 'n',
        ];
        ee()->setMock('config', $configMock);
    }

    private function setupSetAndRemoveTmpltMocks(): void
    {
        ee()->setMock('', new class {
            public function remove($key)
            {
                ee()->setMock($key, null);
            }

            public function set($key, $value)
            {
                if ($key === 'TMPL') {
                    ee()->setMock('TMPL', new \ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template\EE_TemplateProcessTagsInnerTemplateStub());
                } else {
                    ee()->setMock($key, $value);
                }
            }
        });
        ee()->setMock('TMPL', new EE_TemplateProcessTagsInnerTemplateStub());
    }

    public function testProcessTagsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'process_tags'));
        $this->assertTrue(is_callable([$this->template, 'process_tags']));
    }

    public function testProcessTagsInvalidTagInDebugModeCallsFatalError()
    {
        $this->setupProcessTagsServiceMocks(1);

        $langMock = new class {
            public function line($key)
            {
                return $key;
            }
        };
        ee()->setMock('lang', $langMock);

        $outputMock = new class {
            public function fatal_error($message)
            {
                throw new \RuntimeException('fatal_error');
            }
        };
        ee()->setMock('output', $outputMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['fetch_cache_file'])
            ->getMock();

        $templateMock->method('fetch_cache_file')
            ->willReturnCallback(function () use ($templateMock) {
                $templateMock->tag_cache_status = 'EXPIRED';
                return false;
            });

        $templateMock->marker = 'MARK';
        $templateMock->template = 'M0MARK';
        $templateMock->modules = [];
        $templateMock->plugins = [];
        $templateMock->tag_data = [
            [
                'cfile' => 'tag-1',
                'params' => [],
                'class' => 'invalid',
                'method' => 'index',
                'tagparts' => ['invalid', 'invalid'],
                'tag' => '{exp:invalid}',
                'chunk' => 'M0MARK',
                'block' => '',
                'no_results' => '',
                'no_results_block' => '',
                'search_fields' => [],
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fatal_error');
        $templateMock->process_tags();
    }

    public function testProcessTagsProcessesPluginAndWritesExpiredTagCache()
    {
        $this->setupProcessTagsServiceMocks();

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['fetch_cache_file', 'write_cache_file'])
            ->getMock();

        $writes = [];
        $templateMock->method('fetch_cache_file')
            ->willReturnCallback(function ($cfile, $type, $params) use ($templateMock) {
                $templateMock->tag_cache_status = 'EXPIRED';
                return '';
            });
        $templateMock->method('write_cache_file')
            ->willReturnCallback(function ($cfile, $data) use (&$writes) {
                $writes[] = [$cfile, $data];
                return true;
            });

        $templateMock->marker = 'TAGMARK';
        $templateMock->template = 'Before M0TAGMARK After';
        $templateMock->modules = ['channel'];
        $templateMock->plugins = ['demo'];
        $templateMock->module_data = [];
        $templateMock->tag_data = [
            [
                'cfile' => 'tag-cache',
                'params' => [
                    'cache_prefix' => 'cp',
                    'form_id' => 'form-id',
                    'form_class' => 'form-class',
                ],
                'class' => 'demo',
                'method' => 'run',
                'tagparts' => ['demo', 'run'],
                'tag' => '{exp:demo:run}',
                'chunk' => 'Chunk M0TAGMARK',
                'block' => 'Block',
                'no_results' => '',
                'no_results_block' => '',
                'search_fields' => [],
            ],
        ];

        $templateMock->process_tags();

        $this->assertStringContainsString('DEMO-RESULT', $templateMock->template);
        $this->assertNotEmpty($writes);
        $this->assertEquals('tag-cache', $writes[0][0]);
        $this->assertEquals('cp', $templateMock->cache_prefix);
    }

    public function testProcessTagsUsesCurrentCachedTagContent()
    {
        $this->setupProcessTagsServiceMocks();

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['fetch_cache_file'])
            ->getMock();
        $templateMock->method('fetch_cache_file')
            ->willReturnCallback(function () use ($templateMock) {
                $templateMock->tag_cache_status = 'CURRENT';
                return 'CACHED-CONTENT';
            });

        $templateMock->marker = 'TAGMARK';
        $templateMock->template = 'Before M0TAGMARK After';
        $templateMock->modules = [];
        $templateMock->plugins = [];
        $templateMock->tag_data = [
            [
                'cfile' => 'cached',
                'params' => [],
                'class' => 'demo',
                'method' => 'run',
                'tagparts' => ['demo', 'run'],
                'tag' => '{exp:demo:run}',
                'chunk' => 'M0TAGMARK',
                'block' => '',
                'no_results' => '',
                'no_results_block' => '',
                'search_fields' => [],
            ],
        ];

        $templateMock->process_tags();
        $this->assertStringContainsString('CACHED-CONTENT', $templateMock->template);
    }

    public function testProcessTagsReturnsFalseForInvalidTagWhenDebugDisabled()
    {
        $this->setupProcessTagsServiceMocks(0);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['fetch_cache_file'])
            ->getMock();
        $templateMock->method('fetch_cache_file')
            ->willReturnCallback(function () use ($templateMock) {
                $templateMock->tag_cache_status = 'EXPIRED';
                return false;
            });

        $templateMock->marker = 'TAGMARK';
        $templateMock->template = 'M0TAGMARK';
        $templateMock->modules = [];
        $templateMock->plugins = [];
        $templateMock->tag_data = [
            [
                'cfile' => 'invalid-cache',
                'params' => [],
                'class' => 'unknown',
                'method' => 'run',
                'tagparts' => ['unknown'],
                'tag' => '{exp:unknown}',
                'chunk' => 'M0TAGMARK',
                'block' => '',
                'no_results' => '',
                'no_results_block' => '',
                'search_fields' => [],
            ],
        ];

        $this->assertFalse($templateMock->process_tags());
    }

    public function testProcessTagsHandlesPluginInParameterAndNestedPluginBlocks()
    {
        $this->setupProcessTagsServiceMocks();
        $this->setupSetAndRemoveTmpltMocks();

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['fetch_cache_file', 'write_cache_file'])
            ->getMock();
        $templateMock->method('fetch_cache_file')
            ->willReturnCallback(function () use ($templateMock) {
                $templateMock->tag_cache_status = 'EXPIRED';
                return false;
            });
        $templateMock->method('write_cache_file')->willReturn(true);

        $templateMock->marker = 'TAGMARK';
        $templateMock->template = 'M0TAGMARK M1TAGMARK';
        $templateMock->modules = ['channel'];
        $templateMock->plugins = ['demo'];
        $templateMock->module_data = [];
        $templateMock->tag_data = [
            [
                'cfile' => 'tag0',
                'params' => [
                    'parse' => 'inward',
                    'channel' => '{exp:demo:run}',
                ],
                'class' => 'demo',
                'method' => 'run',
                'tagparts' => ['demo', 'run'],
                'tag' => '{exp:demo:run channel="{exp:demo:run}" parse="inward"}',
                'chunk' => 'M0TAGMARK',
                'block' => 'block 1',
                'no_results' => '',
                'no_results_block' => '',
                'search_fields' => ['keywords' => '{exp:demo:run}'],
            ],
            [
                'cfile' => 'tag1',
                'params' => [
                    'parse' => 'outward',
                ],
                'class' => 'demo',
                'method' => 'run',
                'tagparts' => ['demo', 'run'],
                'tag' => '{exp:demo:run parse="outward"}',
                'chunk' => 'M1TAGMARK',
                'block' => 'nested {exp:demo:run}',
                'no_results' => '',
                'no_results_block' => '',
                'search_fields' => [],
            ],
        ];

        $templateMock->process_tags();

        $this->assertStringContainsString('DEMO-RESULT', $templateMock->template);
    }

    public function testProcessTagsReturnsEarlyWhenCeaseProcessingIsSet()
    {
        $this->setupProcessTagsServiceMocks();

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['fetch_cache_file', 'write_cache_file'])
            ->getMock();
        $templateMock->method('fetch_cache_file')
            ->willReturnCallback(function () use ($templateMock) {
                $templateMock->tag_cache_status = 'EXPIRED';
                return false;
            });
        $templateMock->method('write_cache_file')->willReturn(true);

        $templateMock->marker = 'TAGMARK';
        $templateMock->template = 'M0TAGMARK';
        $templateMock->cease_processing = true;
        $templateMock->modules = ['channel'];
        $templateMock->plugins = ['demo'];
        $templateMock->module_data = [];
        $templateMock->tag_data = [
            [
                'cfile' => 'tag-stop',
                'params' => [],
                'class' => 'demo',
                'method' => 'run',
                'tagparts' => ['demo', 'run'],
                'tag' => '{exp:demo:run}',
                'chunk' => 'M0TAGMARK',
                'block' => 'block',
                'no_results' => '',
                'no_results_block' => '',
                'search_fields' => [],
            ],
        ];

        $templateMock->process_tags();
        $this->assertStringContainsString('M0TAGMARK', $templateMock->template);
    }

    public function testProcessTagsHandlesCtorFallbackWhenMethodIsFalse()
    {
        $this->setupProcessTagsServiceMocks();

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['fetch_cache_file', 'write_cache_file'])
            ->getMock();
        $templateMock->method('fetch_cache_file')
            ->willReturnCallback(function () use ($templateMock) {
                $templateMock->tag_cache_status = 'EXPIRED';
                return false;
            });
        $templateMock->method('write_cache_file')->willReturn(true);

        $templateMock->marker = 'TAGMARK';
        $templateMock->template = 'M0TAGMARK';
        $templateMock->modules = [];
        $templateMock->plugins = ['ctoronly'];
        $templateMock->module_data = [];
        $templateMock->tag_data = [
            [
                'cfile' => 'ctor-cache',
                'params' => [],
                'class' => 'ctoronly',
                'method' => false,
                'tagparts' => ['ctoronly'],
                'tag' => '{exp:ctoronly}',
                'chunk' => 'M0TAGMARK',
                'block' => 'block',
                'no_results' => '',
                'no_results_block' => '',
                'search_fields' => [],
            ],
        ];

        $templateMock->process_tags();
        $this->assertStringContainsString('CTOR-ONLY', $templateMock->template);
    }

    public function testProcessTagsReturnsEarlyWhenModuleIsNotInstalledAndDebugOff()
    {
        $this->setupProcessTagsServiceMocks(0);

        $dbMock = new class {
            public function select($fields = '*') { return $this; }
            public function get($table = null) { return new \eeDbResultMock([]); }
        };
        ee()->setMock('db', $dbMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['fetch_cache_file'])
            ->getMock();
        $templateMock->method('fetch_cache_file')
            ->willReturnCallback(function () use ($templateMock) {
                $templateMock->tag_cache_status = 'EXPIRED';
                return false;
            });

        $templateMock->marker = 'TAGMARK';
        $templateMock->template = 'M0TAGMARK';
        $templateMock->modules = ['channel'];
        $templateMock->plugins = [];
        $templateMock->module_data = [];
        $templateMock->tag_data = [
            [
                'cfile' => 'module-missing',
                'params' => [],
                'class' => 'channel',
                'method' => 'entries',
                'tagparts' => ['channel', 'entries'],
                'tag' => '{exp:channel:entries}',
                'chunk' => 'M0TAGMARK',
                'block' => 'block',
                'no_results' => '',
                'no_results_block' => '',
                'search_fields' => [],
            ],
        ];

        $this->assertNull($templateMock->process_tags());
    }

    public function testProcessTagsReportsMissingMethodInDebugMode()
    {
        $this->setupProcessTagsServiceMocks(1);

        $dbMock = new class {
            public function select($fields = '*') { return $this; }
            public function get($table = null) { return new \eeDbResultMock([]); }
        };
        ee()->setMock('db', $dbMock);

        $langMock = new class {
            public function line($key) { return $key; }
        };
        ee()->setMock('lang', $langMock);
        $outputMock = new class {
            public function fatal_error($message) { throw new \RuntimeException('module_method_missing'); }
        };
        ee()->setMock('output', $outputMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['fetch_cache_file'])
            ->getMock();
        $templateMock->method('fetch_cache_file')
            ->willReturnCallback(function () use ($templateMock) {
                $templateMock->tag_cache_status = 'EXPIRED';
                return false;
            });

        $templateMock->marker = 'TAGMARK';
        $templateMock->template = 'M0TAGMARK';
        $templateMock->modules = ['channel'];
        $templateMock->plugins = [];
        $templateMock->module_data = [];
        $templateMock->tag_data = [
            [
                'cfile' => 'module-missing-debug',
                'params' => [],
                'class' => 'channel',
                'method' => 'entries',
                'tagparts' => ['channel', 'channel'],
                'tag' => '{exp:channel:entries}',
                'chunk' => 'M0TAGMARK',
                'block' => 'block',
                'no_results' => '',
                'no_results_block' => '',
                'search_fields' => [],
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('module_method_missing');
        $templateMock->process_tags();
    }

    public function testProcessTagsLoadsModuleDataAndUsesConstructorReturnData()
    {
        $this->setupProcessTagsServiceMocks();

        $dbMock = new class {
            public function select($fields = '*')
            {
                return $this;
            }

            public function get($table = null)
            {
                if ($table === 'modules') {
                    return new \eeDbResultMock([
                        ['module_version' => '1.0.0', 'module_name' => 'Channel'],
                    ]);
                }

                return new \eeDbResultMock([]);
            }

            public function query($sql)
            {
                return new \eeDbResultMock([]);
            }
        };
        ee()->setMock('db', $dbMock);

        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['fetch_cache_file', 'write_cache_file'])
            ->getMock();

        $templateMock->method('fetch_cache_file')
            ->willReturnCallback(function () use ($templateMock) {
                $templateMock->tag_cache_status = 'EXPIRED';
                return false;
            });
        $templateMock->method('write_cache_file')->willReturn(true);

        $templateMock->marker = 'TAGMARK';
        $templateMock->template = 'M0TAGMARK';
        $templateMock->modules = ['channel'];
        $templateMock->plugins = ['demo'];
        $templateMock->module_data = [];
        $templateMock->tag_data = [
            [
                'cfile' => 'module-cache',
                'params' => [],
                'class' => 'channel',
                'method' => false,
                'tagparts' => ['channel'],
                'tag' => '{exp:channel}',
                'chunk' => 'M0TAGMARK',
                'block' => 'Channel block',
                'no_results' => '',
                'no_results_block' => '',
                'search_fields' => [],
            ],
        ];

        $templateMock->process_tags();

        $this->assertArrayHasKey('Channel', $templateMock->module_data);
        $this->assertStringContainsString('MODULE-RESULT', $templateMock->template);
    }
}
