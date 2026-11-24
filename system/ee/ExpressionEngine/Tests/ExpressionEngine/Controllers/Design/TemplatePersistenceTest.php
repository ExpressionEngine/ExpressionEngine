<?php

namespace ExpressionEngine\Tests\Controllers\Design;

use ExpressionEngine\Controller\Design\Template;
use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Service\Database\Database;
use ExpressionEngine\Service\Model\Configuration;
use ExpressionEngine\Service\Model\DataStore;
use ExpressionEngine\Service\Model\Facade;
use ExpressionEngine\Service\Validation\Factory;
use PHPUnit\Framework\TestCase;

/**
 * Isolate PATH_TMPL and the Template model's static group cache per fixture.
 * All content is literal text; no template code is evaluated.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class TemplatePersistenceTest extends TestCase
{
    private $pdo;
    private $models;
    private $controller;
    private $root;
    private $config;
    private $session;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 3) . '/support/template_controller_fixture.php';
        require_once APPPATH . 'core/Controller.php';
        require_once APPPATH . 'libraries/Template.php';
        require_once APPPATH . 'libraries/Api.php';
        require_once APPPATH . 'libraries/api/Api_template_structure.php';

        ee()->resetMocks();
        $_POST = [];
        $this->assertFalse(defined('PATH_TMPL'), 'This fixture needs an isolated PHP process.');
        $this->root = sys_get_temp_dir() . '/ee-template-regression-' . bin2hex(random_bytes(8)) . '/';
        mkdir($this->root);
        define('PATH_TMPL', $this->root);
        if (!defined('AJAX_REQUEST')) {
            define('AJAX_REQUEST', false);
        }

        $this->config = new \FakeConfig();
        $this->config->items = [
            'site_id' => 1, 'site_short_name' => 'fixture',
            'save_tmpl_files' => 'y', 'save_tmpl_revisions' => 'y',
            'max_tmpl_revisions' => 10, 'enable_hit_tracking' => 'n',
            'allow_php' => 'n', 'hidden_template_indicator' => '_',
        ];
        ee()->setMock('config', $this->config);
        $this->session = new class extends \eeSingletonSessionMock {
            public function set_flashdata($key, $value) {}
        };
        $this->session->resetUserdata();
        $this->session->setUserdata('member_id', 10);
        ee()->setMock('session', $this->session);
        ee()->setMock('localize', (object) ['now' => 1234567890]);
        $input = new class {
            public function post($key) { return $_POST[$key] ?? null; }
            public function get($key) { return null; }
        };
        ee()->setMock('input', $input);
        ee()->setMock('Request', $input);
        ee()->setMock('Permission', new class {
            public function isSuperAdmin() { return false; }
            public function can($permission)
            {
                return in_array($permission, [
                    'access_cp', 'access_design',
                    'edit_templates_template_group_id_10',
                    'manage_settings_template_group_id_10',
                    'create_templates_template_group_id_10',
                ], true);
            }
        });
        ee()->setMock('functions', new class {
            public $template_type;
            public function clear_caching($type) {}
            public function redirect($url) { throw new TemplateFixtureRedirect(); }
        });
        $alert = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['makeInline', 'asSuccess', 'asIssue', 'withTitle', 'addToBody', 'defer', 'now'])
            ->getMock();
        foreach (['makeInline', 'asSuccess', 'asIssue', 'withTitle', 'addToBody', 'defer'] as $method) {
            $alert->method($method)->willReturnSelf();
        }
        // Stop at the error response, before unrelated form rendering.
        $alert->method('now')->willThrowException(new TemplateFixtureValidationFailure());
        ee()->setMock('CP/Alert', $alert);
        ee()->setMock('extensions', new class {
            public function active_hook($name) { return false; }
        });
        ee()->setMock('Emoji', new \ExpressionEngine\Library\Emoji\Emoji());
        ee()->setMock('Filesystem', new Filesystem(
            new \ExpressionEngine\Library\Filesystem\Adapter\Local(['path' => $this->root])
        ));
        ee()->setMock('legacy_api', new class {
            public function instantiate($name) {}
        });
        ee()->setMock('api_template_structure',
            (new \ReflectionClass(\Api_template_structure::class))->newInstanceWithoutConstructor());
        ee()->setMock('uri', (object) ['uri_string' => '', 'page_query_string' => '']);

        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $database = $this->getMockBuilder(Database::class)->disableOriginalConstructor()
            ->onlyMethods(['newQuery'])->getMock();
        $database->method('newQuery')->willReturnCallback(function () {
            return new TemplateFixtureQuery($this->pdo);
        });
        $setup = require SYSPATH . 'ee/ExpressionEngine/app.setup.php';
        $aliases = [];
        foreach ($setup['models'] as $name => $class) {
            $aliases['ee:' . $name] = $class[0] === '\\' ? $class : 'ExpressionEngine\\' . $class;
        }
        $configuration = new Configuration();
        $configuration->setDefaultPrefix('ee');
        $configuration->setEnabledPrefixes(['ee']);
        $configuration->setModelAliases($aliases);
        $store = new DataStore($database, $configuration);
        $this->models = new Facade($store);
        $this->models->setValidationFactory(new Factory());
        ee()->setMock('Model', $this->models);

        foreach (['Site', 'TemplateGroup', 'Template', 'TemplateRoute', 'RevisionTracker', 'Role'] as $name) {
            $metadata = $store->getMetaDataReader($name);
            foreach ($metadata->getTables() as $table => $fields) {
                $columns = [];
                foreach ($fields as $field) {
                    $columns[] = '`' . $field . '` ' . ($field === $metadata->getPrimaryKey()
                        ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'TEXT');
                }
                $this->pdo->exec('CREATE TABLE exp_' . $table . ' (' . implode(', ', $columns) . ')');
            }
        }
        $this->pdo->exec('CREATE TABLE exp_templates_roles (template_id INTEGER, role_id INTEGER)');
        $this->insert('roles', ['role_id' => 3, 'name' => 'Fixture reader']);
        $this->insert('sites', ['site_id' => 1, 'site_name' => 'fixture']);
        $this->insert('sites', ['site_id' => 2, 'site_name' => 'second']);
        foreach ([10 => 'source', 20 => 'unassigned', 30 => 'read_only'] as $id => $name) {
            $this->insert('template_groups', ['group_id' => $id, 'site_id' => 1, 'group_name' => $name]);
            mkdir($this->root . 'fixture/' . $name . '.group', 0777, true);
        }
        foreach ([12 => [10, 'example'], 13 => [10, 'taken'], 22 => [20, 'example'], 32 => [30, 'example']] as $id => $parts) {
            $data = 'Original fixture ' . $id . '.';
            $this->insert('templates', [
                'template_id' => $id, 'site_id' => 1, 'group_id' => $parts[0],
                'template_name' => $parts[1], 'template_type' => 'webpage',
                'template_data' => $data, 'edit_date' => 1234567800,
                'last_author_id' => 10, 'cache' => 'n', 'refresh' => 0,
                'hits' => 0, 'enable_http_auth' => 'n', 'allow_php' => 'n',
                'php_parse_location' => 'o', 'protect_javascript' => 'n', 'enable_frontedit' => 'n',
            ]);
            $this->insert('templates_roles', ['template_id' => $id, 'role_id' => 3]);
            $this->insert('revision_tracker', [
                'item_id' => $id, 'item_table' => 'exp_templates', 'item_field' => 'template_data',
                'item_data' => $data, 'item_date' => 1234567800, 'item_author_id' => 10,
            ]);
            $group = [10 => 'source', 20 => 'unassigned', 30 => 'read_only'][$parts[0]];
            $path = $this->root . 'fixture/' . $group . '.group/' . $parts[1] . '.html';
            file_put_contents($path, $data);
            touch($path, 1234567800);
        }

        // The CP constructor's session setup is outside this isolated fixture;
        // each public action still executes its own group/permission checks.
        $this->controller = (new \ReflectionClass(Template::class))->newInstanceWithoutConstructor();
        $assigned = new \ReflectionProperty($this->controller, 'assigned_template_groups');
        \TestReflectionHelper::makeAccessible($assigned);
        $assigned->setValue($this->controller, [10, 30]);
    }

    protected function tearDown(): void
    {
        if ($this->root && is_dir($this->root)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->root);
        }
        $_POST = [];
        ee()->resetMocks();
        ee()->session->resetUserdata();
    }

    /** @dataProvider editProvider */
    public function testEditsPersistOnlyToTheControllerSelectedTemplate($action, $sync, $identifiers, $name)
    {
        $this->config->items['save_tmpl_files'] = $sync;
        $before = $this->state();
        $_POST = $identifiers + [
            'template_data' => 'Updated fixture text.', 'orig_route' => '', 'save_modal' => 'y',
            'allow_php' => 'y', 'php_parse_location' => 'i',
        ];
        if ($name !== null) {
            $_POST['template_name'] = $name;
        }
        $this->submit($action, 12);
        $after = $this->state();

        // Assert effects before model properties, so a collision is observable.
        $this->assertSame($before['output'], $after['output'], 'Destination fixture output');
        $this->assertSame($before['protected'], $after['protected'], 'Destination rows and revisions');
        $this->assertCount(count($before['rows']), $after['rows'], 'No additional template rows');
        $row = $this->rows('templates', 'template_id = 12')[0];
        $this->assertSame(10, (int) $row['group_id']);
        $this->assertSame(1, (int) $row['site_id']);
        $this->assertSame($name ?? 'example', $row['template_name']);
        $this->assertSame('Updated fixture text.', $row['template_data']);
        $this->assertSame('n', $row['allow_php']);
        $this->assertSame('o', $row['php_parse_location']);
        $this->assertSame(10, (int) $row['last_author_id']);
        $this->assertSame(1234567890, (int) $row['edit_date']);

        $expectedFiles = $before['files'];
        if ($sync === 'y') {
            unset($expectedFiles['fixture/source.group/example.html']);
            $expectedFiles['fixture/source.group/' . ($name ?? 'example') . '.html'] = 'Updated fixture text.';
        }
        ksort($expectedFiles);
        $this->assertSame($expectedFiles, $after['files'], 'Only the authorized file may change');
        $this->assertSame('Updated fixture text.', $this->render('source', $name ?? 'example'));
        $revisions = $this->rows('revision_tracker', 'item_id = 12');
        $this->assertCount($action === 'edit' ? 2 : 1, $revisions);
        if ($action === 'edit') {
            $this->assertSame('Updated fixture text.', $revisions[1]['item_data']);
        }
        $this->assertSame($identifiers, array_intersect_key($_POST, $identifiers));
    }

    public static function editProvider(): array
    {
        return [
            'edit collision' => ['edit', 'y', ['group_id' => '20'], 'example'],
            'edit omitted name, no sync' => ['edit', 'n', ['group_id' => '20'], null],
            'edit assigned without permission' => ['edit', 'y', ['group_id' => '30'], null],
            'edit renamed, no sync' => ['edit', 'n', ['group_id' => '30'], 'renamed'],
            'settings omitted name' => ['settings', 'y', ['group_id' => '20'], null],
            'settings renamed, no sync' => ['settings', 'n', ['group_id' => '20'], 'renamed'],
            'settings assigned collision' => ['settings', 'y', ['group_id' => '30'], 'example'],
            'settings assigned, no sync' => ['settings', 'n', ['group_id' => '30'], null],
            'edit site only' => ['edit', 'y', ['site_id' => '2'], 'example'],
            'settings site only' => ['settings', 'n', ['site_id' => '2'], null],
            'edit identity only' => ['edit', 'n', ['template_id' => '22'], null],
            'settings identity only' => ['settings', 'y', ['template_id' => '22'], 'example'],
            'ordinary edit with sync' => ['edit', 'y', [], 'example'],
            'ordinary edit without sync' => ['edit', 'n', [], null],
            'ordinary settings with rename' => ['settings', 'y', [], 'renamed'],
            'ordinary settings without sync' => ['settings', 'n', [], 'example'],
        ];
    }

    /** @dataProvider createProvider */
    public function testCreationAndDuplicationKeepTheSelectedGroupAndGetANewIdentity($sync, $duplicate, $submittedIds)
    {
        $this->config->items['save_tmpl_files'] = $sync;
        $before = $this->state();
        $_POST = $submittedIds + ['template_name' => 'created', 'template_type' => 'webpage'];
        if ($duplicate) {
            $_POST['template_id'] = '12';
        } else {
            $_POST['template_data'] = 'New fixture text.';
        }
        $this->submit('create', 'source');
        $after = $this->state();
        $this->assertSame($before['protected'], $after['protected']);
        $this->assertSame($before['output'], $after['output']);
        $this->assertSame($before['rows'], $this->rows('templates', 'template_id <= 32'));
        $this->assertSame($before['revisions'], $after['revisions']);
        $this->assertCount(count($before['rows']) + 1, $after['rows']);
        $created = $this->rows('templates', 'template_id > 32');
        $this->assertCount(1, $created);
        $this->assertSame(10, (int) $created[0]['group_id']);
        $this->assertSame(1, (int) $created[0]['site_id']);
        $this->assertSame('n', $created[0]['allow_php']);
        $this->assertSame('o', $created[0]['php_parse_location']);
        $content = $duplicate ? 'Original fixture 12.' : 'New fixture text.';
        $this->assertSame($content, $created[0]['template_data']);
        $this->assertSame($content, $this->render('source', 'created'));
        $expectedFiles = $before['files'];
        if ($sync === 'y') {
            $expectedFiles['fixture/source.group/created.html'] = $content;
        }
        ksort($expectedFiles);
        $this->assertSame($expectedFiles, $after['files']);
        if ($duplicate) {
            $this->assertSame('12', $_POST['template_id'], 'Duplication selector stays available');
        }
    }

    public static function createProvider(): array
    {
        $cases = [];
        foreach (['y', 'n'] as $sync) {
            foreach ([false, true] as $duplicate) {
                $label = ($duplicate ? 'duplicate' : 'create') . ' sync=' . $sync;
                $cases[$label] = [$sync, $duplicate, ['group_id' => '20', 'site_id' => '2']];
                $cases['ordinary ' . $label] = [$sync, $duplicate, []];
            }
        }
        return $cases;
    }

    /** @dataProvider deniedProvider */
    public function testDirectUnauthorizedActionsHaveNoEffects($action, $target)
    {
        $before = $this->state();
        $_POST = ['template_name' => 'unused', 'template_data' => 'Unapplied fixture text.'];
        try {
            $this->controller->$action($target);
            $this->fail('Expected the controller permission check to deny access.');
        } catch (\RuntimeException $error) {
            $this->assertSame(403, $error->getCode());
            $this->assertSame('unauthorized_access', $error->getMessage());
        }
        $this->assertSame($before, $this->state());
    }

    public static function deniedProvider(): array
    {
        return [
            ['edit', 22], ['edit', 32], ['settings', 22], ['settings', 32],
            ['create', 'unassigned'], ['create', 'read_only'],
        ];
    }

    /** @dataProvider invalidNameProvider */
    public function testNormalNameValidationPreventsPersistence($action, $name)
    {
        $before = $this->state();
        $_POST = ['template_name' => $name, 'template_type' => 'webpage', 'template_data' => 'Unapplied fixture text.'];
        try {
            $this->controller->$action($action === 'create' ? 'source' : 12);
            $this->fail('Expected real model validation to reject this name.');
        } catch (TemplateFixtureValidationFailure $error) {
            $this->assertSame($before, $this->state());
        }
    }

    public static function invalidNameProvider(): array
    {
        return [['edit', 'taken'], ['edit', 'two words'], ['settings', 'css'], ['create', '']];
    }

    private function submit($action, $target): void
    {
        try {
            $result = $this->controller->$action($target);
            $this->assertSame(['messageType' => 'success'], $result);
        } catch (TemplateFixtureRedirect $redirect) {
            $this->assertContains($action, ['edit', 'create']);
        }
    }

    private function insert($table, array $values): void
    {
        (new TemplateFixtureQuery($this->pdo))->insert($table, $values);
    }

    private function rows($table, $where = '1 = 1'): array
    {
        return $this->pdo->query('SELECT * FROM exp_' . $table . ' WHERE ' . $where . ' ORDER BY 1')
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Capture template state with consistent file paths across platforms.
     *
     * @return array
     */
    private function state(): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $path = str_replace('\\', '/', substr($file->getPathname(), strlen($this->root)));
                $files[$path] = file_get_contents($file->getPathname());
            }
        }
        ksort($files);
        return [
            'rows' => $this->rows('templates'),
            'revisions' => $this->rows('revision_tracker'),
            'protected' => [
                $this->rows('templates', 'template_id IN (13, 22, 32)'),
                $this->rows('revision_tracker', 'item_id != 12'),
                $this->rows('templates_roles', 'template_id IN (13, 22, 32)'),
            ],
            'files' => $files,
            'output' => [$this->render('unassigned', 'example'), $this->render('read_only', 'example')],
        ];
    }

    private function render($group, $name): string
    {
        // Literal text needs no tag execution. Use the real frontend selection,
        // role checks and file reader, with no cached query hiding a change.
        $this->session->cache = [];
        ee()->setMock('db', new TemplateFixtureQuery($this->pdo));
        $renderer = (new \ReflectionClass(\EE_Template::class))->newInstanceWithoutConstructor();
        $renderer->debugging = false;
        return $renderer->fetch_template($group, $name, false, 1);
    }
}
