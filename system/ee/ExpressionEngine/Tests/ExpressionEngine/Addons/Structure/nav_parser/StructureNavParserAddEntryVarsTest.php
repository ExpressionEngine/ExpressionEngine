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
        \TestReflectionHelper::makeMethodAccessible($method);

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

    public function testAddEntryVarsExercisesFieldtypeHandlerBranches()
    {
        ee()->TMPL->var_single = [
            'prefix:body',
            'prefix:summary',
            'prefix:missing',
            'prefix:mod_ok:foo',
            'prefix:mod_fallback:bar',
            'prefix:mod_skip:baz',
        ];
        ee()->TMPL->var_pair = [];
        ee()->TMPL->tagdata = '';

        ee()->db->setRows([
            ['group_id' => 2, 'field_id' => 2, 'field_name' => 'body', 'field_settings' => base64_encode(serialize([]))],
            ['group_id' => 2, 'field_id' => 3, 'field_name' => 'summary', 'field_settings' => base64_encode(serialize([]))],
            ['group_id' => 2, 'field_id' => 4, 'field_name' => 'missing', 'field_settings' => base64_encode(serialize([]))],
            ['group_id' => 2, 'field_id' => 5, 'field_name' => 'mod_ok', 'field_settings' => base64_encode(serialize([]))],
            ['group_id' => 2, 'field_id' => 6, 'field_name' => 'mod_fallback', 'field_settings' => base64_encode(serialize([]))],
            ['group_id' => 2, 'field_id' => 7, 'field_name' => 'mod_skip', 'field_settings' => base64_encode(serialize([]))],
        ]);

        ee()->setMock('api_channel_fields', new class {
            public $settings = [];
            public function set_settings($id, $settings) { $this->settings[$id] = $settings; }
            public function setup_handler($id) { return $id !== 3; }
            public function apply($method, $args)
            {
                if ($method === '_init') {
                    return null;
                }
                if ($method === 'pre_process') {
                    return 'pre:' . $args[0];
                }
                return $method . ':' . $args[0];
            }
            public function check_method_exists($method)
            {
                return in_array($method, ['replace_tag', 'replace_foo', 'replace_tag_catchall'], true);
            }
        });

        $parser = new NavParser();
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('add_entry_vars');
        \TestReflectionHelper::makeMethodAccessible($method);

        $row = [
            'entry_id' => 42,
            'url_title' => 'branchy',
            'field_group' => 2,
            'field_id_2' => 'body-raw',
            'field_id_3' => 'summary-raw',
            'field_id_5' => 'ok-raw',
            'field_id_6' => 'fallback-raw',
            'field_id_7' => 'skip-raw',
        ];
        $varRow = ['__prefix' => 'prefix:'];

        $method->invokeArgs($parser, [&$varRow, $row]);

        $this->assertSame('replace_tag:pre:body-raw', $varRow['prefix:body']);
        $this->assertSame('summary-raw', $varRow['prefix:summary']);
        $this->assertSame('replace_foo:pre:ok-raw', $varRow['prefix:mod_ok:foo']);
        $this->assertSame('replace_tag_catchall:pre:fallback-raw', $varRow['prefix:mod_fallback:bar']);
        $this->assertSame('ok-raw', $varRow['prefix:mod_ok']);
        $this->assertSame('fallback-raw', $varRow['prefix:mod_fallback']);
        $this->assertSame('skip-raw', $varRow['prefix:mod_skip']);
        $this->assertSame('', $varRow['prefix:missing']);
        $this->assertSame([42, ['path_variable' => true]], $varRow['entry_id_path']);
        $this->assertSame(['branchy', ['path_variable' => true]], $varRow['url_title_path']);
        $this->assertSame(['branchy', ['path_variable' => true]], $varRow['title_permalink']);
        unset($parser);
    }
}
