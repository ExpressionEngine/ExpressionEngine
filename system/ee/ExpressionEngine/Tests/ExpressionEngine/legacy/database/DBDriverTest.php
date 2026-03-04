<?php

if (! function_exists('demo')) {
    function demo($value = '')
    {
        return 'demo:' . $value;
    }
}

if (! function_exists('demo_helper')) {
    function demo_helper($left = 0, $right = 0)
    {
        return $left + $right;
    }
}

if (! function_exists('load_class')) {
    function load_class($class, $directory = 'libraries')
    {
        if ($class === 'Lang') {
            return new class {
                public function load($file)
                {
                }

                public function line($key)
                {
                    if ($key === 'db_error_heading') {
                        return 'Database Error';
                    }

                    return $key;
                }
            };
        }

        if ($class === 'Exceptions') {
            return new class {
                public function show_error($heading, $message)
                {
                    if (! empty($GLOBALS['db_driver_test_throw_show_error'])) {
                        throw new RuntimeException('show_error short-circuit');
                    }

                    return $heading . ':' . implode('|', (array) $message);
                }
            };
        }

        return new stdClass();
    }
}

require_once SYSPATH . 'ee/legacy/database/DB_driver.php';

use PHPUnit\Framework\TestCase;

class DBDriverTest extends TestCase
{
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testInitializeCallsConnectionOpen(): void
    {
        $driver = new DBDriverMethodHarness([]);
        $connection = new DBDriverConnectionStub();
        $driver->connection = $connection;

        $this->assertTrue($driver->initialize());
        $this->assertTrue($connection->openCalled);
    }

    public function testDbSetCharsetLoadsLoggerAndReturnsTrue(): void
    {
        $driver = new DBDriverMethodHarness([]);
        $load = new DBDriverLoadStub();
        $logger = new DBDriverLoggerStub();
        ee()->setMock('load', $load);
        ee()->setMock('logger', $logger);

        $this->assertTrue($driver->db_set_charset('utf8mb4', 'utf8mb4_unicode_ci'));
        $this->assertSame(['logger'], $load->libraries);
        $this->assertSame(['3.0'], $logger->deprecatedCalls);
    }

    public function testPlatformReturnsDriverName(): void
    {
        $driver = new DBDriverMethodHarness(['dbdriver' => 'mock']);
        $this->assertSame('mock', $driver->platform());
    }

    public function testVersionHandlesUnsupportedAndSuccessfulPaths(): void
    {
        $driver = new DBDriverMethodHarness([]);
        $driver->versionSql = false;
        $driver->db_debug = false;
        $this->assertFalse($driver->version());

        $driver->db_debug = true;
        $this->assertSame('display_error', $driver->version());

        $driver->versionSql = 'SELECT version() AS ver';
        $driver->queryResponse = new DBDriverQueryResultStub(1, [], (object) ['ver' => '8.0.0'], []);
        $this->assertSame('8.0.0', $driver->version());
    }

    public function testQueryHandlesEmptySqlFailureWriteAndReadPaths(): void
    {
        $driver = new DBDriverQueryHarness([]);

        $driver->db_debug = false;
        $this->assertFalse($driver->query(''));

        $driver->db_debug = true;
        $this->assertSame('display_error', $driver->query(''));

        $driver->db_debug = false;
        $driver->dbprefix = 'exp_';
        $driver->swap_pre = 'swap_';
        $driver->simpleQueryReturn = true;
        $this->assertTrue($driver->query('INSERT INTO swap_members VALUES (?)', ['alpha']));
        $this->assertSame("INSERT INTO exp_members VALUES ('alpha')", $driver->capturedSimpleQuerySql);
        $this->assertSame(1, $driver->query_count);

        $driver->simpleQueryReturn = false;
        $driver->db_debug = false;
        $this->assertFalse($driver->query('SELECT 1'));
        $this->assertFalse($driver->_trans_status);

        $driver->simpleQueryReturn = false;
        $driver->db_debug = true;
        $driver->_trans_status = true;
        $this->assertSame('display_error', $driver->query('SELECT 2'));
        $this->assertTrue($driver->transCompleteCalled);

        $driver->simpleQueryReturn = (object) ['result' => true];
        $driver->db_debug = false;
        $read = $driver->query('SELECT * FROM exp_members');
        $this->assertInstanceOf(DBDriverReadResult::class, $read);
        $this->assertSame(3, $read->num_rows);
    }

