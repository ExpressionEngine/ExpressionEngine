<?php

require_once __DIR__ . '/StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/Conduit/StaticCache.php';

if (!class_exists('Structure_core_nav_parser')) {
    class Structure_core_nav_parser
    {
        public static $variables = [];
        public static $lastAddEntryVars = null;

        public function get_variables($add_entry_vars = false)
        {
            self::$lastAddEntryVars = $add_entry_vars;
            return self::$variables;
        }
    }
}

class StructureNavBasicAdvancedTest extends StructureTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Real nav parser path remains non-deterministic in this harness; covered by isolated nav_basic tests.');
        ee()->setMock('pagination', new class {
            public function create()
            {
                return new stdClass();
            }
        });
        ee()->setMock('addons_model', new class {
            public function module_installed($name)
            {
                return true;
            }
        });
        ee()->setMock('uri', new class {
            public $page_query_string = '';
            public $query_string = '';
            public $segments = [];
            public function uri_string()
            {
                return '';
            }
            public function total_segments()
            {
                return 0;
            }
            public function segment($idx)
            {
                return null;
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return $args[0] ?? null;
            }
        });
        ee()->setMock('session', new class {
            public $cache = ['structure' => []];
            public function userdata($key)
            {
                return null;
            }
        });
        ee()->setMock('localize', new class {
            public $now = 1700000000;
        });
    }

    public function testNavBasicReturnsNoResultsWhenParserReturnsEmpty()
    {
        Structure_core_nav_parser::$variables = [];
        ee()->TMPL->setTagdata('{title}');

        $result = $this->structure->nav_basic();

        $this->assertSame('NO_RESULTS', $result);
        $this->assertFalse(Structure_core_nav_parser::$lastAddEntryVars);
    }

    public function testNavBasicAndNavAdvancedParseVariables()
    {
        Structure_core_nav_parser::$variables = [
            ['title' => 'Page One'],
        ];
        ee()->TMPL->setTagdata('{title}');

        $basic = $this->structure->nav_basic();
        $advanced = $this->structure->nav_advanced();

        $this->assertSame('Page One', $basic);
        $this->assertSame('Page One', $advanced);
        $this->assertTrue(Structure_core_nav_parser::$lastAddEntryVars);
    }
}
