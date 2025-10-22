<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureSetListingDataTest extends StructureTestBase
{
    public function testSetListingDataUpdatesSitePagesAndUpsertsRow()
    {
        $captured = (object) ['update_data' => null, 'queries' => [], 'get_where_calls' => 0];
        ee()->config->items['site_id'] = 1;

        // Ensure extensions mock exists for hook checks inside set_listing_data
        ee()->setMock('extensions', new class {
            public function active_hook($name) { return false; }
            public function call($name) { return null; }
        });

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap) { $this->cap = $cap; }
            public function escape_str($str) { return addslashes($str); }
            public function update_string($table, $data, $where) { $this->cap->update_data = $data; return 'UPDATE ' . $table; }
            public function insert_string($table, $data) { return 'INSERT ' . $table; }
            public function get_where($table, $where)
            {
                $this->cap->get_where_calls++;
                // Simulate no existing row on first call
                return new eeDbResultMock([]);
            }
            public function query($sql)
            {
                $this->cap->queries[] = $sql;
                return new eeDbResultMock([]);
            }
        });

        $initialSitePages = ['url' => '/', 'uris' => [], 'templates' => []];
        $this->structure->sql = new class($initialSitePages) {
            private $sp;
            public function __construct($sp) { $this->sp = $sp; }
            public function get_site_pages() { return $this->sp; }
            public function update_root_node() {}
        };

        // No direct spying on set_site_pages; we'll capture via DB->update_string

        $data = [
            'entry_id' => 77,
            'template_id' => 5,
            'parent_uri' => '/parent',
            'uri' => 'child',
            'listing_cid' => 9,
        ];

        // Call
        $this->structure->set_listing_data($data);

        // Assert site_pages updated (decode what was stored into exp_sites.site_pages)
        $this->assertNotNull($captured->update_data);
        $decoded = unserialize(base64_decode($captured->update_data['site_pages']));
        $this->assertSame('/parent/child', $decoded[1]['uris'][77]);
        $this->assertSame(5, $decoded[1]['templates'][77]);
        // Assert INSERT executed (since no existing row)
        $this->assertNotEmpty($captured->queries);
    }
}



