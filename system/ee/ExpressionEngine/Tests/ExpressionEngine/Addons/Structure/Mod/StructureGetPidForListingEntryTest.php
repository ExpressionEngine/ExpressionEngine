<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureGetPidForListingEntryTest extends StructureTestBase
{
	public function testReturnsParentEntryIdForListingChannel()
	{
		$dbLog = (object) ['queries' => []];
		ee()->setMock('db', $this->makeQueryMock([9, 123], $dbLog));

		$pid = $this->structure->get_pid_for_listing_entry(777);

		$this->assertSame(123, $pid);
		$this->assertSame([
			'SELECT channel_id FROM exp_channel_titles WHERE entry_id = 777 LIMIT 1',
			'SELECT entry_id FROM exp_structure WHERE listing_cid = 9 LIMIT 1',
		], array_map([$this, 'normalizeSql'], $dbLog->queries));
	}

	public function testReturnsFalseWhenChannelIdRowReturnsArray()
	{
		$dbLog = (object) ['queries' => []];
		ee()->setMock('db', $this->makeQueryMock([['weird' => true]], $dbLog));

		$this->assertFalse($this->structure->get_pid_for_listing_entry(1));
		$this->assertSame([
			'SELECT channel_id FROM exp_channel_titles WHERE entry_id = 1 LIMIT 1',
		], array_map([$this, 'normalizeSql'], $dbLog->queries));
	}

	public function testReturnsNullWhenParentEntryNotFound()
	{
		$dbLog = (object) ['queries' => []];
		ee()->setMock('db', $this->makeQueryMock([9, null], $dbLog));

		$this->assertNull($this->structure->get_pid_for_listing_entry(1));
		$this->assertSame(
			'SELECT entry_id FROM exp_structure WHERE listing_cid = 9 LIMIT 1',
			$this->normalizeSql($dbLog->queries[1])
		);
	}

	private function makeQueryMock(array $rowValues, object $dbLog)
	{
		return new class($rowValues, $dbLog) {
			private $rowValues;
			private $dbLog;
			private $calls = 0;

			public function __construct(array $rowValues, object $dbLog)
			{
				$this->rowValues = $rowValues;
				$this->dbLog = $dbLog;
			}

			public function query($sql)
			{
				$this->dbLog->queries[] = $sql;

				if (!array_key_exists($this->calls, $this->rowValues)) {
					throw new RuntimeException('Unexpected extra database query.');
				}

				$rowValue = $this->rowValues[$this->calls];
				$this->calls++;

				return new class($rowValue) {
					private $rowValue;

					public function __construct($rowValue)
					{
						$this->rowValue = $rowValue;
					}

					public function row($column)
					{
						return $this->rowValue;
					}
				};
			}
		};
	}

	private function normalizeSql(string $sql): string
	{
		return preg_replace('/\s+/', ' ', trim($sql));
	}
}

