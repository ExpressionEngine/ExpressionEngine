<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureSetStatusTest extends StructureTestBase
{
	public function testUpdatesStatusViaDirectQuery()
	{
		$captured = (object) ['sql' => null];
		ee()->setMock('db', new class($captured) extends eeDbArMock {
			private $cap; public function __construct($c){ $this->cap=$c; }
			public function query($sql){ $this->cap->sql = $sql; return new eeDbResultMock([]); }
		});

		$this->structure->set_status(55, 'closed');
		$this->assertStringContainsString("UPDATE exp_channel_titles SET status = 'closed'", $captured->sql);
		$this->assertStringContainsString('entry_id = 55', $captured->sql);
	}

	public function testUpdatesDifferentStatusValueIsQuoted()
	{
		$captured = (object) ['sql' => null];
		ee()->setMock('db', new class($captured) extends eeDbArMock {
			private $cap; public function __construct($c){ $this->cap=$c; }
			public function query($sql){ $this->cap->sql = $sql; return new eeDbResultMock([]); }
		});

		$this->structure->set_status(99, 'new');
		$this->assertStringContainsString("status = 'new'", $captured->sql);
		$this->assertStringContainsString('entry_id = 99', $captured->sql);
	}
}



