<?php

require_once __DIR__ . '/PagesTestBase.php';

if (!function_exists('form_open')) {
    function form_open($action, $attrs = '', $hidden = [])
    {
        return '<form action="' . $action . '">';
    }
}
if (!function_exists('form_close')) {
    function form_close()
    {
        return '</form>';
    }
}
if (!function_exists('form_hidden')) {
    function form_hidden($name, $value = '')
    {
        return '<input type="hidden" name="' . $name . '" value="' . $value . '">';
    }
}
if (!function_exists('form_submit')) {
    function form_submit($data = [])
    {
        $value = is_array($data) && isset($data['value']) ? $data['value'] : 'submit';
        return '<button>' . $value . '</button>';
    }
}
if (!function_exists('form_dropdown')) {
    function form_dropdown($name, $options = [], $value = '', $extra = '')
    {
        return '<select name="' . $name . '"></select>';
    }
}
if (!function_exists('form_label')) {
    function form_label($label, $id = '')
    {
        return '<label for="' . $id . '">' . $label . '</label>';
    }
}

if (!class_exists('PagesViewPageNodeStub')) {
    class PagesViewPageNodeStub
    {
        public $id;
        public $title;
        public $uri;
        private $children;
        public function __construct(int $id, string $title, string $uri, array $children = [])
        {
            $this->id = $id;
            $this->title = $title;
            $this->uri = $uri;
            $this->children = $children;
        }
        public function children(): array
        {
            return $this->children;
        }
    }
}

if (!class_exists('PagesViewTreeStub')) {
    class PagesViewTreeStub
    {
        private $children;
        public function __construct(array $children)
        {
            $this->children = $children;
        }
        public function children(): array
        {
            return $this->children;
        }
    }
}

if (!class_exists('PagesViewRendererStub')) {
    class PagesViewRendererStub
    {
        public $embedded = [];
        public $modals = [];
        public $table;

        public function __construct()
        {
            $this->table = new class {
                public $template;
                public $headings = [];
                public $rows = [];
                public function set_template($template)
                {
                    $this->template = $template;
                }
                public function set_heading(...$headings)
                {
                    $this->headings = $headings;
                }
                public function add_row(...$row)
                {
                    $this->rows[] = $row;
                }
                public function generate()
                {
                    return '<table>generated</table>';
                }
            };
        }

        public function embed($name, $vars = [])
        {
            $this->embedded[] = [$name, $vars];
            return '';
        }

        public function make($name)
        {
            return new class($this) {
                private $renderer;
                public function __construct($renderer)
                {
                    $this->renderer = $renderer;
                }
                public function render($vars = [])
                {
                    $this->renderer->modals[] = $vars;
                    return '<div>modal</div>';
                }
            };
        }

        public function renderFile(string $file, array $vars = []): string
        {
            extract($vars, EXTR_SKIP);
            ob_start();
            include $file;
            return (string) ob_get_clean();
        }
    }
}

class PagesViewsTest extends PagesTestBase
{
    public function testAddonSetupReturnsExpectedMetadataArray(): void
    {
        $setup = require __DIR__ . '/../../../../Addons/pages/addon.setup.php';
        $this->assertSame('Pages', $setup['name']);
        $this->assertSame('2.2.0', $setup['version']);
        $this->assertTrue($setup['settings_exist']);
    }

    public function testDeleteConfirmViewRendersHiddenInputsAndSubmitButton(): void
    {
        $renderer = new PagesViewRendererStub();
        $file = __DIR__ . '/../../../../Addons/pages/views/delete_confirm.php';
        $output = $renderer->renderFile($file, [
            'form_hidden' => ['foo' => 'bar'],
            'damned' => [10, 11],
        ]);

        $this->assertStringContainsString('delete[]', $output);
        $this->assertStringContainsString('pages_delete_question', $output);
        $this->assertStringContainsString('action_can_not_be_undone', $output);
    }

    public function testConfigurationViewRendersRowsFromConfigurationFields(): void
    {
        $renderer = new PagesViewRendererStub();
        $file = __DIR__ . '/../../../../Addons/pages/views/configuration.php';
        $output = $renderer->renderFile($file, [
            'cp_pad_table_template' => ['table_open' => '<table>'],
            'configuration_fields' => [
                [
                    'label' => 'Homepage',
                    'field_name' => 'homepage_display',
                    'options' => ['nested' => 'Nested'],
                    'value' => 'nested',
                ],
                [
                    'label' => 'Default Channel',
                    'field_name' => 'default_channel',
                    'options' => [0 => 'None', 2 => 'Pages'],
                    'value' => 2,
                ],
            ],
        ]);

        $this->assertStringContainsString('<table>generated</table>', $output);
        $this->assertCount(2, $renderer->table->rows);
    }

