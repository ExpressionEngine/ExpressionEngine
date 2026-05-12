<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureCountSegmentsTest extends TestCase
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
     * Confirm the test exercises the real add-on file from PATH_ADDONS.
     *
     * @return void
     */
    public function testSqlStructureLoadsFromPathAddonsTargetFile(): void
    {
        $reflection = new ReflectionClass('Sql_structure');

        $this->assertSame(
            realpath(PATH_ADDONS . 'structure/sql.structure.php'),
            $reflection->getFileName()
        );
    }

    /**
     * It decodes slash entities and collapses repeated separators before counting segments.
     *
     * @return void
     */
    public function testCountSegmentsNormalizesSlashVariantsBeforeCounting(): void
    {
        $sql = $this->makeSql();

        $this->assertSame(2, $sql->count_segments('/alpha/beta/'));
        $this->assertSame(2, $sql->count_segments('alpha&#47;&#47;beta'));
        $this->assertSame(3, $sql->count_segments('/alpha//beta///gamma/'));
    }

    /**
     * It returns null for empty and null input instead of counting a segment.
     *
     * @return void
     */
    public function testCountSegmentsReturnsNullForEmptyAndNullInput(): void
    {
        $sql = $this->makeSql();

        $this->assertNull($sql->count_segments(''));
        $this->assertNull($sql->count_segments(null));
    }

    /**
     * It captures method-specific subprocess coverage for the reachable count_segments paths.
     *
     * @return void
     */
    public function testCountSegmentsCoverageSubprocessCoversHappyAndEmptyPaths(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-count-segments-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_count_segments_subprocess.php';
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($outputFile) . ' 2>&1';

        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertFileExists($outputFile);

        $result = json_decode(file_get_contents($outputFile), true);
        @unlink($outputFile);

        $this->assertIsArray($result);
        $this->assertSame(
            realpath(PATH_ADDONS . 'structure/sql.structure.php'),
            $result['real_module_path']
        );
        $this->assertSame(2, $result['simple_result']);
        $this->assertSame(2, $result['entity_encoded_result']);
        $this->assertSame(3, $result['repeated_slashes_result']);
        $this->assertNull($result['empty_result']);
        $this->assertNull($result['null_result']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(100.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([], $result['uncovered_paths']);
        }
    }

    /**
     * Build a Sql_structure instance without running its constructor.
     *
     * @return Sql_structure
     */
    private function makeSql(): Sql_structure
    {
        return (new ReflectionClass('Sql_structure'))->newInstanceWithoutConstructor();
    }
}
