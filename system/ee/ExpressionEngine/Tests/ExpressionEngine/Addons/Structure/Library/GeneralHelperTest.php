<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/General_helper.php';

use PHPUnit\Framework\TestCase;

class GeneralHelperTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        ee()->setMock('view', new class {
            public $cp_page_title = 'Structure CP';
        });
        ee()->setMock('View', new class {
            public function make($template)
            {
                return new class($template) {
                    private $template;
                    public function __construct($template) { $this->template = $template; }
                    public function render($vars) { return 'render:' . $this->template . ':' . ($vars['cp_page_title'] ?? ''); }
                };
            }
            public function makeFromString($string)
            {
                return new class($string) {
                    private $string;
                    public function __construct($string) { $this->string = $string; }
                    public function render($vars) { return 'string:' . $this->string . ':' . ($vars['cp_page_title'] ?? ''); }
                };
            }
        });
        ee()->setMock('CP/URL', new class {
            public function compile()
            {
                return 'compiled://addons/settings/structure/';
            }
            public function make($path, $vars = [])
            {
                return 'cp://' . $path . '?' . http_build_query($vars);
            }
        });
    }

    public function testViewUsesTemplateAndDefaultVars()
    {
        $helper = new General_helper();
        $result = $helper->view('index', [], false, false);

        $this->assertSame('Structure CP', $result['heading']);
        $this->assertSame('Structure', $result['breadcrumb']['compiled://addons/settings/structure/']);
        $this->assertStringContainsString('render:structure:index:Structure CP', $result['body']);
    }

    public function testViewUsesStringTemplateWhenProvided()
    {
        $helper = new General_helper();
        $result = $helper->view('ignored', [], false, 'literal');

        $this->assertStringContainsString('string:literal:Structure CP', $result['body']);
    }

    public function testGetBaseUrlHandlesSlashAndMethod()
    {
        $helper = new General_helper();

        $this->assertInstanceOf(eeSingletonMock::class, $helper->getBaseURL('/'));
        $this->assertInstanceOf(eeSingletonMock::class, $helper->getBaseURL('history', '?x=1'));
    }

    public function testCpUrlHandlesListingPublishCreateAndEditModes()
    {
        $helper = new General_helper();

        $listing = $helper->cpURL('listing', '', []);
        $create = $helper->cpURL('publish', 'create', ['channel_id' => 3, 'foo' => 'bar']);
        $edit = $helper->cpURL('publish', 'edit', ['entry_id' => 99, 'foo' => 'bar']);

        $this->assertSame('cp://publish?', (string) $listing);
        $this->assertSame('cp://publish/create/3?foo=bar', (string) $create);
        $this->assertSame('cp://publish/edit/entry/99?foo=bar', (string) $edit);
    }
}
