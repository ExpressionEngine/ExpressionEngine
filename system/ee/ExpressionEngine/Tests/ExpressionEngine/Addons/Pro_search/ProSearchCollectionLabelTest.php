<?php

use ExpressionEngine\Library\Data\Collection;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ProSearchCollectionLabelTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        // The form helper reads REQ at load time; bootstrap.php does not define it.
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }
        require_once PATH_ADDONS . 'pro_search/helpers/pro_search_helper.php';
        require_once PATH_ADDONS . 'pro_search/mcp.pro_search.php';
        require_once BASEPATH . 'helpers/form_helper.php';
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public static function labelProvider(): array
    {
        $cases = [];
        foreach ([
            'ordinary' => 'Article title',
            'punctuation' => 'News & "Editor\'s" café 日本語',
            'markup' => '<span title="collection marker">Label & café</span>',
        ] as $case => $label) {
            foreach (['new', 7] as $id) {
                $cases[$id . '-' . $case] = [$id, $label];
            }
        }

        return $cases;
    }

    /** @dataProvider labelProvider */
    public function testCollectionTitleLabelsRenderAsLiteralText($id, string $label): void
    {
        $channels = [];
        foreach ([1, 2] as $channelId) {
            $channel = $this->stub(['getAllCustomFields' => new Collection()]);
            $channel->channel_id = $channelId;
            $channel->channel_name = 'channel_' . $channelId;
            $channel->channel_title = 'Channel ' . $channelId;
            $channel->title_field_label = $label;
            $channel->CategoryGroups = [];
            $channels[] = $channel;
        }

        $query = $this->getMockBuilder(stdClass::class)
            ->addMethods(['get', 'with', 'filter', 'order', 'all'])->getMock();
        foreach (['get', 'with', 'order'] as $method) {
            $query->method($method)->willReturnSelf();
        }
        $query->expects($this->exactly(3))->method('filter')
            ->with('site_id', 1)->willReturnSelf();
        $query->method('all')->willReturnOnConsecutiveCalls(
            new Collection($channels), new Collection(), new Collection()
        );
        ee()->setMock('Model', $query);
        ee()->setMock('config', $this->stub(['loadFile' => []]));
        ee()->setMock('cp', $this->stub([
            'add_js_script' => null, 'load_package_css' => null, 'load_package_js' => null,
        ]));
        ee()->setMock('javascript', $this->stub(['set_global' => null]));
        ee()->setMock('view', new stdClass());
        ee()->setMock('pro_search_settings', $this->stub(['permissions' => [], 'get' => []]));
        ee()->setMock('CP/Sidebar', $this->stub(['make' => new stdClass()]));

        // Capture the real method's form data without rendering unrelated CP chrome.
        $view = $this->getMockBuilder(stdClass::class)->addMethods(['render'])->getMock();
        $view->method('render')->willReturnArgument(0);
        ee()->setMock('View', $this->stub(['make' => $view]));

        $weight = $id === 'new' ? 0 : 2;
        $row = [
            'channel_id' => $id === 'new' ? '' : 1,
            'collection_label' => 'Articles', 'collection_name' => 'articles',
            'language' => '', 'excerpt' => '0', 'modifier' => '1',
            'settings' => $id === 'new' ? '' : '{"0":2}',
        ];
        $collection = $this->getMockBuilder(stdClass::class)
            ->addMethods(['empty_row', 'get_one'])->getMock();
        $collection->expects($id === 'new' ? $this->once() : $this->never())
            ->method('empty_row')->willReturn($row);
        $collection->expects($id === 'new' ? $this->never() : $this->once())
            ->method('get_one')->with(7)->willReturn($row);

        $mcp = $this->getMockBuilder(Pro_search_mcp::class)
            ->disableOriginalConstructor()->onlyMethods(['mcp_url'])->getMock();
        $mcp->method('mcp_url')->willReturn('collection-form');
        foreach ([
            'collection' => $collection, 'site_id' => 1, 'package' => 'pro_search',
            'member_group' => 2, 'info' => $this->stub(['getName' => 'Pro Search']),
        ] as $property => $value) {
            $reflection = new ReflectionProperty(Pro_search_mcp::class, $property);
            TestReflectionHelper::makePropertyAccessible($reflection);
            $reflection->setValue($mcp, $value);
        }

        $sections = $mcp->edit_collection($id)['body']['sections'];
        $this->assertCount(5, $sections);

        // Check every channel, including the unselected (initially hidden) group.
        foreach ([1, 2] as $channelId) {
            $excerpt = $sections[$channelId * 2 - 1]['settings']['excerpt']['fields'][0];
            $section = $sections[$channelId * 2];
            $this->assertSame('channel_' . $channelId, $section['group']);
            $this->assertCount(1, $section['settings']);
            $setting = $section['settings'][0];
            $this->assertSame('settings[' . $channelId . '][0]', $setting['fields'][0]['name']);
            $this->assertSame($weight, $setting['fields'][0]['value']);
            $this->assertSame([$label], $excerpt['choices']);
            $this->assertSame('0', $excerpt['value']);

            $document = new DOMDocument();
            $document->loadHTML('<?xml encoding="UTF-8">'
                . $this->renderFieldset($setting, $section['group'])
                . form_dropdown($excerpt['name'], $excerpt['choices'], $excerpt['value']),
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            $labels = $document->getElementsByTagName('label');
            $this->assertSame(1, $labels->length);
            $this->assertSame(0, $labels->item(0)->getElementsByTagName('*')->length,
                'A stored title label must not create HTML elements.');
            $this->assertSame($label, $labels->item(0)->textContent);
            $this->assertSame(1, $document->getElementsByTagName('option')->length);
            $this->assertSame($label, $document->getElementsByTagName('option')->item(0)->textContent);
        }
    }

    private function stub(array $returns)
    {
        $stub = $this->getMockBuilder(stdClass::class)->addMethods(array_keys($returns))->getMock();
        foreach ($returns as $method => $value) {
            $stub->method($method)->willReturn($value);
        }

        return $stub;
    }

    private function renderFieldset(array $setting, string $group): string
    {
        // The test bootstrap's lang() passes these unknown labels through, as EE does.
        $renderer = new class {
            public function embed($view, $vars) {}

            public function render($setting, $group)
            {
                ob_start();
                try {
                    include PATH_ADDONS . '../View/_shared/form/fieldset.php';

                    return ob_get_contents();
                } finally {
                    ob_end_clean();
                }
            }
        };

        return $renderer->render($setting, $group);
    }
}
