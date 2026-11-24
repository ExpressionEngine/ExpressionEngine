<?php

namespace ExpressionEngine\Tests\Installer\Updates;

use ExpressionEngine\Updater\Version_7_6_0\Updater;
use ExpressionEngine\Updater\Library\Filesystem\Filesystem;
use ExpressionEngine\Updater\Service\Updater\DatabaseUpdater;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Version760Test extends TestCase
{
    protected function setUp(): void
    {
        require_once SYSPATH . 'ee/installer/updates/ud_7_06_00.php';
        require_once BASEPATH . 'libraries/Progress.php';
        require_once BASEPATH . 'libraries/template_router/Match.php';
        require_once BASEPATH . 'libraries/Driver.php';
        require_once BASEPATH . 'libraries/Cache/Cache.php';
        ee()->setMock('Security/XSS', new class {
            public function clean($value) { return $value; }
        });
        ee()->setMock('config', new class extends \eeSingletonConfigMock {
            public function set_item($item, $value) { $this->setItem($item, $value); }
        });
        ee()->setMock('cache', new class {
            public $deleted = [];
            public function delete($key, $scope = \Cache::LOCAL_SCOPE)
            {
                $site = ee()->config->item('site_short_name') . ' at ' . ee()->config->item('base_url');
                $this->deleted[] = ($scope === \Cache::GLOBAL_SCOPE ? 'all sites' : $site) . ' ' . $key;

                return true;
            }
        });
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * Preserve stored route definitions while applying the combined update.
     *
     * @param string $required Whether the route requires every segment.
     * @param string $compiled Previously compiled route expression.
     * @return void
     * @dataProvider routeRequirements
     */
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
            ->onlyMethods(['select', 'join', 'where', 'get', 'update'])
            ->addMethods(['table_exists', 'field_exists'])->getMock();
        $db->expects($this->once())->method('table_exists')->with('mfa_sessions')->willReturn(true);
        $db->expects($this->once())->method('field_exists')->with('mfa_login_id', 'sessions')->willReturn(true);
        $db->method('select')->willReturnSelf();
        $db->expects($this->exactly(2))->method('join')->withConsecutive(
            ['templates', 'templates.template_id = template_routes.template_id'],
            ['template_groups', 'template_groups.group_id = templates.group_id']
        )->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->method('get')->willReturnCallback(function ($table) use ($row) {
            return new \eeDbResultMock($table === 'sites' ? [] : [$row]);
        });
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
        $this->assertSame('runUpdateFile[ud_7_06_00.php]', $updater->steps[0]);
        $this->assertContains('template_routes', $updater->getAffectedTables());
        $updater->runUpdateFile('ud_7_06_00.php');
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

    /**
     * Refresh capture names when the CLI still holds the old router in memory.
     *
     * @return void
     */
    public function testUsesNewCaptureNamesWhenTheOldRouterIsAlreadyLoaded()
    {
        // Model the old parser retained in a CLI process after core file replacement.
        class_alias(LegacyRouteStub::class, 'EE_Route');
        $saved = null;
        $db = $this->getMockBuilder(\eeDbArMock::class)
            ->onlyMethods(['select', 'join', 'where', 'get', 'update'])
            ->addMethods(['table_exists', 'field_exists'])->getMock();
        $db->expects($this->once())->method('table_exists')->with('mfa_sessions')->willReturn(true);
        $db->expects($this->once())->method('field_exists')->with('mfa_login_id', 'sessions')->willReturn(true);
        $db->method('select')->willReturnSelf();
        $db->expects($this->exactly(2))->method('join')->withConsecutive(
            ['templates', 'templates.template_id = template_routes.template_id'],
            ['template_groups', 'template_groups.group_id = templates.group_id']
        )->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $routes = [[
            'route_id' => 42,
            'route' => '/articles/{category:alpha_dash}/{entry:alpha_dash}',
            'route_required' => 'y',
        ]];
        $db->method('get')->willReturnCallback(function ($table) use ($routes) {
            return new \eeDbResultMock($table === 'sites' ? [] : $routes);
        });
        $db->expects($this->once())->method('update')->willReturnCallback(function ($table, $data) use (&$saved) {
            $saved = $data['route_parsed'];
        });
        ee()->setMock('db', $db);

        (new Updater())->do_update();
        $this->assertSame(1, preg_match('#' . $saved . '#', 'articles/news/example', $matches));
        $this->assertSame('news', $matches['e5ccbf9c9c5fc1bc34df8238a970949']);
        $this->assertSame('example', $matches['e61c4128c816142244cd9de5f843e1d']);
    }

    /**
     * Complete the combined update when no routes need recompiling.
     *
     * @return void
     */
    public function testUpdateWorksWithoutStoredRoutes()
    {
        $db = $this->getMockBuilder(\eeDbArMock::class)
            ->onlyMethods(['select', 'join', 'where', 'get', 'update', 'insert'])
            ->addMethods(['table_exists', 'field_exists'])->getMock();
        $db->expects($this->once())->method('table_exists')->with('mfa_sessions')->willReturn(true);
        $db->expects($this->once())->method('field_exists')->with('mfa_login_id', 'sessions')->willReturn(true);
        $db->method('select')->willReturnSelf();
        $db->expects($this->exactly(2))->method('join')->withConsecutive(
            ['templates', 'templates.template_id = template_routes.template_id'],
            ['template_groups', 'template_groups.group_id = templates.group_id']
        )->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->method('get')->willReturn(new \eeDbResultMock([]));
        $db->expects($this->never())->method('update');
        $db->expects($this->once())->method('insert')->with('actions', [
            'class' => 'Pro',
            'method' => 'verifyDomainChallenge',
        ])->willReturn(true);
        ee()->setMock('db', $db);
        $updater = new Updater();
        $this->assertSame(['template_routes', 'actions'], $updater->affected_tables);
        $this->assertTrue($updater->do_update());
    }

    /**
     * Keep the callback registered by Pro's installer when the upgrade is run again.
     *
     * @return void
     */
    public function testUpgradeKeepsTheActionRegisteredByPro(): void
    {
        require_once PATH_ADDONS . 'pro/mod.pro.php';
        require_once PATH_ADDONS . 'pro/upd.pro.php';

        $action = ['class' => 'Pro', 'method' => 'verifyDomainChallenge'];
        $this->assertContains($action, get_class_vars(\Pro_upd::class)['actions']);
        $this->assertTrue(is_callable([new \Pro(), $action['method']]));

        $db = $this->createMock(\eeDbArMock::class);
        $db->expects($this->once())->method('where')->with($action)->willReturnSelf();
        $db->expects($this->once())->method('count_all_results')->with('actions')->willReturn(1);
        $db->expects($this->never())->method('insert');
        ee()->setMock('db', $db);

        $method = new \ReflectionMethod(Updater::class, 'registerDomainChallengeAction');
        \TestReflectionHelper::makeAccessible($method);
        $this->assertTrue($method->invoke(new Updater()));
    }

    /**
     * Clear select caches for each site, then return to the current site.
     *
     * @return void
     */
    public function testClearsSavedOutputForEachSite()
    {
        $db = $this->getMockBuilder(\eeDbArMock::class)->onlyMethods(['select', 'get'])->getMock();
        $db->method('select')->willReturnSelf();
        $db->method('get')->willReturnCallback(function ($table) {
            return new \eeDbResultMock($table === 'sites' ? [
                ['site_id' => 1, 'site_name' => 'default_site'],
                ['site_id' => 2, 'site_name' => 'second_site'],
            ] : [
                // The first site's saved address differs from the one it is configured with
                ['site_id' => 1, 'value' => 'https://saved.example/'],
                ['site_id' => 2, 'value' => 'https://two.example/'],
            ]);
        });
        ee()->setMock('db', $db);
        ee()->config->set_item('site_short_name', 'default_site');
        ee()->config->set_item('base_url', 'https://one.example/');

        $method = new \ReflectionMethod(Updater::class, 'clearRenderedCaches');
        \TestReflectionHelper::makeAccessible($method);

        $this->assertTrue($method->invoke(new Updater()));
        $this->assertSame([
            'default_site at https://one.example/ /page_cache/',
            'default_site at https://one.example/ /tag_cache/',
            'default_site at https://one.example/ /resource_cache/',
            'default_site at https://saved.example/ /page_cache/',
            'default_site at https://saved.example/ /tag_cache/',
            'default_site at https://saved.example/ /resource_cache/',
            'second_site at https://two.example/ /page_cache/',
            'second_site at https://two.example/ /tag_cache/',
            'second_site at https://two.example/ /resource_cache/',
            'all sites /resource_cache/',
        ], ee()->cache->deleted);
        $this->assertSame('default_site', ee()->config->item('site_short_name'));
        $this->assertSame('https://one.example/', ee()->config->item('base_url'));
    }

    /**
     * Put the site's settings back when clearing a cache fails part-way.
     *
     * @return void
     */
    public function testSiteSettingsAreRestoredWhenClearingFails()
    {
        $db = $this->getMockBuilder(\eeDbArMock::class)->onlyMethods(['select', 'get'])->getMock();
        $db->method('select')->willReturnSelf();
        $db->method('get')->willReturnCallback(function ($table) {
            return new \eeDbResultMock($table === 'sites' ? [
                ['site_id' => 1, 'site_name' => 'default_site'],
                ['site_id' => 2, 'site_name' => 'second_site'],
            ] : [
                ['site_id' => 1, 'value' => 'https://one.example/'],
                ['site_id' => 2, 'value' => 'https://two.example/'],
            ]);
        });
        ee()->setMock('db', $db);
        ee()->setMock('cache', new class {
            public function delete($key, $scope = \Cache::LOCAL_SCOPE)
            {
                if (ee()->config->item('site_short_name') === 'second_site') {
                    throw new \RuntimeException('Cache could not be cleared.');
                }

                return true;
            }
        });
        ee()->config->set_item('site_short_name', 'default_site');
        ee()->config->set_item('base_url', 'https://one.example/');

        $method = new \ReflectionMethod(Updater::class, 'clearRenderedCaches');
        \TestReflectionHelper::makeAccessible($method);

        try {
            $method->invoke(new Updater());
            $this->fail('Expected the failure to be passed on.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Cache could not be cleared.', $exception->getMessage());
        }

        $this->assertSame('default_site', ee()->config->item('site_short_name'));
        $this->assertSame('https://one.example/', ee()->config->item('base_url'));
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
