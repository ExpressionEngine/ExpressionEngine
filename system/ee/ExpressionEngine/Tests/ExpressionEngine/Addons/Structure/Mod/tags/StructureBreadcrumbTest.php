<?php

require_once __DIR__ . '/../StructureTestBase.php';

class StructureBreadcrumbTest extends StructureTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set default template tagdata for breadcrumb tests
        $this->setTemplateTagdata('{exp:structure:breadcrumb}');
    }

    // setSqlStub now inherited from StructureTestBase

    // Helpers inherited from StructureTestBase: setNsetStub, setTemplateParams, setDbRows

    public function testBreadcrumbWithDefaultParameters()
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
            'uri' => '/about/team/leadership',
            'here_as_title' => 'yes',
            'home_link' => '/',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('<a href="/">Home</a>', $html);
        $this->assertStringContainsString('About', $html);
        $this->assertStringContainsString('Team', $html);
        $this->assertStringContainsString('Leadership', $html);
        $this->assertStringContainsString('&raquo;', $html);
    }

    public function testBreadcrumbWithoutHome()
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
            'uri' => '/about/team',
            'inc_home' => 'no',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringNotContainsString('Home', $html);
        $this->assertStringContainsString('About', $html);
        $this->assertStringContainsString('Here', $html);
    }

    public function testBreadcrumbWithCustomSeparator()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about');
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'separator' => '|',
            'here_as_title' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('|', $html);
        $this->assertStringNotContainsString('&raquo;', $html);
    }

    public function testBreadcrumbWithCustomTitles()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about', 3 => '/about/team' ] ];
        $custom = [ 2 => 'Custom About Title', 3 => 'Custom Team Title' ];
        $this->setSqlStub($sitePages, '/about/team', $custom);
        $this->setNsetStub([
            3 => ['left' => 6, 'right' => 7, 'entry_id' => 3],
        ]);
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About'],
        ]);
        $this->setTemplateParams([
            'uri' => '/about/team',
            'here_as_title' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('Custom About Title', $html);
        $this->assertStringContainsString('Custom Team Title', $html);
    }

    public function testBreadcrumbWithChannelFiltering()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about', 3 => '/about/team' ] ];
        $this->setSqlStub($sitePages, '/about/team', false, [3 => 'Team'], [2,3]);
        $this->setNsetStub([
            3 => ['left' => 6, 'right' => 7, 'entry_id' => 3],
        ]);
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About'],
        ]);
        $this->setTemplateParams([
            'uri' => '/about/team',
            'channel' => '1',
            'here_as_title' => 'yes',
        ]);
        
        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('Home', $html);
        $this->assertStringContainsString('About', $html);
        $this->assertStringContainsString('Team', $html);
    }

    public function testBreadcrumbWithListingEntry()
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
            'uri' => '/about/team',
            'entry_id' => 3,
            'here_as_title' => 'yes',
        ]);
        // Monkey-patch get_pid_for_listing_entry via Closure bind
        $ref = new ReflectionClass($this->structure);
        $method = $ref->getMethod('breadcrumb'); // ensure class is loaded
        // Provide get_pid_for_listing_entry via an anonymous wrapper that extends Structure
        $wrapper = new class($this->structure) extends Structure {
            public $inner; public function __construct($inner){ $this->inner = $inner; }
            public function __call($name, $args) { return $this->inner->$name(...$args); }
            public function get_pid_for_listing_entry($entryId) { return 2; }
        };
        // Copy public props
        $wrapper->sql = $this->structure->sql;
        $wrapper->nset = $this->structure->nset;

        $html = $wrapper->breadcrumb();
        $this->assertStringContainsString('Home', $html);
        $this->assertStringContainsString('About', $html);
        $this->assertStringContainsString('Team', $html);
    }

    public function testBreadcrumbWithNoSitePages()
    {
        $this->setSqlStub(false, '/about');
        $this->setNsetStub([]);
        $this->setTemplateParams([]);

        $html = $this->structure->breadcrumb();
        $this->assertFalse($html);
    }

    public function testBreadcrumbWithNoNodeAndNoEntryId()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/nonexistent');
        $this->setNsetStub([]);
        $this->setTemplateParams([]);

        $html = $this->structure->breadcrumb();
        $this->assertFalse($html);
    }

    public function testBreadcrumbWithWrappingElements()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about');
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'wrap_each' => 'li',
            'wrap_each_class' => 'breadcrumb-item',
            'wrap_here' => 'span',
            'here_as_title' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('<li class="breadcrumb-item">', $html);
        $this->assertStringContainsString('<span>Title 2</span>', $html);
        $this->assertStringContainsString('class="last breadcrumb-item"', $html);
    }

    public function testBreadcrumbWithEncodedTitles()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about', false, [2 => 'About & Company']);
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'here_as_title' => 'yes',
            'encode_titles' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('About &amp; Company', $html);
    }

    public function testBreadcrumbWithIncSeparatorNo()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about');
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'inc_separator' => 'no',
            'here_as_title' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringNotContainsString('&raquo;', $html);
        $this->assertStringNotContainsString('|', $html);
    }

    public function testBreadcrumbWithWrapSeparator()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about');
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'wrap_separator' => 'span',
            'here_as_title' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('<span>&raquo;</span>', $html);
    }

    public function testBreadcrumbWithWrapEachClassOnly()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about');
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'wrap_each' => 'li',
            'wrap_each_class' => 'breadcrumb-item',
            'here_as_title' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('<li class="breadcrumb-item">', $html);
        $this->assertStringContainsString('class="last breadcrumb-item"', $html);
    }

    public function testBreadcrumbWithAddLastClassNo()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about');
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'add_last_class' => 'no',
            'here_as_title' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringNotContainsString('class="last"', $html);
        $this->assertStringNotContainsString('<span class="last">', $html);
    }

    public function testBreadcrumbWithCustomHomeLink()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about');
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'home_link' => 'https://example.com',
            'here_as_title' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('<a href="https://example.com">Home</a>', $html);
    }

    public function testBreadcrumbWithCustomHomeTitle()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about');
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'rename_home' => 'Start Here',
            'here_as_title' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('<a href="">Start Here</a>', $html);
        $this->assertStringNotContainsString('Home', $html);
    }

    public function testBreadcrumbWithHereAsTitleNo()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about');
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'here_as_title' => 'no',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('Here', $html);
        $this->assertStringNotContainsString('Title 2', $html);
    }

    public function testBreadcrumbWrapsHereLabelWhenHereAsTitleIsDisabled()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about');
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'here_as_title' => 'no',
            'wrap_here' => 'strong',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('<strong>Here</strong>', $html);
        $this->assertStringNotContainsString('Title 2', $html);
    }

    public function testBreadcrumbWithWrapHereOnly()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about');
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'wrap_here' => 'strong',
            'here_as_title' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('<strong>Title 2</strong>', $html);
    }

    public function testBreadcrumbWithComplexWrapping()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about', 3 => '/about/team' ] ];
        $this->setSqlStub($sitePages, '/about/team');
        $this->setNsetStub([
            3 => ['left' => 6, 'right' => 7, 'entry_id' => 3],
        ]);
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About'],
        ]);
        $this->setTemplateParams([
            'uri' => '/about/team',
            'wrap_each' => 'div',
            'wrap_each_class' => 'crumb',
            'wrap_here' => 'em',
            'wrap_separator' => 'i',
            'here_as_title' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('<div class="crumb">', $html);
        $this->assertStringContainsString('<em>Title 3</em>', $html);
        $this->assertStringContainsString('<i>&raquo;</i>', $html);
        $this->assertStringContainsString('class="last crumb"', $html);
    }

    public function testBreadcrumbWithNoIncHere()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about');
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'inc_here' => 'no',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringNotContainsString('Here', $html);
        $this->assertStringNotContainsString('Title 2', $html);
        $this->assertStringContainsString('<a href="">Home</a>', $html);
    }

    public function testBreadcrumbDefaultsHomeEntryToZeroAndSkipsHomepageAncestorRow()
    {
        $sitePages = [ 'uris' => [ 0 => '/', 2 => '/about', 3 => '/about/team' ] ];
        $this->setSqlStub($sitePages, '/about/team', false, [3 => 'Team']);
        $this->setNsetStub([
            3 => ['left' => 6, 'right' => 7, 'entry_id' => 3],
        ]);
        $this->setDbRows([
            ['entry_id' => 0, 'title' => 'Hidden Home'],
            ['entry_id' => 2, 'title' => 'About'],
        ]);
        $this->setTemplateParams([
            'uri' => '/about/team',
            'here_as_title' => 'yes',
            'rename_home' => 'Root',
            'home_link' => '/',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('<a href="/">Root</a>', $html);
        $this->assertStringNotContainsString('Hidden Home', $html);
        $this->assertStringContainsString('About', $html);
        $this->assertStringContainsString('Team', $html);
    }

    public function testBreadcrumbWithEmptyCustomTitles()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about', []);
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'here_as_title' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('Title 2', $html);
    }

    public function testBreadcrumbWithChannelFilteringNoMatch()
    {
        $sitePages = [ 'uris' => [ 1 => '/', 2 => '/about' ] ];
        $this->setSqlStub($sitePages, '/about', false, [], []);
        $this->setNsetStub([
            2 => ['left' => 4, 'right' => 5, 'entry_id' => 2],
        ]);
        $this->setDbRows([]);
        $this->setTemplateParams([
            'uri' => '/about',
            'channel' => '1',
            'here_as_title' => 'yes',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('Home', $html);
        $this->assertStringContainsString('Title 2', $html);
    }

    public function testBreadcrumbWithDeepNesting()
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
            'uri' => '/about/team/leadership/executive',
            'here_as_title' => 'yes',
            'home_link' => '/',
        ]);

        $html = $this->structure->breadcrumb();
        $this->assertStringContainsString('<a href="/">Home</a>', $html);
        $this->assertStringContainsString('About', $html);
        $this->assertStringContainsString('Team', $html);
        $this->assertStringContainsString('Leadership', $html);
        $this->assertStringContainsString('Executive', $html);
        // Should have 4 separators (Home > About > Team > Leadership > Executive)
        $this->assertEquals(4, substr_count($html, '&raquo;'));
    }
}




