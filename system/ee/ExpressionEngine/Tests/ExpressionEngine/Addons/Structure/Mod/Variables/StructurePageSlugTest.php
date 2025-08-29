<?php

require_once __DIR__ . '/../StructureTestBase.php';

class StructurePageSlugTest extends StructureTestBase
{
    public function testPageSlugUsesProvidedEntryId()
    {
        $sitePages = ['uris' => [10 => '/parent/child/']];
        $this->structure->sql = new class($sitePages) {
            private $sitePages;
            public function __construct($sitePages) { $this->sitePages = $sitePages; }
            public function get_site_pages() { return $this->sitePages; }
            public function get_uri() { return '/parent/child/'; }
        };
        $this->setTemplateParams(['entry_id' => 10]);

        $slug = $this->structure->page_slug();
        $this->assertSame('child', $slug);
    }

    public function testPageSlugFallsBackToCurrentUriWhenNoEntryIdParam()
    {
        $sitePages = ['uris' => [15 => '/section/current/']];
        $this->structure->sql = new class($sitePages) {
            private $sitePages;
            public function __construct($sitePages) { $this->sitePages = $sitePages; }
            public function get_site_pages() { return $this->sitePages; }
            public function get_uri() { return '/section/current/'; }
        };

        $slug = $this->structure->page_slug();
        $this->assertSame('current', $slug);
    }

    public function testPageSlugForRootReturnsEmptyString()
    {
        $sitePages = ['uris' => [1 => '/']];
        $this->structure->sql = new class($sitePages) {
            private $sitePages;
            public function __construct($sitePages) { $this->sitePages = $sitePages; }
            public function get_site_pages() { return $this->sitePages; }
            public function get_uri() { return '/'; }
        };
        $this->setTemplateParams(['entry_id' => 1]);

        $slug = $this->structure->page_slug();
        $this->assertSame('', $slug);
    }

    public function testPageSlugReturnsFalseWhenNoSitePages()
    {
        $this->structure->sql = new class {
            public function get_site_pages() { return false; }
            public function get_uri() { return '/any/'; }
        };
        $this->assertFalse($this->structure->page_slug());
    }

    public function testPageSlugMethodArgOverridesTemplateParam()
    {
        $sitePages = ['uris' => [11 => '/a/b/', 22 => '/c/d/']];
        $this->structure->sql = new class($sitePages) {
            private $sitePages;
            public function __construct($sitePages) { $this->sitePages = $sitePages; }
            public function get_site_pages() { return $this->sitePages; }
            public function get_uri() { return '/c/d/'; }
        };
        // Template param would point to entry 11 -> slug 'b'
        $this->setTemplateParams(['entry_id' => 11]);
        // But passing method arg 22 should take precedence -> slug 'd'
        $slug = $this->structure->page_slug(22);
        $this->assertSame('d', $slug);
    }

    public function testPageSlugExtractsFromDeepUri()
    {
        $sitePages = ['uris' => [99 => '/grand/parent/child/grandchild/']];
        $this->structure->sql = new class($sitePages) {
            private $sitePages;
            public function __construct($sitePages) { $this->sitePages = $sitePages; }
            public function get_site_pages() { return $this->sitePages; }
            public function get_uri() { return '/grand/parent/child/grandchild/'; }
        };
        $this->setTemplateParams(['entry_id' => 99]);
        $slug = $this->structure->page_slug();
        $this->assertSame('grandchild', $slug);
    }
}


