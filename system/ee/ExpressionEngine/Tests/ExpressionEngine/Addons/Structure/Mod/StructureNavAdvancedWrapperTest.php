<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureNavAdvancedWrapperTest extends StructureTestBase
{
    public function testNavAdvancedDelegatesToNavBasicWithEntryVarsFlag()
    {
        $proxy = new class extends Structure {
            public $received = null;
            public function __construct()
            {
            }
            public function nav_basic($add_entry_vars = false)
            {
                $this->received = $add_entry_vars;
                return 'wrapped-nav';
            }
        };

        $this->assertSame('wrapped-nav', $proxy->nav_advanced());
        $this->assertTrue($proxy->received);
    }
}
