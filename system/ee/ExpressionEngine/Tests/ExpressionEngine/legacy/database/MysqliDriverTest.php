<?php

if (! class_exists('CI_DB')) {
    class CI_DB
    {
        public $_reserved_identifiers = ['*'];
        public $dbprefix = 'exp_';
        public $database = 'ee_main';
        public $ar_where = [];
        public $trans_enabled = true;
        public $_trans_depth = 0;
        public $_trans_failure = false;
        public $simpleQueries = [];
        public $queryResult;

        public function _protect_identifiers($item, $prefix_single = false, $protect_identifiers = null, $field_exists = true)
        {
            if (is_array($item)) {
                return array_map([$this, '_protect_identifiers'], $item);
            }

            return '<' . $item . '>';
        }

        public function escape_like_str($str)
        {
            return str_replace(['%', '_'], ['\\%', '\\_'], $str);
        }

        public function query($sql)
        {
            $this->lastCountAllSql = $sql;

            return $this->queryResult;
        }

        public function simple_query($sql)
        {
            $this->simpleQueries[] = $sql;

            return true;
        }
    }
}

require_once SYSPATH . 'ee/legacy/database/drivers/mysqli/mysqli_driver.php';

use PHPUnit\Framework\TestCase;

class MysqliDriverTest extends TestCase
{
    private $driver;
    private $connection;

    protected function setUp(): void
    {
        $this->driver = new MysqliDriverTestable();
        $this->connection = new MysqliDriverConnectionStub();
        $this->driver->connection = $this->connection;
    }

    public function testManualConnectionMethodsThrowExceptions(): void
    {
        foreach (['db_connect', 'db_pconnect', 'reconnect', 'db_select'] as $method) {
            try {
                $this->driver->$method();
                $this->fail("Expected exception from {$method}");
            } catch (Exception $exception) {
                $this->assertStringContainsString('removed', $exception->getMessage());
            }
        }
    }

    public function testVersionAndExecuteMethods(): void
    {
        $this->assertSame('SELECT version() AS ver', $this->driver->_version());

        $result = $this->driver->_execute('DELETE FROM exp_members');
        $this->assertSame('query-result', $result);
        $this->assertSame('DELETE FROM exp_members WHERE 1=1', $this->connection->lastQuery);
        $this->assertSame('query-result', $this->driver->last_query);
    }

    public function testPrepQueryDeleteHackAndPassthrough(): void
    {
        $this->assertSame(
            'DELETE FROM exp_titles WHERE 1=1',
            $this->driver->_prep_query('DELETE FROM exp_titles')
        );
        $this->assertSame('SELECT 1', $this->driver->_prep_query('SELECT 1'));
    }

    public function testTransactionMethodsHandleDisabledNestedAndNormalPaths(): void
    {
        $this->driver->trans_enabled = false;
        $this->assertTrue($this->driver->trans_begin());
        $this->assertTrue($this->driver->trans_commit());
        $this->assertTrue($this->driver->trans_rollback());

        $this->driver->trans_enabled = true;
        $this->driver->_trans_depth = 1;
        $this->assertTrue($this->driver->trans_begin());
        $this->assertTrue($this->driver->trans_commit());
        $this->assertTrue($this->driver->trans_rollback());

        $this->driver->_trans_depth = 0;
        $this->assertTrue($this->driver->trans_begin(true));
        $this->assertTrue($this->driver->_trans_failure);
        $this->assertTrue($this->driver->trans_commit());
        $this->assertTrue($this->driver->trans_rollback());
        $this->assertSame(
            ['SET AUTOCOMMIT=0', 'START TRANSACTION', 'COMMIT', 'SET AUTOCOMMIT=1', 'ROLLBACK', 'SET AUTOCOMMIT=1'],
            $this->driver->simpleQueries
        );
    }

    public function testEscapeStrHandlesArrayAndLikeEscaping(): void
    {
        $escapedArray = $this->driver->escape_str(['a' => '100%', 'b' => '“quoted”'], true);
        $this->assertSame(['a' => 'escaped:100\\%', 'b' => 'escaped:_quoted_'], $escapedArray);

        $escapedSingle = $this->driver->escape_str('alpha_beta', true);
        $this->assertSame('escaped:alpha\\_beta', $escapedSingle);
    }

    public function testAffectedRowsAndInsertIdHelpers(): void
    {
        $this->assertSame(0, $this->driver->affected_rows());

        $this->driver->last_query = new class {
            public function rowCount()
            {
                return 17;
            }
        };
        $this->assertSame(17, $this->driver->affected_rows());
        $this->assertSame(42, $this->driver->insert_id());
    }

    public function testCountAllHandlesEmptyTableNoRowsAndNormalResult(): void
    {
        $this->assertSame(0, $this->driver->count_all(''));

        $this->driver->queryResult = new MysqliDriverCountResultStub(0, (object) ['numrows' => 0]);
        $this->assertSame(0, $this->driver->count_all('exp_members'));

        $this->driver->queryResult = new MysqliDriverCountResultStub(1, (object) ['numrows' => '5']);
        $this->assertSame(5, $this->driver->count_all('exp_members'));
    }