    public function testLoadRdriverReturnsExpectedClassName(): void
    {
        $driver = new DBDriverMethodHarness([]);
        $class = $driver->load_rdriver();

        $this->assertSame('CI_DB_mysqli_result', $class);
        $this->assertTrue(class_exists($class));
    }

    public function testSimpleQueryInitializesClosedConnectionAndExecutesSql(): void
    {
        $driver = new DBDriverMethodHarness([]);
        $connection = new DBDriverConnectionStub();
        $connection->isOpen = false;
        $driver->connection = $connection;

        $result = $driver->simple_query('SELECT 1');

        $this->assertTrue($connection->openCalled);
        $this->assertSame('SELECT 1', $driver->last_query);
        $this->assertSame('executed:SELECT 1', $result);
    }

    public function testTransactionHelpersCoverAllBranches(): void
    {
        $driver = new DBDriverMethodHarness([]);
        $driver->trans_off();
        $this->assertFalse($driver->trans_enabled);

        $driver->trans_strict('invalid');
        $this->assertTrue($driver->trans_strict);
        $driver->trans_strict(false);
        $this->assertFalse($driver->trans_strict);

        $driver->trans_enabled = false;
        $this->assertFalse($driver->trans_start());
        $this->assertFalse($driver->trans_complete());

        $driver->trans_enabled = true;
        $driver->_trans_depth = 1;
        $driver->trans_start();
        $this->assertSame(2, $driver->_trans_depth);
        $this->assertTrue($driver->trans_complete());
        $this->assertSame(1, $driver->_trans_depth);

        $driver->_trans_depth = 0;
        $driver->trans_start(true);
        $this->assertSame([true], $driver->transBeginCalls);

        $driver->_trans_status = false;
        $driver->trans_strict = false;
        $this->assertFalse($driver->trans_complete());
        $this->assertTrue($driver->transRollbackCalled);
        $this->assertTrue($driver->_trans_status);

        $driver->_trans_status = true;
        $this->assertTrue($driver->trans_complete());
        $this->assertTrue($driver->transCommitCalled);
    }

    public function testTransStatusCompileBindsAndWriteTypeHelpers(): void
    {
        $driver = new DBDriverMethodHarness([]);
        $driver->_trans_status = false;
        $this->assertFalse($driver->trans_status());

        $this->assertSame('SELECT 1', $driver->compile_binds('SELECT 1', ['a']));
        $this->assertSame("SELECT 'esc-a' + 'esc-b'", $driver->compile_binds('SELECT ? + ?', ['a', 'b', 'extra']));
        $this->assertSame("SELECT 'esc-x'", $driver->compile_binds('SELECT ?', 'x'));

        $this->assertTrue($driver->is_write_type('INSERT INTO exp_members VALUES (1)'));
        $this->assertFalse($driver->is_write_type('SELECT * FROM exp_members'));
    }

    public function testElapsedTotalLastAndEscapeHelpers(): void
    {
        $driver = new DBDriverMethodHarness([]);
        $driver->benchmark = 1.23456789;
        $driver->query_count = 9;
        $driver->last_query = 'SELECT NOW()';

        $this->assertSame('1.234568', $driver->elapsed_time());
        $this->assertSame(9, $driver->total_queries());
        $this->assertSame('SELECT NOW()', $driver->last_query());

        $this->assertSame("'esc-alpha'", $driver->escape('alpha'));
        $this->assertSame(1, $driver->escape(true));
        $this->assertSame(0, $driver->escape(false));
        $this->assertSame('NULL', $driver->escape(null));
        $this->assertSame('NULL', $driver->escape((object) ['x' => 1]));
        $this->assertSame(5, $driver->escape(5));
        $this->assertSame(5.5, $driver->escape(5.5));
        $this->assertSame('esc-like%', $driver->escape_like_str('like%'));
    }

