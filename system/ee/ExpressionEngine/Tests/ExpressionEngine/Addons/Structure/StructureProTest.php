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
if (!function_exists('lang')) {
    function lang($key)
    {
        return $key;
    }
}
if (!class_exists('ExpressionEngine\\Addons\\Pro\\Service\\Prolet\\AbstractProlet')) {
    eval(<<<'PHPSTUB'
namespace ExpressionEngine\Addons\Pro\Service\Prolet;
interface ProletInterface {}
abstract class AbstractProlet {}
PHPSTUB
    );
}

require_once __DIR__ . '/../../../../Addons/structure/pro.structure.php';
require_once __DIR__ . '/../../../../Addons/structure/Conduit/StaticCache.php';

use PHPUnit\Framework\TestCase;

class StructureProTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIndexRendersProletView()
    {
        ee()->resetMocks();

        ee()->setMock('uri', new class {
            public $page_query_string = '';
            public $query_string = '';
            public function uri_string()
            {
                return '';
            }
        });
        ee()->setMock('TMPL', new class {
            public $tagproper = 'channel:entries';
            public $tagparams = [];
            public function fetch_param($key, $default = false)
            {
                return $default;
            }
        });
        ee()->setMock('pagination', new class {
            public function create()
            {
                return new class {
                    public function prepare($tagdata)
                    {
                        return $tagdata;
                    }
                };
            }
        });
        ee()->setMock('load', new class {
            public function add_package_path($path)
            {
            }
            public function helper($name)
            {
            }
            public function library($name)
            {
                if ($name === 'pagination') {
                    ee()->setMock('pagination', new class {
                        public function create()
                        {
                            return new class {
                                public function prepare($tagdata)
                                {
                                    return $tagdata;
                                }
                            };
                        }
                    });
                }
                if ($name === 'sql_helper') {
                    ee()->setMock('sql_helper', new class {
                        public function row($sql)
                        {
                            if (strpos($sql, 'SELECT site_pages FROM exp_sites') !== false) {
                                $payload = [1 => ['url' => 'https://example.test/', 'uris' => [2 => '/about/'], 'templates' => [2 => 5]]];
                                return ['site_pages' => base64_encode(serialize($payload))];
                            }
                            return [];
                        }
                    });
                }
                if ($name === 'general_helper') {
                    ee()->setMock('general_helper', new class {
                        public function cpURL($section, $method = '', $params = [])
                        {
                            return 'cp://' . $section . '/' . $method;
                        }
                    });
                }
            }
        });
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'theme_folder_url') {
                    return 'https://themes.example/';
                }
                if ($key === 'reserved_category_word') {
                    return 'category';
                }
                if ($key === 'use_category_name') {
                    return 'n';
                }
                if ($key === 'site_pages') {
                    return [1 => ['url' => 'https://example.test/', 'uris' => [2 => '/about/'], 'templates' => [2 => 5]]];
                }
                return null;
            }
            public function slash_item($key)
            {
                return 'https://themes.example/';
            }
        });
        ee()->setMock('cp', new class {
            public $head = [];
            public $foot = [];
            public $js = [];
            public function add_to_head($html)
            {
                $this->head[] = $html;
            }
            public function add_to_foot($html)
            {
                $this->foot[] = $html;
            }
            public function load_package_js($name)
            {
                $this->js[] = $name;
            }
        });
        ee()->setMock('session', new class {
            public $userdata = [
                'group_id' => 1,
                'assigned_channels' => [2 => ['channel_id' => 2]],
            ];
            public function userdata($key)
            {
                if ($key === 'assigned_channels') {
                    return [2 => ['channel_id' => 2]];
                }
                if ($key === 'member_id') {
                    return 10;
                }
                if ($key === 'group_id') {
                    return 1;
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
        ee()->setMock('functions', new class {
            public function fetch_assigned_channels()
            {
                return [];
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path, $params = [])
            {
                return 'cp://' . $path;
            }
        });
        ee()->setMock('View', new class {
            public function make($view)
            {
                return new class($view) {
                    private $view;
                    public function __construct($view)
                    {
                        $this->view = $view;
                    }
                    public function render($data)
                    {
                        return 'rendered:' . $this->view;
                    }
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
                                    if ($field === 'channel_id') {
                                        return [2];
                                    }
                                    return [];
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
        ee()->setMock('db', new class {
            public function query($sql)
            {
                if (strpos($sql, 'FROM exp_structure_settings') !== false) {
                    return new class {
                        public function num_rows()
                        {
                            return 3;
                        }
                        public function result_array()
                        {
                            return [
                                ['var' => 'show_global_add_page', 'var_value' => 'y'],
                                ['var' => 'show_picker', 'var_value' => 'y'],
                                ['var' => 'add_trailing_slash', 'var_value' => 'y'],
                            ];
                        }
                    };
                }
                if (strpos($sql, 'FROM exp_structure AS node') !== false) {
                    return new class {
                        public function num_rows()
                        {
                            return 1;
                        }
                        public function result_array()
                        {
                            return [[
                                'entry_id' => 2,
                                'title' => 'About',
                                'status' => 'open',
                                'depth' => 1,
                                'parent_id' => 0,
                            ]];
                        }
                    };
                }
                if (strpos($sql, 'FROM exp_channels AS ec') !== false) {
                    return new class {
                        public $num_rows = 1;
                        public function num_rows()
                        {
                            return 1;
                        }
                        public function result_array()
                        {
                            return [[
                                'channel_id' => 2,
                                'channel_title' => 'Pages',
                                'site_id' => 1,
                                'template_id' => 5,
                                'type' => 'page',
                                'split_assets' => 'n',
                                'show_in_page_selector' => 'y',
                            ]];
                        }
                    };
                }
                if (strpos($sql, 'SELECT status, highlight FROM exp_statuses') !== false) {
                    return new class {
                        public function num_rows()
                        {
                            return 1;
                        }
                        public function result_array()
                        {
                            return [['status' => 'open', 'highlight' => '#fff']];
                        }
                    };
                }
                if (strpos($sql, 'SELECT entry_id, listing_cid') !== false) {
                    return new class {
                        public function result_array()
                        {
                            return [];
                        }
                    };
                }
                return new class {
                    public $num_rows = 0;
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
                };
            }
            public function count_all($table)
            {
                if ($table === 'structure') {
                    return 2;
                }
                return 0;
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
                if ($table === 'structure_members') {
                    return new class {
                        public $num_rows = 0;
                        public function row_array()
                        {
                            return [];
                        }
                    };
                }
                return new class {
                    public $num_rows = 0;
                    public function row()
                    {
                        return (object) [];
                    }
                    public function row_array()
                    {
                        return [];
                    }
                };
            }
            public function select($fields = '*')
            {
                return $this;
            }
        });

        $pro = new Structure_pro();
        $output = $pro->index();

        $this->assertSame('rendered:structure:index', $output);
        $this->assertNotEmpty(ee()->cp->head);
        $this->assertNotEmpty(ee()->cp->foot);
        $this->assertNotEmpty(ee()->cp->js);
    }
}
