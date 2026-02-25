<?php

require_once __DIR__ . '/../ProSearchTestBase.php';
require_once PATH_ADDONS . 'pro_search/mod.pro_search.php';

class ProSearchCatchSearchInputMock
{
    public $postValues = [];
    public $getPostValues = [];

    public function post($key)
    {
        return $this->postValues[$key] ?? null;
    }

    public function get_post($key)
    {
        return $this->getPostValues[$key] ?? null;
    }
}

class ProSearchCatchSearchRedirectException extends RuntimeException
{
    private $redirectUrl;

    public function __construct($redirectUrl)
    {
        parent::__construct('Redirect intercepted');
        $this->redirectUrl = $redirectUrl;
    }

    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }
}

class ProSearchModCatchSearchSecurityTest extends ProSearchTestBase
{
    private $mod;
    private $redirectUrl;
    private $originalGet = [];
    private $originalPost = [];
    private $originalReferer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalReferer = $_SERVER['HTTP_REFERER'] ?? null;
        $_GET = [];
        $_POST = [];

        $appInfo = $this->getMockBuilder('stdClass')
            ->addMethods(['getVersion'])
            ->getMock();
        $appInfo->method('getVersion')->willReturn('1.0.0');

        $app = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
        $app->method('get')->willReturn($appInfo);
        ee()->setMock('App', $app);

        ee()->config->setItem('encryption_key', 'unit-test-key');

        $settings = new class {
            public $prefix = 'pro_search_';
            public function get($key)
            {
                $map = [
                    'encode_query' => 'n',
                    'default_result_page' => 'search/results',
                    'search_log_size' => '0',
                ];

                return $map[$key] ?? '';
            }
        };
        ee()->setMock('pro_search_settings', $settings);

        $functions = $this->getMockBuilder('stdClass')
            ->addMethods(['fetch_action_id', 'form_declaration', 'create_url', 'redirect'])
            ->getMock();
        $functions->method('fetch_action_id')->willReturn('123');
        $functions->method('form_declaration')->willReturn('<form>');
        $functions->method('create_url')->willReturnCallback(function ($path = '') {
            $path = trim((string) $path, '/');
            return $path ? 'https://example.com/' . $path : 'https://example.com/';
        });
        $functions->method('redirect')->willReturnCallback(function ($url) {
            $this->redirectUrl = $url;
            return null;
        });
        ee()->setMock('functions', $functions);

        $extensions = $this->getMockBuilder('stdClass')
            ->addMethods(['active_hook', 'call'])
            ->getMock();
        $extensions->end_script = false;
        $extensions->method('active_hook')->willReturn(false);
        $extensions->method('call')->willReturn(null);
        ee()->setMock('extensions', $extensions);

        $input = new ProSearchCatchSearchInputMock();
        ee()->setMock('input', $input);

        $session = $this->getMockBuilder('stdClass')
            ->addMethods(['set_flashdata'])
            ->getMock();
        $session->method('set_flashdata')->willReturn(null);
        $session->flashdata = [];
        ee()->setMock('session', $session);

