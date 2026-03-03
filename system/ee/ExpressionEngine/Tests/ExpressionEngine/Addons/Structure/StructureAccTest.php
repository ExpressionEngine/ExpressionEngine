<?php

require_once __DIR__ . '/StructureTestBase.php';
require_once __DIR__ . '/../../../../Addons/structure/acc.structure.php';

if (!defined('URL_THEMES')) {
    define('URL_THEMES', 'https://themes.example/');
}

class StructureAccTest extends StructureTestBase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        ee()->setMock('load', new class {
            public function library($name) {}
        });
    }

    public function testSetSectionsShowsNotInstalledMessage()
    {
        ee()->setMock('db', new class {
            public function query($sql)
            {
                return new class {
                    public $num_rows = 0;
                };
            }
        });

        $acc = new Structure_acc();
        $acc->set_sections();

        $this->assertSame('Structure is not installed.', $acc->sections['Not Installed']);
    }

    public function testGetAssetsNormalizesAssetDataAndReturnsView()
    {
        $acc = (new ReflectionClass(Structure_acc::class))->newInstanceWithoutConstructor();
        $acc->installed = true;
        $acc->structure = new class {
            public function get_structure_channels($type)
            {
                return false;
            }
        };

        $captured = [];
        ee()->setMock('general_helper', new class($captured) {
            private $captured;
            public function __construct(&$captured) { $this->captured = &$captured; }
            public function view($view, $data, $return = false)
            {
                $this->captured = [$view, $data, $return];
                return 'accessory-view';
            }
        });

        $result = $acc->get_assets();

        $this->assertSame('accessory-view', $result);
        $this->assertSame('accessory', $captured[0]);
        $this->assertSame([], $captured[1]['asset_data']);
        $this->assertSame(URL_THEMES, $captured[1]['theme_url']);
        $this->assertTrue($captured[2]);
    }

    public function testSetSectionsUsesAssetsWhenInstalled()
    {
        $acc = (new ReflectionClass(Structure_acc::class))->newInstanceWithoutConstructor();
        $acc->installed = true;
        $acc->structure = new class {
            public function get_structure_channels($type)
            {
                return [];
            }
        };

        ee()->setMock('general_helper', new class {
            public function view($view, $data, $return = false)
            {
                return 'assets-body';
            }
        });

        $acc->set_sections();

        $this->assertSame('assets-body', $acc->sections['Assets']);
    }

    public function testConstructorUsesCachedModuleQueryWhenPresent()
    {
        ee()->setMock('db', new class {
            public function query($sql)
            {
                throw new RuntimeException('db->query should not be called when cache exists');
            }
        });

        $acc = (new ReflectionClass(Structure_acc::class))->newInstanceWithoutConstructor();
        $acc->cache = ['module_id_query' => (object) ['num_rows' => 0]];
        $acc->__construct();

        $this->assertFalse($acc->installed);
        $this->assertNull($acc->structure);
    }

    public function testConstructorMarksInstalledAndBuildsStructureWhenModuleExists()
    {
        ee()->setMock('uri', (object) ['page_query_string' => '', 'query_string' => '']);
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
                if ($key === 'site_pages') {
                    return [1 => ['url' => '/', 'uris' => [1 => '/'], 'templates' => [1 => 1]]];
                }
                if ($key === 'reserved_category_word') {
                    return 'category';
                }
                if ($key === 'use_category_name') {
                    return 'n';
                }
                return null;
            }
        });
        ee()->setMock('load', new class {
            public function add_package_path($path)
            {
            }
            public function library($name)
            {
            }
            public function helper($name)
            {
            }
        });
        ee()->setMock('addons_model', new class {
            public function module_installed($name)
            {
                return true;
            }
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

        $acc = (new ReflectionClass(Structure_acc::class))->newInstanceWithoutConstructor();
        $acc->cache = ['module_id_query' => (object) ['num_rows' => 1]];
        $acc->__construct();

        $this->assertTrue($acc->installed);
        $this->assertInstanceOf(Structure::class, $acc->structure);
    }
}
