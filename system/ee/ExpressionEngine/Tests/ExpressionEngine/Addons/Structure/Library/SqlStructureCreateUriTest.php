<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureCreateUriTest extends TestCase
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
     * It preserves the create_uri contract across the high-value sanitizer vectors.
     *
     * @dataProvider createUriProvider
     * @param string $uri
     * @param string $urlTitle
     * @param string $expected
     * @return void
     */
    public function testCreateUriSanitizesHighValueVectors(string $uri, string $urlTitle, string $expected): void
    {
        $sql = $this->makeSql();

        $this->assertSame($expected, $sql->create_uri($uri, $urlTitle));
    }

    /**
     * Provide branch, boundary, and output invariant vectors for create_uri.
     *
     * @return array
     */
    public static function createUriProvider(): array
    {
        return [
            'empty-uri-falls-back-to-url-title' => ['', 'my-title', 'my-title'],
            'string-zero-falls-back-because-php-treats-it-as-empty' => ['0', 'fallback-title', 'fallback-title'],
            'whitespace-only-uri-is-truthy-and-sanitizes-to-empty' => ['   ', 'fallback-title', ''],
            'invalid-characters-are-stripped' => ['Hello World!@#$', 'fallback', 'HelloWorld'],
            'leading-and-trailing-underscores-are-trimmed' => ['_hello_world_', 'ignored', 'hello_world'],
            'case-and-middle-underscores-are-preserved' => ['Hello___World', 'fallback', 'Hello___World'],
            'unicode-is-dropped-without-transliteration' => ['Málaga', '', 'Mlaga'],
            'allowed-punctuation-is-preserved' => ['a-b.c_d', '', 'a-b.c_d'],
            'all-underscores-trim-to-empty' => ['____', '', ''],
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
