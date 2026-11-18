<?php

require_once __DIR__ . '/../StructureTestBase.php';

class StructurePaginateTest extends StructureTestBase
{
    private const DEPRECATION_MESSAGE = 'The structure paginate tag was deprecated in 2011 and has been removed for compatibility with ExpressionEngine 4. Please use the native ExpressionEngine pagination.';

    private $loadMock;

    private $loggerMock;

    /**
     * Set up logger-related mocks for paginate tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMock = new class {
            public $libraries = [];

            public function library($name)
            {
                $this->libraries[] = $name;
            }
        };

        $this->loggerMock = new class {
            public $messages = [];

            public function developer($msg)
            {
                $this->messages[] = $msg;
            }
        };

        ee()->setMock('load', $this->loadMock);
        ee()->setMock('logger', $this->loggerMock);
    }

    /**
     * Confirm paginate loads the logger and logs the exact deprecation notice.
     *
     * @return void
     */
    public function testPaginateReturnsFalseAfterLoggingDeprecationNotice()
    {
        $result = $this->structure->paginate();

        $this->assertFalse($result);
        $this->assertSame(['logger'], $this->loadMock->libraries);
        $this->assertSame([self::DEPRECATION_MESSAGE], $this->loggerMock->messages);
    }

    /**
     * Confirm paginate keeps the same observable side effects on repeated calls.
     *
     * @return void
     */
    public function testPaginateLogsDeprecationNoticeOnEveryCall()
    {
        $this->assertFalse($this->structure->paginate());
        $this->assertFalse($this->structure->paginate());

        $this->assertSame(['logger', 'logger'], $this->loadMock->libraries);
        $this->assertSame(
            [self::DEPRECATION_MESSAGE, self::DEPRECATION_MESSAGE],
            $this->loggerMock->messages
        );
    }
}


