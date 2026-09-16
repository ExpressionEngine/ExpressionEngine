<?php

use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Library\CP\URL;
use PHPUnit\Framework\TestCase;

class ProSearchGroupViewReady extends RuntimeException {}
class ProSearchGroupSaveDenied extends RuntimeException {}

/**
 * Isolate legacy helper definitions, but retain form_prep()'s cache across rows.
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ProSearchGroupLabelRegressionTest extends TestCase
{
    private $mcp;
    private $groups;

    protected function setUp(): void
    {
        ee()->resetMocks();
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }
        if (!defined('CSRF_TOKEN')) {
            define('CSRF_TOKEN', 'benign-test-token');
        }
        if (!function_exists('bool_config_item')) {
            function bool_config_item($key) { return false; }
        }
        if (!function_exists('show_error')) {
            function show_error($message, $status = 500, $heading = 'Error')
            {
                throw new ProSearchGroupSaveDenied();
            }
        }
        require_once BASEPATH . 'helpers/form_helper.php';
        require_once PATH_ADDONS . 'pro_search/mcp.pro_search.php';

        $this->mcp = $this->getMockBuilder(Pro_search_mcp::class)
            ->disableOriginalConstructor()->onlyMethods(['mcp_url'])->getMock();
        $this->mcp->method('mcp_url')->willReturn('groups');
        $this->groups = $this->stub(['get_one', 'get_by_site', 'insert', 'update']);
        $shortcuts = $this->stub(['get_by_group', 'get_group_counts']);
        $shortcuts->method('get_by_group')->willReturn([]);
        $shortcuts->method('get_group_counts')->willReturn([]);
        foreach (['groups' => $this->groups, 'shortcuts' => $shortcuts,
            'site_id' => 1, 'member_group' => 5] as $name => $value) {
            $property = new ReflectionProperty(Pro_search_mcp::class, $name);
            TestReflectionHelper::makeAccessible($property);
            $property->setValue($this->mcp, $value);
        }

        ee()->setMock('view', new stdClass());
        // Stop at page chrome; the tests render the actual label-bearing views.
        ee()->setMock('cp', new class {
            public function add_js_script($scripts) {}
            public function load_package_css($package) { throw new ProSearchGroupViewReady(); }
        });
        $uri = $this->stub(['reformat']);
        $uri->method('reformat')->willReturn('groups');
        ee()->setMock('uri', $uri);
        $load = $this->stub(['view', 'is_loaded']);
        $load->method('view')->willReturn(''); // Unrelated toolbar icons.
        $load->method('is_loaded')->willReturn(false);
        ee()->setMock('load', $load);
        ee()->setMock('CP/Alert', $this->stub(['getAllInlines']));
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public static function labels(): array
    {
        return [
            'ordinary' => ['Editorial searches'],
            'ampersand' => ['A & b'],
            'entity' => ['A &amp; b'],
            'nested entity' => ['A &amp;amp; b'],
            'quotes' => ['The "editor\'s" searches'],
            'entities' => ['Named &copy; &quot; and numeric &#38; &#x26;'],
            'markup' => ['EE <em data-ee-marker="benign">marker</em>'],
            'unicode' => ['Café 日本語 🌱'],
        ];
    }

    /** @dataProvider labels */
    public function testShortcutHeadingDisplaysStoredLabelLiterally($label): void
    {
        $this->groups->method('get_one')->willReturn(['group_id' => 7, 'group_label' => $label]);
        $data = $this->pageData('shortcuts', 7);
        unset($data['remove_url']); // Modal registration is unrelated to the heading.
        $document = $this->parse($this->render(PATH_ADDONS . 'pro_search/views/list.php', $data));
        $heading = $document->getElementsByTagName('h1')->item(0);
        $this->assertNotNull($heading);
        $this->assertSame(0, $heading->getElementsByTagName('*')->length);
        $this->assertSame($label, $heading->textContent);
    }

    /** @dataProvider labels */
    public function testEditFieldKeepsStoredLabelReadable($label): void
    {
        $this->groups->method('get_one')->willReturn(['group_id' => 7, 'group_label' => $label]);
        $data = $this->pageData('edit_group', 7);
        $field = $data['sections'][0][0]['fields']['group_label'];
        $this->assertSame($label, $field['value']);
        $document = $this->parse($this->render(PATH_ADDONS . '../View/_shared/form/field.php', [
            'field_name' => 'group_label', 'field' => $field,
            'grid' => false, // Supplied by the parent fieldset in the ordinary edit form.
        ]));
        $this->assertSame($label, $document->getElementsByTagName('input')->item(0)->getAttribute('value'));
    }

    /**
     * Preserve the stored label when creating a quick link from the page title.
     *
     * @param string $label
     * @return void
     * @dataProvider labels
     */
    public function testShortcutPageTitlePreservesQuicklinkName($label): void
    {
        $this->groups->method('get_one')->willReturn(['group_id' => 7, 'group_label' => $label]);
        $this->pageData('shortcuts', 7);
        $this->assertSame($label, ee()->view->cp_page_title);

        $url = new URL('members/profile/quicklinks/create', null, [
            'name' => ee()->view->cp_page_title
        ]);
        parse_str(parse_url($url->compile(), PHP_URL_QUERY), $query);
        $this->assertSame($label, $query['name']);

        $document = $this->parse($this->render(PATH_ADDONS . '../View/_shared/form/field.php', [
            'field_name' => 'name',
            'field' => ['type' => 'text', 'value' => $query['name']],
            'grid' => false,
        ]));
        $this->assertSame($label, $document->getElementsByTagName('input')->item(0)->getAttribute('value'));
    }

    public static function confirmationBatches(): array
    {
        $entities = ['A & b', 'A &amp; b', 'A &amp;amp; b'];
        $batches = [];
        foreach ([[0, 1, 2], [0, 2, 1], [1, 0, 2], [1, 2, 0], [2, 0, 1], [2, 1, 0]] as $order) {
            $batches['entity order ' . implode('', $order)] = [array_map(function ($i) use ($entities) {
                return $entities[$i];
            }, $order)];
        }
        $labels = self::labels();
        $mixed = [$labels['markup'][0], $labels['quotes'][0], $labels['entities'][0], $labels['unicode'][0]];
        $batches['ordinary'] = [[$labels['ordinary'][0]]];
        $batches['mixed'] = [$mixed];
        $batches['mixed reversed'] = [array_reverse($mixed)];
        $batches['repeated entities'] = [array_merge($entities, [$entities[0], $entities[1]])];
        return $batches;
    }

    /** @dataProvider confirmationBatches */
    public function testTableAndRemovalConfirmationPreserveEveryLabel($labels): void
    {
        $rows = [];
        foreach ($labels as $i => $label) {
            $rows[] = ['group_id' => $i + 1, 'group_label' => $label];
        }
        $this->groups->method('get_by_site')->willReturn($rows);
        $data = $this->pageData('groups');
        // One render, with no form_prep() reset between any of these rows.
        $document = $this->parse($this->render(PATH_ADDONS . '../View/_shared/table.php', $data['table']));
        $xpath = new DOMXPath($document);
        $cells = $xpath->query('//tbody/tr/td[2]/a');
        $checkboxes = $xpath->query('//input[@name="group_id[]"]');
        $this->assertSame(count($labels), $cells->length);
        $this->assertSame(count($labels), $checkboxes->length);
        $items = [];
        foreach ($labels as $i => $label) {
            $this->assertSame($label, $cells->item($i)->textContent);
            $this->assertSame(0, $cells->item($i)->getElementsByTagName('*')->length);
            // Attribute parsing followed by HTML insertion, as in confirm_remove.js.
            $items[] = '<li>' . $checkboxes->item($i)->getAttribute('data-confirm') . '</li>';
        }
        // Check each individual selection and the full selection (fewer than six).
        $indices = array_keys($labels);
        $selections = array_map(function ($i) { return [$i]; }, $indices);
        $selections[] = $indices;
        foreach ($selections as $selection) {
            $html = implode('', array_map(function ($i) use ($items) { return $items[$i]; }, $selection));
            $confirmation = $this->parse('<ul>' . $html . '</ul>');
            $list = $confirmation->getElementsByTagName('li');
            $this->assertSame(count($selection), $list->length);
            foreach ($selection as $i => $labelIndex) {
                $node = $list->item($i);
                $this->assertSame($labels[$labelIndex], $node->textContent);
                $elements = $node->getElementsByTagName('*');
                $this->assertLessThanOrEqual(1, $elements->length);
                foreach ($elements as $element) {
                    $this->assertSame('span', $element->tagName);
                    $this->assertSame(0, $element->attributes->length);
                }
            }
        }
    }

    public static function saves(): array
    {
        $cases = [];
        foreach (['new', 7] as $id) {
            foreach ([false, true] as $allowed) {
                $cases[$id . ($allowed ? ' allowed' : ' denied')] = [$id, $allowed];
            }
        }
        return $cases;
    }

    /** @dataProvider saves */
    public function testSaveRequiresShortcutPermissionBeforePersistence($id, $allowed): void
    {
        $settings = $this->stub(['get']);
        $settings->method('get')->with('can_manage_shortcuts')->willReturn($allowed ? [5] : []);
        ee()->setMock('pro_search_settings', $settings);
        $input = $this->stub(['post']);
        $label = 'Editorial &amp; <em data-ee-marker="benign">marker</em>';
        $input->method('post')->willReturnMap([['group_id', $id], ['group_label', ' ' . $label . ' ']]);
        ee()->setMock('input', $input);
        foreach (['insert', 'update'] as $method) {
            $writes = $allowed && $method === ($id === 'new' ? 'insert' : 'update');
            $expectation = $this->groups->expects($writes ? $this->once() : $this->never())->method($method);
            if ($writes) {
                $data = ['site_id' => 1, 'group_label' => $label];
                $expectation->with(...($method === 'insert' ? [$data] : [$id, $data]));
            }
        }
        $session = $this->stub(['set_flashdata']);
        $session->expects($allowed ? $this->once() : $this->never())->method('set_flashdata');
        ee()->setMock('session', $session);
        $functions = $this->stub(['redirect']);
        $functions->expects($allowed ? $this->once() : $this->never())->method('redirect');
        ee()->setMock('functions', $functions);
        if (!$allowed) {
            $this->expectException(ProSearchGroupSaveDenied::class);
        }
        $this->mcp->save_group();
    }

    private function stub(array $methods)
    {
        return $this->getMockBuilder(stdClass::class)->addMethods($methods)->getMock();
    }

    private function pageData($method, ...$arguments): array
    {
        ee()->setMock('CP/Table', new Table(['sortable' => false]));
        try {
            $this->mcp->$method(...$arguments);
            $this->fail('Expected the controller to reach its view.');
        } catch (ProSearchGroupViewReady $ready) {
            $property = new ReflectionProperty(Pro_search_mcp::class, 'data');
            TestReflectionHelper::makeAccessible($property);
            return $property->getValue($this->mcp);
        }
    }

    public function embed($view, $data): void
    {
        $this->assertSame('ee:_shared/table', $view);
        echo $this->render(PATH_ADDONS . '../View/_shared/table.php', $data);
    }

    private function render($path, array $vars): string
    {
        extract($vars, EXTR_SKIP);
        ob_start();
        try {
            include $path;
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    private function parse($html): DOMDocument
    {
        $document = new DOMDocument();
        $this->assertTrue($document->loadHTML(
            '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>' . $html . '</body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        ));
        return $document;
    }
}
