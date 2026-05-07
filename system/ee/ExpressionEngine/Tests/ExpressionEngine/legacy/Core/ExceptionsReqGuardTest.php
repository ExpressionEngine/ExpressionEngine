<?php

use PHPUnit\Framework\TestCase;

if (!defined('CLI_STDOUT_FAILURE')) {
    define('CLI_STDOUT_FAILURE', 4);
}

if (!function_exists('set_status_header')) {
    /**
     * Stub status header setter for isolated legacy exception tests.
     *
     * @param int $code
     * @param string $text
     * @return void
     */
    function set_status_header($code = 200, $text = '')
    {
    }
}

if (!function_exists('stdout')) {
    /**
     * Capture CLI stdout calls from EE_Exceptions::show_php_error.
     *
     * @param string $message
     * @param int $status
     * @return void
     */
    function stdout($message, $status = 0)
    {
        $GLOBALS['ee_exceptions_stdout_calls'][] = [
            'message' => $message,
            'status' => $status,
        ];
    }
}

class ExceptionsReqGuardTest extends TestCase
{
    /**
     * Verifies that show_error returns template output when REQ is undefined.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testShowErrorDoesNotFatalWhenReqIsUndefined()
    {
        $this->assertFalse(defined('REQ'));

        require_once SYSPATH . 'ee/legacy/core/Exceptions.php';

        $exceptions = new EE_Exceptions();
        $output = $exceptions->show_error(
            'Error',
            'The URI you submitted has disallowed characters.',
            'error_general',
            400
        );

        $this->assertIsString($output);
        $this->assertStringContainsString('The URI you submitted has disallowed characters.', $output);
    }

    /**
     * Verifies CLI-specific output still runs when REQ is explicitly CLI.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testShowPhpErrorUsesCliBranchWhenReqIsCli()
    {
        define('REQ', 'CLI');
        $GLOBALS['ee_exceptions_stdout_calls'] = [];

        require_once SYSPATH . 'ee/legacy/core/Exceptions.php';

        $exceptions = new EE_Exceptions();

        ob_start();
        $exceptions->show_php_error(E_WARNING, 'CLI warning message', '/tmp/cli.php', 21);
        $output = ob_get_clean();

        $this->assertCount(1, $GLOBALS['ee_exceptions_stdout_calls']);
        $this->assertSame('PHP Warning:', $GLOBALS['ee_exceptions_stdout_calls'][0]['message']);
        $this->assertSame(CLI_STDOUT_FAILURE, $GLOBALS['ee_exceptions_stdout_calls'][0]['status']);
        $this->assertStringContainsString('CLI warning message', $output);
        $this->assertStringContainsString('/tmp/cli.php: 21', $output);
    }
}
