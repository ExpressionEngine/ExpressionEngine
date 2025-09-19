<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserConstructTest extends StructureTestBase
{
    public function testConstructorDoesNotAlterTemplate()
    {
        $original = ee()->TMPL;
        $parser = new NavParser();
        $this->assertSame($original, ee()->TMPL);
        unset($parser);
    }
}


