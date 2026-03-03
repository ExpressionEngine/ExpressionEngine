<?php

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Libraries;

use PHPUnit\Framework\TestCase;

class InstallerLoggerTestMsmConfig
{
}

class InstallerLoggerTest extends TestCase
{
    private static $stubRoot;

    public static function setUpBeforeClass(): void
    {
        self::$stubRoot = sys_get_temp_dir() . '/installer-logger-stub-' . uniqid('', true);
        $librariesDir = self::$stubRoot . '/libraries';

        if (! is_dir($librariesDir)) {
            mkdir($librariesDir, 0777, true);
        }

        $stub = <<<'PHP'
<?php
class EE_Logger
{
    public static $calls = [];
    public function deprecate_template_tag($message, $regex, $replacement)
    {
        self::$calls[] = [$message, $regex, $replacement];
    }
}
PHP;

        file_put_contents($librariesDir . '/Logger.php', $stub);

        if (! defined('EE_APPPATH')) {
            define('EE_APPPATH', self::$stubRoot . '/');
        }

        if (! class_exists('MSM_Config', false)) {
            class_alias(InstallerLoggerTestMsmConfig::class, 'MSM_Config');
        }

        require_once SYSPATH . 'ee/installer/libraries/Logger.php';
    }

    protected function setUp(): void
    {
        ee()->resetMocks();
        \EE_Logger::$calls = [];
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testDeprecateTemplateTagLoadsInstallerTemplateAndCallsParentLogger()
    {
        $autoload = function ($class) {
            if ($class !== 'Installer_Template' || class_exists('Installer_Template', false)) {
                return;
            }

            // Let class_exists('Installer_Template') return false so the
            // require_once branch in Installer_Logger executes first.
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3) as $frame) {
                if (($frame['function'] ?? null) === 'class_exists') {
                    return;
                }
            }

            eval('class Installer_Template {}');
        };
        spl_autoload_register($autoload);

        try {
            $reflection = new \ReflectionClass(\Installer_Logger::class);
            $logger = $reflection->newInstanceWithoutConstructor();
            $logger->deprecate_template_tag('Deprecated tag', '/\\{old\\}/', '{new}');
        } finally {
            spl_autoload_unregister($autoload);
        }

        $this->assertTrue(class_exists('Installer_Template', false));
        $this->assertSame(
            [['Deprecated tag', '/\{old\}/', '{new}']],
            \EE_Logger::$calls
        );
    }
}