        $this->mod = new Pro_search();
    }

    protected function tearDown(): void
    {
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;

        if (is_null($this->originalReferer)) {
            unset($_SERVER['HTTP_REFERER']);
        } else {
            $_SERVER['HTTP_REFERER'] = $this->originalReferer;
        }

        parent::tearDown();
    }

    private function setExtensionHookCallback(callable $callback): void
    {
        $extensions = $this->getMockBuilder('stdClass')
            ->addMethods(['active_hook', 'call'])
            ->getMock();
        $extensions->end_script = false;
        $extensions->method('active_hook')->willReturn(true);
        $extensions->method('call')->willReturnCallback($callback);
        ee()->setMock('extensions', $extensions);
    }

    private function setRedirectToThrowException(): void
    {
        $functions = $this->getMockBuilder('stdClass')
            ->addMethods(['fetch_action_id', 'form_declaration', 'create_url', 'redirect'])
            ->getMock();
        $functions->method('fetch_action_id')->willReturn('123');
        $functions->method('form_declaration')->willReturn('<form>');
        $functions->method('create_url')->willReturnCallback(function ($path = '') {
            $path = trim((string) $path, '/');
            return $path ? 'https://example.com/' . $path : 'https://example.com/';
        });
        $functions->method('redirect')->willReturnCallback(function ($url) {
            throw new ProSearchCatchSearchRedirectException($url);
        });
        ee()->setMock('functions', $functions);
    }

    public function testCatchSearchUsesDefaultResultPageWhenUnsignedRemoteResultPageIsProvided()
    {
        $params = pro_search_encode(['result_page' => 'https://results.example.org/path']);
        $_POST = ['params' => $params];

        $input = ee()->input;
        $input->postValues['params'] = $params;
        $input->getPostValues['sig'] = null;

        $this->mod->catch_search();

        $this->assertSame('https://example.com/search/results', $this->redirectUrl);
    }

    public function testCatchSearchUsesDefaultResultPageWhenResultPageSignatureIsInvalid()
    {
        $params = pro_search_encode(['result_page' => 'https://results.example.org/path']);
        $_POST = ['params' => $params, 'sig' => 'invalid-signature'];

        $input = ee()->input;
        $input->postValues['params'] = $params;
        $input->getPostValues['sig'] = 'invalid-signature';

        $this->mod->catch_search();

        $this->assertSame('https://example.com/search/results', $this->redirectUrl);
    }

    public function testCatchSearchUsesDefaultResultPageWhenUnsignedProtocolRelativeResultPageIsProvided()
    {
        $params = pro_search_encode(['result_page' => '//results.example.org/path']);
        $_POST = ['params' => $params];

        $input = ee()->input;
        $input->postValues['params'] = $params;
        $input->getPostValues['sig'] = null;

        $this->mod->catch_search();

        $this->assertSame('https://example.com/search/results', $this->redirectUrl);
    }

    public function testCatchSearchUsesDefaultResultPageWhenRawExternalResultPageIsProvided()
    {
        $_POST = ['result_page' => 'https://results.example.org/path'];

        $this->mod->catch_search();

        $this->assertSame('https://example.com/search/results', $this->redirectUrl);
    }

    public function testCatchSearchRespectsSignedResultPageFromParams()
    {
        $params = pro_search_encode(['result_page' => 'https://results.example.org/path']);
        $sig = hash_hmac('sha256', $params, 'unit-test-key');
        $_POST = ['params' => $params, 'sig' => $sig];

        $input = ee()->input;
        $input->postValues['params'] = $params;
        $input->getPostValues['sig'] = $sig;

        $this->mod->catch_search();

        $this->assertSame('https://results.example.org/path', $this->redirectUrl);
    }

    public function testCatchSearchIgnoresRawResultPageOverrideWhenSignedParamsExist()
    {
        $params = pro_search_encode(['result_page' => 'search/results']);
        $sig = hash_hmac('sha256', $params, 'unit-test-key');
        $_POST = [
            'params' => $params,
            'sig' => $sig,
            'result_page' => 'https://results.example.org/path',
        ];

        $input = ee()->input;
        $input->postValues['params'] = $params;
        $input->getPostValues['sig'] = $sig;

        $this->mod->catch_search();

        $this->assertSame('https://example.com/search/results', $this->redirectUrl);
    }

    public function testCatchSearchUsesDefaultResultPageWhenExtensionSetsUnsignedExternalResultPage()
    {
        $this->setExtensionHookCallback(function ($hook, $data) {
            $data['result_page'] = 'https://results.example.org/path';
            return $data;
        });

        $_POST = ['keywords' => 'test'];

        $this->mod->catch_search();

        $this->assertStringStartsWith('https://example.com/search/results', $this->redirectUrl);
    }

    public function testCatchSearchUsesSignedForceProtocolWhenRawOverrideIsProvided()
    {
        $params = pro_search_encode([
            'result_page' => 'search/results',
            'force_protocol' => 'http',
        ]);
        $sig = hash_hmac('sha256', $params, 'unit-test-key');
        $_POST = [
            'params' => $params,
            'sig' => $sig,
            'force_protocol' => 'https',
        ];

        $input = ee()->input;
        $input->postValues['params'] = $params;
        $input->getPostValues['sig'] = $sig;

        $this->mod->catch_search();

        $this->assertStringStartsWith('http://example.com/search/results', $this->redirectUrl);
    }

    public function testCatchSearchIgnoresForceProtocolWhenSignatureIsInvalid()
    {
        $params = pro_search_encode([
            'result_page' => 'search/results',
            'force_protocol' => 'http',
        ]);
        $_POST = [
            'params' => $params,
            'sig' => 'invalid-signature',
        ];

        $input = ee()->input;
        $input->postValues['params'] = $params;
        $input->getPostValues['sig'] = 'invalid-signature';

        $this->mod->catch_search();

        $this->assertStringStartsWith('https://example.com/search/results', $this->redirectUrl);
    }

    public function testCatchSearchUsesSignedRequiredWhenRawOverrideIsProvided()
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/form';
        $this->setRedirectToThrowException();

        $params = pro_search_encode(['required' => 'keywords']);
        $sig = hash_hmac('sha256', $params, 'unit-test-key');
        $_POST = [
            'params' => $params,
            'sig' => $sig,
            'required' => 'safe_field',
            'safe_field' => '1',
        ];

        $input = ee()->input;
        $input->postValues['params'] = $params;
        $input->getPostValues['sig'] = $sig;

        try {
            $this->mod->catch_search();
            $this->fail('Expected redirect was not triggered.');
        } catch (ProSearchCatchSearchRedirectException $exception) {
            $this->assertSame('https://example.com/form', $exception->getRedirectUrl());
        }
    }

    public function testCatchSearchIgnoresUnsignedRequiredWhenSignatureIsInvalid()
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/form';
        $this->setRedirectToThrowException();

        $params = pro_search_encode(['required' => 'keywords']);
        $_POST = [
            'params' => $params,
            'sig' => 'invalid-signature',
        ];

        $input = ee()->input;
        $input->postValues['params'] = $params;
        $input->getPostValues['sig'] = 'invalid-signature';

        try {
            $this->mod->catch_search();
            $this->fail('Expected redirect was not triggered.');
        } catch (ProSearchCatchSearchRedirectException $exception) {
            $this->assertSame('https://example.com/search/results', $exception->getRedirectUrl());
        }
    }
}