    public function testPrimaryListTablesAndTableExists(): void
    {
        $driver = new DBDriverMethodHarness([]);
        $driver->listFieldsResponse = ['id', 'title'];
        $this->assertSame('id', $driver->primary('exp_members'));

        $driver->listFieldsResponse = false;
        $this->assertFalse($driver->primary('exp_members'));

        $driver->listTablesSql = false;
        $driver->listTablesResponse = null;
        $driver->db_debug = false;
        $this->assertFalse($driver->list_tables());

        $driver->db_debug = true;
        $this->assertSame('display_error', $driver->list_tables());

        $driver->db_debug = false;
        $driver->listTablesSql = 'SHOW TABLES';
        $driver->queryResponse = new DBDriverQueryResultStub(2, [
            ['TABLE_NAME' => 'exp_members'],
            ['0' => 'exp_titles'],
        ], null, []);
        $tables = $driver->list_tables();
        $this->assertSame(['exp_members', 'exp_titles'], $tables);
        $this->assertSame($tables, $driver->list_tables());

        $driver->dbprefix = 'exp_';
        $driver->listTablesResponse = ['exp_members'];
        $this->assertTrue($driver->table_exists('members'));
        $this->assertFalse($driver->table_exists('titles'));
    }

    public function testListFieldsFieldExistsAndFieldData(): void
    {
        $driver = new DBDriverMethodHarness([]);
        $driver->data_cache['field_names']['members'] = ['id'];
        $this->assertSame(['id'], $driver->list_fields('members'));

        $driver = new DBDriverMethodHarness([]);
        $driver->listFieldsResponse = null;
        $driver->db_debug = false;
        $this->assertSame([], $driver->list_fields(''));
        $driver->db_debug = true;
        $this->assertSame('display_error', $driver->list_fields(''));

        $config = ee()->config;
        $config->setItem('legacy_members', 'n');
        ee()->setMock('config', $config);
        $driver = new DBDriverMethodHarness([]);
        $driver->listFieldsResponse = null;
        $this->assertSame([], $driver->list_fields('members'));

        $config = ee()->config;
        $config->setItem('legacy_members', 'y');
        ee()->setMock('config', $config);

        $driver = new DBDriverMethodHarness([]);
        $driver->listFieldsResponse = null;
        $driver->listColumnsSql = false;
        $driver->db_debug = false;
        $this->assertSame([], $driver->list_fields('members'));
        $driver->db_debug = true;
        $this->assertSame('display_error', $driver->list_fields('members'));

        $driver = new DBDriverMethodHarness([]);
        $driver->listFieldsResponse = null;
        $driver->queryResponse = new DBDriverQueryResultStub(2, [
            ['COLUMN_NAME' => 'id'],
            ['0' => 'title'],
        ], null, []);
        $fields = $driver->list_fields('members');
        $this->assertSame(['id', 'title'], $fields);
        $this->assertSame($fields, $driver->list_fields('members'));

        $driver->listFieldsResponse = ['id', 'title'];
        $this->assertTrue($driver->field_exists('id', 'members'));
        $this->assertFalse($driver->field_exists('body', 'members'));

        $driver->db_debug = false;
        $this->assertFalse($driver->field_data(''));
        $driver->db_debug = true;
        $this->assertSame('display_error', $driver->field_data(''));

        $driver->db_debug = false;
        $driver->queryResponse = new DBDriverQueryResultStub(1, [], null, ['x' => 'y']);
        $this->assertSame(['x' => 'y'], $driver->field_data('members'));
    }

