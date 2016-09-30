<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Database\Backup;

use EllisLab\ExpressionEngine\Service\Database\Backup\Backup;
use Mockery;

class BackupTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->filesystem = Mockery::mock('EllisLab\ExpressionEngine\Library\Filesystem\Filesystem');
		$this->query = Mockery::mock('EllisLab\ExpressionEngine\Service\Database\Backup\Query');
		$this->formatter = Mockery::mock('EllisLab\ExpressionEngine\Service\Formatter\FormatterFactory');
		$this->filesystem->shouldReceive('write');

		$this->backup = new Backup($this->filesystem, $this->query, 'some/path.sql', $this->formatter);
	}

	public function tearDown()
	{
		$this->filesystem = NULL;
		$this->query = NULL;
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

		$this->query->shouldReceive('getTotalRows')->with('table1')->andReturn(20);
		$this->query->shouldReceive('getTotalRows')->with('table2')->andReturn(50);
		$this->query->shouldReceive('getTotalRows')->with('table3')->andReturn(75);
		$this->query->shouldReceive('getTotalRows')->with('table4')->andReturn(26);
		$this->query->shouldReceive('getTotalRows')->with('table5')->andReturn(1);
		$this->query->shouldReceive('getTotalRows')->with('table6')->andReturn(0);

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

		$returned = $this->backup->writeTableInsertsConservatively('table4', 5);
		$this->assertEquals(FALSE, $returned);
	}
}
