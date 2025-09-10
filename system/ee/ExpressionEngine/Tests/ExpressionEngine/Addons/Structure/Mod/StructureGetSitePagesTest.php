<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureGetSitePagesTest extends StructureTestBase
{
	public function testGetSitePagesDelegatesToSql()
	{
		$sitePages = [
			'uris' => [1 => '/', 2 => '/about/'],
			'url' => 'https://example.test/'
		];
		$this->structure->sql = new class($sitePages) {
			private $sp; public function __construct($sp){$this->sp=$sp;} public function get_site_pages(){return $this->sp;}
		};

		$this->assertSame($sitePages, $this->structure->get_site_pages());
	}

	public function testGetSitePagesHandlesNull()
	{
		$this->structure->sql = new class {
			public function get_site_pages(){ return null; }
		};
		$this->assertNull($this->structure->get_site_pages());
	}
}