    public function testInsertAndUpdateStringBuildersAndOperators(): void
    {
        $driver = new DBDriverMethodHarness([]);

        $insert = $driver->insert_string('members', ['name' => 'alpha', 'active' => true]);
        $this->assertStringContainsString('INSERT INTO "members"', $insert);
        $this->assertStringContainsString('"name"', $insert);
        $this->assertStringContainsString("'esc-alpha'", $insert);

        $this->assertFalse($driver->update_string('members', ['name' => 'a'], ''));

        $updateSimple = $driver->update_string('members', ['name' => 'alpha'], 'id = 1');
        $this->assertStringContainsString('UPDATE "members" SET', $updateSimple);
        $this->assertStringContainsString('"name" = \'esc-alpha\'', $updateSimple);

        $updateArray = $driver->update_string('members', ['name' => 'alpha'], ['id' => 1, 'status >' => 2]);
        $this->assertStringContainsString('UPDATE "members" SET', $updateArray);
        $this->assertStringContainsString('"name" = \'esc-alpha\'', $updateArray);

        $this->assertTrue($driver->_has_operator('status >'));
        $this->assertFalse($driver->_has_operator('status'));
    }

    public function testCallFunctionCachingAndClose(): void
    {
        $driver = new DBDriverMethodHarness(['dbdriver' => 'demo']);

        try {
            $driver->call_function('demo');
            $this->fail('Expected null args TypeError on PHP 8+');
        } catch (Throwable $exception) {
            $this->assertStringContainsString('call_user_func_array', $exception->getMessage());
        }

        try {
            $driver->call_function('helper', 2, 3);
            $this->fail('Expected argument by-reference error on PHP 8+');
        } catch (Throwable $exception) {
            $this->assertStringContainsString('array_splice', $exception->getMessage());
        }

        $driver->db_debug = false;
        $this->assertFalse($driver->call_function('missing'));
        $driver->db_debug = true;
        $this->assertSame('display_error', $driver->call_function('missing'));

        $load = new DBDriverLoadStub();
        $logger = new DBDriverLoggerStub();
        ee()->setMock('load', $load);
        ee()->setMock('logger', $logger);
        $this->assertTrue($driver->cache_on());
        $this->assertFalse($driver->cache_off());
        $this->assertSame(['logger', 'logger'], $load->libraries);
        $this->assertSame(['3.0', '3.0'], $logger->deprecatedCalls);

        $connection = new DBDriverConnectionStub();
        $driver->connection = $connection;
        $driver->close();
        $this->assertTrue($connection->closeCalled);
    }

    public function testDisplayErrorThrowsWhenDbExceptionEnabled(): void
    {
        $driver = new DBDriverMethodHarness([]);
        $driver->db_exception = true;

        try {
            $driver->display_error('db_invalid_query', 'x', false);
            $this->fail('Expected exception');
        } catch (Exception $exception) {
            $this->assertStringContainsString('db_invalid_query', $exception->getMessage());
            $this->assertStringContainsString('File location', $exception->getMessage());
        }

        try {
            $driver->display_error(['E1001', 'Something failed'], '', true);
            $this->fail('Expected exception');
        } catch (Exception $exception) {
            $this->assertStringContainsString('Something failed', $exception->getMessage());
            $this->assertStringContainsString('E1001', $exception->getMessage());
            $this->assertStringContainsString('File location', $exception->getMessage());
            $this->assertStringContainsString('Line number', $exception->getMessage());
        }
    }

