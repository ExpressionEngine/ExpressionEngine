<?php

require_once __DIR__ . '/../StructureTestBase.php';

class StructureChildIdsTest extends StructureTestBase
{
    public function testChildIdsReturnsFalseWhenNoSitePages()
    {
        $this->structure->sql = new class {
            public function get_site_pages() { return false; }
        };
        $this->assertFalse($this->structure->child_ids());
    }
    public function testChildIdsForExplicitParent()
    {
        // site_pages is used to early return false if missing; provide minimal
        $this->structure->sql = new class {
            public function get_site_pages() { return ['uris' => [10 => '/parent/']]; }
        };

        // DB returns children for parent_id = 10
        $this->setDbRows([
            ['entry_id' => 21, 'parent_id' => 10],
            ['entry_id' => 22, 'parent_id' => 10],
        ]);

        $this->setTemplateParams([
            'entry_id' => 10,
            'delimiter' => ',',
        ]);

        $result = $this->structure->child_ids();
        $this->assertSame('21,22', $result);
    }

    public function testChildIdsResolvesParentFromStartFrom()
    {
        $sitePages = ['uris' => [10 => '/parent/']];
        $this->structure->sql = new class($sitePages) {
            private $sitePages;
            public function __construct($sitePages) { $this->sitePages = $sitePages; }
            public function get_site_pages() { return $this->sitePages; }
        };

        $this->setDbRows([
            ['entry_id' => 31, 'parent_id' => 10],
        ]);

        $this->setTemplateParams([
            'start_from' => 'parent',
        ]);

        $result = $this->structure->child_ids();
        $this->assertSame('31', $result);
    }

    public function testChildIdsAutoResolvesParentFromCurrentUri()
    {
        $sitePages = ['uris' => [10 => '/parent/']];
        $this->structure->sql = new class($sitePages) {
            private $sitePages;
            public function __construct($sitePages) { $this->sitePages = $sitePages; }
            public function get_site_pages() { return $this->sitePages; }
        };

        // Simulate current URI of 'parent' so child_ids() finds parent by array_search
        ee()->setMock('uri', new class {
            public function segment_array() { return ['parent']; }
        });

        $this->setDbRows([
            ['entry_id' => 41, 'parent_id' => 10],
            ['entry_id' => 42, 'parent_id' => 10],
        ]);

        $result = $this->structure->child_ids();
        $this->assertSame('41|42', $result);
    }

    public function testChildIdsReturnsZeroWhenNoChildren()
    {
        $sitePages = ['uris' => [10 => '/parent/']];
        $this->structure->sql = new class($sitePages) {
            private $sitePages;
            public function __construct($sitePages) { $this->sitePages = $sitePages; }
            public function get_site_pages() { return $this->sitePages; }
        };

        $this->setDbRows([]);

        $this->setTemplateParams([
            'entry_id' => 10,
        ]);

        $result = $this->structure->child_ids();
        $this->assertSame('0', $result);
    }

    public function testChildIdsRespectsCustomDelimiter()
    {
        $sitePages = ['uris' => [10 => '/parent/']];
        $this->structure->sql = new class($sitePages) {
            private $sitePages;
            public function __construct($sitePages) { $this->sitePages = $sitePages; }
            public function get_site_pages() { return $this->sitePages; }
        };

        $this->setDbRows([
            ['entry_id' => 51, 'parent_id' => 10],
            ['entry_id' => 52, 'parent_id' => 10],
        ]);

        $this->setTemplateParams([
            'entry_id' => 10,
            'delimiter' => ';',
        ]);

        $result = $this->structure->child_ids();
        $this->assertSame('51;52', $result);
    }

    public function testChildIdsReturnsNullAndSkipsQueryWhenStartFromCannotBeResolved()
    {
        $sitePages = ['uris' => [10 => '/parent/']];
        $this->structure->sql = new class($sitePages) {
            private $sitePages;
            public function __construct($sitePages) { $this->sitePages = $sitePages; }
            public function get_site_pages() { return $this->sitePages; }
        };

        $captured = (object) ['queries' => []];
        ee()->setMock('db', new class($captured) {
            private $captured;
            public function __construct($captured) { $this->captured = $captured; }
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return new eeDbResultMock([]);
            }
        });

        $this->setTemplateParams([
            'start_from' => 'missing',
        ]);

        $this->assertNull($this->structure->child_ids());
        $this->assertSame([], $captured->queries);
    }

    public function testChildIdsTrimsStartFromAndUsesConfiguredSiteIdInQuery()
    {
        $sitePages = ['uris' => [10 => '/parent/']];
        $this->structure->sql = new class($sitePages) {
            private $sitePages;
            public function __construct($sitePages) { $this->sitePages = $sitePages; }
            public function get_site_pages() { return $this->sitePages; }
        };

        $captured = (object) ['queries' => []];
        ee()->setMock('db', new class($captured) {
            private $captured;
            public function __construct($captured) { $this->captured = $captured; }
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return new eeDbResultMock([
                    ['entry_id' => 61, 'parent_id' => 10],
                ]);
            }
        });

        ee()->config->setItem('site_id', 7);

        $this->setTemplateParams([
            'start_from' => '/parent/',
        ]);

        $result = $this->structure->child_ids();

        $this->assertSame('61', $result);
        $this->assertCount(1, $captured->queries);
        $this->assertStringContainsString("parent_id = '10'", $captured->queries[0]);
        $this->assertStringContainsString("entry_id != '0'", $captured->queries[0]);
        $this->assertStringContainsString("site_id = 7", $captured->queries[0]);
    }
}

