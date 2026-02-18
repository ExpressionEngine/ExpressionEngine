<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';

if (!class_exists('EE_Fieldtype')) {
    class EE_Fieldtype
    {
        public $settings = [];
        public $field_name;
        public $field_id;
        public $cell_name;
        public $row;
        public $id;
        public $name;
        public $content_id;
        public $content_type;

        public function __construct()
        {
        }

        public function _init($config = [])
        {
            $config = (array) $config;

            if (isset($config['field_id']) && !isset($config['id'])) {
                $config['id'] = $config['field_id'];
            }
            if (isset($config['field_name']) && !isset($config['name'])) {
                $config['name'] = $config['field_name'];
            }

            foreach ($config as $key => $value) {
                $this->{$key} = $value;
            }

            $this->field_id = $this->id ?? $this->field_id;
            $this->field_name = $this->name ?? $this->field_name;

            if (!array_key_exists('content_id', $config)) {
                $this->content_id = null;
            }
        }

        public function name()
        {
            return $this->field_name ?? '';
        }

        public function id()
        {
            return $this->id;
        }

        public function isNew()
        {
            return is_null($this->id);
        }

        public function content_id()
        {
            return $this->content_id;
        }

        public function content_type()
        {
            return $this->content_type ?? 'channel';
        }

        public function row($key, $default = null)
        {
            if (!isset($this->row)) {
                return $default;
            }

            if (is_array($this->row)) {
                return array_key_exists($key, $this->row) ? $this->row[$key] : $default;
            }

            if (is_object($this->row)) {
                return isset($this->row->{$key}) ? $this->row->{$key} : $default;
            }

            return $default;
        }
    }
}

require_once __DIR__ . '/../../../../../Addons/structure/ft.structure.php';

use PHPUnit\Framework\TestCase;

