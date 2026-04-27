<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureCreateFullUriTest extends TestCase
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
     * It preserves the create_full_uri contract across high-value vectors.
     *
     * @dataProvider createFullUriProvider
     * @param string $parentUri
     * @param string $listingUri
     * @param string $expected
     * @return void
     */
    public function testCreateFullUriNormalizesHighValueVectors(string $parentUri, string $listingUri, string $expected): void
    {
        $sql = $this->makeSql();

        $this->assertSame($expected, $sql->create_full_uri($parentUri, $listingUri));
    }

    /**
     * It captures method-specific subprocess coverage for the reachable create_full_uri path.
     *
     * @return void
     */
    public function testCreateFullUriCoverageSubprocessCoversReachablePath(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-create-full-uri-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_create_full_uri_subprocess.php';
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
        $this->assertSame('/parent/child/', $result['happy_result']);
        $this->assertSame('/parent section/childx1frag/', $result['preserved_parent_result']);
        $this->assertSame('/parent/', $result['zero_result']);
        $this->assertSame('/child/', $result['root_parent_result']);
        $this->assertSame('/', $result['root_result']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(100.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([], $result['uncovered_branches']);
        }
    }

    /**
     * Provide happy-path, boundary, and invariant vectors for create_full_uri.
     *
     * @return array
     */
    public static function createFullUriProvider(): array
    {
        return [
            'happy-path-joins-parent-and-child' => ['/parent', 'child', '/parent/child/'],
            'empty-listing-preserves-parent-with-trailing-slash' => ['/parent', '', '/parent/'],
            'empty-parent-yields-child-rooted-uri' => ['', 'child', '/child/'],
            'both-emptyish-values-collapse-to-root' => ['', '@#$%^&*()', '/'],
            'string-zero-listing-collapses-to-parent-because-create-uri-treats-zero-as-empty' => ['/parent', '0', '/parent/'],
            'parent-uri-is-not-sanitized-while-listing-uri-is-sanitized' => ['/parent section/', 'child?x=1#frag', '/parent section/childx1frag/'],
            'embedded-slashes-are-normalized-after-listing-sanitization' => ['/parent/', '/child/', '/parent/child/'],
            'root-parent-does-not-leave-a-double-slash-prefix' => ['/', 'child', '/child/'],
        ];
    }

    /**
     * Build a Sql_structure instance without running its constructor.
     *
     * @return Sql_structure
     */
    private function makeSql(): Sql_structure
    {
        $sql = (new ReflectionClass('Sql_structure'))->newInstanceWithoutConstructor();
        $sql->site_id = 1;
        $sql->cache = [];

        return $sql;
    }
}
