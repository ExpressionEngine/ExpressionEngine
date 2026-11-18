<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateParseGlobalsTest extends EE_TemplateTestBase
{
    private function defineStandardGlobalFallbackConstants(): void
    {
        $fallbacks = [
            'APP_BUILD' => '0',
            'APP_VER_ID' => '0',
            'DOC_URL' => 'https://docs.example.com',
            'PASSWORD_MAX_LENGTH' => 72,
            'USERNAME_MAX_LENGTH' => 32,
            'URL_THEMES' => 'https://example.com/themes/',
            'URL_THIRD_THEMES' => 'https://example.com/themes/third_party/',
            'QUERY_MARKER' => '?',
        ];

        foreach ($fallbacks as $name => $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }

    private function setupStandardGlobalsDependenciesForParseGlobals(): void
    {
        $this->defineStandardGlobalFallbackConstants();

        $sessionMock = new class {
            public $userdata = [
                'member_id' => 0,
                'role_id' => 3,
                'username' => '',
                'screen_name' => '',
            ];
            public $access_cp = false;

            public function userdata($key, $default = false)
            {
                return $this->userdata[$key] ?? $default;
            }
        };
        ee()->setMock('session', $sessionMock);

        $modelMock = new class {
            public function make($model)
            {
                return new class {
                    public function loadAll()
                    {
                        return $this;
                    }

                    public function getDictionary($key, $value)
                    {
                        return ['my_global' => 'global-value'];
                    }
                };
            }
        };
        ee()->setMock('Model', $modelMock);

        $inputMock = new class {
            public function ip_address()
            {
                return '127.0.0.1';
            }
        };
        ee()->setMock('input', $inputMock);

        $configMock = new \FakeConfig();
        $configMock->items = [
            'site_id' => 1,
            'site_short_name' => 'default_site',
            'site_name' => 'Example Site',
            'site_label' => 'Example Site',
            'site_url' => 'https://example.com/',
            'site_description' => 'Description',
            'site_index' => 'index.php',
            'webmaster_email' => 'admin@example.com',
            'profile_trigger' => 'member',
            'xml_lang' => 'en',
            'output_charset' => 'UTF-8',
            'send_headers' => 'y',
            'save_tmpl_files' => 'n',
            'encode_removed_text' => '[email removed]',
            'debug' => 1,
            'gzip_output' => 'n',
        ];
        ee()->setMock('config', $configMock);

        $dbMock = new class {
            public function select($fields = '*')
            {
                return $this;
            }

            public function from($table)
            {
                return $this;
            }

            public function join($table, $condition, $type = '')
            {
                return $this;
            }

            public function where($field, $value = null)
            {
                return $this;
            }

            public function get($table = null)
            {
                return new \eeDbResultMock([
                    [
                        'site_name' => 'default_site',
                        'template_name' => 'app',
                        'group_name' => 'assets',
                        'edit_date' => 1700000000,
                        'template_type' => 'css',
                    ],
                ]);
            }

            public function escape_str($value)
            {
                return addslashes($value);
            }

            public function dbprefix($table = '')
            {
                return 'exp_' . $table;
            }
        };
        ee()->setMock('db', $dbMock);

        $functionsMock = new class {
            public function fetch_current_uri()
            {
                return 'news/index';
            }

            public function fetch_site_index($a = 0, $b = 0)
            {
                return '/index.php?';
            }

            public function create_url($match)
            {
                $path = is_array($match) ? ($match[1] ?? '') : $match;

                return 'https://example.com/' . ltrim(trim($path, "\"'"), '/');
            }

            public function create_route($match)
            {
                $path = is_array($match) ? ($match[1] ?? '') : $match;
                $path = trim($path, "\"'");
                $parts = preg_split('/\s+/', $path);

                return '/route/' . ($parts[0] ?? '');
            }

            public function extract_path($path)
            {
                return ltrim(trim($path, "=\"'"), '=');
            }

            public function redirect($url, $method = false, $status = null)
            {
                throw new \RuntimeException('redirect:' . $url . ':' . (string) $status);
            }

            public function encode_email($email, $title = '')
            {
                return '[encoded:' . $email . ']';
            }

            public function add_form_security_hash($str)
            {
                return $str . '|csrf';
            }

            public function insert_action_ids($str)
            {
                return $str . '|action_ids';
            }
        };
        ee()->setMock('functions', $functionsMock);

        $variablesParser = new class {
            public function parseModifiedVariables($str, $variables = [])
            {
                return $str;
            }
        };
        ee()->setMock('Variables/Parser', $variablesParser);
    }

    public function testParseGlobalsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'parse_globals'));
        $this->assertTrue(is_callable([$this->template, 'parse_globals']));
    }

    public function testParseGlobalsCanBeCalled()
    {
        $this->setupStandardGlobalsDependenciesForParseGlobals();

        $result = $this->template->parse_globals('basic');
        $this->assertIsString($result);
    }

    public function testParseGlobalsHandlesExternalRedirectWithStatusCode()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('redirect:https://example.com/next:307');

        $functionsMock = new class {
            public function redirect($url, $method = false, $status = null)
            {
                throw new \RuntimeException('redirect:' . $url . ':' . (string) $status);
            }

            public function create_url($path)
            {
                return 'https://example.com/' . ltrim($path, '/');
            }

            public function extract_path($path)
            {
                return $path;
            }
        };
        ee()->setMock('functions', $functionsMock);

        $this->template->parse_globals('{redirect="https://example.com/next" status_code="307"}');
    }

    public function testParseGlobalsHandlesInternalRedirect()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('redirect:https://example.com/account/login:');

        $functionsMock = new class {
            public function redirect($url, $method = false, $status = null)
            {
                throw new \RuntimeException('redirect:' . $url . ':' . (string) $status);
            }

            public function create_url($path)
            {
                return 'https://example.com/' . ltrim($path, '/');
            }

            public function extract_path($path)
            {
                return trim($path, '="');
            }
        };
        ee()->setMock('functions', $functionsMock);

        $this->template->parse_globals('{redirect="account/login"}');
    }

    public function testParseGlobalsProcessesAssetsPathsRoutesAndEncodeReplacement()
    {
        if (!defined('CSRF_TOKEN')) {
            define('CSRF_TOKEN', 'test-csrf-token');
        }

        $this->setupStandardGlobalsDependenciesForParseGlobals();
        $this->template->encode_email = false;
        $this->template->template = 'tmpl';

        $str = implode(' ', [
            '{stylesheet="assets/app"}',
            '{script="assets/app"}',
            '{encode="hello@example.com"}',
            '{path=blog/index}',
            '{route=blog/index foo="bar"}',
            '{my_global}',
            '<form method="post"></form>',
        ]);

        $result = $this->template->parse_globals($str);

        $this->assertStringContainsString('css=assets/app.v.1700000000', $result);
        $this->assertStringContainsString('js=assets/app.v.1700000000', $result);
        $this->assertStringContainsString('[email removed]', $result);
        $this->assertStringContainsString('https://example.com/blog/index', $result);
        $this->assertStringContainsString('/route/blog/index', $result);
        $this->assertStringContainsString('global-value', $result);
        $this->assertStringContainsString('|csrf|action_ids', $result);
    }

    public function testParseGlobalsProcessesSitePrefixedAssetsTemplateFilesAndEncodedEmail()
    {
        $this->setupStandardGlobalsDependenciesForParseGlobals();

        ee()->config->setItem('save_tmpl_files', 'y');
        $this->template->encode_email = true;

        $assetDir = PATH_TMPL . 'default_site/assets.group';
        if (!is_dir($assetDir)) {
            mkdir($assetDir, 0777, true);
        }
        $assetFile = $assetDir . '/app.css';
        file_put_contents($assetFile, 'body { color: black; }');
        touch($assetFile, 1800000000);

        $result = $this->template->parse_globals(
            '{stylesheet="default_site:assets/app"} {encode="person@example.com"}'
        );

        $this->assertStringContainsString('css=default_site:assets/app.v.1800000000', $result);
        $this->assertStringContainsString('[encoded:"person@example.com"]', $result);

        @unlink($assetFile);
        @rmdir($assetDir);
        @rmdir(PATH_TMPL . 'default_site');
    }

    public function testParseGlobalsRedirect404InvokesShow404()
    {
        $templateMock = $this->getMockBuilder(\EE_Template::class)
            ->onlyMethods(['show_404'])
            ->getMock();
        $templateMock->method('show_404')
            ->willThrowException(new \RuntimeException('show_404_called'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('show_404_called');

        $templateMock->parse_globals('{redirect="404"}');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testParseGlobalsAppendsAmpersandWhenQueryMarkerDisabled()
    {
        if (!defined('QUERY_MARKER')) {
            define('QUERY_MARKER', false);
        }

        $this->setupStandardGlobalsDependenciesForParseGlobals();

        $functionsMock = new class {
            public function fetch_current_uri()
            {
                return 'news/index';
            }

            public function fetch_site_index($a = 0, $b = 0)
            {
                return '/index.php';
            }

            public function create_url($match)
            {
                $path = is_array($match) ? ($match[1] ?? '') : $match;

                return 'https://example.com/' . ltrim(trim($path, "\"'"), '/');
            }

            public function create_route($match)
            {
                $path = is_array($match) ? ($match[1] ?? '') : $match;
                $path = trim($path, "\"'");
                $parts = preg_split('/\s+/', $path);

                return '/route/' . ($parts[0] ?? '');
            }

            public function extract_path($path)
            {
                return ltrim(trim($path, "=\"'"), '=');
            }

            public function redirect($url, $method = false, $status = null)
            {
                throw new \RuntimeException('redirect:' . $url . ':' . (string) $status);
            }

            public function encode_email($email, $title = '')
            {
                return '[encoded:' . $email . ']';
            }

            public function add_form_security_hash($str)
            {
                return $str;
            }

            public function insert_action_ids($str)
            {
                return $str;
            }
        };
        ee()->setMock('functions', $functionsMock);

        $result = $this->template->parse_globals('{stylesheet="assets/app"}');

        $this->assertStringContainsString('/index.php&css=assets/app', $result);
    }

    public function testParseGlobalsMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'parse_globals');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('str', $parameters[0]->getName());
    }
}
