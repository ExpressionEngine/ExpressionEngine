<?php

require_once SYSPATH . 'ee/legacy/database/drivers/mysqli/mysqli_connection.php';

use PHPUnit\Framework\TestCase;

class MysqliConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testGetConfigReturnsConstructorConfig(): void
    {
        $config = $this->baseConfig();
        $connection = new CI_DB_mysqli_connection($config);

        $this->assertSame($config, $connection->getConfig());
    }

    public function testOpenExecutesPdoConstructionPath(): void
    {
        $config = $this->baseConfig();
        $config['hostname'] = '127.0.0.1';
        $config['port'] = 65535;
        $config['dbcollat_default'] = true;
        $config['MYSQL_ATTR_INIT_COMMAND'] = "SET NAMES 'utf8mb4'";

        $connection = new CI_DB_mysqli_connection($config);

        try {
            $connection->open();
        } catch (Throwable $exception) {
            $this->assertTrue(
                $exception instanceof PDOException || strpos($exception->getMessage(), 'SQLSTATE') !== false
            );
        }
    }

    public function testCloseClearsNativeConnection(): void
    {
        $connection = new CI_DB_mysqli_connection($this->baseConfig());
        $native = new MysqliConnectionNativeStub();

        $this->setProtectedProperty($connection, 'connection', $native);
        $this->assertTrue($connection->isOpen());

        $connection->close();

        $this->assertFalse($connection->isOpen());
        $this->assertNull($connection->getNative());
    }

    public function testQueryAppliesCreateTableEnforcementAndLogsTimings(): void
    {
        $connection = new CI_DB_mysqli_connection($this->baseConfig());
        $native = new MysqliConnectionNativeStub();
        $native->queryReturn = 'query-result';
        $log = new MysqliConnectionLogStub();

        $this->setProtectedProperty($connection, 'connection', $native);
        $this->setProtectedProperty($connection, 'mysqlnd', true);
        $connection->log = $log;

        $result = $connection->query(" CREATE TABLE exp_demo (id INT) ");

        $this->assertSame('query-result', $result);
        $this->assertStringContainsString('CREATE TABLE exp_demo (id INT)', $native->lastQuery);
        $this->assertStringContainsString('CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $native->lastQuery);
        $this->assertStringContainsString('ENGINE=InnoDB', $native->lastQuery);
        $this->assertSame([[PDO::ATTR_EMULATE_PREPARES, true]], $native->setAttributes);
        $this->assertCount(1, $log->queries);
    }

    public function testQuerySetsEmulatePreparesFalseForSelectWhenMysqlndEnabled(): void
    {
        $connection = new CI_DB_mysqli_connection($this->baseConfig());
        $native = new MysqliConnectionNativeStub();

        $this->setProtectedProperty($connection, 'connection', $native);
        $this->setProtectedProperty($connection, 'mysqlnd', true);

        $connection->query("SELECT * FROM exp_demo");

        $this->assertSame([[PDO::ATTR_EMULATE_PREPARES, false]], $native->setAttributes);
    }

    public function testQueryRethrowsWithHtmlEncodedSqlOnError(): void
    {
        $connection = new CI_DB_mysqli_connection($this->baseConfig());
        $native = new MysqliConnectionNativeStub();
        $native->throwOnQuery = true;

        $this->setProtectedProperty($connection, 'connection', $native);
        $this->setProtectedProperty($connection, 'mysqlnd', false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SELECT');
        $connection->query("SELECT '<unsafe>'");
    }

    public function testEscapeOpensConnectionWhenClosedAndStripsOuterQuotes(): void
    {
        $connection = new MysqliConnectionOpenStub($this->baseConfig());

        $escaped = $connection->escape("don't panic");

        $this->assertTrue($connection->openCalled);
        $this->assertSame("escaped-don\\'t panic", $escaped);
    }

    public function testErrorAndInsertHelpersProxyToNativeConnection(): void
    {
        $connection = new CI_DB_mysqli_connection($this->baseConfig());
        $native = new MysqliConnectionNativeStub();

        $this->setProtectedProperty($connection, 'connection', $native);

        $this->assertSame('native error', $connection->getErrorMessage());
        $this->assertSame('HY000', $connection->getErrorNumber());
        $this->assertSame('123', $connection->getInsertId());
        $this->assertSame($native, $connection->getNative());
    }

    public function testGetIndexesReturnsEmptyWhenTableDoesNotExist(): void
    {
        $connection = new CI_DB_mysqli_connection($this->baseConfig());
        $load = new MysqliConnectionLoadStub();
        $logger = new MysqliConnectionLoggerStub();
        $db = new MysqliConnectionEeDbStub();
        $db->tableExists = false;

        ee()->setMock('load', $load);
        ee()->setMock('logger', $logger);
        ee()->setMock('db', $db);

        $indexes = $connection->get_indexes('news');

        $this->assertSame([], $indexes);
        $this->assertSame(['logger'], $load->libraries);
        $this->assertSame(['array'], $load->helpers);
        $this->assertStringContainsString('does not exist', $logger->messages[0]);
    }

    public function testGetIndexesReturnsEmptyWhenShowIndexHasNoRows(): void
    {
        $connection = new CI_DB_mysqli_connection($this->baseConfig());
        $load = new MysqliConnectionLoadStub();
        $logger = new MysqliConnectionLoggerStub();
        $db = new MysqliConnectionEeDbStub();
        $db->tableExists = true;
        $db->queryResult = new MysqliConnectionQueryResultStub(0, []);

        ee()->setMock('load', $load);
        ee()->setMock('logger', $logger);
        ee()->setMock('db', $db);

        $indexes = $connection->get_indexes('news');

        $this->assertSame([], $indexes);
        $this->assertStringContainsString('Unable to get indexes', $logger->messages[0]);
    }

    public function testGetIndexesReturnsNormalizedLowerCaseRows(): void
    {
        $connection = new CI_DB_mysqli_connection($this->baseConfig());
        $load = new MysqliConnectionLoadStub();
        $logger = new MysqliConnectionLoggerStub();
        $db = new MysqliConnectionEeDbStub();
        $db->tableExists = true;
        $db->queryResult = new MysqliConnectionQueryResultStub(2, [
            ['Key_name' => 'PRIMARY', 'Seq_in_index' => 1],
            ['Key_name' => 'title_idx', 'Seq_in_index' => 1],
        ]);

        ee()->setMock('load', $load);
        ee()->setMock('logger', $logger);
        ee()->setMock('db', $db);

        $indexes = $connection->get_indexes('news');

        $this->assertCount(2, $indexes);
        $this->assertSame(['key_name' => 'PRIMARY', 'seq_in_index' => 1], $indexes[0]);
        $this->assertSame(['key_name' => 'title_idx', 'seq_in_index' => 1], $indexes[1]);
    }

    public function testPrivateEnforcementHelpersHandleCreateAndNonCreateQueries(): void
    {
        $connection = new CI_DB_mysqli_connection($this->baseConfig());

        $same = $this->invokePrivate($connection, 'enforceCreateTableParameters', 'SELECT 1');
        $this->assertSame('SELECT 1', $same);

        $rewritten = $this->invokePrivate($connection, 'enforceCreateTableParameters', 'CREATE TABLE t (id INT)');
        $this->assertStringContainsString('CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $rewritten);
        $this->assertStringContainsString('ENGINE=InnoDB', $rewritten);

        $replaced = $this->invokePrivate(
            $connection,
            'enforceCharsetAndCollation',
            'CREATE TABLE t (id INT) DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;'
        );
        $this->assertStringContainsString('CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $replaced);

        $appended = $this->invokePrivate(
            $connection,
            'enforceCharsetAndCollation',
            'CREATE TABLE t (id INT);'
        );
        $this->assertStringContainsString('CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', $appended);

        $engineAdded = $this->invokePrivate($connection, 'addEngineIfNotPresent', 'CREATE TABLE t (id INT);');
        $this->assertStringContainsString('ENGINE=InnoDB;', $engineAdded);

        $engineUntouched = $this->invokePrivate(
            $connection,
            'addEngineIfNotPresent',
            'CREATE TABLE t (id INT) ENGINE=MyISAM;'
        );
        $this->assertStringContainsString('ENGINE=MyISAM', $engineUntouched);
    }

    private function baseConfig(): array
    {
        return [
            'hostname' => 'localhost',
            'username' => 'user',
            'password' => 'pass',
            'database' => 'ee',
            'char_set' => 'utf8mb4',
            'pconnect' => false,
            'dbcollat' => 'utf8mb4_unicode_ci',
            'port' => 3306,
        ];
    }

    private function setProtectedProperty($object, string $name, $value): void
    {
        $property = new ReflectionProperty(CI_DB_mysqli_connection::class, $name);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    private function invokePrivate($object, string $method, ...$args)
    {
        $reflection = new ReflectionMethod(CI_DB_mysqli_connection::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($object, ...$args);
    }
}

class MysqliConnectionOpenStub extends CI_DB_mysqli_connection
{
    public $openCalled = false;

    public function open()
    {
        $this->openCalled = true;

        $native = new MysqliConnectionNativeStub();
        $native->quotedReturn = "'escaped-don\\'t panic'";
        $property = new ReflectionProperty(CI_DB_mysqli_connection::class, 'connection');
        $property->setAccessible(true);
        $property->setValue($this, $native);
    }
}

class MysqliConnectionNativeStub
{
    public $queryReturn = true;
    public $throwOnQuery = false;
    public $lastQuery;
    public $setAttributes = [];
    public $quotedReturn = "'escaped'";

    public function query($query)
    {
        $this->lastQuery = $query;

        if ($this->throwOnQuery) {
            throw new Exception('native query failure');
        }

        return $this->queryReturn;
    }

    public function setAttribute($attribute, $value)
    {
        $this->setAttributes[] = [$attribute, $value];
    }

    public function quote($value)
    {
        return $this->quotedReturn;
    }

    public function errorInfo()
    {
        return [null, null, 'native error'];
    }

    public function errorCode()
    {
        return 'HY000';
    }

    public function lastInsertId()
    {
        return '123';
    }
}

class MysqliConnectionLogStub
{
    public $queries = [];

    public function addQuery($query, $time, $memory)
    {
        $this->queries[] = [$query, $time, $memory];
    }
}

class MysqliConnectionLoadStub
{
    public $libraries = [];
    public $helpers = [];

    public function library($name)
    {
        $this->libraries[] = $name;
    }

    public function helper($name)
    {
        $this->helpers[] = $name;
    }
}

class MysqliConnectionLoggerStub
{
    public $messages = [];

    public function updater($message, $force)
    {
        $this->messages[] = $message;
    }
}

class MysqliConnectionEeDbStub
{
    public $tableExists = false;
    public $dbprefix = 'exp_';
    public $queryResult;

    public function table_exists($table)
    {
        return $this->tableExists;
    }

    public function query($sql)
    {
        return $this->queryResult;
    }
}

class MysqliConnectionQueryResultStub
{
    private $numRows;
    private $rows;

    public function __construct(int $numRows, array $rows)
    {
        $this->numRows = $numRows;
        $this->rows = $rows;
    }

    public function num_rows()
    {
        return $this->numRows;
    }

    public function result_array()
    {
        return $this->rows;
    }
}
