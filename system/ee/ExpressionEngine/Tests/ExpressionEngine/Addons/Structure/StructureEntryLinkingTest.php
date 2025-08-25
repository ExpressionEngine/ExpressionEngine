<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureEntryLinkingTest extends StructureTestBase
{
    private function setTemplateForEntryLinking(string $tagdata): void
    {
        ee()->setMock('TMPL', new class($tagdata) extends FakeTemplate {
            public $var_single;
            public function __construct($tagdata)
            {
                $this->tagdata = $tagdata;
                $this->var_single = [
                    'linking_title' => 'linking_title',
                    'linking_page_url' => 'linking_page_url',
                ];
            }
            public function swap_var_single($var, $val, $html)
            {
                return str_replace('{' . $var . '}', $val, $html);
            }
        });
    }

    private function setDbForOrder(string $order): void
    {
        $rowsAsc = [
            ['entry_id' => 200, 'title' => 'Prev'],
            ['entry_id' => 201, 'title' => 'Current'],
            ['entry_id' => 202, 'title' => 'Next'],
        ];
        ee()->setMock('db', new class($rowsAsc) extends FakeDb {
            private $rowsAsc;
            public function __construct($rowsAsc) { $this->rowsAsc = $rowsAsc; }
            public function query($sql)
            {
                // When Structure queries exp_channel_titles for channel_id in get_pid_for_listing_entry
                if (stripos($sql, 'FROM exp_channel_titles') !== false && stripos($sql, 'WHERE entry_id') !== false) {
                    return new class {
                        public function row($column = null) { return ($column === 'channel_id') ? 9 : null; }
                    };
                }
                // When Structure queries exp_structure for listing parent entry
                if (stripos($sql, 'FROM exp_structure') !== false && stripos($sql, 'WHERE listing_cid') !== false) {
                    return new class {
                        public function row($column = null) { return ($column === 'entry_id') ? 100 : null; }
                    };
                }
                $useDesc = stripos($sql, 'DESC') !== false;
                $rows = $useDesc ? array_reverse($this->rowsAsc) : $this->rowsAsc;
                return new class($rows) {
                    private $rows;
                    public $num_rows;
                    public function __construct($rows) { $this->rows = $rows; $this->num_rows = count($rows); }
                    public function result_array() { return $this->rows; }
                };
            }
        });
    }

    private function setSiteAndNset(): void
    {
        $this->structure->site_pages = [
            'url' => '/',
            'uris' => [
                200 => '/parent/prev',
                201 => '/parent/item',
                202 => '/parent/next',
            ],
        ];

        $sitePages = $this->structure->site_pages;
        $this->structure->sql = new class($sitePages) {
            private $sp;
            public function __construct($sp) { $this->sp = $sp; }
            public function get_uri() { return '/parent/item'; }
            public function get_site_pages() { return $this->sp; }
        };

        // Node for current entry with listing channel id
        $this->structure->nset = new class {
            public function getNode($entryId)
            {
                if ($entryId == 201) {
                    return [
                        'listing_cid' => 9,
                        'parent_id' => 100,
                    ];
                }
                return false;
            }
        };
    }

    public function testEntryLinkingNextReplacesTemplateVariables()
    {
        $this->setTemplateForEntryLinking('Next up: {linking_title} ({linking_page_url})');
        $this->setSiteAndNset();
        $this->setDbForOrder('ASC');

        $this->setTemplateParams(['type' => 'next']);
        $out = $this->structure->entry_linking();

        $this->assertSame('Next up: Next (/parent/next)', $out);
    }

    public function testEntryLinkingPreviousReplacesTemplateVariables()
    {
        $this->setTemplateForEntryLinking('Previous: {linking_title} ({linking_page_url})');
        $this->setSiteAndNset();
        $this->setDbForOrder('DESC');

        $this->setTemplateParams(['type' => 'previous']);
        $out = $this->structure->entry_linking();

        // In DESC order, the next element after Current is Prev
        $this->assertSame('Previous: Prev (/parent/prev)', $out);
    }

    public function testInvalidTypeReturnsEmptyString()
    {
        $this->setTemplateForEntryLinking('X');
        $this->setSiteAndNset();
        $this->setDbForOrder('ASC');
        $this->setTemplateParams(['type' => 'bogus']);
        $out = $this->structure->entry_linking();
        $this->assertSame('', $out);
    }

    public function testNextReturnsEmptyWhenAtEndOfListing()
    {
        $this->setTemplateForEntryLinking('{linking_title}');
        $this->setSiteAndNset();
        // Current is the last item (entry_id 202)
        $sitePages = $this->structure->site_pages;
        $this->structure->sql = new class($sitePages) {
            private $sp; public function __construct($sp){$this->sp=$sp;}
            public function get_uri(){ return '/parent/next'; }
            public function get_site_pages(){ return $this->sp; }
        };
        // Override nset to return listing_cid for entry 202 as well
        $this->structure->nset = new class {
            public function getNode($entryId)
            {
                if ($entryId == 200 || $entryId == 201 || $entryId == 202) {
                    return ['listing_cid' => 9, 'parent_id' => 100];
                }
                return false;
            }
        };
        $this->setDbForOrder('ASC');
        $this->setTemplateParams(['type' => 'next']);
        $out = $this->structure->entry_linking();
        $this->assertSame('', $out);
    }

    public function testPreviousReturnsEmptyWhenAtStartOfListing()
    {
        $this->setTemplateForEntryLinking('{linking_title}');
        $this->setSiteAndNset();
        // Make current be the first item by adjusting order to DESC and uri
        $sitePages = $this->structure->site_pages;
        $this->structure->sql = new class($sitePages) {
            private $sp; public function __construct($sp){$this->sp=$sp;}
            public function get_uri(){ return '/parent/prev'; }
            public function get_site_pages(){ return $this->sp; }
        };
        // Ensure nset returns a node for current entry id
        $this->structure->nset = new class {
            public function getNode($entryId)
            {
                if ($entryId == 200) { return ['listing_cid' => 9, 'parent_id' => 100]; }
                return false;
            }
        };
        $this->setDbForOrder('DESC');
        $this->setTemplateParams(['type' => 'previous']);
        $out = $this->structure->entry_linking();
        $this->assertSame('', $out);
    }

    public function testReturnsEmptyWhenNotListingChannel()
    {
        $this->setTemplateForEntryLinking('{linking_title}');
        // nset node with listing_cid 0
        $this->structure->site_pages = [ 'url' => '/', 'uris' => [201 => '/parent/item'] ];
        $sitePages = $this->structure->site_pages;
        $this->structure->sql = new class($sitePages) {
            private $sp; public function __construct($sp){$this->sp=$sp;}
            public function get_uri(){ return '/parent/item'; }
            public function get_site_pages(){ return $this->sp; }
        };
        $this->structure->nset = new class {
            public function getNode($entryId){ return ['listing_cid' => 0, 'parent_id' => 0]; }
        };
        $this->setDbForOrder('ASC');
        $this->setTemplateParams(['type' => 'next']);
        $out = $this->structure->entry_linking();
        $this->assertSame('', $out);
    }
}