    public function testDisplayErrorCoversNativeNoSessionPathWithoutTriggeringExit(): void
    {
        $driver = new CI_DB_driver([]);
        ee()->setMock('session', null);
        $GLOBALS['db_driver_test_throw_show_error'] = true;

        try {
            $driver->display_error(['native message'], '', true);
            $this->fail('Expected RuntimeException from Exceptions::show_error short-circuit.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('show_error short-circuit', $exception->getMessage());
        } finally {
            unset($GLOBALS['db_driver_test_throw_show_error']);
        }
    }

    public function testProtectIdentifiersAndEscapeIdentifiersCoverBranches(): void
    {
        $driver = new DBDriverMethodHarness([]);
        $driver->dbprefix = 'exp_';
        $driver->swap_pre = 'swap_';
        $driver->ar_aliased_tables = ['m'];

        $this->assertSame('"exp_members"', $driver->protect_identifiers('members', true));
        $this->assertSame('COUNT(*) AS total', $driver->protect_identifiers('COUNT(*) AS total', true));
        $this->assertSame('"m"."id" AS alias', $driver->protect_identifiers('m.id AS alias', true));
        $this->assertSame('"host"."db"."exp_members"."id"', $driver->protect_identifiers('host.db.swap_members.id', false));
        $this->assertSame('"db"."exp_members"."id"', $driver->protect_identifiers('db.swap_members.id', false));
        $this->assertSame('"swap_members"."exp_id"', $driver->_protect_identifiers('swap_members.id', false, null, false));
        $this->assertSame('"exp_members"."id"', $driver->protect_identifiers('swap_members.id', false));
        $this->assertSame('exp_members', $driver->_protect_identifiers('swap_members', true, false));

        $arrayResult = $driver->protect_identifiers(['name' => 'members.id'], true);
        $this->assertArrayHasKey('"name"', $arrayResult);
        $this->assertSame('"exp_members"."id"', $arrayResult['"name"']);

        $driver->setEscapeChar('"');
        $driver->_reserved_identifiers = ['*', 'COUNT(*)'];
        $this->assertSame('COUNT(*)', $driver->escape_identifiers('COUNT(*)'));
        $this->assertSame(['"name"', 'COUNT(*)'], $driver->escape_identifiers(['name', 'COUNT(*)']));
        $this->assertSame('123', $driver->escape_identifiers('123'));
        $this->assertSame("'quoted'", $driver->escape_identifiers("'quoted'"));
        $this->assertSame('NOW()', $driver->escape_identifiers('NOW()'));
        $this->assertSame('table.COUNT(*)', $driver->escape_identifiers('table.COUNT(*)'));
        $this->assertSame('"table"."column"', $driver->escape_identifiers('table.column'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLoadRdriverIncludesFilesWhenClassMissingInIsolatedProcess(): void
    {
        if (class_exists('CI_DB_mysqli_result', false)) {
            $this->markTestSkipped('CI_DB_mysqli_result already loaded in this isolated process.');
        }

        $driver = new CI_DB_driver([]);
        $class = $driver->load_rdriver();

        $this->assertSame('CI_DB_mysqli_result', $class);
        $this->assertTrue(class_exists('CI_DB_mysqli_result', false));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEscapeIdentifiersCoversArrayEscapeCharAndReservedDotPathInIsolatedProcess(): void
    {
        $driver = new CI_DB_driver([]);
        $driver->_reserved_identifiers = ['tail'];

        $property = new ReflectionProperty(CI_DB_driver::class, '_escape_char');
        $property->setAccessible(true);
        $property->setValue($driver, ['[', ']']);

        $this->assertSame('[table].[column]', $driver->escape_identifiers('table.column'));
        $this->assertSame('[table].tail', $driver->escape_identifiers('table.tail'));
    }

}

class DBDriverMethodHarness extends CI_DB_driver
{
    public $displayErrorCalls = [];
    public $querySqls = [];
    public $queryResponse;
    public $versionSql = 'SELECT version() AS ver';
    public $listTablesSql = 'SHOW TABLES';
    public $listColumnsSql = 'SHOW COLUMNS FROM table';
    public $fieldDataSql = 'SELECT * FROM table LIMIT 1';
    public $transBeginCalls = [];
    public $transCommitCalled = false;
    public $transRollbackCalled = false;
    public $listFieldsResponse = ['id'];
    public $listTablesResponse = ['exp_members'];
    public $ar_aliased_tables = [];
    public $ar_limit = 0;

    public function display_error($error = '', $swap = '', $native = false)
    {
        if ($this->db_exception) {
            return parent::display_error($error, $swap, $native);
        }

        $this->displayErrorCalls[] = [$error, $swap, $native];

        return 'display_error';
    }

    public function _execute($sql)
    {
        return 'executed:' . $sql;
    }

    public function _version()
    {
        return $this->versionSql;
    }

    public function query($sql, $binds = false, $return_object = true)
    {
        $this->querySqls[] = $sql;

        return $this->queryResponse ?? new DBDriverQueryResultStub(0, [], null, []);
    }

    public function _list_tables($constrain_by_prefix = false)
    {
        return $this->listTablesSql;
    }

    public function list_tables($constrain_by_prefix = false)
    {
        if ($this->listTablesResponse !== null) {
            return $this->listTablesResponse;
        }

        return parent::list_tables($constrain_by_prefix);
    }

    public function _list_columns($table = '')
    {
        return $this->listColumnsSql;
    }

    public function _field_data($table = '')
    {
        return $this->fieldDataSql;
    }

    public function trans_begin($test_mode = false)
    {
        $this->transBeginCalls[] = $test_mode;
    }

    public function trans_commit()
    {
        $this->transCommitCalled = true;
    }

    public function trans_rollback()
    {
        $this->transRollbackCalled = true;
    }

    public function escape_str($str, $like = false)
    {
        return 'esc-' . $str;
    }

    public function list_fields($table = '')
    {
        if ($this->listFieldsResponse !== null) {
            return $this->listFieldsResponse;
        }

        return parent::list_fields($table);
    }

    protected function _compile_wh($qb_key)
    {
        return ' WHERE x = 1';
    }

    protected function _compile_order_by()
    {
        return ' ORDER BY y';
    }

    public function setEscapeChar($value): void
    {
        $this->_escape_char = $value;
    }
}

class DBDriverQueryHarness extends CI_DB_driver
{
    public $simpleQueryReturn = true;
    public $capturedSimpleQuerySql;
    public $displayErrorReturn = 'display_error';
    public $displayErrorCalls = [];
    public $transCompleteCalled = false;
    public $_errorNumber = 1001;
    public $_errorMessage = 'query failure';

    public function simple_query($sql)
    {
        $this->capturedSimpleQuerySql = $sql;

        return $this->simpleQueryReturn;
    }

    public function display_error($error = '', $swap = '', $native = false)
    {
        $this->displayErrorCalls[] = [$error, $swap, $native];

        return $this->displayErrorReturn;
    }

    public function _error_number()
    {
        return $this->_errorNumber;
    }

    public function _error_message()
    {
        return $this->_errorMessage;
    }

    public function trans_complete()
    {
        $this->transCompleteCalled = true;
    }

    public function escape_str($str, $like = false)
    {
        return $str;
    }

    public function load_rdriver()
    {
        return DBDriverReadResult::class;
    }
}

class DBDriverReadResult
{
    public $num_rows = 0;

    public function __construct($result_id)
    {
    }

    public function num_rows()
    {
        return 3;
    }
}

class DBDriverConnectionStub
{
    public $isOpen = false;
    public $openCalled = false;
    public $closeCalled = false;

    public function isOpen()
    {
        return $this->isOpen;
    }

    public function open()
    {
        $this->openCalled = true;
        $this->isOpen = true;
    }

    public function close()
    {
        $this->closeCalled = true;
    }
}

class DBDriverQueryResultStub
{
    private $numRows;
    private $rows;
    private $row;
    private $fieldData;

    public function __construct(int $numRows, array $rows, $row, array $fieldData)
    {
        $this->numRows = $numRows;
        $this->rows = $rows;
        $this->row = $row;
        $this->fieldData = $fieldData;
    }

    public function num_rows()
    {
        return $this->numRows;
    }

    public function result_array()
    {
        return $this->rows;
    }

    public function row($field = null)
    {
        if ($field !== null && is_object($this->row) && isset($this->row->$field)) {
            return $this->row->$field;
        }

        return $this->row;
    }

    public function field_data()
    {
        return $this->fieldData;
    }
}

class DBDriverLoadStub
{
    public $libraries = [];

    public function library($name)
    {
        $this->libraries[] = $name;
    }
}

class DBDriverLoggerStub
{
    public $deprecatedCalls = [];

    public function deprecated($version)
    {
        $this->deprecatedCalls[] = $version;
    }
}
