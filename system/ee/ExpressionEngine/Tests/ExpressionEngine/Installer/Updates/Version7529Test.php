<?php

namespace ExpressionEngine\Tests\Installer\Updates;

use ExpressionEngine\Updater\Version_7_5_29\Updater;
use ExpressionEngine\Updater\Library\Filesystem\Filesystem;
use ExpressionEngine\Updater\Service\Updater\DatabaseUpdater;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Version7529Test extends TestCase
{
    protected function setUp(): void
    {
        define('EE_APPPATH', BASEPATH);
        require_once SYSPATH . 'ee/installer/updates/ud_7_05_29.php';
        require_once BASEPATH . 'libraries/Progress.php';
        require_once BASEPATH . 'libraries/template_router/Match.php';
        ee()->setMock('Security/XSS', new class {
            public function clean($value) { return $value; }
        });
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /** @dataProvider routeRequirements */
    public function testUpgradeFrom7528RecompilesSavedRoutesWithoutChangingTheirDefinitions($required, $compiled)
    {
        require_once BASEPATH . 'libraries/template_router/Route.php';

        // Captures produced by EE 7.5.28, before the PHP 8.6 precision fix.
        $row = [
            'route_id' => 42,
            'route' => '/articles/{category:alpha_dash}/{entry:alpha_dash}',
            'route_required' => $required,
            'route_parsed' => $compiled,
        ];
        $saved = $row;
        $db = $this->getMockBuilder(\eeDbArMock::class)
            ->onlyMethods(['select', 'join', 'where', 'get', 'update'])->getMock();
        $db->method('select')->willReturnSelf();
        $db->expects($this->exactly(2))->method('join')->withConsecutive(
            ['templates', 'templates.template_id = template_routes.template_id'],
            ['template_groups', 'template_groups.group_id = templates.group_id']
        )->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->method('get')->willReturn(new \eeDbResultMock([$row]));
        $db->expects($this->once())->method('update')->willReturnCallback(function ($table, $data, $where) use (&$saved) {
            $this->assertSame('template_routes', $table);
            $this->assertSame(['route_id' => 42], $where);
            $this->assertSame(['route_parsed'], array_keys($data));
            $saved = array_merge($saved, $data);
            return true;
        });
        ee()->setMock('db', $db);

        $updater = new DatabaseUpdater('7.5.28', new Filesystem());
        $this->assertTrue($updater->hasUpdatesToRun());
        $this->assertSame('runUpdateFile[ud_7_05_29.php]', $updater->steps[0]);
        $this->assertContains('template_routes', $updater->getAffectedTables());
        $updater->runUpdateFile('ud_7_05_29.php');
        $this->assertNotSame($row['route_parsed'], $saved['route_parsed']);
        $this->assertSame($row['route'], $saved['route']);
        $this->assertSame($required, $saved['route_required']);
        $this->assertSame(1, preg_match_all('#' . $saved['route_parsed'] . '#', 'articles/news/example', $matches));
        $route = new \EE_Route($saved['route'], $required === 'y');
        $match = new \EE_Route_match([], $matches, $route);
        $this->assertSame(['category' => ['news'], 'entry' => ['example']], $match->matches);
        $this->assertSame($required === 'y' ? 0 : 1, preg_match('#' . $saved['route_parsed'] . '#', 'articles/news'));
    }

    public function routeRequirements()
    {
        return [
            'required' => ['y', '^(articles)\/((?P<eau88zztlnqos048sc80go4g4skgo0gg>(((?=\b([A-Za-z0-9_-]+)\b)([^\/]*)))))\/((?P<ebf4igd1hpwoogk4sgkkswk0s444so8s>(((?=\b([A-Za-z0-9_-]+)\b)([^\/]*)))))\/?$'],
            'optional' => ['n', '^(articles)\/((?P<eau88zztlnqos048sc80go4g4skgo0gg>(((?=\b([A-Za-z0-9_-]+)\b)([^\/]*)))))\/?((?P<ebf4igd1hpwoogk4sgkkswk0s444so8s>(((?=\b([A-Za-z0-9_-]+)\b)([^\/]*)))))?\/?$'],
        ];
    }

    public function testUsesNewCaptureNamesWhenTheOldRouterIsAlreadyLoaded()
    {
        // Model the old parser retained in a CLI process after core file replacement.
        class_alias(LegacyRouteStub::class, 'EE_Route');
        $saved = null;
        $db = $this->getMockBuilder(\eeDbArMock::class)
            ->onlyMethods(['select', 'join', 'where', 'get', 'update'])->getMock();
        $db->method('select')->willReturnSelf();
        $db->expects($this->exactly(2))->method('join')->withConsecutive(
            ['templates', 'templates.template_id = template_routes.template_id'],
            ['template_groups', 'template_groups.group_id = templates.group_id']
        )->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->method('get')->willReturn(new \eeDbResultMock([[
            'route_id' => 42,
            'route' => '/articles/{category:alpha_dash}/{entry:alpha_dash}',
            'route_required' => 'y',
        ]]));
        $db->expects($this->once())->method('update')->willReturnCallback(function ($table, $data) use (&$saved) {
            $saved = $data['route_parsed'];
        });
        ee()->setMock('db', $db);

        (new Updater())->do_update();
        $this->assertSame(1, preg_match('#' . $saved . '#', 'articles/news/example', $matches));
        $this->assertSame('news', $matches['e5ccbf9c9c5fc1bc34df8238a970949']);
        $this->assertSame('example', $matches['e61c4128c816142244cd9de5f843e1d']);
    }

    public function testUpdateWorksWithoutStoredRoutes()
    {
        $db = $this->getMockBuilder(\eeDbArMock::class)
            ->onlyMethods(['select', 'join', 'where', 'get', 'update'])->getMock();
        $db->method('select')->willReturnSelf();
        $db->expects($this->exactly(2))->method('join')->withConsecutive(
            ['templates', 'templates.template_id = template_routes.template_id'],
            ['template_groups', 'template_groups.group_id = templates.group_id']
        )->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->method('get')->willReturn(new \eeDbResultMock([]));
        $db->expects($this->never())->method('update');
        ee()->setMock('db', $db);
        $updater = new Updater();
        $this->assertSame(['template_routes'], $updater->affected_tables);
        $this->assertTrue($updater->do_update());
    }
}

/** A minimal double for the public API of a preloaded EE 7.5.28 route. */
class LegacyRouteStub
{
    public $subpatterns = [
        'eau88zztlnqos048sc80go4g4skgo0gg' => 'category',
        'ebf4igd1hpwoogk4sgkkswk0s444so8s' => 'entry',
    ];

    public function compile()
    {
        return '^articles/(?P<eau88zztlnqos048sc80go4g4skgo0gg>[a-z]+)/(?P<ebf4igd1hpwoogk4sgkkswk0s444so8s>[a-z]+)$';
    }
}
