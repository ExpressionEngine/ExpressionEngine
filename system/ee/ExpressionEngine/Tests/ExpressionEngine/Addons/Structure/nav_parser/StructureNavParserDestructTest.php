<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserDestructTest extends StructureTestBase
{
    public function testDestructorRestoresTMPL()
    {
        $original = ee()->TMPL;
        $parser = new NavParser();
        // Swap TMPL to simulate change
        $fake = new FakeTemplate();
        ee()->set('TMPL', $fake);
        unset($parser); // triggers __destruct
        $this->assertSame($original, ee()->TMPL);
    }
}


