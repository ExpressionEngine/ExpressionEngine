<?php

namespace {
    if (! defined('DEBUG')) {
        define('DEBUG', 0);
    }

    if (! defined('IS_CORE')) {
        define('IS_CORE', false);
    }

    if (! function_exists('show_error')) {
        function show_error($message, $status = 500)
        {
            throw new \RuntimeException((string) $message, (int) $status);
        }
    }

    if (! function_exists('reduce_double_slashes')) {
        function reduce_double_slashes($str)
        {
            return preg_replace('#(^|[^:])//+#', '$1/', $str);
        }
    }

    if (! class_exists('EE_Config')) {
        class EE_Config
        {
            public $config = [];
            public $default_ini = [];
            public $_global_vars = [];
            public $trackingDisabled = false;

            public function __construct()
            {
            }

            public function set_item($key, $value)
            {
                $this->config[$key] = $value;
            }

            public function item($key, $index = '', $raw_value = false)
            {
                return $this->config[$key] ?? false;
            }

            public function disable_tracking()
            {
                $this->trackingDisabled = true;
            }

            public function site_pages($siteId, $data)
            {
                return [
                    $siteId => ['uris' => []],
                ];
            }
        }
    }

}

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Core {

use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/installer/core/Installer_Config.php';

class InstallerConfigQueryResultStub
{
    private $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function num_rows()
    {
        return count($this->rows);
    }

    public function row_array()
    {
        return $this->rows[0] ?? [];
    }

    public function result_array()
    {
        return $this->rows;
    }
}

class InstallerConfigDbStub
{
    public $selectCalls = [];
    public $getCalls = [];
    public $getWhereCalls = [];
    public $whereCalls = [];
    public $updateCalls = [];
    public $save_queries = false;
    public $queue = [];

    public function select($fields)
    {
        $this->selectCalls[] = $fields;
    }

    public function get($table)
    {
        $this->getCalls[] = $table;

        return new InstallerConfigQueryResultStub(array_shift($this->queue) ?? []);
    }

    public function get_where($table, $where)
    {
        $this->getWhereCalls[] = [$table, $where];

        return new InstallerConfigQueryResultStub(array_shift($this->queue) ?? []);
    }

    public function where($key, $value)
    {
        $this->whereCalls[] = [$key, $value];
    }

    public function update($table, $data)
    {
        $this->updateCalls[] = [$table, $data];
    }
}

class InstallerConfigTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testInitializeLoadsConfigAndAppliesOverrides()
    {
        $configFile = $this->createTempConfigFile([
            'site_url' => 'https://example.test',
            'site_index' => 'index.php',
            'template_group' => 'news',
            'global_vars' => ['foo' => 'bar'],
        ]);

        $config = $this->newInstallerConfigWithoutConstructor();
        $config->config_path = $configFile;

        $config->_initialize();

        $this->assertSame('https://example.test', $config->config['site_url']);
        $this->assertSame('index.php', $config->config['site_index']);
        $this->assertSame('news', $config->config['template_group']);
        $this->assertSame(['foo' => 'bar'], $config->_global_vars);
        $this->assertSame(true, $config->config['enable_query_strings']);
        $this->assertSame('Installer_', $config->config['subclass_prefix']);
        $this->assertArrayHasKey('site_url', $config->config);
    }

    public function testInitializeReturnsFalseForEmptyConfig()
    {
        $config = $this->newInstallerConfigWithoutConstructor();
        $config->config_path = $this->createTempConfigFile([]);

        $this->assertFalse($config->_initialize());
    }

    public function testSetOverridesStoresExceptionsWhenCpRequest()
    {
        if (! defined('REQ')) {
            define('REQ', 'CP');
        }

        $config = $this->newInstallerConfigWithoutConstructor();
        $config->config = [];

        $config->_set_overrides([
            'site_url' => 'https://cp.test',
            'site_index' => 'admin.php',
            'template_group' => 'pages',
            'global_vars' => ['alpha' => 'beta'],
        ]);

        $this->assertSame(['alpha' => 'beta'], $config->_global_vars);
        $this->assertArrayHasKey('site_url', $config->exceptions);
        $this->assertArrayNotHasKey('site_url', $config->config);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetOverridesAppliesValuesWhenRequestIsNotCp()
    {
        $config = $this->newInstallerConfigWithoutConstructor();
        $config->config = [];

        $config->_set_overrides([
            'site_url' => 'https://front-end.test',
            'site_index' => 'index.php',
        ]);

        $this->assertSame('https://front-end.test', $config->config['site_url']);
        $this->assertSame('index.php', $config->config['site_index']);
    }

    public function testSetOverridesReturnsEarlyWhenParamsAreInvalid()
    {
        $config = $this->newInstallerConfigWithoutConstructor();
        $config->_global_vars = ['existing' => 'value'];

        $config->_set_overrides('not-an-array');

        $this->assertSame(['existing' => 'value'], $config->_global_vars);
    }

    public function testInstallerConfigRemoveConfigItemRemovesKeysAndUpdatesChangedRows()
    {
        $db = new InstallerConfigDbStub();
        $db->queue[] = [
            [
                'site_id' => 1,
                'site_system_preferences' => base64_encode(serialize(['keep' => 1, 'remove_me' => 'yes'])),
                'site_member_preferences' => base64_encode(serialize(['keep' => 1])),
                'site_template_preferences' => base64_encode(serialize(['remove_me' => 'yes'])),
                'site_channel_preferences' => base64_encode(serialize([])),
            ],
            [
                'site_id' => 2,
                'site_system_preferences' => base64_encode(serialize(['keep' => 1])),
                'site_member_preferences' => base64_encode(serialize(['keep' => 1])),
                'site_template_preferences' => base64_encode(serialize([])),
                'site_channel_preferences' => base64_encode(serialize([])),
            ],
        ];
        ee()->setMock('db', $db);

        $config = $this->newInstallerConfigWithoutConstructor();
        $config->remove_config_item('remove_me');

        $this->assertSame(['site_system_preferences, site_member_preferences, site_template_preferences, site_channel_preferences, site_id'], $db->selectCalls);
        $this->assertSame(['sites'], $db->getCalls);
        $this->assertCount(1, $db->updateCalls);
        $this->assertSame([['site_id', 1]], $db->whereCalls);
    }

    public function testMsmConfigRemoveConfigItemAlsoUpdatesChangedRows()
    {
        $db = new InstallerConfigDbStub();
        $db->queue[] = [
            [
                'site_id' => 10,
                'site_system_preferences' => base64_encode(serialize(['remove_me' => 'yes'])),
                'site_member_preferences' => base64_encode(serialize([])),
                'site_template_preferences' => base64_encode(serialize([])),
                'site_channel_preferences' => base64_encode(serialize([])),
            ],
        ];
        ee()->setMock('db', $db);

        $config = $this->newMsmConfigWithoutConstructor();
        $config->remove_config_item('remove_me');

        $this->assertCount(1, $db->updateCalls);
        $this->assertSame([['site_id', 10]], $db->whereCalls);
    }

    public function testSitePrefsLoadsSiteByNameAndAppliesPreferences()
    {
        $this->ensureEeAppPathDefined();

        $db = new InstallerConfigDbStub();
        $db->queue[] = [[
            'site_id' => 2,
            'site_name' => 'mysite',
            'site_label' => 'My Site',
            'site_system_preferences' => base64_encode(serialize([
                'site_url' => 'https://mysite.test',
                'site_index' => 'index.php',
                'charset' => 'UTF-8',
                'disable_all_tracking' => 'y',
            ])),
            'site_member_preferences' => base64_encode(serialize([])),
            'site_template_preferences' => base64_encode(serialize([])),
            'site_channel_preferences' => base64_encode(serialize([])),
            'site_pages' => 'serialized-pages',
            'site_bootstrap_checksums' => base64_encode(serialize(['hash' => 'abc'])),
            'sites_custom' => 'custom-value',
        ]];
        ee()->setMock('db', $db);

        $config = $this->newMsmConfigWithoutConstructor();
        $config->default_ini = [
            'multiple_sites_enabled' => 'y',
            'show_profiler' => 'n',
            'charset' => 'UTF-8',
        ];
        ee()->setMock('config', $config);

        $config->site_prefs('mysite', 2);

        $this->assertSame([['sites', ['site_name' => 'mysite']]], $db->getWhereCalls);
        $this->assertSame('mysite', $config->config['site_short_name']);
        $this->assertSame('My Site', $config->config['site_name']);
        $this->assertSame('custom-value', $config->config['site_custom']);
        $this->assertSame('utf-8', $config->config['output_charset']);
        $this->assertSame('https://mysite.test/index.php/', $config->config['site_pages'][2]['url']);
        $this->assertTrue($config->trackingDisabled);
        $this->assertFalse($db->save_queries);
    }

    public function testSitePrefsFallsBackToSiteIdOneWhenMsmsUnavailable()
    {
        $this->ensureEeAppPathDefined();

        $db = new InstallerConfigDbStub();
        $db->queue[] = [[
            'site_id' => 1,
            'site_name' => 'default',
            'site_label' => 'Default',
            'site_system_preferences' => base64_encode(serialize([
                'site_url' => 'https://default.test',
                'site_index' => 'index.php',
                'charset' => 'UTF-8',
            ])),
            'site_member_preferences' => base64_encode(serialize([])),
            'site_template_preferences' => base64_encode(serialize([])),
            'site_channel_preferences' => base64_encode(serialize([])),
            'site_pages' => 'pages',
            'site_bootstrap_checksums' => base64_encode(serialize([])),
        ]];
        ee()->setMock('db', $db);

        $config = $this->newMsmConfigWithoutConstructor();
        $config->default_ini = ['multiple_sites_enabled' => 'n', 'charset' => 'UTF-8'];
        ee()->setMock('config', $config);

        $config->site_prefs('other', 9);

        $this->assertSame([['sites', ['site_id' => 1]]], $db->getWhereCalls);
    }

    public function testSitePrefsRecursesToDefaultSiteWhenRequestedSiteIdMissing()
    {
        $this->ensureEeAppPathDefined();

        $db = new InstallerConfigDbStub();
        $db->queue[] = [];
        $db->queue[] = [[
            'site_id' => 1,
            'site_name' => 'default',
            'site_label' => 'Default',
            'site_system_preferences' => base64_encode(serialize([
                'site_url' => 'https://default.test',
                'site_index' => 'index.php',
                'charset' => 'UTF-8',
            ])),
            'site_member_preferences' => base64_encode(serialize([])),
            'site_template_preferences' => base64_encode(serialize([])),
            'site_channel_preferences' => base64_encode(serialize([])),
            'site_pages' => 'pages',
            'site_bootstrap_checksums' => base64_encode(serialize([])),
        ]];
        ee()->setMock('db', $db);

        $config = $this->newMsmConfigWithoutConstructor();
        $config->default_ini = ['multiple_sites_enabled' => 'y', 'charset' => 'UTF-8'];
        ee()->setMock('config', $config);

        $config->site_prefs('', 5);

        $this->assertSame(
            [
                ['sites', ['site_id' => 5]],
                ['sites', ['site_id' => 1]],
            ],
            $db->getWhereCalls
        );
    }

    public function testSitePrefsSetsEmptyChecksumsWhenChecksumPayloadIsInvalid()
    {
        $this->ensureEeAppPathDefined();

        $db = new InstallerConfigDbStub();
        $db->queue[] = [[
            'site_id' => 1,
            'site_name' => 'default',
            'site_label' => 'Default',
            'site_system_preferences' => base64_encode(serialize([
                'site_url' => 'https://default.test',
                'site_index' => 'index.php',
                'charset' => 'UTF-8',
            ])),
            'site_member_preferences' => base64_encode(serialize([])),
            'site_template_preferences' => base64_encode(serialize([])),
            'site_channel_preferences' => base64_encode(serialize([])),
            'site_pages' => 'pages',
            'site_bootstrap_checksums' => 'invalid',
        ]];
        ee()->setMock('db', $db);

        $config = $this->newMsmConfigWithoutConstructor();
        $config->default_ini = ['multiple_sites_enabled' => 'y', 'charset' => 'UTF-8'];
        ee()->setMock('config', $config);

        $config->site_prefs('', 1);

        $this->assertSame([], $config->config['site_bootstrap_checksums']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitializeThrowsWhenConfigFileCannotBeIncludedInSeparateProcess()
    {
        $config = $this->newInstallerConfigWithoutConstructor();
        $config->config_path = sys_get_temp_dir() . '/missing-installer-config-' . uniqid('', true) . '.php';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to locate your config file');
        $config->_initialize();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSitePrefsThrowsWhenNoPreferencesFoundInSeparateProcess()
    {
        $this->ensureEeAppPathDefined();

        $db = new InstallerConfigDbStub();
        $db->queue[] = [];
        ee()->setMock('db', $db);

        $config = $this->newMsmConfigWithoutConstructor();
        $config->default_ini = ['multiple_sites_enabled' => 'y', 'charset' => 'UTF-8'];
        ee()->setMock('config', $config);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No Preferences Found');
        $config->site_prefs('mysite', 1);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSitePrefsThrowsWhenPreferencePayloadIsInvalidInSeparateProcess()
    {
        $this->ensureEeAppPathDefined();

        $db = new InstallerConfigDbStub();
        $db->queue[] = [[
            'site_id' => 1,
            'site_name' => 'default',
            'site_label' => 'Default',
            'site_system_preferences' => 'invalid',
            'site_member_preferences' => base64_encode(serialize([])),
            'site_template_preferences' => base64_encode(serialize([])),
            'site_channel_preferences' => base64_encode(serialize([])),
            'site_pages' => 'pages',
            'site_bootstrap_checksums' => base64_encode(serialize([])),
        ]];
        ee()->setMock('db', $db);

        $config = $this->newMsmConfigWithoutConstructor();
        $config->default_ini = ['multiple_sites_enabled' => 'y', 'charset' => 'UTF-8'];
        ee()->setMock('config', $config);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid Preference Data');
        $config->site_prefs('', 1);
    }

    private function createTempConfigFile(array $config): string
    {
        $file = sys_get_temp_dir() . '/installer-config-' . uniqid('', true) . '.php';
        file_put_contents($file, "<?php\n\$config = " . var_export($config, true) . ";\n");

        return $file;
    }

    private function newInstallerConfigWithoutConstructor(): \Installer_Config
    {
        $reflection = new \ReflectionClass(\Installer_Config::class);

        return $reflection->newInstanceWithoutConstructor();
    }

    private function newMsmConfigWithoutConstructor(): \MSM_Config
    {
        $reflection = new \ReflectionClass(\MSM_Config::class);

        return $reflection->newInstanceWithoutConstructor();
    }

    private function ensureEeAppPathDefined(): void
    {
        if (! defined('EE_APPPATH')) {
            define('EE_APPPATH', APPPATH);
        }
    }
}
}
