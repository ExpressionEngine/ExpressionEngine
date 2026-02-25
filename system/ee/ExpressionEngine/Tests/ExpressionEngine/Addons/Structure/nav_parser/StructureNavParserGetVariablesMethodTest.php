<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';

use PHPUnit\Framework\TestCase;
use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserGetVariablesMethodTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetVariablesParsesNavigationMarkupAndNoNavPath()
    {
        if (!class_exists('Structure')) {
            eval('
                class Structure {
                    public static $nav = "";
                    public function nav($site_id) { return self::$nav; }
                }
            ');
        }

        ee()->resetMocks();
        ee()->setMock('TMPL', new FakeTemplate());
        ee()->TMPL->tagparams = [
            'add_level_classes' => 'y',
            'add_span' => 'y',
            'css_class' => 'x',
            'css_id' => 'x',
            'current_class' => 'x',
            'has_children_class' => 'x',
            'include_ul' => 'x',
        ];
        ee()->setMock('config', new FakeConfig());
        ee()->config->items['charset'] = 'UTF-8';
        ee()->config->items['word_separator'] = 'underscore';
        ee()->setMock('functions', new FakeFunctions());

        require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

        Structure::$nav = '<ul id="nav_sub"><li id="nav_sub_10" class="here"><a href="https://example.com/path">Title</a></li></ul>';
        $parser = new NavParser();
        $vars = $parser->get_variables(false);

        $this->assertCount(1, $vars);
        $this->assertSame('10', $vars[0]['root:entry_id']);
        $this->assertSame('Title', $vars[0]['root:title']);
        $this->assertTrue($vars[0]['root:active']);

        Structure::$nav = '';
        $this->assertSame([], $parser->get_variables(false));
    }
}
