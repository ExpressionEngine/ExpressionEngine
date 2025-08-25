<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureSetListingDataTest extends StructureTestBase
{
    public function testSetListingDataUpdatesSitePagesAndUpsertsRow()
    {
        $captured = (object) ['site_pages' => null, 'queries' => [], 'get_where_calls' => 0];
        ee()->config->items['site_id'] = 1;

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap) { $this->cap = $cap; }
            public function escape_str($str) { return addslashes($str); }
            public function update_string($table, $data, $where) { return 'UPDATE ' . $table; }
            public function insert_string($table, $data) { return 'INSERT ' . $table; }
            public function get_where($table, $where)
            {
                $this->cap->get_where_calls++;
                // Simulate no existing row on first call
                return new FakeDbResult([]);
            }
            public function query($sql)
            {
                $this->cap->queries[] = $sql;
                return new FakeDbResult([]);
            }
        });

        $initialSitePages = ['url' => '/', 'uris' => [], 'templates' => []];
        $this->structure->sql = new class($initialSitePages) {
            private $sp;
            public function __construct($sp) { $this->sp = $sp; }
            public function get_site_pages() { return $this->sp; }
            public function update_root_node() {}
        };

        // Spy set_site_pages to capture written pages
        $self = $this;
        $this->structure->set_site_pages = function($site_id, $site_pages) use ($captured) {
            $captured->site_pages = $site_pages;
        };

        $data = [
            'entry_id' => 77,
            'template_id' => 5,
            'parent_uri' => '/parent',
            'uri' => 'child',
            'listing_cid' => 9,
        ];

        // Call
        $this->structure->set_listing_data($data);

        // Assert site_pages updated
        $this->assertSame('/parent/child', $captured->site_pages['uris'][77]);
        $this->assertSame(5, $captured->site_pages['templates'][77]);
        // Assert INSERT executed (since no existing row)
        $this->assertNotEmpty($captured->queries);
    }
}



