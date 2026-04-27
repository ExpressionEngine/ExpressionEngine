<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureSetChannelIdsTest extends TestCase
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
     * It issues the expected update query and preserves the null return contract.
     *
     * @dataProvider setChannelIdsProvider
     * @param int $entryId
     * @param int $channelId
     * @param string $expectedSql
     * @return void
     */
    public function testSetChannelIdsIssuesExpectedUpdateQueryAndReturnsNull(
        int $entryId,
        int $channelId,
        string $expectedSql
    ): void {
        $captured = (object) ['queries' => []];

        ee()->setMock('db', new class($captured) {
            private $captured;

            /**
             * Store the shared query ledger.
             *
             * @param object $captured
             * @return void
             */
            public function __construct($captured)
            {
                $this->captured = $captured;
            }

            /**
             * Capture the raw SQL issued by the wrapper method.
             *
             * @param string $sql
             * @return void
             */
            public function query($sql)
            {
                $this->captured->queries[] = $sql;
            }
        });

        $result = Sql_structure::set_channel_ids($entryId, $channelId);

        $this->assertNull($result);
        $this->assertSame([$expectedSql], $captured->queries);
    }

    /**
     * Provide stable ids that the wrapper currently forwards unchanged.
     *
     * @return array<string, array{0:int, 1:int, 2:string}>
     */
    public static function setChannelIdsProvider(): array
    {
        return [
            'happy path ids' => [
                7,
                3,
                'UPDATE exp_structure SET channel_id = 3 WHERE entry_id = 7 ',
            ],
            'zero boundary ids' => [
                0,
                0,
                'UPDATE exp_structure SET channel_id = 0 WHERE entry_id = 0 ',
            ],
        ];
    }
}
