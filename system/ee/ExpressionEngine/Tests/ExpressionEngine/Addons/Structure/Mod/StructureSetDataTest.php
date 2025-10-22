<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureSetDataTest extends StructureTestBase
{
    public function testSetDataWritesSitePagesAndCallsUpdateRootNode()
    {
        // Capture site_pages updates and that update_root_node is called
        $captured = (object) ['site_pages_blob' => null, 'update_root_called' => false, 'queries' => []];

        // Fake DB for update queries executed inside set_data
        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap) { $this->cap = $cap; }
            public function query($sql)
            {
                $this->cap->queries[] = $sql;
                // Provide structure channels data when requested
                if (stripos($sql, 'FROM exp_channels AS ec') !== false) {
                    return new eeDbResultMock([
                        [
                            'channel_id' => 7,
                            'channel_title' => 'Pages',
                            'template_id' => 2,
                            'type' => 'page',
                            'site_id' => 1,
                        ],
                    ]);
                }
                return new eeDbResultMock([]);
            }
            public function update_string($table, $data, $where)
            {
                // Capture the encoded site_pages blob
                if (isset($data['site_pages'])) {
                    $this->cap->site_pages_blob = $data['site_pages'];
                }
                return 'UPDATE';
            }
            public function delete($table, $where) { $this->cap->queries[] = 'DELETE ' . $table; }
            public function escape_str($str) { return addslashes($str); }
        });

        // sql mock to provide settings and site_pages
        $site_pages = [ 'url' => '/', 'uris' => [], 'templates' => [] ];
        $this->structure->sql = new class($site_pages, $captured) {
            private $sp; private $cap;
            public function __construct($sp, $cap) { $this->sp = $sp; $this->cap = $cap; }
            public function get_site_pages($cache_bust = false, $force = false) { return $this->sp; }
            public function get_settings() { return ['add_trailing_slash' => 'n']; }
            public function update_root_node() { $this->cap->update_root_called = true; }
            public function get_channel_listing_entries($channel) { return []; }
        };

        // Minimal nested set with newLastChild and getTree
        $this->structure->nset = new class {
            private $nodes = [];
            public function getNode($entryId) { return $this->nodes[$entryId] ?? false; }
            public function newLastChild($right, $extra) { $this->nodes[$extra['entry_id']] = ['id' => $extra['entry_id'], 'right' => $right + 1, 'listing_cid' => $extra['listing_cid'], 'parent_id' => $extra['parent_id'], 'channel_id' => $extra['channel_id'], 'hidden' => $extra['hidden'], 'template_id' => 0]; }
            public function getTree($entryId) { return []; }
            public function moveToLastChild($node, $parent) {}
        };

        // functions mock to allow get_structure_channels allowed list
        ee()->setMock('functions', new class {
            public function fetch_site_index($a = 0, $b = 0) { return '/'; }
            public function fetch_assigned_channels() { return [7]; }
        });

        ee()->config->items['site_id'] = 1;

        $data = [
            'channel_id' => 7,
            'entry_id' => 100,
            'uri' => '/new',
            'structure_uri' => 'new',
            'template_id' => 2,
            'listing_cid' => 0,
            'parent_id' => 0,
            'hidden' => 'n',
        ];

        $this->structure->set_data($data);

        // Decode captured site_pages and verify content
        $decoded = unserialize(base64_decode($captured->site_pages_blob));
        $this->assertSame('/new', $decoded[1]['uris'][100]);
        $this->assertSame(2, $decoded[1]['templates'][100]);
        $this->assertTrue($captured->update_root_called);
    }
}



