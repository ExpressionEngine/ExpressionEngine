<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchResultsTest extends Pro_searchTestBase
{
    public function testResultsRequireShortcutReturnsNoResultsWhenMissing()
    {
        // Set require_shortcut=yes and no shortcut param
        $this->setTemplateParams(['require_shortcut' => 'yes']);

        // pro_search_shortcut_model should return false (no shortcut found)
        ee()->setMock('pro_search_shortcut_model', new class {
            public function get_template_attrs(){ return []; }
            public function get_one($v,$a){ return false; }
        });

        // Minimal filters/fields libs used later should not be invoked if shortcut missing
        ee()->setMock('pro_search_filters', new class {
            public function filter(){}
            public function entry_ids(){ return []; }
            public function fixed_order(){ return false; }
            public function set_entry_ids($ids){}
            public function exclude(){ return []; }
        });

        $out = $this->pro->results();
        $this->assertSame('NO_RESULTS', $out);
    }
}


