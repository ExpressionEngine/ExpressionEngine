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
            'entry_id' => '10',
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

    public function testChildIdsRejectsMalformedParentWithoutQuery()
    {
        $this->structure->sql = new class {
            public function get_site_pages() { return ['uris' => [10 => '/parent/']]; }
        };

        $captured = (object) ['queries' => []];
        ee()->setMock('db', new class($captured) {
            private $captured;
            public function __construct($captured) { $this->captured = $captured; }
            public function query($sql, $binds = false)
            {
                $this->captured->queries[] = [$sql, $binds];

                return new eeDbResultMock([]);
            }
        });

        foreach (["10'", '10abc', '1e2', '-1', '000', [10]] as $entryId) {
            $this->setTemplateParams(['entry_id' => $entryId]);
            $this->assertNull($this->structure->child_ids());
        }

        $this->assertSame([], $captured->queries);
    }

    public function testChildIdsPreservesLargeDecimalParentInQueryBinds()
    {
        $this->structure->sql = new class {
            public function get_site_pages() { return ['uris' => []]; }
        };

        $captured = (object) ['binds' => null];
        ee()->setMock('db', new class($captured) {
            private $captured;
            public function __construct($captured) { $this->captured = $captured; }
            public function query($sql, $binds = false)
            {
                $this->captured->binds = $binds;

                return new eeDbResultMock([]);
            }
        });

        $this->setTemplateParams(['entry_id' => '4294967295']);

        $this->assertSame('0', $this->structure->child_ids());
        $this->assertSame(['4294967295', 1], $captured->binds);
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
            public function query($sql, $binds = false)
            {
                $this->captured->queries[] = [$sql, $binds];

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
        $this->assertSame(
            "SELECT entry_id FROM exp_structure WHERE parent_id = ? AND entry_id != '0' AND site_id = ? ORDER BY lft ASC",
            $captured->queries[0][0]
        );
        $this->assertSame(['10', 7], $captured->queries[0][1]);
    }
}

