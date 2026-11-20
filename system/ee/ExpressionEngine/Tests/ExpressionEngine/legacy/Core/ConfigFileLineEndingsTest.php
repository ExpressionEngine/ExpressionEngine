<?php

namespace {
    if (! function_exists('is_really_writable')) {
        function is_really_writable($file)
        {
            return is_writable($file);
        }
    }
}

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Core {
    use PHPUnit\Framework\TestCase;

    /**
     * Settings are added to, changed in and removed from the config file as
     * text, on files that may have been edited anywhere.
     *
     * @runTestsInSeparateProcesses
     * @preserveGlobalState disabled
     */
    class ConfigFileLineEndingsTest extends TestCase
    {
        private $path;

        protected function setUp(): void
        {
            require_once BASEPATH . 'core/Config.php';

            if (! defined('REQ')) {
                define('REQ', 'CP');
            }

            $this->path = tempnam(sys_get_temp_dir(), 'ee-config');
        }

        protected function tearDown(): void
        {
            @unlink($this->path);
        }

        /** @dataProvider lineEndings */
        public function testASettingIsAddedChangedAndRemovedWithoutDisturbingTheOthers(array $endings)
        {
            $lines = [
                '<?php',
                "\$config['app_version'] = '7.5.29';",
                "\$config['theme_folder_url'] = 'https://example.com/themes/';",
                "\$config['database'] = array(",
                "    'hostname' => 'localhost',",
                "    'database' => 'ee',",
                ");",
                "\$config['site_url'] = 'https://example.com/';",
                "\$config['cookie_prefix'] = 'ee';",
                '// EOF',
            ];

            $file = '';

            foreach ($lines as $number => $line) {
                // The last ending given serves for the rest of the file
                $file .= $line . $endings[min($number, count($endings) - 1)];
            }

            file_put_contents($this->path, $file);
            $config = $this->config();

            // What an update does: record the new version along with a note
            // of its own, then take the note away once it has finished
            $config->_update_config(['app_version' => '7.5.30', 'updater_completion_pending' => '7.5.30']);
            $this->assertSame('7.5.30', $this->settings()['updater_completion_pending']);

            $config->_update_config([], ['updater_completion_pending' => '7.5.30']);

            $this->assertSame([
                'app_version' => '7.5.30',
                'theme_folder_url' => 'https://example.com/themes/',
                'database' => ['hostname' => 'localhost', 'database' => 'ee'],
                'site_url' => 'https://example.com/',
                'cookie_prefix' => 'ee',
            ], $this->sorted($this->settings()));

            // A setting written over several lines goes as a whole
            $config->_update_config([], ['database' => '']);

            $this->assertSame([
                'app_version' => '7.5.30',
                'theme_folder_url' => 'https://example.com/themes/',
                'site_url' => 'https://example.com/',
                'cookie_prefix' => 'ee',
            ], $this->sorted($this->settings()));
        }

        public function lineEndings()
        {
            $unix = "\n";
            $windows = "\r\n";

            return [
                'Unix' => [[$unix]],
                'Windows' => [[$windows]],
                'Windows, then Unix from the fourth line' => [[$windows, $windows, $windows, $unix]],
                'Unix, then Windows from the fourth line' => [[$unix, $unix, $unix, $windows]],
                'alternating' => [[$unix, $windows, $unix, $windows, $unix, $windows, $unix, $windows, $unix, $windows]],
            ];
        }

        private function config()
        {
            $config = $this->getMockBuilder(\EE_Config::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['divineAll'])
                ->getMock();
            $config->method('divineAll')->willReturn([]);
            $config->config_path = $this->path;

            return $config;
        }

        /**
         * The settings as PHP reads them from the file
         */
        private function settings()
        {
            // A copy, so that a version of the file PHP has already compiled is not what comes back
            $copy = $this->path . '-' . bin2hex(random_bytes(4)) . '.php';
            copy($this->path, $copy);

            try {
                return (function ($path) {
                    $config = [];
                    require $path;

                    return $config;
                })($copy);
            } finally {
                unlink($copy);
            }
        }

        private function sorted(array $settings)
        {
            $order = ['app_version', 'theme_folder_url', 'database', 'site_url', 'cookie_prefix'];

            return array_replace(array_intersect_key(array_flip($order), $settings), $settings);
        }
    }
}
