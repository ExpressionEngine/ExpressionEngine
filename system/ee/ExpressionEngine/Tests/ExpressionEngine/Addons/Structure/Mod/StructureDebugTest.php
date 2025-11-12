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
}
