<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserParseUlUnderscoreTest extends StructureTestBase
{
    public function testParseUlWithUnderscoreSeparator()
    {
        ee()->config->items['word_separator'] = 'underscore';
        $doc = new DOMDocument();
        $html = '<ul>'
              . '<li id="nav_sub_9" class=""><a href="https://example.com/9">Nine</a></li>'
              . '</ul>';
        $doc->loadHTML('<?xml version="1.0" encoding="UTF-8"?><!doctype html>' . $html);
        $ul = $doc->getElementsByTagName('ul')->item(0);

        $parser = new NavParser();
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('parse_ul');
        $method->setAccessible(true);
        $vars = $method->invoke($parser, $ul);

        $this->assertSame('9', $vars[0]['root:entry_id']);
        unset($parser);
    }
}


