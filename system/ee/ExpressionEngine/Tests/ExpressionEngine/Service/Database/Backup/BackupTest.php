<?php

namespace ExpressionEngine\Tests\Service\Database\Backup;

use ExpressionEngine\Service\Database\Backup\Backup;
use Mockery;
use PHPUnit\Framework\TestCase;

class BackupTest extends TestCase
{
    public function setUp(): void
    {
        $this->filesystem = Mockery::mock('ExpressionEngine\Library\Filesystem\Filesystem');
        $this->query = Mockery::mock('ExpressionEngine\Service\Database\Backup\Query');
        $this->filesystem->shouldReceive('write');

        $this->backup = new Backup($this->filesystem, $this->query, 'some/path.sql', 0);
    }

    public function tearDown(): void
    {
        $this->filesystem = null;
        $this->query = null;
        $this->backup = null;

        Mockery::close();
    }

    public function testConservativeInserts()
    {
        $this->query->shouldReceive('getTables')->andReturn([
            'table1' => ['rows' => 20, 'size' => 1234],
            'table2' => ['rows' => 50, 'size' => 1234],
            'table3' => ['rows' => 75, 'size' => 1234],
            'table4' => ['rows' => 26, 'size' => 1234],
            'table5' => ['rows' => 1, 'size' => 1234],
            'table6' => ['rows' => 0, 'size' => 1234],
        ]);

        $this->backup->setRowLimit(50);

        $this->query->shouldReceive('getInsertsForTable')->with('table1', 0, 50)->andReturn([
            'insert_string' => '',
            'rows_exported' => 20
        ]);
        $this->query->shouldReceive('getInsertsForTable')->with('table2', 0, 30)->andReturn([
            'insert_string' => '',
            'rows_exported' => 30
        ]);

        $returned = $this->backup->writeTableInsertsConservatively();
        $this->assertEquals('table2', $returned['table_name']);
        $this->assertEquals(30, $returned['offset']);

        $this->query->shouldReceive('getInsertsForTable')->with('table2', 30, 50)->andReturn([
            'insert_string' => '',
            'rows_exported' => 20
        ]);
        $this->query->shouldReceive('getInsertsForTable')->with('table3', 0, 30)->andReturn([
            'insert_string' => '',
            'rows_exported' => 30
        ]);

        $returned = $this->backup->writeTableInsertsConservatively('table2', 30);
        $this->assertEquals('table3', $returned['table_name']);
        $this->assertEquals(30, $returned['offset']);

        $this->query->shouldReceive('getInsertsForTable')->with('table3', 30, 50)->andReturn([
            'insert_string' => '',
            'rows_exported' => 45
        ]);
        $this->query->shouldReceive('getInsertsForTable')->with('table4', 0, 5)->andReturn([
            'insert_string' => '',
            'rows_exported' => 5
        ]);

        $returned = $this->backup->writeTableInsertsConservatively('table3', 30);
        $this->assertEquals('table4', $returned['table_name']);
        $this->assertEquals(5, $returned['offset']);

        $this->query->shouldReceive('getInsertsForTable')->with('table4', 5, 50)->andReturn([
            'insert_string' => '',
            'rows_exported' => 21
        ]);
        $this->query->shouldReceive('getInsertsForTable')->with('table5', 0, 29)->andReturn([
            'insert_string' => '',
            'rows_exported' => 1
        ]);
        $this->query->shouldReceive('getInsertsForTable')->with('table6', 0, 28)->andReturn(null);

        $returned = $this->backup->writeTableInsertsConservatively('table4', 5);
        $this->assertEquals(false, $returned);
    }
}
