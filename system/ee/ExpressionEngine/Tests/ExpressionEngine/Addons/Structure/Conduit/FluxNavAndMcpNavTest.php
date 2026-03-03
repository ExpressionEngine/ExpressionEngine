<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once __DIR__ . '/../../../../../Addons/structure/sql.structure.php';

if (!function_exists('lang')) {
    function lang($key)
    {
        return strtoupper($key);
    }
}

require_once __DIR__ . '/../../../../../Addons/structure/Conduit/FluxNav.php';
require_once __DIR__ . '/../../../../../Addons/structure/Conduit/McpNav.php';

use ExpressionEngine\Structure\Conduit\FluxNav;
use ExpressionEngine\Structure\Conduit\McpNav;
use PHPUnit\Framework\TestCase;

class FluxNavDefaultFixture extends FluxNav
{
}

class FluxNavConfiguredFixture extends FluxNav
{
    private $fixtureItems;
    private $fixtureButtons;
    private $fixtureActiveMap;
    private $fixtureDefer;

    public function __construct(array $items, array $buttons, array $activeMap, bool $defer = false)
    {
        $this->fixtureItems = $items;
        $this->fixtureButtons = $buttons;
        $this->fixtureActiveMap = $activeMap;
        $this->fixtureDefer = $defer;

        parent::__construct();
    }

    protected function defaultItems()
    {
        return $this->fixtureItems;
    }

    protected function defaultButtons()
    {
        return $this->fixtureButtons;
    }

    protected function defaultActiveMap()
    {
        return $this->fixtureActiveMap;
    }

    public function deferGenerate()
    {
        return $this->fixtureDefer;
    }
}

class FluxNavFakeSidebar
{
    public $headers = [];

    public function addHeader($title)
    {
        $item = new FluxNavFakeSidebarItem($title);
        $this->headers[] = $item;
        return $item;
    }
}

class FluxNavFakeSidebarItem
{
    public $title;
    public $url;
    public $buttons = [];
    public $active = false;

    public function __construct($title)
    {
        $this->title = $title;
    }

    public function withUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function withButton($title, $url)
    {
        $this->buttons[] = [$title, $url];
        return $this;
    }

    public function isActive()
    {
        $this->active = true;
        return $this;
    }
}

class FluxNavAndMcpNavTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();

        ee()->setMock('uri', new class {
            public $segments = ['addons', 'settings', 'structure', 'index'];
            public function segment_array()
            {
                return $this->segments;
            }
        });
        ee()->setMock('load', new class {
            public function add_package_path($path) {}
            public function library($name) {}
            public function helper($name) {}
            public function model($name) {}
        });
        ee()->setMock('addons_model', new class {
            public function module_installed($name)
            {
                return false;
            }
        });
        ee()->setMock('view', new class {
            public $cp_page_title;
            public $header;
        });
        ee()->setMock('cp', new class {
            public $crumbs = [];
            public function set_breadcrumb($url, $title)
            {
                $this->crumbs[] = [$url, $title];
            }
        });
        ee()->setMock('CP/Sidebar', new class {
            public function make()
            {
                return new FluxNavFakeSidebar();
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return 'cp://' . $path;
            }
        });
        ee()->session->setUserdata('group_id', 1);
    }

    public function testFluxNavConstructorFormatsAndActivatesMappedItem()
    {
        ee()->uri->segments = ['addons', 'settings', 'structure', 'alias'];

        $nav = new FluxNavConfiguredFixture(
            ['/' => 'Pages', 'validation' => 'Validation'],
            ['validation' => ['channel_settings' => 'Channel Settings']],
            ['alias' => 'validation'],
            false
        );

        $this->assertArrayHasKey('index', $nav->nav_items);
        $this->assertArrayHasKey('validation', $nav->nav_items);
        $this->assertTrue($nav->nav_items['validation']->active);
        $this->assertSame('Validation', $nav->active_title);
        $this->assertSame('Validation', ee()->view->cp_page_title);
        $this->assertNotEmpty(ee()->cp->crumbs);
        $this->assertNotEmpty($nav->nav_items['validation']->buttons);
        $this->assertSame($nav->nav_items['validation'], $nav->getActiveItem());
    }

    public function testFluxNavGetUrlAndToolbarIcon()
    {
        $nav = new FluxNavDefaultFixture();

        $this->assertSame('http://example.com/x', $nav->getUrl('http://example.com/x'));
        $this->assertIsObject($nav->getUrl('/'));

        $nav->setToolbarIcon();
        $this->assertSame('Structure', ee()->view->header['title']);
        $this->assertArrayHasKey('settings', ee()->view->header['toolbar_items']);
    }

    public function testFluxNavCanDeferGeneration()
    {
        ee()->uri->segments = ['addons', 'settings', 'structure', 'validation'];

        $nav = new FluxNavConfiguredFixture(
            ['validation' => 'Validation'],
            [],
            [],
            true
        );

        $this->assertNull($nav->sidebar);
        $this->assertNull($nav->nav_items);
    }

    public function testFluxNavSetActiveMatchesDirectSegment()
    {
        ee()->uri->segments = ['addons', 'settings', 'structure', 'validation'];

        $nav = new FluxNavConfiguredFixture(
            ['validation' => 'Validation'],
            [],
            [],
            false
        );

        $this->assertSame('Validation', $nav->active_title);
        $this->assertTrue($nav->nav_items['validation']->active);
        $this->assertSame($nav->nav_items['validation'], $nav->getActiveItem());
    }

    public function testMcpNavDefaultItemsAndMapUsePermissions()
    {
        $mcp = (new ReflectionClass(McpNav::class))->newInstanceWithoutConstructor();

        $defaultItems = $this->invokeProtected($mcp, 'defaultItems');
        $defaultButtons = $this->invokeProtected($mcp, 'defaultButtons');
        $defaultMap = $this->invokeProtected($mcp, 'defaultActiveMap');

        $this->assertArrayHasKey('index', $defaultItems);
        $this->assertArrayHasKey('channel_settings', $defaultItems);
        $this->assertArrayHasKey('module_settings', $defaultItems);
        $this->assertArrayHasKey('validation', $defaultItems);
        $this->assertArrayHasKey('nav_history', $defaultItems);
        $this->assertSame([], $defaultButtons);
        $this->assertSame(['structure' => 'index'], $defaultMap);
    }

    public function testMcpNavNoopMethodsReturnNull()
    {
        $mcp = (new ReflectionClass(McpNav::class))->newInstanceWithoutConstructor();

        $this->assertNull($mcp->deferGenerate());
        $this->assertNull($mcp->postGenerateNav());
    }

    private function invokeProtected($object, $method)
    {
        $rm = new ReflectionMethod($object, $method);
        $rm->setAccessible(true);
        return $rm->invoke($object);
    }
}
