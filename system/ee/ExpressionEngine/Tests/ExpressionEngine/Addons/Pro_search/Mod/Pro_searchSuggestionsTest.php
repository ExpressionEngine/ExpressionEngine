<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchSuggestionsTest extends Pro_searchTestBase
{
	public function testSuggestionsReturnsNoResultsWhenNoWords()
	{
		$this->setTemplateTagdata('x');
		$this->setTemplateParams(['keywords' => '   ']);
		$wordsLib = new class {
			public function clean($s){ return trim($s); }
			public function is_valid($w){ return strlen($w) > 0; }
		};
		ee()->setMock('pro_search_words', $wordsLib);
		ee()->setMock('pro_search_word_model', new class {
			public function get_unknown($k,$l,$s){ return []; }
		});
		$out = $this->pro->suggestions();
		$this->assertSame('NO_RESULTS', $out);
	}

	public function testSuggestionsParsesSuggestedWords()
	{
		$this->setTemplateParams(['keywords' => 'alpha beta']);
		$this->setTemplateTagdata('{suggestion}');
		ee()->setMock('pro_search_words', new class { public function clean($s){ return $s; } public function is_valid($w){ return true; } });
		ee()->setMock('pro_search_word_model', new class {
			public function get_unknown($k,$l,$s){ return ['alpha','beta']; }
			public function get_suggestions($words,$lang,$sites,$distance,$limit){ return ['alfa','betta']; }
		});
		$out = $this->pro->suggestions();
		$this->assertSame('alfabetta', $out);
	}
}


