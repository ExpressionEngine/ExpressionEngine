<?php

namespace {
    if (getenv('PROGRESS_STUB_SAPI') === '1' && ! function_exists('php_sapi_name')) {
        function php_sapi_name()
        {
            return getenv('PROGRESS_STUB_SAPI_VALUE') ?: 'fpm-fcgi';
        }
    }
}

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Libraries {

use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/installer/libraries/Progress.php';

class ProgressTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        $this->closeSessionIfActive();
        $_SESSION = [];

        if (getenv('PROGRESS_STUB_SAPI') === '1') {
            putenv('PROGRESS_STUB_SAPI_VALUE=cli');
        }
    }

    protected function tearDown(): void
    {
        if (getenv('PROGRESS_STUB_SAPI') === '1') {
            putenv('PROGRESS_STUB_SAPI_VALUE');
        }

        $this->closeSessionIfActive();
        ee()->resetMocks();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateStateAndGetStateReturnPrefixedMessage()
    {
        $progress = new \Progress();
        $progress->prefix = 'Installer: ';

        $progress->update_state('Running');

        $this->assertSame('Installer: Running', $progress->get_state());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetStateReturnsFalseWhenSessionStateIsMissing()
    {
        $progress = new \Progress();
        $progress->prefix = 'Installer: ';

        $this->assertFalse($progress->get_state());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testClearStateRemovesStoredProgressMessage()
    {
        $progress = new \Progress();
        $progress->update_state('Step 1');

        $progress->clear_state();

        $this->assertFalse($progress->get_state());
    }

    public function testFetchProgressHeaderDelegatesToViewLoader()
    {
        $loader = new class {
            public $libraryCalls = [];
            public $viewCalls = [];

            public function library($name)
            {
                $this->libraryCalls[] = $name;
            }

            public function view($view, $settings, $return = false)
            {
                $this->viewCalls[] = [$view, $settings, $return];

                return '<meta http-equiv="refresh" content="1">';
            }
        };
        ee()->setMock('load', $loader);

        $progress = new \Progress();
        $result = $progress->fetch_progress_header(['redirect' => 'next-step']);

        $this->assertSame(['view'], $loader->libraryCalls);
        $this->assertSame(
            [['progress_header', ['redirect' => 'next-step'], true]],
            $loader->viewCalls
        );
        $this->assertSame('<meta http-equiv="refresh" content="1">', $result);
    }

    public function testProgressIteratorCurrentReturnsCurrentValueInCliWithoutCallingProgressService()
    {
        $progressService = new class {
            public $updates = [];

            public function update_state($state)
            {
                $this->updates[] = $state;
            }
        };
        ee()->setMock('progress', $progressService);

        $iterator = new \ProgressIterator(['first', 'second']);

        $this->assertSame('first', $iterator->current());
        $this->assertSame([], $progressService->updates);
    }

    public function testProgressIteratorCurrentReturnsAdvancedItem()
    {
        $iterator = new \ProgressIterator(['first', 'second']);
        $iterator->next();

        $this->assertSame('second', $iterator->current());
    }

    public function testProgressIteratorCurrentWorksWhenProgressServiceIsMissing()
    {
        $iterator = new \ProgressIterator(['first']);

        $this->assertSame('first', $iterator->current());
    }

    public function testProgressIteratorCurrentUpdatesProgressStateWhenSapiIsNonCli()
    {
        if (getenv('PROGRESS_STUB_SAPI') !== '1') {
            $this->markTestSkipped('Requires PROGRESS_STUB_SAPI=1 and php_sapi_name() disable for non-CLI branch.');
        }

        putenv('PROGRESS_STUB_SAPI_VALUE=fpm-fcgi');

        $progressService = new class {
            public $updates = [];

            public function update_state($state)
            {
                $this->updates[] = $state;
            }
        };
        ee()->setMock('progress', $progressService);

        $iterator = new \ProgressIterator(['first', 'second', 'third']);

        $this->assertSame('first', $iterator->current());
        $this->assertSame(['Step 0 of 3'], $progressService->updates);
    }

    private function closeSessionIfActive(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}
}
