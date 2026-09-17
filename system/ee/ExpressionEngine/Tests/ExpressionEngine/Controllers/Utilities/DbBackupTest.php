<?php

namespace ExpressionEngine\Controller\Utilities {
    class DbBackupPermissionDenied extends \RuntimeException
    {
    }

    function show_error($message, $status = 500)
    {
        throw new DbBackupPermissionDenied($message, $status);
    }
}

namespace ExpressionEngine\Tests\Controllers\Utilities {

use ExpressionEngine\Controller\Utilities\DbBackup;
use ExpressionEngine\Controller\Utilities\DbBackupPermissionDenied;
use ExpressionEngine\Controller\Utilities\SyncConditionalFields;
use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Service\Database\Backup\Backup;
use ExpressionEngine\Service\Database\Backup\Query;
use PHPUnit\Framework\TestCase;

class DbBackupTest extends TestCase
{
    private $filesystem;
    private $query;
    private $metadataCalls = 0;
    private $exports = [];
    private $writes = [];
    private $globals = [];
    private $runtimeLimits;

    public static function setUpBeforeClass(): void
    {
        require_once APPPATH . 'core/Controller.php';
    }

    protected function setUp(): void
    {
        ee()->resetMocks();
        $this->runtimeLimits = [ini_get('memory_limit'), ini_get('max_execution_time')];

        // CP authentication and MFA are preconditions; exercise the real constructors.
        $core = $this->service('core', ['bootstrap', 'run_ee', 'run_cp']);
        $core->expects($this->atLeastOnce())->method('run_cp');
        $this->service('extensions', ['active_hook'])->method('active_hook')->willReturn(false);
        $this->service('lang', ['loadfile']);
        ee()->setMock('view', new \stdClass());
        ee()->setMock('db', (object) ['database' => 'synthetic']);
        $this->service('localize', ['format_date'])->method('format_date')->willReturn('fixture');

        $fluent = ['makeDeprecationNotice', 'now', 'makeInline', 'asIssue', 'asSuccess',
            'canClose', 'withTitle', 'addToBody', 'defer', 'make'];
        $ui = $this->service('CP/Alert', array_merge($fluent, ['render', 'compile']));
        foreach ($fluent as $method) {
            $ui->method($method)->willReturnSelf();
        }
        $ui->method('render')->willReturn('synthetic view');
        $ui->method('compile')->willReturn('#');
        ee()->setMock('View', $ui);
        ee()->setMock('CP/URL', $ui);
        $this->service('cp', ['add_js_script', 'masked_url', 'render'])
            ->method('masked_url')->willReturn('#');
        $this->service('javascript', ['set_global'])->method('set_global')
            ->willReturnCallback(function ($globals) { $this->globals = $globals; });

        // Every filesystem method is doubled, including Utilities' garbage collection.
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->filesystem->method('isWritable')->willReturn(true);
        $this->filesystem->method('getFreeDiskSpace')->willReturn(1048576);
        $this->filesystem->method('write')->willReturnCallback(
            function ($path, $data, $overwrite = false, $append = false) {
                $this->writes[] = [$path, $data, $overwrite, $append];
            }
        );
        ee()->setMock('Filesystem', $this->filesystem);

        $this->query = $this->createMock(Query::class);
        $this->query->method('getTables')->willReturnCallback(function () {
            ++$this->metadataCalls;
            return ['fixture_notes' => ['rows' => 2, 'size' => 64, 'type' => Query::TABLE_STRUCTURE]];
        });
        $this->query->method('getCharset')->willReturn('utf8mb4');
        $this->query->method('getDropStatement')->willReturn('DROP TABLE IF EXISTS fixture_notes;');
        $this->query->method('getCreateForTable')->willReturn('CREATE TABLE fixture_notes (note TEXT);');
        $this->query->method('getInsertsForTable')->willReturnCallback(function ($table, $offset, $limit) {
            $this->exports[] = [$table, $offset, $limit];
            $rows = ['first synthetic note', 'second synthetic note'];
            $row = $rows[$offset ?? 0] ?? null;
            return $row === null ? null : [
                'insert_string' => "INSERT INTO fixture_notes VALUES ('{$row}');",
                'rows_exported' => 1,
            ];
        });
        ee()->setMock('Database/Backup/Query', $this->query);
    }

    protected function tearDown(): void
    {
        ini_set('memory_limit', $this->runtimeLimits[0]);
        set_time_limit((int) $this->runtimeLimits[1]);
        ee()->resetMocks();
    }

    /** @dataProvider deniedRequests */
    public function testDeniedRequestsHaveNoBackupSideEffects($permissions, $action, $method, $post)
    {
        $this->permissions($permissions);
        $status = null;
        try {
            $this->invoke($action, $method, $post);
        } catch (DbBackupPermissionDenied $error) {
            $status = $error->getCode();
        }

        // On the unfixed controller, field editors and Utilities users reach the recorders.
        $this->assertSame(
            [403, 0, [], [], []],
            [$status, $this->metadataCalls, $this->exports, $this->writes, $this->globals],
            'Deny backup access before metadata, export, file writes or page disclosure.'
        );
    }

