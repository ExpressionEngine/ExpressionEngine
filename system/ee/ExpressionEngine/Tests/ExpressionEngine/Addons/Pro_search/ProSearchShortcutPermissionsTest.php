<?php

use PHPUnit\Framework\TestCase;

class ProSearchShortcutPermissionDenied extends RuntimeException {}
class ProSearchShortcutViewReady extends RuntimeException {}

/**
 * Isolate the legacy error helper and request constants.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ProSearchShortcutPermissionsTest extends TestCase
{
    private $mcp;
    private $groups;
    private $shortcuts;
    private $input;

    /**
     * Prepare the controller with isolated request and persistence mocks.
     *
     * @return void
     */
    protected function setUp(): void
    {
        ee()->resetMocks();
        if (!defined('AJAX_REQUEST')) {
            define('AJAX_REQUEST', false);
        }
        if (!function_exists('show_error')) {
            function show_error($message, $status = 500, $heading = 'Error')
            {
                throw new ProSearchShortcutPermissionDenied($message);
            }
        }
        require_once BASEPATH . 'helpers/string_helper.php';
        require_once PATH_ADDONS . 'pro_search/mcp.pro_search.php';
        require_once PATH_ADDONS . 'pro_search/helpers/pro_search_helper.php';

        $this->mcp = $this->getMockBuilder(Pro_search_mcp::class)
            ->disableOriginalConstructor()->onlyMethods(['mcp_url'])->getMock();
        $this->mcp->method('mcp_url')->willReturn('groups');
        $this->groups = $this->stub(['get_by_site', 'insert', 'delete']);
        $this->shortcuts = $this->stub(['validate', 'insert', 'update', 'delete', 'delete_by_group', 'empty_row']);
        $this->shortcuts->method('validate')->willReturnArgument(0);
        $this->setProperty('groups', $this->groups);
        $this->setProperty('shortcuts', $this->shortcuts);
        $this->setProperty('site_id', 1);
        ee()->setMock('pro_search_group_model', $this->groups);

        $this->input = $this->stub(['post', 'get', 'get_post']);
        ee()->setMock('input', $this->input);
        ee()->setMock('session', $this->stub(['set_flashdata']));
        ee()->setMock('functions', $this->stub(['redirect']));
        $alert = $this->stub(['makeInline', 'asSuccess', 'withTitle', 'defer']);
        foreach (['makeInline', 'asSuccess', 'withTitle'] as $method) {
            $alert->method($method)->willReturnSelf();
        }
        ee()->setMock('CP/Alert', $alert);
    }

    /**
     * Release request state between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * Cover denied roles, permitted roles, and the Super Admin exception.
     *
     * @return array
     */
    public static function permissions(): array
    {
        return [
            'denied' => [5, [], false],
            'allowed' => [5, [5], true],
            'super admin' => [1, [], true],
        ];
    }

    /**
     * Require permission before deleting a group or its shortcuts.
     *
     * @param int $role
     * @param array $permittedRoles
     * @param bool $allowed
     * @return void
     * @dataProvider permissions
     */
    public function testDeleteGroupRequiresPermission($role, $permittedRoles, $allowed): void
    {
        $this->setPermission($role, $permittedRoles, $allowed);
        $this->input->method('post')->with('group_id')->willReturn([7, 8]);
        $this->groups->expects($allowed ? $this->once() : $this->never())
            ->method('delete')->with([7, 8]);
        $this->shortcuts->expects($allowed ? $this->once() : $this->never())
            ->method('delete_by_group')->with([7, 8]);

        $this->mcp->delete_group();
    }

    /**
     * Cover both shortcut insertion and update for each permission state.
     *
     * @return array
     */
    public static function saves(): array
    {
        $cases = [];
        foreach (self::permissions() as $name => $permission) {
            foreach (['new', 9] as $id) {
                $cases[$name . ' ' . $id] = array_merge($permission, [$id]);
            }
        }
        return $cases;
    }

    /**
     * Require permission before inserting or updating a shortcut.
     *
     * @param int $role
     * @param array $permittedRoles
     * @param bool $allowed
     * @param int|string $id
     * @return void
     * @dataProvider saves
     */
    public function testSaveShortcutRequiresPermission($role, $permittedRoles, $allowed, $id): void
    {
        $this->setPermission($role, $permittedRoles, $allowed);
        $this->input->method('post')->willReturnMap([
            ['shortcut_id', $id], ['group_id', 7],
            ['shortcut_name', 'editorial'], ['shortcut_label', 'Editorial'],
            ['param-key', ['keywords']], ['param-val', ['news']],
        ]);
        $data = [
            'shortcut_id' => $id === 'new' ? null : $id,
            'site_id' => 1, 'group_id' => 7,
            'shortcut_name' => 'editorial', 'shortcut_label' => 'Editorial',
            'parameters' => '{"keywords":"news"}',
        ];
        foreach (['insert', 'update'] as $method) {
            $writes = $allowed && $method === ($id === 'new' ? 'insert' : 'update');
            $expectation = $this->shortcuts->expects($writes ? $this->once() : $this->never())->method($method);
            if ($writes) {
                $expectation->with(...($method === 'insert' ? [$data] : [$id, $data]));
            }
        }

        $this->mcp->save_shortcut();
    }

    /**
     * Require permission before deleting shortcuts.
     *
     * @param int $role
     * @param array $permittedRoles
     * @param bool $allowed
     * @return void
     * @dataProvider permissions
     */
    public function testDeleteShortcutRequiresPermission($role, $permittedRoles, $allowed): void
    {
        $this->setPermission($role, $permittedRoles, $allowed);
        $this->input->method('post')->with('shortcut_id')->willReturn([9, 10]);
        $this->shortcuts->expects($allowed ? $this->once() : $this->never())
            ->method('delete')->with([9, 10]);

        $this->mcp->delete_shortcut();
    }

    /**
     * Require permission before changing shortcut order.
     *
     * @param int $role
     * @param array $permittedRoles
     * @param bool $allowed
     * @return void
     * @dataProvider permissions
     */
    public function testOrderShortcutsRequiresPermission($role, $permittedRoles, $allowed): void
    {
        $this->setPermission($role, $permittedRoles, $allowed);
        $this->input->method('post')->with('order')->willReturn([10, 9]);
        $updates = [];
        $this->shortcuts->expects($allowed ? $this->exactly(2) : $this->never())
            ->method('update')->willReturnCallback(function ($id, $data) use (&$updates) {
                $updates[$id] = $data;
            });

        $this->mcp->order_shortcuts();
        $this->assertSame([10 => ['sort_order' => 1], 9 => ['sort_order' => 2]], $updates);
    }

    /**
     * Require permission before the edit form creates a default group.
     *
     * @param int $role
     * @param array $permittedRoles
     * @param bool $allowed
     * @return void
     * @dataProvider permissions
     */
    public function testEditShortcutRequiresPermissionBeforeCreatingDefaultGroup($role, $permittedRoles, $allowed): void
    {
        $this->setPermission($role, $permittedRoles, $allowed);
        $this->groups->method('get_by_site')->willReturn([]);
        $this->groups->expects($allowed ? $this->once() : $this->never())
            ->method('insert')->with(['site_id' => 1, 'group_label' => 'Default'])->willReturn(7);
        // Stop after the default group write, before unrelated form rendering.
        $this->shortcuts->method('empty_row')->willThrowException(new ProSearchShortcutViewReady());
        if ($allowed) {
            $this->expectException(ProSearchShortcutViewReady::class);
        }

        $this->mcp->edit_shortcut();
    }

    /**
     * Show the shortcut action only to users who can manage shortcuts.
     *
     * @param int $role
     * @param array $permittedRoles
     * @param bool $allowed
     * @return void
     * @dataProvider permissions
     */
    public function testSearchLogShortcutActionRequiresPermission($role, $permittedRoles, $allowed): void
    {
        $this->setProperty('member_group', $role);
        $settings = $this->stub(['get']);
        $settings->method('get')->willReturnMap([
            ['search_log_size', 0],
            ['can_view_search_log', [$role]],
            ['can_manage_shortcuts', $permittedRoles],
        ]);
        ee()->setMock('pro_search_settings', $settings);

        $request = $this->stub(['post', 'get']);
        $request->method('get')->with('page', 1)->willReturn(1);
        ee()->setMock('Request', $request);
        $log = $this->stub(['get_site_count', 'get_member_ids', 'get_dates', 'get_filtered_rows']);
        $log->method('get_site_count')->willReturn(1);
        $log->method('get_member_ids')->willReturn([]);
        $log->method('get_dates')->willReturn([]);
        $log->method('get_filtered_rows')->willReturn([[
            'log_id' => 9, 'keywords' => 'news', 'num_results' => 2,
            'member_id' => 0, 'ip_address' => '127.0.0.1',
            'search_date' => 0, 'parameters' => '',
        ]]);
        ee()->setMock('pro_search_log_model', $log);
        ee()->setMock('db', new FakeDb());
        ee()->setMock('localize', $this->stub(['human_time']));
        $view = $this->stub(['make', 'render']);
        $view->method('make')->willReturnSelf();
        ee()->setMock('View', $view);

        $table = $this->stub(['setNoResultsText', 'setColumns', 'setData', 'viewData']);
        $table->expects($this->once())->method('setData')->willReturnCallback(function ($rows) use ($allowed) {
            $this->assertCount(1, $rows);
            $this->assertSame('news', $rows[0][0]);
            $this->assertSame($allowed ? ['next'] : [], array_keys($rows[0][6]['toolbar_items']));
        });
        // Stop after assembling the real log rows, before unrelated page rendering.
        $table->method('viewData')->willThrowException(new ProSearchShortcutViewReady());
        ee()->setMock('CP/Table', $table);
        $this->expectException(ProSearchShortcutViewReady::class);

        $this->mcp->search_log();
    }

    /**
     * Use the real role check and expect denied requests to stop.
     *
     * @param int $role
     * @param array $permittedRoles
     * @param bool $allowed
     * @return void
     */
    private function setPermission($role, array $permittedRoles, $allowed): void
    {
        $this->setProperty('member_group', $role);
        $settings = $this->stub(['get']);
        $settings->method('get')->with('can_manage_shortcuts')->willReturn($permittedRoles);
        ee()->setMock('pro_search_settings', $settings);
        if (!$allowed) {
            $this->expectException(ProSearchShortcutPermissionDenied::class);
            $this->expectExceptionMessage('Operation not permitted');
        }
    }

    /**
     * Set controller state without loading application services.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    private function setProperty($name, $value): void
    {
        $property = new ReflectionProperty(Pro_search_mcp::class, $name);
        TestReflectionHelper::makeAccessible($property);
        $property->setValue($this->mcp, $value);
    }

    /**
     * Create a small service mock.
     *
     * @param array $methods
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function stub(array $methods)
    {
        return $this->getMockBuilder(stdClass::class)->addMethods($methods)->getMock();
    }
}
