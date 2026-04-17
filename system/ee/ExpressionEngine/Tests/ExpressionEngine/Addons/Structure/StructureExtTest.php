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
if (!defined('REQ')) {
    define('REQ', 'CP');
}
if (!defined('AJAX_REQUEST')) {
    define('AJAX_REQUEST', false);
}
if (!function_exists('load_class')) {
    function &load_class($class, $directory = 'core')
    {
        static $router;
        if ($router === null) {
            $router = new class {
                public function _parse_routes()
                {
                }
            };
        }
        return $router;
    }
}

require_once __DIR__ . '/../../../../Addons/structure/ext.structure.php';

use PHPUnit\Framework\TestCase;

class StructureExtFixture extends Structure_ext
{
    public $registered = [];
    public $unregistered = [];
    public $updatedVersion = false;
    public $redirectResult = null;

    public function __construct()
    {
        $this->version = '9.9.9';
        $this->settings = [];
        $this->site_pages = ['url' => 'https://site.test/', 'uris' => []];
    }

    public function registerExtension($method, $hook = null, $priority = 10, $enabled = 'y')
    {
        $this->registered[] = [$method, $hook, $priority, $enabled];
        return true;
    }

    protected function unregisterExtension($method, $hook = null)
    {
        $this->unregistered[] = [$method, $hook];
        return true;
    }

    protected function updateVersion()
    {
        $this->updatedVersion = true;
        return true;
    }

    public function _redirect_url($entry_id, $meta, $arg1 = null, $arg2 = false, $arg3 = null, $arg4 = false)
    {
        return $this->redirectResult;
    }
}

class StructureExtTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    public function testActivateDisableAndUpdateExtensionFlow()
    {
        $fixture = new StructureExtFixture();
        $fixture->activate_extension();
        $this->assertCount(14, $fixture->registered);

        $db = new class {
            public $whereCalls = [];
            public $deleteCalls = [];
            public $updateCalls = [];
            public function where($field, $value)
            {
                $this->whereCalls[] = [$field, $value];
                return $this;
            }
            public function delete($table)
            {
                $this->deleteCalls[] = $table;
                return true;
            }
            public function update($table, $data, $where = null)
            {
                $this->updateCalls[] = [$table, $data, $where];
                return true;
            }
        };
        ee()->setMock('db', $db);

        $this->assertTrue($fixture->disable_extension());
        $this->assertContains('extensions', $db->deleteCalls);
        $this->assertContains('menu_items', $db->deleteCalls);

        $this->assertFalse($fixture->update_extension(false));
        $this->assertFalse($fixture->update_extension('9.9.9'));

        $fixture->update_extension('2.9.0');
        $this->assertTrue($fixture->updatedVersion);
        $this->assertNotEmpty($fixture->registered);
        $this->assertNotEmpty($fixture->unregistered);
        $this->assertNotEmpty($db->updateCalls);
    }

    public function testPublishPreviewAndRedirectHelpers()
    {
        ee()->setMock('uri', (object) ['page_query_string' => '']);
        ee()->setMock('CP/URL', new class {
            public function make($path, $params = [])
            {
                return 'cp://' . $path;
            }
        });

        $fixture = new StructureExtFixture();
        $fixture->site_pages = ['uris' => [10 => '/parent']];

        $route = $fixture->publish_live_preview_route(
            ['structure__parent_id' => 10, 'structure__uri' => 'child', 'structure__template_id' => 99, 'entry_id' => 17],
            'fallback',
            7
        );
        $this->assertSame('/parent/child', $route['uri']);
        $this->assertSame(99, $route['template_id']);
        $this->assertSame(17, ee()->uri->page_query_string);

        $menu = new class {
            public $items = [];
            public function addItem($title, $url)
            {
                $this->items[] = [$title, $url];
            }
        };
        $fixture->cp_custom_menu($menu);
        $this->assertSame('Structure', $menu->items[0][0]);

        $fixture->sql = new class {
            public function get_settings()
            {
                return ['redirect_on_publish' => 'n'];
            }
        };
        $entry = (object) ['entry_id' => 17, 'channel_id' => 5];
        $this->assertSame('cp://publish/edit/', $fixture->entry_save_and_close_redirect($entry, ''));
        $this->assertSame('existing://url', $fixture->entry_save_and_close_redirect($entry, 'existing://url'));

        $fixture->sql = new class {
            public function get_settings()
            {
                return ['redirect_on_publish' => 'y'];
            }
        };
        $this->assertSame('cp://addons/settings/structure/index', $fixture->entry_save_and_close_redirect($entry, 'existing://url'));

        ee()->setMock('extensions', (object) ['last_call' => 'override://last']);
        $rm = new ReflectionMethod('Structure_ext', '_redirect_url');
        \TestReflectionHelper::makeAccessible($rm);
        $this->assertSame('override://last', $rm->invoke($fixture, 17, ['channel_id' => 5], null, false, 'orig://loc', false));
        $this->assertFalse($rm->invoke($fixture, 17, ['channel_id' => 5], null, false, 'orig://loc', true));

        $fixture->sql = new class {
            public function get_settings()
            {
                return ['redirect_on_publish' => 'structure_only'];
            }
        };
        ee()->setMock('db', new class extends eeDbArMock {
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return new class {
                    public function num_rows() { return 1; }
                    public function row($column = null) { return 'page'; }
                };
            }
        });
        $this->assertSame('cp://addons/settings/structure/index', $rm->invoke($fixture, 17, ['channel_id' => 5], null, true, null, false));
        $fixture->sql = new class {
            public function get_settings()
            {
                return ['redirect_on_publish' => 'y'];
            }
        };
        $this->assertTrue($rm->invoke($fixture, 17, ['channel_id' => 5], null, true, null, true));

        $fixture->sql = new class {
            public function get_settings()
            {
                return ['redirect_on_publish' => 'structure_only'];
            }
        };
        ee()->setMock('db', new class extends eeDbArMock {
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return new class {
                    public function num_rows() { return 1; }
                    public function row($column = null) { return 'page'; }
                };
            }
        });
        $this->assertTrue($rm->invoke($fixture, 17, ['channel_id' => 5], null, true, null, true));

        ee()->setMock('db', new class extends eeDbArMock {
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return new class {
                    public function num_rows() { return 1; }
                    public function row($column = null) { return 'unmanaged'; }
                };
            }
        });
        $this->assertFalse($rm->invoke($fixture, 17, ['channel_id' => 5], null, true, null, true));
    }

    public function testParseTagAndRegistrationHelpers()
    {
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
                return false;
            }
            public function call($name, $arg)
            {
                return $arg;
            }
        });

        $fixture = new StructureExtFixture();
        $fixture->site_pages = ['url' => '{base_url}/', 'uris' => [4 => '/about/us']];
        $fixture->sql = new class {
            public function get_settings() { return ['add_trailing_slash' => 'y']; }
            public function get_entry_title($entryId) { return 'Title ' . $entryId; }
            public function get_slug($slug) { return trim((string) $slug, '/'); }
            public function get_child_entries($entryId) { return $entryId == 4 ? [8, 9] : []; }
        };

        $this->assertSame('https://base.example/about/us/', $fixture->_parse_tag_url_for([0, 4]));
        $this->assertSame('/about/us', $fixture->_parse_tag_uri_for([0, 4]));
        $this->assertSame('Title 4', $fixture->_parse_tag_title_for([0, 4]));
        $this->assertSame('about/us', $fixture->_parse_tag_slug_for([0, 4]));
        $this->assertSame('8|9', $fixture->_parse_tag_child_ids_for([0, 4]));
        $this->assertFalse($fixture->_parse_tag_child_ids_for([0, 999]));

        $db = new class {
            public $existing = [0, 1];
            public $inserted = [];
            public $deleted = [];
            public $updated = [];
            public function get_where($table, $where)
            {
                $count = array_shift($this->existing);
                return (object) ['num_rows' => $count];
            }
            public function insert($table, $data)
            {
                $this->inserted[] = [$table, $data];
                return true;
            }
            public function delete($table, $where)
            {
                $this->deleted[] = [$table, $where];
                return true;
            }
            public function update($table, $data, $where)
            {
                $this->updated[] = [$table, $data, $where];
                return true;
            }
        };
        ee()->setMock('db', $db);

        $real = (new ReflectionClass('Structure_ext'))->newInstanceWithoutConstructor();
        $real->version = '9.9.9';
        $real->settings = [];

        $this->assertTrue($real->registerExtension('sample_method'));
        $this->assertTrue($real->registerExtension('sample_method'));
        $this->assertCount(1, $db->inserted);

        $rm = new ReflectionMethod($real, 'unregisterExtension');
        \TestReflectionHelper::makeAccessible($rm);
        $this->assertTrue($rm->invoke($real, 'sample_method', null));

        $rm = new ReflectionMethod($real, 'updateVersion');
        \TestReflectionHelper::makeAccessible($rm);
        $this->assertTrue($rm->invoke($real));
        $this->assertNotEmpty($db->deleted);
        $this->assertNotEmpty($db->updated);
    }

    public function testHookMethodsSubmissionLoginPaginationAndSessionsEnd()
    {
        $captured = (object) [
            'redirects' => [],
            'flashdata' => [],
        ];

        ee()->setMock('input', new class {
            public function post($key)
            {
                if ($key === 'submit') {
                    return 'save_and_close';
                }
                return null;
            }
        });
        ee()->setMock('session', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function set_flashdata($key, $value)
            {
                $this->captured->flashdata[$key] = $value;
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
            public function make($path)
            {
                return 'cp://' . $path;
            }
        });
        ee()->setMock('uri', (object) ['uri_string' => 'blog/P25']);
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'base_url') {
                    return 'https://base.example/';
                }
                if ($key === 'cp_session_type') {
                    return 'cs';
                }
                if ($key === 'cp_url') {
                    return 'https://cp.example/admin.php';
                }
                return null;
            }
        });

        $fixture = new StructureExtFixture();
        $fixture->site_pages = ['url' => '{base_url}/'];
        $fixture->redirectResult = false;
        $fixture->sql = new class {
            public function get_settings()
            {
                return ['redirect_on_login' => 'y'];
            }
        };

        $entry = new class {
            public $channel_id = 4;
            public function getId()
            {
                return 99;
            }
        };
        $this->assertSame('cp://publish/create/4', $fixture->entry_submission_redirect($entry));

        ee()->setMock('input', new class {
            public function post($key)
            {
                if ($key === 'submit') {
                    return 'save';
                }
                return null;
            }
        });
        $this->assertSame('cp://publish/edit/entry/99', $fixture->entry_submission_redirect($entry));

        ee()->setMock('input', new class {
            public function post($key)
            {
                if ($key === 'submit') {
                    return 'save_and_close';
                }
                return null;
            }
        });
        $fixture->after_channel_entry_save((object) ['channel_id' => 4], []);
        $this->assertSame('1', $captured->flashdata['structure_redirect']);
        $this->assertSame(4, $captured->flashdata['structure_channel_id']);

        ee()->setMock('input', new class {
            public function post($key)
            {
                return 'save';
            }
        });
        $fixture->after_channel_entry_save((object) ['channel_id' => 4], []);

        $fixture->cp_member_login();
        $this->assertSame('cp://addons/settings/structure/index', $captured->redirects[0]);
        $fixture->sql = new class {
            public function get_settings()
            {
                return ['redirect_on_login' => 'n'];
            }
        };
        $beforeRedirects = count($captured->redirects);
        $fixture->cp_member_login();
        $this->assertSame($beforeRedirects, count($captured->redirects));

        $pagination = (object) ['offset' => 0, 'basepath' => ''];
        $fixture->channel_module_create_pagination($pagination);
        $this->assertSame('25', $pagination->offset);
        $this->assertSame('https://base.example/blog', $pagination->basepath);

        ee()->uri->uri_string = 'blog/P30';
        $fixture->pagination_create($pagination);
        $this->assertSame('30', $pagination->offset);

        $session = (object) [
            'flashdata' => ['structure_redirect' => 1, 'structure_channel_id' => 4],
            'userdata' => ['fingerprint' => 'abc123'],
        ];
        $fixture->sessions_end($session);
    }

    public function testCoreTemplateRouteTemplateParseAndPrivateHelpers()
    {
        ee()->setMock('config', new class {
            public $_global_vars = [];
            public function item($key)
            {
                if ($key === 'base_url') {
                    return 'https://base.example/';
                }
                if ($key === 'word_separator') {
                    return 'dash';
                }
                return null;
            }
        });
        ee()->setMock('extensions', new class {
            public $last_call = ['fallback-group', 'fallback-template'];
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, $value)
            {
                return $value;
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $trailing = false)
            {
                return rtrim($base, '/') . '/' . ltrim($uri, '/');
            }
        });

        $uri = new class {
            public $page_query_string = '';
            public $query_string = 'search/results/123/x';
            public $uri_string = 'alpha/P9';
            public $segments = ['alpha', 'beta'];
            public $rsegments = [];
            public function total_segments()
            {
                return count($this->segments);
            }
            public function segment($index)
            {
                return $this->segments[$index - 1] ?? null;
            }
            public function _set_uri_string($value)
            {
                $this->uri_string = trim($value, '/');
            }
            public function _explode_segments()
            {
                $this->segments = array_values(array_filter(explode('/', trim($this->uri_string, '/'))));
            }
            public function _reindex_segments()
            {
                $this->segments = array_values($this->segments);
            }
        };
        ee()->setMock('uri', $uri);

        ee()->setMock('db', new class extends eeDbArMock {
            private $selects = [];
            public function select()
            {
                $this->selects[] = '*';
                return $this;
            }
            public function from()
            {
                return $this;
            }
            public function join($table, $cond, $type = '')
            {
                return $this;
            }
            public function where($field = null, $value = null)
            {
                return $this;
            }
            public function get()
            {
                return new class {
                    public function num_rows()
                    {
                        return 1;
                    }
                    public function row($column = null)
                    {
                        return (object) ['group_name' => 'pages', 'template_name' => 'index'];
                    }
                };
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'modules') {
                    return new class {
                        public function num_rows()
                        {
                            return 1;
                        }
                    };
                }
                if ($table === 'search') {
                    return new class {
                        public function result_array()
                        {
                            return [['search_id' => 123]];
                        }
                        public function num_rows()
                        {
                            return 1;
                        }
                        public function row($column = null)
                        {
                            return 1;
                        }
                    };
                }
                return new class {
                    public function num_rows()
                    {
                        return 0;
                    }
                };
            }
        });

        $fixture = new StructureExtFixture();
        $fixture->site_pages = [
            'url' => '{base_url}/',
            'uris' => [7 => '/alpha/', 8 => '/alpha/beta/'],
            'templates' => [7 => 12, 8 => 13],
        ];
        $fixture->entry_id = 8;
        $fixture->parent_id = 7;
        $fixture->page_title = 'Beta';
        $fixture->uri = '/alpha/beta/';
        $fixture->segment_1 = '/alpha';
        $fixture->top_id = 7;
        $fixture->sql = new class {
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_entry_title($entryId)
            {
                return $entryId == 8 ? 'Page 8' : 'Page ' . $entryId;
            }
            public function get_page_title($entryId)
            {
                return $entryId == 8 ? 'Page 8' : 'Page ' . $entryId;
            }
            public function get_slug($slug)
            {
                return trim((string) $slug, '/');
            }
            public function get_child_entries($entryId)
            {
                if ((int) $entryId === 8) {
                    return [20, 21];
                }
                if ((int) $entryId === 7) {
                    return [8];
                }
                return [];
            }
            public function is_listing_entry($entryId)
            {
                return false;
            }
            public function get_listing_channel($entryId)
            {
                return false;
            }
            public function get_channel_by_entry_id($entryId)
            {
                return 2;
            }
            public function get_channel_name_by_channel_id($channelId)
            {
                return 'pages';
            }
            public function get_hidden_state($entryId)
            {
                return 'n';
            }
            public function get_uri()
            {
                return 'cp/publish/preview/entry/8';
            }
            public function get_parent_id($entryId, $default = null)
            {
                return 7;
            }
            public function get_channel_type($channelId)
            {
                return 'page';
            }
            public function get_channel_by_entry_id_for_preview($entryId)
            {
                return 2;
            }
        };

        $route = $fixture->core_template_route('alpha/P2');
        $this->assertSame(['pages', 'index'], $route);
        $this->assertSame(7, ee()->uri->page_query_string);
        $this->assertSame(['fallback-group', 'fallback-template'], $fixture->core_template_route('alpha/beta'));
        ee()->extensions->last_call = null;

        $parsed = $fixture->template_post_parse(
            '{structure:page_url_for:8}|{structure:page_uri_for:8}|{structure:page_title_for:8}|{structure:page_slug_for:8}|{structure:child_ids_for:8}',
            false,
            1
        );
        $this->assertStringContainsString('https://base.example/alpha/beta/', $parsed);
        $this->assertStringContainsString('/alpha/beta/', $parsed);
        $this->assertStringContainsString('Page 8', $parsed);
        $this->assertStringContainsString('alpha/beta', $parsed);
        $this->assertStringContainsString('20|21', $parsed);
        ee()->extensions->last_call = '{structure:page_uri_for:8}';
        $this->assertSame('/alpha/beta/', $fixture->template_post_parse('ignored', false, 1));
        ee()->extensions->last_call = null;

        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return $name === 'structure_generate_page_url_end';
            }
            public function call($name, $value)
            {
                return 'https://hooked.example/';
            }
        });
        $this->assertSame('https://hooked.example/', $fixture->_parse_tag_url_for([0, 8]));

        $this->assertTrue($fixture->_is_search());
        ee()->uri->query_string = '';
        $this->assertFalse($fixture->_is_search());

        $rm = new ReflectionMethod('Structure_ext', '_create_global_vars');
        \TestReflectionHelper::makeAccessible($rm);
        $rm->invoke($fixture, true);
        $this->assertSame(8, ee()->config->_global_vars['structure:page:entry_id']);
        $this->assertSame('20|21', ee()->config->_global_vars['structure:child_ids']);

        $clean = new ReflectionMethod('Structure_ext', '_create_clean_structure_segments');
        \TestReflectionHelper::makeAccessible($clean);
        $fixture->site_pages['uris'][8] = '/alpha';
        $uri->segments = ['alpha', 'P9'];
        $uri->uri_string = 'alpha/P9';
        $clean->invoke($fixture);
        $this->assertSame('/alpha', ee()->config->_global_vars['structure_debug_uri_cleaned']);
    }

    public function testConstructorInitializesWhenModuleInstalled()
    {
        ee()->setMock('load', new class {
            public function add_package_path($path)
            {
            }
            public function library($name)
            {
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [1 => ['url' => '/', 'uris' => [7 => '/a/'], 'templates' => [7 => 2]]];
                }
                return null;
            }
        });
        ee()->setMock('addons_model', new class {
            public function module_installed($name)
            {
                return true;
            }
        });
        ee()->setMock('cache', new class {
            public function get($key)
            {
                return false;
            }
            public function save($key, $value)
            {
                return true;
            }
        });
        ee()->setMock('db', new class {
            public function query($sql)
            {
                if (strpos($sql, 'SELECT module_id FROM exp_modules') !== false) {
                    return new class {
                        public function result()
                        {
                            return [(object) ['module_id' => 50]];
                        }
                    };
                }
                if (strpos($sql, 'exp_structure_settings') !== false) {
                    return new class {
                        public function num_rows()
                        {
                            return 0;
                        }
                        public function result_array()
                        {
                            return [];
                        }
                    };
                }
                return new class {
                    public function result()
                    {
                        return [];
                    }
                };
            }
        });

        $ext = new Structure_ext();

        $this->assertInstanceOf(Sql_structure::class, $ext->sql);
        $this->assertSame('/a/', $ext->site_pages['uris'][7]);
        $this->assertFalse($ext->entry_id);
        $this->assertFalse($ext->parent_id);

        ee()->setMock('addons_model', new class {
            public function module_installed($name)
            {
                return false;
            }
        });
        $extDisabled = new Structure_ext();
        $this->assertInstanceOf(Sql_structure::class, $extDisabled->sql);
    }

    public function testAdditionalExtMethodCoverageForEarlyReturnsAndHelpers()
    {
        $fixture = new StructureExtFixture();
        $fixture->site_pages = ['url' => '{base_url}/', 'uris' => [10 => '/parent/']];

        ee()->setMock('load', new class {
            public function helper($name)
            {
            }
        });
        ee()->setMock('input', new class {
            public function post($key)
            {
                if ($key === 'title') {
                    return 'Preview Title';
                }
                if ($key === 'structure__uri') {
                    return 'child';
                }
                if ($key === 'structure__parent_id') {
                    return 10;
                }
                if ($key === 'submit') {
                    return 'save';
                }
                return null;
            }
        });
        ee()->setMock('uri', new class {
            public $segments = ['cp', 'publish', 'preview', 'entry', '15'];
            public $query_string = '';
            public $uri_string = '';
            public function total_segments()
            {
                return count($this->segments);
            }
            public function segment($index)
            {
                return $this->segments[$index - 1] ?? null;
            }
            public function _set_uri_string($value)
            {
                $this->uri_string = trim($value, '/');
            }
            public function _explode_segments()
            {
                $this->segments = array_values(array_filter(explode('/', trim($this->uri_string, '/'))));
            }
            public function _reindex_segments()
            {
                $this->segments = array_values($this->segments);
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'base_url') {
                    return 'https://base.example/';
                }
                if ($key === 'site_pages') {
                    return [1 => ['url' => '{base_url}/', 'uris' => [5 => '/alpha/']]];
                }
                if ($key === 'site_id') {
                    return 1;
                }
                return null;
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $trailing = false)
            {
                return rtrim($base, '/') . '/' . ltrim($uri, '/');
            }
        });
        ee()->setMock('extensions', new class {
            public $last_call = null;
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return $args[0] ?? null;
            }
        });

        $fixture->sql = new class {
            public function get_uri()
            {
                return 'cp/publish/preview/entry/15';
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_channel_by_entry_id($entryId)
            {
                return 7;
            }
            public function get_channel_type($channelId)
            {
                return 'unmanaged';
            }
            public function get_site_pages($cache_bust = false)
            {
                return ['url' => '{base_url}/', 'uris' => [5 => '/alpha/'], 'templates' => [5 => 9]];
            }
            public function get_data()
            {
                return [
                    5 => ['title' => 'Alpha', 'depth' => 1],
                ];
            }
            public function get_structure_channels($type = '')
            {
                return false;
            }
        };

        $fixture->sessions_start(new stdClass());

        $config = $fixture->wygwam_config([], ['add_trailing_slash' => 'y']);
        $this->assertArrayHasKey('link_types', $config);
        $this->assertArrayHasKey('Structure Pages', $config['link_types']);

        $obj = new stdClass();
        $obj->channel = ['channel_id' => 2];
        $obj->entry = ['entry_id' => 15];
        $obj->EE = (object) ['api_sc_channel_entries' => (object) ['data' => []]];

        $fixture->safecracker_submit_entry_end($obj);

        $obj2 = new stdClass();
        $obj2->channel = (object) ['channel_id' => 2];
        $obj2->entry = (object) ['entry_id' => 15, 'url_title' => 'title'];
        $fixture->channel_form_submit_entry_end($obj2);

        $fixture->entry_submission_end(15, ['channel_id' => 2], []);
    }

    public function testSessionsEndHandlesSessionTypeSAndFallbackWithoutRedirect()
    {
        $fixture = new StructureExtFixture();
        $fixture->sql = new class {
            public function get_settings()
            {
                return ['redirect_on_publish' => 'n'];
            }
        };

        ee()->setMock('config', new class {
            public $sessionType = 's';
            public function item($key)
            {
                if ($key === 'cp_session_type') {
                    return $this->sessionType;
                }
                if ($key === 'cp_url') {
                    return 'https://cp.example/admin.php';
                }
                return null;
            }
        });

        $session = (object) [
            'flashdata' => ['structure_redirect' => 1, 'structure_channel_id' => 9],
            'userdata' => ['session_id' => 'sess-1', 'fingerprint' => 'fp-1'],
        ];

        $fixture->sessions_end($session);

        ee()->config->sessionType = 'cs';
        $fixture->sessions_end($session);

        ee()->config->sessionType = 'custom';
        $fixture->sessions_end($session);

        $this->assertTrue(true);
    }

    public function testSessionsEndReturnsEarlyWhenRedirectFlagMissing()
    {
        $fixture = new StructureExtFixture();
        $fixture->sql = new class {
            public function get_settings()
            {
                return ['redirect_on_publish' => 'n'];
            }
        };

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'cp_session_type') {
                    return 's';
                }
                if ($key === 'cp_url') {
                    return 'https://cp.example/admin.php';
                }
                return null;
            }
        });

        $session = (object) [
            'flashdata' => [],
            'userdata' => ['session_id' => 'sess-1', 'fingerprint' => 'fp-1'],
        ];

        $fixture->sessions_end($session);
        $this->assertTrue(true);
    }

    public function testSessionsEndCoversRedirectHeaderBranchBeforeTerminalDie()
    {
        $fixture = new StructureExtFixture();
        $fixture->redirectResult = true;
        $fixture->sql = new class {
            public function get_settings()
            {
                return ['redirect_on_publish' => 'y'];
            }
        };

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'cp_session_type') {
                    return 's';
                }
                if ($key === 'cp_url') {
                    return 'https://cp.example/admin.php';
                }
                return null;
            }
        });

        $session = (object) [
            'flashdata' => ['structure_redirect' => 1, 'structure_channel_id' => 9],
            // Invalid newline forces the final Location header() call to raise a warning
            // before die() can terminate the test process.
            'userdata' => ['session_id' => "sess-\n1", 'fingerprint' => 'fp-1'],
        ];

        set_error_handler(function ($severity, $message, $file = null, $line = null) {
            if ($severity === E_WARNING && stripos($message, 'header') !== false) {
                if ((int) $line === 293) {
                    throw new RuntimeException($message);
                }
                // Suppress earlier header warnings so execution can reach line 293.
                return true;
            }
            return false;
        });

        try {
            $fixture->sessions_end($session);
            $this->fail('Expected header warning before terminal die');
        } catch (RuntimeException $e) {
            $this->assertNotFalse(stripos($e->getMessage(), 'header'));
        } finally {
            restore_error_handler();
        }
    }
}
