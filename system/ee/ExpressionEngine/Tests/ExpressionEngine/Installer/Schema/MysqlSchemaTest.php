<?php

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Schema;

use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/installer/schema/mysql_schema.php';
require_once SYSPATH . 'ee/language/english/email_data.php';

if (! defined('USERNAME_MAX_LENGTH')) {
    define('USERNAME_MAX_LENGTH', 100);
}
if (! defined('URL_TITLE_MAX_LENGTH')) {
    define('URL_TITLE_MAX_LENGTH', 75);
}

class MysqlSchemaDbMock
{
    public $char_set = 'utf8mb4';
    public $dbcollat = 'utf8mb4_unicode_ci';
    public $queries = [];
    public $listTables = ['exp_legacy'];
    public $failOnCreate = false;

    public function escape_like_str($value)
    {
        return str_replace('_', '\_', $value);
    }

    public function escape_str($value)
    {
        return addslashes($value);
    }

    public function insert_string($table, $data)
    {
        return "INSERT INTO {$table} (`site_id`) VALUES (" . (int) $data['site_id'] . ")";
    }

    public function list_tables($constrain = true)
    {
        return $this->listTables;
    }

    public function query($sql)
    {
        $this->queries[] = $sql;

        if ($this->failOnCreate && strncmp($sql, 'CREATE TABLE', 12) === 0) {
            return false;
        }

        return true;
    }
}

class MysqlSchemaConfigMock
{
    public function loadFile($name)
    {
        return [
            'defaults' => ['bold', 'italic'],
            'buttons' => [
                'bold' => [
                    'tag_name' => 'Bold',
                    'tag_open' => '<b>',
                    'tag_close' => '</b>',
                    'accesskey' => 'b',
                    'classname' => 'bold',
                ],
                'italic' => [
                    'tag_name' => 'Italic',
                    'tag_open' => '<i>',
                    'tag_close' => '</i>',
                    'accesskey' => 'i',
                    'classname' => 'italic',
                ],
            ],
        ];
    }
}

class MysqlSchemaInputMock
{
    public function ip_address()
    {
        return '127.0.0.1';
    }
}

class MysqlSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testSqlFindLikeBuildsEscapedPrefixQuery()
    {
        $db = new MysqlSchemaDbMock();
        ee()->setMock('db', $db);

        $schema = new \EE_Schema();
        $schema->userdata = ['db_prefix' => 'exp_'];

        $this->assertSame("SHOW tables LIKE 'exp\\_%'", $schema->sql_find_like());
    }

    public function testInstallTablesAndDataBuildsSqlAndReturnsTrueOnSuccess()
    {
        $db = new MysqlSchemaDbMock();
        ee()->setMock('db', $db);
        ee()->setMock('input', new MysqlSchemaInputMock());
        ee()->setMock('config', new MysqlSchemaConfigMock());

        $schema = $this->makeSchemaFixture($db);

        $result = $schema->install_tables_and_data();

        $this->assertTrue($result);
        $this->assertNotEmpty($db->queries);
        $this->assertStringStartsWith('DROP TABLE IF EXISTS exp_legacy', $db->queries[0]);
        $this->assertGreaterThan(100, count($db->queries));
        $this->assertGreaterThan(
            0,
            count(array_filter($db->queries, function ($query) {
                return strpos($query, 'ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci') !== false;
            }))
        );
        $this->assertGreaterThan(
            0,
            count(array_filter($db->queries, function ($query) {
                return strpos($query, 'INSERT INTO exp_permissions') !== false;
            }))
        );
    }

    public function testInstallTablesAndDataReturnsFalseAndDropsTablesWhenCreateFails()
    {
        $db = new MysqlSchemaDbMock();
        $db->failOnCreate = true;
        $db->listTables = ['exp_old_a', 'exp_old_b'];
        ee()->setMock('db', $db);
        ee()->setMock('input', new MysqlSchemaInputMock());
        ee()->setMock('config', new MysqlSchemaConfigMock());

        $schema = $this->makeSchemaFixture($db);
        $schema->DB = new class {
            public function list_tables($constrain = true)
            {
                return ['exp_cleanup_1', 'exp_cleanup_2'];
            }
        };

        $result = $schema->install_tables_and_data();

        $this->assertFalse($result);
        $this->assertGreaterThan(
            0,
            count(array_filter($db->queries, function ($query) {
                return strpos($query, 'DROP TABLE IF EXISTS exp_cleanup_1') === 0
                    || strpos($query, 'DROP TABLE IF EXISTS exp_cleanup_2') === 0;
            }))
        );
    }

    /**
     * Ensure fresh installations store a year that is resolved at render time.
     *
     * @return void
     */
    public function testPostInstallMessageTemplateUsesDynamicCopyrightYear()
    {
        $template = \post_install_message_template();

        $this->assertStringContainsString('&copy;{current_time format="%Y"}', $template);
        $this->assertDoesNotMatchRegularExpression('/&copy;\d{4}/', $template);
    }

    private function makeSchemaFixture(MysqlSchemaDbMock $db): \EE_Schema
    {
        $schema = new class extends \EE_Schema {
            public $DB;
        };
        $schema->now = 1700000000;
        $schema->version = '7.6.0';
        $schema->userdata = [
            'db_prefix' => 'exp_',
            'username' => 'admin',
            'password' => 'hashed',
            'salt' => 'salt',
            'unique_id' => 'unique',
            'email_address' => 'admin@example.com',
            'screen_name' => 'Admin',
            'default_site_timezone' => 'UTC',
            'deft_lang' => 'english',
            'site_label' => 'Site Label',
            'site_name' => 'default_site',
        ];
        $schema->DB = $db;

        return $schema;
    }
}
