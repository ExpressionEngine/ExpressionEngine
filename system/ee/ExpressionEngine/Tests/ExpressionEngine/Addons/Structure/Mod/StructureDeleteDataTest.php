<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureDeleteDataTest extends StructureTestBase
{
	public function testRejectsInvalidIds()
	{
		$this->assertFalse($this->structure->delete_data('not-array'));
	}

	public function testDeletesSingleEntryAndRemovesFromSitePages()
	{
		$sitePages = [
			'url' => '/',
			'uris' => [10 => '/a', 11 => '/b'],
			'templates' => [10 => 1, 11 => 2],
		];

		$nsetStub = new class {
			public $deleted = [];
			public function getNode($id){ return ['entry_id' => $id, 'listing_cid' => 0]; }
			public function getTree($id){ return [['entry_id' => $id]]; }
			public function deleteNode($node){ $this->deleted[] = $node['entry_id']; }
		};
		$this->structure->nset = $nsetStub;

		// DB: For status update and listings query
		ee()->db->setRows([]);

		// Mock ee('Model') chain used for closing entries
		ee()->setMock('Model', new class {
			public function get($model, $id){ return $this; }
			public function fields($field){ return $this; }
			public function first(){ return $this; }
			public function setProperty($k,$v){ return $this; }
			public function save(){ return true; }
		});

		// Override set_site_pages to capture changes
		$captured = (object) ['pages' => null];
		$proxy = new class($this->structure, $sitePages, $captured) extends Structure {
			private $pages; private $cap; public function __construct($base,$pages,$cap){ foreach (get_object_vars($base) as $k=>$v){ $this->$k=$v; } $this->pages=$pages; $this->cap=$cap; }
			public function set_site_pages($site_id, $pages){ $this->cap->pages = $pages; }
			public function get_site_pages(){ return $this->pages; }
		};
		$proxy->sql = new class($sitePages){ private $pages; public function __construct($p){ $this->pages=$p; } public function get_site_pages(){ return $this->pages; } };

		$result = $proxy->delete_data(10);
		$this->assertTrue($result);
		$this->assertSame(['/b'], array_values($captured->pages['uris']));
		$this->assertContains(10, $nsetStub->deleted);
	}

	public function testDeletesNodeWithChildrenAndListings()
	{
		$sitePages = [ 'url' => '/', 'uris' => [1 => '/root', 2 => '/child', 200 => '/list'], 'templates' => [1=>1,2=>1,200=>1] ];
		$nsetStub = new class {
			public $deleted = [];
			public function getNode($id){ return ['entry_id' => $id, 'listing_cid' => 9]; }
			public function getTree($id){ return [['entry_id' => 1], ['entry_id' => 2]]; }
			public function deleteNode($node){ $this->deleted[] = $node['entry_id']; }
		};
		$this->structure->nset = $nsetStub;

		// Listings: when listing_cid != 0, it queries channel_titles for entry_id
		ee()->db->setRows([ ['entry_id' => 200] ]);

		// Mock ee('Model') chain again
		ee()->setMock('Model', new class {
			public function get($model, $id){ return $this; }
			public function fields($field){ return $this; }
			public function first(){ return $this; }
			public function setProperty($k,$v){ return $this; }
			public function save(){ return true; }
		});

		$captured = (object) ['pages' => null];
		$proxy = new class($this->structure, $sitePages, $captured) extends Structure {
			private $pages; private $cap; public function __construct($base,$pages,$cap){ foreach (get_object_vars($base) as $k=>$v){ $this->$k=$v; } $this->pages=$pages; $this->cap=$cap; }
			public function set_site_pages($site_id, $pages){ $this->cap->pages = $pages; }
			public function get_site_pages(){ return $this->pages; }
		};
		$proxy->sql = new class($sitePages){ private $pages; public function __construct($p){ $this->pages=$p; } public function get_site_pages(){ return $this->pages; } };

		$result = $proxy->delete_data([1]);
		$this->assertTrue($result);
		$this->assertArrayNotHasKey(1, $captured->pages['uris']);
		$this->assertArrayNotHasKey(2, $captured->pages['uris']);
		$this->assertArrayNotHasKey(200, $captured->pages['uris']);
		$this->assertContains(1, $nsetStub->deleted);
		$this->assertContains(2, $nsetStub->deleted);
	}

	public function testDuplicateIdsProcessedOnceAndListingsNotClosed()
	{
		$sitePages = [ 'url' => '/', 'uris' => [1 => '/root', 200 => '/list'], 'templates' => [1=>1,200=>1] ];
		$nsetStub = new class {
			public $deleted = []; public function getNode($id){ return ['entry_id'=>$id, 'listing_cid' => ($id===1?9:0)]; }
			public function getTree($id){ return [['entry_id' => 1]]; }
			public function deleteNode($node){ if (!in_array($node['entry_id'],$this->deleted)) { $this->deleted[] = $node['entry_id']; } }
		};
		$this->structure->nset = $nsetStub;

		ee()->db->setRows([ ['entry_id' => 200] ]);

		$calls = (object) ['closed' => []];
		ee()->setMock('Model', new class($calls) {
			private $calls; private $currentId; public function __construct($c){ $this->calls=$c; }
			public function get($model, $id){ $this->currentId=$id; return $this; }
			public function fields($field){ return $this; }
			public function first(){ return $this; }
			public function setProperty($k,$v){ if ($k==='status' && $v==='closed') { $this->calls->closed[] = $this->currentId; } return $this; }
			public function save(){ return true; }
		});

		$captured = (object) ['pages' => null];
		$proxy = new class($this->structure, $sitePages, $captured) extends Structure {
			private $pages; private $cap; public function __construct($base,$pages,$cap){ foreach (get_object_vars($base) as $k=>$v){ $this->$k=$v; } $this->pages=$pages; $this->cap=$cap; }
			public function set_site_pages($site_id, $pages){ $this->cap->pages = $pages; }
			public function get_site_pages(){ return $this->pages; }
		};
		$proxy->sql = new class($sitePages){ private $pages; public function __construct($p){ $this->pages=$p; } public function get_site_pages(){ return $this->pages; } };

		$result = $proxy->delete_data([1,1]);
		$this->assertTrue($result);
		$this->assertContains(1, $nsetStub->deleted);
		$this->assertContains(200, $nsetStub->deleted);
		$this->assertSame($nsetStub->deleted, array_values(array_unique($nsetStub->deleted)));
		$this->assertNotContains(200, $calls->closed);
	}

	public function testEmptyIdsArrayResultsInNoChangesAndTrue()
	{
		$sitePages = [ 'url' => '/', 'uris' => [], 'templates' => [] ];
		$this->structure->nset = new class { public function getNode($id){ return null; } public function getTree($id){ return []; } public function deleteNode($node){ } };
		$captured = (object) ['pages' => null];
		$proxy = new class($this->structure, $sitePages, $captured) extends Structure {
			private $pages; private $cap; public function __construct($base,$pages,$cap){ foreach (get_object_vars($base) as $k=>$v){ $this->$k=$v; } $this->pages=$pages; $this->cap=$cap; }
			public function set_site_pages($site_id, $pages){ $this->cap->pages = $pages; }
			public function get_site_pages(){ return $this->pages; }
		};
		$proxy->sql = new class($sitePages){ private $pages; public function __construct($p){ $this->pages=$p; } public function get_site_pages(){ return $this->pages; } };

		$result = $proxy->delete_data([]);
		$this->assertTrue($result);
		$this->assertSame($sitePages, $captured->pages);
	}
}



