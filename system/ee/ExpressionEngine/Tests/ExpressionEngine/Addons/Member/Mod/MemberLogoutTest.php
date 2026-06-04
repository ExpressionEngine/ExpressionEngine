<?php

use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
require_once PATH_ADDONS . 'member/mod.member.php';
require_once PATH_ADDONS . 'member/mod.member_auth.php';

if (! defined('CSRF_TOKEN')) {
    define('CSRF_TOKEN', 'test-csrf-token');
}

class MemberLogoutFunctionsMock
{
    public $redirects = [];

    private $returnLink;

    public function __construct($returnLink = '/member/logout-complete')
    {
        $this->returnLink = $returnLink;
    }

    public function handle_protected()
    {
        return [];
    }

    public function determine_return($go_to_index = false)
    {
        return $this->returnLink;
    }

    public function determine_error_return()
    {
        return false;
    }

    public function redirect($url)
    {
        $this->redirects[] = $url;

        return [
            'redirect' => $url,
        ];
    }
}

class MemberLogoutInputMock
{
    private $getValues;

    public function __construct(array $getValues = [])
    {
        $this->getValues = $getValues;
    }

    public function get($key)
    {
        return $this->getValues[$key] ?? null;
    }
}

class MemberLogoutSessionMock
{
    private $memberId;

    public function __construct($memberId)
    {
        $this->memberId = $memberId;
    }

    public function userdata($key, $default = false)
    {
        if ($key === 'member_id') {
            return $this->memberId;
        }

        return $default;
    }
}

class MemberLogoutOutputMock
{
    public $formErrors = [];

    public function show_form_error($errors)
    {
        $this->formErrors[] = $errors;

        return [
            'form_error' => $errors,
        ];
    }
}

class MemberLogoutConfigMock
{
    private $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function item($key)
    {
        return $this->items[$key] ?? null;
    }
}

class MemberLogoutTest extends TestCase
{
    private $requestMethod;
    private $functions;
    private $output;

    protected function setUp(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }

        $this->requestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->functions = new MemberLogoutFunctionsMock();
        $this->output = new MemberLogoutOutputMock();

        ee()->setMock('functions', $this->functions);
        ee()->setMock('output', $this->output);
        ee()->setMock('config', new MemberLogoutConfigMock([
            'disable_csrf_protection' => 'n',
        ]));
    }

    protected function tearDown(): void
    {
        if ($this->requestMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->requestMethod;
        }

        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
    }

    public function testLoggedOutGetWithoutTokenShowsFormErrorAndDoesNotRedirect()
    {
        ee()->setMock('input', new MemberLogoutInputMock());
        ee()->setMock('session', new MemberLogoutSessionMock(0));

        $result = $this->makeMemberAuth()->member_logout();

        $this->assertSame(['form_error' => ['general' => 'not_authorized']], $result);
        $this->assertSame([['general' => 'not_authorized']], $this->output->formErrors);
        $this->assertSame([], $this->functions->redirects);
    }

    public function testLoggedOutGetWithValidTokenRedirectsToReturnLink()
    {
        ee()->setMock('input', new MemberLogoutInputMock([
            'csrf_token' => CSRF_TOKEN,
        ]));
        ee()->setMock('session', new MemberLogoutSessionMock(0));

        $result = $this->makeMemberAuth()->member_logout();

        $this->assertSame(['redirect' => '/member/logout-complete'], $result);
        $this->assertSame(['/member/logout-complete'], $this->functions->redirects);
        $this->assertSame([], $this->output->formErrors);
    }

    public function testLoggedInGetWithInvalidTokenShowsFormError()
    {
        ee()->setMock('input', new MemberLogoutInputMock([
            'csrf_token' => 'invalid-token',
        ]));
        ee()->setMock('session', new MemberLogoutSessionMock(5));

        $result = $this->makeMemberAuth()->member_logout();

        $this->assertSame(['form_error' => ['general' => 'not_authorized']], $result);
        $this->assertSame([['general' => 'not_authorized']], $this->output->formErrors);
        $this->assertSame([], $this->functions->redirects);
    }

    private function makeMemberAuth()
    {
        $reflection = new ReflectionClass(Member_auth::class);

        return $reflection->newInstanceWithoutConstructor();
    }
}
