<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureGetChannelsByTypeTest extends StructureTestBase
{
	public function testReturnsArrayOfRowsForGivenType()
	{
		$rows = [
			['channel_id' => 10, 'type' => 'page'],
			['channel_id' => 11, 'type' => 'page'],
		];

		ee()->setMock('db', new class($rows) extends FakeDb {
			public $rows; public function __construct($r){$this->rows=$r;}
			public function get_where($table, $where = null, $limit = null, $offset = null) { return new eeDbResultMock($this->rows); }
		});

		$result = $this->structure->get_channels_by_type('page');
		$this->assertIsArray($result);
		$this->assertCount(2, $result);
		$this->assertSame(10, $result[0]['channel_id']);
		$this->assertSame('page', $result[0]['type']);
	}

	public function testUnknownTypeReturnsEmptyArray()
	{
		ee()->setMock('db', new class extends FakeDb {
			public function get_where($table, $where = null, $limit = null, $offset = null) { return new eeDbResultMock([]); }
		});
		$result = $this->structure->get_channels_by_type('unknown');
		$this->assertSame([], $result);
	}

	public function testCaseSensitivityDelegatedToDatabase()
	{
		$rows = [ ['channel_id' => 12, 'type' => 'Page'] ];
		ee()->setMock('db', new class($rows) extends FakeDb {
			public $rows; public function __construct($r){$this->rows=$r;}
			public function get_where($table, $where = null, $limit = null, $offset = null) { return new eeDbResultMock($this->rows); }
		});
		$result = $this->structure->get_channels_by_type('Page');
		$this->assertCount(1, $result);
		$this->assertSame('Page', $result[0]['type']);
	}
}


