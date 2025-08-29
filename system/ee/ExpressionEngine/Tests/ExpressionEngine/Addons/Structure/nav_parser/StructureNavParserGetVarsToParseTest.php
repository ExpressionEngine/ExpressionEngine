<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserGetVarsToParseTest extends StructureTestBase
{
    public function testGetVarsToParseFindsSinglesAndPairs()
    {
        // Prepare template tags
        ee()->TMPL->var_single = [
            'prefix:title',
            'prefix:field_one format="yes"',
            'other:ignore',
        ];
        ee()->TMPL->var_pair = [
            'prefix:items' => ['limit' => '2'],
        ];
        ee()->TMPL->tagdata = '{prefix:items}{/prefix:items}';

        $parser = new NavParser();
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('get_vars_to_parse');
        $method->setAccessible(true);

        $result = $method->invoke($parser, 'prefix:', [
            'title' => 1,
            'field_one' => 2,
            'items' => 3,
        ]);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
        $fieldIds = array_column($result, 'field_id');
        $this->assertContains(1, $fieldIds);
        $this->assertContains(2, $fieldIds);
        $this->assertContains(3, $fieldIds);
        unset($parser);
    }
}


