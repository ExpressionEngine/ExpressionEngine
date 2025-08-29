<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchFiltersTest extends Pro_searchTestBase
{
	public function testFiltersWithBasicParametersReturnsFormattedVars()
	{
		$this->setTemplateParams([]);
		$this->setTemplateTagdata('{pro_search_keywords}{pro_search_keywords:raw}');
		$this->setParamsStub(['keywords' => 'test query']);

		$result = $this->pro->filters();

		$this->assertStringContainsString('test query', $result);
		// Template variables should be replaced, so we shouldn't see the raw variables
		$this->assertStringNotContainsString('{pro_search_keywords}', $result);
		$this->assertStringNotContainsString('{pro_search_keywords:raw}', $result);
	}

	public function testFiltersWithShortcutAddsShortcutVars()
	{
		$this->setTemplateParams([]);
		$this->setTemplateTagdata('{pro_search_shortcut_name}{pro_search_shortcut_label}');

		// Mock shortcut model to return shortcut data
		ee()->setMock('pro_search_shortcut_model', new class {
			public function get_template_attrs(){
				return ['shortcut_name', 'shortcut_label'];
			}
			public function get_one($v, $a){
				return [
					'shortcut_name' => 'test_shortcut',
					'shortcut_label' => 'Test Shortcut',
					'parameters' => ['keywords' => 'shortcut query']
				];
			}
		});

		$this->setTemplateParams(['shortcut' => 'test_shortcut']);
		$this->setParamsStub([]);

		$result = $this->pro->filters();

		$this->assertStringContainsString('test_shortcut', $result);
		$this->assertStringContainsString('Test Shortcut', $result);
	}

	public function testFiltersWithNoShortcutDoesNotAddShortcutVars()
	{
		$this->setTemplateParams([]);
		$this->setTemplateTagdata('{pro_search_shortcut_name} and some content');

		// Mock shortcut model to return no shortcut
		ee()->setMock('pro_search_shortcut_model', new class {
			public function get_template_attrs(){
				return ['shortcut_name'];
			}
			public function get_one($v, $a){
				return false;
			}
		});

		$this->setParamsStub([]);

		$result = $this->pro->filters();

		// Since no shortcut is found, the variable should be replaced with empty string
		// and the template parser removes empty variables, so we should see the remaining content
		$this->assertStringContainsString(' and some content', $result);
		$this->assertStringNotContainsString('{pro_search_shortcut_name}', $result);
	}

	public function testFiltersCanProcessTemplateVariables()
	{
		$this->setTemplateParams([]);
		$this->setTemplateTagdata('{pro_search_keywords} and {pro_search_collection}');

		$this->setParamsStub(['keywords' => 'search term', 'collection' => 'news']);

		$result = $this->pro->filters();

		$this->assertStringContainsString('search term', $result);
		$this->assertStringContainsString('news', $result);
	}






	public function testFiltersWithFlashDataErrorMessage()
	{
		$this->setTemplateParams([]);
		$this->setTemplateTagdata('{pro_search_error_message}');

		$this->setParamsStub([]);

		$result = $this->pro->filters();

		// Without flash data, error message variable should be replaced with empty string
		$this->assertSame('', $result);
	}

	public function testFiltersWithFieldSpecificErrors()
	{
		$this->setTemplateParams([]);
		$this->setTemplateTagdata('{pro_search_keywords_missing}{pro_search_collection_missing}');

		$this->setParamsStub([]);

		$result = $this->pro->filters();

		// Without flash data errors, the variables should be replaced with empty strings
		$this->assertSame('', $result);
	}

	public function testFiltersWithArrayFieldErrors()
	{
		$this->setTemplateParams([]);
		$this->setTemplateTagdata('{pro_search_keywords_missing}{pro_search_collection_missing}');

		// Mock session to return array errors
		$this->setMock('session', new class {
			public $flashdata = ['errors' => ['keywords', 'collection']];
			public function flashdata($key){ return $this->flashdata[$key] ?? null; }
			public function set_flashdata($key,$val){ $this->flashdata[$key] = $val; }
			public function userdata($key){ return null; }
			public function set_flashdata_data(array $a){ foreach($a as $k=>$v){ $this->set_flashdata($k,$v); } }
		});

		$this->setParamsStub([]);

		$result = $this->pro->filters();

		// Variables should be replaced with boolean true values
		$this->assertStringNotContainsString('{pro_search_keywords_missing}', $result);
		$this->assertStringNotContainsString('{pro_search_collection_missing}', $result);
		$this->assertStringContainsString('1', $result); // boolean true becomes '1' in template
	}

	public function testFiltersWithEmptyCollectionsReturnsEmptyCollectionsVar()
	{
		$this->setTemplateParams([]);
		$this->setTemplateTagdata('{pro_search_collections}');

		$this->mockEmptyCollectionModel();

		$this->setParamsStub([]);

		$result = $this->pro->filters();

		// collections variable should be replaced with empty string when no collections
		$this->assertSame('', $result);
	}

	public function testFiltersCallsLanguageLoadfile()
	{
		$this->setTemplateParams([]);
		$this->setTemplateTagdata('test');

		$langLoaded = false;
		$this->setMock('lang', new class($langLoaded) {
			private $loaded;
			public function __construct(&$loaded){ $this->loaded = &$loaded; }
			public function loadfile($file){ $this->loaded = $file; }
			public function line($key){ return $key; }
		});

		$this->setParamsStub([]);

		$this->pro->filters();

		$this->assertEquals('pro_search', $langLoaded);
	}


}