<?php

require_once __DIR__ . '/../StructureTestBase.php';

class StructureTraverseTest extends StructureTestBase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setTemplateTagdata('Prev: {prev_title} ({prev_url}) | Next: {next_title} ({next_url})');
    }

    protected function setSqlStubWithTraverse($sitePages, string $uri, array $selectiveData, $tracker = null): void
    {
        $sql = new class($sitePages, $uri, $selectiveData, $tracker) {
            private $sitePages;
            private $uri;
            private $selectiveData;
            private $tracker;

            public function __construct($sitePages, $uri, $selectiveData, $tracker)
            {
                $this->sitePages = $sitePages;
                $this->uri = $uri;
                $this->selectiveData = $selectiveData;
                $this->tracker = $tracker;
            }

            public function get_site_pages() { return $this->sitePages; }
            public function get_uri() { return $this->uri; }
            public function get_selective_data($siteId, $entryId, $parentId, $type, $depth, $limit, $status, $include, $exclude, $showExpired, $showFuture, $showExpired2, $showFuture2) {
                if ($this->tracker) {
                    $this->tracker->selectiveDataArgs = func_get_args();
                }

                return $this->selectiveData;
            }
        };
        $this->structure->sql = $sql;
    }

    public function testTraverseWithPreviousAndNext()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team', 4 => '/contact']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
            4 => ['entry_id' => 4, 'title' => 'Contact', 'uri' => '/contact', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
        ];

        $this->setSqlStubWithTraverse($sitePages, '/team', $selectiveData);
        $this->setTemplateParams(['entry_id' => 3]);

        $result = $this->structure->traverse();

        $this->assertStringContainsString('Prev: About (/about)', $result);
        $this->assertStringContainsString('Next: Contact (/contact)', $result);
    }

    public function testTraverseWithOnlyNext()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
        ];

        $this->setSqlStubWithTraverse($sitePages, '/about', $selectiveData);
        $this->setTemplateParams(['entry_id' => 2]);

        $result = $this->structure->traverse();

        $this->assertStringContainsString('Next: Team (/team)', $result);
    }

    public function testTraverseWithOnlyPrevious()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
        ];

        $this->setSqlStubWithTraverse($sitePages, '/team', $selectiveData);
        $this->setTemplateParams(['entry_id' => 3]);

        $result = $this->structure->traverse();

        $this->assertStringContainsString('Prev: About (/about)', $result);
    }

    public function testTraverseWithNoSelectiveDataKeepsTemplate()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about']];
        $this->setSqlStubWithTraverse($sitePages, '/about', []);
        $this->setTemplateParams(['entry_id' => 2]);

        $expected = 'Prev: {prev_title} ({prev_url}) | Next: {next_title} ({next_url})';
        $result = $this->structure->traverse();
        $this->assertSame($expected, $result);
    }

    public function testTraverseWithoutEntryIdUsesUriLookup()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
        ];

        $this->setSqlStubWithTraverse($sitePages, '/team', $selectiveData);
        // No entry_id param

        $result = $this->structure->traverse();
        $this->assertStringContainsString('Prev: About (/about)', $result);
    }

    public function testTraverseUsesUriDerivedEntryIdAndForwardsTemplateParametersToSelectiveData()
    {
        $tracker = new stdClass();
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team', 4 => '/contact']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
            4 => ['entry_id' => 4, 'title' => 'Contact', 'uri' => '/contact', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'closed'],
        ];

        $this->setSqlStubWithTraverse($sitePages, '/team', $selectiveData, $tracker);
        $this->setTemplateParams([
            'status' => 'closed',
            'include' => [2, 4],
            'exclude' => [5],
            'show_expired' => 'yes',
            'show_future_entries' => 'yes',
        ]);

        $result = $this->structure->traverse();

        $this->assertSame([1, 3, 0, 'full', 1, -1, 'closed', [2, 4], [5], false, false, 'yes', 'yes'], $tracker->selectiveDataArgs);
        $this->assertStringContainsString('Prev: About (/about)', $result);
        $this->assertStringContainsString('Next: Contact (/contact)', $result);
    }

    public function testTraverseRespectsArrayOrderForNeighbors()
    {
        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team', 4 => '/contact', 5 => '/blog']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
            4 => ['entry_id' => 4, 'title' => 'Contact', 'uri' => '/contact', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
            5 => ['entry_id' => 5, 'title' => 'Blog', 'uri' => '/blog', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
        ];

        $this->setSqlStubWithTraverse($sitePages, '/contact', $selectiveData);
        $this->setTemplateParams(['entry_id' => 4]);

        $result = $this->structure->traverse();

        $this->assertStringContainsString('Prev: Team (/team)', $result);
        $this->assertStringContainsString('Next: Blog (/blog)', $result);
        $this->assertStringNotContainsString('About (/about)', $result);
    }

    public function testTraverseOutputsIdsAndUrls()
    {
        $this->setTemplateTagdata('PrevID: {prev_entry_id} PrevURL: {prev_url} NextID: {next_entry_id} NextURL: {next_url}');

        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team', 4 => '/contact']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
            4 => ['entry_id' => 4, 'title' => 'Contact', 'uri' => '/contact', 'parent_id' => 0, 'channel_id' => 1, 'status' => 'open'],
        ];

        $this->setSqlStubWithTraverse($sitePages, '/team', $selectiveData);
        $this->setTemplateParams(['entry_id' => 3]);

        $result = $this->structure->traverse();
        $this->assertStringContainsString('PrevID: 2', $result);
        $this->assertStringContainsString('PrevURL: /about', $result);
        $this->assertStringContainsString('NextID: 4', $result);
        $this->assertStringContainsString('NextURL: /contact', $result);
    }

    public function testTraversePassesFullPrevAndNextMetadataToTemplateParser()
    {
        $template = new class extends FakeTemplate {
            public $capturedVars = [];

            public function parse_variables($tagdata, $vars)
            {
                $this->capturedVars = $vars;

                return parent::parse_variables($tagdata, $vars);
            }
        };
        $template->tagproper = 'structure:traverse';
        $template->setTagdata('{prev_title}|{prev_entry_id}|{next_title}|{next_entry_id}');
        ee()->setMock('TMPL', $template);

        $sitePages = ['uris' => [1 => '/', 2 => '/about', 3 => '/team', 4 => '/contact']];
        $selectiveData = [
            2 => ['entry_id' => 2, 'title' => 'About', 'uri' => '/about', 'parent_id' => 7, 'channel_id' => 10, 'status' => 'closed'],
            3 => ['entry_id' => 3, 'title' => 'Team', 'uri' => '/team', 'parent_id' => 7, 'channel_id' => 10, 'status' => 'open'],
            4 => ['entry_id' => 4, 'title' => 'Contact', 'uri' => '/contact', 'parent_id' => 7, 'channel_id' => 12, 'status' => 'draft'],
        ];

        $this->setSqlStubWithTraverse($sitePages, '/team', $selectiveData);
        $this->setTemplateParams(['entry_id' => 3]);

        $result = $this->structure->traverse();

        $this->assertSame([
            [
                'prev' => [[
                    'title' => 'About',
                    'url' => '/about',
                    'entry_id' => 2,
                    'parent_id' => 7,
                    'channel_id' => 10,
                    'status' => 'closed',
                ]],
                'next' => [[
                    'title' => 'Contact',
                    'url' => '/contact',
                    'entry_id' => 4,
                    'parent_id' => 7,
                    'channel_id' => 12,
                    'status' => 'draft',
                ]],
            ],
        ], $template->capturedVars);
        $this->assertSame('About|2|Contact|4', $result);
    }
}

