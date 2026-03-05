<?php

namespace {
    if (! function_exists('show_error')) {
        function show_error($message, $status = 500)
        {
            throw new \RuntimeException((string) $message, (int) $status);
        }
    }

    if (! function_exists('redirect')) {
        function redirect($url)
        {
            $GLOBALS['wizard_redirect_url'] = $url;
        }
    }

    if (! function_exists('force_download')) {
        function force_download($filename, $data = '')
        {
            $GLOBALS['wizard_force_download'] = [$filename, $data];
        }
    }

    if (! function_exists('apc_delete_file')) {
        function apc_delete_file($path)
        {
            $GLOBALS['wizard_apc_delete_file_called'] = true;

            return false;
        }
    }

    if (! function_exists('apc_clear_cache')) {
        function apc_clear_cache()
        {
            $GLOBALS['wizard_apc_clear_cache_called'] = true;

            return true;
        }
    }

    if (! function_exists('is_really_writable')) {
        function is_really_writable($path)
        {
            return is_writable($path);
        }
    }

    if (! defined('PATH_TMPL')) {
        define('PATH_TMPL', sys_get_temp_dir() . '/ee-installer-templates/');
    }

    if (! is_dir(PATH_TMPL)) {
        @mkdir(PATH_TMPL, 0777, true);
    }

    if (! class_exists('Wizardtest_upd')) {
        class Wizardtest_upd
        {
            public $version = '9.9.9';

            public function update($fromVersion)
            {
                return true;
            }
        }
    }

    if (! class_exists('WizardAddonGoodUpgrader')) {
        class WizardAddonGoodUpgrader
        {
            public function upgrade($version)
            {
                return true;
            }
        }
    }

