<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructurePageIdTest extends StructureTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set default template tagdata for page_id tests
        $this->setTemplateTagdata('{exp:structure:page_id}');
    }

    // setSqlStub now inherited from StructureTestBase

    // Helpers inherited from StructureTestBase: setNsetStub, setTemplateParams, setDbRows

    protected function setUriString(string $uriString): void
    {
        // Mock the ee('uri')->uri_string() via core test container
        $uri = new class($uriString) {
            private $uriString;
            public function __construct($uriString) { $this->uriString = $uriString; }
            public function uri_string() { return $this->uriString; }
        };
        ee()->setMock('uri', $uri);
    }

    public function testPageIdWithNoSitePages()
    {
        $this->setSqlStub(false, '/about');

        $result = $this->structure->page_id();
        $this->assertFalse($result);
    }

    public function testPageIdWithSimplePage()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/']];
        $this->setSqlStub($sitePages, '/about');
        $this->setUriString('about');

        $result = $this->structure->page_id();
        $this->assertEquals(2, $result);
    }

    public function testPageIdWithRootPage()
    {
        $sitePages = ['uris' => [1 => '/']];
        $this->setSqlStub($sitePages, '/');
        $this->setUriString('');

        $result = $this->structure->page_id();
        $this->assertFalse($result); // Empty uri_string becomes '//' which doesn't match '/'
    }

    public function testPageIdWithNestedPage()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/about/team/']];
        $this->setSqlStub($sitePages, '/about/team');
        $this->setUriString('about/team');

        $result = $this->structure->page_id();
        $this->assertEquals(3, $result);
    }

    public function testPageIdWithDeepNestedPage()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/about/team/', 4 => '/about/team/leadership/']];
        $this->setSqlStub($sitePages, '/about/team/leadership');
        $this->setUriString('about/team/leadership');

        $result = $this->structure->page_id();
        $this->assertEquals(4, $result);
    }

    public function testPageIdWithTemplateParamEntryUri()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/about/team']];
        $this->setSqlStub($sitePages, '/about');
        $this->setTemplateParams(['entry_uri' => '/about/team']);
        $this->setUriString('about');

        $result = $this->structure->page_id();
        $this->assertEquals(3, $result); // Template param should override current URI
    }

    public function testPageIdWithEmptyTemplateParam()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/']];
        $this->setSqlStub($sitePages, '/about');
        $this->setTemplateParams(['entry_uri' => '']);
        $this->setUriString('about');

        $result = $this->structure->page_id();
        $this->assertEquals(2, $result); // Should fall back to current URI when template param is empty
    }

    public function testPageIdWithNullTemplateParam()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/']];
        $this->setSqlStub($sitePages, '/about');
        $this->setTemplateParams(['entry_uri' => null]);
        $this->setUriString('about');

        $result = $this->structure->page_id();
        $this->assertEquals(2, $result); // Should fall back to current URI when template param is null
    }

    public function testPageIdWithNonexistentUri()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about']];
        $this->setSqlStub($sitePages, '/nonexistent');
        $this->setUriString('nonexistent');

        $result = $this->structure->page_id();
        $this->assertFalse($result); // Should return false for nonexistent URI
    }

    public function testPageIdWithNonexistentTemplateParam()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about']];
        $this->setSqlStub($sitePages, '/about');
        $this->setTemplateParams(['entry_uri' => '/nonexistent']);
        $this->setUriString('about');

        $result = $this->structure->page_id();
        $this->assertFalse($result); // Should return false for nonexistent template param URI
    }

    public function testPageIdWithComplexNestedStructure()
    {
        $sitePages = ['uris' => [
            1 => '/', 
            2 => '/products/', 
            3 => '/products/electronics/', 
            4 => '/products/electronics/computers/',
            5 => '/products/electronics/computers/laptops/',
            6 => '/products/electronics/computers/laptops/gaming/'
        ]];
        $this->setSqlStub($sitePages, '/products/electronics/computers/laptops/gaming');
        $this->setUriString('products/electronics/computers/laptops/gaming');

        $result = $this->structure->page_id();
        $this->assertEquals(6, $result);
    }

    public function testPageIdWithNumericUri()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/2024/', 3 => '/2024/01/']];
        $this->setSqlStub($sitePages, '/2024/01');
        $this->setUriString('2024/01');

        $result = $this->structure->page_id();
        $this->assertEquals(3, $result);
    }

    public function testPageIdWithSpecialCharacters()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about-us/', 3 => '/about-us/our-team/']];
        $this->setSqlStub($sitePages, '/about-us/our-team');
        $this->setUriString('about-us/our-team');

        $result = $this->structure->page_id();
        $this->assertEquals(3, $result);
    }

    public function testPageIdWithUnderscores()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about_us/', 3 => '/about_us/our_team/']];
        $this->setSqlStub($sitePages, '/about_us/our_team');
        $this->setUriString('about_us/our_team');

        $result = $this->structure->page_id();
        $this->assertEquals(3, $result);
    }

    public function testPageIdWithMixedCase()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/About/', 3 => '/About/Team/']];
        $this->setSqlStub($sitePages, '/About/Team');
        $this->setUriString('About/Team');

        $result = $this->structure->page_id();
        $this->assertEquals(3, $result);
    }

    public function testPageIdWithTrailingSlash()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about//']];
        $this->setSqlStub($sitePages, '/about/');
        $this->setUriString('about/');

        $result = $this->structure->page_id();
        $this->assertEquals(2, $result);
    }

    public function testPageIdWithLeadingSlash()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/']];
        $this->setSqlStub($sitePages, '/about');
        $this->setUriString('about');

        $result = $this->structure->page_id();
        $this->assertEquals(2, $result);
    }

    public function testPageIdWithMultipleSlashes()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about//team/']];
        $this->setSqlStub($sitePages, '/about//team');
        $this->setUriString('about//team');

        $result = $this->structure->page_id();
        $this->assertEquals(2, $result);
    }

    public function testPageIdWithWhitespace()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/ about /', 3 => '/ about / team /']];
        $this->setSqlStub($sitePages, '/ about / team ');
        $this->setUriString(' about / team ');

        $result = $this->structure->page_id();
        $this->assertEquals(3, $result);
    }

    public function testPageIdWithEmptyUriString()
    {
        $sitePages = ['uris' => [1 => '/']];
        $this->setSqlStub($sitePages, '/');
        $this->setUriString('');

        $result = $this->structure->page_id();
        $this->assertFalse($result); // Empty uri_string becomes '//' which doesn't match '/'
    }

    public function testPageIdWithSlashOnlyUriString()
    {
        $sitePages = ['uris' => [1 => '/']];
        $this->setSqlStub($sitePages, '/');
        $this->setUriString('/');

        $result = $this->structure->page_id();
        $this->assertFalse($result); // '/uri_string()' becomes '//' which doesn't match '/'
    }

    public function testPageIdWithTemplateParamOverride()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/about/team/']];
        $this->setSqlStub($sitePages, '/about');
        $this->setTemplateParams(['entry_uri' => '/about/team/']);
        $this->setUriString('about');

        $result = $this->structure->page_id();
        $this->assertEquals(3, $result); // Template param should override current URI
    }

    public function testPageIdWithComplexTemplateParam()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/products', 3 => '/products/electronics']];
        $this->setSqlStub($sitePages, '/products');
        $this->setTemplateParams(['entry_uri' => '/products/electronics/computers/laptops']);
        $this->setUriString('products');

        $result = $this->structure->page_id();
        $this->assertFalse($result); // Should return false for nonexistent template param URI
    }
}


