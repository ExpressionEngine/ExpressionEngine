<?php

require_once __DIR__ . '/../StructureTestBase.php';

class StructureTitleTrailTest extends StructureTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set default template tagdata for titletrail tests
        $this->setTemplateTagdata('{exp:structure:titletrail}');
        
        // Set default site name for tests
        ee()->config->items['site_name'] = 'Test Site';
    }

    protected function setSqlStub($sitePages, string $uri = '', $customTitles = false, array $entryTitleMap = [], array $channelEntries = []): void
    {
        $sql = new class($sitePages, $uri, $customTitles, $entryTitleMap, $channelEntries) {
            private $sitePages; 
            private $uri; 
            private $customTitles; 
            private $entryTitleMap; 
            private $channelEntries;
            
            public function __construct($sitePages, $uri, $customTitles, $entryTitleMap, $channelEntries) {
                $this->sitePages = $sitePages; 
                $this->uri = $uri; 
                $this->customTitles = $customTitles; 
                $this->entryTitleMap = $entryTitleMap; 
                $this->channelEntries = $channelEntries;
            }
            
            public function get_site_pages() { return $this->sitePages; }
            public function get_uri() { return $this->uri; }
            public function create_custom_titles($flag = false) { return $this->customTitles; }
            public function get_entry_title($entryId) { return $this->entryTitleMap[$entryId] ?? 'Title ' . $entryId; }
            public function get_entries_by_channel($channelId) { return $this->channelEntries; }
        };
        $this->structure->sql = $sql;
    }

    protected function setQueryCapturingDb(array $rows): object
    {
        $db = new class($rows) extends FakeDb {
            public $capturedQueries = [];

            public function __construct(array $rows)
            {
                $this->setRows($rows);
            }

            public function query($sql)
            {
                $this->capturedQueries[] = $sql;

                return new eeDbResultMock($this->rows);
            }
        };

        $this->setMock('db', $db);

        return $db;
    }

    // Helpers inherited from StructureTestBase: setNsetStub, setTemplateParams, setDbRows

    public function testTitleTrailWithDefaultParameters()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about', 3 => '/about/team', 4 => '/about/team/leadership' ] ];
        $this->setSqlStub($sitePages, '/about/team/leadership', false, [4 => 'Leadership']);
        $this->setNsetStub([
            4 => ['left' => 8, 'right' => 9, 'entry_id' => 4],
        ]);
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About'],
            ['entry_id' => 3, 'title' => 'Team'],
        ]);
        $this->setTemplateParams([
            'entry_id' => 4,
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('Leadership | About | Team', $result);
    }

    public function testTitleTrailWithoutEntryId()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about', 3 => '/about/team' ] ];
        $this->setSqlStub($sitePages, '/about/team', false, [3 => 'Team']);
        $this->setNsetStub([
            3 => ['left' => 6, 'right' => 7, 'entry_id' => 3],
        ]);
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About'],
        ]);
        $this->setTemplateParams([]);

        $result = $this->structure->titletrail();
        $this->assertEquals('Team | About', $result);
    }

    public function testTitleTrailWithCustomSeparator()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about', false, [2 => 'About']);
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'entry_id' => 2,
            'separator' => '>',
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('About', $result);
    }

    public function testTitleTrailWithCustomTitles()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about', 3 => '/about/team' ] ];
        $customTitles = [ 2 => 'Custom About Title', 3 => 'Custom Team Title' ];
        $this->setSqlStub($sitePages, '/about/team', $customTitles, [3 => 'Team']);
        $this->setNsetStub([
            3 => ['left' => 6, 'right' => 7, 'entry_id' => 3],
        ]);
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About'],
        ]);
        $this->setTemplateParams([
            'entry_id' => 3,
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('Custom Team Title | Custom About Title', $result);
    }

    public function testTitleTrailWithReverseOrder()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about', 3 => '/about/team' ] ];
        $this->setSqlStub($sitePages, '/about/team', false, [3 => 'Team']);
        $this->setNsetStub([
            3 => ['left' => 6, 'right' => 7, 'entry_id' => 3],
        ]);
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About'],
        ]);
        $this->setTemplateParams([
            'entry_id' => 3,
            'reverse' => 'yes',
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('About | Team', $result);
    }

    public function testTitleTrailWithSiteName()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about', false, [2 => 'About']);
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'entry_id' => 2,
            'site_name' => 'yes',
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('About | Test Site', $result);
    }

    public function testTitleTrailWithSiteNameAndReverse()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about', false, [2 => 'About']);
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'entry_id' => 2,
            'site_name' => 'yes',
            'reverse' => 'yes',
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('Test Site | About', $result);
    }

    public function testTitleTrailWithListingEntry()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about', 3 => '/about/team' ] ];
        $this->setSqlStub($sitePages, '/about/team', false, [3 => 'Team']);
        // No node for 3 to simulate listing entry, but parent 2 exists
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 7, 'entry_id' => 2],
        ]);
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About'],
        ]);
        $this->setTemplateParams([
            'entry_id' => 3,
        ]);
        
        // Monkey-patch get_pid_for_listing_entry via Closure bind
        $ref = new ReflectionClass($this->structure);
        $method = $ref->getMethod('titletrail'); // ensure class is loaded
        // Provide get_pid_for_listing_entry via an anonymous wrapper that extends Structure
        $wrapper = new class($this->structure) extends Structure {
            public $inner; 
            public function __construct($inner){ $this->inner = $inner; }
            public function __call($name, $args) { return $this->inner->$name(...$args); }
            public function get_pid_for_listing_entry($entryId) { return 2; }
        };
        // Copy public props
        $wrapper->sql = $this->structure->sql;
        $wrapper->nset = $this->structure->nset;

        $result = $wrapper->titletrail();
        $this->assertEquals('Team | About', $result);
    }

    public function testTitleTrailBuildsStrictAncestorQueryForRegularEntries()
    {
        ee()->config->items['site_id'] = 7;

        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about', 3 => '/about/team' ] ];
        $this->setSqlStub($sitePages, '/about/team', false, [3 => 'Team']);
        $this->setNsetStub([
            3 => ['left' => 6, 'right' => 7, 'entry_id' => 3],
        ]);
        $db = $this->setQueryCapturingDb([
            ['entry_id' => 2, 'title' => 'About'],
        ]);
        $this->setTemplateParams([
            'entry_id' => 3,
        ]);

        $this->structure->titletrail();

        $this->assertCount(1, $db->capturedQueries);
        $this->assertStringContainsString('AND node.lft < 7', $db->capturedQueries[0]);
        $this->assertStringContainsString('AND node.rgt > 7', $db->capturedQueries[0]);
        $this->assertStringNotContainsString('AND node.rgt >= 7', $db->capturedQueries[0]);
        $this->assertStringContainsString('AND expt.site_id = 7', $db->capturedQueries[0]);
        $this->assertStringContainsString('AND node.lft != 2', $db->capturedQueries[0]);
    }

    public function testTitleTrailBuildsInclusiveAncestorQueryForListingEntries()
    {
        ee()->config->items['site_id'] = 7;

        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about', 3 => '/about/team' ] ];
        $this->setSqlStub($sitePages, '/about/team', false, [3 => 'Team']);
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 7, 'entry_id' => 2],
        ]);
        $db = $this->setQueryCapturingDb([
            ['entry_id' => 2, 'title' => 'About'],
        ]);
        $this->setTemplateParams([
            'entry_id' => 3,
        ]);

        $wrapper = new class($this->structure) extends Structure {
            public $inner;

            public function __construct($inner)
            {
                $this->inner = $inner;
            }

            public function __call($name, $args)
            {
                return $this->inner->$name(...$args);
            }

            public function get_pid_for_listing_entry($entryId)
            {
                return 2;
            }
        };
        $wrapper->sql = $this->structure->sql;
        $wrapper->nset = $this->structure->nset;

        $wrapper->titletrail();

        $this->assertCount(1, $db->capturedQueries);
        $this->assertStringContainsString('AND node.lft < 7', $db->capturedQueries[0]);
        $this->assertStringContainsString('AND node.rgt >= 7', $db->capturedQueries[0]);
        $this->assertStringNotContainsString('AND node.rgt > 7', $db->capturedQueries[0]);
        $this->assertStringContainsString('AND expt.site_id = 7', $db->capturedQueries[0]);
    }

    public function testTitleTrailWithNoSitePages()
    {
        $this->setSqlStub(false, '/about');
        $this->setNsetStub([]);
        $this->setTemplateParams([]);

        $result = $this->structure->titletrail();
        $this->assertFalse($result);
    }

    public function testTitleTrailWithNoNodeAndNoEntryId()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/nonexistent');
        $this->setNsetStub([]);
        $this->setTemplateParams([]);

        $result = $this->structure->titletrail();
        $this->assertEquals('Test Site', $result);
    }

    public function testTitleTrailWithDeepNesting()
    {
        $sitePages = [ 'uris' => [ 
            1 => '/', 
            2 => '/about', 
            3 => '/about/team', 
            4 => '/about/team/leadership',
            5 => '/about/team/leadership/executive'
        ]];
        $this->setSqlStub($sitePages, '/about/team/leadership/executive', false, [5 => 'Executive']);
        $this->setNsetStub([
            5 => ['left' => 10, 'right' => 11, 'entry_id' => 5],
        ]);
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About'],
            ['entry_id' => 3, 'title' => 'Team'],
            ['entry_id' => 4, 'title' => 'Leadership'],
        ]);
        $this->setTemplateParams([
            'entry_id' => 5,
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('Executive | About | Team | Leadership', $result);
    }

    public function testTitleTrailWithEncodedTitles()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about', false, [2 => 'About & Company']);
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'entry_id' => 2,
            'encode_titles' => 'yes',
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('About &amp; Company', $result);
    }

    public function testTitleTrailWithEncodedTitlesDisabled()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about', false, [2 => 'About & Company']);
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'entry_id' => 2,
            'encode_titles' => 'no',
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('About & Company', $result);
    }

    public function testTitleTrailWithEmptyCustomTitles()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about', [], [2 => 'About']);
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'entry_id' => 2,
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('About', $result);
    }

    public function testTitleTrailWithSingleLevel()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about', false, [2 => 'About']);
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'entry_id' => 2,
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('About', $result);
    }

    public function testTitleTrailWithTwoLevels()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about', 3 => '/about/team' ] ];
        $this->setSqlStub($sitePages, '/about/team', false, [3 => 'Team']);
        $this->setNsetStub([
            3 => ['left' => 6, 'right' => 7, 'entry_id' => 3],
        ]);
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About'],
        ]);
        $this->setTemplateParams([
            'entry_id' => 3,
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('Team | About', $result);
    }

    public function testTitleTrailWithCustomSeparatorAndSpaces()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about', false, [2 => 'About']);
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'entry_id' => 2,
            'separator' => '->',
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('About', $result);
    }

    public function testTitleTrailWithComplexNestingAndAllOptions()
    {
        $sitePages = [ 'uris' => [ 
            1 => '/', 
            2 => '/about', 
            3 => '/about/team', 
            4 => '/about/team/leadership'
        ]];
        $customTitles = [ 2 => 'About Us', 3 => 'Our Team', 4 => 'Leadership Team' ];
        $this->setSqlStub($sitePages, '/about/team/leadership', $customTitles, [4 => 'Leadership']);
        $this->setNsetStub([
            4 => ['left' => 8, 'right' => 9, 'entry_id' => 4],
        ]);
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About'],
            ['entry_id' => 3, 'title' => 'Team'],
        ]);
        $this->setTemplateParams([
            'entry_id' => 4,
            'separator' => '>',
            'site_name' => 'yes',
            'reverse' => 'yes',
        ]);

        $result = $this->structure->titletrail();
        $this->assertEquals('Test Site > Our Team > About Us > Leadership Team', $result);
    }
}

