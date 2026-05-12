<?php

require_once rtrim(PATH_ADDONS, '/') . '/structure/mod.structure.php';

use PHPUnit\Framework\TestCase;

class StructureNavAdvancedRealMethodParserStub
{
    public $variables = [];
    public $lastAddEntryVars = null;

    public function get_variables($add_entry_vars = false)
    {
        $this->lastAddEntryVars = $add_entry_vars;

        return $this->variables;
    }
}

class StructureNavAdvancedRealMethodStructureStub extends Structure
{
    public $navParser;

    protected function makeNavParser()
    {
        return $this->navParser;
    }
}

class StructureNavAdvancedRealMethodTest extends TestCase
{
    /**
     * Verify nav_advanced() executes from the real module file and preserves
     * its observable no-results and parse-variable behavior.
     */
    public function testNavAdvancedUsesRealModuleFileForNoResultsAndParsedBranches()
    {
        ee()->resetMocks();
        ee()->setMock('TMPL', new class {
            public $tagdata = 'ADVANCED_TAGDATA';
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

        $parser = new StructureNavAdvancedRealMethodParserStub();
        $structure = (new ReflectionClass('StructureNavAdvancedRealMethodStructureStub'))->newInstanceWithoutConstructor();
        $structure->navParser = $parser;

        $parser->variables = [];
        $this->assertSame('NO_RESULTS', $structure->nav_advanced());
        $this->assertTrue($parser->lastAddEntryVars);
        $this->assertSame(1, ee()->TMPL->noResultsCalls);
        $this->assertSame(0, ee()->TMPL->parseVariablesCalls);

        $parser->variables = [['entry_id' => 42]];
        $this->assertSame('PARSED:1', $structure->nav_advanced());
        $this->assertTrue($parser->lastAddEntryVars);
        $this->assertSame(1, ee()->TMPL->noResultsCalls);
        $this->assertSame(1, ee()->TMPL->parseVariablesCalls);
        $this->assertSame('ADVANCED_TAGDATA', ee()->TMPL->lastParsedTagdata);
        $this->assertSame([['entry_id' => 42]], ee()->TMPL->lastParsedVariables);
    }
}
