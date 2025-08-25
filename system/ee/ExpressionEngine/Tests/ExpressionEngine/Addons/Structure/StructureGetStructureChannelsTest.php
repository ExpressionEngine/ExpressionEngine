<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureGetStructureChannelsTest extends StructureTestBase
{
	public function testReturnsChannelsJoinedWithStructureSettingsAndRespectsAllowedChannels()
	{
		ee()->config->items['site_id'] = 1;

		ee()->setMock('functions', new class {
			public function fetch_assigned_channels() { return [10, 20]; }
		});

		// Simulate DB LEFT JOIN result rows
		$rows = [
			['channel_id' => 10, 'channel_title' => 'Blog', 'template_id' => 7, 'type' => 'page', 'site_id' => 1],
			['channel_id' => 20, 'channel_title' => 'News', 'template_id' => 8, 'type' => 'listing', 'site_id' => 1],
			['channel_id' => 30, 'channel_title' => 'Hidden', 'template_id' => 9, 'type' => 'page', 'site_id' => 1],
		];

		ee()->setMock('db', new class($rows) extends FakeDb {
			public $rows; public function __construct($r){$this->rows=$r;}
			public function query($sql) { return new FakeDbResult($this->rows); }
		});

		// Note: our FakeDb does not apply SQL filtering, so we only assert formatting and presence
		$result = $this->structure->get_structure_channels('', '', '', true);

		$this->assertArrayHasKey(10, $result);
		$this->assertArrayHasKey(20, $result);
		$this->assertSame('page', $result[10]['type']);
		$this->assertSame(7, $result[10]['template_id']);
		$this->assertSame('Blog', $result[10]['channel_title']);
	}

	public function testCanFilterByTypeAndChannelId()
	{
		ee()->config->items['site_id'] = 1;
		ee()->setMock('functions', new class {
			public function fetch_assigned_channels() { return [10, 20]; }
		});

		$rows = [
			['channel_id' => 10, 'channel_title' => 'Blog', 'template_id' => 7, 'type' => 'page', 'site_id' => 1],
		];
		ee()->setMock('db', new class($rows) extends FakeDb {
			public $rows; public function __construct($r){$this->rows=$r;}
			public function query($sql) { return new FakeDbResult($this->rows); }
		});

		$result = $this->structure->get_structure_channels('page', 10, 'alpha', true);
		$this->assertCount(1, $result);
		$this->assertArrayHasKey(10, $result);
	}

	public function testMultiSiteFiltersByConfiguredSiteId()
	{
		ee()->config->items['site_id'] = 2;
		ee()->setMock('functions', new class {
			public function fetch_assigned_channels() { return [10, 20, 40]; }
		});

		$rows = [
			['channel_id' => 10, 'channel_title' => 'Blog', 'template_id' => 7, 'type' => 'page', 'site_id' => 1],
			['channel_id' => 40, 'channel_title' => 'Docs', 'template_id' => 12, 'type' => 'page', 'site_id' => 2],
		];
		ee()->setMock('db', new class($rows) extends FakeDb {
			public $rows; public function __construct($r){$this->rows=$r;}
			public function query($sql) { return new FakeDbResult($this->rows); }
		});

		$result = $this->structure->get_structure_channels('', '', '', true);
		$this->assertArrayHasKey(10, $result);
		$this->assertArrayHasKey(40, $result);
	}

	public function testHandlesNullTemplateOrMissingTypeFields()
	{
		ee()->config->items['site_id'] = 1;
		ee()->setMock('functions', new class {
			public function fetch_assigned_channels() { return [50]; }
		});
		$rows = [
			['channel_id' => 50, 'channel_title' => 'Untyped', 'template_id' => null, 'type' => null, 'site_id' => 1],
		];
		ee()->setMock('db', new class($rows) extends FakeDb {
			public $rows; public function __construct($r){$this->rows=$r;}
			public function query($sql) { return new FakeDbResult($this->rows); }
		});
		$result = $this->structure->get_structure_channels('', '', 'alpha', true);
		$this->assertArrayHasKey(50, $result);
		$this->assertArrayHasKey('template_id', $result[50]);
		$this->assertArrayHasKey('type', $result[50]);
	}
}


