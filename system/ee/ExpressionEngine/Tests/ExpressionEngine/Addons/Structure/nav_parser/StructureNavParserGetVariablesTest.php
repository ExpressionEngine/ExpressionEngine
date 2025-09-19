<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserGetVariablesTest extends StructureTestBase
{
    private function setStructureNavStub(string $html): void
    {
        // Make sure TMPL defaults that parser adjusts exist
        ee()->TMPL->var_single = [];
        ee()->TMPL->var_pair = [];
    }

    public function testGetVariablesParsesSimpleList()
    {
        ee()->config->items['word_separator'] = 'dash';
        $html = '<ul id="nav-sub">'
              . '<li id="nav-sub-10" class="here"><a href="https://example.com/">Home</a></li>'
              . '<li id="nav-sub-11" class="parent-here"><a href="https://example.com/about">About</a>'
              . '  <ul>'
              . '    <li id="nav-sub-12"><a href="https://example.com/about/team">Team</a></li>'
              . '  </ul>'
              . '</li>'
              . '</ul>';

        $this->markTestSkipped('get_variables integrates Structure::nav; parse_ul is tested separately.');
    }

    public function testGetVariablesReturnsEmptyOnEmptyNav()
    {
        $this->markTestSkipped('get_variables integrates Structure::nav; parse_ul is tested separately.');
    }
}


