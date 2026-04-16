<?php

require_once __DIR__ . '/../StructureTestBase.php';

class StructureTopLevelTitleTest extends StructureTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up URI stub for testing
        $this->setUriStub('/');
    }

    protected function setUriStub(string $uri): void
    {
        $uriStub = new class($uri) {
            private $uri;
            public function __construct($uri) { $this->uri = $uri; }
            public function segment($segment) { 
                if ($segment == 1) {
                    // Handle the case where URI is just '/' or empty
                    if ($this->uri === '/' || $this->uri === '') {
                        return '';
                    }
                    // First trim whitespace, then remove leading/trailing slashes, then get first segment
                    $path = trim($this->uri, ' '); // Remove leading/trailing whitespace first
                    $path = trim($path, '/'); // Then remove leading/trailing slashes
                    $segments = explode('/', $path);
                    return $segments[0] ?? '';
                }
                return '';
            }
        };
        
        // Register URI stub via ee() container
        ee()->setMock('uri', $uriStub);
    }

    // setSqlStub now inherited from StructureTestBase

    // Use base setDbRows to seed exp_channel_titles

    public function testTopLevelTitleWithValidSegment()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/about/');
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About Us']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('About Us', $result);
    }

    public function testTopLevelTitleWithNoSitePages()
    {
        $this->setSqlStub(false);
        $this->setUriStub('/about/');

        $result = $this->structure->top_level_title();
        $this->assertFalse($result);
    }

    public function testTopLevelTitleWithNoMatchingUri()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/nonexistent/');

        $result = $this->structure->top_level_title();
        $this->assertEquals('', $result);
    }

    public function testTopLevelTitleWithEmptySegment()
    {
        $sitePages = ['uris' => [1 => '//', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/');
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Home']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('Home', $result);
    }

    public function testTopLevelTitleWithRootUriStoredAsSingleSlashReturnsEmptyString()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/');
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Home']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertSame('', $result);
    }

    public function testTopLevelTitleWithSingleSegment()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/contact/');
        $this->setDbRows([
            ['entry_id' => 3, 'title' => 'Contact Information']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('Contact Information', $result);
    }

    public function testTopLevelTitleWithDeepPath()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/about/team/', 4 => '/about/team/leadership/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/about/team/leadership/');
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About Us']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('About Us', $result);
    }

    public function testTopLevelTitleWithNoDatabaseResult()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/about/');
        $this->setDbRows([]); // No matching entry

        $result = $this->structure->top_level_title();
        $this->assertEquals('', $result);
    }

    public function testTopLevelTitleWithMultipleDatabaseResults()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/about/');
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About Us'],
            ['entry_id' => 2, 'title' => 'Duplicate Entry'] // Should be ignored due to limit(1)
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('About Us', $result);
    }

    public function testTopLevelTitleWithSpecialCharacters()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/about/');
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About & Company']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('About & Company', $result);
    }

    public function testTopLevelTitleWithNumericTitle()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/about/');
        $this->setDbRows([
            ['entry_id' => 2, 'title' => '123 About']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('123 About', $result);
    }

    public function testTopLevelTitleWithEmptyTitle()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/about/');
        $this->setDbRows([
            ['entry_id' => 2, 'title' => '']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('', $result);
    }

    public function testTopLevelTitleWithNullTitle()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/about/');
        $this->setDbRows([
            ['entry_id' => 2, 'title' => null]
        ]);

        $result = $this->structure->top_level_title();
        $this->assertNull($result);
    }

    public function testTopLevelTitleWithComplexUriStructure()
    {
        $sitePages = ['uris' => [
            1 => '/',
            2 => '/about/',
            3 => '/about/team/',
            4 => '/about/team/leadership/',
            5 => '/products/',
            6 => '/products/software/',
            7 => '/products/software/enterprise/'
        ]];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/products/software/enterprise/');
        $this->setDbRows([
            ['entry_id' => 5, 'title' => 'Products']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('Products', $result);
    }

    public function testTopLevelTitleWithTrailingSlash()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/about/');
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About Us']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('About Us', $result);
    }

    public function testTopLevelTitleWithoutTrailingSlash()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/about');
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About Us']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('About Us', $result);
    }

    public function testTopLevelTitleWithLeadingSlash()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('/about/');
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About Us']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('About Us', $result);
    }

    public function testTopLevelTitleWithEmptyUri()
    {
        $sitePages = ['uris' => [1 => '//', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub('');
        $this->setDbRows([
            ['entry_id' => 1, 'title' => 'Home']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('Home', $result);
    }

    public function testTopLevelTitleWithWhitespaceInUri()
    {
        $sitePages = ['uris' => [1 => '//', 2 => '/about/', 3 => '/contact/']];
        $this->setSqlStub($sitePages);
        $this->setUriStub(' /about/ ');
        $this->setDbRows([
            ['entry_id' => 2, 'title' => 'About Us']
        ]);

        $result = $this->structure->top_level_title();
        $this->assertEquals('About Us', $result);
    }
}

