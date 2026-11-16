<?php

namespace ExpressionEngine\Tests\Cli\Commands;

use ExpressionEngine\Cli\Commands\CommandUpdate;
use ExpressionEngine\Dependency\Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CommandUpdateTest extends TestCase
{
    private $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/ee-cli-update-' . bin2hex(random_bytes(8));
        mkdir($this->directory . '/system/ee', 0700, true);
        define('FCPATH', $this->directory . '/system/ee/');
        unset($_ENV['EE_INSTALL_MODE'], $_SERVER['EE_INSTALL_MODE']);

        require_once SYSPATH . 'ee/legacy/core/Config.php';
        ee()->resetMocks();
        ee()->setMock('load', new class {
            public function add_package_path($path)
            {
                // Stop after the environment guard, before loading updater services.
                throw new \RuntimeException('Local updater initialized.');
            }
        });
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
        if (file_exists($this->directory . '/.env.php')) {
            unlink($this->directory . '/.env.php');
        }
        rmdir($this->directory . '/system/ee');
        rmdir($this->directory . '/system');
        rmdir($this->directory);
    }

    /** @dataProvider installModeProvider */
    public function testLocalUpdateChecksInstallMode($installMode, $hasEnvFile, $canUpdate)
    {
        if ($hasEnvFile) {
            $contents = "EE_TEST_SETTING=fixture-value\n";
            if ($installMode !== null) {
                $contents .= 'EE_INSTALL_MODE=' . $installMode . "\n";
            }
            file_put_contents($this->directory . '/.env.php', $contents);
            // Match Boot/boot.php: the command receives an already loaded environment.
            Dotenv::createImmutable($this->directory, '.env.php')->load();
        }

        $this->expectOutputString('');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($canUpdate
            ? 'Local updater initialized.'
            : 'EE_INSTALL_MODE needs to be set to TRUE in .env.php to run update command');

        $command = (new \ReflectionClass(CommandUpdate::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(CommandUpdate::class, 'upgradeFromLocalVersion');
        \TestReflectionHelper::makeMethodAccessible($method);
        $method->invoke($command);
    }

    public function installModeProvider()
    {
        return [
            'enabled' => ['TRUE', true, true],
            'disabled' => ['FALSE', true, false],
            'missing setting' => [null, true, false],
            'lowercase true' => ['true', true, false],
            'empty setting' => ['', true, false],
            'site without dotenv' => [null, false, true],
        ];
    }
}
