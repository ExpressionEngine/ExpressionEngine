<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureGetPidForListingEntryTest extends StructureTestBase
{
	public function testReturnsParentEntryIdForListingChannel()
	{
		// DB mocks: first query returns channel_id for entry, second query returns structure row with parent entry_id
		ee()->db->setRows([
			['channel_id' => 9, 'entry_id' => 999], // for first SELECT channel_id ... WHERE entry_id = ?
		]);

		// We need query() to return different shapes; mod.structure expects ->row('column') access
		ee()->setMock('db', new class {
			private $calls = 0;
			public function query($sql)
			{
				$this->calls++;
				if ($this->calls === 1) {
					return new class {
						public function row($column){ return 9; }
					};
				}
				return new class {
					public function row($column){ return 123; }
				};
			}
		});

		$pid = $this->structure->get_pid_for_listing_entry(777);
		$this->assertSame(123, $pid);
	}

	public function testReturnsFalseWhenChannelIdRowReturnsArray()
	{
		// First query returns an array-like to trigger is_array guard
		ee()->setMock('db', new class {
			private $calls = 0;
			public function query($sql)
			{
				$this->calls++;
				if ($this->calls === 1) {
					return new class {
						public function row($column){ return ['weird' => true]; }
					};
				}
				return new class {
					public function row($column){ return null; }
				};
			}
		});

		$this->assertFalse($this->structure->get_pid_for_listing_entry(1));
	}

	public function testReturnsNullWhenParentEntryNotFound()
	{
		ee()->setMock('db', new class {
			private $calls = 0;
			public function query($sql)
			{
				$this->calls++;
				if ($this->calls === 1) {
					return new class { public function row($c){ return 9; } };
				}
				return new class { public function row($c){ return null; } };
			}
		});

		$this->assertNull($this->structure->get_pid_for_listing_entry(1));
	}
}



