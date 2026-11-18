<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureCreatePageUriTest extends TestCase
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
     * It preserves the create_page_uri contract across high-value sanitizer vectors.
     *
     * @dataProvider createPageUriProvider
     * @param string $parentUri
     * @param string $pageUri
     * @param string $expected
     * @return void
     */
    public function testCreatePageUriNormalizesHighValueVectors(string $parentUri, string $pageUri, string $expected): void
    {
        $sql = $this->makeSql();

        $this->assertSame($expected, $sql->create_page_uri($parentUri, $pageUri));
    }

    /**
     * It captures method-specific subprocess coverage for the reachable create_page_uri path.
     *
     * @return void
     */
    public function testCreatePageUriCoverageSubprocessCoversReachablePath(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-create-page-uri-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_create_page_uri_subprocess.php';
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
        $this->assertSame('/parentsection/childx1frag/', $result['sanitized_result']);
        $this->assertSame('/mlaut/nio/', $result['unicode_result']);
        $this->assertSame('/', $result['root_result']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(100.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([], $result['uncovered_branches']);
        }
    }

    /**
     * Provide happy-path, boundary, and sanitizer vectors for create_page_uri.
     *
     * @return array
     */
    public static function createPageUriProvider(): array
    {
        return [
            'happy-path-joins-parent-and-child' => ['/parent', 'child', '/parent/child/'],
            'empty-child-preserves-parent-with-trailing-slash' => ['/parent', '', '/parent/'],
            'empty-parent-yields-child-rooted-uri' => ['', 'child', '/child/'],
            'both-empty-collapse-to-root' => ['', '', '/'],
            'string-zero-segments-are-preserved' => ['0', '0', '/0/0/'],
            'disallowed-punctuation-is-stripped-from-both-segments' => ['/parent section/', 'child?x=1#frag', '/parentsection/childx1frag/'],
            'embedded-slashes-are-removed-before-join' => ['/a//b', 'c//d', '/ab/cd/'],
            'allowed-punctuation-remains-intact' => ['parent.name', 'child_slug-1', '/parent.name/child_slug-1/'],
            'unicode-is-dropped-without-transliteration' => ['/ümlaut', 'niño', '/mlaut/nio/'],
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
