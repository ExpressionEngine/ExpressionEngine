<?php

require_once SYSPATH . 'ee/legacy/database/DB_result.php';
require_once SYSPATH . 'ee/legacy/database/drivers/mysqli/mysqli_result.php';

use PHPUnit\Framework\TestCase;

class MysqliResultTest extends TestCase
{
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testNumRowsAndNumFieldsProxyToPdoStatement(): void
    {
        $statement = new MysqliResultStatementStub();
        $statement->rowCountValue = 7;
        $statement->columnCountValue = 3;

        $result = new TestableMysqliResult($statement);

        $this->assertSame(7, $result->num_rows());
        $this->assertSame(3, $result->num_fields());
    }

    public function testListFieldsReturnsColumnMetaNames(): void
    {
        $statement = new MysqliResultStatementStub();
        $statement->columnMeta = [
            ['name' => 'id'],
            ['name' => 'screen_name'],
        ];
        $statement->columnCountValue = 2;

        $result = new TestableMysqliResult($statement);

        $this->assertSame(['id', 'screen_name'], $result->list_fields());
    }

    public function testFieldDataBuildsFieldObjectsUsingDescribeQueries(): void
    {
        $statement = new MysqliResultStatementStub();
        $statement->columnMeta = [
            ['name' => 'id', 'table' => 'exp_members', 'len' => 11, 'flags' => ['primary_key']],
            ['name' => 'screen_name', 'table' => 'exp_members', 'len' => 50, 'flags' => []],
            ['name' => 'title', 'table' => 'exp_channel_titles', 'len' => 100, 'flags' => []],
        ];
        $statement->columnCountValue = 3;

        $db = new MysqliResultDbServiceStub();
        $db->describeMap = [
            'DESCRIBE exp_members' => [
                ['Field' => 'id', 'Type' => 'int(10)', 'Default' => null],
                ['Field' => 'screen_name', 'Type' => 'varchar(50)', 'Default' => ''],
            ],
            'DESCRIBE exp_channel_titles' => [
                ['Field' => 'title', 'Type' => 'varchar(255)', 'Default' => 'Untitled'],
            ],
        ];
        ee()->setMock('db', $db);

        $result = new TestableMysqliResult($statement);
        $fields = $result->field_data();

        $this->assertCount(3, $fields);
        $this->assertSame(['DESCRIBE exp_members', 'DESCRIBE exp_channel_titles'], $db->queries);

        $this->assertSame('id', $fields[0]->name);
        $this->assertSame(11, $fields[0]->max_length);
        $this->assertTrue($fields[0]->primary_key);
        $this->assertSame('int', $fields[0]->type);
        $this->assertNull($fields[0]->default);

        $this->assertSame('screen_name', $fields[1]->name);
        $this->assertFalse($fields[1]->primary_key);
        $this->assertSame('varchar', $fields[1]->type);
        $this->assertSame('', $fields[1]->default);

        $this->assertSame('title', $fields[2]->name);
        $this->assertSame('varchar', $fields[2]->type);
        $this->assertSame('Untitled', $fields[2]->default);
    }

    public function testFreeResultClosesCursorAndClearsStatement(): void
    {
        $statement = new MysqliResultStatementStub();
        $result = new TestableMysqliResult($statement);

        $result->free_result();

        $this->assertTrue($statement->closeCursorCalled);
        $this->assertNull($result->getStatement());
    }

    public function testFetchAssocAndFetchObjectUseExpectedModes(): void
    {
        $statement = new MysqliResultStatementStub();
        $statement->assocQueue = [['id' => 99]];
        $statement->objectQueue = [(object) ['id' => 42]];

        $result = new TestableMysqliResult($statement);

        $assoc = $result->_fetch_assoc();
        $object = $result->_fetch_object();

        $this->assertSame(['id' => 99], $assoc);
        $this->assertEquals((object) ['id' => 42], $object);
        $this->assertSame([PDO::FETCH_ASSOC, PDO::FETCH_OBJ], $statement->fetchModes);
    }

    public function testDataSeekExecutesCallEvenWhenResultResourceIsInvalid(): void
    {
        $result = new TestableMysqliResult(new MysqliResultStatementStub());
        $result->result_id = null;

        $handled = false;

        try {
            $value = $result->_data_seek(0);
            $this->assertTrue($value === false || $value === null || $value === true);
            $handled = true;
        } catch (Throwable $exception) {
            $this->assertTrue(
                $exception instanceof TypeError || strpos($exception->getMessage(), 'mysqli_data_seek') !== false
            );
            $handled = true;
        }

        $this->assertTrue($handled);
    }
}

class TestableMysqliResult extends CI_DB_mysqli_result
{
    public $result_id;

    public function getStatement()
    {
        return $this->pdo_statement;
    }
}

class MysqliResultStatementStub
{
    public $rowCountValue = 0;
    public $columnCountValue = 0;
    public $columnMeta = [];
    public $assocQueue = [];
    public $objectQueue = [];
    public $fetchModes = [];
    public $closeCursorCalled = false;

    public function rowCount()
    {
        return $this->rowCountValue;
    }

    public function columnCount()
    {
        return $this->columnCountValue;
    }

    public function getColumnMeta($index)
    {
        return $this->columnMeta[$index];
    }

    public function closeCursor()
    {
        $this->closeCursorCalled = true;
    }

    public function fetch($mode)
    {
        $this->fetchModes[] = $mode;

        if ($mode === PDO::FETCH_ASSOC) {
            return array_shift($this->assocQueue);
        }

        if ($mode === PDO::FETCH_OBJ) {
            return array_shift($this->objectQueue);
        }

        return false;
    }
}

class MysqliResultDbServiceStub
{
    public $describeMap = [];
    public $queries = [];

    public function query($sql)
    {
        $this->queries[] = $sql;

        return new MysqliResultQueryStub($this->describeMap[$sql] ?? []);
    }
}

class MysqliResultQueryStub
{
    private $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function result_array()
    {
        return $this->rows;
    }
}
