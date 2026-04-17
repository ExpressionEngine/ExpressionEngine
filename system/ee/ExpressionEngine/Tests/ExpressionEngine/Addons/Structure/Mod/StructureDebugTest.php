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

	public function testDebugDiesInSubprocessWhenRequested()
	{
		$outputFile = sys_get_temp_dir() . '/structure-debug-' . uniqid('', true) . '.json';
		$script = dirname(__DIR__, 4) . '/support/structure_debug_subprocess.php';
		$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' --mode=die ' . escapeshellarg($outputFile) . ' 2>&1';

		exec($command, $output, $exitCode);

		$this->assertSame(0, $exitCode, implode("\n", $output));
		$this->assertFileExists($outputFile);

		$result = json_decode(file_get_contents($outputFile), true);
		@unlink($outputFile);

		$this->assertIsArray($result);
		$this->assertFalse($result['returned']);
		$this->assertSame('<pre>Array' . "\n" . '(' . "\n" . '    [shutdown] => yes' . "\n" . ')' . "\n" . '</pre>', $result['output']);
		$this->assertSame(1, $result['lines']['2102']);
		$this->assertSame(1, $result['lines']['2103']);
		$this->assertSame(1, $result['lines']['2104']);
		$this->assertSame(1, $result['lines']['2106']);
		$this->assertSame(1, $result['lines']['2107']);
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