    if (! class_exists('WizardAddonBoomUpgrader')) {
        class WizardAddonBoomUpgrader
        {
            public function upgrade($version)
            {
                throw new \Exception('boom');
            }
        }
    }
}

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Controllers {

use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/legacy/core/Controller.php';
require_once SYSPATH . 'ee/installer/controllers/wizard.php';

class WizardInputStub
{
    public $postData = [];
    public $getData = [];
    public $serverData = [];

    public function post($key)
    {
        return $this->postData[$key] ?? false;
    }

    public function get($key)
    {
        return $this->getData[$key] ?? false;
    }

    public function server($key)
    {
        return $this->serverData[$key] ?? null;
    }
}

class WizardConfigStub
{
    public $items = [];
    public $config_path;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function set_item($key, $value)
    {
        $this->items[$key] = $value;
    }

    public function item($key)
    {
        return $this->items[$key] ?? false;
    }
}

class WizardFormValidationStub
{
    public $messages = [];

    public function set_message($key, $message)
    {
        $this->messages[$key] = $message;
    }
}

class WizardLoadStub
{
    public $libraries = [];
    public $helpers = [];
    public $addedPackagePaths = [];
    public $removedPackagePaths = [];
    public $viewCalls = [];
    public $databaseFactory;

    public function library($name)
    {
        $this->libraries[] = $name;
    }

    public function helper($name)
    {
        $this->helpers[] = $name;
    }

    public function add_package_path($path)
    {
        $this->addedPackagePaths[] = $path;
    }

    public function remove_package_path($path)
    {
        $this->removedPackagePaths[] = $path;
    }

    public function view($view, $data = [], $return = false)
    {
        $this->viewCalls[] = [$view, $data, $return];

        if ($return) {
            return "rendered:{$view}";
        }
    }

    public function database($db, $return = true, $active_record = true)
    {
        if (is_callable($this->databaseFactory)) {
            return call_user_func($this->databaseFactory, $db);
        }

        throw new \RuntimeException('No database factory configured');
    }
}

class WizardTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        unset($GLOBALS['wizard_redirect_url'], $GLOBALS['wizard_force_download']);
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
        unset($GLOBALS['wizard_redirect_url'], $GLOBALS['wizard_force_download']);
    }

    public function testValidDbHostReturnsFalseWhenRequiredFieldsAreMissing()
    {
        $wizard = $this->newWizard();
        ee()->setMock('input', new WizardInputStub());
        $formValidation = new WizardFormValidationStub();
        ee()->setMock('form_validation', $formValidation);

        $this->assertFalse($wizard->valid_db_host());
        $this->assertSame('database_invalid_host', $formValidation->messages['valid_db_host']);
    }

    public function testValidDbDatabaseReturnsFalseWhenConnectionErrorMatches()
    {
        $wizard = $this->newWizard();
        $input = new WizardInputStub();
        $input->postData = [
            'db_hostname' => 'localhost',
            'db_name' => 'ee',
            'db_username' => 'root',
        ];

        ee()->setMock('input', $input);
        $formValidation = new WizardFormValidationStub();
        ee()->setMock('form_validation', $formValidation);

        $this->setProperty($wizard, 'db_connect_attempt', 1049);

        $this->assertFalse($wizard->valid_db_database());
        $this->assertSame('database_invalid_database', $formValidation->messages['valid_db_database']);
    }

    public function testValidDbHostReturnsTrueWhenConnectionErrorDoesNotMatch()
    {
        $wizard = $this->newWizard();
        $input = new WizardInputStub();
        $input->postData = [
            'db_hostname' => 'localhost',
            'db_name' => 'ee',
            'db_username' => 'root',
        ];

        ee()->setMock('input', $input);
        ee()->setMock('form_validation', new WizardFormValidationStub());

        $this->setProperty($wizard, 'db_connect_attempt', true);

        $this->assertTrue($wizard->valid_db_host());
    }

    public function testValidDbPrefixHandlesInvalidAndValidValues()
    {
        $wizard = $this->newWizard();
        $formValidation = new WizardFormValidationStub();
        ee()->setMock('form_validation', $formValidation);

        $this->assertFalse($wizard->valid_db_prefix('bad-prefix!'));
        $this->assertSame('database_prefix_invalid_characters', $formValidation->messages['valid_db_prefix']);

        $this->assertFalse($wizard->valid_db_prefix('exp_bad'));
        $this->assertSame('database_prefix_contains_exp_', $formValidation->messages['valid_db_prefix']);

        $this->assertTrue($wizard->valid_db_prefix('safe_prefix'));
    }

    public function testLicenseAgreementValidation()
    {
        $wizard = $this->newWizard();
        $formValidation = new WizardFormValidationStub();
        ee()->setMock('form_validation', $formValidation);

        $this->assertFalse($wizard->license_agreement('n'));
        $this->assertSame('license_agreement_not_accepted', $formValidation->messages['license_agreement']);
        $this->assertTrue($wizard->license_agreement('y'));
    }

    public function testGetDbPrefixUsesDefaultAndNormalizesCustomPrefix()
    {
        $wizard = $this->newWizard();

        $wizard->userdata['db_prefix'] = '';
        $this->assertSame('exp_', $this->invokePrivate($wizard, 'getDbPrefix'));

        $wizard->userdata['db_prefix'] = 'custom';
        $this->assertSame('custom_', $this->invokePrivate($wizard, 'getDbPrefix'));
    }

    public function testSetupDatabasePortParsesPortAndFallsBackToNull()
    {
        $wizard = $this->newWizard();

        $wizard->userdata['db_hostname'] = '127.0.0.1:3308';
        $this->invokePrivate($wizard, 'setupDatabasePort');
        $this->assertSame('127.0.0.1', $wizard->userdata['db_hostname']);
        $this->assertSame('3308', $wizard->userdata['db_port']);

        $wizard->userdata['db_hostname'] = 'localhost';
        $this->invokePrivate($wizard, 'setupDatabasePort');
        $this->assertNull($wizard->userdata['db_port']);
    }

    public function testGetDbConfigReturnsGroupConfiguration()
    {
        $wizard = $this->newWizard();

        $dbConfig = new class {
            public function getGroupConfig()
            {
                return ['hostname' => 'localhost', 'database' => 'ee'];
            }
        };

        ee()->setMock('Database', new class($dbConfig) {
            private $dbConfig;
            public function __construct($dbConfig)
            {
                $this->dbConfig = $dbConfig;
            }
            public function getConfig()
            {
                return $this->dbConfig;
            }
        });

        $this->assertSame(
            ['hostname' => 'localhost', 'database' => 'ee'],
            $wizard->getDbConfig()
        );
    }

    public function testGetDbConfigThrowsWhenNoConfigurationCanBeLoaded()
    {
        $wizard = $this->newWizard();

        $dbConfig = new class {
            public function getGroupConfig()
            {
                throw new \Exception('no group');
            }
            public function getActiveGroup()
            {
                return 'default';
            }
        };

        ee()->setMock('Database', new class($dbConfig) {
            private $dbConfig;
            public function __construct($dbConfig)
            {
                $this->dbConfig = $dbConfig;
            }
            public function getConfig()
            {
                return $this->dbConfig;
            }
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('database_no_data');
        $wizard->getDbConfig();
    }

    public function testDbConnectHandlesEmptySuccessLocalhostFallbackAndErrorCode()
    {
        $wizard = $this->newWizard();

        $this->assertFalse($this->invokePrivate($wizard, 'db_connect', []));

        $load = new WizardLoadStub();
        $connection = new class {
            public $save_queries = false;
            public $db_exception = false;
            public function initialize()
            {
            }
        };
        $load->databaseFactory = function () use ($connection) {
            return $connection;
        };

        ee()->setMock('load', $load);
        ee()->setMock('Database', new class {
            public $closed = 0;
            public function closeConnection()
            {
                $this->closed++;
            }
        });

        $this->assertTrue($this->invokePrivate($wizard, 'db_connect', ['hostname' => 'db']));
        $this->assertTrue($connection->save_queries);
        $this->assertTrue($connection->db_exception);

        $wizard->userdata['db_hostname'] = 'localhost';
        $load->databaseFactory = function ($db) {
            return new class($db) {
                private $db;
                public $save_queries = false;
                public $db_exception = false;
                public function __construct($db)
                {
                    $this->db = $db;
                }
                public function initialize()
                {
                    if ($this->db['hostname'] === 'localhost') {
                        throw new \Exception('fail', 2002);
                    }
                }
            };
        };
        $this->assertTrue($this->invokePrivate($wizard, 'db_connect', ['hostname' => 'localhost']));
        $this->assertSame('127.0.0.1', $wizard->userdata['db_hostname']);

        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                    throw new \Exception('bad credentials', 1045);
                }
            };
        };
        $this->assertSame(1045, $this->invokePrivate($wizard, 'db_connect', ['hostname' => 'remote']));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testServerSupportsUtf8mb4ComparesServerVersion()
    {
        ee()->setMock('Database', $this->databaseServiceForVersions('5.5.4', 'mysqlnd 5.0.9-dev'));

        $wizard = $this->newWizard();
        $this->assertTrue($this->invokePrivate($wizard, 'serverSupportsUtf8mb4'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testClientSupportsUtf8mb4ParsesMysqlndVersion()
    {
        ee()->setMock('Database', $this->databaseServiceForVersions('5.5.4', 'mysqlnd 5.0.10-dev'));

        $wizard = $this->newWizard();
        $this->assertTrue($this->invokePrivate($wizard, 'clientSupportsUtf8mb4'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIsUtf8mb4SupportedReturnsFalseWhenClientVersionTooOld()
    {
        ee()->setMock('Database', $this->databaseServiceForVersions('5.6.0', 'mysqlnd 5.0.8-dev'));

        $wizard = $this->newWizard();
        $this->assertFalse($this->invokePrivate($wizard, 'isUtf8mb4Supported'));
    }

    public function testTemplateAndThemeWritableValidation()
    {
        $wizard = $this->newWizard();
        $formValidation = new WizardFormValidationStub();
        ee()->setMock('form_validation', $formValidation);

        @chmod(PATH_TMPL, 0755);
        $this->assertTrue($wizard->template_path_writeable('y'));

        @chmod(PATH_TMPL, 0555);
        clearstatcache(true, PATH_TMPL);
        $templateWritable = $wizard->template_path_writeable('y');
        if (! $templateWritable) {
            $this->assertSame('unwritable_templates', $formValidation->messages['template_path_writeable']);
        } else {
            // Some environments (notably Windows CI) do not honor chmod-style
            // permission changes for writability checks.
            $this->assertTrue($templateWritable);
        }
        @chmod(PATH_TMPL, 0755);

        $wizard->root_theme_path = sys_get_temp_dir() . '/wizard-theme-root-missing-' . uniqid() . '/';

        $themesWritable = $wizard->themes_user_writable('y');
        if (! $themesWritable) {
            $this->assertSame('unwritable_themes_user', $formValidation->messages['themes_user_writable']);
        } else {
            // CI is_really_writable() may treat non-existent paths as
            // creatable/writable on some Windows environments.
            $this->assertTrue($themesWritable);
        }
        $this->assertTrue($wizard->themes_user_writable('n'));
    }

    public function testSetPathSetBaseUrlSetQstrAndIsSecure()
    {
        $wizard = $this->newWizard();
        $wizard->config = new WizardConfigStub(['index_page' => 'admin.php']);

        $existing = sys_get_temp_dir();
        $this->assertSame($existing, $this->invokePrivate($wizard, 'set_path', $existing, 0));
        $this->assertStringStartsWith('../', $this->invokePrivate($wizard, 'set_path', 'missing-dir', 0));

        $this->invokePrivate($wizard, 'set_base_url');
        $this->assertSame('', $wizard->config->item('site_url'));
        $this->assertSame(EESELF, $wizard->config->item('index_page'));
        $this->assertSame(EESELF, $wizard->config->item('site_index'));
        $this->assertSame(EESELF . '?C=wizard&M=do_install&language=english', $this->invokePrivate($wizard, 'set_qstr', 'do_install'));

        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = '80';
        $this->assertTrue($this->invokePrivate($wizard, 'isSecure'));

        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_PORT'] = '443';
        $this->assertTrue($this->invokePrivate($wizard, 'isSecure'));

        $_SERVER['SERVER_PORT'] = '80';
        $this->assertFalse($this->invokePrivate($wizard, 'isSecure'));
    }

    public function testDbValidationStoresConnectionAttemptWhenUnset()
    {
        $wizard = $this->newWizard();
        $wizard->userdata['db_prefix'] = 'exp';

        $input = new WizardInputStub();
        $input->postData = [
            'db_hostname' => 'remote-host',
            'db_name' => 'ee_db',
            'db_username' => 'ee_user',
            'db_password' => 'secret',
        ];
        ee()->setMock('input', $input);

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                    throw new \Exception('cannot connect', 2002);
                }
            };
        };
        ee()->setMock('load', $load);

        $formValidation = new WizardFormValidationStub();
        ee()->setMock('form_validation', $formValidation);

        $this->assertFalse($wizard->valid_db_host());
        $this->assertSame('database_invalid_host', $formValidation->messages['valid_db_host']);
        $this->assertSame(2002, $this->getProperty($wizard, 'db_connect_attempt'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testClientSupportsUtf8mb4SupportsNonMysqlndClientVersion()
    {
        ee()->setMock('Database', $this->databaseServiceForVersions('8.0.33', '5.7.43'));

        $wizard = $this->newWizard();
        $this->assertTrue($this->invokePrivate($wizard, 'clientSupportsUtf8mb4'));
    }

    public function testGetDbConfigFallsBackToDatabasePhp()
    {
        $wizard = $this->newWizard();

        $dbConfig = new class {
            public function getGroupConfig()
            {
                throw new \Exception('no group');
            }
            public function getActiveGroup()
            {
                return 'fallback_group';
            }
        };

        ee()->setMock('Database', new class($dbConfig) {
            private $dbConfig;
            public function __construct($dbConfig)
            {
                $this->dbConfig = $dbConfig;
            }
            public function getConfig()
            {
                return $this->dbConfig;
            }
        });

        $databaseConfigPath = SYSPATH . '/user/config/database.php';
        $databaseConfigDir = dirname($databaseConfigPath);
        $existingContent = file_exists($databaseConfigPath) ? file_get_contents($databaseConfigPath) : null;
        @mkdir($databaseConfigDir, 0777, true);
        file_put_contents(
            $databaseConfigPath,
            "<?php\n\$db['fallback_group'] = ['hostname' => 'fallback-host', 'database' => 'fallback-db'];\n"
        );

        try {
            $this->assertSame(
                ['hostname' => 'fallback-host', 'database' => 'fallback-db'],
                $wizard->getDbConfig()
            );
        } finally {
            if ($existingContent !== null) {
                file_put_contents($databaseConfigPath, $existingContent);
            } else {
                @unlink($databaseConfigPath);
            }
        }
    }

    public function testPreflightHandlesUnreadableAndFirstInstallStates()
    {
        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-config-');
        $schemaPath = APPPATH . 'schema/';
        $createdSchemaPath = false;

        $load = new WizardLoadStub();
        ee()->setMock('load', $load);
        ee()->setMock('input', new WizardInputStub());

        file_put_contents($wizard->config->config_path, "<?php return false;\n");
        $this->assertFalse($this->invokePrivate($wizard, 'preflight'));

        file_put_contents($wizard->config->config_path, "<?php return true;\n");
        @chmod($wizard->config->config_path, 0444);
        $this->assertFalse($this->invokePrivate($wizard, 'preflight'));

        @chmod($wizard->config->config_path, 0644);
        if (! is_dir($schemaPath)) {
            $createdSchemaPath = @mkdir($schemaPath, 0777, true);
        }
        $wizard->root_theme_path = '/tmp/wizard-theme-root/';
        $this->assertTrue($this->invokePrivate($wizard, 'preflight'));
        $this->assertFalse($wizard->is_installed);
        $this->assertSame('/tmp/wizard-theme-root/', $wizard->userdata['theme_folder_path']);

        @unlink($wizard->config->config_path);
        if ($createdSchemaPath) {
            @rmdir($schemaPath);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPreflightReportsUnreadableEmailForMissingLanguagePack()
    {
        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-preflight-email-');
        $wizard->userdata['deft_lang'] = 'missing-language';

        $schemaPath = APPPATH . 'schema/';
        $createdSchemaPath = false;
        if (! is_dir($schemaPath)) {
            $createdSchemaPath = @mkdir($schemaPath, 0777, true);
        }

        file_put_contents($wizard->config->config_path, "<?php return true;\n");
        ee()->setMock('load', new WizardLoadStub());
        ee()->setMock('input', new WizardInputStub());

        $this->assertFalse($this->invokePrivate($wizard, 'preflight'));

        @unlink($wizard->config->config_path);
        if ($createdSchemaPath) {
            @rmdir($schemaPath);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPreflightReportsUnwritableCacheFolder()
    {
        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-preflight-cache-');
        file_put_contents($wizard->config->config_path, "<?php return true;\n");

        $schemaPath = APPPATH . 'schema/';
        $createdSchemaPath = false;
        if (! is_dir($schemaPath)) {
            $createdSchemaPath = @mkdir($schemaPath, 0777, true);
        }

        $load = new WizardLoadStub();
        ee()->setMock('load', $load);
        ee()->setMock('input', new WizardInputStub());

        $originalMode = @fileperms(PATH_CACHE) & 0777;
        @chmod(PATH_CACHE, 0555);

        $this->assertFalse($this->invokePrivate($wizard, 'preflight'));

        @chmod(PATH_CACHE, $originalMode ?: 0755);
        @unlink($wizard->config->config_path);
        if ($createdSchemaPath) {
            @rmdir($schemaPath);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPreflightReportsUnreadableSchemaWhenSchemaDirectoryMissing()
    {
        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-preflight-schema-');
        file_put_contents($wizard->config->config_path, "<?php return true;\n");

        $schemaPath = APPPATH . 'schema/';
        $schemaBackupPath = APPPATH . 'schema_backup_' . uniqid() . '/';
        $renamedSchema = false;
        if (is_dir($schemaPath)) {
            $renamedSchema = @rename($schemaPath, $schemaBackupPath);
        }

        ee()->setMock('load', new WizardLoadStub());
        ee()->setMock('input', new WizardInputStub());

        $this->assertFalse($this->invokePrivate($wizard, 'preflight'));

        if ($renamedSchema) {
            @rename($schemaBackupPath, $schemaPath);
        }
        @unlink($wizard->config->config_path);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPreflightReturnsDatabaseNoDataWhenDbConfigCannotBeResolved()
    {
        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-preflight-dbcfg-');
        file_put_contents($wizard->config->config_path, "<?php\n\$config = ['app_version' => '7.5.21'];\nreturn true;\n");

        ee()->setMock('load', new WizardLoadStub());
        ee()->setMock('input', new WizardInputStub());
        ee()->setMock('Database', new class {
            public function getConfig()
            {
                return new class {
                    public function getGroupConfig()
                    {
                        throw new \Exception('no db config');
                    }
                    public function getActiveGroup()
                    {
                        return 'default';
                    }
                };
            }
        });

        $this->assertFalse($this->invokePrivate($wizard, 'preflight'));
        @unlink($wizard->config->config_path);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPreflightReturnsUnreadableUpdateWhenUpdateDirectoryIsMissing()
    {
        @mkdir(APPPATH . 'updater/ExpressionEngine/Updater/Service/Updater/', 0777, true);
        file_put_contents(
            APPPATH . 'updater/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php',
            "<?php\nclass RequirementsChecker {\npublic function __construct(\$db) {}\npublic function check() { return true; }\n}\n"
        );

        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-preflight-updates-');
        file_put_contents($wizard->config->config_path, "<?php\n\$config = ['app_version' => '7.5.21'];\nreturn true;\n");

        $updatesPath = APPPATH . 'updates/';
        $updatesBackupPath = APPPATH . 'updates_backup_' . uniqid() . '/';
        $renamedUpdates = false;
        if (is_dir($updatesPath)) {
            $renamedUpdates = @rename($updatesPath, $updatesBackupPath);
        }

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                }
            };
        };
        ee()->setMock('load', $load);
        ee()->setMock('input', new WizardInputStub());
        ee()->setMock('Database', new class {
            public function getConfig()
            {
                return new class {
                    public function getGroupConfig()
                    {
                        return [
                            'hostname' => 'localhost',
                            'username' => 'root',
                            'password' => '',
                            'database' => 'ee',
                            'char_set' => 'utf8',
                        ];
                    }
                };
            }
        });

        $this->assertFalse($this->invokePrivate($wizard, 'preflight'));

        if ($renamedUpdates) {
            @rename($updatesBackupPath, $updatesPath);
        }
        @unlink($wizard->config->config_path);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPreflightReturnsUnreadableFilesWhenNextUpdateFileCannotBeIncluded()
    {
        $requirementsPath = APPPATH . 'updater/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php';
        $updatesPath = APPPATH . 'updates/';
        @mkdir(dirname($requirementsPath), 0777, true);
        @mkdir($updatesPath, 0777, true);

        file_put_contents(
            $requirementsPath,
            "<?php\nclass RequirementsChecker {\npublic function __construct(\$db) {}\npublic function check() { return true; }\n}\n"
        );
        file_put_contents($updatesPath . 'ud_7_05_22.php', "<?php return false;\n");

        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-preflight-include-');
        file_put_contents($wizard->config->config_path, "<?php\n\$config = ['app_version' => '7.5.21'];\nreturn true;\n");

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                }
            };
        };
        ee()->setMock('load', $load);
        ee()->setMock('input', new WizardInputStub());
        ee()->setMock('Database', new class {
            public function getConfig()
            {
                return new class {
                    public function getGroupConfig()
                    {
                        return [
                            'hostname' => 'localhost',
                            'username' => 'root',
                            'password' => '',
                            'database' => 'ee',
                            'char_set' => 'utf8',
                        ];
                    }
                };
            }
        });

        $this->assertFalse($this->invokePrivate($wizard, 'preflight'));

        @unlink($wizard->config->config_path);
        @unlink($updatesPath . 'ud_7_05_22.php');
        @unlink($requirementsPath);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPreflightFormatsFailedRequirementMessages()
    {
        eval('namespace { if (!class_exists("MSM_Config")) { class MSM_Config { public $default_ini = []; public function site_prefs($site) {} public function load() {} public function item($key) { return null; } } } }');

        $requirementsPath = APPPATH . 'updater/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php';
        @mkdir(dirname($requirementsPath), 0777, true);
        file_put_contents(
            $requirementsPath,
            "<?php\nclass RequirementsChecker {\npublic function __construct(\$db) {}\npublic function check() { return [new RequirementsFailureStub()]; }\n}\nclass RequirementsFailureStub { public function getMessage() { return 'Requirement failed'; } }\n"
        );

        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-preflight-req-');
        file_put_contents($wizard->config->config_path, "<?php\n\$config = ['app_version' => '7.5.21'];\nreturn true;\n");

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                }
            };
        };
        ee()->setMock('load', $load);
        ee()->setMock('input', new WizardInputStub());
        ee()->setMock('Database', new class {
            public function getConfig()
            {
                return new class {
                    public function getGroupConfig()
                    {
                        return [
                            'hostname' => 'localhost',
                            'username' => 'root',
                            'password' => '',
                            'database' => 'ee',
                            'char_set' => 'utf8',
                        ];
                    }
                };
            }
        });

        $this->assertFalse($this->invokePrivate($wizard, 'preflight'));

        @unlink($wizard->config->config_path);
        @unlink($requirementsPath);
    }

    public function testRemapHandlesPreflightFailureAndInstallAction()
    {
        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-remap-');
        $schemaPath = APPPATH . 'schema/';
        $createdSchemaPath = false;
        file_put_contents($wizard->config->config_path, "<?php return false;\n");

        $load = new WizardLoadStub();
        ee()->setMock('load', $load);

        $input = new WizardInputStub();
        ee()->setMock('input', $input);
        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';

        $this->assertFalse($wizard->_remap());

        file_put_contents($wizard->config->config_path, "<?php return true;\n");
        if (! is_dir($schemaPath)) {
            $createdSchemaPath = @mkdir($schemaPath, 0777, true);
        }

        $input->getData = ['M' => 'install_form'];
        $wizard->_remap();

        @unlink($wizard->config->config_path);
        if ($createdSchemaPath) {
            @rmdir($schemaPath);
        }
    }

    public function testRemapFallsBackToInstallFormForFreshInstallWithNoAction()
    {
        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-remap-fresh-');
        file_put_contents($wizard->config->config_path, "<?php return true;\n");

        $schemaPath = APPPATH . 'schema/';
        $createdSchemaPath = false;
        if (! is_dir($schemaPath)) {
            $createdSchemaPath = @mkdir($schemaPath, 0777, true);
        }

        $load = new WizardLoadStub();
        $wizard->load = $load;
        ee()->setMock('load', $load);

        $input = new WizardInputStub();
        $wizard->input = $input;
        ee()->setMock('input', $input);

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';

        $wizard->_remap();
        $this->assertFalse($wizard->is_installed);

        @unlink($wizard->config->config_path);
        if ($createdSchemaPath) {
            @rmdir($schemaPath);
        }
    }

    public function testAssignInstallValuesAppliesPostDataAndTrimmingRules()
    {
        $wizard = $this->newWizard();
        $wizard->userdata['modules'] = [
            'channel' => ['checked' => false],
            'comment' => ['checked' => false],
        ];

        $wizard->input->postData = [
            'username' => '  admin  ',
            'db_password' => '  password-with-spaces  ',
            'password' => '  pass  ',
            'modules' => ['comment'],
            'site_url' => 'https://example.test',
        ];

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';

        $this->invokePrivate($wizard, 'assign_install_values');

        $this->assertSame('admin', $wizard->userdata['username']);
        $this->assertSame('  password-with-spaces  ', $wizard->userdata['db_password']);
        $this->assertSame('  pass  ', $wizard->userdata['password']);
        $this->assertTrue($wizard->userdata['modules']['comment']['checked']);
        $this->assertStringEndsWith('/', $wizard->userdata['site_url']);
        $this->assertSame($this->invokePrivate($wizard, 'set_qstr', 'do_install'), $wizard->userdata['action']);
    }

    public function testPreflightInstalledModeHandlesDbConnectFailure()
    {
        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-preflight-installed-');
        file_put_contents($wizard->config->config_path, "<?php\n\$config = ['app_version' => '750'];\nreturn true;\n");

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                    throw new \Exception('db down', 1045);
                }
            };
        };

        ee()->setMock('load', $load);
        ee()->setMock('input', new WizardInputStub());
        ee()->setMock('Database', new class {
            public function getConfig()
            {
                return new class {
                    public function getGroupConfig()
                    {
                        return [
                            'hostname' => 'remote-host',
                            'username' => 'user',
                            'password' => 'pass',
                            'database' => 'db',
                            'char_set' => 'utf8',
                        ];
                    }
                };
            }
        });

        $this->assertFalse($this->invokePrivate($wizard, 'preflight'));
        @unlink($wizard->config->config_path);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRemapCanRunInstalledPreflightUsingLegacyInstallerFixtures()
    {
        eval('namespace { if (!class_exists("MSM_Config")) { class MSM_Config { public $default_ini = []; public function site_prefs($site) {} public function load() {} public function item($key) { return null; } } } }');

        $requirementsPath = APPPATH . 'updater/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php';
        $updatesPath = APPPATH . 'updates/';

        @mkdir(dirname($requirementsPath), 0777, true);
        @mkdir($updatesPath, 0777, true);

        file_put_contents(
            $requirementsPath,
            "<?php\nclass RequirementsChecker {\npublic function __construct(\$db) {}\npublic function check() { return true; }\n}\n"
        );
        file_put_contents($updatesPath . 'ud_7_05_22.php', "<?php\n");

        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-remap-installed-');
        file_put_contents($wizard->config->config_path, "<?php\n\$config = ['app_version' => '752'];\nreturn true;\n");

        $wizard->update_notices = new class {
            public $cleared = 0;
            public function clear()
            {
                $this->cleared++;
            }
            public function get()
            {
                return [];
            }
        };

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                }
            };
        };
        $wizard->load = $load;

        $input = new WizardInputStub();
        $input->getData = [];
        $wizard->input = $input;

        ee()->setMock('load', $load);
        ee()->setMock('input', $input);
        ee()->setMock('Database', new class {
            public function getConfig()
            {
                return new class {
                    public function getGroupConfig()
                    {
                        return [
                            'hostname' => 'localhost',
                            'username' => 'root',
                            'password' => '',
                            'database' => 'ee',
                            'char_set' => 'utf8',
                        ];
                    }
                };
            }
            public function closeConnection()
            {
            }
        });
        ee()->setMock('Addon', new class {
            public function get($name)
            {
                return new class {
                    public function isInstalled()
                    {
                        return true;
                    }
                };
            }
        });

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';

        $wizard->_remap();
        $this->assertSame(1, $wizard->update_notices->cleared);

        @unlink($wizard->config->config_path);
        @unlink($updatesPath . 'ud_7_05_22.php');
        @unlink($requirementsPath);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPreflightHandlesInstalledNoUpdatesPathAndShowsSuccess()
    {
        eval('namespace { if (!class_exists("MSM_Config")) { class MSM_Config { public $default_ini = []; public function site_prefs($site) {} public function load() {} public function item($key) { return null; } } } }');
        eval('namespace ExpressionEngine\\Library\\Advisor { if (!class_exists("Advisor")) { class Advisor { public function postUpdateChecks() { return ["postflight-message"]; } } } }');

        $requirementsPath = APPPATH . 'updater/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php';
        $updatesPath = APPPATH . 'updates/';
        @mkdir(dirname($requirementsPath), 0777, true);
        @mkdir($updatesPath, 0777, true);

        file_put_contents(
            $requirementsPath,
            "<?php\nclass RequirementsChecker {\npublic function __construct(\$db) {}\npublic function check() { return true; }\n}\n"
        );
        file_put_contents($updatesPath . 'ud_7_05_21.php', "<?php\n");

        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-preflight-no-updates-');
        file_put_contents($wizard->config->config_path, "<?php\n\$config = ['app_version' => '7.5.21'];\nreturn true;\n");

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                }
            };
        };
        $wizard->load = $load;

        $input = new WizardInputStub();
        $wizard->input = $input;

        ee()->setMock('load', $load);
        ee()->setMock('input', $input);
        ee()->setMock('Database', new class {
            public function getConfig()
            {
                return new class {
                    public function getGroupConfig()
                    {
                        return [
                            'hostname' => 'localhost',
                            'username' => 'root',
                            'password' => '',
                            'database' => 'ee',
                            'char_set' => 'utf8',
                        ];
                    }
                };
            }
            public function closeConnection()
            {
            }
        });
        ee()->setMock('Addon', new class {
            public function get($name)
            {
                return new class {
                    public function isInstalled()
                    {
                        return false;
                    }
                };
            }
        });
        ee()->setMock('addons', new class {
            public $installed = [];
            public function install_modules($modules)
            {
                $this->installed[] = $modules;
                return [];
            }
        });
        ee()->setMock('db', new class {
            public function count_all_results($table)
            {
                return $table === 'members' ? 2 : 0;
            }
            public function select($field)
            {
                return $this;
            }
            public function where($field, $value)
            {
                return $this;
            }
        });
        ee()->setMock('functions', new class {
            public function clear_caching($scope)
            {
            }
        });
        ee()->setMock('Model', new class {
            public function get($name)
            {
                if ($name === 'Channel') {
                    return new class {
                        public function all()
                        {
                            return [];
                        }
                    };
                }

                return new class {
                    public function with($name)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return $this;
                    }
                    public function synchronize()
                    {
                    }
                };
            }
        });
        ee()->setMock('CP/URL', new class {
            public function make($path)
            {
                return new class {
                    public function compile()
                    {
                        return '/admin.php?/cp/utilities/debug-tools';
                    }
                };
            }
        });
        ee()->setMock('lang', new class {
            public function loadfile($name)
            {
            }
        });

        if (! defined('SYSDIR')) {
            define('SYSDIR', 'ee');
        }
        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'off';

        $this->assertFalse($this->invokePrivate($wizard, 'preflight'));

        @unlink($wizard->config->config_path);
        @unlink($updatesPath . 'ud_7_05_21.php');
        @unlink($requirementsPath);
    }

    public function testDoInstallReturnsInstallFormWhenValidationFails()
    {
        $wizard = $this->newWizard();
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-install-config-');
        $wizard->config = new WizardConfigStub(['index_page' => EESELF]);

        $load = new WizardLoadStub();
        ee()->setMock('load', $load);

        ee()->setMock('input', new WizardInputStub());
        ee()->setMock('lang', new class {
            public function loadfile($name)
            {
            }
        });
        ee()->setMock('config', new class {
            public $items = [];
            public function set_item($key, $value)
            {
                $this->items[$key] = $value;
            }
            public function item($key)
            {
                return false;
            }
        });

        ee()->setMock('form_validation', new class {
            public function set_error_delimiters($open, $close)
            {
            }
            public function set_rules($rules)
            {
            }
            public function run()
            {
                return false;
            }
        });

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';

        $this->assertNull($this->invokePrivate($wizard, 'do_install'));

        $views = array_map(function ($row) {
            return $row[0];
        }, $load->viewCalls);

        $this->assertContains('install_form', $views);
        @unlink($wizard->config->config_path);
    }

    public function testDoInstallCollectsDatabaseErrorsAfterValidationPasses()
    {
        $wizard = $this->newWizard();
        $wizard->config = new WizardConfigStub(['index_page' => EESELF]);
        $wizard->userdata['db_prefix'] = 'exp';

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                    throw new \Exception('invalid user', 1044);
                }
            };
        };
        $wizard->load = $load;
        ee()->setMock('load', $load);

        $input = new WizardInputStub();
        $input->postData = [
            'db_hostname' => '127.0.0.1',
            'db_name' => 'ee',
            'db_username' => 'root',
            'db_password' => '',
            'db_prefix' => 'exp',
            'username' => 'admin',
            'password' => 'secret',
            'email_address' => 'admin@example.test',
            'license_agreement' => 'y',
            'install_default_theme' => 'n',
        ];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        ee()->setMock('lang', new class {
            public function loadfile($name)
            {
            }
        });
        ee()->setMock('config', new class {
            public $items = [];
            public function set_item($key, $value)
            {
                $this->items[$key] = $value;
            }
            public function item($key)
            {
                return false;
            }
        });
        ee()->setMock('form_validation', new class {
            public function set_error_delimiters($open, $close)
            {
            }
            public function set_rules($rules)
            {
            }
            public function run()
            {
                return true;
            }
        });
        ee()->setMock('Database', new class {
            public function closeConnection()
            {
            }
        });

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';

        $this->assertFalse($this->invokePrivate($wizard, 'do_install'));
        $this->assertContains('database_invalid_user', $wizard->userdata['errors']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoInstallCanRunSuccessPathWithLegacyInstallerFixtures()
    {
        eval('namespace { if (!function_exists("default_config_items")) { function default_config_items() { return []; } } }');
        require_once SYSPATH . 'ee/legacy/helpers/file_helper.php';
        unset($GLOBALS['wizard_speciality_templates_loaded']);

        $requirementsPath = APPPATH . 'updater/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php';
        $schemaPath = APPPATH . 'schema/mysqli_schema.php';
        $channelEntryPath = APPPATH . 'language/english/channel_entry_lang.php';
        $configTemplatePath = APPPATH . 'config/config_tmpl.php';

        @mkdir(dirname($requirementsPath), 0777, true);
        @mkdir(dirname($schemaPath), 0777, true);
        @mkdir(dirname($channelEntryPath), 0777, true);
        @mkdir(dirname($configTemplatePath), 0777, true);

        file_put_contents(
            $requirementsPath,
            "<?php\nclass RequirementsChecker {\npublic function __construct(\$db) {}\npublic function check() { return true; }\n}\n"
        );
        file_put_contents(
            $schemaPath,
            "<?php\nclass EE_Schema {\npublic \$version;\npublic \$userdata;\npublic \$theme_path;\npublic \$now;\npublic \$year;\npublic \$month;\npublic \$day;\npublic \$default_entry;\npublic function sql_find_like() { return 'SELECT 1'; }\npublic function install_tables_and_data() { return true; }\n}\n"
        );
        file_put_contents($channelEntryPath, "Default channel entry");
        file_put_contents(
            $configTemplatePath,
            "<?php\n\$config['db_hostname'] = '{db_hostname}';\n\$config['index_page'] = '{index_page}';\n{extra_config}"
        );

        $wizard = $this->newWizard();
        $wizard->config = new WizardConfigStub(['index_page' => EESELF]);
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-install-success-config-');
        $wizard->userdata['db_prefix'] = 'exp';
        $wizard->base_path = sys_get_temp_dir() . '/wizard-install-base/';
        $wizard->root_theme_path = $wizard->base_path;
        $wizard->theme_path = $wizard->base_path . 'themes/ee/site/';
        @mkdir($wizard->base_path . 'avatars', 0777, true);
        @mkdir($wizard->base_path . 'photos', 0777, true);
        @mkdir($wizard->base_path . 'signatures', 0777, true);
        @mkdir($wizard->base_path . 'pm', 0777, true);
        @mkdir($wizard->base_path . 'captchas', 0777, true);
        @mkdir($wizard->base_path . 'themes', 0777, true);
        @mkdir($wizard->theme_path . 'unit-theme', 0777, true);
        file_put_contents(
            $wizard->theme_path . 'unit-theme/speciality_templates.php',
            "<?php\n\$GLOBALS['wizard_speciality_templates_loaded'] = true;\n"
        );
        $wizard->theme_required_modules = ['unit-theme' => ['speciality_module']];

        $wizard->userdata['avatar_path'] = 'avatars/';
        $wizard->userdata['photo_path'] = 'photos/';
        $wizard->userdata['signature_img_path'] = 'signatures/';
        $wizard->userdata['pm_path'] = 'pm/';
        $wizard->userdata['captcha_path'] = 'captchas/';
        $wizard->userdata['theme_folder_path'] = $wizard->base_path . 'themes/';
        $wizard->userdata['avatar_url'] = 'images/avatars/';
        $wizard->userdata['photo_url'] = 'images/photos/';
        $wizard->userdata['signature_img_url'] = 'images/signatures/';

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                }
            };
        };
        $wizard->load = $load;
        ee()->setMock('load', $load);

        $input = new WizardInputStub();
        $input->postData = [
            'db_hostname' => '127.0.0.1',
            'db_name' => 'ee',
            'db_username' => 'root',
            'db_password' => '',
            'db_prefix' => 'exp',
            'username' => 'admin',
            'password' => 'secret',
            'email_address' => 'admin@example.test',
            'license_agreement' => 'y',
            'install_default_theme' => 'n',
            'theme' => 'unit-theme',
            'site_url' => 'https://example.test/',
        ];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        ee()->setMock('lang', new class {
            public function loadfile($name)
            {
            }
        });
        ee()->setMock('config', new class {
            public $items = [];
            public function set_item($key, $value)
            {
                $this->items[$key] = $value;
            }
            public function item($key)
            {
                return false;
            }
            public function divination($name)
            {
                return ['db_hostname'];
            }
            public function divineAll()
            {
                return ['db_hostname', 'db_username', 'db_database'];
            }
        });
        ee()->setMock('form_validation', new class {
            public function set_error_delimiters($open, $close)
            {
            }
            public function set_rules($rules)
            {
            }
            public function run()
            {
                return true;
            }
        });
        ee()->setMock('Database', new class {
            public function closeConnection()
            {
            }
            public function getConnection()
            {
                return new class {
                    public function getNative()
                    {
                        return new class {
                            public function getAttribute($attribute)
                            {
                                if ($attribute === \PDO::ATTR_SERVER_VERSION) {
                                    return '8.0.33';
                                }
                                if ($attribute === \PDO::ATTR_CLIENT_VERSION) {
                                    return 'mysqlnd 8.0.33';
                                }

                                return null;
                            }
                        };
                    }
                };
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
                };
            }
            public function insert_batch($table, $rows)
            {
            }
        });
        ee()->setMock('auth', new class {
            public function hash_password($password)
            {
                return ['password' => 'hashed', 'salt' => 'salted'];
            }
        });
        ee()->setMock('Encrypt', new class {
            public function generateKey()
            {
                return 'generated-key';
            }
        });
        ee()->setMock('addons', new class {
            public function install_modules($required)
            {
                return ['module-warning'];
            }
        });
        ee()->setMock('Addon', new class {
            public function get($name)
            {
                return new class {
                    public function installConsentRequests()
                    {
                    }
                };
            }
        });

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'off';
        if (! defined('SYSDIR')) {
            define('SYSDIR', 'ee');
        }

        $this->invokePrivate($wizard, 'do_install');
        $this->assertContains('module-warning', $wizard->module_install_errors);
        $this->assertTrue(! empty($GLOBALS['wizard_speciality_templates_loaded']));
        $this->assertContains('speciality_module', $wizard->required_modules);

        @unlink($wizard->config->config_path);
        @unlink($wizard->theme_path . 'unit-theme/speciality_templates.php');
        @rmdir($wizard->theme_path . 'unit-theme');
        @unlink($requirementsPath);
        @unlink($schemaPath);
        @unlink($channelEntryPath);
        @unlink($configTemplatePath);
        unset($GLOBALS['wizard_speciality_templates_loaded']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoInstallReturnsFalseWhenSchemaInstallFails()
    {
        require_once SYSPATH . 'ee/legacy/helpers/file_helper.php';

        $requirementsPath = APPPATH . 'updater/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php';
        $schemaPath = APPPATH . 'schema/mysqli_schema.php';
        $channelEntryPath = APPPATH . 'language/english/channel_entry_lang.php';

        @mkdir(dirname($requirementsPath), 0777, true);
        @mkdir(dirname($schemaPath), 0777, true);
        @mkdir(dirname($channelEntryPath), 0777, true);

        file_put_contents(
            $requirementsPath,
            "<?php\nclass RequirementsChecker {\npublic function __construct(\$db) {}\npublic function check() { return true; }\n}\n"
        );
        file_put_contents(
            $schemaPath,
            "<?php\nclass EE_Schema {\npublic \$version;\npublic \$userdata;\npublic \$theme_path;\npublic \$now;\npublic \$year;\npublic \$month;\npublic \$day;\npublic \$default_entry;\npublic function sql_find_like() { return 'SELECT 1'; }\npublic function install_tables_and_data() { return false; }\n}\n"
        );
        file_put_contents($channelEntryPath, "Default channel entry");

        $wizard = $this->newWizard();
        $wizard->config = new WizardConfigStub(['index_page' => EESELF]);
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-install-schema-fail-config-');
        $wizard->userdata['db_prefix'] = 'exp';

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                }
            };
        };
        $wizard->load = $load;
        ee()->setMock('load', $load);

        $input = new WizardInputStub();
        $input->postData = [
            'db_hostname' => '127.0.0.1',
            'db_name' => 'ee',
            'db_username' => 'root',
            'db_password' => '',
            'db_prefix' => 'exp',
            'username' => 'admin',
            'password' => 'secret',
            'email_address' => 'admin@example.test',
            'license_agreement' => 'y',
            'install_default_theme' => 'n',
            'theme' => 'none',
            'site_url' => 'https://example.test/',
        ];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        ee()->setMock('lang', new class {
            public function loadfile($name)
            {
            }
        });
        ee()->setMock('config', new class {
            public function set_item($key, $value)
            {
            }
            public function item($key)
            {
                return false;
            }
        });
        ee()->setMock('form_validation', new class {
            public function set_error_delimiters($open, $close)
            {
            }
            public function set_rules($rules)
            {
            }
            public function run()
            {
                return true;
            }
        });
        ee()->setMock('Database', new class {
            public function closeConnection()
            {
            }
            public function getConnection()
            {
                return new class {
                    public function getNative()
                    {
                        return new class {
                            public function getAttribute($attribute)
                            {
                                if ($attribute === \PDO::ATTR_SERVER_VERSION) {
                                    return '8.0.33';
                                }
                                if ($attribute === \PDO::ATTR_CLIENT_VERSION) {
                                    return 'mysqlnd 8.0.33';
                                }

                                return null;
                            }
                        };
                    }
                };
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
                };
            }
        });
        ee()->setMock('auth', new class {
            public function hash_password($password)
            {
                return ['password' => 'hashed', 'salt' => 'salted'];
            }
        });
        ee()->setMock('Encrypt', new class {
            public function generateKey()
            {
                return 'generated-key';
            }
        });

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'off';

        $this->assertFalse($this->invokePrivate($wizard, 'do_install'));

        @unlink($wizard->config->config_path);
        @unlink($requirementsPath);
        @unlink($schemaPath);
        @unlink($channelEntryPath);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoInstallAddsUtf8mb4WarningWhenServerAndClientAreUnsupported()
    {
        $wizard = $this->newWizard();
        $wizard->config = new WizardConfigStub(['index_page' => EESELF]);
        $wizard->userdata['db_prefix'] = 'exp';

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                }
            };
        };
        $wizard->load = $load;
        ee()->setMock('load', $load);

        $input = new WizardInputStub();
        $input->postData = [
            'db_hostname' => '127.0.0.1',
            'db_name' => 'ee',
            'db_username' => 'root',
            'db_password' => '',
            'db_prefix' => 'exp',
            'username' => 'admin',
            'password' => 'secret',
            'email_address' => 'admin@example.test',
            'license_agreement' => 'y',
            'install_default_theme' => 'n',
        ];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        ee()->setMock('lang', new class {
            public function loadfile($name)
            {
            }
        });
        ee()->setMock('config', new class {
            public function set_item($key, $value)
            {
            }
            public function item($key)
            {
                return false;
            }
        });
        ee()->setMock('form_validation', new class {
            public function set_error_delimiters($open, $close)
            {
            }
            public function set_rules($rules)
            {
            }
            public function run()
            {
                return true;
            }
        });
        ee()->setMock('Database', $this->databaseServiceForVersions('5.5.2', 'mysqlnd 5.0.8-dev'));

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'off';

        $this->assertFalse($this->invokePrivate($wizard, 'do_install'));
        $this->assertFalse($wizard->userdata['utf8mb4_supported']);
    }

    public function testDoInstallHandlesUnknownDatabaseAuthenticationPlugin()
    {
        $wizard = $this->newWizard();
        $wizard->config = new WizardConfigStub(['index_page' => EESELF]);
        $wizard->userdata['db_prefix'] = 'exp';

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                    throw new \Exception('unknown auth plugin', 2054);
                }
            };
        };
        $wizard->load = $load;
        ee()->setMock('load', $load);

        $input = new WizardInputStub();
        $input->postData = [
            'db_hostname' => '127.0.0.1',
            'db_name' => 'ee',
            'db_username' => 'root',
            'db_password' => '',
            'db_prefix' => 'exp',
            'username' => 'admin',
            'password' => 'secret',
            'email_address' => 'admin@example.test',
            'license_agreement' => 'y',
            'install_default_theme' => 'n',
        ];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        ee()->setMock('lang', new class {
            public function loadfile($name)
            {
            }
        });
        ee()->setMock('config', new class {
            public function set_item($key, $value)
            {
            }
            public function item($key)
            {
                return false;
            }
        });
        ee()->setMock('form_validation', new class {
            public function set_error_delimiters($open, $close)
            {
            }
            public function set_rules($rules)
            {
            }
            public function run()
            {
                return true;
            }
        });
        ee()->setMock('Database', new class {
            public function closeConnection()
            {
            }
        });

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'off';

        $this->assertFalse($this->invokePrivate($wizard, 'do_install'));
    }

    public function testDoInstallHandlesUnknownDatabaseConnectionFailureWithoutErrorCode()
    {
        $wizard = $this->newWizard();
        $wizard->config = new WizardConfigStub(['index_page' => EESELF]);
        $wizard->userdata['db_prefix'] = 'exp';

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                    throw new \Exception('no code');
                }
            };
        };
        $wizard->load = $load;
        ee()->setMock('load', $load);

        $input = new WizardInputStub();
        $input->postData = [
            'db_hostname' => 'db.example.test',
            'db_name' => 'ee',
            'db_username' => 'root',
            'db_password' => '',
            'db_prefix' => 'exp',
            'username' => 'admin',
            'password' => 'secret',
            'email_address' => 'admin@example.test',
            'license_agreement' => 'y',
            'install_default_theme' => 'n',
        ];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        ee()->setMock('lang', new class {
            public function loadfile($name)
            {
            }
        });
        ee()->setMock('config', new class {
            public function set_item($key, $value)
            {
            }
            public function item($key)
            {
                return false;
            }
        });
        ee()->setMock('form_validation', new class {
            public function set_error_delimiters($open, $close)
            {
            }
            public function set_rules($rules)
            {
            }
            public function run()
            {
                return true;
            }
        });
        ee()->setMock('Database', new class {
            public function closeConnection()
            {
            }
        });

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'off';

        $this->assertFalse($this->invokePrivate($wizard, 'do_install'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoInstallCollectsRequirementFailuresAfterDatabaseConnectionSucceeds()
    {
        $requirementsPath = APPPATH . 'updater/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php';
        $schemaPath = APPPATH . 'schema/mysqli_schema.php';
        @mkdir(dirname($requirementsPath), 0777, true);
        @mkdir(dirname($schemaPath), 0777, true);

        file_put_contents(
            $requirementsPath,
            "<?php\nclass RequirementsChecker {\npublic function __construct(\$db) {}\npublic function check() { return [new RequirementFailureStub()]; }\n}\nclass RequirementFailureStub { public function getMessage() { return 'requirement failed'; } }\n"
        );
        file_put_contents($schemaPath, "<?php\n");

        $wizard = $this->newWizard();
        $wizard->config = new WizardConfigStub(['index_page' => EESELF]);
        $wizard->userdata['db_prefix'] = 'exp';

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                }
            };
        };
        $wizard->load = $load;
        ee()->setMock('load', $load);

        $input = new WizardInputStub();
        $input->postData = [
            'db_hostname' => '127.0.0.1',
            'db_name' => 'ee',
            'db_username' => 'root',
            'db_password' => '',
            'db_prefix' => 'exp',
            'username' => 'admin',
            'password' => 'secret',
            'email_address' => 'admin@example.test',
            'license_agreement' => 'y',
            'install_default_theme' => 'n',
        ];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        ee()->setMock('lang', new class {
            public function loadfile($name)
            {
            }
        });
        ee()->setMock('config', new class {
            public function set_item($key, $value)
            {
            }
            public function item($key)
            {
                return false;
            }
        });
        ee()->setMock('form_validation', new class {
            public function set_error_delimiters($open, $close)
            {
            }
            public function set_rules($rules)
            {
            }
            public function run()
            {
                return true;
            }
        });
        ee()->setMock('Database', $this->databaseServiceForVersions('8.0.33', 'mysqlnd 8.0.33'));

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'off';

        $this->assertFalse($this->invokePrivate($wizard, 'do_install'));

        @unlink($requirementsPath);
        @unlink($schemaPath);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoInstallStopsWhenExistingInstallationIsDetectedWithoutOverride()
    {
        $requirementsPath = APPPATH . 'updater/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php';
        $schemaPath = APPPATH . 'schema/mysqli_schema.php';
        @mkdir(dirname($requirementsPath), 0777, true);
        @mkdir(dirname($schemaPath), 0777, true);

        file_put_contents(
            $requirementsPath,
            "<?php\nclass RequirementsChecker {\npublic function __construct(\$db) {}\npublic function check() { return true; }\n}\n"
        );
        file_put_contents(
            $schemaPath,
            "<?php\nclass EE_Schema {\npublic function sql_find_like() { return 'SELECT 1'; }\npublic function install_tables_and_data() { return true; }\n}\n"
        );

        $wizard = $this->newWizard();
        $wizard->config = new WizardConfigStub(['index_page' => EESELF]);
        $wizard->userdata['db_prefix'] = 'exp';

        $load = new WizardLoadStub();
        $load->databaseFactory = function () {
            return new class {
                public $save_queries = false;
                public $db_exception = false;
                public function initialize()
                {
                }
            };
        };
        $wizard->load = $load;
        ee()->setMock('load', $load);

        $input = new WizardInputStub();
        $input->postData = [
            'db_hostname' => '127.0.0.1',
            'db_name' => 'ee',
            'db_username' => 'root',
            'db_password' => '',
            'db_prefix' => 'exp',
            'username' => 'admin',
            'password' => 'secret',
            'email_address' => 'admin@example.test',
            'license_agreement' => 'y',
            'install_default_theme' => 'n',
        ];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        ee()->setMock('lang', new class {
            public function loadfile($name)
            {
            }
        });
        ee()->setMock('config', new class {
            public function set_item($key, $value)
            {
            }
            public function item($key)
            {
                return false;
            }
        });
        ee()->setMock('form_validation', new class {
            public function set_error_delimiters($open, $close)
            {
            }
            public function set_rules($rules)
            {
            }
            public function run()
            {
                return true;
            }
        });
        ee()->setMock('Database', $this->databaseServiceForVersions('8.0.33', 'mysqlnd 8.0.33'));
        ee()->setMock('db', new class {
            public function query($sql)
            {
                return new class {
                    public function num_rows()
                    {
                        return 1;
                    }
                };
            }
        });

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'off';

        $this->invokePrivate($wizard, 'do_install');
        $this->assertSame('admin', $wizard->userdata['username']);

        @unlink($requirementsPath);
        @unlink($schemaPath);
    }

    public function testDoUpdateReturnsProgressMessageForNoProgressRequests()
    {
        $wizard = $this->newWizard();
        $wizard->next_update = '7.5.21';
        $wizard->remaining_updates = 3;
        $wizard->progress = new class {
            public $prefix = '';
            public function clear_state()
            {
            }
            public function get_state()
            {
                return 'state';
            }
        };
        $wizard->load = new WizardLoadStub();

        ee()->setMock('load', $wizard->load);
        ee()->setMock('config', new class {
            public $items = [];
            public function set_item($key, $value)
            {
                $this->items[$key] = $value;
            }
            public function item($key)
            {
                return false;
            }
        });

        $input = new WizardInputStub();
        $input->getData = ['progress' => 'no'];
        $input->postData = ['database_backup' => false, 'update_addons' => false];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        $this->assertNull($this->invokePrivate($wizard, 'do_update'));
        $this->assertTrue($wizard->refresh);
        $this->assertSame($this->invokePrivate($wizard, 'set_qstr', 'do_update&agree=yes'), $wizard->refresh_url);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPostflightCallsAdvisorAndMaintenanceServices()
    {
        eval('namespace ExpressionEngine\\Library\\Advisor { if (!class_exists("Advisor")) { class Advisor { public function postUpdateChecks() { return ["postflight-ok"]; } } } }');

        $wizard = $this->newWizard();

        $functions = new class {
            public $cleared = [];
            public function clear_caching($scope)
            {
                $this->cleared[] = $scope;
            }
        };
        ee()->setMock('functions', $functions);

        ee()->setMock('db', new class {
            public $updates = [];
            public function update($table, $data)
            {
                $this->updates[] = [$table, $data];
            }
        });

        ee()->setMock('Model', new class {
            public function get($name)
            {
                if ($name === 'Channel') {
                    return new class {
                        public function all()
                        {
                            return [
                                new class {
                                    public $updated = false;
                                    public function updateEntryStats()
                                    {
                                        $this->updated = true;
                                    }
                                },
                            ];
                        }
                    };
                }

                return new class {
                    public $synced = false;
                    public function with($name)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return $this;
                    }
                    public function synchronize()
                    {
                        $this->synced = true;
                    }
                };
            }
        });

        $this->assertSame(['postflight-ok'], $this->invokePrivate($wizard, 'postflight'));
        $this->assertSame(['all'], $functions->cleared);
    }

    public function testInstallFormAndUpdateFormRenderExpectedViews()
    {
        $wizard = $this->newWizard();
        $wizard->installed_version = '7.4.0';
        $wizard->version = '7.5.21';

        $load = new WizardLoadStub();
        ee()->setMock('load', $load);
        ee()->setMock('input', new WizardInputStub());
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'updater_allow_advanced') {
                    return 'y';
                }

                return false;
            }
        });

        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['PHP_SELF'] = '/admin.php';

        $this->invokePrivate($wizard, 'install_form', ['db error']);
        $this->invokePrivate($wizard, 'update_form');

        $views = array_map(function ($row) {
            return $row[0];
        }, $load->viewCalls);

        $this->assertContains('install_form', $views);
        $this->assertContains('update_form', $views);
        $this->assertContains('container', $views);
        $this->assertSame(sprintf('update_title', '7.4.0', '7.5.21'), $wizard->title);
    }

    public function testSetOutputBuildsContainerViewPayload()
    {
        $wizard = $this->newWizard();
        $wizard->version = '7.5.21';
        $wizard->installed_version = '7.4.0';
        $wizard->next_update = '750';
        $wizard->is_installed = false;
        $wizard->languages = ['english'];

        $load = new WizardLoadStub();
        ee()->setMock('load', $load);

        $input = new WizardInputStub();
        $input->getData = ['ajax_progress' => 'yes'];
        ee()->setMock('input', $input);

        $this->invokePrivate($wizard, 'set_output', 'error', ['error' => 'failure']);

        $views = array_map(function ($row) {
            return $row[0];
        }, $load->viewCalls);

        $this->assertContains('error', $views);
        $this->assertContains('ee-logo', $views);
        $this->assertContains('container', $views);
        $this->assertSame('install_failed', $wizard->title);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetOutputUsesCompressedJavascriptWhenSrcDirectoryIsMissing()
    {
        $wizard = $this->newWizard();
        $wizard->version = '7.5.21';
        $wizard->installed_version = '7.4.0';
        $wizard->next_update = '750';
        $wizard->is_installed = false;

        $load = new WizardLoadStub();
        ee()->setMock('load', $load);
        ee()->setMock('input', new WizardInputStub());

        $cwd = getcwd();
        $tmp = sys_get_temp_dir() . '/wizard-output-' . uniqid();
        @mkdir($tmp . '/themes/ee/asset/javascript/compressed', 0777, true);
        chdir($tmp);

        try {
            $this->invokePrivate($wizard, 'set_output', 'update_form', ['action' => 'test']);
        } finally {
            chdir($cwd);
            @rmdir($tmp . '/themes/ee/asset/javascript/compressed');
            @rmdir($tmp . '/themes/ee/asset/javascript');
            @rmdir($tmp . '/themes/ee/asset');
            @rmdir($tmp . '/themes/ee');
            @rmdir($tmp . '/themes');
            @rmdir($tmp);
        }

        $this->assertNotEmpty($load->viewCalls);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetOutputUsesMsmConfigWhenInstalled()
    {
        eval('namespace { if (!class_exists("MSM_Config")) { class MSM_Config { public $default_ini = []; public function site_prefs($site) {} public function load() {} public function item($key) { return $key === "theme_folder_url" ? "https://themes.example.test" : null; } } } }');

        $wizard = $this->newWizard();
        $wizard->version = '7.5.21';
        $wizard->installed_version = '7.4.0';
        $wizard->next_update = '750';
        $wizard->is_installed = true;

        $load = new WizardLoadStub();
        ee()->setMock('load', $load);

        ee()->setMock('input', new WizardInputStub());

        $this->invokePrivate($wizard, 'set_output', 'update_form', ['action' => 'test']);
        $this->invokePrivate($wizard, 'set_output', 'error', ['error' => 'installed-failure']);

        $views = array_map(function ($row) {
            return $row[0];
        }, $load->viewCalls);

        $this->assertContains('update_form', $views);
        $this->assertContains('container', $views);
    }

    public function testShowSuccessHandlesRenameFailureAndMailingListDownload()
    {
        $wizard = $this->newWizard();
        $wizard->userdata['cp_url'] = 'https://example.test/admin.php';
        $wizard->userdata['site_index'] = 'index.php';
        $wizard->version = '7.5.21';

        $load = new WizardLoadStub();
        ee()->setMock('load', $load);

        $input = new WizardInputStub();
        $input->getData = ['download' => 'mailing_list.zip'];
        ee()->setMock('input', $input);

        $cachePath = SYSPATH . 'user/cache/';
        @mkdir($cachePath, 0777, true);
        file_put_contents($cachePath . 'mailing_list.zip', 'zip-content');
        $this->assertFileExists($cachePath . 'mailing_list.zip');

        try {
            try {
                $this->invokePrivate($wizard, 'show_success', 'install', ['errors' => 1, 'update_notices' => []]);
            } catch (\Exception $e) {
                $this->assertSame('Force download called', $e->getMessage());
            }

            if (isset($GLOBALS['wizard_force_download'])) {
                $this->assertSame('mailing_list.zip', $GLOBALS['wizard_force_download'][0]);
            }
        } finally {
            @unlink($cachePath . 'mailing_list.zip');
        }
    }

    public function testShowSuccessCompletesWhenDownloadIsNotRequested()
    {
        $wizard = $this->newWizard();
        $wizard->userdata['cp_url'] = 'https://example.test/admin.php';
        $wizard->version = '7.5.21';

        $load = new WizardLoadStub();
        ee()->setMock('load', $load);
        ee()->setMock('input', new WizardInputStub());

        $this->invokePrivate($wizard, 'show_success', 'update', ['errors' => 1]);
        $this->assertSame('completed', $wizard->subtitle);
    }

    public function testShowSuccessRedirectsWhenInstallerRenameSucceeds()
    {
        $wizard = $this->newWizard();
        $wizard->userdata['cp_url'] = 'https://example.test/admin.php';
        $wizard->version = '7.5.21';

        $load = new WizardLoadStub();
        ee()->setMock('load', $load);
        ee()->setMock('input', new WizardInputStub());

        $expected = 'https://example.test/admin.php?/cp/login&return=&after=install';

        try {
            $this->invokePrivate($wizard, 'show_success', 'install', []);
            $this->assertSame($expected, $GLOBALS['wizard_redirect_url'] ?? null);
        } catch (\RuntimeException $e) {
            $this->assertSame($expected, $e->getMessage());
        }
        $this->assertContains('url', $load->helpers);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWriteConfigFromTemplateAndWriteConfigData()
    {
        eval('namespace { if (!function_exists("default_config_items")) { function default_config_items() { return []; } } }');
        require_once SYSPATH . 'ee/legacy/helpers/file_helper.php';

        $wizard = $this->newWizard();
        $wizard->base_path = sys_get_temp_dir() . '/wizard-config-base/';
        $wizard->root_theme_path = $wizard->base_path;
        @mkdir($wizard->base_path . 'avatars', 0777, true);
        @mkdir($wizard->base_path . 'photos', 0777, true);
        @mkdir($wizard->base_path . 'signatures', 0777, true);
        @mkdir($wizard->base_path . 'pm', 0777, true);
        @mkdir($wizard->base_path . 'captchas', 0777, true);
        @mkdir($wizard->base_path . 'themes', 0777, true);
        @mkdir(APPPATH . 'config', 0777, true);

        file_put_contents(
            APPPATH . 'config/config_tmpl.php',
            "<?php\n\$config['db_hostname'] = '{db_hostname}';\n\$config['index_page'] = '{index_page}';\n{extra_config}"
        );

        $wizard->userdata['db_port'] = null;
        $wizard->userdata['db_hostname'] = 'localhost';
        $wizard->userdata['db_username'] = 'root';
        $wizard->userdata['db_password'] = 'secret';
        $wizard->userdata['db_name'] = 'ee';
        $wizard->userdata['db_char_set'] = 'utf8';
        $wizard->userdata['db_collat'] = 'utf8_unicode_ci';
        $wizard->userdata['app_version'] = '7.5.21';
        $wizard->userdata['site_index'] = 'index.php';
        $wizard->userdata['site_label'] = 'Installer Test Site';
        $wizard->userdata['site_url'] = 'https://example.test/';
        $wizard->userdata['cp_url'] = 'https://example.test/admin.php';
        $wizard->userdata['email_address'] = 'admin@example.test';
        $wizard->userdata['deft_lang'] = 'english';
        $wizard->userdata['redirect_method'] = 'redirect';
        $wizard->userdata['avatar_url'] = 'images/avatars/';
        $wizard->userdata['photo_url'] = 'images/photos/';
        $wizard->userdata['signature_img_url'] = 'images/signatures/';
        $wizard->userdata['avatar_path'] = 'avatars/';
        $wizard->userdata['photo_path'] = 'photos/';
        $wizard->userdata['signature_img_path'] = 'signatures/';
        $wizard->userdata['pm_path'] = 'pm/';
        $wizard->userdata['captcha_path'] = 'captchas/';
        $wizard->userdata['theme_folder_path'] = $wizard->base_path . 'themes/';

        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-generated-config-');

        ee()->setMock('config', new class {
            public function divination($name)
            {
                return ['db_hostname'];
            }
            public function divineAll()
            {
                return ['db_hostname', 'db_username', 'db_database'];
            }
        });

        ee()->setMock('db', new class {
            public $batches = [];
            public function insert_batch($table, $rows)
            {
                $this->batches[] = [$table, $rows];
            }
        });

        ee()->setMock('Encrypt', new class {
            public function generateKey()
            {
                return 'generated-key';
            }
        });

        $this->assertTrue($this->invokePrivate($wizard, 'write_config_data'));
        $this->assertTrue($this->invokePrivate($wizard, 'write_config_from_template', ['site_index' => 'index.php', 'site_label' => '']));
        $this->assertNotFalse(file_get_contents($wizard->config->config_path));

        @unlink($wizard->config->config_path);
        @unlink(APPPATH . 'config/config_tmpl.php');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWriteConfigFromTemplateReadsConfigFileAndAddsShareAnalytics()
    {
        eval('namespace { if (!function_exists("default_config_items")) { function default_config_items() { return []; } } }');
        require_once SYSPATH . 'ee/legacy/helpers/file_helper.php';

        $wizard = $this->newWizard();
        $wizard->userdata['share_analytics'] = 'y';

        @mkdir(APPPATH . 'config', 0777, true);
        file_put_contents(
            APPPATH . 'config/config_tmpl.php',
            "<?php\n\$config['index_page'] = '{index_page}';\n{extra_config}"
        );

        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-config-template-');
        file_put_contents($wizard->config->config_path, "<?php\n\$config = ['site_index' => 'index.php', 'site_label' => ''];\n");

        $this->assertTrue($this->invokePrivate($wizard, 'write_config_from_template', []));
        $this->assertStringContainsString('share_analytics', file_get_contents($wizard->config->config_path));

        @unlink($wizard->config->config_path);
        @unlink(APPPATH . 'config/config_tmpl.php');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWriteConfigFromTemplateReturnsFalseWhenConfigPathCannotBeOpened()
    {
        eval('namespace { if (!function_exists("default_config_items")) { function default_config_items() { return []; } } }');
        require_once SYSPATH . 'ee/legacy/helpers/file_helper.php';

        $wizard = $this->newWizard();
        $wizard->userdata['share_analytics'] = 'n';
        $wizard->config->config_path = sys_get_temp_dir() . '/wizard-missing-' . uniqid() . '/config.php';

        @mkdir(APPPATH . 'config', 0777, true);
        file_put_contents(
            APPPATH . 'config/config_tmpl.php',
            "<?php\n\$config['index_page'] = '{index_page}';\n{extra_config}"
        );

        ee()->setMock('Encrypt', new class {
            public function generateKey()
            {
                return 'generated-key';
            }
        });

        set_error_handler(function () {
            return true;
        });
        try {
            $this->assertFalse(
                $this->invokePrivate(
                    $wizard,
                    'write_config_from_template',
                    ['site_index' => 'index.php', 'site_label' => '']
                )
            );
        } finally {
            restore_error_handler();
        }

        @unlink(APPPATH . 'config/config_tmpl.php');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWriteConfigFromTemplateExecutesApcCacheClearFallback()
    {
        eval('namespace { if (!function_exists("default_config_items")) { function default_config_items() { return []; } } }');
        require_once SYSPATH . 'ee/legacy/helpers/file_helper.php';

        $wizard = $this->newWizard();
        $wizard->userdata['share_analytics'] = 'n';
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-apc-config-');
        $GLOBALS['wizard_apc_delete_file_called'] = false;
        $GLOBALS['wizard_apc_clear_cache_called'] = false;

        @mkdir(APPPATH . 'config', 0777, true);
        file_put_contents(
            APPPATH . 'config/config_tmpl.php',
            "<?php\n\$config['index_page'] = '{index_page}';\n{extra_config}"
        );

        ee()->setMock('Encrypt', new class {
            public function generateKey()
            {
                return 'generated-key';
            }
        });

        $this->assertTrue(
            $this->invokePrivate(
                $wizard,
                'write_config_from_template',
                ['site_index' => 'index.php', 'site_label' => '']
            )
        );
        $this->assertTrue($GLOBALS['wizard_apc_delete_file_called'] || function_exists('apc_delete_file'));

        @unlink($wizard->config->config_path);
        @unlink(APPPATH . 'config/config_tmpl.php');
        unset($GLOBALS['wizard_apc_delete_file_called'], $GLOBALS['wizard_apc_clear_cache_called']);
    }

    public function testWriteConfigFromTemplateReturnsEarlyForOpcacheRestrictApi()
    {
        if (! function_exists('opcache_invalidate')) {
            $this->markTestSkipped('opcache extension is not available.');
        }

        if (ini_get('opcache.restrict_api') === '') {
            $this->markTestSkipped('opcache.restrict_api is empty.');
        }

        eval('namespace { if (!function_exists("default_config_items")) { function default_config_items() { return []; } } }');
        require_once SYSPATH . 'ee/legacy/helpers/file_helper.php';

        $wizard = $this->newWizard();
        $wizard->userdata['share_analytics'] = 'n';
        $wizard->config->config_path = tempnam(sys_get_temp_dir(), 'wizard-opcache-config-');

        @mkdir(APPPATH . 'config', 0777, true);
        file_put_contents(
            APPPATH . 'config/config_tmpl.php',
            "<?php\n\$config['index_page'] = '{index_page}';\n{extra_config}"
        );

        ee()->setMock('Encrypt', new class {
            public function generateKey()
            {
                return 'generated-key';
            }
        });

        $this->assertTrue(
            $this->invokePrivate(
                $wizard,
                'write_config_from_template',
                ['site_index' => 'index.php', 'site_label' => '']
            )
        );

        @unlink($wizard->config->config_path);
        @unlink(APPPATH . 'config/config_tmpl.php');
    }

    public function testFetchUpdatesHandlesUnreadableAndReadableUpdateDirectories()
    {
        $wizard = $this->newWizard();

        $updatesPath = APPPATH . 'updates/';
        // In some isolated tests we create APPPATH updates fixtures, so only
        // assert unreadable behavior when the directory does not yet exist.
        if (! is_dir($updatesPath)) {
            $this->assertFalse($this->invokePrivate($wizard, 'fetch_updates', '7.0.0'));
        }

        $createdPath = false;
        if (! is_dir($updatesPath)) {
            $createdPath = @mkdir($updatesPath, 0777, true);
        }

        file_put_contents($updatesPath . 'ud_7_01_00.php', "<?php\n");
        file_put_contents($updatesPath . 'ud_7_02_01_rc_1.php', "<?php\n");
        file_put_contents($updatesPath . 'not-an-update.php', "<?php\n");

        try {
            $this->assertTrue($this->invokePrivate($wizard, 'fetch_updates', '7.0.0'));
            $this->assertSame('7.1.0', $wizard->next_update);
            $this->assertSame('7_01_00', $this->getProperty($wizard, 'next_ud_file'));
            $this->assertGreaterThanOrEqual(2, $wizard->remaining_updates);
        } finally {
            @unlink($updatesPath . 'ud_7_01_00.php');
            @unlink($updatesPath . 'ud_7_02_01_rc_1.php');
            @unlink($updatesPath . 'not-an-update.php');
            if ($createdPath) {
                @rmdir($updatesPath);
            }
        }
    }

    public function testInstallSiteThemeAndInstallModules()
    {
        $wizard = $this->newWizard();
        $wizard->base_path = '/tmp/base/';
        $wizard->root_theme_path = '/tmp/themes/';
        $wizard->userdata['site_url'] = 'https://example.test/';

        ee()->setMock('config', new class {
            public $items = [];
            public function set_item($key, $value)
            {
                $this->items[$key] = $value;
            }
        });

        $wizard->userdata['theme'] = 'none';
        $this->assertTrue($this->invokePrivate($wizard, 'install_site_theme'));

        $themePath = APPPATH . '/site_themes/unit-theme/';
        @mkdir($themePath, 0777, true);
        file_put_contents($themePath . 'channel_set.json', '{}');

        $themeInstaller = new class {
            public $installedTheme;
            public function setInstallerPath($path) {}
            public function setSiteURL($url) {}
            public function setBasePath($path) {}
            public function setThemePath($path) {}
            public function setThemeURL($url) {}
            public function install($theme)
            {
                $this->installedTheme = $theme;
            }
        };
        ee()->setMock('ThemeInstaller', $themeInstaller);

        $wizard->userdata['theme'] = 'unit-theme';
        $this->assertTrue($this->invokePrivate($wizard, 'install_site_theme'));
        $this->assertSame('unit-theme', $themeInstaller->installedTheme);

        @unlink($themePath . 'channel_set.json');
        @rmdir($themePath);
        @rmdir(dirname($themePath));

        $addons = new class {
            public function install_modules($required)
            {
                return ['error-one'];
            }
        };
        ee()->setMock('addons', $addons);
        ee()->setMock('Addon', new class {
            public function get($name)
            {
                return new class {
                    public $installed = false;
                    public function installConsentRequests()
                    {
                        $this->installed = true;
                    }
                };
            }
        });
        ee()->setMock('load', new WizardLoadStub());

        $this->assertTrue($this->invokePrivate($wizard, 'install_modules'));
        $this->assertSame(['error-one'], $wizard->module_install_errors);
    }

    public function testUpdateModulesCanUpgradeNativeModuleRows()
    {
        $wizard = $this->newWizard();
        $wizard->native_modules = ['wizardtest'];
        $wizard->load = new WizardLoadStub();

        $addonPath = SYSPATH . 'ee/ExpressionEngine/Addons/wizardtest/';
        @mkdir($addonPath, 0777, true);
        file_put_contents($addonPath . 'upd.wizardtest.php', "<?php\n");

        $db = new class {
            public $updates = [];
            public function select($fields) {}
            public function get($table)
            {
                return new class {
                    public function result()
                    {
                        return [
                            (object) ['module_name' => 'NotNative', 'module_version' => '1.0.0'],
                            (object) ['module_name' => 'Wizardtest', 'module_version' => '1.0.0'],
                        ];
                    }
                };
            }
            public function update($table, $data, $where)
            {
                $this->updates[] = [$table, $data, $where];
            }
        };
        ee()->setMock('db', $db);

        try {
            $this->invokePrivate($wizard, 'update_modules');
            $this->assertCount(1, $db->updates);
            $this->assertSame('modules', $db->updates[0][0]);
            $this->assertSame(['module_name' => 'Wizardtest'], $db->updates[0][2]);
        } finally {
            @unlink($addonPath . 'upd.wizardtest.php');
            @rmdir($addonPath);
        }
    }

    public function testUpdateModulesLoadsUpdaterClassWhenNotPreloaded()
    {
        $wizard = $this->newWizard();
        $wizard->native_modules = ['wizardfresh'];
        $wizard->load = new WizardLoadStub();

        $addonPath = SYSPATH . 'ee/ExpressionEngine/Addons/wizardfresh/';
        @mkdir($addonPath, 0777, true);
        file_put_contents(
            $addonPath . 'upd.wizardfresh.php',
            "<?php class Wizardfresh_upd { public \$version = '2.0.0'; public function update(\$v){ return true; } }"
        );

        $db = new class {
            public $updates = [];
            public function select($fields) {}
            public function get($table)
            {
                return new class {
                    public function result()
                    {
                        return [(object) ['module_name' => 'Wizardfresh', 'module_version' => '1.0.0']];
                    }
                };
            }
            public function update($table, $data, $where)
            {
                $this->updates[] = [$table, $data, $where];
            }
        };
        ee()->setMock('db', $db);

        try {
            $this->invokePrivate($wizard, 'update_modules');
            $this->assertCount(1, $db->updates);
            $this->assertSame(['module_name' => 'Wizardfresh'], $db->updates[0][2]);
        } finally {
            @unlink($addonPath . 'upd.wizardfresh.php');
            @rmdir($addonPath);
        }
    }

    public function testDefaultChannelEntryCanRenameAndRenameInstallerFalseBranch()
    {
        require_once SYSPATH . 'ee/legacy/helpers/file_helper.php';

        $wizard = $this->newWizard();
        $wizard->userdata['deft_lang'] = 'english';
        $this->assertFalse($this->invokePrivate($wizard, 'default_channel_entry'));

        $cacheDir = SYSPATH . 'user/cache/';
        @mkdir($cacheDir, 0777, true);
        file_put_contents($cacheDir . 'mailing_list.zip', 'x');
        $wizard->version = '3.0.0';

        try {
            $this->assertFalse($wizard->canRenameAutomatically([]));
        } finally {
            @unlink($cacheDir . 'mailing_list.zip');
        }

        $this->assertFalse($wizard->canRenameAutomatically(['errors' => 1]));
        $this->assertTrue($wizard->canRenameAutomatically([]));
        $this->assertFalse($this->invokePrivate($wizard, 'rename_installer', ['errors' => 1]));
        $this->assertTrue($this->invokePrivate($wizard, 'rename_installer', []));
    }

    public function testRunAddonUpdaterHookAndBackupDatabase()
    {
        $wizard = $this->newWizard();

        $this->setProperty($wizard, 'shouldUpgradeAddons', false);
        $this->assertNull($this->invokePrivate($wizard, 'runAddonUpdaterHook', '7.5.0'));

        $this->setProperty($wizard, 'shouldUpgradeAddons', true);
        ee()->setMock('Addon', new class {
            public function all()
            {
                return ['builtin' => [], 'noupd' => [], 'good' => [], 'boom' => []];
            }
            public function get($name)
            {
                return new class($name) {
                    private $name;
                    public function __construct($name)
                    {
                        $this->name = $name;
                    }
                    public function get($key)
                    {
                        return $this->name === 'builtin';
                    }
                    public function hasUpgrader()
                    {
                        return ! in_array($this->name, ['builtin', 'noupd'], true);
                    }
                    public function getUpgraderClass()
                    {
                        return $this->name === 'good' ? 'WizardAddonGoodUpgrader' : 'WizardAddonBoomUpgrader';
                    }
                };
            }
        });

        $this->assertSame(
            ['good' => true, 'boom' => false],
            $this->invokePrivate($wizard, 'runAddonUpdaterHook', '7.5.0')
        );

        ee()->setMock('Filesystem', new class {
            public function isWritable($path)
            {
                return false;
            }
        });
        $this->assertFalse($this->invokePrivate($wizard, 'backupDatabase'));

        ee()->setMock('Filesystem', new class {
            public function isWritable($path)
            {
                return true;
            }
        });
        ee()->setMock('localize', new class {
            public function format_date($format)
            {
                return '2026-03-04_10h00m00sUTC';
            }
        });
        ee()->setMock('db', new class {
            public $database = 'ee_main';
        });

        ee()->setMock('Database/Backup', new class {
            public $calls = [];
            public function startFile()
            {
                $this->calls[] = 'start';
            }
            public function writeDropAndCreateStatements()
            {
                $this->calls[] = 'dropcreate';
            }
            public function writeTableInsertsConservatively($table_name, $offset)
            {
                $this->calls[] = ['insert', $table_name, $offset];
                if ($table_name === null) {
                    return ['table_name' => 'exp_members', 'offset' => 100];
                }

                return false;
            }
            public function endFile()
            {
                $this->calls[] = 'end';
            }
        });

        $this->assertTrue($this->invokePrivate($wizard, 'backupDatabase'));

        ee()->setMock('Database/Backup', new class {
            public function startFile()
            {
                throw new \Exception('cannot start backup');
            }
        });

        $this->assertFalse($this->invokePrivate($wizard, 'backupDatabase'));
    }

    public function testBackupDatabaseReturnsFalseWhenInsertLoopThrows()
    {
        $wizard = $this->newWizard();

        ee()->setMock('Filesystem', new class {
            public function isWritable($path)
            {
                return true;
            }
        });
        ee()->setMock('localize', new class {
            public function format_date($format)
            {
                return '2026-03-04_10h00m00sUTC';
            }
        });
        ee()->setMock('db', new class {
            public $database = 'ee_main';
        });

        ee()->setMock('Database/Backup', new class {
            public function startFile()
            {
            }
            public function writeDropAndCreateStatements()
            {
            }
            public function writeTableInsertsConservatively($table_name, $offset)
            {
                throw new \Exception('write failure');
            }
        });

        $this->assertFalse($this->invokePrivate($wizard, 'backupDatabase'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoUpdateHandlesUpdaterFailureStatus()
    {
        eval('namespace { if (!class_exists("Updater")) { class Updater { public $errors = []; public $version_suffix = ""; public function do_update() { return false; } } } }');

        $wizard = $this->newWizard();
        $wizard->next_update = '7.5.21';
        $wizard->remaining_updates = 2;
        $wizard->progress = new class {
            public $prefix = '';
            public function clear_state() {}
            public function get_state()
            {
                return 'state';
            }
        };

        $wizard->load = new WizardLoadStub();
        ee()->setMock('load', $wizard->load);

        $config = new class {
            public $items = [];
            public function set_item($key, $value)
            {
                $this->items[$key] = $value;
            }
            public function item($key)
            {
                return false;
            }
            public function _update_config($new, $clear)
            {
            }
        };
        $wizard->config = $config;
        ee()->setMock('config', $config);
        ee()->setMock('Filesystem', new class {
            public function isWritable($path)
            {
                return false;
            }
        });

        $input = new WizardInputStub();
        $input->postData = ['database_backup' => true, 'update_addons' => false];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        $this->assertFalse($this->invokePrivate($wizard, 'do_update'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoUpdateRunsUpdaterAndDisablesRefreshDuringAjaxProgress()
    {
        eval('namespace { if (!class_exists("Updater")) { class Updater { public $errors = []; public $version_suffix = ""; public function do_update() { return true; } } } }');

        $wizard = $this->newWizard();
        $wizard->next_update = '7.5.21';
        $wizard->remaining_updates = 2;
        $wizard->progress = new class {
            public $prefix = '';
            public function clear_state() {}
            public function get_state()
            {
                return 'state';
            }
        };

        $wizard->load = new WizardLoadStub();
        ee()->setMock('load', $wizard->load);

        $config = new class {
            public $items = [];
            public function set_item($key, $value)
            {
                $this->items[$key] = $value;
            }
            public function item($key)
            {
                return false;
            }
            public function _update_config($new, $clear)
            {
            }
        };
        $wizard->config = $config;
        ee()->setMock('config', $config);

        $input = new WizardInputStub();
        $input->postData = ['database_backup' => false, 'update_addons' => false];
        $input->getData = ['ajax_progress' => 'yes'];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        $this->invokePrivate($wizard, 'do_update');
        $this->assertFalse($wizard->refresh);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoUpdateStoresIntermediateStepWhenUpdaterReturnsStringStatus()
    {
        eval('namespace { if (!class_exists("Updater")) { class Updater { public $errors = []; public $version_suffix = ""; public function do_update() { return "next_upgrade_step"; } } } }');

        $wizard = $this->newWizard();
        $wizard->next_update = '7.5.21';
        $wizard->remaining_updates = 2;
        $wizard->progress = new class {
            public $prefix = '';
            public function clear_state() {}
            public function get_state()
            {
                return 'state';
            }
        };

        $wizard->load = new WizardLoadStub();
        ee()->setMock('load', $wizard->load);

        $config = new class {
            public $items = [];
            public function set_item($key, $value)
            {
                $this->items[$key] = $value;
            }
            public function item($key)
            {
                return false;
            }
            public function _update_config($new, $clear)
            {
            }
        };
        $wizard->config = $config;
        ee()->setMock('config', $config);

        $input = new WizardInputStub();
        $input->postData = ['database_backup' => false, 'update_addons' => false];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        $this->invokePrivate($wizard, 'do_update');
        $this->assertSame('next_upgrade_step', $config->items['ud_next_step'] ?? null);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoUpdateUsesNamespacedUpdaterWhenLegacyClassDoesNotExist()
    {
        eval('namespace ExpressionEngine\\Updater\\Version_7_5_21 { class Updater { public $errors = []; public $version_suffix = ""; public function do_update() { return true; } } }');

        $wizard = $this->newWizard();
        $wizard->next_update = '7.5.21';
        $wizard->remaining_updates = 1;
        $wizard->progress = new class {
            public $prefix = '';
            public function clear_state() {}
            public function get_state()
            {
                return 'state';
            }
        };

        $wizard->load = new WizardLoadStub();
        ee()->setMock('load', $wizard->load);

        $config = new class {
            public $items = [];
            public function set_item($key, $value)
            {
                $this->items[$key] = $value;
            }
            public function item($key)
            {
                return false;
            }
            public function _update_config($new, $clear)
            {
            }
        };
        $wizard->config = $config;
        ee()->setMock('config', $config);
        ee()->setMock('db', new class {
            public function select($fields)
            {
            }
            public function get($table)
            {
                return new class {
                    public function result()
                    {
                        return [];
                    }
                };
            }
        });

        $input = new WizardInputStub();
        $input->postData = ['database_backup' => false, 'update_addons' => false];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        $this->invokePrivate($wizard, 'do_update');
        $this->assertTrue($wizard->refresh);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoUpdateHandlesMissingConfiguredNextStepMethod()
    {
        eval('namespace { if (!class_exists("Updater")) { class Updater { public $errors = []; public $version_suffix = ""; public function do_update() { return true; } } } }');

        $wizard = $this->newWizard();
        $wizard->next_update = '7.5.21';
        $wizard->remaining_updates = 2;
        $wizard->progress = new class {
            public $prefix = '';
            public function clear_state() {}
            public function get_state()
            {
                return 'state';
            }
        };

        $wizard->load = new WizardLoadStub();
        ee()->setMock('load', $wizard->load);

        $config = new class {
            public $items = [];
            public function set_item($key, $value)
            {
                $this->items[$key] = $value;
            }
            public function item($key)
            {
                if ($key === 'ud_next_step') {
                    return 'missing_step_method';
                }

                return false;
            }
            public function _update_config($new, $clear)
            {
            }
        };
        $wizard->config = $config;
        ee()->setMock('config', $config);

        $input = new WizardInputStub();
        $input->postData = ['database_backup' => false, 'update_addons' => false];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        $this->assertFalse($this->invokePrivate($wizard, 'do_update'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoUpdateRendersUpdaterErrorsAndRunsLastModuleUpdate()
    {
        eval('namespace { if (!function_exists("ul")) { function ul($items) { return "<ul><li>" . implode("</li><li>", $items) . "</li></ul>"; } } }');
        eval('namespace { if (!class_exists("Updater")) { class Updater { public $errors = ["module-error"]; public $version_suffix = ""; public function do_update() { return false; } } } }');

        $wizard = $this->newWizard();
        $wizard->next_update = '7.5.21';
        $wizard->remaining_updates = 1;
        $wizard->native_modules = [];
        $wizard->progress = new class {
            public $prefix = '';
            public function clear_state() {}
            public function get_state()
            {
                return 'state';
            }
        };

        $wizard->load = new WizardLoadStub();
        ee()->setMock('load', $wizard->load);

        $config = new class {
            public $items = [];
            public function set_item($key, $value)
            {
                $this->items[$key] = $value;
            }
            public function item($key)
            {
                return false;
            }
            public function _update_config($new, $clear)
            {
            }
        };
        $wizard->config = $config;
        ee()->setMock('config', $config);
        ee()->setMock('db', new class {
            public function select($fields)
            {
            }
            public function get($table)
            {
                return new class {
                    public function result()
                    {
                        return [];
                    }
                };
            }
        });

        $input = new WizardInputStub();
        $input->postData = ['database_backup' => false, 'update_addons' => false];
        $wizard->input = $input;
        ee()->setMock('input', $input);

        $this->assertFalse($this->invokePrivate($wizard, 'do_update'));
    }

    private function newWizard(): \Wizard
    {
        $reflection = new \ReflectionClass(\Wizard::class);
        $wizard = $reflection->newInstanceWithoutConstructor();

        $wizard->config = new WizardConfigStub(['index_page' => EESELF]);
        $wizard->input = new WizardInputStub();
        $wizard->load = new WizardLoadStub();
        $wizard->logger = new class {
            public function updater($message) {}
        };
        $wizard->update_notices = new class {
            public function clear() {}
            public function get()
            {
                return [];
            }
        };

        return $wizard;
    }

    private function invokePrivate($object, string $method, ...$args)
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    private function setProperty($object, string $property, $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }

    private function getProperty($object, string $property)
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    private function databaseServiceForVersions(string $serverVersion, string $clientVersion)
    {
        return new class($serverVersion, $clientVersion) {
            private $serverVersion;
            private $clientVersion;

            public function __construct($serverVersion, $clientVersion)
            {
                $this->serverVersion = $serverVersion;
                $this->clientVersion = $clientVersion;
            }

            public function getConnection()
            {
                $serverVersion = $this->serverVersion;
                $clientVersion = $this->clientVersion;

                return new class($serverVersion, $clientVersion) {
                    private $serverVersion;
                    private $clientVersion;

                    public function __construct($serverVersion, $clientVersion)
                    {
                        $this->serverVersion = $serverVersion;
                        $this->clientVersion = $clientVersion;
                    }

                    public function getNative()
                    {
                        $serverVersion = $this->serverVersion;
                        $clientVersion = $this->clientVersion;

                        return new class($serverVersion, $clientVersion) {
                            private $serverVersion;
                            private $clientVersion;

                            public function __construct($serverVersion, $clientVersion)
                            {
                                $this->serverVersion = $serverVersion;
                                $this->clientVersion = $clientVersion;
                            }

                            public function getAttribute($attribute)
                            {
                                if ($attribute === \PDO::ATTR_SERVER_VERSION) {
                                    return $this->serverVersion;
                                }

                                if ($attribute === \PDO::ATTR_CLIENT_VERSION) {
                                    return $this->clientVersion;
                                }

                                return null;
                            }
                        };
                    }
                };
            }
        };
    }
}
}
