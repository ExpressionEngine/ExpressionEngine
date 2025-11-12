<?php

require_once dirname(__DIR__, 4) . '/eeObjectMock.php';

// Define a minimal Structure stub BEFORE loading the parser library
if (!class_exists('Structure')) {
    class Structure {
        private static $html = '';
        public function __construct() {}
        public function nav($arg) { return self::$html; }
        public static function setHtml($html) { self::$html = $html; }
    }
}

require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

use PHPUnit\Framework\TestCase;
use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserGetVariablesIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        // If the real Structure class is already loaded (without our stub API), skip
        if (class_exists('Structure') && !method_exists('Structure', 'setHtml')) {
            $this->markTestSkipped('Cannot stub Structure::nav; Structure already loaded by other tests.');
        }
        // Minimal TMPL and functions mocks
        if (function_exists('ee') && method_exists(ee(), 'setMock')) {
            ee()->setMock('TMPL', new class {
                public $tagparams = [];
                public $var_single = [];
                public $var_pair = [];
                public $tagdata = '';
            });
            ee()->setMock('functions', new class {
                public function create_url($path = '') { return 'https://example.com/'; }
            });
            ee()->config->setItem('charset', 'UTF-8');
            ee()->config->setItem('word_separator', 'dash');
        }
    }

    public function testGetVariablesParsesSimpleList()
    {
        $html = '<ul id="nav-sub">'
              . '<li id="nav-sub-10" class="here"><a href="https://example.com/">Home</a></li>'
              . '<li id="nav-sub-11" class="parent-here"><a href="https://example.com/about">About</a>'
              . '  <ul>'
              . '    <li id="nav-sub-12"><a href="https://example.com/about/team">Team</a></li>'
              . '  </ul>'
              . '</li>'
              . '</ul>';

        Structure::setHtml($html);
        $parser = new NavParser();
        $vars = $parser->get_variables(false);

        $this->assertIsArray($vars);
        $this->assertCount(2, $vars);
        $this->assertSame('10', $vars[0]['root:entry_id']);
        $this->assertSame('Home', $vars[0]['root:title']);
        $this->assertTrue($vars[0]['root:first_child']);
        $this->assertTrue($vars[0]['root:active']);
        $this->assertFalse($vars[0]['root:has_children']);

        $this->assertSame('11', $vars[1]['root:entry_id']);
        $this->assertSame('About', $vars[1]['root:title']);
        $this->assertTrue($vars[1]['root:has_children']);
        $this->assertTrue($vars[1]['root:has_active_child']);
        $this->assertIsArray($vars[1]['root:children']);
        $this->assertSame('12', $vars[1]['root:children'][0]['child:entry_id']);
    }

    public function testGetVariablesReturnsEmptyOnEmptyNav()
    {
        Structure::setHtml('');
        $parser = new NavParser();
        $this->assertSame([], $parser->get_variables(false));
    }
}


