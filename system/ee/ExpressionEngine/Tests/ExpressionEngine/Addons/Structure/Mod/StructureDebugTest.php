<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureDebugTest extends StructureTestBase
{
	public function testDebugOutputsPreAndPrintR()
	{
		// Capture output
		ob_start();
		$this->structure->debug(['a' => 1, 'b' => 2], false);
		$out = ob_get_clean();

		$this->assertStringContainsString('<pre>', $out);
		$this->assertStringContainsString('</pre>', $out);
		$this->assertStringContainsString("Array", $out);
		$this->assertStringContainsString("[a] => 1", $out);
	}

	public function testDebugHandlesScalar()
	{
		ob_start();
		$this->structure->debug('hello', false);
		$out = ob_get_clean();
		$this->assertStringContainsString('hello', $out);
	}

	public function testConstructorInitializesSqlAndNestedSet()
	{
		ee()->setMock('pagination', new class {
			public function create()
			{
				return new stdClass();
			}
		});
		ee()->setMock('addons_model', new class {
			public function module_installed($name)
			{
				return true;
			}
		});

		$real = new Structure();
		$this->assertInstanceOf(Sql_structure::class, $real->sql);
		$this->assertNotNull($real->nset);
	}
}
