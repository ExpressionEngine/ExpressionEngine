<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureSetDataBranchFixture extends Structure
{
    public $changedValue = true;
    public $setListingsCalls = [];
    public $setSitePagesCalls = [];

    public function __construct()
    {
    }

    public function has_changed($node, $data)
    {
        return $this->changedValue;
    }

    public function get_structure_channels($type = '', $channel_id = '', $order = '', $allowed = false)
    {
        return [
            7 => ['channel_id' => 7, 'type' => 'page', 'template_id' => 2],
            9 => ['channel_id' => 9, 'type' => 'listing', 'template_id' => 5],
        ];
    }

    public function create_full_uri($parent_uri, $uri)
    {
        return rtrim($parent_uri, '/') . '/' . trim($uri, '/') . '/';
    }

    public function set_listings($listing_data)
    {
        $this->setListingsCalls[] = $listing_data;
    }

    public function set_site_pages($site_id, $site_pages)
    {
        $this->setSitePagesCalls[] = [$site_id, $site_pages];
    }
}

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

    public function testSetDataCoversChangedTrueListingBranchWithHooks()
    {
        $captured = (object) ['queries' => [], 'rootUpdated' => 0];

        ee()->setMock('extensions', new class {
            public function active_hook($hook)
            {
                return in_array($hook, ['structure_listing_parent', 'structure_before_save_listing'], true);
            }
            public function call($hook, ...$args)
            {
                if ($hook === 'structure_listing_parent') {
                    return 10;
                }
                if ($hook === 'structure_before_save_listing') {
                    $listing = $args[0];
                    $listing['uri'] = 'hooked-' . $listing['uri'];
                    return $listing;
                }
                return null;
            }
        });
        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap)
            {
                $this->cap = $cap;
            }
            public function query($sql)
            {
                $this->cap->queries[] = $sql;
                if (strpos($sql, 'SELECT listing_cid FROM exp_structure WHERE entry_id = 100') !== false) {
                    return new eeDbResultMock([['listing_cid' => 0]]);
                }
                if (strpos($sql, 'SELECT entry_id, url_title FROM exp_channel_titles WHERE channel_id = 9') !== false) {
                    return new eeDbResultMock([
                        ['entry_id' => 201, 'url_title' => 'alpha'],
                        ['entry_id' => 202, 'url_title' => 'beta'],
                    ]);
                }
                return new eeDbResultMock([]);
            }
            public function escape_str($str)
            {
                return addslashes($str);
            }
        });

        $structure = new StructureSetDataBranchFixture();
        $structure->changedValue = true;
        $structure->sql = new class($captured) {
            private $cap;
            public function __construct($cap)
            {
                $this->cap = $cap;
            }
            public function get_site_pages($cache_bust = false, $force = false)
            {
                return [
                    'url' => '/',
                    'uris' => [10 => '/parent/', 100 => '/old/'],
                    'templates' => [100 => 2],
                ];
            }
            public function get_channel_listing_entries($channel)
            {
                return [
                    201 => ['template_id' => 8, 'uri' => 'custom-alpha'],
                ];
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function update_root_node()
            {
                $this->cap->rootUpdated++;
            }
        };
        $structure->nset = new class {
            public function getNode($entryId)
            {
                if ($entryId === 100) {
                    return ['id' => 100, 'right' => 3, 'listing_cid' => 0];
                }
                if ($entryId === 10) {
                    return ['id' => 10, 'right' => 2];
                }
                return false;
            }
            public function newLastChild($right, $extra)
            {
            }
            public function getTree($entryId)
            {
                return [];
            }
            public function moveToLastChild($node, $parent)
            {
            }
        };

        $data = [
            'channel_id' => 7,
            'entry_id' => 100,
            'uri' => '/new',
            'structure_uri' => 'new',
            'template_id' => 2,
            'listing_cid' => 9,
            'parent_id' => 10,
            'hidden' => 'n',
        ];

        $structure->set_data($data);

        $this->assertCount(1, $structure->setListingsCalls);
        $this->assertSame('hooked-custom-alpha', $structure->setListingsCalls[0][0]['uri']);
        $this->assertSame('hooked-beta', $structure->setListingsCalls[0][1]['uri']);
        $this->assertSame(1, $captured->rootUpdated);
        $this->assertNotEmpty($structure->setSitePagesCalls);
    }

    public function testSetDataCoversParentMoveAndLocalTreeAdjustments()
    {
        $captured = (object) ['moved' => 0, 'queries' => [], 'rootUpdated' => 0];

        ee()->setMock('extensions', new class {
            public function active_hook($hook)
            {
                return false;
            }
            public function call($hook, ...$args)
            {
                return null;
            }
        });
        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap)
            {
                $this->cap = $cap;
            }
            public function query($sql)
            {
                $this->cap->queries[] = $sql;
                if (strpos($sql, 'SELECT listing_cid FROM exp_structure WHERE entry_id = 100') !== false) {
                    return new eeDbResultMock([['listing_cid' => 0]]);
                }
                if (strpos($sql, 'SELECT entry_id FROM exp_structure_listings WHERE parent_id IN (100,101)') !== false) {
                    return new eeDbResultMock([['entry_id' => 201]]);
                }
                return new eeDbResultMock([]);
            }
            public function escape_str($str)
            {
                return addslashes($str);
            }
        });

        $structure = new StructureSetDataBranchFixture();
        $structure->changedValue = 'parent';
        $structure->sql = new class($captured) {
            private $cap;
            public function __construct($cap)
            {
                $this->cap = $cap;
            }
            public function get_site_pages($cache_bust = false, $force = false)
            {
                return [
                    'url' => '/',
                    'uris' => [
                        10 => '/parent/',
                        100 => '/old/path/',
                        101 => '/old/path/child/',
                        201 => '/old/path/listing/',
                    ],
                    'templates' => [100 => 2, 101 => 3, 201 => 4],
                ];
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'n'];
            }
            public function update_root_node()
            {
                $this->cap->rootUpdated++;
            }
            public function get_channel_listing_entries($channel)
            {
                return [];
            }
        };
        $structure->nset = new class($captured) {
            private $cap;
            public function __construct($cap)
            {
                $this->cap = $cap;
            }
            public function getNode($entryId)
            {
                if ($entryId === 100) {
                    return ['id' => 100, 'right' => 5, 'listing_cid' => 0];
                }
                if ($entryId === 10) {
                    return ['id' => 10, 'right' => 2];
                }
                return false;
            }
            public function newLastChild($right, $extra)
            {
            }
            public function getTree($entryId)
            {
                return [
                    ['entry_id' => 100],
                    ['entry_id' => 101],
                ];
            }
            public function moveToLastChild($node, $parent)
            {
                $this->cap->moved++;
            }
        };

        $data = [
            'channel_id' => 7,
            'entry_id' => 100,
            'uri' => '/new-path',
            'structure_uri' => 'new-path',
            'template_id' => 2,
            'listing_cid' => 0,
            'parent_id' => 10,
            'hidden' => 'n',
        ];

        $structure->set_data($data);

        $sitePages = $structure->setSitePagesCalls[0][1];
        $this->assertSame('/new-path', $sitePages['uris'][100]);
        $this->assertStringContainsString('/new-path/', $sitePages['uris'][101]);
        $this->assertStringContainsString('/new-path/', $sitePages['uris'][201]);
        $this->assertSame(1, $captured->moved);
    }

    public function testSetDataCoversHiddenAndUnmanagedListingRemovalPaths()
    {
        $captured = (object) ['deleted' => [], 'queries' => []];

        ee()->setMock('extensions', new class {
            public function active_hook($hook)
            {
                return false;
            }
            public function call($hook, ...$args)
            {
                return null;
            }
        });
        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap)
            {
                $this->cap = $cap;
            }
            public function query($sql)
            {
                $this->cap->queries[] = $sql;
                if (strpos($sql, 'SELECT listing_cid FROM exp_structure WHERE entry_id = 100') !== false) {
                    return new eeDbResultMock([['listing_cid' => 9]]);
                }
                if (strpos($sql, 'SELECT * FROM exp_channel_titles WHERE channel_id = 9') !== false) {
                    return new eeDbResultMock([
                        ['entry_id' => 301],
                        ['entry_id' => 302],
                    ]);
                }
                if (strpos($sql, 'SELECT entry_id FROM exp_channel_titles WHERE channel_id = 9') !== false) {
                    return new eeDbResultMock([
                        ['entry_id' => 301],
                    ]);
                }
                return new eeDbResultMock([]);
            }
            public function delete($table, $where = null)
            {
                $this->cap->deleted[] = [$table, $where];
                return true;
            }
            public function escape_str($str)
            {
                return addslashes($str);
            }
        });

        $structure = new StructureSetDataBranchFixture();
        $structure->changedValue = 'hidden';
        $structure->sql = new class {
            public function get_site_pages($cache_bust = false, $force = false)
            {
                return [
                    'url' => '/',
                    'uris' => [
                        10 => '/parent/',
                        100 => '/',
                        101 => '/child/',
                        301 => '/listing-one/',
                        302 => '/listing-two/',
                    ],
                    'templates' => [100 => 2, 301 => 3, 302 => 4],
                ];
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'n'];
            }
            public function update_root_node()
            {
            }
            public function get_channel_listing_entries($channel)
            {
                return [];
            }
        };
        $structure->nset = new class {
            public function getNode($entryId)
            {
                if ($entryId === 100) {
                    return ['id' => 100, 'right' => 3, 'listing_cid' => 0];
                }
                if ($entryId === 10) {
                    return ['id' => 10, 'right' => 2];
                }
                return false;
            }
            public function newLastChild($right, $extra)
            {
            }
            public function getTree($entryId)
            {
                return [
                    ['entry_id' => 100],
                    ['entry_id' => 101],
                ];
            }
            public function moveToLastChild($node, $parent)
            {
            }
        };

        $data = [
            'channel_id' => 7,
            'entry_id' => 100,
            'uri' => '/new-root',
            'structure_uri' => 'new-root',
            'template_id' => 2,
            'listing_cid' => 0,
            'parent_id' => 10,
            'hidden' => 'y',
        ];

        $structure->set_data($data);

        $this->assertNotEmpty($captured->deleted);
        $queries = implode("\n", $captured->queries);
        $this->assertStringContainsString("UPDATE exp_structure SET hidden = 'y' WHERE entry_id = 100", $queries);
    }

    public function testSetDataCoversUnchangedNodeListingCidBranch()
    {
        ee()->setMock('extensions', new class {
            public function active_hook($hook)
            {
                return false;
            }
            public function call($hook, ...$args)
            {
                return null;
            }
        });
        ee()->setMock('db', new class extends FakeDb {
            public function query($sql)
            {
                if (strpos($sql, 'SELECT entry_id, url_title FROM exp_channel_titles WHERE channel_id = 9') !== false) {
                    return new eeDbResultMock([
                        ['entry_id' => 501, 'url_title' => 'first'],
                    ]);
                }
                return new eeDbResultMock([]);
            }
            public function escape_str($str)
            {
                return addslashes($str);
            }
        });

        $structure = new StructureSetDataBranchFixture();
        $structure->changedValue = false;
        $structure->sql = new class {
            public function get_site_pages($cache_bust = false, $force = false)
            {
                return [
                    'url' => '/',
                    'uris' => [100 => '/root/'],
                    'templates' => [100 => 2],
                ];
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'n'];
            }
            public function update_root_node()
            {
            }
            public function get_channel_listing_entries($channel)
            {
                return [];
            }
        };
        $structure->nset = new class {
            public function getNode($entryId)
            {
                if ($entryId === 100) {
                    return ['id' => 100, 'right' => 3, 'listing_cid' => 9];
                }
                return false;
            }
            public function newLastChild($right, $extra)
            {
            }
            public function getTree($entryId)
            {
                return [];
            }
            public function moveToLastChild($node, $parent)
            {
            }
        };

        $data = [
            'channel_id' => 7,
            'entry_id' => 100,
            'uri' => '/unchanged',
            'structure_uri' => 'unchanged',
            'template_id' => 2,
            'listing_cid' => 9,
            'parent_id' => 0,
            'hidden' => 'n',
        ];

        $structure->set_data($data);

        $this->assertNotEmpty($structure->setListingsCalls);
        $this->assertSame(501, $structure->setListingsCalls[0][0]['entry_id']);
    }
}
