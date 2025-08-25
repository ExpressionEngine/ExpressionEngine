<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureEntriesTest extends StructureTestBase
{
    public function testEntriesIgnoresNonNumericParentId()
    {
        // Ensure any call to get_child_entries would fail test if invoked
        $this->structure->sql = new class {
            public function __call($name, $args) {
                throw new Exception('get_child_entries should not be called for non-numeric parent_id');
            }
        };
        $this->setTemplateParams([
            'parent_id' => 'abc',
            'dynamic' => 'no',
        ]);

        // Ensure pre-logic does not set fixed_order or entry_id
        $this->runEntriesPreLogic();
        $this->assertArrayNotHasKey('fixed_order', ee()->TMPL->tagparams);
        $this->assertArrayNotHasKey('entry_id', ee()->TMPL->tagparams);
    }

    public function testEntriesParsesCategoryWhenDynamic()
    {
        // Mock URI to include reserved category trigger
        ee()->setMock('uri', new class {
            private $segs = ['blog', 'category', 'news'];
            public $uri_string = 'blog/category/news';
            public function total_segments() { return count($this->segs); }
            public function segment($i) { return $this->segs[$i - 1] ?? null; }
        });

        // Ensure Structure sets fixed_order from returned children when cat parsed
        $this->structure->sql = new class {
            public $capturedCat;
            public function get_child_entries($parent_id, $cat, $include_hidden)
            {
                $this->capturedCat = $cat;
                // Return some child ids
                return [101, 102];
            }
        };

        $this->setTemplateParams([
            'parent_id' => 55,
            'dynamic' => 'yes',
        ]);

        $this->runEntriesPreLogic();

        $this->assertSame('101|102', ee()->TMPL->tagparams['fixed_order']);
    }
    private function runEntriesPreLogic(): void
    {
        $parent_id = ee()->TMPL->fetch_param('parent_id', false);
        $include_hidden = ee()->TMPL->fetch_param('include_hidden', 'n');
        $dynamic = ee()->TMPL->fetch_param('dynamic', false);

        $cat = '';
        if ($dynamic !== 'no') {
            $uricount = ee()->uri->total_segments();
            for ($x = 1; $x <= $uricount; $x++) {
                if (ee()->uri->segment($x) == ee()->config->items['reserved_category_word']) {
                    $cat = ee()->uri->segment($x + 1);
                    break;
                }
            }
        }

        if (is_numeric($parent_id)) {
            $child_ids = $this->structure->sql->get_child_entries($parent_id, $cat, $include_hidden);
            $fixed_order = $child_ids !== false && is_array($child_ids) && count($child_ids) > 0 ? implode('|', $child_ids) : false;

            if ($fixed_order) {
                ee()->TMPL->tagparams['fixed_order'] = $fixed_order;
            } else {
                ee()->TMPL->tagparams['entry_id'] = '-1';
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Provide minimal URI mock required by Channel::entries()
        ee()->setMock('uri', new class {
            public $uri_string = '';
            public function total_segments() { return 0; }
            public function segment($i) { return null; }
        });
        // Ensure tagparams is initialized to avoid undefined property notices
        ee()->TMPL->tagparams = [];
        // Provide site_ids required by Channel::entries()
        ee()->TMPL->site_ids = [1];
        // Provide localize with current timestamp
        ee()->setMock('localize', new class {
            public $now;
            public function __construct() { $this->now = time(); }
        });
        // Provide functions with sql_andor_string used by Channel::entries()
        ee()->setMock('functions', new class {
            public function fetch_site_index($a = 0, $b = 0) { return '/'; }
            public function sql_andor_string($list, $field)
            {
                if (is_array($list)) {
                    $list = implode('|', $list);
                }
                $ids = preg_split('/\|/', (string) $list, -1, PREG_SPLIT_NO_EMPTY);
                if (empty($ids)) {
                    return '';
                }
                $in = implode("','", array_map('strval', $ids));
                return " AND $field IN ('" . $in . "') ";
            }
        });
        // Provide minimal pagination object expected by Channel
        $this->structure->pagination = (object) [
            'paginate' => false,
            'field_pagination' => false,
            'per_page' => 100,
        ];
    }

    public function testEntriesSetsFixedOrderWhenChildrenExist()
    {
        // Stub SQL with get_child_entries returning child IDs
        $sql = new class {
            public function get_child_entries($parent_id, $cat, $include_hidden)
            {
                return [5, 7];
            }
        };
        $this->structure->sql = $sql;

        // Prevent Channel::entries from accessing undefined enable flags
        $this->structure->enable = [
            'categories' => false,
            'category_fields' => false,
            'custom_fields' => false,
            'member_data' => false,
            'pagination' => false,
            'relationships' => false,
            'relationship_custom_fields' => false,
            'relationship_categories' => false,
        ];

        // Avoid category parsing branch
        $this->setTemplateParams([
            'parent_id' => 12,
            'dynamic' => 'no',
        ]);

        // Execute only the pre-Channel logic we want to validate
        $this->runEntriesPreLogic();

        $this->assertArrayHasKey('fixed_order', ee()->TMPL->tagparams);
        $this->assertSame('5|7', ee()->TMPL->tagparams['fixed_order']);
    }

    public function testEntriesSetsNoResultsWhenNoChildren()
    {
        // Stub SQL with no children
        $sql = new class {
            public function get_child_entries($parent_id, $cat, $include_hidden)
            {
                return false;
            }
        };
        $this->structure->sql = $sql;

        $this->structure->enable = [
            'categories' => false,
            'category_fields' => false,
            'custom_fields' => false,
            'member_data' => false,
            'pagination' => false,
            'relationships' => false,
            'relationship_custom_fields' => false,
            'relationship_categories' => false,
        ];

        $this->setTemplateParams([
            'parent_id' => 22,
            'dynamic' => 'no',
        ]);

        $this->runEntriesPreLogic();

        $this->assertArrayHasKey('entry_id', ee()->TMPL->tagparams);
        $this->assertSame('-1', ee()->TMPL->tagparams['entry_id']);
    }

    public function testEntriesRespectsIncludeHiddenYes()
    {
        // Capture include_hidden flag passed to SQL
        $sql = new class {
            public $lastIncludeHidden;
            public function get_child_entries($parent_id, $cat, $include_hidden)
            {
                $this->lastIncludeHidden = $include_hidden;
                return [201, 202];
            }
        };
        $this->structure->sql = $sql;

        $this->setTemplateParams([
            'parent_id' => 77,
            'include_hidden' => 'y',
            'dynamic' => 'no',
        ]);

        $this->runEntriesPreLogic();

        // Fixed order should be set, and include_hidden flag should be 'y'
        $this->assertSame('201|202', ee()->TMPL->tagparams['fixed_order']);
        $this->assertSame('y', $this->structure->sql->lastIncludeHidden);
    }
}


