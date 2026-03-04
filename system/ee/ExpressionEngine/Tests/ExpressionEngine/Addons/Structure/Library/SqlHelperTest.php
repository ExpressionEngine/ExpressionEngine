<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/Sql_helper.php';

use PHPUnit\Framework\TestCase;

if (!function_exists('mysql_real_escape_string')) {
    function mysql_real_escape_string($str, $conn = null)
    {
        return 'real:' . addslashes($str);
    }
}

class SqlHelperResultMock
{
    public $num_rows;
    private $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->num_rows = count($rows);
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

class SqlHelperDbMock
{
    public $queries = [];
    public $affected_rows = 0;
    public $insert_id = 0;
    public $conn_id = null;
    public $queue = [];

    public function query($sql)
    {
        $this->queries[] = $sql;
        if (!empty($this->queue)) {
            return array_shift($this->queue);
        }
        return 1;
    }
}

class SqlHelperTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    public function testBatchRowAndResults()
    {
        $db = new SqlHelperDbMock();
        $db->queue = [
            1,
            1,
            new SqlHelperResultMock([['id' => 1, 'name' => 'A']]),
            new SqlHelperResultMock([['id' => 2], ['id' => 3]]),
            new SqlHelperResultMock([]),
        ];
        ee()->setMock('db', $db);

        $helper = new Sql_helper();
        $helper->batch(['Q1', 'Q2']);

        $this->assertSame(['id' => 1, 'name' => 'A'], $helper->row('ROW_SQL'));
        $this->assertSame([['id' => 2], ['id' => 3]], $helper->results('RESULTS_SQL'));
        $this->assertSame([], $helper->results('EMPTY_SQL'));
    }

    public function testRowReturnsNullWhenQueryFails()
    {
        $db = new SqlHelperDbMock();
        $db->queue = [false];
        ee()->setMock('db', $db);

        $helper = new Sql_helper();
        $this->assertNull($helper->row('ROW_FAIL'));
    }

    public function testUpdateAndAffectedAndEscape()
    {
        $db = new SqlHelperDbMock();
        $db->affected_rows = 4;
        ee()->setMock('db', $db);

        $helper = new Sql_helper();
        $affected = $helper->update('tbl', ['name' => "O'Reilly", 'deleted_at' => null], 'id = 5');

        $this->assertSame(4, $affected);
        $this->assertStringContainsString("UPDATE `tbl` SET", $db->queries[0]);
        $this->assertStringContainsString("`deleted_at` = NULL", $db->queries[0]);
        $this->assertStringContainsString("\\'", $db->queries[0]);
        $this->assertSame(-1, $helper->update('tbl', [], 'id = 5'));
        $this->assertSame("a\\'b", $helper->escape("a'b"));
    }

    public function testInsertAndInsertSql()
    {
        $db = new SqlHelperDbMock();
        $db->insert_id = 77;
        $db->queue = [1, 1, 0];
        ee()->setMock('db', $db);

        $helper = new Sql_helper();

        $id = $helper->insert('tbl', ['name' => 'X', 'deleted_at' => null]);
        $idFromSql = $helper->insert_sql('INSERT ANY');
        $nullId = $helper->insert_sql('INSERT FAIL');
        $emptyInsert = $helper->insert('tbl', []);

        $this->assertSame(77, $id);
        $this->assertSame(77, $idFromSql);
        $this->assertNull($nullId);
        $this->assertNull($emptyInsert);
        $this->assertStringContainsString("INSERT INTO `tbl`", $db->queries[0]);
    }

    public function testEscapeUsesMysqlRealEscapeWhenResourceConnectionExists()
    {
        $db = new SqlHelperDbMock();
        $db->conn_id = fopen('php://memory', 'r');
        ee()->setMock('db', $db);

        $helper = new Sql_helper();
        $this->assertSame("real:a\\'b", $helper->escape("a'b"));

        fclose($db->conn_id);
    }

    public function testEscapeUsesMysqlEscapeStringBranchWhenAvailable()
    {
        if (!function_exists('mysql_escape_string')) {
            eval('function mysql_escape_string($str){ return "legacy:" . addslashes($str); }');
        }

        $db = new SqlHelperDbMock();
        $db->conn_id = null;
        ee()->setMock('db', $db);

        $helper = new Sql_helper();
        $this->assertSame("legacy:a\\'b", $helper->escape("a'b"));
    }
}