    public static function deniedRequests(): array
    {
        $requests = [
            'page' => ['index', 'GET', []],
            'initial POST' => ['doBackup', 'POST', ['table_name' => '', 'offset' => 0, 'file_path' => '']],
            'continuation POST' => ['doBackup', 'POST', [
                'table_name' => 'fixture_notes', 'offset' => 1,
                'file_path' => str_replace(SYSPATH, '', self::filePath()),
            ]],
            'direct GET' => ['doBackup', 'GET', []],
        ];
        $cases = [];
        foreach ([
            'CP only' => [],
            'field editor' => ['edit_channel_fields'],
            'Utilities user' => ['access_utilities'],
        ] as $role => $permissions) {
            foreach ($requests as $request => $arguments) {
                $cases[$role . ': ' . $request] = array_merge([$permissions], $arguments);
            }
        }
        return $cases;
    }

    /** @dataProvider authorizedMembers */
    public function testAuthorizedMembersCanRenderAndCompleteBackup($permissions, $superAdmin)
    {
        $this->permissions($permissions, $superAdmin);
        $this->invoke('index', 'GET', []);
        $this->assertSame(['fixture_notes'], $this->globals['db_backup']['tables']);
        $this->assertSame(['fixture_notes' => 2], $this->globals['db_backup']['table_counts']);

        $post = ['table_name' => '', 'offset' => 0, 'file_path' => ''];
        $relativePath = str_replace(SYSPATH, '', self::filePath());
        for ($offset = 1; $offset <= 2; ++$offset) {
            $result = $this->invoke('doBackup', 'POST', $post);
            $this->assertSame([
                'status' => 'in_progress', 'table_name' => 'fixture_notes',
                'offset' => $offset, 'file_path' => $relativePath,
            ], $result);
            $post = $result;
        }
        $this->assertSame(
            ['status' => 'finished', 'file_path' => $relativePath],
            $this->invoke('doBackup', 'POST', $post)
        );
        $this->assertSame([
            ['fixture_notes', 0, 1], ['fixture_notes', 1, 1], ['fixture_notes', 2, 1],
        ], $this->exports);
        $this->assertSame([self::filePath(), '', true, false], $this->writes[0]);
        foreach (array_slice($this->writes, 1) as $write) {
            $this->assertSame([self::filePath(), false, true], [$write[0], $write[2], $write[3]]);
        }
        $contents = implode('', array_column($this->writes, 1));
        $this->assertStringContainsString("INSERT INTO fixture_notes VALUES ('first synthetic note');", $contents);
        $this->assertStringContainsString("INSERT INTO fixture_notes VALUES ('second synthetic note');", $contents);
        $this->assertStringContainsString('Database backup completed by ExpressionEngine', $contents);
    }

    public static function authorizedMembers(): array
    {
        return [
            'Utilities SQL Manager' => [['access_utilities', 'access_sql_manager'], false],
            'field editor SQL Manager' => [['edit_channel_fields', 'access_sql_manager'], false],
            'Super Admin' => [[], true],
        ];
    }

    public function testFieldEditorsRetainConditionalSyncAccess()
    {
        $this->permissions(['edit_channel_fields']);
        $this->assertInstanceOf(SyncConditionalFields::class, $this->controller(SyncConditionalFields::class));
        $this->assertSame([0, [], []], [$this->metadataCalls, $this->exports, $this->writes]);
    }

    private function permissions(array $permissions, $superAdmin = false)
    {
        $permissions[] = 'access_cp';
        $this->service('Permission', ['can'])->method('can')
            ->willReturnCallback(function ($permission) use ($permissions, $superAdmin) {
                return $superAdmin || in_array($permission, $permissions, true);
            });
    }

    private function invoke($action, $method, array $post)
    {
        $request = $this->service('Request', ['method', 'post']);
        // Request::method() shadows PHPUnit's shortcut; configure through expects().
        $request->expects($this->any())->method('method')->willReturn($method);
        $request->expects($this->any())->method('post')->willReturnCallback(function ($key) use ($method, $post) {
            return $method === 'POST' ? ($post[$key] ?? null) : null;
        });
        // The test container returns a fixed service; each request gets a fresh real backup.
        ee()->setMock('Database/Backup', new Backup($this->filesystem, $this->query, self::filePath(), 1));
        return $this->controller(DbBackup::class)->$action();
    }

    private function controller($class)
    {
        // Only sidebar presentation is replaced; constructors and actions run unchanged.
        return $this->getMockBuilder($class)->onlyMethods(['generateSidebar'])->getMock();
    }

    private function service($name, array $methods)
    {
        $mock = $this->getMockBuilder(\stdClass::class)->addMethods($methods)->getMock();
        ee()->setMock($name, $mock);
        return $mock;
    }

    private static function filePath(): string
    {
        return PATH_CACHE . 'synthetic_fixture.sql';
    }
}
}
