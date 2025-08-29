<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchShortcutsTest extends Pro_searchTestBase
{
	public function testShortcutsReturnsNoResultsWhenEmpty()
	{
		$this->setTemplateParams([]);
		$this->setTemplateTagdata('x');

		// db builder mock used by shortcuts()
		ee()->setMock('db', new class {
			public function select($s){ return $this; }
			public function from($t){ return $this; }
			public function where_in($k, $v){ return $this; }
			public function order_by($k,$v){ return $this; }
			public function limit($l,$o=0){ return $this; }
			public function get(){ return new class { public function result_array(){ return []; } }; }
		});

		$out = $this->pro->shortcuts();
		$this->assertSame('NO_RESULTS', $out);
	}

	public function testShortcutsParsesRows()
	{
		$this->setTemplateTagdata('{shortcut_name}{sort_order}');
		// Provide one row
		ee()->setMock('db', new class {
			private $rows = [
				['shortcut_id'=>1,'shortcut_name'=>'foo','shortcut_label'=>'Foo','parameters'=>'e30=','sort_order'=>1]
			];
			public function select($s){ return $this; }
			public function from($t){ return $this; }
			public function where_in($k, $v){ return $this; }
			public function order_by($k,$v){ return $this; }
			public function limit($l,$o=0){ return $this; }
			public function get(){ $r = $this->rows; return new class($r){ private $r; public function __construct($r){$this->r=$r;} public function result_array(){ return $this->r; } }; }
		});

		$out = $this->pro->shortcuts();
		$this->assertStringContainsString('foo1', $out);
	}
}


