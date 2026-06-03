<?php

require_once __DIR__ . '/../StructureTestBase.php';

class StructureParentTitleTest extends StructureTestBase
{
    public function testParentTitleReturnsFalseWhenNoSitePages()
    {
        $this->structure->sql = new class {
            public function get_site_pages() { return false; }
        };

        $this->assertFalse($this->structure->parent_title());
    }

    public function testParentTitleReturnsUnescapedSiteNameWhenNoNodeAndNoEntryId()
    {
        // parent_title() will call $this->nset->getNode($entry_id) where entry_id resolves from URI
        // Provide no site_pages uri match so $entry_id is falsy
        $this->structure->sql = new class {
            public function get_site_pages() { return ['uris' => []]; }
            public function get_uri() { return '/unmapped/'; }
        };
        ee()->config->items['site_name'] = "My Site\\'s Name";

        $title = $this->structure->parent_title();
        $this->assertSame("My Site's Name", $title);
    }

    public function testParentTitleReturnsImmediateParentTitle()
    {
        // Site pages with mapping for current entry id 3
        $sitePages = ['uris' => [2 => '/parent/', 3 => '/parent/child/']];
        // Node for current entry 3 with right value > parent
        $this->setNsetStub([
            3 => ['right' => 10],
        ]);
        // SQL stub returns site_pages and minimal join results with parent as first row
        $sql = new class($sitePages) {
            private $sitePages;
            public function __construct($sitePages) { $this->sitePages = $sitePages; }
            public function get_site_pages() { return $this->sitePages; }
            public function get_uri() { return '/parent/child/'; }
        };
        $this->structure->sql = $sql;

        // DB should return a row where index 0 is the immediate parent
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'Parent Title'],
            ['entry_id' => 1, 'title' => 'Home'],
        ]);

        $title = $this->structure->parent_title();
        $this->assertSame('Parent Title', $title);
    }

    public function testParentTitleForListingEntryUsesParentNode()
    {
        $sitePages = ['uris' => [5 => '/parent/', 9 => '/parent/listing-entry/']];
        $this->setNsetStub([
            9 => false,
            5 => ['right' => 20],
        ]);

        $this->structure->sql = new class($sitePages) {
            private $sitePages;
            public function __construct($sitePages) { $this->sitePages = $sitePages; }
            public function get_site_pages() { return $this->sitePages; }
            public function get_uri() { return '/parent/listing-entry/'; }
        };

        $db = new class extends FakeDb {
            public $queries = [];

            public function query($sql) {
                $this->queries[] = $sql;

                if (strpos($sql, 'INNER JOIN exp_channel_titles AS expt') !== false) {
                    return new eeDbResultMock([['entry_id' => 5, 'title' => 'Parent Title']]);
                }

                if (strpos($sql, 'FROM exp_channel_titles') !== false) {
                    return new eeDbResultMock([['channel_id' => 77]]);
                }

                if (strpos($sql, 'WHERE listing_cid = 77') !== false) {
                    return new eeDbResultMock([['entry_id' => 5]]);
                }

                return new eeDbResultMock([]);
            }
        };
        ee()->setMock('db', $db);

        $title = $this->structure->parent_title(9);

        $this->assertSame('Parent Title', $title);
        $this->assertStringContainsString('WHERE entry_id = 9', $db->queries[0]);
        $this->assertStringContainsString('WHERE listing_cid = 77', $db->queries[1]);
        $this->assertStringContainsString('AND node.rgt >= 20', $db->queries[2]);
    }
}

