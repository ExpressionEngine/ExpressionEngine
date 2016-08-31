<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Database\Backup;

use EllisLab\ExpressionEngine\Service\Database\Backup\Backup;
use Mockery;

class BackupTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->query = Mockery::mock('EllisLab\ExpressionEngine\Service\Database\Backup\Query');
		$this->logger = Mockery::mock('EllisLab\ExpressionEngine\Service\Logger\File');
		$this->logger->shouldReceive('log');

		$this->backup = new Backup($this->query, $this->logger);
	}

	public function tearDown()
	{
		$this->query = NULL;
		$this->logger = NULL;
	}

	public function testConservativeInserts()
	{
		$this->query->shouldReceive('getTables')->andReturn([
			'table1',
			'table2',
			'table3',
			'table4',
			'table5',
			'table6',
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
