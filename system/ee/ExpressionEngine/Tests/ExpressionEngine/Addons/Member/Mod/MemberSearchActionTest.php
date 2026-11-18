<?php

use PHPUnit\Framework\TestCase;

class MemberSearchActionFunctionsMock
{
    public $handleProtectedCalls = 0;
    public $redirects = [];

    public function handle_protected()
    {
        $this->handleProtectedCalls++;

        return [];
    }

    public function redirect($url)
    {
        $this->redirects[] = $url;

        return [
            'redirect' => $url,
        ];
    }
}

class MemberSearchActionOutputMock
{
    public $formErrors = [];

    public function show_form_error($errors, $type = 'general')
    {
        $this->formErrors[] = [
            'errors' => $errors,
            'type' => $type,
        ];

        return [
            'form_error' => $errors,
            'type' => $type,
        ];
    }
}

class MemberSearchActionLangMock
{
    public $loadedFiles = [];

    public function loadfile($file)
    {
        $this->loadedFiles[] = $file;
    }

    public function line($key)
    {
        return $key;
    }
}

class MemberSearchActionPermissionMock
{
    public function can($permission)
    {
        return false;
    }

    public function isSuperAdmin()
    {
        return false;
    }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MemberSearchActionTest extends TestCase
{
    private $requestMethod;
    private $functions;
    private $output;
    private $lang;

    protected function setUp(): void
    {
        if (! defined('REQ')) {
            define('REQ', 'ACTION');
        }

        require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
        require_once PATH_ADDONS . 'member/mod.member.php';
        require_once PATH_ADDONS . 'member/mod.member_memberlist.php';

        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }

        $this->requestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $this->functions = new MemberSearchActionFunctionsMock();
        $this->output = new MemberSearchActionOutputMock();
        $this->lang = new MemberSearchActionLangMock();

        ee()->setMock('functions', $this->functions);
        ee()->setMock('output', $this->output);
        ee()->setMock('lang', $this->lang);
        ee()->setMock('Permission', new MemberSearchActionPermissionMock());
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

    public function testDirectGetActionShowsFormErrorBeforeProcessingSearch()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $result = $this->makeMemberList()->do_member_search();

        $this->assertSame(['form_error' => ['general' => 'not_authorized'], 'type' => 'general'], $result);
        $this->assertSame([
            [
                'errors' => ['general' => 'not_authorized'],
                'type' => 'general',
            ],
        ], $this->output->formErrors);
        $this->assertSame(0, $this->functions->handleProtectedCalls);
        $this->assertSame([], $this->functions->redirects);
        $this->assertSame([], $this->lang->loadedFiles);
    }

    public function testPostActionContinuesToExistingSearchValidation()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $result = $this->makeMemberList()->do_member_search();

        $this->assertSame(['form_error' => ['general' => 'search_not_allowed'], 'type' => 'general'], $result);
        $this->assertSame(1, $this->functions->handleProtectedCalls);
        $this->assertSame([], $this->functions->redirects);
        $this->assertSame(['search'], $this->lang->loadedFiles);
    }

    private function makeMemberList()
    {
        $reflection = new ReflectionClass(Member_memberlist::class);

        return $reflection->newInstanceWithoutConstructor();
    }
}
