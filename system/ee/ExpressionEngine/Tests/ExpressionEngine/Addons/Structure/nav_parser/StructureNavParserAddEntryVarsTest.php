<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserAddEntryVarsTest extends StructureTestBase
{
    public function testAddEntryVarsCopiesNonFieldKeys()
    {
        ee()->config->items['word_separator'] = 'dash';
        // Ensure template tag arrays exist for get_vars_to_parse
        ee()->TMPL->var_single = [];
        ee()->TMPL->var_pair = [];
        ee()->TMPL->tagdata = '';
        $parser = new NavParser();
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('add_entry_vars');
        $method->setAccessible(true);

        $row = [
            'entry_id' => 7,
            'url_title' => 'foo',
            'field_group' => 1,
            'field_id_2' => 'bar',
        ];
        $varRow = ['__prefix' => 'prefix:'];

        $method->invokeArgs($parser, [&$varRow, $row]);

        $this->assertSame(7, $varRow['prefix:entry_id']);
        $this->assertSame('foo', $varRow['prefix:url_title']);
        $this->assertArrayHasKey('entry_id_path', $varRow);
        unset($parser);
    }
}


