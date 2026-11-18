<?php

namespace ExpressionEngine\Tests\Cli\Commands;

use ExpressionEngine\Cli\Commands\CommandConfigConfig;
use ExpressionEngine\Cli\Commands\CommandConfigEnv;
use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Service\Config\Directory;
use ExpressionEngine\Service\Config\File;
use PHPUnit\Framework\TestCase;

class ConfigCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /** @dataProvider failureProvider */
    public function testEnvCommandHandlesFailure($error)
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('exists')->willReturn(true);
        $filesystem->method('read')->willThrowException($error);
        ee()->setMock('Filesystem', $filesystem);

        $command = $this->makeCommand(CommandConfigEnv::class, $error);
        $command->method('getOptionOrAsk')->willReturnMap([
            ['--env-variable', 'command_config_env_ask_config_variable', '', true, 'EE_TEST_SETTING'],
            ['--value', 'command_config_env_ask_config_value', '', true, 'fixture-value'],
        ]);

        $this->expectOutputString('');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CLI failure reported.');
        $command->handle();
    }

    /** @dataProvider failureProvider */
    public function testConfigCommandHandlesFailure($error)
    {
        $file = $this->createMock(File::class);
        $file->expects($this->once())->method('set')
            ->with('test_setting', 'fixture-value', true)
            ->willThrowException($error);
        $config = $this->createMock(Directory::class);
        $config->method('getFile')->willReturn($file);
        ee()->setMock('Config', $config);
        ee()->setMock('cache', new class {
            public function save($key, $value)
            {
            }
        });

        $command = $this->makeCommand(CommandConfigConfig::class, $error);
        $command->method('getOptionOrAsk')->willReturnMap([
            ['--config-variable', 'command_config_config_ask_config_variable', '', true, 'test_setting'],
            ['--value', 'command_config_config_ask_config_value', '', true, 'fixture-value'],
        ]);

        $this->expectOutputString('');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CLI failure reported.');
        $command->handle();
    }

    private function makeCommand($class, $error)
    {
        $command = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOptionOrAsk', 'info', 'fail'])
            ->getMock();
        // fail() normally exits; throw here to verify the same early termination.
        $command->expects($this->once())->method('fail')->with($error->getMessage())
            ->willThrowException(new \RuntimeException('CLI failure reported.'));

        return $command;
    }

    public function failureProvider()
    {
        return [
            'exception' => [new \RuntimeException('Configuration operation failed.')],
            'PHP error' => [new \TypeError('Invalid configuration value.')],
        ];
    }
}
