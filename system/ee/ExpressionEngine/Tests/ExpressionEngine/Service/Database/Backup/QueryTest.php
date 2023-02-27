<?php

namespace ExpressionEngine\Tests\Service\Database\Backup;

use ExpressionEngine\Service\Database\Backup\Query;
use Mockery;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    public $query;
    public $db_query;
    public $db_query2;
    private $newline = "\n";

    public function setUp(): void
    {
        $this->db_query = Mockery::mock('ExpressionEngine\Service\Database\Query');
        $this->db_query2 = Mockery::mock('ExpressionEngine\Service\Database\Query');

        $this->query = new Query($this->db_query);
    }

    public function tearDown(): void
    {
        $this->db_query = null;
        $this->db_query2 = null;
        $this->query = null;

        Mockery::close();
    }

    public function testGetInsertsForTable()
    {
        $this->db_query->shouldReceive('query')->with('DESCRIBE `table`;')->andReturn($this->db_query);
        $this->db_query->shouldReceive('result_array')->andReturn([
            [
                'Field' => 'column1',
                'Type' => 'int'
            ],
            [
                'Field' => 'column2',
                'Type' => 'varchar'
            ],
            [
                'Field' => 'column3',
                'Type' => 'binary'
            ]
        ]);

        $this->db_query->shouldReceive('offset')->with(0)->andReturn($this->db_query);
        $this->db_query->shouldReceive('limit')->with(50)->andReturn($this->db_query);
        $this->db_query->shouldReceive('get')->with('table')->andReturn($this->db_query2);
        $this->db_query2->shouldReceive('result_array')->andReturn([
            [
                'column1' => 123,
                'column2' => 'testing',
                'column3' => 0x00000
            ],
            [
                'column1' => 0,
                'column2' => 'testing2',
                'column3' => 0x00001
            ],
            [
                'column1' => 321,
                'column2' => 'testing3',
                'column3' => 0x00011
            ]
        ]);

        $this->db_query->shouldReceive('escape_str')->with('testing')->andReturn('testing');
        $this->db_query->shouldReceive('escape_str')->with('testing2')->andReturn('testing2');
        $this->db_query->shouldReceive('escape_str')->with('testing3')->andReturn('testing3');

        $inserts = $this->query->getInsertsForTable('table', 0, 50);

        $this->assertEquals([
            'insert_string' => "INSERT INTO `table` (`column1`, `column2`, `column3`) VALUES\x20{$this->newline}	(123, 'testing', x'30'),{$this->newline}	(0, 'testing2', x'31'),{$this->newline}	(321, 'testing3', x'3137');",
            'rows_exported' => 3
        ], $inserts);

        $this->query->makeCompactQueries();
        $inserts = $this->query->getInsertsForTable('table', 0, 50);

        $this->assertEquals([
            'insert_string' => "INSERT INTO `table` (`column1`, `column2`, `column3`) VALUES (123, 'testing', x'30'), (0, 'testing2', x'31'), (321, 'testing3', x'3137');",
            'rows_exported' => 3
        ], $inserts);

        $this->query->setQuerySizeLimit(55);
        $this->query->makePrettyQueries();

        $inserts = $this->query->getInsertsForTable('table', 0, 50);

        $this->assertEquals([
            'insert_string' => "INSERT INTO `table` (`column1`, `column2`, `column3`) VALUES\x20{$this->newline}	(123, 'testing', x'30'),{$this->newline}	(0, 'testing2', x'31');{$this->newline}INSERT INTO `table` (`column1`, `column2`, `column3`) VALUES\x20{$this->newline}	(321, 'testing3', x'3137');",
            'rows_exported' => 3
        ], $inserts);

        $this->query->makeCompactQueries();
        $inserts = $this->query->getInsertsForTable('table', 0, 50);

        $this->assertEquals([
            'insert_string' => "INSERT INTO `table` (`column1`, `column2`, `column3`) VALUES (123, 'testing', x'30'), (0, 'testing2', x'31');{$this->newline}INSERT INTO `table` (`column1`, `column2`, `column3`) VALUES (321, 'testing3', x'3137');",
            'rows_exported' => 3
        ], $inserts);
    }

    public function testGetInsertsNoRows()
    {
        $this->db_query->shouldReceive('query')->with('DESCRIBE `table`;')->andReturn($this->db_query);
        $this->db_query->shouldReceive('result_array')->andReturn([
            [
                'Field' => 'column1',
                'Type' => 'int'
            ],
            [
                'Field' => 'column2',
                'Type' => 'varchar'
            ],
            [
                'Field' => 'column3',
                'Type' => 'binary'
            ]
        ]);

        $this->db_query->shouldReceive('offset')->with(0)->andReturn($this->db_query);
        $this->db_query->shouldReceive('limit')->with(50)->andReturn($this->db_query);
        $this->db_query->shouldReceive('get')->with('table')->andReturn($this->db_query2);
        $this->db_query2->shouldReceive('result_array')->andReturn([]);

        $inserts = $this->query->getInsertsForTable('table', 0, 50);

        $this->assertEquals(null, $inserts);
    }
}
