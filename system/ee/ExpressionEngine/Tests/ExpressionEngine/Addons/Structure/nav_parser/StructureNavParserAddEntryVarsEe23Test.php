<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserAddEntryVarsEe23Test extends StructureTestBase
{
    public function testAddEntryVarsEe23UsesExtensionHook()
    {
        // Build parser and manually seed entries/rows to avoid constructing Channel
        $parser = (new ReflectionClass(NavParser::class))->newInstanceWithoutConstructor();
        $parser->entry_ids = ['200'];
        $parser->rows_by_entry['200'] = ['__prefix' => 'root:'];

        // Provide a dedicated extensions mock and activate the hook
        if (function_exists('ee') && method_exists(ee(), 'setMock')) {
            ee()->setMock('extensions', new class {
                public $hooks = [];
                public function active_hook($name) { return $this->hooks[$name]['active'] ?? false; }
                public function call($name, $arg) { return $this->hooks[$name]['return'] ?? null; }
            });
        }
        // Activate extension hook to return legacy rows
        $rows = [
            [
                'entry_id' => 200,
                'title' => 'Legacy X',
                'field_group' => 9,
                'url_title' => 'x',
                'channel_id' => 1,
            ],
        ];
        ee()->extensions->hooks['structure_get_custom_variables'] = [
            'active' => true,
            'return' => new FakeDbResult($rows),
        ];

        // Ensure template var arrays exist for get_vars_to_parse()
        ee()->TMPL->var_single = [];
        ee()->TMPL->var_pair = [];
        ee()->TMPL->tagdata = '';

        // Invoke protected method directly
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('add_entry_vars_ee23');
        $method->setAccessible(true);
        $method->invoke($parser);

        $this->assertSame('Legacy X', $parser->rows_by_entry['200']['root:title']);
        unset($parser);
    }
}


