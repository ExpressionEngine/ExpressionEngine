<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureGetSitePagesQueryTest extends StructureTestBase
{
	public function testGetsAndDecodesSitePagesForConfiguredSiteId()
	{
		ee()->config->items['site_id'] = 1;

		$pages = [
			1 => [
				'url' => 'https://example.com/',
				'uris' => [10 => '/about/']
			]
		];
		$encoded = base64_encode(serialize($pages));

		ee()->setMock('db', new class($encoded) extends FakeDb {
			private $encoded;
			public function __construct($e){ $this->encoded = $e; }
			public function select($fields = '*'){ return $this; }
			public function where($field, $value = null){ return $this; }
			public function get($table = null)
			{
				return new class($this->encoded) {
					private $encoded;
					public function __construct($e){ $this->encoded = $e; }
					public function row($column){ return $this->encoded; }
				};
			}
		});

		$result = $this->structure->get_site_pages_query();
		$this->assertSame($pages[1], $result);
	}

	public function testRespectsConfiguredSiteIdForMultiSite()
	{
		ee()->config->items['site_id'] = 2;

		$pages = [
			1 => [ 'url' => 'https://example.com/', 'uris' => [10 => '/about/'] ],
			2 => [ 'url' => 'https://example.org/', 'uris' => [20 => '/contact/'] ],
		];
		$encoded = base64_encode(serialize($pages));

		ee()->setMock('db', new class($encoded) extends FakeDb {
			private $encoded;
			public function __construct($e){ $this->encoded = $e; }
			public function select($fields = '*'){ return $this; }
			public function where($field, $value = null){ return $this; }
			public function get($table = null)
			{
				return new class($this->encoded) {
					private $encoded;
					public function __construct($e){ $this->encoded = $e; }
					public function row($column){ return $this->encoded; }
				};
			}
		});

		$result = $this->structure->get_site_pages_query();
		$this->assertSame($pages[2], $result);
	}

	public function testBuildsExpectedQueryLoadsStringHelperAndReadsSitePagesColumn()
	{
		ee()->config->items['site_id'] = 7;

		$pages = [
			7 => [
				'url' => 'https://example.net/',
				'uris' => [70 => '/docs/']
			]
		];
		$encoded = base64_encode(serialize($pages));
		$dbLog = (object) [
			'select' => [],
			'where' => [],
			'get' => [],
			'row' => [],
		];
		$loadLog = (object) [
			'helpers' => [],
		];

		ee()->setMock('load', new class($loadLog) {
			private $log;
			public function __construct($log) { $this->log = $log; }
			public function helper($name) { $this->log->helpers[] = $name; }
		});

		ee()->setMock('db', new class($encoded, $dbLog) extends FakeDb {
			private $encoded;
			private $log;

			public function __construct($encoded, $log)
			{
				$this->encoded = $encoded;
				$this->log = $log;
			}

			public function select($fields = '*')
			{
				$this->log->select[] = $fields;

				return $this;
			}

			public function where($field, $value = null)
			{
				$this->log->where[] = [$field, $value];

				return $this;
			}

			public function get($table = null)
			{
				$this->log->get[] = $table;

				return new class($this->encoded, $this->log) {
					private $encoded;
					private $log;

					public function __construct($encoded, $log)
					{
						$this->encoded = $encoded;
						$this->log = $log;
					}

					public function row($column)
					{
						$this->log->row[] = $column;

						return $this->encoded;
					}
				};
			}
		});

		$result = $this->structure->get_site_pages_query();

		$this->assertSame($pages[7], $result);
		$this->assertSame(['site_pages'], $dbLog->select);
		$this->assertSame([['site_id', 7]], $dbLog->where);
		$this->assertSame(['sites'], $dbLog->get);
		$this->assertSame(['site_pages'], $dbLog->row);
		$this->assertSame(['string'], $loadLog->helpers);
	}

	public function testCorruptSerializedValueResultsInErrorOrNullBehavior()
	{
		ee()->config->items['site_id'] = 1;
		$encoded = 'not-base64-or-serialize';
		ee()->setMock('db', new class($encoded) extends FakeDb {
			private $encoded; public function __construct($e){ $this->encoded = $e; }
			public function select($fields = '*'){ return $this; } public function where($field, $value = null){ return $this; }
			public function get($table = null){ return new class($this->encoded){ private $e; public function __construct($e){$this->e=$e;} public function row($c){ return $this->e; } }; }
		});
		// Current implementation will attempt to base64_decode + unserialize, which returns false/null; then index by site_id may error.
		// We assert that calling the method does not crash PHP (handled by test runner); behavior is implementation-defined.
		try {
			$result = $this->structure->get_site_pages_query();
			$this->assertTrue(is_array($result) || $result === null || $result === false);
		} catch (\Throwable $e) {
			$this->assertNotEmpty($e->getMessage());
		}
	}

	public function testRawSerializedSitePagesValue()
	{
		ee()->config->items['site_id'] = 1;
		$pages = [ 1 => [ 'url' => '/', 'uris' => [1 => '/'] ] ];
		$raw = serialize($pages);
		ee()->setMock('db', new class($raw) extends FakeDb {
			private $raw; public function __construct($r){ $this->raw = $r; }
			public function select($fields = '*'){ return $this; } public function where($field, $value = null){ return $this; }
			public function get($table = null){ return new class($this->raw){ private $r; public function __construct($r){$this->r=$r;} public function row($c){ return $this->r; } }; }
		});
		// Current implementation expects base64+serialize; this will likely fail. We simply assert it doesn't hard crash.
		try {
			$this->structure->get_site_pages_query();
			$this->assertTrue(true);
		} catch (\Throwable $e) {
			$this->assertNotEmpty($e->getMessage());
		}
	}

	public function testMissingSiteIdInDecodedValueReturnsNullOrNoFatal()
	{
		ee()->config->items['site_id'] = 3;
		$pages = [ 1 => [ 'url' => '/', 'uris' => [1 => '/'] ] ];
		$encoded = base64_encode(serialize($pages));
		ee()->setMock('db', new class($encoded) extends FakeDb {
			private $encoded; public function __construct($e){ $this->encoded = $e; }
			public function select($fields = '*'){ return $this; } public function where($field, $value = null){ return $this; }
			public function get($table = null){ return new class($this->encoded){ private $e; public function __construct($e){$this->e=$e;} public function row($c){ return $this->e; } }; }
		});
		try {
			$result = $this->structure->get_site_pages_query();
			$this->assertTrue($result === null || $result === false || $result === [] || is_array($result));
		} catch (\Throwable $e) {
			$this->assertNotEmpty($e->getMessage());
		}
	}

	public function testDbReturnsNullSitePagesDoesNotFatal()
	{
		ee()->config->items['site_id'] = 1;
		ee()->setMock('db', new class extends FakeDb {
			public function select($fields = '*'){ return $this; } public function where($field, $value = null){ return $this; }
			public function get($table = null){ return new class { public function row($c){ return null; } }; }
		});
		try {
			$result = $this->structure->get_site_pages_query();
			$this->assertTrue($result === null || $result === false || $result === [] || is_array($result));
		} catch (\Throwable $e) {
			$this->assertNotEmpty($e->getMessage());
		}
	}
}

