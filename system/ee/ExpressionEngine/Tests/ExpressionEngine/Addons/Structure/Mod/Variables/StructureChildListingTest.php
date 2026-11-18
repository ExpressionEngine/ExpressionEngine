<?php

require_once __DIR__ . '/../StructureTestBase.php';

class StructureChildListingTest extends StructureTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Mock extensions to avoid hook calls
        ee()->setMock('extensions', new class {
            public function active_hook($hook) { return false; }
            public function call($hook) { return null; }
        });
        // Provide uri
        ee()->setMock('uri', new class {
            public function uri_string() { return ''; }
        });
    }

    public function testReturnsListingCidByDefault()
    {
        // sql->get_data() should provide listing_cid for an entry id
        $this->structure->sql = new class {
            public function get_site_pages() { return ['uris' => []]; }
            public function get_data() { return [10 => ['listing_cid' => 55]]; }
        };

        $this->setTemplateParams(['entry_id' => 10]);

        $value = $this->structure->child_listing();
        $this->assertSame(55, $value);
    }

    public function testReturnsChannelNameWhenRequested()
    {
        $this->structure->sql = new class {
            public function get_site_pages() { return ['uris' => []]; }
            public function get_data() { return [10 => ['listing_cid' => 3]]; }
        };

        // Fake DB to return channel row
        ee()->setMock('db', new class extends FakeDb {
            public function query($sql) {
                return new class {
                    private $row;
                    public function __construct() {
                        $this->row = [
                            'channel_id' => 3,
                            'channel_name' => 'blog',
                            'channel_title' => 'Blog',
                        ];
                    }
                    public function row($column = null) {
                        if ($column !== null) {
                            return $this->row[$column] ?? null;
                        }
                        return (object) $this->row;
                    }
                };
            }
        });

        $this->setTemplateParams(['entry_id' => 10, 'show' => 'channel_name']);
        $value = $this->structure->child_listing();
        $this->assertSame('blog', $value);
    }

    public function testReturnsChannelTitleWhenRequested()
    {
        $this->structure->sql = new class {
            public function get_site_pages() { return ['uris' => []]; }
            public function get_data() { return [10 => ['listing_cid' => 8]]; }
        };

        ee()->setMock('db', new class extends FakeDb {
            public function query($sql) {
                return new class {
                    private $row;
                    public function __construct() {
                        $this->row = [
                            'channel_id' => 8,
                            'channel_name' => 'news',
                            'channel_title' => 'News',
                        ];
                    }
                    public function row($column = null) {
                        if ($column !== null) {
                            return $this->row[$column] ?? null;
                        }
                        return (object) $this->row;
                    }
                };
            }
        });

        $this->setTemplateParams(['entry_id' => 10, 'show' => 'channel_title']);
        $value = $this->structure->child_listing();
        $this->assertSame('News', $value);
    }

    public function testReturnsFalseWhenNoSitePages()
    {
        $this->structure->sql = new class {
            public function get_site_pages() { return false; }
        };
        $this->assertFalse($this->structure->child_listing());
    }

    public function testFallsBackToCurrentUriWhenEntryIdParamIsZeroString()
    {
        $this->structure->sql = new class {
            public function get_site_pages() { return ['uris' => [15 => '/current-page/']]; }
            public function get_data() { return [15 => ['listing_cid' => 42]]; }
        };

        ee()->setMock('uri', new class {
            public function uri_string() { return 'current-page'; }
        });

        $this->setTemplateParams(['entry_id' => '0']);

        $value = $this->structure->child_listing();
        $this->assertSame(42, $value);
    }

    public function testReturnsFalseWhenEntryIdCannotBeResolved()
    {
        $this->structure->sql = new class {
            public function get_site_pages() { return ['uris' => [15 => '/other-page/']]; }
            public function get_data() { return [15 => ['listing_cid' => 42]]; }
        };

        ee()->setMock('uri', new class {
            public function uri_string() { return 'missing-page'; }
        });

        $this->setTemplateParams([]);

        $this->assertFalse($this->structure->child_listing());
    }

    public function testReturnsEmptyWhenListingCidZero()
    {
        $this->structure->sql = new class {
            public function get_site_pages() { return ['uris' => []]; }
            public function get_data() { return [10 => ['listing_cid' => 0]]; }
        };
        $this->setTemplateParams(['entry_id' => 10, 'show' => 'channel_name']);
        $value = $this->structure->child_listing();
        $this->assertSame('', $value);
    }

    public function testReturnsEmptyWhenListingCidIsMissing()
    {
        $this->structure->sql = new class {
            public function get_site_pages() { return ['uris' => []]; }
            public function get_data() { return [10 => []]; }
        };

        $db = new class extends FakeDb {
            public $queryCount = 0;

            public function query($sql)
            {
                $this->queryCount++;

                return parent::query($sql);
            }
        };
        ee()->setMock('db', $db);

        $this->setTemplateParams(['entry_id' => 10, 'show' => 'channel_name']);

        $value = $this->structure->child_listing();
        $this->assertSame('', $value);
        $this->assertSame(0, $db->queryCount);
    }

    public function testUnknownShowFallsBackToListingCid()
    {
        $this->structure->sql = new class {
            public function get_site_pages() { return ['uris' => []]; }
            public function get_data() { return [10 => ['listing_cid' => 99]]; }
        };
        $this->setTemplateParams(['entry_id' => 10, 'show' => 'bogus']);
        $value = $this->structure->child_listing();
        $this->assertSame(99, $value);
    }
}

