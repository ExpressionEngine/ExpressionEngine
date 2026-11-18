<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

use PHPUnit\Framework\TestCase;

class EE_TemplateParseFronteditCoverageTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testParseSetsFronteditGlobalWhenAllRequirementsAreMet()
    {
        if (!defined('SYSPATH')) {
            define('SYSPATH', realpath(getcwd() . '/system/') . '/');
        }
        if (!defined('BASEPATH')) {
            define('BASEPATH', SYSPATH . 'ee/legacy/');
        }
        if (!defined('PATH_CACHE')) {
            define('PATH_CACHE', BASEPATH . 'system/ee/cache/');
        }
        if (!defined('PATH_TMPL')) {
            define('PATH_TMPL', BASEPATH . 'user/templates/');
        }
        if (!defined('LD')) {
            define('LD', '{');
        }
        if (!defined('RD')) {
            define('RD', '}');
        }
        if (!defined('AJAX_REQUEST')) {
            define('AJAX_REQUEST', false);
        }
        if (!defined('REQ')) {
            define('REQ', 'PAGE');
        }

        require_once __DIR__ . '/../../../eeObjectMock.php';
        require_once SYSPATH . 'ee/legacy/libraries/Template.php';

        ee()->resetMocks();

        $configMock = new class extends \FakeConfig {
            public function site_url()
            {
                return 'https://example.com/';
            }
        };
        $configMock->items = [
            'smart_static_parsing' => 'y',
            'site_id' => 1,
            'site_label' => 'Example Site',
            'site_short_name' => 'default_site',
            'site_name' => 'Example Site',
            'site_url' => 'https://example.com/',
            'site_description' => 'Description',
            'site_index' => '',
            'webmaster_email' => 'admin@example.com',
            'enable_frontedit' => 'y',
        ];
        $configMock->_global_vars = [];
        ee()->setMock('config', $configMock);

        $sessionMock = new \eeSingletonSessionMock();
        $sessionMock->setUserdata('admin_sess', 1);
        $sessionMock->setUserdata('role_id', 1);
        ee()->setMock('session', $sessionMock);

        $functionsMock = new class {
            public function fetch_current_uri()
            {
                return 'news/article';
            }
        };
        ee()->setMock('functions', $functionsMock);

        $uriMock = new class {
            public $uri_string = 'news/article';

            public function segment_array()
            {
                return ['news', 'article'];
            }
        };
        ee()->setMock('uri', $uriMock);

        $inputMock = new class {
            public function cookie($key)
            {
                return '';
            }
        };
        ee()->setMock('input', $inputMock);

        $livePreviewMock = new class {
            public function hasEntryData()
            {
                return false;
            }
        };
        ee()->setMock('LivePreview', $livePreviewMock);

        $proAccessMock = new class {
            public function hasRequiredLicense()
            {
                return true;
            }

            public function hasDockPermission()
            {
                return true;
            }
        };
        ee()->setMock('pro:Access', $proAccessMock);

        $variablesParserMock = new class {
            public function parseModifiedVariables($template, $vars = [])
            {
                return $template;
            }
        };
        ee()->setMock('Variables/Parser', $variablesParserMock);

        $tmplMock = new class {
            public $template_type = 'webpage';
            public $enable_frontedit = 'y';
        };
        ee()->setMock('TMPL', $tmplMock);

        $template = new class extends \EE_Template {
            public function getMemberVariables()
            {
                return [];
            }

            public function markContext($context = null)
            {
                throw new \RuntimeException('frontedit-coverage-stop');
            }
        };
        $template->template_type = 'webpage';
        $template->template_name = 'index';
        $template->group_name = 'news';
        $template->template_group_id = 1;
        $template->template_id = 1;

        $input = '{has_tags}';

        try {
            $template->parse($input, false);
            $this->fail('Expected parse to stop after frontedit branch');
        } catch (\RuntimeException $e) {
            $this->assertSame('frontedit-coverage-stop', $e->getMessage());
        }

        $this->assertTrue((bool) ee()->config->_global_vars['frontedit']);
    }
}
