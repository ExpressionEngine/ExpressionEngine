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
        \TestReflectionHelper::makeMethodAccessible($method);

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

    public function testGetVarsToParseHandlesModifierAndSkipsUnknownTags()
    {
        ee()->TMPL->var_single = [
            'prefix:field_one:uppercase format="yes"',
            'prefix:unknown',
        ];
        ee()->TMPL->var_pair = [
            'other:ignore' => ['limit' => '1'],
            'prefix:unknown_pair' => ['limit' => '2'],
        ];
        ee()->TMPL->tagdata = '{prefix:unknown_pair}{/prefix:unknown_pair}';

        $parser = new NavParser();
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('get_vars_to_parse');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($parser, 'prefix:', [
            'field_one' => 2,
        ]);

        $this->assertCount(1, $result);
        $this->assertSame('uppercase', $result[0]['modifier']);
        $this->assertSame(2, $result[0]['field_id']);
        $this->assertSame('prefix:field_one:uppercase format="yes"', $result[0]['replace']);
        $this->assertSame(['format' => 'yes'], $result[0]['params']);
        unset($parser);
    }

    public function testGetVarsToParseSupportsSpaceTagNameBranch()
    {
        ee()->TMPL->var_single = [];
        ee()->TMPL->var_pair = [
            ' ' => ['flag' => 'y'],
        ];
        ee()->TMPL->tagdata = '{ }payload{/}';

        $parser = new NavParser();
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('get_vars_to_parse');
        \TestReflectionHelper::makeMethodAccessible($method);

        $result = $method->invoke($parser, '', [
            '' => 77,
        ]);

        $this->assertCount(1, $result);
        $this->assertSame(77, $result[0]['field_id']);
        $this->assertSame('payload', $result[0]['tagdata']);
        unset($parser);
    }
}

