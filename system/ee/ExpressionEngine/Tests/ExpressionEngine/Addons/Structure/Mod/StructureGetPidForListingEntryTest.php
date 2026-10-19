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
			'SELECT channel_id FROM exp_channel_titles WHERE entry_id = ? LIMIT 1',
			'SELECT entry_id FROM exp_structure WHERE listing_cid = ? LIMIT 1',
		], array_map([$this, 'normalizeSql'], array_column($dbLog->queries, 0)));
		$this->assertSame([['777'], ['9']], array_column($dbLog->queries, 1));
	}

	public function testReturnsFalseWhenChannelIdRowReturnsArray()
	{
		$dbLog = (object) ['queries' => []];
		ee()->setMock('db', $this->makeQueryMock([['weird' => true]], $dbLog));

		$this->assertFalse($this->structure->get_pid_for_listing_entry(1));
		$this->assertSame([
			'SELECT channel_id FROM exp_channel_titles WHERE entry_id = ? LIMIT 1',
		], array_map([$this, 'normalizeSql'], array_column($dbLog->queries, 0)));
		$this->assertSame([['1']], array_column($dbLog->queries, 1));
	}

	public function testReturnsNullWhenParentEntryNotFound()
	{
		$dbLog = (object) ['queries' => []];
		ee()->setMock('db', $this->makeQueryMock([9, null], $dbLog));

		$this->assertNull($this->structure->get_pid_for_listing_entry(1));
		$this->assertSame(
			'SELECT entry_id FROM exp_structure WHERE listing_cid = ? LIMIT 1',
			$this->normalizeSql($dbLog->queries[1][0])
		);
		$this->assertSame([['1'], ['9']], array_column($dbLog->queries, 1));
	}

	public function testReturnsFalseBeforeQueryForMalformedEntryId()
	{
		$dbLog = (object) ['queries' => []];
		ee()->setMock('db', $this->makeQueryMock([], $dbLog));

		$this->assertFalse($this->structure->get_pid_for_listing_entry("10'"));
		$this->assertSame([], $dbLog->queries);
	}

	public function testReturnsFalseWithoutParentQueryWhenChannelIsMissing()
	{
		$dbLog = (object) ['queries' => []];
		ee()->setMock('db', $this->makeQueryMock([null], $dbLog));

		$this->assertFalse($this->structure->get_pid_for_listing_entry(1));
		$this->assertCount(1, $dbLog->queries);
	}

	public function testKeepsLargeDecimalEntryIdIntactInBinding()
	{
		$dbLog = (object) ['queries' => []];
		ee()->setMock('db', $this->makeQueryMock([9, 123], $dbLog));

		$this->assertSame(123, $this->structure->get_pid_for_listing_entry('18446744073709551616'));
		$this->assertSame(['18446744073709551616'], $dbLog->queries[0][1]);
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

			public function query($sql, $binds = [])
			{
				$this->dbLog->queries[] = [$sql, $binds];

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