    public function testListTableAndColumnFieldDataSqlBuilders(): void
    {
        $this->assertSame('SHOW TABLES FROM `ee_main`', $this->driver->_list_tables(false));
        $this->assertSame("SHOW TABLES FROM `ee_main` LIKE 'exp\\_%'", $this->driver->_list_tables(true));
        $this->assertSame('SHOW COLUMNS FROM <exp_members>', $this->driver->_list_columns('exp_members'));
        $this->assertSame('SELECT * FROM exp_members LIMIT 1', $this->driver->_field_data('exp_members'));
    }

    public function testErrorHelperMethodsDelegateToConnection(): void
    {
        $this->assertSame('driver error', $this->driver->_error_message());
        $this->assertSame(987, $this->driver->_error_number());
    }

    public function testEscapeIdentifiersHandlesAllCodePaths(): void
    {
        $this->driver->_escape_char = '';
        $this->assertSame('exp_members', $this->driver->escape_identifiers('exp_members'));

        $this->driver->_escape_char = '`';
        $this->driver->_reserved_identifiers = ['*', 'COUNT(*)'];
        $this->assertSame('`table`.COUNT(*)', $this->driver->escape_identifiers('table.COUNT(*)'));
        $this->assertSame('`exp`.`members`', $this->driver->escape_identifiers('exp.members'));
        $this->assertSame('`members`', $this->driver->escape_identifiers('members'));
    }

    public function testFromTablesInsertReplaceAndInsertBatchBuilders(): void
    {
        $this->assertSame('(exp_members)', $this->driver->_from_tables('exp_members'));
        $this->assertSame('(exp_members, exp_titles)', $this->driver->_from_tables(['exp_members', 'exp_titles']));
        $this->assertSame('INSERT INTO exp_members (id, title) VALUES (1, \'x\')', $this->driver->_insert('exp_members', ['id', 'title'], ['1', "'x'"]));
        $this->assertSame('REPLACE INTO exp_members (id, title) VALUES (1, \'x\')', $this->driver->_replace('exp_members', ['id', 'title'], ['1', "'x'"]));
        $this->assertSame("INSERT INTO exp_members (id, title) VALUES (1, 'a'), (2, 'b')", $this->driver->_insert_batch('exp_members', ['id', 'title'], ["(1, 'a')", "(2, 'b')"]));
    }

    public function testUpdateBuildersHandleWhereOrderAndBatchCases(): void
    {
        $sql = $this->driver->_update(
            'exp_members',
            ['title' => "'new'"],
            ['id = 1'],
            ['id DESC'],
            10
        );
        $this->assertSame("UPDATE exp_members SET title = 'new' WHERE id = 1 ORDER BY id DESC LIMIT 10", $sql);

        $sqlNoWhere = $this->driver->_update('exp_members', ['title' => "'new'"], [], [], false);
        $this->assertSame("UPDATE exp_members SET title = 'new'", $sqlNoWhere);

        $batch = $this->driver->_update_batch(
            'exp_members',
            [
                ['id' => 1, 'title' => "'a'"],
                ['id' => 2, 'title' => "'b'"],
            ],
            'id',
            ['site_id = 1']
        );
        $this->assertStringContainsString("UPDATE exp_members SET title = CASE", $batch);
        $this->assertStringContainsString("WHEN id = 1 THEN 'a'", $batch);
        $this->assertStringContainsString("WHERE site_id = 1 AND id IN (1,2)", $batch);
    }

    public function testTruncateDeleteLimitAndClose(): void
    {
        $this->assertSame('TRUNCATE exp_members', $this->driver->_truncate('exp_members'));

        $this->driver->ar_where = ['id = 1'];
        $delete = $this->driver->_delete('exp_members', ['id = 1'], ["title LIKE 'x%'"], 5);
        $this->assertSame("DELETE FROM exp_members\nWHERE id = 1 AND title LIKE 'x%' LIMIT 5", $delete);

        $this->driver->ar_where = [];
        $this->assertSame('DELETE FROM exp_members', $this->driver->_delete('exp_members', [], [], false));

        $this->assertSame('SELECT * LIMIT 10 OFFSET 2', $this->driver->_limit('SELECT * ', 10, 2));
        $this->assertSame('SELECT * LIMIT 10', $this->driver->_limit('SELECT * ', 10, 0));

        $this->driver->_close();
        $this->assertTrue($this->connection->closed);
    }
}

class MysqliDriverTestable extends CI_DB_mysqli_driver
{
}

class MysqliDriverConnectionStub
{
    public $lastQuery;
    public $closed = false;

    public function query($sql)
    {
        $this->lastQuery = $sql;

        return 'query-result';
    }

    public function escape($value)
    {
        return 'escaped:' . $value;
    }

    public function getInsertId()
    {
        return 42;
    }

    public function getErrorMessage()
    {
        return 'driver error';
    }

    public function getErrorNumber()
    {
        return 987;
    }

    public function close()
    {
        $this->closed = true;
    }
}

class MysqliDriverCountResultStub
{
    private $numRows;
    private $row;

    public function __construct(int $numRows, $row)
    {
        $this->numRows = $numRows;
        $this->row = $row;
    }

    public function num_rows()
    {
        return $this->numRows;
    }

    public function row()
    {
        return $this->row;
    }
}
