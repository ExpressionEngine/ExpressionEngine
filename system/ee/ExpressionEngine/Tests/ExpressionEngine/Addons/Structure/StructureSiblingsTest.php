<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureSiblingsTest extends StructureTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set default template tagdata
        $this->setTemplateTagdata('{prev_title}|{next_title}');
    }

    protected function setSqlStubWithSiblings($sitePages, string $uri, $customTitles = false, $parentId = 0, $selectiveData = []): void
    {
        $sql = new class($sitePages, $uri, $customTitles, $parentId, $selectiveData) {
            private $sitePages; 
            private $uri; 
            private $customTitles; 
            private $parentId;
            private $selectiveData;
            
            public function __construct($sitePages, $uri, $customTitles, $parentId, $selectiveData) {
                $this->sitePages = $sitePages; 
                $this->uri = $uri; 
                $this->customTitles = $customTitles; 
                $this->parentId = $parentId;
                $this->selectiveData = $selectiveData;
            }
            
            public function get_site_pages() { return $this->sitePages; }
            public function get_uri() { return $this->uri; }
            public function create_custom_titles($flag = false) { return $this->customTitles; }
            public function get_parent_id($entryId) { return $this->parentId; }
            public function get_home_page_id() { return 1; }
            public function get_selective_data($siteId, $entryId, $parentId, $type, $depth, $limit, $status, $include, $exclude, $showExpired, $showFuture, $showExpired2, $showFuture2) {
                return $this->selectiveData;
            }
        };
        $this->structure->sql = $sql;
    }

    public function testSiblingsWithPreviousAndNext()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team', 4 => '/contact']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            4 => ['entry_id' => 4, 'title' => 'Contact', 'uri' => '/contact', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/team', false, 0, $selectiveData);
        $this->setTemplateParams(['entry_id' => 3]);

        $result = $this->structure->siblings();
        
        $this->assertStringContainsString('About', $result);
        $this->assertStringContainsString('Contact', $result);
        $this->assertStringContainsString('|', $result);
    }

    public function testSiblingsWithOnlyPrevious()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/team', false, 0, $selectiveData);
        $this->setTemplateParams(['entry_id' => 3]);

        $result = $this->structure->siblings();
        
        $this->assertStringContainsString('About', $result);
        $this->assertStringNotContainsString('Contact', $result);
        $this->assertStringContainsString('|', $result);
    }

    public function testSiblingsWithOnlyNext()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/about', false, 0, $selectiveData);
        $this->setTemplateParams(['entry_id' => 2]);

        $result = $this->structure->siblings();
        
        $this->assertStringNotContainsString('About', $result);
        $this->assertStringContainsString('Team', $result);
        $this->assertStringContainsString('|', $result);
    }

    public function testSiblingsWithNoSiblings()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/about', false, 0, $selectiveData);
        $this->setTemplateParams(['entry_id' => 2]);

        $result = $this->structure->siblings();
        
        $this->assertStringNotContainsString('About', $result);
        $this->assertStringContainsString('|', $result);
    }

    public function testSiblingsWithCustomTitles()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team', 4 => '/contact']];
        $customTitles = [2 => 'Custom About', 3 => 'Custom Team', 4 => 'Custom Contact'];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            4 => ['entry_id' => 4, 'title' => 'Contact', 'uri' => '/contact', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/team', $customTitles, 0, $selectiveData);
        $this->setTemplateParams(['entry_id' => 3]);

        $result = $this->structure->siblings();
        
        $this->assertStringContainsString('Custom About', $result);
        $this->assertStringContainsString('Custom Contact', $result);
        // The original title might still appear in the template if not replaced
        $this->assertStringContainsString('|', $result);
    }

    public function testSiblingsWithDifferentDepths()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/about/team', 4 => '/contact']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/about/team', 'parent_id' => 2, 'channel_id' => 1, 'status' => 'open', 'depth' => 2],
            4 => ['entry_id' => 4, 'title' => 'Contact', 'uri' => '/contact', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/about/team', false, 2, $selectiveData);
        $this->setTemplateParams(['entry_id' => 3]);

        $result = $this->structure->siblings();
        
        // Should not include About (different depth) or Contact (different parent)
        $this->assertStringNotContainsString('About', $result);
        $this->assertStringNotContainsString('Contact', $result);
        $this->assertStringContainsString('|', $result);
    }

    public function testSiblingsWithParentIdOverride()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team']];
        // The method expects the selective data to be ordered in a way that allows it to find neighbors
        // Let's provide data in the order it expects
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/team', false, 5, $selectiveData);
        $this->setTemplateParams(['entry_id' => 3]);

        $result = $this->structure->siblings();
        
        // Should still work even with different parent_id
        $this->assertStringContainsString('About', $result);
        // The method might not find a next sibling if it's the last in the array
        $this->assertStringContainsString('|', $result);
    }

    public function testSiblingsWithHomePageParent()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/team', false, 1, $selectiveData);
        $this->setTemplateParams(['entry_id' => 3]);

        $result = $this->structure->siblings();
        
        // Should treat home page parent as 0
        $this->assertStringContainsString('About', $result);
        // The method might not find a next sibling if it's the last in the array
        $this->assertStringContainsString('|', $result);
    }

    public function testSiblingsWithNoSelectiveData()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about']];
        $this->setSqlStubWithSiblings($sitePages, '/about', false, 0, []);
        $this->setTemplateParams(['entry_id' => 2]);

        $result = $this->structure->siblings();
        
        $this->assertNull($result);
    }

    public function testSiblingsWithEmptySelectiveData()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about']];
        $this->setSqlStubWithSiblings($sitePages, '/about', false, 0, []);
        $this->setTemplateParams(['entry_id' => 2]);

        $result = $this->structure->siblings();
        
        $this->assertNull($result);
    }

    public function testSiblingsWithNoEntryIdParameter()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/team', false, 0, $selectiveData);
        // No entry_id parameter, should use URI lookup

        $result = $this->structure->siblings();
        
        $this->assertStringContainsString('About', $result);
        // The method might not find a next sibling if it's the last in the array
        $this->assertStringContainsString('|', $result);
    }

    public function testSiblingsWithComplexTemplate()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team', 4 => '/contact']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            4 => ['entry_id' => 4, 'title' => 'Contact', 'uri' => '/contact', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/team', false, 0, $selectiveData);
        $this->setTemplateParams(['entry_id' => 3]);
        $this->setTemplateTagdata('Previous: {prev_title} ({prev_url}) | Next: {next_title} ({next_url})');

        $result = $this->structure->siblings();
        
        $this->assertStringContainsString('Previous: About (/about)', $result);
        $this->assertStringContainsString('Next: Contact (/contact)', $result);
    }

    public function testSiblingsWithMultipleSiblings()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team', 4 => '/contact', 5 => '/blog']];
        // The selective data needs to be ordered by the array keys to match the logic
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            4 => ['entry_id' => 4, 'title' => 'Contact', 'uri' => '/contact', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            5 => ['entry_id' => 5, 'title' => 'Blog', 'uri' => '/blog', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/contact', false, 0, $selectiveData);
        $this->setTemplateParams(['entry_id' => 4]);

        $result = $this->structure->siblings();
        
        // The method finds immediate array neighbors at the same depth
        // For entry 4 (Contact), it should find Team (entry 3) as previous and Blog (entry 5) as next
        $this->assertStringContainsString('Team', $result);
        $this->assertStringContainsString('Blog', $result);
        $this->assertStringNotContainsString('About', $result);
    }

    public function testSiblingsWithStatusFiltering()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team', 4 => '/contact']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            4 => ['entry_id' => 4, 'title' => 'Contact', 'uri' => '/contact', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/team', false, 0, $selectiveData);
        $this->setTemplateParams(['entry_id' => 3, 'status' => 'closed']);

        $result = $this->structure->siblings();
        
        // Should still work with status parameter
        $this->assertStringContainsString('About', $result);
        $this->assertStringContainsString('Contact', $result);
    }

    public function testSiblingsWithIncludeExcludeParameters()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team', 4 => '/contact']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            4 => ['entry_id' => 4, 'title' => 'Contact', 'uri' => '/contact', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/team', false, 0, $selectiveData);
        $this->setTemplateParams(['entry_id' => 3, 'include' => [2, 4], 'exclude' => [4]]);

        $result = $this->structure->siblings();
        
        // The include/exclude parameters are passed to get_selective_data but don't affect the siblings logic
        // The method will still show both About and Contact as they are at the same depth
        $this->assertStringContainsString('About', $result);
        $this->assertStringContainsString('Contact', $result);
    }

    public function testSiblingsWithShowExpiredAndFuture()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team', 4 => '/contact']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            4 => ['entry_id' => 4, 'title' => 'Contact', 'uri' => '/contact', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/team', false, 0, $selectiveData);
        $this->setTemplateParams([
            'entry_id' => 3, 
            'show_expired' => 'yes', 
            'show_future_entries' => 'yes'
        ]);

        $result = $this->structure->siblings();
        
        // Should work with show_expired and show_future_entries parameters
        $this->assertStringContainsString('About', $result);
        $this->assertStringContainsString('Contact', $result);
    }

    public function testSiblingsWithNoSitePages()
    {
        // The method expects site_pages to have a 'uris' key, so we provide an empty one
        $sitePages = ['uris' => []];
        $this->setSqlStubWithSiblings($sitePages, '/about', false, 0, []);
        $this->setTemplateParams(['entry_id' => 2]);

        $result = $this->structure->siblings();
        
        // Should return null when no selective data is available
        $this->assertNull($result);
    }

    public function testSiblingsWithNonArraySelectiveData()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about']];
        $this->setSqlStubWithSiblings($sitePages, '/about', false, 0, 'not_an_array');
        $this->setTemplateParams(['entry_id' => 2]);

        $result = $this->structure->siblings();
        
        $this->assertNull($result);
    }

    public function testSiblingsWithMissingEntryInSelectiveData()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open', 'depth' => 1],
            // Missing entry 3
        ];
        
        $this->setSqlStubWithSiblings($sitePages, '/team', false, 0, $selectiveData);
        $this->setTemplateParams(['entry_id' => 3]);

        $result = $this->structure->siblings();
        
        // Should handle missing entry gracefully
        $this->assertStringContainsString('|', $result);
    }
}





