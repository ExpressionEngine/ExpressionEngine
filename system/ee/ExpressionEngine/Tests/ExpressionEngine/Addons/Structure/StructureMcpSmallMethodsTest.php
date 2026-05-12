<?php

require_once __DIR__ . '/../../../eeObjectMock.php';

if (!defined('APP_VER')) {
    define('APP_VER', '7.5.14');
}
if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}
if (!defined('PATH_ADDONS')) {
    $addonsPath = defined('SYSPATH')
        ? (SYSPATH . 'ee/ExpressionEngine/Addons/')
        : (__DIR__ . '/../../../../Addons/');
    define('PATH_ADDONS', $addonsPath);
}
if (!defined('PATH_PRO_ADDONS')) {
    define('PATH_PRO_ADDONS', PATH_ADDONS);
}
if (!defined('PATH_MOD')) {
    define('PATH_MOD', PATH_ADDONS);
}
if (!defined('XID_SECURE_HASH')) {
    define('XID_SECURE_HASH', 'xid-test');
}
if (!defined('URL_THEMES')) {
    define('URL_THEMES', 'https://cdn.example.com/themes/');
}
if (!function_exists('redirect')) {
    function redirect($uri = '', $method = 'auto', $http_response_code = 302)
    {
        throw new RuntimeException((string) $uri);
    }
}
if (!class_exists('StructureMcpNavCtorStub')) {
    class StructureMcpNavCtorStub
    {
    }
}

require_once __DIR__ . '/../../../../Addons/structure/mcp.structure.php';

use PHPUnit\Framework\TestCase;

class StructureMcpSmallMethodsTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    public function testGetSitePathAndSetCpTitle()
    {
        ee()->setMock('functions', new class {
            public function fetch_site_index()
            {
                return 'https://example.com/sub/site/index.php';
            }
        });
        ee()->setMock('view', (object) ['cp_page_title' => '']);
        ee()->setMock('lang', new class {
            public function line($key)
            {
                return strtoupper($key);
            }
        });

        $mcp = $this->makeMcp();
        $this->assertSame('/sub/site', $mcp->get_site_path());

        $rm = new ReflectionMethod($mcp, 'set_cp_title');
        \TestReflectionHelper::makeAccessible($rm);
        $rm->invoke($mcp, 'pages');
        $this->assertSame('PAGES', ee()->view->cp_page_title);
    }

    public function testConstructorRunsWithExpectedDependencies()
    {
        if (!class_exists('ExpressionEngine\\Structure\\Conduit\\McpNav')) {
            class_alias('StructureMcpNavCtorStub', 'ExpressionEngine\\Structure\\Conduit\\McpNav');
        }

        ee()->setMock('uri', new class {
            public $page_query_string = '';
            public $query_string = '';
            public function segment_array()
            {
                return ['addons', 'settings', 'structure'];
            }
        });
        ee()->setMock('pagination', new class {
            public function create()
            {
                return new stdClass();
            }
        });
        ee()->setMock('functions', new class {
            public function fetch_assigned_channels()
            {
                return [];
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'reserved_category_word') {
                    return 'category';
                }
                if ($key === 'use_category_name') {
                    return 'n';
                }
                if ($key === 'theme_folder_url') {
                    return 'https://themes.example/';
                }
                return null;
            }
            public function slash_item($key)
            {
                return 'https://themes.example/';
            }
        });
        ee()->setMock('load', new class {
            public $libraries = [];
            public function add_package_path($path)
            {
            }
            public function library($name)
            {
                $this->libraries[] = $name;
            }
            public function helper($name)
            {
            }
        });
        ee()->setMock('cp', new class {
            public $head = [];
            public function add_to_head($html)
            {
                $this->head[] = $html;
            }
            public function set_breadcrumb($url, $title)
            {
            }
        });
        ee()->setMock('view', (object) ['cp_page_title' => '']);
        ee()->setMock('CP/Sidebar', new class {
            public function make()
            {
                return new class {
                    public function addHeader($title)
                    {
                        return new class {
                            public function withUrl($url)
                            {
                                return $this;
                            }
                            public function withButton($label, $url)
                            {
                                return $this;
                            }
                            public function isActive()
                            {
                                return $this;
                            }
                        };
                    }
                    public function addLink($label, $url)
                    {
                        return $this;
                    }
                };
            }
        });
        ee()->setMock('addons_model', new class {
            public function module_installed($name)
            {
                return true;
            }
        });
        ee()->setMock('session', new class {
            public $userdata = ['group_id' => 1];
        });
        ee()->setMock('db', new class {
            public function query($sql)
            {
                return new class {
                    public function num_rows()
                    {
                        return 0;
                    }
                    public function result_array()
                    {
                        return [];
                    }
                    public function result()
                    {
                        return [];
                    }
                    public function row($column = null)
                    {
                        return null;
                    }
                };
            }
        });
        ee()->setMock('CP/URL', 'cp://addons/settings/structure');

        $mcp = (new ReflectionClass('Structure_mcp'))->newInstanceWithoutConstructor();
        $mcp->logging = true;
        $mcp->__construct();

        $this->assertInstanceOf(eeSingletonMock::class, $mcp->base_url);
        $this->assertSame(1, $mcp->site_id);
        $this->assertNotEmpty(ee()->cp->head);
        $this->assertContains('logger', ee()->load->libraries);
    }

    public function testEntriesMissingFromStructureBuildsRows()
    {
        ee()->setMock('general_helper', new class {
            public function cpURL($section, $method, $params = [])
            {
                return 'cp://' . $section . '/' . $method . '/' . ($params['entry_id'] ?? '0');
            }
        });
        ee()->setMock('db', new class {
            public function query($sql)
            {
                return new class {
                    public function result()
                    {
                        return [
                            (object) ['entry_id' => 10, 'title' => 'Missing One'],
                            (object) ['entry_id' => 11, 'title' => 'Missing Two'],
                        ];
                    }
                };
            }
        });

        $mcp = $this->makeMcp();
        $mcp->sql = new class {
            public function get_structure_channels($type = '')
            {
                return [3 => ['channel_title' => 'Pages']];
            }
        };

        $missing = $mcp->entries_missing_from_structure();
        $this->assertCount(2, $missing);
        $this->assertSame(10, $missing[0]['entry_id']);
        $this->assertStringContainsString('cp://publish/edit/10', $missing[0]['ee_url']);
    }

    public function testLinkBuildsStructureUrlAndCallsRedirect()
    {
        ee()->setMock('input', new class {
            public function get_post($key)
            {
                if ($key === 'entry_id') {
                    return 42;
                }
                return null;
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $trailing = false)
            {
                return $base . ltrim($uri, '/');
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'base_url') {
                    return 'https://base.example/';
                }
                return null;
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return $name === 'structure_generate_page_url_end';
            }
            public function call($name, $url)
            {
                return 'https://hooked.example/final';
            }
        });

        $mcp = $this->makeMcp();
        $mcp->sql = new class {
            public function get_site_pages()
            {
                return ['url' => '{base_url}/', 'uris' => [42 => '/about/']];
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('https://hooked.example/final');
        $mcp->link();
    }

    public function testDeleteValidationSubmitAndListingSiteIdFix()
    {
        $captured = (object) [
            'deleted' => [],
            'redirects' => [],
            'cleanupModes' => [],
            'updates' => [],
        ];

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'structure_nav_history') {
                    return 'n';
                }
                return null;
            }
        });
        ee()->setMock('input', new class {
            public function get_post($key)
            {
                if ($key === 'toggle') {
                    return [20, 21];
                }
                if ($key === 'id') {
                    return 5;
                }
                return null;
            }
            public function post($key)
            {
                if ($key === 'mode') {
                    return 'from_post';
                }
                return null;
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
                $this->captured->redirects[] = $url;
            }
        });
        ee()->setMock('db', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function where($field, $value)
            {
                $this->captured->updates[] = ['where', $field, $value];
                return $this;
            }
            public function update($table, $data)
            {
                $this->captured->updates[] = ['update', $table, $data];
                return true;
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return 'cp://' . $path;
            }
        });
        ee()->setMock('CP/Alert', new class {
            public function makeInline($title)
            {
                return new class {
                    public function asSuccess() { return $this; }
                    public function withTitle($title) { return $this; }
                    public function canClose() { return $this; }
                    public function defer() { return $this; }
                };
            }
        });

        $mcp = $this->makeMcp();
        $mcp->site_id = 1;
        $mcp->base_url = 'cp://addons/settings/structure/index';
        $mcp->structure = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function delete_data($ids)
            {
                $this->captured->deleted[] = $ids;
            }
        };
        $mcp->sql = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function cleanup($mode)
            {
                $this->captured->cleanupModes[] = $mode;
            }
            public function get_structure_channels($type = '')
            {
                if ($type === 'listing') {
                    return [7 => ['channel_title' => 'Listings']];
                }
                return [];
            }
        };

        $mcp->delete();
        $_GET['mode'] = 'from_get';
        $mcp->validation_submit();
        unset($_GET['mode']);
        $mcp->validation_submit();
        $mcp->listing_site_id_fix();

        $this->assertSame([[20, 21]], $captured->deleted);
        $this->assertContains('from_get', $captured->cleanupModes);
        $this->assertContains('from_post', $captured->cleanupModes);
        $this->assertNotEmpty($captured->updates);
        $this->assertNotEmpty($captured->redirects);
    }

    public function testIndexChannelModuleValidationAndNavHistoryViews()
    {
        $captured = (object) [
            'views' => [],
            'redirects' => [],
            'js' => [],
            'dbUpdates' => [],
        ];

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'theme_folder_url') {
                    return 'https://cdn.example.com/themes/';
                }
                return null;
            }
        });
        ee()->setMock('lang', new class {
            public function line($key)
            {
                return strtoupper($key);
            }
        });
        ee()->setMock('view', (object) ['cp_page_title' => '']);
        ee()->setMock('session', new class {
            public function userdata($key)
            {
                if ($key === 'assigned_channels') {
                    return [2 => ['allowed' => true]];
                }
                if ($key === 'member_id') {
                    return 10;
                }
                if ($key === 'screen_name') {
                    return 'tester';
                }
                return null;
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
                $this->captured->redirects[] = $url;
            }
        });
        ee()->setMock('cp', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function load_package_js($name)
            {
                $this->captured->js[] = $name;
            }
            public function add_to_head($html)
            {
            }
            public function add_to_foot($html)
            {
            }
            public function set_breadcrumb($url, $title)
            {
            }
        });
        ee()->setMock('general_helper', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function cpURL($section, $method = '', $params = [])
            {
                return 'cp://' . $section . '/' . $method;
            }
            public function view($view, $vars, $return = false)
            {
                $this->captured->views[] = $view;
                return 'view:' . $view;
            }
        });
        ee()->setMock('load', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function library($name)
            {
                if ($name === 'pagination') {
                    ee()->setMock('pagination', new class {
                        public function initialize($config)
                        {
                        }
                        public function create_links()
                        {
                            return 'links';
                        }
                    });
                }
            }
            public function helper($name)
            {
            }
            public function view($view, $vars = [], $return = false)
            {
                $this->captured->views[] = $view;
                return 'load-view:' . $view;
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return $args[0] ?? null;
            }
        });
        ee()->setMock('input', new class {
            public function get($key, $xss = false)
            {
                if ($key === 'per_page') {
                    return 0;
                }
                return null;
            }
            public function get_post($key)
            {
                return null;
            }
            public function post($key)
            {
                return null;
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path, $params = [])
            {
                return 'cp://' . $path;
            }
        });
        ee()->setMock('CP/Alert', new class {
            public function makeInline($name)
            {
                return new class {
                    public function asIssue() { return $this; }
                    public function asSuccess() { return $this; }
                    public function withTitle($title) { return $this; }
                    public function canClose() { return $this; }
                    public function defer() { return $this; }
                };
            }
        });
        ee()->setMock('Model', new class {
            public function get($model)
            {
                if ($model === 'Channel') {
                    return new class {
                        public function filter($field, $operator, $value)
                        {
                            return $this;
                        }
                        public function order($field, $direction)
                        {
                            return $this;
                        }
                        public function fields(...$fields)
                        {
                            return $this;
                        }
                        public function all()
                        {
                            return new class {
                                public function pluck($field)
                                {
                                    if ($field === 'allow_preview') {
                                        return ['y'];
                                    }
                                    return [2];
                                }
                            };
                        }
                    };
                }
                return new class {
                    public function __call($name, $args)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return [];
                    }
                };
            }
        });
        ee()->setMock('db', new class($this, $captured) {
            private $test;
            private $captured;
            public function __construct($test, $captured)
            {
                $this->test = $test;
                $this->captured = $captured;
            }
            public function field_exists($field, $table)
            {
                return true;
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                return $this;
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'structure') {
                    return new class {
                        public function row()
                        {
                            return (object) ['updated' => '2026-01-01 00:00:00'];
                        }
                    };
                }
                return $this->test->result([]);
            }
            public function query($sql)
            {
                if (strpos($sql, 'SELECT channel_id FROM exp_channels') !== false) {
                    return $this->test->result([['channel_id' => 2]], 1);
                }
                return $this->test->result([]);
            }
            public function where($field = null, $value = null)
            {
                $this->captured->dbUpdates[] = ['where', $field, $value];
                return $this;
            }
            public function update($table, $data)
            {
                $this->captured->dbUpdates[] = ['update', $table, $data];
                return true;
            }
            public function get($table = null, $limit = null, $offset = null)
            {
                if ($table === 'structure_nav_history') {
                    return new class {
                        public function num_rows()
                        {
                            return 1;
                        }
                        public function result()
                        {
                            return [(object) ['id' => 1, 'site_id' => 1]];
                        }
                    };
                }
                return $this->test->result([]);
            }
            public function order_by($field, $direction = '')
            {
                return $this;
            }
        });

        $mcp = $this->makeMcp();
        $mcp->site_id = 1;
        $mcp->base_url = 'cp://addons/settings/structure';
        $mcp->data = [];
        $mcp->sql = new class {
            public function get_settings()
            {
                return [
                    'show_global_add_page' => 'y',
                    'show_picker' => 'y',
                    'redirect_on_login' => 'n',
                ];
            }
            public function user_access($perm, $settings = [])
            {
                return true;
            }
            public function get_data()
            {
                return [2 => ['entry_id' => 2]];
            }
            public function get_site_pages()
            {
                return ['url' => '/', 'uris' => [2 => '/about/'], 'templates' => [2 => 5]];
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                if ($type === 'listing') {
                    return [];
                }
                return [
                    2 => ['channel_id' => 2, 'channel_title' => 'Pages', 'template_id' => 5, 'type' => 'page'],
                ];
            }
            public function get_member_settings()
            {
                return ['nav_state' => (object) []];
            }
            public function get_cp_asset_data()
            {
                return [];
            }
            public function get_page_count()
            {
                return 1;
            }
            public function get_status_colors()
            {
                return ['open' => '#fff'];
            }
            public function get_templates()
            {
                return [['template_id' => 5, 'template_name' => 'index']];
            }
            public function get_member_groups()
            {
                return [['id' => 2, 'title' => 'Members']];
            }
            public function extension_is_installed()
            {
                return true;
            }
            public function cleanup_check()
            {
                return [
                    'total_site_pages_entries' => 1,
                    'total_structure_entries' => 1,
                    'total_site_pages_duplicates' => 0,
                    'orphaned_entries' => [],
                    'ee_orphans' => 0,
                    'site_pages_orphans' => 0,
                    'site_pages_listing_orphans' => 0,
                    'structure_orphans' => 0,
                    'structure_listing_orphans' => 0,
                    'validation_action_enabled' => false,
                    'duplicate_rights' => 0,
                    'duplicate_lefts' => 0,
                    'site_pages_uri_duplicates' => [],
                    'mismatch_url_entries' => [],
                    'template_id_errors' => [],
                ];
            }
            public function cleanup($mode)
            {
            }
        };
        $mcp->structure = new class {
            public function get_data_cids($listings = false)
            {
                return [];
            }
            public function get_structure_channels($type = '')
            {
                return [2 => ['template_id' => 5]];
            }
        };

        $this->assertSame('view:index', $mcp->index());
        $this->assertSame('view:channel_settings', $mcp->channel_settings());
        $this->assertSame('view:module_settings', $mcp->module_settings());
        $this->assertSame('view:validation', $mcp->validation());
        $this->assertSame('load-view:nav_history', $mcp->nav_history());
        $this->assertNotEmpty($captured->views);
        $this->assertNotEmpty($captured->js);
    }

    public function testSubmitDeleteAndRestoreFlows()
    {
        $captured = (object) [
            'redirects' => [],
            'alerts' => 0,
            'structureDeletes' => [],
            'updates' => [],
            'insertBatches' => [],
        ];
        $state = (object) ['phase' => 'channel_settings_submit'];

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'structure_nav_history') {
                    return 'n';
                }
                return null;
            }
        });
        ee()->setMock('input', new class($state) {
            private $state;
            public function __construct($state)
            {
                $this->state = $state;
            }
            public function get_post($key)
            {
                if ($this->state->phase === 'delete_channels' && $key === 'channel_ids') {
                    return '2';
                }
                if ($this->state->phase === 'restore' && $key === 'id') {
                    return 9;
                }
                return null;
            }
            public function post($key)
            {
                return null;
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
                $this->captured->redirects[] = $url;
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path, $params = [])
            {
                return 'cp://' . $path;
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
                $this->captured->alerts++;
                return new class {
                    public function asSuccess() { return $this; }
                    public function asIssue() { return $this; }
                    public function withTitle($title) { return $this; }
                    public function canClose() { return $this; }
                    public function defer() { return $this; }
                };
            }
        });
        ee()->setMock('general_helper', new class {
            public function view($view, $vars, $return = false)
            {
                return 'view:' . $view;
            }
        });
        ee()->setMock('db', new class($this, $captured) {
            private $test;
            private $captured;
            public function __construct($test, $captured)
            {
                $this->test = $test;
                $this->captured = $captured;
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'structure_channels') {
                    return $this->test->result([
                        ['channel_id' => 2, 'site_id' => 1, 'type' => 'page']
                    ], 1);
                }
                if ($table === 'structure_nav_history') {
                    return new class {
                        public $num_rows = 1;
                        public function row()
                        {
                            return (object) [
                                'site_id' => 1,
                                'site_pages' => 'encoded-pages',
                                'structure' => json_encode([['entry_id' => 2, 'site_id' => 1]])
                            ];
                        }
                    };
                }
                return $this->test->result([]);
            }
            public function where($field = null, $value = null)
            {
                $this->captured->updates[] = ['where', $field, $value];
                return $this;
            }
            public function where_in($field, $values)
            {
                $this->captured->updates[] = ['where_in', $field, $values];
                return $this;
            }
            public function update($table, $data)
            {
                $this->captured->updates[] = ['update', $table, $data];
                return true;
            }
            public function insert($table, $data)
            {
                $this->captured->updates[] = ['insert', $table, $data];
                return true;
            }
            public function field_exists($field, $table)
            {
                return true;
            }
            public function query($sql)
            {
                $this->captured->updates[] = ['query', $sql];
                if (strpos($sql, 'SELECT channel_id, channel_title FROM channels') !== false) {
                    return $this->test->result([['channel_id' => 2, 'channel_title' => 'Pages']], 1);
                }
                return $this->test->result([]);
            }
            public function insert_string($table, $data)
            {
                return 'INSERT_SQL';
            }
            public function delete($table, $where = null)
            {
                $this->captured->updates[] = ['delete', $table, $where];
                return true;
            }
            public function insert_batch($table, $rows)
            {
                $this->captured->insertBatches[] = [$table, $rows];
                return true;
            }
        });

        $mcp = $this->makeMcp();
        $mcp->site_id = 1;
        $mcp->base_url = 'cp://addons/settings/structure';
        $mcp->sql = new class {
            public function get_settings()
            {
                return ['show_picker' => 'y'];
            }
            public function user_access($perm, $settings = [])
            {
                return true;
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                if ($type === '') {
                    return [2 => ['channel_id' => 2, 'type' => 'page']];
                }
                return [];
            }
        };
        $mcp->structure = new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function delete_data_by_channel($channel)
            {
                $this->captured->structureDeletes[] = $channel;
            }
        };

        $_POST = [
            2 => ['type' => 'page', 'template_id' => 5, 'split_assets' => 'n', 'show_in_page_selector' => 'y']
        ];
        $mcp->channel_settings_submit();

        $_POST = [
            'show_picker' => 'y',
            'perm_delete_2' => 2,
            'submit' => 'save',
        ];
        $mcp->module_settings_submit();

        $state->phase = 'delete_channels';
        $mcp->delete_channels();

        $state->phase = 'restore';
        $mcp->restore();

        $this->assertContains('2', $captured->structureDeletes);
        $this->assertNotEmpty($captured->insertBatches);
        $this->assertGreaterThan(0, $captured->alerts);
        $this->assertNotEmpty($captured->redirects);
    }

    public function testAdditionalMcpBranchesForCoverage()
    {
        $captured = (object) ['views' => [], 'redirects' => []];

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'theme_folder_url') {
                    return 'https://cdn.example.com/themes/';
                }
                return null;
            }
        });
        ee()->setMock('lang', new class {
            public function line($key)
            {
                return $key;
            }
        });
        ee()->setMock('view', (object) ['cp_page_title' => '']);
        ee()->setMock('session', new class {
            public function userdata($key)
            {
                if ($key === 'assigned_channels') {
                    return [];
                }
                return null;
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
                $this->captured->redirects[] = $url;
            }
        });
        ee()->setMock('general_helper', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function cpURL($section, $method = '', $params = [])
            {
                return 'cp://' . $section . '/' . $method;
            }
            public function view($view, $vars, $return = false)
            {
                $this->captured->views[] = $view;
                return 'view:' . $view;
            }
        });
        ee()->setMock('cp', new class {
            public function load_package_js($name)
            {
            }
            public function add_to_head($html)
            {
            }
            public function add_to_foot($html)
            {
            }
            public function set_breadcrumb($url, $title)
            {
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
            public function helper($name)
            {
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return $args[0] ?? null;
            }
        });
        ee()->setMock('input', new class {
            public function get_post($key)
            {
                return null;
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path, $params = [])
            {
                return 'cp://' . $path;
            }
        });
        ee()->setMock('CP/Alert', new class {
            public function makeInline($name)
            {
                return new class {
                    public function asIssue() { return $this; }
                    public function asSuccess() { return $this; }
                    public function withTitle($title) { return $this; }
                    public function canClose() { return $this; }
                    public function defer() { return $this; }
                };
            }
        });
        ee()->setMock('Model', new class {
            public function get($model)
            {
                return new class {
                    public function filter($field, $operator, $value) { return $this; }
                    public function order($field, $direction) { return $this; }
                    public function fields(...$fields) { return $this; }
                    public function all()
                    {
                        return new class {
                            public function pluck($field)
                            {
                                return [];
                            }
                        };
                    }
                };
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function field_exists($field, $table)
            {
                return true;
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                return $this;
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'structure') {
                    return new class {
                        public function row()
                        {
                            return (object) ['updated' => '2026-01-01 00:00:00'];
                        }
                    };
                }
                if ($table === 'structure_channels') {
                    return $this->test->result([['channel_id' => 2, 'type' => 'page']], 1);
                }
                return $this->test->result([]);
            }
            public function query($sql)
            {
                if (strpos($sql, 'SELECT channel_id, channel_title FROM channels') !== false) {
                    return $this->test->result([['channel_id' => 2, 'channel_title' => 'Pages']], 1);
                }
                return $this->test->result([]);
            }
            public function where($field = null, $value = null)
            {
                return $this;
            }
            public function where_in($field, $values)
            {
                return $this;
            }
            public function update($table, $data)
            {
                return true;
            }
            public function get($table = null)
            {
                return $this->test->result([]);
            }
        });

        $mcp = $this->makeMcp();
        $mcp->site_id = 1;
        $mcp->base_url = 'cp://addons/settings/structure';
        $mcp->extra_reorder_options = true;
        $mcp->sql = new class {
            public function get_settings()
            {
                return ['show_global_add_page' => 'y', 'show_picker' => 'y'];
            }
            public function user_access($perm, $settings = [])
            {
                if ($perm === 'perm_view_validation') {
                    return false;
                }
                return true;
            }
            public function get_data()
            {
                return [];
            }
            public function get_site_pages()
            {
                return ['url' => '/', 'uris' => [2 => '/'], 'templates' => [2 => 5]];
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                return [2 => ['channel_id' => 2, 'type' => 'page', 'template_id' => 5, 'channel_title' => 'Pages']];
            }
            public function get_member_settings()
            {
                return [];
            }
            public function get_cp_asset_data()
            {
                return [];
            }
            public function get_page_count()
            {
                return 0;
            }
            public function get_status_colors()
            {
                return [];
            }
            public function get_templates()
            {
                return [];
            }
            public function get_member_groups()
            {
                return [];
            }
            public function extension_is_installed()
            {
                return true;
            }
            public function cleanup_check()
            {
                return [
                    'total_site_pages_entries' => 0,
                    'total_structure_entries' => 0,
                    'total_site_pages_duplicates' => 0,
                    'orphaned_entries' => [],
                    'ee_orphans' => 0,
                    'site_pages_orphans' => 0,
                    'site_pages_listing_orphans' => 0,
                    'structure_orphans' => 0,
                    'structure_listing_orphans' => 0,
                    'validation_action_enabled' => false,
                    'duplicate_rights' => 0,
                    'duplicate_lefts' => 0,
                    'site_pages_uri_duplicates' => [],
                    'mismatch_url_entries' => [],
                    'template_id_errors' => [],
                ];
            }
        };
        $mcp->structure = new class {
            public function get_data_cids($listings = false)
            {
                return [];
            }
        };

        $_POST = [
            2 => ['type' => 'unmanaged', 'template_id' => 0]
        ];
        $this->assertSame('view:get_started', $mcp->index());
        $this->assertSame('view:delete_channels_confirm', $mcp->channel_settings_submit());
        $this->assertSame('view:module_settings', $mcp->module_settings());
        $this->assertSame('view:validation', $mcp->validation());
        $this->assertNotEmpty($captured->redirects);
    }

    public function testChannelSettingsAssetBranchAndDeletePermissionDenied()
    {
        $captured = (object) ['redirects' => [], 'saved' => 0];

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'structure_nav_history') {
                    return 'n';
                }
                return null;
            }
        });
        ee()->setMock('input', new class {
            public function get_post($key)
            {
                if ($key === 'channel_ids') {
                    return '2';
                }
                return null;
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
                $this->captured->redirects[] = $url;
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path, $params = [])
            {
                return 'cp://' . $path;
            }
        });
        ee()->setMock('CP/Alert', new class {
            public function makeInline($name)
            {
                return new class {
                    public function asIssue() { return $this; }
                    public function asSuccess() { return $this; }
                    public function withTitle($title) { return $this; }
                    public function canClose() { return $this; }
                    public function defer() { return $this; }
                };
            }
        });
        ee()->setMock('Model', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get($model)
            {
                return new class($this->captured) {
                    private $captured;
                    public function __construct($captured)
                    {
                        $this->captured = $captured;
                    }
                    public function filter($field, $operator, $value)
                    {
                        return $this;
                    }
                    public function first()
                    {
                        return new class($this->captured) {
                            private $captured;
                            public $preview_url = '';
                            public function __construct($captured)
                            {
                                $this->captured = $captured;
                            }
                            public function save()
                            {
                                $this->captured->saved++;
                            }
                        };
                    }
                };
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'structure_channels') {
                    return $this->test->result([['channel_id' => 2, 'site_id' => 1, 'type' => 'asset']], 1);
                }
                return $this->test->result([]);
            }
            public function field_exists($field, $table)
            {
                return true;
            }
            public function where($field = null, $value = null)
            {
                return $this;
            }
            public function where_in($field, $values)
            {
                return $this;
            }
            public function update($table, $data)
            {
                return true;
            }
            public function insert($table, $data)
            {
                return true;
            }
        });

        $mcp = $this->makeMcp();
        $mcp->site_id = 1;
        $mcp->base_url = 'cp://addons/settings/structure';
        $mcp->sql = new class {
            public function get_settings()
            {
                return [];
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                return [2 => ['channel_id' => 2, 'type' => 'asset']];
            }
            public function user_access($perm, $settings = [])
            {
                if ($perm === 'perm_delete') {
                    return false;
                }
                return true;
            }
        };
        $mcp->structure = new class {
            public function delete_data_by_channel($channel)
            {
            }
        };

        $_POST = [
            2 => ['type' => 'asset', 'template_id' => 0]
        ];
        $mcp->channel_settings_submit();
        $mcp->delete_channels();

        $this->assertSame(1, $captured->saved);
        $this->assertNotEmpty($captured->redirects);
    }

    public function testAjaxCollapseCoversUpdateInsertAndPreDieEncodingPath()
    {
        $mcp = $this->makeMcp();
        $mcp->site_id = 1;

        ee()->setMock('input', new class {
            public function get_post($key)
            {
                if ($key === 'collapsed') {
                    return [10, 11];
                }
                return null;
            }
        });

        ee()->setMock('session', new class {
            public function userdata($key)
            {
                if ($key === 'member_id') {
                    return 77;
                }
                return null;
            }
        });

        // update branch
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return new class {
                    public $num_rows = 1;
                };
            }
            public function where($where)
            {
                return $this;
            }
            public function update($table, $data)
            {
                throw new RuntimeException('stop-update');
            }
        });
        try {
            $mcp->ajax_collapse();
            $this->fail('Expected update branch interruption');
        } catch (RuntimeException $e) {
            $this->assertSame('stop-update', $e->getMessage());
        }

        // insert branch
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return new class {
                    public $num_rows = 0;
                };
            }
            public function insert($table, $data)
            {
                throw new RuntimeException('stop-insert');
            }
        });
        try {
            $mcp->ajax_collapse();
            $this->fail('Expected insert branch interruption');
        } catch (RuntimeException $e) {
            $this->assertSame('stop-insert', $e->getMessage());
        }

    }

    public function testAjaxReorderCoversPrimaryFlowUntilHook()
    {
        $oldPost = $_POST;
        $oldGet = $_GET;
        $_POST = [
            'timestamp' => '2026-01-01 00:00:00',
            'page-ui' => [['entry_id' => 10], ['entry_id' => 20]],
        ];
        $_GET = ['site_id' => 1];

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'structure_nav_history') {
                    return 'n';
                }
                return null;
            }
        });
        ee()->setMock('session', new class {
            public function userdata($key)
            {
                if ($key === 'screen_name') {
                    return 'Test User';
                }
                return null;
            }
        });
        ee()->setMock('logger', new class {
            public $messages = [];
            public function log_action($message)
            {
                $this->messages[] = $message;
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return $name === 'structure_reorder_end';
            }
            public function call($name, ...$args)
            {
                throw new RuntimeException('stop-reorder-hook');
            }
        });

        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'structure') {
                    return new class {
                        public function row()
                        {
                            return (object) ['updated' => '2026-01-01 00:00:00'];
                        }
                    };
                }
                return $this->test->result([]);
            }
            public function query($sql)
            {
                if (strpos($sql, 'SELECT entry_id,channel_id FROM exp_channel_titles') !== false) {
                    return $this->test->result([
                        ['entry_id' => 10, 'channel_id' => 2],
                        ['entry_id' => 20, 'channel_id' => 3],
                    ], 2);
                }
                if (strpos($sql, 'SELECT entry_id, url_title FROM exp_channel_titles WHERE channel_id = 7') !== false) {
                    return $this->test->result([
                        ['entry_id' => 30, 'url_title' => 'Listing-30'],
                    ], 1);
                }
                return $this->test->result([]);
            }
            public function where($field, $value = null)
            {
                return $this;
            }
            public function update($table, $data = null)
            {
                return true;
            }
            public function escape_str($value)
            {
                return $value;
            }
        });

        $mcp = $this->makeMcp();
        $mcp->site_id = 1;
        $mcp->logging = true;
        $mcp->structure = new class {
            public function nestedsortable_to_nestedset($sortable)
            {
                return [
                    10 => ['crumb' => [10], 'lft' => 2, 'rgt' => 5, 'depth' => 1],
                    20 => ['crumb' => [10, 20], 'lft' => 3, 'rgt' => 4, 'depth' => 2],
                ];
            }
            public function get_structure_channels()
            {
                return [7 => ['template_id' => 88]];
            }
            public function create_full_uri($parent_uri, $slug)
            {
                return trim($parent_uri, '/') . '/' . trim($slug, '/') . '/';
            }
            public function set_site_pages($site_id, $site_pages)
            {
                return true;
            }
        };
        $mcp->sql = new class {
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [10 => '/', 20 => '/about/team/'],
                    'templates' => [10 => 5, 20 => 6],
                ];
            }
            public function get_data()
            {
                return [
                    10 => ['listing_cid' => 7, 'hidden' => 'n', 'structure_url_title' => 'home', 'template_id' => 5],
                    20 => ['listing_cid' => 0, 'hidden' => 'n', 'structure_url_title' => 'team', 'template_id' => 6],
                ];
            }
            public function get_listing_channel($entry_id)
            {
                return ((int) $entry_id === 10) ? 7 : false;
            }
            public function get_channel_listing_entries($listing_channel)
            {
                return [
                    30 => ['uri' => 'custom-listing', 'template_id' => 99],
                ];
            }
        };

        try {
            $mcp->ajax_reorder();
            $this->fail('Expected hook interruption before terminal die');
        } catch (RuntimeException $e) {
            $this->assertSame('stop-reorder-hook', $e->getMessage());
        } finally {
            $_POST = $oldPost;
            $_GET = $oldGet;
        }

        $this->assertNotEmpty(ee()->logger->messages);
    }

    private function makeMcp()
    {
        return (new ReflectionClass('Structure_mcp'))->newInstanceWithoutConstructor();
    }

    public function result(array $rows, ?int $numRows = null)
    {
        return new class($rows, $numRows) {
            private $rows;
            public $num_rows;
            public function __construct($rows, $numRows)
            {
                $this->rows = $rows;
                $this->num_rows = $numRows ?? count($rows);
            }
            public function num_rows()
            {
                return $this->num_rows;
            }
            public function result_array()
            {
                return $this->rows;
            }
            public function result()
            {
                return array_map(function ($row) {
                    return (object) $row;
                }, $this->rows);
            }
            public function row($column = null)
            {
                $row = (object) ($this->rows[0] ?? []);
                if ($column !== null) {
                    return $row->$column ?? null;
                }
                return $row;
            }
        };
    }
}
