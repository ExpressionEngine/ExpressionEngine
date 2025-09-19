<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserParseUlTest extends StructureTestBase
{
    public function testParseUlBuildsHierarchyAndFlags()
    {
        ee()->config->items['word_separator'] = 'dash';
        $doc = new DOMDocument();
        $html = '<ul>'
              . '<li id="nav-sub-1" class="here"><a href="https://example.com/1">One</a>'
              . '  <ul>'
              . '    <li id="nav-sub-2"><a href="https://example.com/1/2">Two</a></li>'
              . '  </ul>'
              . '</li>'
              . '<li id="nav-sub-3"><a href="https://example.com/3">Three</a></li>'
              . '</ul>';
        $doc->loadHTML('<?xml version="1.0"?><!doctype html>' . $html);
        $ul = $doc->getElementsByTagName('ul')->item(0);

        $parser = new NavParser();
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('parse_ul');
        $method->setAccessible(true);

        $vars = $method->invoke($parser, $ul, 0);
        $this->assertCount(2, $vars);
        $this->assertTrue($vars[0]['root:active']);
        $this->assertTrue($vars[0]['root:has_children']);
        $this->assertTrue($vars[0]['root:first_child']);
        $this->assertTrue($vars[1]['root:last_child']);
        unset($parser);
    }
}


