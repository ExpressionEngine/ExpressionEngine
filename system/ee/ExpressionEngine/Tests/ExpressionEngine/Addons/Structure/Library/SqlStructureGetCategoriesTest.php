<?php

use PHPUnit\Framework\TestCase;

class SqlStructureGetCategoriesTest extends TestCase
{
    /**
     * Reset singleton mocks between test runs.
     *
     * @return void
     */
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    /**
     * It queries categories, fetches rows, warns on the debug variable, and exits before returning.
     *
     * @return void
     */
    public function testGetCategoriesExecutesRealModuleMethodAndExitsAfterDebugOutput(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-get-categories-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_get_categories_subprocess.php';
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($outputFile) . ' 2>&1';

        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertFileExists($outputFile);

        $result = json_decode(file_get_contents($outputFile), true);
        @unlink($outputFile);

        $this->assertIsArray($result);
        $this->assertFalse($result['returned']);
        $this->assertSame(
            realpath(PATH_ADDONS . 'structure/sql.structure.php'),
            $result['real_module_path']
        );
        $this->assertSame(
            ["SELECT * from exp_categories where group_id=7"],
            $result['queries']
        );
        $this->assertSame(1, $result['result_array_calls']);
        $this->assertStringContainsString('Undefined variable', $result['output']);
        $this->assertStringContainsString(
            'sql.structure.php on line ' . $result['line_numbers']['warning_line'],
            $result['output']
        );

        $lineCoverage = $result['lines'] ?? [];

        if (($result['xdebug_available'] ?? false) && ! empty($lineCoverage)) {
            foreach (['sql_line', 'query_line', 'result_array_line', 'header_line', 'warning_line'] as $lineKey) {
                $this->assertSame(1, $lineCoverage[$result['line_numbers'][$lineKey]] ?? null);
            }

            $this->assertSame(-2, $lineCoverage[$result['line_numbers']['return_line']] ?? null);
            $this->assertSame(1, $result['branches'][0]['hit'] ?? null);
        }
    }
}