    public function testPagePartialRendersWithAndWithoutChildren(): void
    {
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return 'cp://' . $path;
            }
        });

        $renderer = new PagesViewRendererStub();
        $file = __DIR__ . '/../../../../Addons/pages/views/_page.php';

        $parent = new PagesViewPageNodeStub(
            1,
            'Parent',
            '/parent',
            [new PagesViewPageNodeStub(2, 'Child', '/parent/child')]
        );
        $withChildren = $renderer->renderFile($file, ['page' => $parent]);
        $this->assertStringContainsString('#1', $withChildren);
        $this->assertNotEmpty($renderer->embedded);

        $rendererNoChild = new PagesViewRendererStub();
        $leaf = new PagesViewPageNodeStub(3, 'Leaf', '/leaf', []);
        $withoutChildren = $rendererNoChild->renderFile($file, ['page' => $leaf]);
        $this->assertStringContainsString('#3', $withoutChildren);
        $this->assertSame([], $rendererNoChild->embedded);
    }

    public function testIndexViewRendersWithRowsAndBulkActions(): void
    {
        ee()->setMock('menu', new class {
            public function generate_menu()
            {
                return ['channels' => ['create' => ['Pages' => 'cp://create/pages']]];
            }
        });
        ee()->setMock('CP/Alert', new class {
            public function get($name)
            {
                return '<div>alert</div>';
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return 'cp://' . $path;
            }
        });
        $captured = (object) ['modals' => []];
        ee()->setMock('CP/Modal', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function addModal($name, $modal)
            {
                $this->captured->modals[] = [$name, $modal];
            }
        });

        $renderer = new PagesViewRendererStub();
        $file = __DIR__ . '/../../../../Addons/pages/views/index.php';
        $output = $renderer->renderFile($file, [
            'base_url' => 'cp://addons/settings/pages',
            'pagination' => '<div>pagination</div>',
            'table' => [
                'columns' => ['name', 'url'],
                'data' => [['name' => 'Entry']],
            ],
        ]);

        $this->assertStringContainsString('all_pages', $output);
        $this->assertStringContainsString('pagination', $output);
        $this->assertNotEmpty($renderer->embedded);
        $this->assertNotEmpty($captured->modals);
    }

    public function testIndexViewRendersWithoutBulkActionsWhenNoRows(): void
    {
        ee()->setMock('menu', new class {
            public function generate_menu()
            {
                return ['channels' => ['create' => ['Pages' => 'cp://create/pages']]];
            }
        });
        ee()->setMock('CP/Alert', new class {
            public function get($name)
            {
                return '<div>alert</div>';
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return 'cp://' . $path;
            }
        });
        ee()->setMock('CP/Modal', new class {
            public function addModal($name, $modal)
            {
            }
        });

        $renderer = new PagesViewRendererStub();
        $file = __DIR__ . '/../../../../Addons/pages/views/index.php';
        $renderer->renderFile($file, [
            'base_url' => 'cp://addons/settings/pages',
            'pagination' => '',
            'table' => [
                'columns' => [],
                'data' => [],
            ],
        ]);

        $embedNames = array_map(function ($item) {
            return $item[0];
        }, $renderer->embedded);
        $this->assertContains('ee:_shared/table', $embedNames);
        $this->assertNotContains('ee:_shared/form/bulk-action-bar', $embedNames);
    }

    public function testNestedViewRendersWithChildrenAndWithoutChildren(): void
    {
        ee()->setMock('menu', new class {
            public function generate_menu()
            {
                return ['channels' => ['create' => ['Pages' => 'cp://create/pages']]];
            }
        });
        ee()->setMock('CP/Alert', new class {
            public function getAllInlines()
            {
                return '<div>alerts</div>';
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return 'cp://' . $path;
            }
        });
        $captured = (object) ['modals' => []];
        ee()->setMock('CP/Modal', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function addModal($name, $modal)
            {
                $this->captured->modals[] = [$name, $modal];
            }
        });

        $renderer = new PagesViewRendererStub();
        $file = __DIR__ . '/../../../../Addons/pages/views/nested.php';

        $treeWithChildren = new PagesViewTreeStub([
            new PagesViewPageNodeStub(1, 'Parent', '/parent'),
        ]);
        $withChildren = $renderer->renderFile($file, [
            'base_url' => 'cp://addons/settings/pages',
            'pages' => $treeWithChildren,
        ]);
        $this->assertStringContainsString('all_pages', $withChildren);
        $this->assertNotEmpty($renderer->embedded);

        $rendererNoChildren = new PagesViewRendererStub();
        $treeWithoutChildren = new PagesViewTreeStub([]);
        $withoutChildren = $rendererNoChildren->renderFile($file, [
            'base_url' => 'cp://addons/settings/pages',
            'pages' => $treeWithoutChildren,
        ]);
        $this->assertStringContainsString('no_found', $withoutChildren);
        $this->assertNotEmpty($captured->modals);
    }
}
