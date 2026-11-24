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

    /** @dataProvider cliBootstrapStates */
    public function testCliBootstrapDoesNotRequireBrowserAuthorization($step, $constants)
    {
        $autoloader = 'ee/updater/ExpressionEngine/Updater/Core/';
        mkdir($this->directory . $autoloader, 0700, true);
        // Release packaging copies the core autoloader into the updater namespace.
        $source = file_get_contents(SYSPATH . 'ee/ExpressionEngine/Core/Autoloader.php');
        $source = str_replace(
            'namespace ExpressionEngine\\Core;', 'namespace ExpressionEngine\\Updater\\Core;', $source
        );
        file_put_contents($this->directory . $autoloader . 'Autoloader.php', $source);
        if ($constants !== null) {
            mkdir($this->directory . 'ee/ExpressionEngine/Config', 0700, true);
            file_put_contents($this->directory . 'ee/ExpressionEngine/Config/constants.php', $constants);
        }
        $result = $this->runBoot('CLI', $step);
        $this->assertSame('', $result['body']);
        $this->assertFileDoesNotExist($this->directory . 'ee/updater/.authorization.php');
        $this->assertFileDoesNotExist($this->directory . 'user/cache/.ee-updater.lock');
    }

    public function cliBootstrapStates()
    {
        return [
            'normal bootstrap' => [null, '<?php return [];'],
            'recovery with missing application' => ['rollback', null],
            'recovery with partially written application' => ['rollback', '<?php interrupted file'],
        ];
    }

    /** @dataProvider cliHelperStates */
    public function testRecoveryUsesPackagedHelpersWithoutApplicationFiles($preloadHelper)
    {
        $updater = $this->directory . 'ee/updater/';
        mkdir($updater . 'legacy/helpers', 0700, true);
        $helper = SYSPATH . 'ee/legacy/helpers/cli_helper.php';
        copy($helper, $updater . 'legacy/helpers/cli_helper.php');
        copy($this->source . 'recovery.php', $updater . 'recovery.php');
        // Isolate the recovery entry point from the runner's file and database operations.
        file_put_contents($updater . 'boot.php', '<?php
            namespace ExpressionEngine\Updater\Service\Updater;
            class Runner {
                public function runStep($step) { stdout($step, CLI_STDOUT_SUCCESS); }
                public function getNextStep() { return false; }
            }
        ');

        $script = '<?php ';
        if ($preloadHelper) {
            $script .= 'require ' . var_export($helper, true) . ';';
        }
        $script .= 'require ' . var_export($updater . 'recovery.php', true) . ';';
        $output = $this->runPhp($script);
        $this->assertSame("==> rollback\n", preg_replace('/\x1b\[[0-9;]*m/', '', $output));
    }

    public function cliHelperStates()
    {
        return [
            'standalone recovery' => [false],
            'CLI helper already loaded' => [true],
        ];
    }

    /**
     * Recovery continuations must boot the installed site even when its installer remains.
     *
     * @return void
     */
    public function testRollbackKeepsApplicationModeInEachRecoveryProcess(): void
    {
        $updater = $this->directory . 'ee/updater/';
        mkdir($this->directory . 'ee/installer', 0700, true);
        mkdir($this->directory . 'user/config', 0700, true);
        touch($this->directory . 'user/config/config.php');
        mkdir($updater . 'legacy/helpers', 0700, true);
        copy(SYSPATH . 'ee/legacy/helpers/cli_helper.php', $updater . 'legacy/helpers/cli_helper.php');
        copy($this->source . 'recovery.php', $updater . 'recovery.php');
        copy(SYSPATH . 'ee/eecli.php', $this->directory . 'ee/eecli.php');

        // Exercise the real CLI handoff and subprocesses without replacing files or opening a database.
        file_put_contents($updater . 'boot.php', '<?php
            namespace ExpressionEngine\Updater\Service\Updater;
            class Runner {
                public function runStep($step) {
                    echo $step . ":" . (defined("INSTALL_MODE") && INSTALL_MODE === false ? "app" : "installer") . "\n";
                }
                public function getNextStep() {
                    $next = ["rollback" => "restoreDatabase", "restoreDatabase" => "selfDestruct[rollback]"];
                    return $next[$_GET["step"]] ?? false;
                }
            }
        ');

        $entry = var_export($this->directory . 'ee/eecli.php', true);
        $output = $this->runPhp('<?php $argv = ["eecli.php", "update", "--rollback"]; require ' . $entry . ';');

        $this->assertSame("rollback:app\nrestoreDatabase:app\nselfDestruct[rollback]:app\n", $output);
    }

    /**
     * A failed update is rolled back by the packaged recovery script alone:
     * the previous files return and the new ones are set aside.
     *
     * @dataProvider interruptedUpdates
     * @param array $files New files the interrupted update left behind, by path:
     *   moved into place, or still in the update's archive
     * @param array $php Options for the PHP that runs the recovery
     * @return void
     */
    public function testRecoveryRestoresTheBackedUpInstallation(array $files, array $php = []): void
    {
        $output = $this->runRecovery($this->interruptedUpdate($files), $php);

        $this->assertStringEndsWith("restored application: selfDestruct[rollback]\n", $output);
        $this->assertRolledBack();
    }

    public function interruptedUpdates()
    {
        $newFiles = [
            'ee/version.txt' => 'new',
            // Part of the new application; the updater's boot loads it when it is there
            'ee/ExpressionEngine/Library/Compat/Random/random.php' => file_get_contents(
                SYSPATH . 'ee/ExpressionEngine/Library/Compat/Random/random.php'
            ),
            'themes/ee/theme.txt' => 'new',
        ];
        $notYetMoved = [
            'user/cache/ee_update/archive/system/ee/version.txt' => 'new',
            'user/cache/ee_update/archive/themes/ee/theme.txt' => 'new',
        ];

        return [
            'new files in place' => [$newFiles],
            'previous files backed up, new ones not yet in place' => [$notYetMoved],
            'host that disables proc_open' => [$newFiles, ['-d', 'disable_functions=proc_open']],
        ];
    }

    /**
     * Where PHP cannot start another PHP process, the first step still restores
     * the files, and running the command it gives finishes the rollback.
     *
     * @return void
     */
    public function testRecoveryThatCannotContinueSaysHowToFinish(): void
    {
        $updater = $this->interruptedUpdate([
            'ee/version.txt' => 'new',
            'themes/ee/theme.txt' => 'new',
        ]);

        $result = $this->runPhpProcess(
            '<?php require ' . var_export($updater . 'recovery.php', true) . ';',
            ['-d', 'disable_functions=proc_open,passthru']
        );

        $this->assertSame(1, $result['status'], $result['errors'] . $result['output']);
        $this->assertRolledBack();
        $this->assertStringContainsString("To finish it, run:\n", $result['errors']);

        // Run the command as given, through the shell a site owner would paste it into
        $given = substr($result['errors'], strpos($result['errors'], "run:\n") + strlen("run:\n"));
        $command = trim(strtok($given, "\n"));
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $this->assertSame(0, proc_close($process), $command . "\n" . $errors . $output);
        $this->assertSame("restored application: selfDestruct[rollback]\n", $output);
    }

    /**
     * Sets up the packaged updater and a site whose update failed, with the
     * previous files in the update's backups.
     *
     * @param array $files New files the interrupted update left behind, by path:
     *   moved into place, or still in the update's archive
     * @return string The updater's directory
     */
    private function interruptedUpdate(array $files)
    {
        $updater = $this->directory . 'ee/updater/';
        $this->packageUpdater($updater);

        // Moving the previous themes into the backups leaves their folder behind
        mkdir($this->directory . 'themes/ee', 0700, true);

        $files += [
            'user/cache/ee_update/backups/system_ee/version.txt' => 'old',
            // Stands in for the restored application, which finishes the recovery in a new process.
            'user/cache/ee_update/backups/system_ee/ExpressionEngine/Boot/boot.php' =>
                '<?php echo "restored application: " . $_GET["step"] . "\n"; exit(0);',
            'user/cache/ee_update/backups/themes_ee/theme.txt' => 'old',
            'user/cache/ee_update/configs.json' => json_encode([
                'archive_path' => $this->directory . 'user/cache/ee_update/archive',
                'theme_paths' => [$this->directory . 'themes'],
            ]),
        ];
        foreach ($files as $path => $contents) {
            $this->writeFile($this->directory . $path, $contents);
        }

        return $updater;
    }

    /**
     * Runs the packaged recovery script as a site owner would
     *
     * @param string $updater The updater's directory
     * @param array $php Options for PHP
     * @return string Its output
     */
    private function runRecovery($updater, array $php = [])
    {
        return $this->runPhp('<?php require ' . var_export($updater . 'recovery.php', true) . ';', $php);
    }

    private function assertRolledBack()
    {
        $this->assertStringEqualsFile($this->directory . 'ee/version.txt', 'old');
        $this->assertStringEqualsFile($this->directory . 'themes/ee/theme.txt', 'old');
        $this->assertStringEqualsFile($this->directory . 'user/cache/ee_update/archive/system/ee/version.txt', 'new');
    }

    /**
     * Assembles the updater as release packaging does: its own files, plus
     * the application files that are copied into the updater's namespace.
     * The file list and namespace rewrite mirror fillUpdaterDependencies() in
     * build-tools/gulpfile.js; a file added to one must be added to the other.
     */
    private function packageUpdater($destination)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->source . 'ExpressionEngine', \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($files as $file) {
            $this->writeFile(
                $destination . substr($file->getPathname(), strlen($this->source)),
                file_get_contents($file->getPathname())
            );
        }
        copy($this->source . 'boot.php', $destination . 'boot.php');
        copy($this->source . 'recovery.php', $destination . 'recovery.php');

        foreach ([
            'legacy/helpers/cli_helper.php',
            'ExpressionEngine/Boot/boot.common.php',
            'ExpressionEngine/Core/Autoloader.php',
            'ExpressionEngine/Service/Logger/File.php',
            'ExpressionEngine/Service/Updater/Logger.php',
            'ExpressionEngine/Service/Updater/SteppableTrait.php',
            'ExpressionEngine/Service/Updater/UpdaterException.php',
            'ExpressionEngine/Service/Updater/Verifier.php',
        ] as $path) {
            $source = preg_replace(
                '/(namespace|use) ExpressionEngine\\\\/i',
                '$1 ExpressionEngine\\Updater\\',
                file_get_contents(SYSPATH . 'ee/' . $path)
            );
            $this->writeFile(
                $destination . preg_replace('#^ExpressionEngine/#', 'ExpressionEngine/Updater/', $path),
                $source
            );
        }
    }

    private function writeFile($path, $contents)
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        file_put_contents($path, $contents);
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
        $output = $this->runPhp($script);
        $result = json_decode($output, true);
        $this->assertIsArray($result, $output);

        return $result;
    }

    private function runPhp($script, array $options = [])
    {
        $result = $this->runPhpProcess($script, $options);
        $this->assertSame(0, $result['status'], $result['errors'] . $result['output']);

        return $result['output'];
    }

    private function runPhpProcess($script, array $options = [])
    {
        file_put_contents($this->directory . 'request.php', $script);
        // Retain configured extensions: PHP 7 installations may load JSON through php.ini.
        $command = array_merge([PHP_BINARY], $options, [$this->directory . 'request.php']);
        if (PHP_VERSION_ID < 70400) {
            $command = implode(' ', array_map('escapeshellarg', $command));
        }
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['status' => proc_close($process), 'output' => $output, 'errors' => $errors];
    }
}
