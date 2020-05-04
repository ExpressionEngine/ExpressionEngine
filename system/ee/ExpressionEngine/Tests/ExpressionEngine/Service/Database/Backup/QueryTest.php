<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Database\Backup;

use EllisLab\ExpressionEngine\Service\Database\Backup\Query;
use Mockery;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase {

	public function setUp()
	{
		$this->db_query = Mockery::mock('EllisLab\ExpressionEngine\Service\Database\Query');
		$this->db_query2 = Mockery::mock('EllisLab\ExpressionEngine\Service\Database\Query');

		$this->query = new Query($this->db_query);
	}

	public function tearDown()
	{
		$this->db_query = NULL;
		$this->db_query2 = NULL;
		$this->query = NULL;
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
			'insert_string' => "INSERT INTO `table` (`column1`, `column2`, `column3`) VALUES\x20
	(123, 'testing', x'30'),
	(0, 'testing2', x'31'),
	(321, 'testing3', x'3137');",
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
			'insert_string' => "INSERT INTO `table` (`column1`, `column2`, `column3`) VALUES\x20
	(123, 'testing', x'30'),
	(0, 'testing2', x'31');
INSERT INTO `table` (`column1`, `column2`, `column3`) VALUES\x20
	(321, 'testing3', x'3137');",
			'rows_exported' => 3
		], $inserts);

		$this->query->makeCompactQueries();
		$inserts = $this->query->getInsertsForTable('table', 0, 50);

		$this->assertEquals([
			'insert_string' => "INSERT INTO `table` (`column1`, `column2`, `column3`) VALUES (123, 'testing', x'30'), (0, 'testing2', x'31');
INSERT INTO `table` (`column1`, `column2`, `column3`) VALUES (321, 'testing3', x'3137');",
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

		$this->assertEquals(NULL, $inserts);
	}
}
