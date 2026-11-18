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
            public function get_where($table, $where = null, $limit = null, $offset = null)
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

    public function testSetListingDataUsesProvidedSitePagesAndSkipsSqlFetch()
    {
        $captured = (object) [
            'site_pages_update_data' => null,
            'insert_data' => null,
            'queries' => [],
        ];
        $sqlState = (object) [
            'get_site_pages_calls' => 0,
            'update_root_node_calls' => 0,
        ];

        ee()->config->items['site_id'] = 4;

        ee()->setMock('extensions', new class {
            public function active_hook($name) { return false; }
            public function call($name, $listing = null) { return $listing; }
        });

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;

            public function __construct($cap)
            {
                $this->cap = $cap;
            }

            public function escape_str($str)
            {
                return addslashes($str);
            }

            public function update_string($table, $data, $where)
            {
                if ($table === 'exp_sites') {
                    $this->cap->site_pages_update_data = $data;
                }

                return 'UPDATE ' . $table;
            }

            public function insert_string($table, $data)
            {
                $this->cap->insert_data = $data;

                return 'INSERT ' . $table;
            }

            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return new eeDbResultMock([]);
            }

            public function query($sql)
            {
                $this->cap->queries[] = $sql;

                return new eeDbResultMock([]);
            }
        });

        $this->structure->sql = new class($sqlState) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function get_site_pages()
            {
                $this->state->get_site_pages_calls++;

                return ['url' => '/', 'uris' => [], 'templates' => []];
            }

            public function update_root_node()
            {
                $this->state->update_root_node_calls++;
            }
        };

        $providedSitePages = [
            'url' => '/',
            'uris' => [11 => '/existing'],
            'templates' => [11 => 2],
        ];
        $data = [
            'entry_id' => 99,
            'template_id' => 12,
            'parent_uri' => '/parent',
            'uri' => 'child',
            'listing_cid' => 6,
        ];

        $this->structure->set_listing_data($data, $providedSitePages);

        $this->assertSame(0, $sqlState->get_site_pages_calls);
        $this->assertSame(1, $sqlState->update_root_node_calls);
        $this->assertNotNull($captured->site_pages_update_data);

        $decoded = unserialize(base64_decode($captured->site_pages_update_data['site_pages']));
        $this->assertSame('/existing', $decoded[4]['uris'][11]);
        $this->assertSame('/parent/child', $decoded[4]['uris'][99]);
        $this->assertSame(2, $decoded[4]['templates'][11]);
        $this->assertSame(12, $decoded[4]['templates'][99]);

        $this->assertIsArray($captured->insert_data);
        $this->assertSame(4, $captured->insert_data['site_id']);
        $this->assertArrayNotHasKey('listing_cid', $captured->insert_data);
        $this->assertArrayNotHasKey('parent_uri', $captured->insert_data);
        $this->assertNotEmpty($captured->queries);
    }

    public function testSetListingDataUsesUpdatePathWhenRowAlreadyExists()
    {
        $captured = (object) ['update_data' => null, 'where' => null, 'queries' => []];
        ee()->config->items['site_id'] = 1;

        ee()->setMock('extensions', new class {
            public function active_hook($name) { return false; }
            public function call($name, $listing = null) { return $listing; }
        });

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap) { $this->cap = $cap; }
            public function update_string($table, $data, $where)
            {
                $this->cap->update_data = $data;
                $this->cap->where = $where;
                return 'UPDATE ' . $table;
            }
            public function insert_string($table, $data) { return 'INSERT ' . $table; }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return new eeDbResultMock([['entry_id' => $where['entry_id']]]);
            }
            public function query($sql)
            {
                $this->cap->queries[] = $sql;
                return new eeDbResultMock([]);
            }
        });

        $this->structure->sql = new class {
            public function get_site_pages() { return ['url' => '/', 'uris' => [], 'templates' => []]; }
            public function update_root_node() {}
        };

        $data = [
            'entry_id' => 88,
            'template_id' => 9,
            'parent_uri' => '/parent',
            'uri' => 'child',
            'listing_cid' => 2,
        ];

        $this->structure->set_listing_data($data);

        $this->assertIsArray($captured->update_data);
        $this->assertArrayNotHasKey('entry_id', $captured->update_data);
        $this->assertStringContainsString('entry_id = 88', $captured->where);
        $this->assertNotEmpty($captured->queries);
    }

    public function testSetListingDataHookPathTriggersUndefinedListingVariableError()
    {
        ee()->config->items['site_id'] = 1;

        ee()->setMock('extensions', new class {
            public function active_hook($name) { return $name === 'structure_before_save_listing'; }
            public function call($name, $listing = null) { return $listing; }
        });

        ee()->setMock('db', new class extends FakeDb {
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return new eeDbResultMock([['entry_id' => $where['entry_id']]]);
            }
            public function update_string($table, $data, $where) { return 'UPDATE ' . $table; }
            public function insert_string($table, $data) { return 'INSERT ' . $table; }
            public function query($sql) { return new eeDbResultMock([]); }
        });

        $this->structure->sql = new class {
            public function get_site_pages() { return ['url' => '/', 'uris' => [], 'templates' => []]; }
            public function update_root_node() {}
        };

        $data = [
            'entry_id' => 88,
            'template_id' => 9,
            'parent_uri' => '/parent',
            'uri' => 'child',
            'listing_cid' => 2,
        ];

        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Undefined variable');

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $this->structure->set_listing_data($data);
        } finally {
            restore_error_handler();
        }
    }
}
