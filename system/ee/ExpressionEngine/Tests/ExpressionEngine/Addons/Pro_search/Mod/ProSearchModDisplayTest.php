<?php

require_once __DIR__ . '/../ProSearchTestBase.php';
require_once PATH_ADDONS . 'pro_search/mod.pro_search.php';

class ProSearchModDisplayTest extends ProSearchTestBase
{
    protected $mod;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock App container
        $appInfo = $this->getMockBuilder('stdClass')
            ->addMethods(['getVersion'])
            ->getMock();
        $appInfo->method('getVersion')->willReturn('1.0.0');
        
        $app = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
        $app->method('get')->willReturn($appInfo);
        ee()->setMock('App', $app);
        
        // Mock Params
        $params = $this->getMockBuilder('stdClass')
            ->addMethods(['set', 'get', 'explode', 'site_ids', 'get_prefixed', 'get_vars'])
            ->getMock();
        $params->method('set')->willReturn(null);
        $params->method('get')->will($this->returnCallback(function($key = null) {
            if ($key === null) {
                return [];
            }
            return null;
        }));
        $params->method('explode')->willReturn([[], true]);
        $params->method('site_ids')->willReturn([1]);
        $params->method('get_prefixed')->willReturn([]);
        $params->method('get_vars')->willReturn([]);
        ee()->setMock('pro_search_params', $params);
        
        // Mock Settings - already set in parent setUp(), but ensure it uses test class
        // The parent setUp() already sets pro_search_settings with Pro_search_settings_test
        
        // Mock Functions
        $functions = $this->getMockBuilder('stdClass')
            ->addMethods(['fetch_action_id', 'form_declaration', 'create_url', 'redirect'])
            ->getMock();
        $functions->method('fetch_action_id')->willReturn('123');
        $functions->method('form_declaration')->willReturn('<form>');
        $functions->method('create_url')->willReturn('http://example.com');
        ee()->setMock('functions', $functions);
        
        // Mock Shortcut Model
        $shortcutModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get_one', 'get_template_attrs', 'table'])
            ->getMock();
        $shortcutModel->method('get_one')->willReturn(false);
        $shortcutModel->method('get_template_attrs')->willReturn([]);
        $shortcutModel->method('table')->willReturn('exp_pro_search_shortcuts');
        ee()->setMock('pro_search_shortcut_model', $shortcutModel);
        
        // Mock Collection Model
        $colModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get_by_site', 'get_by_param', 'get_by_language'])
            ->getMock();
        $colModel->method('get_by_site')->willReturn([]);
        ee()->setMock('pro_search_collection_model', $colModel);
        
