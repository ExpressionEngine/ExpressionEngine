<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserGetCustomFieldsTest extends StructureTestBase
{
    public function testGetCustomFieldsBuildsGroupedMapAndCaches()
    {
        ee()->db->setRows([
            ['group_id' => 1, 'field_id' => 10, 'field_name' => 'alpha', 'field_settings' => base64_encode(serialize([]))],
            ['group_id' => 1, 'field_id' => 11, 'field_name' => 'beta', 'field_settings' => base64_encode(serialize([]))],
            ['group_id' => 2, 'field_id' => 12, 'field_name' => 'gamma', 'field_settings' => base64_encode(serialize([]))],
        ]);

        $parser = new NavParser();
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('get_custom_fields');
        $method->setAccessible(true);
        $fields = $method->invoke($parser);
        $this->assertArrayHasKey(1, $fields);
        $this->assertArrayHasKey(2, $fields);
        $this->assertArrayHasKey(10, $fields[1]);

        // Change DB; cached value should remain
        ee()->db->setRows([]);
        $fields2 = $method->invoke($parser);
        $this->assertEquals($fields, $fields2);
        unset($parser);
    }
}


