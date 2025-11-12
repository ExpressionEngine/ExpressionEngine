<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureGetDataTest extends StructureTestBase
{
	public function testGetDataDelegatesToSql()
	{
		$expected = [
			10 => ['title' => 'Home'],
			11 => ['title' => 'About'],
		];
		// Stub sql with get_data
		$this->structure->sql = new class($expected) {
			private $data; public function __construct($d){$this->data=$d;} public function get_data(){return $this->data;}
		};

		$this->assertSame($expected, $this->structure->get_data());
	}

	public function testGetDataReturnsEmptyArray()
	{
		$this->structure->sql = new class {
			public function get_data(){ return []; }
		};
		$this->assertSame([], $this->structure->get_data());
	}
}