        $this->mod = new Pro_search();
    }

    public function testFilters()
    {
        ee()->TMPL->tagdata = '{pro_search:keywords}';
        ee()->TMPL->tagparams = [];
        ee()->TMPL->var_pair = [];
        
        $result = $this->mod->filters();
        
        $this->assertIsString($result);
    }

    public function testForm()
    {
        ee()->TMPL->tagdata = '<input name="keywords">';
        ee()->TMPL->tagparams = [];
        
        $result = $this->mod->form();
        
        $this->assertIsString($result);
        $this->assertStringContainsString('<form', $result);
        $this->assertStringContainsString('</form>', $result);
    }

    public function testFormAddsSignedParamsWhenParamsArePresent()
    {
        $captured = null;

        $functions = $this->getMockBuilder('stdClass')
            ->addMethods(['fetch_action_id', 'form_declaration', 'create_url', 'redirect'])
            ->getMock();
        $functions->method('fetch_action_id')->willReturn('123');
        $functions->method('create_url')->willReturn('http://example.com');
        $functions->method('form_declaration')->willReturnCallback(function ($data) use (&$captured) {
            $captured = $data;
            return '<form>';
        });
        ee()->setMock('functions', $functions);

        ee()->config->setItem('encryption_key', 'unit-test-key');

        // Include one overridable param so the form emits the encoded payload.
        ee()->TMPL->tagdata = '<input name="keywords">';
        ee()->TMPL->tagparams = ['result_page' => 'https://example.org/search'];
        ee()->TMPL->setMap(['result_page' => 'https://example.org/search']);

        $this->mod = new Pro_search();
        $this->mod->form();

        $this->assertIsArray($captured);
        $this->assertArrayHasKey('hidden_fields', $captured);
        $this->assertArrayHasKey('params', $captured['hidden_fields']);
        $this->assertArrayHasKey('sig', $captured['hidden_fields']);
        $this->assertNotEmpty($captured['hidden_fields']['sig']);
    }

    public function testShortcuts()
    {
        $rows = [
            ['shortcut_id' => '1', 'shortcut_name' => 'test', 'shortcut_label' => 'Test', 'sort_order' => '1']
        ];
        ee()->db->setRows($rows);
        
        ee()->TMPL->tagdata = '{shortcut_name}';
        ee()->TMPL->tagparams = [];
        ee()->TMPL->site_ids = [1];
        
        $result = $this->mod->shortcuts();
        
        $this->assertIsString($result);
    }

    public function testKeywords()
    {
        $params = ee()->pro_search_params;
        $params->method('get')->willReturn('test keywords');
        
        ee()->TMPL->tagdata = '';
        ee()->TMPL->tagparams = [];
        
        $result = $this->mod->keywords();
        
        $this->assertIsString($result);
    }

    public function testParam()
    {
        $params = ee()->pro_search_params;
        $params->method('get')->willReturn('test value');
        
        ee()->TMPL->tagdata = '';
        ee()->TMPL->tagparams = ['get' => 'keywords'];
        
        $result = $this->mod->param('keywords');
        
        $this->assertIsString($result);
    }

    public function testPopular()
    {
        $logModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get_popular_keywords'])
            ->getMock();
        $logModel->method('get_popular_keywords')->willReturn([
            ['keywords' => 'test', 'search_count' => 5]
        ]);
        ee()->setMock('pro_search_log_model', $logModel);
        
        ee()->db->setRows([]);
        ee()->TMPL->tagdata = '{keywords}';
        ee()->TMPL->tagparams = [];
        
        $result = $this->mod->popular();
        
        $this->assertIsString($result);
    }

    public function testUrl()
    {
        $params = $this->getMockBuilder('stdClass')
            ->addMethods(['reset', 'set', 'get'])
            ->getMock();
        $params->method('reset')->willReturn(null);
        $params->method('set')->willReturn(null);
        $params->method('get')->will($this->returnCallback(function($key = null) {
            if ($key === null) {
                return [];
            }
            return null;
        }));
        ee()->setMock('pro_search_params', $params);
        
        $settings = ee()->pro_search_settings;
        $settings->method('get')->willReturn('n'); // encode_query = 'n'
        
        ee()->TMPL->tagdata = '';
        ee()->TMPL->tagparams = [];
        
        $result = $this->mod->url();
        
        $this->assertIsString($result);
    }

    public function testSuggestions()
    {
        $params = ee()->pro_search_params;
        $params->method('get')->willReturn('test');
        $params->method('site_ids')->willReturn([1]);
        
        $words = $this->getMockBuilder('stdClass')
            ->addMethods(['clean', 'is_valid'])
            ->getMock();
        $words->method('clean')->willReturn('test');
        $words->method('is_valid')->willReturn(true);
        ee()->setMock('pro_search_words', $words);
        
        $wordModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get_unknown', 'get_suggestions'])
            ->getMock();
        $wordModel->method('get_unknown')->willReturn(['test']);
        $wordModel->method('get_suggestions')->willReturn(['test', 'tests']);
        ee()->setMock('pro_search_word_model', $wordModel);
        
        ee()->TMPL->tagdata = '{suggestion}';
        ee()->TMPL->tagparams = [];
        
        $result = $this->mod->suggestions();
        
        $this->assertIsString($result);
    }

    public function testCollections()
    {
        $colModel = ee()->pro_search_collection_model;
        $colModel->method('get_by_site')->willReturn([
            ['collection_id' => '1', 'collection_name' => 'news', 'collection_label' => 'News', 'language' => 'en']
        ]);
        $colModel->method('get_by_param')->willReturn([]);
        $colModel->method('get_by_language')->willReturn([]);
        
        $params = ee()->pro_search_params;
        $params->method('explode')->willReturn([['en'], true]);
        
        ee()->TMPL->tagdata = '{collection_name}';
        ee()->TMPL->tagparams = [];
        ee()->TMPL->site_ids = [1];
        
        $result = $this->mod->collections();
        
        $this->assertIsString($result);
    }
}
