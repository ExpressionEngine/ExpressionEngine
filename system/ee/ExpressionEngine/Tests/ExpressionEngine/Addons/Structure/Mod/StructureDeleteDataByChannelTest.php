<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureDeleteDataByChannelTest extends StructureTestBase
{
	public function testRejectsNonNumericChannelId()
	{
		$this->assertFalse($this->structure->delete_data_by_channel('abc'));
	}

	public function testRejectsWhenUserHasNoPermission()
	{
		// Stub sql with user_access returning false
		$this->structure->sql = new class {
			public function get_settings(){ return []; }
			public function user_access($p, $s){ return false; }
		};
		$this->assertFalse($this->structure->delete_data_by_channel(5));
	}

	public function testDeletesEntriesAndUpdatesSitePagesAndListings()
	{
		// Allow permission
		$sitePages = [
			'url' => '/',
			'uris' => [100 => '/a', 101 => '/b'],
			'templates' => [100 => 1, 101 => 2],
		];
		$captured = (object) [ 'setSitePages' => null, 'queries' => [] ];

		$this->structure->nset = new class {
			public $deleted = [];
			public function getNode($id){ return ['entry_id' => $id]; }
			public function deleteNode($node){ $this->deleted[] = $node['entry_id']; }
		};

		$this->structure->sql = new class($sitePages) {
			private $pages; public function __construct($p){ $this->pages=$p; }
			public function get_settings(){ return []; }
			public function user_access($p,$s){ return true; }
		};

		// Mock DB with minimal chainable methods used by helper and queries used by method
		ee()->setMock('db', new class($captured) {
			private $cap; private $call=0; private $lastFrom=null; private $lastGetTable=null;
			public function __construct($c){ $this->cap=$c; }
			public function query($sql){ $this->cap->queries[] = $sql; $this->call++; if ($this->call === 1) { return new eeDbResultMock([ ['entry_id' => 100], ['entry_id' => 101] ]); } return new eeDbResultMock([]); }
			public function order_by($f,$d){ return $this; }
			public function select($f='*'){ return $this; }
			public function from($t){ $this->lastFrom = $t; return $this; }
			public function where($f,$v){ return $this; }
			public function limit($n){ return $this; }
			public function where_in($f,$ids){ return $this; }
			public function delete($t){ return true; }
			public function update($t,$data){ return true; }
			public function insert($t,$data){ return true; }
			public function get($t=null,$l=null,$o=null){
				// Handle stale history get on structure_nav_history
				$table = $t ?: $this->lastFrom;
				$this->lastGetTable = $table;
				if ($table === 'structure_nav_history') {
					return new class {
						public $num_rows = 0;
						public function result(){ return []; }
					};
				}
				if ($table === 'sites') {
					return new class {
						public $num_rows = 1;
						public function result(){ return [ (object) ['site_id' => 1, 'site_pages' => base64_encode(serialize([1=>['url'=>'/','uris'=>[]]]))] ]; }
					};
				}
				return new eeDbResultMock([]);
			}
		});

		// Mock helper functions used by method
		ee()->config->items['site_id'] = 1;
		
		// Mock get_site_pages_query and set_site_pages via partials: we'll override on structure instance
		$ref = new ReflectionClass($this->structure);
		$setSitePages = $ref->getMethod('set_site_pages');
		\TestReflectionHelper::makeMethodAccessible($setSitePages);

		// Monkey-patch by creating a proxy with __call for set_site_pages and get_site_pages_query
		$structureProxy = new class($this->structure, $sitePages, $captured) extends Structure {
			private $pages; private $cap; public function __construct($base, $pages, $cap){ foreach (get_object_vars($base) as $k=>$v){ $this->$k=$v; } $this->pages=$pages; $this->cap=$cap; }
			public function get_site_pages_query(){ return $this->pages; }
			public function set_site_pages($site_id, $pages){ $this->cap->setSitePages = $pages; }
		};

		$result = $structureProxy->delete_data_by_channel(9);
		$this->assertTrue($result);
		$this->assertIsArray($captured->setSitePages);
		$this->assertArrayNotHasKey(100, $captured->setSitePages['uris']);
	}

	public function testHandlesNoEntriesForChannelGracefully()
	{
		$sitePages = [ 'url' => '/', 'uris' => [], 'templates' => [] ];
		$captured = (object) [ 'setSitePages' => null, 'queries' => [] ];

		$this->structure->nset = new class {
			public function getNode($id){ return null; }
			public function deleteNode($node){ }
		};

		$this->structure->sql = new class($sitePages) {
			private $pages; public function __construct($p){ $this->pages=$p; }
			public function get_settings(){ return []; }
			public function user_access($p,$s){ return true; }
		};

		ee()->setMock('db', new class($captured) {
			private $cap; private $lastFrom=null; private $lastGetTable=null;
			public function __construct($c){ $this->cap=$c; }
			public function query($sql){ $this->cap->queries[] = $sql; return new eeDbResultMock([]); }
			public function order_by($f,$d){ return $this; }
			public function select($f='*'){ return $this; }
			public function from($t){ $this->lastFrom = $t; return $this; }
			public function where($f,$v){ return $this; }
			public function limit($n){ return $this; }
			public function where_in($f,$ids){ return $this; }
			public function delete($t){ return true; }
			public function update($t,$data){ return true; }
			public function insert($t,$data){ return true; }
			public function get($t=null,$l=null,$o=null){
				$table = $t ?: $this->lastFrom;
				$this->lastGetTable = $table;
				if ($table === 'structure_nav_history') {
					return new class {
						public $num_rows = 0;
						public function result(){ return []; }
					};
				}
				if ($table === 'sites') {
					return new class {
						public $num_rows = 1;
						public function result(){ return [ (object) ['site_id' => 1, 'site_pages' => base64_encode(serialize([1=>['url'=>'/','uris'=>[]]]))] ]; }
					};
				}
				if ($table === 'structure') {
					return new class { public function result(){ return []; } };
				}
				return new eeDbResultMock([]);
			}
		});

		ee()->config->items['site_id'] = 1;

		$structureProxy = new class($this->structure, $sitePages, $captured) extends Structure {
			private $pages; private $cap; public function __construct($base, $pages, $cap){ foreach (get_object_vars($base) as $k=>$v){ $this->$k=$v; } $this->pages=$pages; $this->cap=$cap; }
			public function get_site_pages_query(){ return $this->pages; }
			public function set_site_pages($site_id, $pages){ $this->cap->setSitePages = $pages; }
		};

		$result = $structureProxy->delete_data_by_channel(9);
		$this->assertTrue($result);
		$this->assertSame($sitePages, $captured->setSitePages);
	}

	public function testDeletesStructureNodeWhenEntryHasNoSitePagesMapping()
	{
		$sitePages = [
			'url' => '/',
			'uris' => [102 => '/untouched'],
			'templates' => [102 => 3],
		];
		$captured = (object) [ 'setSitePages' => null, 'queries' => [] ];

		$this->structure->nset = new class {
			public $deleted = [];
			public function getNode($id){ return ['entry_id' => $id]; }
			public function deleteNode($node){ $this->deleted[] = $node['entry_id']; }
		};

		$this->structure->sql = new class {
			public function get_settings(){ return []; }
			public function user_access($p, $s){ return true; }
		};

		ee()->setMock('db', new class($captured) {
			private $cap; private $call = 0; private $lastFrom = null; private $lastGetTable = null;
			public function __construct($c){ $this->cap = $c; }
			public function query($sql){ $this->cap->queries[] = $sql; $this->call++; if ($this->call === 1) { return new eeDbResultMock([['entry_id' => 100]]); } return new eeDbResultMock([]); }
			public function order_by($f, $d){ return $this; }
			public function select($f = '*'){ return $this; }
			public function from($t){ $this->lastFrom = $t; return $this; }
			public function where($f, $v){ return $this; }
			public function limit($n){ return $this; }
			public function where_in($f, $ids){ return $this; }
			public function delete($t){ return true; }
			public function update($t, $data){ return true; }
			public function insert($t, $data){ return true; }
			public function get($t = null, $l = null, $o = null){
				$table = $t ?: $this->lastFrom;
				$this->lastGetTable = $table;
				if ($table === 'structure_nav_history') {
					return new class {
						public $num_rows = 0;
						public function result(){ return []; }
					};
				}
				if ($table === 'sites') {
					return new class {
						public $num_rows = 1;
						public function result(){ return [ (object) ['site_id' => 1, 'site_pages' => base64_encode(serialize([1 => ['url' => '/', 'uris' => []]]))] ]; }
					};
				}
				if ($table === 'structure') {
					return new class { public function result(){ return []; } };
				}
				return new eeDbResultMock([]);
			}
		});

		ee()->config->items['site_id'] = 1;

		$structureProxy = new class($this->structure, $sitePages, $captured) extends Structure {
			private $pages; private $cap; public function __construct($base, $pages, $cap){ foreach (get_object_vars($base) as $k => $v){ $this->$k = $v; } $this->pages = $pages; $this->cap = $cap; }
			public function get_site_pages_query(){ return $this->pages; }
			public function set_site_pages($site_id, $pages){ $this->cap->setSitePages = $pages; }
		};

		$result = $structureProxy->delete_data_by_channel(9);

		$this->assertTrue($result);
		$this->assertSame([100], $this->structure->nset->deleted);
		$this->assertSame($sitePages, $captured->setSitePages);
		$this->assertCount(3, $captured->queries);
		$this->assertStringContainsString('SELECT entry_id', $captured->queries[0]);
		$this->assertSame('UPDATE exp_structure SET listing_cid = 0 WHERE listing_cid = 9', $captured->queries[1]);
		$this->assertStringContainsString('DELETE FROM exp_structure_listings', $captured->queries[2]);
	}

	public function testRemovesSitePagesMappingWhenEntryHasNoStructureNode()
	{
		$sitePages = [
			'url' => '/',
			'uris' => [101 => '/mapped', 102 => '/untouched'],
			'templates' => [101 => 2, 102 => 3],
		];
		$captured = (object) [ 'setSitePages' => null, 'queries' => [] ];

		$this->structure->nset = new class {
			public $deleted = [];
			public function getNode($id){ return null; }
			public function deleteNode($node){ $this->deleted[] = $node['entry_id']; }
		};

		$this->structure->sql = new class {
			public function get_settings(){ return []; }
			public function user_access($p, $s){ return true; }
		};

		ee()->setMock('db', new class($captured) {
			private $cap; private $call = 0; private $lastFrom = null; private $lastGetTable = null;
			public function __construct($c){ $this->cap = $c; }
			public function query($sql){ $this->cap->queries[] = $sql; $this->call++; if ($this->call === 1) { return new eeDbResultMock([['entry_id' => 101]]); } return new eeDbResultMock([]); }
			public function order_by($f, $d){ return $this; }
			public function select($f = '*'){ return $this; }
			public function from($t){ $this->lastFrom = $t; return $this; }
			public function where($f, $v){ return $this; }
			public function limit($n){ return $this; }
			public function where_in($f, $ids){ return $this; }
			public function delete($t){ return true; }
			public function update($t, $data){ return true; }
			public function insert($t, $data){ return true; }
			public function get($t = null, $l = null, $o = null){
				$table = $t ?: $this->lastFrom;
				$this->lastGetTable = $table;
				if ($table === 'structure_nav_history') {
					return new class {
						public $num_rows = 0;
						public function result(){ return []; }
					};
				}
				if ($table === 'sites') {
					return new class {
						public $num_rows = 1;
						public function result(){ return [ (object) ['site_id' => 1, 'site_pages' => base64_encode(serialize([1 => ['url' => '/', 'uris' => []]]))] ]; }
					};
				}
				if ($table === 'structure') {
					return new class { public function result(){ return []; } };
				}
				return new eeDbResultMock([]);
			}
		});

		ee()->config->items['site_id'] = 1;

		$structureProxy = new class($this->structure, $sitePages, $captured) extends Structure {
			private $pages; private $cap; public function __construct($base, $pages, $cap){ foreach (get_object_vars($base) as $k => $v){ $this->$k = $v; } $this->pages = $pages; $this->cap = $cap; }
			public function get_site_pages_query(){ return $this->pages; }
			public function set_site_pages($site_id, $pages){ $this->cap->setSitePages = $pages; }
		};

		$result = $structureProxy->delete_data_by_channel(9);

		$this->assertTrue($result);
		$this->assertSame([], $this->structure->nset->deleted);
		$this->assertSame([102 => '/untouched'], $captured->setSitePages['uris']);
		$this->assertSame([102 => 3], $captured->setSitePages['templates']);
		$this->assertCount(3, $captured->queries);
		$this->assertStringContainsString('SELECT entry_id', $captured->queries[0]);
		$this->assertSame('UPDATE exp_structure SET listing_cid = 0 WHERE listing_cid = 9', $captured->queries[1]);
		$this->assertStringContainsString('DELETE FROM exp_structure_listings', $captured->queries[2]);
	}
}


