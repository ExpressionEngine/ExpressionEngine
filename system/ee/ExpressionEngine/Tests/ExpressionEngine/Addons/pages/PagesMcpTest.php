<?php

require_once __DIR__ . '/PagesTestBase.php';
require_once __DIR__ . '/../../../../Addons/pages/mcp.pages.php';

if (!class_exists('PagesMcpUrlResultStub')) {
    class PagesMcpUrlResultStub
    {
        private $path;
        public function __construct(string $path)
        {
            $this->path = $path;
        }
        public function compile(): string
        {
            return 'cp://' . $this->path;
        }
        public function __toString(): string
        {
            return $this->compile();
        }
    }
}

class PagesMcpTest extends PagesTestBase
{
    public function testConstructorLoadsHomepageDisplayAndToolbar(): void
    {
        ee()->setMock('load', new class {
            public function model($name)
            {
            }
        });
        ee()->setMock('pages_model', new class {
            public function fetch_configuration()
            {
                return new eeDbResultMock([
                    ['configuration_name' => 'homepage_display', 'configuration_value' => 'nested'],
                ]);
            }
        });
        ee()->setMock('view', (object) []);
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return new PagesMcpUrlResultStub($path);
            }
        });

        $mcp = new Pages_mcp();

        $this->assertSame('nested', $mcp->homepage_display);
        $this->assertArrayHasKey('toolbar_items', ee()->view->header);
    }

    public function testConstructorFallsBackToNotNestedWhenNoSetting(): void
    {
        ee()->setMock('load', new class {
            public function model($name)
            {
            }
        });
        ee()->setMock('pages_model', new class {
            public function fetch_configuration()
            {
                return new eeDbResultMock([]);
            }
        });
        ee()->setMock('view', (object) []);
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return new PagesMcpUrlResultStub($path);
            }
        });

        $mcp = new Pages_mcp();
        $this->assertSame('not_nested', $mcp->homepage_display);
    }

    public function testGetPagesTreeBuildsNestedListFromUris(): void
    {
        $captured = (object) ['treeList' => null];
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [
                        1 => [
                            'uris' => [
                                1 => '/',
                                2 => '/about',
                                3 => '/about/team',
                            ],
                        ],
                    ];
                }
                return null;
            }
        });
        ee()->setMock('Model', new class {
            public function get($entity, $ids = [])
            {
                return new class {
                    public function fields(...$fields)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return new class {
                            public function getDictionary($key, $value)
                            {
                                return [
                                    1 => 'Home',
                                    2 => 'About',
                                    3 => 'Team',
                                ];
                            }
                        };
                    }
                };
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });
        ee()->setMock('tree', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function from_list($list)
            {
                $this->captured->treeList = $list;
                return ['tree' => $list];
            }
        });

        $mcp = (new ReflectionClass(Pages_mcp::class))->newInstanceWithoutConstructor();
        $tree = $this->invokePrivate($mcp, 'getPagesTree');

        $this->assertIsArray($tree);
        $this->assertCount(2, $captured->treeList);
        $this->assertSame(2, $captured->treeList[0]['id']);
        $this->assertSame(2, $captured->treeList[1]['parent_id']);
    }

    public function testIndexRendersFlatViewAndLogsMissingEntries(): void
    {
        $captured = (object) ['tableData' => [], 'logged' => [], 'js' => [], 'cpJs' => []];
        $mcp = (new ReflectionClass(Pages_mcp::class))->newInstanceWithoutConstructor();
        $mcp->homepage_display = 'not_nested';

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [
                        1 => [
                            'uris' => [
                                100 => '/one',
                                200 => '/missing',
                            ],
                        ],
                    ];
                }
                return null;
            }
        });
        ee()->setMock('Model', new class {
            public function get($entity, $ids = [])
            {
                return new class {
                    public function fields(...$fields)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return new class {
                            public function getDictionary($key, $value)
                            {
                                return [100 => 'Entry One'];
                            }
                        };
                    }
                };
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return new PagesMcpUrlResultStub($path);
            }
        });
        ee()->setMock('CP/Table', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function setColumns($columns)
            {
                return $this;
            }
            public function setNoResultsText($text)
            {
                return $this;
            }
            public function setData($data)
            {
                $this->captured->tableData = $data;
                return $this;
            }
            public function viewData($baseUrl)
            {
                return [
                    'base_url' => $baseUrl,
                    'limit' => 20,
                    'page' => 1,
                    'total_rows' => count($this->captured->tableData),
                ];
            }
        });
        ee()->setMock('CP/Pagination', new class {
            public function perPage($count)
            {
                return $this;
            }
            public function currentPage($page)
            {
                return $this;
            }
            public function render($baseUrl)
            {
                return 'pagination';
            }
        });
        ee()->setMock('View', new class {
            public function make($view)
            {
                return new class {
                    public function render($vars = [])
                    {
                        return 'INDEX_BODY';
                    }
                };
            }
        });
        ee()->setMock('javascript', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function set_global($key, $value)
            {
                $this->captured->js[] = [$key, $value];
            }
        });
        ee()->setMock('cp', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function add_js_script($config)
            {
                $this->captured->cpJs[] = $config;
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });
        ee()->setMock('logger', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function developer($message, $a = true, $b = 0)
            {
                $this->captured->logged[] = $message;
            }
        });

        $result = $mcp->index();

        $this->assertSame('pages_manager', $result['heading']);
        $this->assertSame('INDEX_BODY', $result['body']);
        $this->assertCount(1, $captured->tableData);
        $this->assertNotEmpty($captured->logged);
    }

    public function testIndexHandlesSitePagesDisabledWithoutModelLookup(): void
    {
        $captured = (object) ['modelGetCalls' => 0, 'tableData' => null];
        $mcp = (new ReflectionClass(Pages_mcp::class))->newInstanceWithoutConstructor();
        $mcp->homepage_display = 'not_nested';

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return false;
                }
                return null;
            }
        });
        ee()->setMock('Model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get($entity, $ids = [])
            {
                $this->captured->modelGetCalls++;
                return null;
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return new PagesMcpUrlResultStub($path);
            }
        });
        ee()->setMock('CP/Table', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function setColumns($columns)
            {
                return $this;
            }
            public function setNoResultsText($text)
            {
                return $this;
            }
            public function setData($data)
            {
                $this->captured->tableData = $data;
                return $this;
            }
            public function viewData($baseUrl)
            {
                return [
                    'base_url' => $baseUrl,
                    'limit' => 20,
                    'page' => 1,
                    'total_rows' => 0,
                ];
            }
        });
        ee()->setMock('CP/Pagination', new class {
            public function perPage($count)
            {
                return $this;
            }
            public function currentPage($page)
            {
                return $this;
            }
            public function render($baseUrl)
            {
                return 'pagination';
            }
        });
        ee()->setMock('View', new class {
            public function make($view)
            {
                return new class {
                    public function render($vars = [])
                    {
                        return 'INDEX_EMPTY';
                    }
                };
            }
        });
        ee()->setMock('javascript', new class {
            public function set_global($key, $value)
            {
            }
        });
        ee()->setMock('cp', new class {
            public function add_js_script($config)
            {
            }
        });

        $result = $mcp->index();
        $this->assertSame('INDEX_EMPTY', $result['body']);
        $this->assertSame([], $captured->tableData);
        $this->assertSame(0, $captured->modelGetCalls);
    }

    public function testIndexHandlesEmptyUriListWithoutModelLookup(): void
    {
        $captured = (object) ['modelGetCalls' => 0, 'tableData' => null];
        $mcp = (new ReflectionClass(Pages_mcp::class))->newInstanceWithoutConstructor();
        $mcp->homepage_display = 'not_nested';

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [1 => ['uris' => []]];
                }
                return null;
            }
        });
        ee()->setMock('Model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get($entity, $ids = [])
            {
                $this->captured->modelGetCalls++;
                return null;
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return new PagesMcpUrlResultStub($path);
            }
        });
        ee()->setMock('CP/Table', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function setColumns($columns)
            {
                return $this;
            }
            public function setNoResultsText($text)
            {
                return $this;
            }
            public function setData($data)
            {
                $this->captured->tableData = $data;
                return $this;
            }
            public function viewData($baseUrl)
            {
                return [
                    'base_url' => $baseUrl,
                    'limit' => 20,
                    'page' => 1,
                    'total_rows' => 0,
                ];
            }
        });
        ee()->setMock('CP/Pagination', new class {
            public function perPage($count)
            {
                return $this;
            }
            public function currentPage($page)
            {
                return $this;
            }
            public function render($baseUrl)
            {
                return 'pagination';
            }
        });
        ee()->setMock('View', new class {
            public function make($view)
            {
                return new class {
                    public function render($vars = [])
                    {
                        return 'INDEX_EMPTY_URIS';
                    }
                };
            }
        });
        ee()->setMock('javascript', new class {
            public function set_global($key, $value)
            {
            }
        });
        ee()->setMock('cp', new class {
            public function add_js_script($config)
            {
            }
        });

        $result = $mcp->index();
        $this->assertSame('INDEX_EMPTY_URIS', $result['body']);
        $this->assertSame([], $captured->tableData);
        $this->assertSame(0, $captured->modelGetCalls);
    }

    public function testIndexInvokesDeleteWhenPostSelectionProvided(): void
    {
        $captured = (object) ['deleteCalls' => 0];
        $mcp = (new ReflectionClass(Pages_mcp::class))->newInstanceWithoutConstructor();
        $mcp->homepage_display = 'not_nested';
        $_POST = ['selection' => [11]];

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [1 => ['uris' => [11 => '/a'], 'templates' => [11 => 1]]];
                }
                return null;
            }
        });
        ee()->setMock('load', new class {
            public function model($name)
            {
            }
            public function library($name)
            {
            }
        });
        ee()->setMock('Model', new class {
            public function get($entity, $ids = [])
            {
                return new class {
                    public function fields(...$fields)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return new class {
                            public function getDictionary($key, $value)
                            {
                                return [11 => 'Entry'];
                            }
                        };
                    }
                };
            }
        });
        ee()->setMock('pages_model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function delete_site_pages($ids)
            {
                $this->captured->deleteCalls++;
                return false;
            }
        });
        ee()->setMock('CP/Alert', new class {
            public function makeInline($name)
            {
                return new class {
                    public function asSuccess()
                    {
                        return $this;
                    }
                    public function withTitle($title)
                    {
                        return $this;
                    }
                    public function addToBody($body)
                    {
                        return $this;
                    }
                    public function defer()
                    {
                        return $this;
                    }
                };
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return new PagesMcpUrlResultStub($path);
            }
        });
        ee()->setMock('functions', new class {
            public function redirect($url)
            {
            }
        });
        ee()->setMock('CP/Table', new class {
            public function setColumns($columns)
            {
                return $this;
            }
            public function setNoResultsText($text)
            {
                return $this;
            }
            public function setData($data)
            {
                return $this;
            }
            public function viewData($baseUrl)
            {
                return [
                    'base_url' => $baseUrl,
                    'limit' => 20,
                    'page' => 1,
                    'total_rows' => 0,
                ];
            }
        });
        ee()->setMock('CP/Pagination', new class {
            public function perPage($count)
            {
                return $this;
            }
            public function currentPage($page)
            {
                return $this;
            }
            public function render($baseUrl)
            {
                return 'pagination';
            }
        });
        ee()->setMock('View', new class {
            public function make($view)
            {
                return new class {
                    public function render($vars = [])
                    {
                        return 'INDEX_AFTER_DELETE';
                    }
                };
            }
        });
        ee()->setMock('javascript', new class {
            public function set_global($key, $value)
            {
            }
        });
        ee()->setMock('cp', new class {
            public function add_js_script($config)
            {
            }
        });

        $result = $mcp->index();
        $this->assertSame('INDEX_AFTER_DELETE', $result['body']);
        $this->assertSame(1, $captured->deleteCalls);
    }

    public function testIndexRendersNestedViewWhenConfigured(): void
    {
        $mcp = (new ReflectionClass(Pages_mcp::class))->newInstanceWithoutConstructor();
        $mcp->homepage_display = 'nested';

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [1 => ['uris' => [1 => '/', 2 => '/about']]];
                }
                return null;
            }
        });
        ee()->setMock('Model', new class {
            public function get($entity, $ids = [])
            {
                return new class {
                    public function fields(...$fields)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return new class {
                            public function getDictionary($key, $value)
                            {
                                return [1 => 'Home', 2 => 'About'];
                            }
                        };
                    }
                };
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });
        ee()->setMock('tree', new class {
            public function from_list($list)
            {
                return ['nested' => $list];
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return new PagesMcpUrlResultStub($path);
            }
        });
        ee()->setMock('View', new class {
            public function make($view)
            {
                return new class {
                    public function render($vars = [])
                    {
                        return 'NESTED_BODY';
                    }
                };
            }
        });
        ee()->setMock('javascript', new class {
            public function set_global($key, $value)
            {
            }
        });
        ee()->setMock('cp', new class {
            public function add_js_script($config)
            {
            }
        });

        $result = $mcp->index();
        $this->assertSame('NESTED_BODY', $result['body']);
    }

    public function testDeleteBuildsAlertAndRedirectsOnSuccess(): void
    {
        $captured = (object) ['alertBodies' => [], 'redirects' => []];
        $mcp = (new ReflectionClass(Pages_mcp::class))->newInstanceWithoutConstructor();

        $_POST = ['selection' => [11, 12]];

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [
                        1 => [
                            'uris' => [
                                11 => '/a',
                                12 => '/b',
                            ],
                        ],
                    ];
                }
                return null;
            }
        });
        ee()->setMock('load', new class {
            public function model($name)
            {
            }
        });
        ee()->setMock('pages_model', new class {
            public function delete_site_pages($ids)
            {
                return 2;
            }
        });
        ee()->setMock('CP/Alert', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function makeInline($name)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function asSuccess()
                    {
                        return $this;
                    }
                    public function withTitle($title)
                    {
                        return $this;
                    }
                    public function addToBody($body)
                    {
                        $this->captured->alertBodies[] = $body;
                        return $this;
                    }
                    public function defer()
                    {
                        return $this;
                    }
                };
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return new PagesMcpUrlResultStub($path);
            }
        });
        ee()->setMock('functions', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function redirect($url)
            {
                $this->captured->redirects[] = (string) $url;
            }
        });

        $this->invokePrivate($mcp, 'delete');

        $this->assertContains('/a', $captured->alertBodies[1]);
        $this->assertContains('/b', $captured->alertBodies[1]);
        $this->assertSame('cp://addons/settings/pages', $captured->redirects[0]);
    }

    public function testSettingsBuildsFormAndSavesPostValues(): void
    {
        $captured = (object) ['updated' => [], 'messages' => [], 'redirects' => [], 'viewVars' => null];
        $mcp = (new ReflectionClass(Pages_mcp::class))->newInstanceWithoutConstructor();

        $_POST = [
            'homepage_display' => 'nested',
            'default_channel' => '2',
            'template_channel_2' => '8',
            'template_channel_3' => '0',
            'ignored' => 'abc',
        ];

        ee()->setMock('config', new class {
            public function item($key)
            {
                return $key === 'site_id' ? 1 : null;
            }
        });
        ee()->setMock('Model', new class {
            public function get($entity)
            {
                return new class {
                    public function filter($field, $value)
                    {
                        return $this;
                    }
                    public function order($field)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return [
                            (object) ['channel_id' => 2, 'channel_title' => 'Pages'],
                            (object) ['channel_id' => 3, 'channel_title' => 'Blog'],
                        ];
                    }
                };
            }
        });
        ee()->setMock('template_model', new class {
            public function get_templates($siteId)
            {
                return new eeDbResultMock([
                    ['template_id' => 8, 'group_name' => 'site', 'template_name' => 'index'],
                    ['template_id' => 9, 'group_name' => 'site', 'template_name' => 'about'],
                ]);
            }
        });
        ee()->setMock('pages_model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function fetch_site_pages_config()
            {
                return new eeDbResultMock([
                    ['configuration_name' => 'homepage_display', 'configuration_value' => 'not_nested'],
                    ['configuration_name' => 'default_channel', 'configuration_value' => '2'],
                ]);
            }
            public function update_pages_configuration($data)
            {
                $this->captured->updated[] = $data;
            }
        });
        ee()->setMock('load', new class {
            public function model($name)
            {
            }
            public function add_package_path($path)
            {
            }
        });
        ee()->setMock('view', new class($captured) {
            public $header = [];
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function set_message($type, $title, $body, $defer)
            {
                $this->captured->messages[] = [$type, $title, $body, $defer];
            }
        });
        ee()->setMock('functions', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function redirect($url)
            {
                $this->captured->redirects[] = (string) $url;
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return new PagesMcpUrlResultStub($path);
            }
        });
        ee()->setMock('View', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function make($view)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function render($vars = [])
                    {
                        $this->captured->viewVars = $vars;
                        return 'SETTINGS_BODY';
                    }
                };
            }
        });

        $result = $mcp->settings();

        $this->assertSame('pages_settings', $result['heading']);
        $this->assertSame('SETTINGS_BODY', $result['body']);
        $this->assertNotEmpty($captured->updated);
        $this->assertSame('nested', $captured->updated[0]['homepage_display']);
        $this->assertSame('2', $captured->updated[0]['default_channel']);
        $this->assertSame('8', $captured->updated[0]['template_channel_2']);
        $this->assertArrayNotHasKey('template_channel_3', $captured->updated[0]);
        $this->assertNotEmpty($captured->messages);
        $this->assertNotEmpty($captured->redirects);
    }

    public function testSettingsRendersFormWithoutPostAndUsesConfigDefaults(): void
    {
        $captured = (object) ['updated' => [], 'redirects' => [], 'viewVars' => null];
        $mcp = (new ReflectionClass(Pages_mcp::class))->newInstanceWithoutConstructor();
        $_POST = [];

        ee()->setMock('config', new class {
            public function item($key)
            {
                return $key === 'site_id' ? 1 : null;
            }
        });
        ee()->setMock('Model', new class {
            public function get($entity)
            {
                return new class {
                    public function filter($field, $value)
                    {
                        return $this;
                    }
                    public function order($field)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return [
                            (object) ['channel_id' => 2, 'channel_title' => 'Pages'],
                            (object) ['channel_id' => 3, 'channel_title' => 'Blog'],
                        ];
                    }
                };
            }
        });
        ee()->setMock('template_model', new class {
            public function get_templates($siteId)
            {
                return new eeDbResultMock([
                    ['template_id' => 8, 'group_name' => 'site', 'template_name' => 'index'],
                ]);
            }
        });
        ee()->setMock('pages_model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function fetch_site_pages_config()
            {
                return new eeDbResultMock([]);
            }
            public function update_pages_configuration($data)
            {
                $this->captured->updated[] = $data;
            }
        });
        ee()->setMock('load', new class {
            public function model($name)
            {
            }
            public function add_package_path($path)
            {
            }
        });
        ee()->setMock('view', new class {
            public $header = [];
            public function set_message($type, $title, $body, $defer)
            {
            }
        });
        ee()->setMock('functions', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function redirect($url)
            {
                $this->captured->redirects[] = (string) $url;
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return new PagesMcpUrlResultStub($path);
            }
        });
        ee()->setMock('View', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function make($view)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function render($vars = [])
                    {
                        $this->captured->viewVars = $vars;
                        return 'SETTINGS_RENDER_ONLY';
                    }
                };
            }
        });

        $result = $mcp->settings();

        $this->assertSame('pages_settings', $result['heading']);
        $this->assertSame('SETTINGS_RENDER_ONLY', $result['body']);
        $this->assertSame([], $captured->updated);
        $this->assertSame([], $captured->redirects);
        $sections = $captured->viewVars['sections'][0];
        $this->assertSame('not_nested', $sections[0]['fields']['homepage_display']['value']);
        $this->assertSame(0, $sections[1]['fields']['default_channel']['value']);
        $this->assertArrayHasKey('template_channel_2', $sections[2]['fields']['pages_templates']['choices']);
        $this->assertArrayHasKey('template_channel_3', $sections[2]['fields']['pages_templates']['choices']);
    }

    public function testSaveSettingsReturnsTrueWithoutUpdateWhenNoValidPostData(): void
    {
        $captured = (object) ['updated' => 0];
        $mcp = (new ReflectionClass(Pages_mcp::class))->newInstanceWithoutConstructor();
        $_POST = ['default_channel' => '0', 'homepage_display' => 'invalid', 'foo' => 'bar'];

        ee()->setMock('load', new class {
            public function model($name)
            {
            }
        });
        ee()->setMock('pages_model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function update_pages_configuration($data)
            {
                $this->captured->updated++;
            }
        });

        $result = $this->invokePrivate($mcp, 'saveSettings');
        $this->assertTrue($result);
        $this->assertSame(0, $captured->updated);
    }
}
