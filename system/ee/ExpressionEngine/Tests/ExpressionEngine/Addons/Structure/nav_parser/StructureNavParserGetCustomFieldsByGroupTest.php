<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserGetCustomFieldsByGroupTest extends StructureTestBase
{
    public function testGetCustomFieldsByGroupCachesAndReturnsNames()
    {
        // Seed DB channel_fields
        ee()->db->setRows([
            ['group_id' => 5, 'field_id' => 10, 'field_name' => 'alpha', 'field_settings' => base64_encode(serialize([]))],
            ['group_id' => 5, 'field_id' => 11, 'field_name' => 'beta', 'field_settings' => base64_encode(serialize([]))],
            ['group_id' => 6, 'field_id' => 12, 'field_name' => 'gamma', 'field_settings' => base64_encode(serialize([]))],
        ]);

        $parser = new NavParser();
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('get_custom_fields_by_group');
        \TestReflectionHelper::makeMethodAccessible($method);

        $map = $method->invoke($parser, 5);
        $this->assertSame(['alpha' => 10, 'beta' => 11], $map);

        // Calling again should return cached result even if DB rows change
        ee()->db->setRows([]);
        $map2 = $method->invoke($parser, 5);
        $this->assertSame($map, $map2);
        unset($parser);
    }

    public function testGetCustomFieldsByGroupReturnsEmptyMapForEmptyGroup()
    {
        $parser = new NavParser();
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('get_custom_fields_by_group');
        \TestReflectionHelper::makeMethodAccessible($method);

        $this->assertSame([], $method->invoke($parser, 0));
        unset($parser);
    }

    public function testGetCustomFieldsByGroupFallsBackToRawFieldWhenSettingsAreInvalid()
    {
        ee()->db->setRows([
            ['group_id' => 8, 'field_id' => 20, 'field_name' => 'broken', 'field_settings' => 'not-valid-base64'],
        ]);

        $parser = new NavParser();
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('get_custom_fields_by_group');
        \TestReflectionHelper::makeMethodAccessible($method);

        $map = $method->invoke($parser, 8);

        $this->assertSame(['broken' => 20], $map);
        $this->assertArrayHasKey(20, ee()->api_channel_fields->settings);
        $this->assertSame('broken', ee()->api_channel_fields->settings[20]['field_name']);
        unset($parser);
    }
}

