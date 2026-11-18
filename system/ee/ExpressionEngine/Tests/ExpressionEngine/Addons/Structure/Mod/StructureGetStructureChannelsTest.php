<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureGetStructureChannelsTest extends StructureTestBase
{
	/**
	 * Verifies allowed-channel formatting keeps keyed rows and strips channel_id from payloads.
	 *
	 * @return void
	 */
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

		$db = new class($rows) extends FakeDb {
			public $rows; public function __construct($r){$this->rows=$r;}
			public $queries = [];
			public function query($sql)
			{
				$this->queries[] = $sql;

				return new eeDbResultMock($this->rows);
			}
		};
		ee()->setMock('db', $db);

		$result = $this->structure->get_structure_channels('', '', '', true);

		$this->assertArrayHasKey(10, $result);
		$this->assertArrayHasKey(20, $result);
		$this->assertSame('page', $result[10]['type']);
		$this->assertSame(7, $result[10]['template_id']);
		$this->assertSame('Blog', $result[10]['channel_title']);
		$this->assertArrayNotHasKey('channel_id', $result[10]);
		$this->assertStringContainsString("AND esc.channel_id IN (10,20)", $db->queries[0]);
	}

	/**
	 * Verifies type, channel, and ordering filters are appended to the Structure query.
	 *
	 * @return void
	 */
	public function testCanFilterByTypeAndChannelId()
	{
		ee()->config->items['site_id'] = 1;
		ee()->setMock('functions', new class {
			public function fetch_assigned_channels() { return [10, 20]; }
		});

		$rows = [
			['channel_id' => 10, 'channel_title' => 'Blog', 'template_id' => 7, 'type' => 'page', 'site_id' => 1],
		];
		$db = new class($rows) extends FakeDb {
			public $rows; public function __construct($r){$this->rows=$r;}
			public $queries = [];
			public function query($sql)
			{
				$this->queries[] = $sql;

				return new eeDbResultMock($this->rows);
			}
		};
		ee()->setMock('db', $db);

		$result = $this->structure->get_structure_channels('page', 10, 'alpha', true);
		$this->assertCount(1, $result);
		$this->assertArrayHasKey(10, $result);
		$this->assertStringContainsString("AND esc.channel_id IN (10,20)", $db->queries[0]);
		$this->assertStringContainsString("AND esc.type = 'page'", $db->queries[0]);
		$this->assertStringContainsString("AND esc.channel_id = '10'", $db->queries[0]);
		$this->assertStringContainsString('ORDER BY ec.channel_title', $db->queries[0]);
	}

	/**
	 * Verifies the configured site id drives the multi-site channel query constraint.
	 *
	 * @return void
	 */
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
		$db = new class($rows) extends FakeDb {
			public $rows; public function __construct($r){$this->rows=$r;}
			public $queries = [];
			public function query($sql)
			{
				$this->queries[] = $sql;

				return new eeDbResultMock($this->rows);
			}
		};
		ee()->setMock('db', $db);

		$result = $this->structure->get_structure_channels('', '', '', true);
		$this->assertArrayHasKey(10, $result);
		$this->assertArrayHasKey(40, $result);
		$this->assertStringContainsString("WHERE ec.site_id = '2'", $db->queries[0]);
	}

	/**
	 * Verifies null template and type values survive output formatting without notices.
	 *
	 * @return void
	 */
	public function testHandlesNullTemplateOrMissingTypeFields()
	{
		ee()->config->items['site_id'] = 1;
		ee()->setMock('functions', new class {
			public function fetch_assigned_channels() { return [50]; }
		});
		$rows = [
			['channel_id' => 50, 'channel_title' => 'Untyped', 'template_id' => null, 'type' => null, 'site_id' => 1],
		];
		$db = new class($rows) extends FakeDb {
			public $rows; public function __construct($r){$this->rows=$r;}
			public function query($sql) { return new eeDbResultMock($this->rows); }
		};
		ee()->setMock('db', $db);
		$result = $this->structure->get_structure_channels('', '', 'alpha', true);
		$this->assertArrayHasKey(50, $result);
		$this->assertArrayHasKey('template_id', $result[50]);
		$this->assertArrayHasKey('type', $result[50]);
	}

	/**
	 * Verifies an empty assigned-channel list triggers the early-return guard before DB access.
	 *
	 * @return void
	 */
	public function testNoAssignedChannelsReturnsNullWithoutQueryingDatabase()
	{
		ee()->config->items['site_id'] = 1;
		ee()->setMock('functions', new class {
			public function fetch_assigned_channels() { return []; }
		});

		$db = new class extends FakeDb {
			public $queries = [];
			public function query($sql)
			{
				$this->queries[] = $sql;

				return new eeDbResultMock([]);
			}
		};
		ee()->setMock('db', $db);

		$result = $this->structure->get_structure_channels('', '', '', true);

		$this->assertNull($result);
		$this->assertSame([], $db->queries);
	}

	/**
	 * Verifies the unfiltered path skips the allowed-channel SQL clause and formats empty results.
	 *
	 * @return void
	 */
	public function testReturnsEmptyArrayWithoutAllowedChannelFilterWhenAllowedFlagIsFalse()
	{
		ee()->config->items['site_id'] = 1;
		ee()->setMock('functions', new class {
			public function fetch_assigned_channels() { return [10, 20]; }
		});

		$db = new class extends FakeDb {
			public $queries = [];
			public function query($sql)
			{
				$this->queries[] = $sql;

				return new eeDbResultMock([]);
			}
		};
		ee()->setMock('db', $db);

		$result = $this->structure->get_structure_channels('', '', '', false);

		$this->assertSame([], $result);
		$this->assertCount(1, $db->queries);
		$this->assertStringContainsString("WHERE ec.site_id = '1'", $db->queries[0]);
		$this->assertStringNotContainsString('AND esc.channel_id IN (10,20)', $db->queries[0]);
	}
}