if (!function_exists('form_dropdown')) {
    function form_dropdown($name, $options, $selected = null)
    {
        $html = '<select name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">';
        foreach ((array) $options as $value => $label) {
            $isSelected = ((string) $value === (string) $selected) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"' . $isSelected . '>'
                . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        $html .= '</select>';

        return $html;
    }
}

class StructureFtSqlStub
{
    public function get_structure_channels($type)
    {
        if ($type === 'listing') {
            return [
                10 => ['channel_title' => 'Products', 'template_id' => 2],
            ];
        }

        return [];
    }

    public function get_listing_channel_data($channelId)
    {
        return [
            ['entry_id' => 1, 'title' => 'One', 'status' => 'open', 'depth' => 0],
            ['entry_id' => 2, 'title' => 'Two', 'status' => 'closed', 'parent_id' => 1, 'depth' => 1],
            ['entry_id' => 3, 'title' => 'Three', 'status' => 'open', 'parent_id' => 2, 'depth' => 2],
        ];
    }

    public function get_data()
    {
        return [
            ['entry_id' => 11, 'title' => 'A', 'status' => 'open', 'depth' => 0],
            ['entry_id' => 12, 'title' => 'B', 'status' => 'open', 'depth' => 1],
        ];
    }
}

class StructureFtTest extends TestCase
{
    private function makeFt()
    {
        $ft = (new ReflectionClass(Structure_ft::class))->newInstanceWithoutConstructor();
        $ft->sql = new StructureFtSqlStub();
        $ft->site_pages = [
            'url' => 'https://example.com/',
            'uris' => [5 => '/products/item'],
        ];
        $ft->field_name = 'my_field';
        $ft->field_id = 99;
        $ft->cell_name = 'cell_name';
        $ft->settings = ['structure_list_type' => 10];

        return $ft;
    }

    protected function setUp(): void
    {
        ee()->resetMocks();
        ee()->setMock('input', new class {
            public function post($name)
            {
                return 'pages';
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $flag = false)
            {
                return '{base_url}/' . ltrim($uri, '/');
            }
            public function fetch_site_index($a = 0, $b = 0)
            {
                return '/index.php/';
            }
        });
        ee()->setMock('config', new class {
            public $items = ['base_url' => 'https://example.com/'];
            public function item($name)
            {
                return $this->items[$name] ?? null;
            }
        });
        ee()->setMock('extensions', new class {
            public $active = false;
            public function active_hook($name) { return $this->active; }
            public function call($name, $url) { return 'https://override.test/path'; }
        });
    }

    public function testSimpleMethods()
    {
        $ft = $this->makeFt();

        $this->assertSame(['structure_list_type' => 'pages'], $ft->install());
        $this->assertTrue($ft->update('1.0.0'));
        $this->assertTrue($ft->accepts_content_type('channel'));
        $this->assertTrue($ft->accepts_content_type('grid'));
        $this->assertTrue($ft->accepts_content_type('blocks/1'));
        $this->assertFalse($ft->accepts_content_type('member'));
    }

    public function testDisplayAndGridMethods()
    {
        $ft = $this->makeFt();

        $field = $ft->display_field(11);
        $cell = $ft->display_cell(12);
        $gridCell = $ft->grid_display_cell(13);
        $varField = $ft->display_var_field(14);
        $gridSettings = $ft->grid_display_settings(['structure_list_type' => 'pages']);

        $this->assertStringContainsString('my_field', $field);
        $this->assertStringContainsString('cell_name', $cell);
        $this->assertStringContainsString('cell_name', $gridCell);
        $this->assertStringContainsString('my_field', $varField);
        $this->assertArrayHasKey('field_options', $gridSettings);
    }

    public function testDropdownAndSettingsHelpers()
    {
        $ft = $this->makeFt();

        $dropdown = $ft->_get_dropdown(['structure_list_type' => 10]);
        $ee3 = $ft->_get_ee3_dropdown($dropdown);
        $gridDropdown = $ft->_get_grid_dropdown([]);
        $displaySettings = $ft->display_settings(['structure_list_type' => 10]);

        $this->assertStringContainsString('Listing Channel: Products', $dropdown);
        $this->assertArrayHasKey('field_options_structure', $ee3);
        $this->assertArrayHasKey('pages', $gridDropdown);
        $this->assertArrayHasKey(10, $gridDropdown);
        $this->assertArrayHasKey('field_options_structure', $displaySettings);
    }

    public function testSaveSettingsAndReplaceMethods()
    {
        $ft = $this->makeFt();

        $this->assertSame(['structure_list_type' => 'pages'], $ft->save_settings([]));
        $this->assertSame(['structure_list_type' => 'x'], $ft->grid_save_settings(['a' => 'x']));

        $replaced = $ft->replace_tag(5);
        $this->assertSame('index.php/products/item', $replaced);
        $this->assertFalse($ft->replace_tag('not-numeric'));
    }

    public function testDisplayVarTagRespectsExtensionOverride()
    {
        $ft = $this->makeFt();

        $this->assertSame(
            'https://example.com/products/item',
            $ft->display_var_tag(5, [], '')
        );

        ee()->extensions->active = true;
        $this->assertSame('https://override.test/path', $ft->display_var_tag(5, [], ''));
    }

    public function testConstructorInitializesStateWhenModuleInstalled()
    {
        ee()->resetMocks();

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
                    return [1 => ['url' => '/', 'uris' => [10 => '/a/'], 'templates' => [10 => 2]]];
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
            private $store = [];
            public function get($key)
            {
                return $this->store[$key] ?? false;
            }
            public function save($key, $value)
            {
                $this->store[$key] = $value;
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
        });

        $ft = new Structure_ft();

        $this->assertInstanceOf(Sql_structure::class, $ft->sql);
        $this->assertSame(1, $ft->site_id);
        $this->assertSame('/a/', $ft->site_pages['uris'][10]);
    }

    public function testConstructorReturnsEarlyWhenModuleNotInstalled()
    {
        ee()->resetMocks();

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
                return null;
            }
        });
        ee()->setMock('addons_model', new class {
            public function module_installed($name)
            {
                return false;
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
                            return [];
                        }
                    };
                }
                return new class {
                    public function result()
                    {
                        return [];
                    }
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
        });

        $ft = new Structure_ft();

        $this->assertInstanceOf(Sql_structure::class, $ft->sql);
        $this->assertNull($ft->site_pages);
    }
}
