<?php

namespace ExpressionEngine\Tests\Installer\Updater;

use PHPUnit\Framework\TestCase;

class BootTest extends TestCase
{
    private $directory;
    private $source;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/ee-updater-boot-' . bin2hex(random_bytes(8)) . '/';
        $this->source = SYSPATH . 'ee/installer/updater/';
        mkdir($this->directory . 'ee/updater', 0700, true);
        mkdir($this->directory . 'user/cache', 0700, true);
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->directory);
    }

    public function testMissingCapabilityIsRejectedBeforeLoadingApplicationFiles()
    {
        $result = $this->runBoot('CP', 'checkForDbUpdates');
        $this->assertSame(403, $result['status']);
        $this->assertSame('Unauthorized updater request.', $result['body']['message']);
        $this->assertSame([], $result['body']['trace']);
        $this->assertFileDoesNotExist($this->directory . 'ee/updater/.authorization.php');
    }

    /** @dataProvider applicationPathProvider */
    public function testInitialHandoffLoadsTheInstalledApplicationBeforePreparingState($path)
    {
        mkdir($this->directory . $path, 0700, true);
        file_put_contents($this->directory . $path . 'boot.php', '<?php
            file_put_contents(SYSPATH . "booted", "yes");
            throw new \RuntimeException("Control Panel authorization required", 403);
        ');
        $result = $this->runBoot('CP', 'updateFiles');
        $this->assertSame(403, $result['status']);
        $this->assertFileExists($this->directory . 'booted');
        $this->assertFileDoesNotExist($this->directory . 'ee/updater/.authorization.php');
    }

    public function applicationPathProvider()
    {
        return [['ee/ExpressionEngine/Boot/'], ['ee/EllisLab/ExpressionEngine/Boot/']];
    }

    public function testCliBootstrapDoesNotRequireBrowserAuthorization()
    {
        $autoloader = 'ee/updater/ExpressionEngine/Updater/Core/';
        mkdir($this->directory . $autoloader, 0700, true);
        // Release packaging copies the core autoloader into the updater namespace.
        $source = file_get_contents(SYSPATH . 'ee/ExpressionEngine/Core/Autoloader.php');
        $source = str_replace(
            'namespace ExpressionEngine\\Core;', 'namespace ExpressionEngine\\Updater\\Core;', $source
        );
        file_put_contents($this->directory . $autoloader . 'Autoloader.php', $source);
        mkdir($this->directory . 'ee/ExpressionEngine/Config', 0700, true);
        file_put_contents($this->directory . 'ee/ExpressionEngine/Config/constants.php', '<?php return [];');
        $result = $this->runBoot('CLI', null);
        $this->assertSame('', $result['body']);
        $this->assertFileDoesNotExist($this->directory . 'ee/updater/.authorization.php');
        $this->assertFileDoesNotExist($this->directory . 'user/cache/.ee-updater.lock');
    }

    private function runBoot($requestType, $step)
    {
        $script = '<?php
            define("SYSPATH", ' . var_export($this->directory, true) . ');
            define("REQ", ' . var_export($requestType, true) . ');
            define("BASEPATH", SYSPATH . "ee/legacy/");
            $_GET = ["step" => ' . var_export($step, true) . '];
            $_COOKIE = [];
            $_SERVER["REQUEST_METHOD"] = "POST";
            $_SERVER["HTTP_X_CSRF_TOKEN"] = "fixture-csrf";
            ob_start();
            register_shutdown_function(function () {
                $body = ob_get_clean();
                echo json_encode([
                    "status" => http_response_code(),
                    "body" => json_decode($body, true) ?: $body
                ]);
            });
            require ' . var_export($this->source . 'boot.php', true) . ';';
        file_put_contents($this->directory . 'request.php', $script);
        // Retain configured extensions: PHP 7 installations may load JSON through php.ini.
        $command = [PHP_BINARY, $this->directory . 'request.php'];
        if (PHP_VERSION_ID < 70400) {
            $command = implode(' ', array_map('escapeshellarg', $command));
        }
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $errors . $output);
        $result = json_decode($output, true);
        $this->assertIsArray($result, $output);

        return $result;
    }
}
