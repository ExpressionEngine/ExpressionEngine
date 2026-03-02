<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Controllers\Utilities;

use PHPUnit\Framework\TestCase;

class TranslateTest extends TestCase
{
    private $controller;

    public static function setUpBeforeClass(): void
    {
        require_once(SYSPATH . 'ee/ExpressionEngine/Tests/support/translate_function_overrides.php');
        require_once(SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php');
        require_once(APPPATH . 'core/Controller.php');
        require_once(SYSPATH . 'ee/ExpressionEngine/Tests/eeObjectMock.php');
    }

    public function setUp(): void
    {
        if (function_exists('ee')) {
            ee()->resetMocks();
        }

        \ExpressionEngine\Controller\Utilities\TranslateTestFunctionOverrides::$isReallyWritable = null;
        \ExpressionEngine\Controller\Utilities\TranslateTestFunctionOverrides::$isReadable = null;
        \ExpressionEngine\Controller\Utilities\TranslateTestFunctionOverrides::$throwOnForceDownload = false;
        \ExpressionEngine\Controller\Utilities\TranslateTestFunctionOverrides::$tempnamPath = null;

        $reflection = new \ReflectionClass('ExpressionEngine\Controller\Utilities\Translate');
        $this->controller = $reflection->newInstanceWithoutConstructor();
    }

    public function tearDown(): void
    {
        if (function_exists('ee')) {
            ee()->resetMocks();
        }

        \ExpressionEngine\Controller\Utilities\TranslateTestFunctionOverrides::$isReallyWritable = null;
        \ExpressionEngine\Controller\Utilities\TranslateTestFunctionOverrides::$isReadable = null;
        \ExpressionEngine\Controller\Utilities\TranslateTestFunctionOverrides::$throwOnForceDownload = false;
        \ExpressionEngine\Controller\Utilities\TranslateTestFunctionOverrides::$tempnamPath = null;

        $this->controller = null;
    }

    public function testIndexRendersLanguagesListAndPagination()
    {
        ee()->config->setItem('deft_lang', 'english');
        ee()->setMock('lang', new class {
            public function load($item)
            {
                return true;
            }

            public function line($key)
            {
                return $key;
            }

            public function language_pack_names()
            {
                return [
                    'english' => 'English',
                    'french' => 'French',
                ];
            }
        });
        ee()->setMock('view', (object) []);
        ee()->setMock('CP/URL', $this->makeUrlFactory());

        $table = new class {
            public $data = [];

            public function setColumns($columns)
            {
                return $this;
            }

            public function setNoResultsText($text)
            {
                return $this;
            }

            public function setData($data)
            {
                $this->data = $data;

                return $this;
            }

            public function viewData($baseUrl)
            {
                return [
                    'data' => $this->data,
                    'total_rows' => count($this->data),
                    'limit' => 25,
                    'page' => 1,
                    'search' => 'english',
                ];
            }
        };
        ee()->setMock('CP/Table', $table);

        $pagination = new class {
            public function perPage($limit)
            {
                return $this;
            }

            public function currentPage($page)
            {
                return $this;
            }

            public function render($base_url)
            {
                return 'pagination-html';
            }
        };
        ee()->setMock('CP/Pagination', $pagination);

        $cp = new class {
            public $calls = [];

            public function render($view, $vars)
            {
                $this->calls[] = [$view, $vars];

                return true;
            }
        };
        ee()->setMock('cp', $cp);

        $this->controller->index();

        $this->assertSame('cp_translations', ee()->view->cp_page_title);
        $this->assertSame('pagination-html', $cp->calls[0][1]['pagination']);
        $this->assertSame('utilities/translate/languages', $cp->calls[0][0]);
    }

    public function testRoutableMethods()
    {
        $controller_methods = array();

        foreach (get_class_methods('ExpressionEngine\Controller\Utilities\Translate') as $method) {
            $method = strtolower($method);
            if (strncmp($method, '_', 1) != 0) {
                $controller_methods[] = $method;
            }
        }

        sort($controller_methods);

        // This one has more routable functions due to __call(), we need to
        // test those as well @TODO
        $this->assertEquals(array('index'), $controller_methods);
    }

    public function testGetAllowedTranslationKeysSkipsEmptyKey()
    {
        ee()->setMock('lang', new class {
            public function load($file, $language, $return)
            {
                return [
                    'alpha' => 'Alpha',
                    '' => '',
                    'beta' => 'Beta'
                ];
            }
        });

        $allowed = $this->invokePrivateMethod('getAllowedTranslationKeys', ['addons']);

        $this->assertSame(['alpha', 'beta'], $allowed);
    }

    public function testGetAllowedTranslationKeysReturnsEmptyArrayWhenLangLoadIsNotArray()
    {
        ee()->setMock('lang', new class {
            public function load($file, $language, $return)
            {
                return 'not-an-array';
            }
        });

        $allowed = $this->invokePrivateMethod('getAllowedTranslationKeys', ['addons']);

        $this->assertSame([], $allowed);
    }

    public function testNormalizeSubmittedTranslationsRejectsUnexpectedKeys()
    {
        $payload_key = "x' => `id`, 'y";
        $normalized = $this->invokePrivateMethod('normalizeSubmittedTranslations', [[
            'csrf_token' => 'token',
            'alpha' => 'ok',
            $payload_key => 'pwned'
        ], ['alpha'], function ($value) {
            return $value;
        }]);

        $this->assertFalse($normalized['is_valid']);
        $this->assertSame([$payload_key], $normalized['unexpected_keys']);
        $this->assertSame([], $normalized['invalid_value_keys']);
    }

    public function testNormalizeSubmittedTranslationsUsesAllowlistOrderAndKnownFormKeys()
    {
        $normalized = $this->invokePrivateMethod('normalizeSubmittedTranslations', [[
            'csrf_token' => 'token',
            'second' => '2',
            'first' => '1',
            'site_id' => '1'
        ], ['first', 'second'], function ($value) {
            return $value;
        }]);

        $this->assertTrue($normalized['is_valid']);
        $this->assertSame(['first', 'second'], array_keys($normalized['translations']));
        $this->assertSame(['first' => '1', 'second' => '2'], $normalized['translations']);
    }

    public function testNormalizeSubmittedTranslationsRejectsNonScalarValues()
    {
        $normalized = $this->invokePrivateMethod('normalizeSubmittedTranslations', [[
            'alpha' => ['nested' => 'value'],
            'beta' => 'ok'
        ], ['alpha', 'beta'], function ($value) {
            return $value;
        }]);

        $this->assertFalse($normalized['is_valid']);
        $this->assertSame(['alpha'], $normalized['invalid_value_keys']);
        $this->assertSame(['beta' => 'ok'], $normalized['translations']);
    }

    public function testNormalizeSubmittedTranslationsUsesDefaultCleanerForNonArrayPostInput()
    {
        $xss = new class {
            public $values = [];

            public function clean($value)
            {
                $this->values[] = $value;

                return 'cleaned:' . $value;
            }
        };
        ee()->setMock('Security/XSS', $xss);

        $normalized = $this->invokePrivateMethod('normalizeSubmittedTranslations', [
            'not-an-array',
            ['alpha']
        ]);

        $this->assertTrue($normalized['is_valid']);
        $this->assertSame(['alpha' => 'cleaned:'], $normalized['translations']);
        $this->assertSame([''], $xss->values);
    }

    public function testRenderLanguagePhpProducesLoadableSafeArray()
    {
        $translations = [
            "x' => `id`, 'y" => "keep `backticks` as text",
            'path' => 'C:\\temp\\data'
        ];

        $php = $this->invokePrivateMethod('renderLanguagePhp', [$translations]);
        $tmp = tempnam(sys_get_temp_dir(), 'translate_test_');
        file_put_contents($tmp, $php);

        $lang = null;
        require $tmp;
        unlink($tmp);

        $this->assertIsArray($lang);
        $this->assertSame($translations["x' => `id`, 'y"], $lang["x' => `id`, 'y"]);
        $this->assertSame($translations['path'], $lang['path']);
        $this->assertSame('', $lang['']);
    }

    public function testGetLanguageDirectoryReturnsExistingUserLanguagePath()
    {
        $language = 'unittest_' . uniqid();
        $path = SYSPATH . 'user/language/' . $language;
        mkdir($path, 0777, true);

        try {
            $result = $this->invokePrivateMethod('getLanguageDirectory', [$language]);

            $this->assertSame($path . '/', $result);
        } finally {
            @rmdir($path);
        }
    }

    public function testGetLanguageDirectoryShowsIssueWhenMissing()
    {
        ee()->setMock('lang', new class {
            public function line($key)
            {
                return $key;
            }
        });

        $alert = $this->makeAlertRecorder(true);
        ee()->setMock('CP/Alert', $alert);

        $result = $this->invokePrivateMethod('getLanguageDirectory', ['missing_' . uniqid()]);

        $this->assertSame('', $result);
        $this->assertSame('now', $alert->calls[count($alert->calls) - 1][0]);
    }

    public function testExportWithEmptySelectionShowsIssueMessageAndReturns()
    {
        ee()->setMock('lang', new class {
            public function line($key)
            {
                return $key;
            }
        });

        $view = new class {
            public $messages = [];

            public function set_message($type, $message)
            {
                $this->messages[] = [$type, $message];
            }
        };
        ee()->setMock('view', $view);

        $this->invokePrivateMethod('export', ['english', []]);

        $this->assertSame([['issue', 'no_files_selected']], $view->messages);
    }

    public function testExportWithUnreadableSelectedFileShowsIssueMessageAndReturns()
    {
        ee()->setMock('lang', new class {
            public function line($key)
            {
                return $key;
            }
        });

        $language = 'unittestexport_' . uniqid();
        $languageDir = SYSPATH . 'user/language/' . $language;
        mkdir($languageDir, 0777, true);

        $view = new class {
            public $messages = [];

            public function set_message($type, $message)
            {
                $this->messages[] = [$type, $message];
            }
        };
        ee()->setMock('view', $view);

        try {
            $this->invokePrivateMethod('export', [$language, ['missing_file']]);
        } finally {
            @rmdir($languageDir);
        }

        $this->assertSame('issue', $view->messages[0][0]);
        $this->assertStringContainsString('missing_file_lang.php', $view->messages[0][1]);
    }

    public function testExportBuildsZipAndLoadsDownloadHelperBeforeSendingFile()
    {
        ee()->setMock('lang', new class {
            public function line($key)
            {
                return $key;
            }
        });

        $language = 'unittestexportzip_' . uniqid();
        $languageDir = SYSPATH . 'user/language/' . $language;
        mkdir($languageDir, 0777, true);
        file_put_contents($languageDir . '/addons_lang.php', "<?php\n\$lang = ['sample' => 'Sample'];\n");

        $load = new class {
            public $helpers = [];

            public function helper($name)
            {
                $this->helpers[] = $name;
            }
        };
        ee()->setMock('load', $load);
        ee()->setMock('view', new class {
            public function set_message($type, $message)
            {
                return true;
            }
        });

        try {
            \ExpressionEngine\Controller\Utilities\TranslateTestFunctionOverrides::$throwOnForceDownload = true;
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('stop-before-exit');
            $this->invokePrivateMethod('export', [$language, ['addons']]);
        } finally {
            @unlink($languageDir . '/addons_lang.php');
            @rmdir($languageDir);
        }

        $this->assertSame(['download'], $load->helpers);
    }

    public function testExportShowsIssueWhenZipCannotBeCreated()
    {
        ee()->setMock('lang', new class {
            public function line($key)
            {
                return $key;
            }
        });

        $language = 'unittestexportzipfail_' . uniqid();
        $languageDir = SYSPATH . 'user/language/' . $language;
        mkdir($languageDir, 0777, true);
        file_put_contents($languageDir . '/addons_lang.php', "<?php\n\$lang = ['sample' => 'Sample'];\n");

        $view = new class {
            public $messages = [];

            public function set_message($type, $message)
            {
                $this->messages[] = [$type, $message];
            }
        };
        ee()->setMock('view', $view);
        ee()->setMock('load', new class {
            public function helper($name)
            {
                return true;
            }
        });

        try {
            \ExpressionEngine\Controller\Utilities\TranslateTestFunctionOverrides::$tempnamPath = sys_get_temp_dir();
            $this->invokePrivateMethod('export', [$language, ['addons']]);
        } finally {
            @unlink($languageDir . '/addons_lang.php');
            @rmdir($languageDir);
        }

        $this->assertSame('issue', $view->messages[0][0]);
        $this->assertSame('cannot_create_zip', $view->messages[0][1]);
    }

    public function testListFilesRendersTranslateListView()
    {
        $language = 'unittestlist_' . uniqid();
        $languageDir = SYSPATH . 'user/language/' . $language;
        mkdir($languageDir, 0777, true);
        file_put_contents($languageDir . '/addons_lang.php', "<?php\n\$lang = [];\n");

        ee()->setMock('lang', new class {
            public function line($key)
            {
                return $key;
            }
        });
        ee()->setMock('input', new class {
            public function get_post($item)
            {
                return null;
            }
        });
        ee()->setMock('view', (object) []);
        ee()->setMock('load', new class {
            public function helper($name)
            {
                if ($name === 'file') {
                    require_once BASEPATH . 'helpers/file_helper.php';
                }
            }
        });
        ee()->setMock('CP/URL', $this->makeUrlFactory());

        $table = new class {
            public function setColumns($columns)
            {
                return $this;
            }

            public function setNoResultsText($text)
            {
                return $this;
            }

            public function setData($data)
            {
                return $this;
            }

            public function viewData($baseUrl)
            {
                return [
                    'data' => [['row' => true]],
                    'total_rows' => 1,
                    'limit' => 25,
                    'page' => 1,
                    'search' => '',
                    'base_url' => $baseUrl,
                ];
            }
        };
        ee()->setMock('CP/Table', $table);

        $pagination = new class {
            public function perPage($limit)
            {
                return $this;
            }

            public function currentPage($page)
            {
                return $this;
            }

            public function render($base_url)
            {
                return 'pagination-list';
            }
        };
        ee()->setMock('CP/Pagination', $pagination);

        $cp = new class {
            public $calls = [];

            public function render($view, $vars)
            {
                $this->calls[] = [$view, $vars];
            }
        };
        ee()->setMock('cp', $cp);

        try {
            $this->invokePrivateMethod('listFiles', [$language]);
        } finally {
            @unlink($languageDir . '/addons_lang.php');
            @rmdir($languageDir);
        }

        $this->assertSame('utilities/translate/list', $cp->calls[0][0]);
    }

    public function testEditReturnsRenderedFormForMissingUserTranslationFile()
    {
        $language = 'unittestedit_' . uniqid();
        $languageDir = SYSPATH . 'user/language/' . $language;
        mkdir($languageDir, 0777, true);

        ee()->setMock('security', new class {
            public function sanitize_filename($file)
            {
                return $file;
            }
        });
        ee()->setMock('lang', new class {
            public function load($file, $language = null, $return = false)
            {
                if ($return) {
                    return ['sample_key' => 'Sample Value'];
                }

                return true;
            }

            public function line($key)
            {
                return $key;
            }
        });
        ee()->setMock('Format', new class {
            public function make($type, $value)
            {
                return new class($value) {
                    private $value;

                    public function __construct($value)
                    {
                        $this->value = $value;
                    }

                    public function convertToEntities()
                    {
                        return $this;
                    }

                    public function compile()
                    {
                        return $this->value;
                    }
                };
            }
        });
        ee()->setMock('CP/URL', $this->makeUrlFactory());
        ee()->setMock('view', (object) []);

        $cp = new class {
            public function render($view, $vars)
            {
                return ['view' => $view, 'vars' => $vars];
            }
        };
        ee()->setMock('cp', $cp);

        try {
            $result = $this->invokePrivateMethod('edit', [$language, 'addons']);
        } finally {
            @rmdir($languageDir);
        }

        $this->assertSame('settings/form', $result['view']);
        $this->assertArrayHasKey('sections', $result['vars']);
    }

    public function testEditFallsBackAndAlertsWhenTranslationFileContainsInvalidLangVariable()
    {
        $language = 'unittesteditbad_' . uniqid();
        $languageDir = SYSPATH . 'user/language/' . $language;
        mkdir($languageDir, 0777, true);
        file_put_contents($languageDir . '/addons_lang.php', "<?php\n\$lang = 'invalid';\n");

        ee()->setMock('security', new class {
            public function sanitize_filename($file)
            {
                return $file;
            }
        });
        ee()->setMock('lang', new class {
            public function load($file, $language = null, $return = false)
            {
                if ($return) {
                    return ['sample_key' => 'Sample Value'];
                }

                return true;
            }

            public function line($key)
            {
                return $key;
            }
        });
        ee()->setMock('Format', new class {
            public function make($type, $value)
            {
                return new class($value) {
                    private $value;

                    public function __construct($value)
                    {
                        $this->value = $value;
                    }

                    public function convertToEntities()
                    {
                        return $this;
                    }

                    public function compile()
                    {
                        return $this->value;
                    }
                };
            }
        });
        ee()->setMock('CP/URL', $this->makeUrlFactory());
        ee()->setMock('view', (object) []);
        $alerts = $this->makeAlertRecorder();
        ee()->setMock('CP/Alert', $alerts);

        $cp = new class {
            public function render($view, $vars)
            {
                return ['view' => $view, 'vars' => $vars];
            }
        };
        ee()->setMock('cp', $cp);

        try {
            $result = $this->invokePrivateMethod('edit', [$language, 'addons']);
        } finally {
            @unlink($languageDir . '/addons_lang.php');
            @rmdir($languageDir);
        }

        $this->assertSame('settings/form', $result['view']);
        $this->assertSame('cannot_access', $alerts->title);
    }

    public function testEditUsesValidUserTranslationFileValuesWhenPresent()
    {
        $language = 'unittesteditvalid_' . uniqid();
        $languageDir = SYSPATH . 'user/language/' . $language;
        mkdir($languageDir, 0777, true);
        file_put_contents($languageDir . '/addons_lang.php', "<?php\n\$lang = ['sample_key' => 'Stored Value'];\n");

        ee()->setMock('security', new class {
            public function sanitize_filename($file)
            {
                return $file;
            }
        });
        ee()->setMock('lang', new class {
            public function load($file, $language = null, $return = false)
            {
                if ($return) {
                    return ['sample_key' => 'Sample Value'];
                }

                return true;
            }

            public function line($key)
            {
                return $key;
            }
        });
        ee()->setMock('Format', new class {
            public function make($type, $value)
            {
                return new class($value) {
                    private $value;

                    public function __construct($value)
                    {
                        $this->value = $value;
                    }

                    public function convertToEntities()
                    {
                        return $this;
                    }

                    public function compile()
                    {
                        return $this->value;
                    }
                };
            }
        });
        ee()->setMock('CP/URL', $this->makeUrlFactory());
        ee()->setMock('view', (object) []);

        $cp = new class {
            public function render($view, $vars)
            {
                return ['view' => $view, 'vars' => $vars];
            }
        };
        ee()->setMock('cp', $cp);

        try {
            $result = $this->invokePrivateMethod('edit', [$language, 'addons']);
        } finally {
            @unlink($languageDir . '/addons_lang.php');
            @rmdir($languageDir);
        }

        $field = $result['vars']['sections'][0][0]['fields']['sample_key'];
        $this->assertSame('Stored Value', $field['value']);
        $this->assertSame('settings/form', $result['view']);
    }

    public function testEditSetsIssueAndRedirectsWhenExistingFileIsUnreadable()
    {
        $language = 'unittesteditunreadable_' . uniqid();
        $languageDir = SYSPATH . 'user/language/' . $language;
        mkdir($languageDir, 0777, true);
        $targetFile = $languageDir . '/addons_lang.php';
        file_put_contents($targetFile, "<?php\n\$lang = ['sample_key' => 'Sample'];\n");

        ee()->setMock('security', new class {
            public function sanitize_filename($file)
            {
                return $file;
            }
        });
        ee()->setMock('lang', new class {
            public function load($file, $language = null, $return = false)
            {
                if ($return) {
                    return ['sample_key' => 'Sample Value'];
                }

                return true;
            }

            public function line($key)
            {
                return $key;
            }
        });
        ee()->setMock('Format', new class {
            public function make($type, $value)
            {
                return new class($value) {
                    private $value;

                    public function __construct($value)
                    {
                        $this->value = $value;
                    }

                    public function convertToEntities()
                    {
                        return $this;
                    }

                    public function compile()
                    {
                        return $this->value;
                    }
                };
            }
        });
        ee()->setMock('CP/URL', $this->makeUrlFactory());
        $view = new class {
            public $messages = [];

            public function set_message($type, $message, $a = '', $b = false)
            {
                $this->messages[] = [$type, $message];
            }
        };
        ee()->setMock('view', $view);
        $functions = new class {
            public $redirects = [];

            public function redirect($url)
            {
                $this->redirects[] = $url;
            }
        };
        ee()->setMock('functions', $functions);

        $cp = new class {
            public function render($view, $vars)
            {
                return ['view' => $view, 'vars' => $vars];
            }
        };
        ee()->setMock('cp', $cp);

        try {
            \ExpressionEngine\Controller\Utilities\TranslateTestFunctionOverrides::$isReadable = false;
            $result = $this->invokePrivateMethod('edit', [$language, 'addons']);
        } finally {
            @unlink($targetFile);
            @rmdir($languageDir);
        }

        $this->assertNotEmpty($functions->redirects);
        $this->assertSame('issue', $view->messages[0][0]);
        $this->assertStringContainsString('cannot_access', $view->messages[0][1]);
        $this->assertSame('settings/form', $result['view']);
    }

    public function testSaveWritesTranslationFileForValidPostData()
    {
        $baseDir = sys_get_temp_dir() . '/ee_translate_' . uniqid() . '/';
        $language = 'qa';
        $languageDir = $baseDir . $language;
        mkdir($languageDir, 0777, true);

        $_POST = ['alpha' => 'Saved Value'];

        ee()->setMock('security', new class {
            public function sanitize_filename($file)
            {
                return $file;
            }
        });
        ee()->setMock('lang', new class {
            public function loadfile($file)
            {
                return true;
            }

            public function load($file, $language = null, $return = false)
            {
                if ($return) {
                    return ['alpha' => 'Alpha'];
                }

                return true;
            }

            public function line($key)
            {
                return $key;
            }
        });
        ee()->setMock('Security/XSS', new class {
            public function clean($value)
            {
                return $value;
            }
        });
        ee()->setMock('CP/Alert', $this->makeAlertRecorder());
        ee()->setMock('CP/URL', $this->makeUrlFactory());
        ee()->setMock('functions', new class {
            public $redirects = [];

            public function redirect($url)
            {
                $this->redirects[] = $url;
            }
        });

        $this->controller->load = new class {
            public function helper($name)
            {
                if ($name === 'file') {
                    require_once BASEPATH . 'helpers/file_helper.php';
                }
            }
        };
        $this->setControllerProperty('languages_dir', $baseDir);

        try {
            $this->invokePrivateMethod('save', [$language, 'addons']);

            $saved = file_get_contents($languageDir . '/addons_lang.php');
            $this->assertStringContainsString("'alpha' => 'Saved Value'", $saved);
        } finally {
            $_POST = [];
            @unlink($languageDir . '/addons_lang.php');
            @rmdir($languageDir);
            @rmdir($baseDir);
        }
    }

    public function testSaveRejectsUnexpectedPostedKeysAndDoesNotWriteFile()
    {
        $baseDir = sys_get_temp_dir() . '/ee_translate_' . uniqid() . '/';
        $language = 'qa';
        $languageDir = $baseDir . $language;
        mkdir($languageDir, 0777, true);

        $_POST = [
            'alpha' => 'ok',
            "x' => `id`, 'y" => 'boom',
        ];

        ee()->setMock('security', new class {
            public function sanitize_filename($file)
            {
                return $file;
            }
        });
        ee()->setMock('lang', new class {
            public function loadfile($file)
            {
                return true;
            }

            public function load($file, $language = null, $return = false)
            {
                if ($return) {
                    return ['alpha' => 'Alpha'];
                }

                return true;
            }

            public function line($key)
            {
                return $key;
            }
        });
        ee()->setMock('Security/XSS', new class {
            public function clean($value)
            {
                return $value;
            }
        });
        $alerts = $this->makeAlertRecorder();
        ee()->setMock('CP/Alert', $alerts);
        ee()->setMock('CP/URL', $this->makeUrlFactory());
        ee()->setMock('functions', new class {
            public function redirect($url)
            {
                return true;
            }
        });
        $this->setControllerProperty('languages_dir', $baseDir);

        try {
            $this->invokePrivateMethod('save', [$language, 'addons']);

            $this->assertFileDoesNotExist($languageDir . '/addons_lang.php');
            $this->assertSame('translate_error', $alerts->title);
        } finally {
            $_POST = [];
            @rmdir($languageDir);
            @rmdir($baseDir);
        }
    }

    public function testSaveShowsInvalidPathAlertWhenWriteFails()
    {
        $baseDir = sys_get_temp_dir() . '/ee_translate_' . uniqid() . '/';
        $language = 'qa_missing_dir';
        mkdir($baseDir, 0777, true);

        $_POST = ['alpha' => 'ok'];

        ee()->setMock('security', new class {
            public function sanitize_filename($file)
            {
                return $file;
            }
        });
        ee()->setMock('lang', new class {
            public function loadfile($file)
            {
                return true;
            }

            public function load($file, $language = null, $return = false)
            {
                if ($return) {
                    return ['alpha' => 'Alpha'];
                }

                return true;
            }

            public function line($key)
            {
                return $key;
            }
        });
        ee()->setMock('Security/XSS', new class {
            public function clean($value)
            {
                return $value;
            }
        });
        $alerts = $this->makeAlertRecorder();
        ee()->setMock('CP/Alert', $alerts);
        ee()->setMock('CP/URL', $this->makeUrlFactory());
        ee()->setMock('functions', new class {
            public function redirect($url)
            {
                return true;
            }
        });
        $this->controller->load = new class {
            public function helper($name)
            {
                if ($name === 'file') {
                    require_once BASEPATH . 'helpers/file_helper.php';
                }
            }
        };
        $this->setControllerProperty('languages_dir', $baseDir);

        try {
            $this->invokePrivateMethod('save', [$language, 'addons']);

            $this->assertSame('invalid_path', $alerts->title);
        } finally {
            $_POST = [];
            @rmdir($baseDir);
        }
    }

    public function testSaveChecksWritableExistingFileBeforeOverwrite()
    {
        require_once BASEPATH . 'helpers/file_helper.php';

        $baseDir = sys_get_temp_dir() . '/ee_translate_' . uniqid() . '/';
        $language = 'qa_overwrite';
        $languageDir = $baseDir . $language;
        mkdir($languageDir, 0777, true);
        $destFile = $languageDir . '/addons_lang.php';
        file_put_contents($destFile, "<?php\n\$lang = ['alpha' => 'old'];\n");

        $_POST = ['alpha' => 'new'];

        ee()->setMock('security', new class {
            public function sanitize_filename($file)
            {
                return $file;
            }
        });
        ee()->setMock('lang', new class {
            public function loadfile($file)
            {
                return true;
            }

            public function load($file, $language = null, $return = false)
            {
                if ($return) {
                    return ['alpha' => 'Alpha'];
                }

                return true;
            }

            public function line($key)
            {
                return $key;
            }
        });
        ee()->setMock('Security/XSS', new class {
            public function clean($value)
            {
                return $value;
            }
        });
        ee()->setMock('CP/Alert', $this->makeAlertRecorder());
        ee()->setMock('CP/URL', $this->makeUrlFactory());
        ee()->setMock('functions', new class {
            public function redirect($url)
            {
                return true;
            }
        });
        $this->controller->load = new class {
            public function helper($name)
            {
                if ($name === 'file') {
                    require_once BASEPATH . 'helpers/file_helper.php';
                }
            }
        };
        $this->setControllerProperty('languages_dir', $baseDir);

        try {
            $this->invokePrivateMethod('save', [$language, 'addons']);
            $saved = file_get_contents($destFile);
        } finally {
            $_POST = [];
            @unlink($destFile);
            @rmdir($languageDir);
            @rmdir($baseDir);
        }

        $this->assertStringContainsString("'alpha' => 'new'", $saved);
    }

    public function testSaveShowsNotWritableAlertWhenExistingFileCannotBeWritten()
    {
        require_once BASEPATH . 'helpers/file_helper.php';

        $baseDir = sys_get_temp_dir() . '/ee_translate_' . uniqid() . '/';
        $language = 'qa_readonly';
        $languageDir = $baseDir . $language;
        mkdir($languageDir, 0777, true);
        $destFile = $languageDir . '/addons_lang.php';
        file_put_contents($destFile, "<?php\n\$lang = ['alpha' => 'old'];\n");
        chmod($destFile, 0444);

        $_POST = ['alpha' => 'new'];

        ee()->setMock('security', new class {
            public function sanitize_filename($file)
            {
                return $file;
            }
        });
        ee()->setMock('lang', new class {
            public function loadfile($file)
            {
                return true;
            }

            public function load($file, $language = null, $return = false)
            {
                if ($return) {
                    return ['alpha' => 'Alpha'];
                }

                return true;
            }

            public function line($key)
            {
                return $key;
            }
        });
        ee()->setMock('Security/XSS', new class {
            public function clean($value)
            {
                return $value;
            }
        });
        $alerts = $this->makeAlertRecorder();
        ee()->setMock('CP/Alert', $alerts);
        ee()->setMock('CP/URL', $this->makeUrlFactory());
        ee()->setMock('functions', new class {
            public function redirect($url)
            {
                return true;
            }
        });
        $this->controller->load = new class {
            public function helper($name)
            {
                if ($name === 'file') {
                    require_once BASEPATH . 'helpers/file_helper.php';
                }
            }
        };
        $this->setControllerProperty('languages_dir', $baseDir);

        try {
            \ExpressionEngine\Controller\Utilities\TranslateTestFunctionOverrides::$isReallyWritable = false;
            $this->invokePrivateMethod('save', [$language, 'addons']);
        } finally {
            $_POST = [];
            chmod($destFile, 0644);
            @unlink($destFile);
            @rmdir($languageDir);
            @rmdir($baseDir);
        }

        $this->assertContains(['withTitle', 'trans_file_not_writable'], $alerts->calls);
    }

    public function testAddInvalidTranslationFileAlertQueuesIssueAlert()
    {
        ee()->setMock('lang', new class {
            public function line($key)
            {
                return $key;
            }
        });

        $alert = new class {
            public $calls = [];

            public function makeInline($name)
            {
                $this->calls[] = ['makeInline', $name];

                return $this;
            }

            public function asIssue()
            {
                $this->calls[] = ['asIssue'];

                return $this;
            }

            public function withTitle($title)
            {
                $this->calls[] = ['withTitle', $title];

                return $this;
            }

            public function addToBody($body)
            {
                $this->calls[] = ['addToBody', $body];

                return $this;
            }

            public function defer()
            {
                $this->calls[] = ['defer'];

                return $this;
            }
        };
        ee()->setMock('CP/Alert', $alert);

        $this->invokePrivateMethod('addInvalidTranslationFileAlert', ['/tmp/poisoned.php']);

        $this->assertSame([
            ['makeInline', 'shared-form'],
            ['asIssue'],
            ['withTitle', 'cannot_access'],
            ['addToBody', '/tmp/poisoned.php'],
            ['defer'],
        ], $alert->calls);
    }

    /**
     * Invoke a private controller method to unit test isolated helper behavior.
     *
     * @param string $method
     * @param array<int, mixed> $args
     * @return mixed
     */
    private function invokePrivateMethod($method, array $args = [])
    {
        $reflection = new \ReflectionMethod($this->controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($this->controller, $args);
    }

    private function setControllerProperty($property, $value): void
    {
        $reflection = new \ReflectionProperty($this->controller, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($this->controller, $value);
    }

    private function makeUrlFactory()
    {
        return new class {
            public function make($path)
            {
                return new class($path) {
                    private $path;

                    public function __construct($path)
                    {
                        $this->path = $path;
                    }

                    public function compile()
                    {
                        return 'mock://' . $this->path;
                    }

                    public function matchesTheRequestedURI()
                    {
                        return false;
                    }
                };
            }
        };
    }

    private function makeAlertRecorder($with_now = false)
    {
        return new class($with_now) {
            public $calls = [];
            public $title;
            private $with_now;

            public function __construct($with_now)
            {
                $this->with_now = $with_now;
            }

            public function makeInline($name)
            {
                $this->calls[] = ['makeInline', $name];

                return $this;
            }

            public function asIssue()
            {
                $this->calls[] = ['asIssue'];

                return $this;
            }

            public function asSuccess()
            {
                $this->calls[] = ['asSuccess'];

                return $this;
            }

            public function withTitle($title)
            {
                $this->title = $title;
                $this->calls[] = ['withTitle', $title];

                return $this;
            }

            public function addToBody($body)
            {
                $this->calls[] = ['addToBody', $body];

                return $this;
            }

            public function defer()
            {
                $this->calls[] = ['defer'];

                return $this;
            }

            public function now()
            {
                if ($this->with_now) {
                    $this->calls[] = ['now'];
                }

                return $this;
            }
        };
    }
}
