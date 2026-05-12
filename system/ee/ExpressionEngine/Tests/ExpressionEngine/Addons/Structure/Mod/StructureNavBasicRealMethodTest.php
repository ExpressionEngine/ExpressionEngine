<?php

use PHPUnit\Framework\TestCase;

class StructureNavBasicRealMethodTest extends TestCase
{
    /**
     * Verify nav_basic() uses the real module file and preserves its
     * observable no-results and parse-variable behavior.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testNavBasicUsesRealModuleFileForNoResultsAndParsedBranches()
    {
        if (!defined('STRUCTURE_NAV_BASIC_REAL_METHOD_BOOTSTRAP')) {
            $this->markTestSkipped('Requires the dedicated nav_basic bootstrap to isolate PATH_ADDONS.');
        }

        require_once rtrim(PATH_ADDONS, '/') . '/structure/mod.structure.php';

        ee()->resetMocks();
        ee()->setMock('TMPL', new class {
            public $tagdata = 'TAGDATA';
            public $noResultsCalls = 0;
            public $parseVariablesCalls = 0;
            public $lastParsedTagdata = null;
            public $lastParsedVariables = [];

            public function no_results()
            {
                $this->noResultsCalls++;

                return 'NO_RESULTS';
            }

            public function parse_variables($tagdata, $variables)
            {
                $this->parseVariablesCalls++;
                $this->lastParsedTagdata = $tagdata;
                $this->lastParsedVariables = $variables;

                return 'PARSED:' . count($variables);
            }
        });

        $expectedModulePath = realpath(__DIR__ . '/../../../../../Addons/structure/mod.structure.php');
        $this->assertSame($expectedModulePath, (new ReflectionClass('Structure'))->getFileName());

        $parserClass = 'ExpressionEngine\\Addons\\Structure\\Libraries\\Structure_core_nav_parser';
        $structure = (new ReflectionClass('Structure'))->newInstanceWithoutConstructor();

        $parserClass::$variables = [];
        $this->assertSame('NO_RESULTS', $structure->nav_basic(false));
        $this->assertFalse($parserClass::$lastAddEntryVars);
        $this->assertSame(1, ee()->TMPL->noResultsCalls);
        $this->assertSame(0, ee()->TMPL->parseVariablesCalls);

        $parserClass::$variables = [['entry_id' => 1]];
        $this->assertSame('PARSED:1', $structure->nav_basic(true));
        $this->assertTrue($parserClass::$lastAddEntryVars);
        $this->assertSame(1, ee()->TMPL->noResultsCalls);
        $this->assertSame(1, ee()->TMPL->parseVariablesCalls);
        $this->assertSame('TAGDATA', ee()->TMPL->lastParsedTagdata);
        $this->assertSame([['entry_id' => 1]], ee()->TMPL->lastParsedVariables);
    }
}
