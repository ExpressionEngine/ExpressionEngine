<?php

use PHPUnit\Framework\TestCase;

require_once PATH_ADDONS . 'member/mod.member.php';
require_once PATH_ADDONS . 'member/mod.member_register.php';

if (! defined('USERNAME_MAX_LENGTH')) {
    define('USERNAME_MAX_LENGTH', 75);
}

if (! function_exists('trim_nbs')) {
    function trim_nbs($str)
    {
        return trim(str_replace("\xc2\xa0", ' ', (string) $str));
    }
}

if (! function_exists('get_bool_from_string')) {
    function get_bool_from_string($value)
    {
        if (is_bool($value) || is_null($value)) {
            return $value;
        }

        switch (strtolower((string) $value)) {
            case 'true':
            case 'yes':
            case 'y':
            case 'on':
            case '1':
                return true;

            case 'false':
            case 'no':
            case 'n':
            case 'off':
            case '0':
                return false;
        }

        return null;
    }
}

class MemberRegisterEmailAsUsernameConfigMock
{
    private $items = [];

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function item($key)
    {
        return $this->items[$key] ?? null;
    }
}

class MemberRegisterEmailAsUsernameFunctionsMock
{
    private $protected = [];

    public function __construct(array $protected = [])
    {
        $this->protected = $protected;
    }

    public function handle_protected()
    {
        return $this->protected;
    }

    public function determine_return()
    {
        return '/return';
    }

    public function determine_error_return()
    {
        return '/error';
    }
}

class MemberRegisterEmailAsUsernameSessionMock
{
    public function userdata($key, $default = false)
    {
        return false;
    }
}

class MemberRegisterEmailAsUsernameLoadMock
{
    public function helper($name)
    {
        return;
    }
}

class MemberRegisterEmailAsUsernameExtensionsMock
{
    public $end_script = false;

    public function call()
    {
        return;
    }
}

class MemberRegisterEmailAsUsernameInputMock
{
    public function post($key)
    {
        return $_POST[$key] ?? null;
    }
}

class MemberRegisterEmailAsUsernameOutputMock
{
    public $errors = [];

    public function show_form_error($errors, $type = 'general')
    {
        $payload = [
            'errors' => $errors,
            'type' => $type,
        ];
        $this->errors[] = $payload;

        return $payload;
    }

    public function show_user_error($type, $errors = [])
    {
        return [
            'type' => $type,
            'errors' => $errors,
        ];
    }
}

class MemberRegisterEmailAsUsernameTest extends TestCase
{
    private $memberRegister;
    private $output;

    protected function setUp(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }

        $_POST = [];

        $reflection = new ReflectionClass(Member_register::class);
        $this->memberRegister = $reflection->newInstanceWithoutConstructor();
    }

    protected function tearDown(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }

        $_POST = [];
    }

    public function testDeriveEmailAsUsernameFallbackStripsDisallowedCharacters()
    {
        $this->assertSame(
            'oconnor@example.com',
            $this->callPrivateMethod('_derive_email_as_username_fallback', ["o'connor!@example.com"])
        );
    }

    public function testDeriveEmailAsUsernameFallbackLeavesValidEmailUnchanged()
    {
        $this->assertSame(
            'simple.user@example.com',
            $this->callPrivateMethod('_derive_email_as_username_fallback', array('simple.user@example.com'))
        );
    }

    public function testApplyEmailAsUsernameFallbackDoesNothingWhenUsernameProvided()
    {
        $_POST['username'] = 'chosen-username';
        $_POST['email'] = "o'connor@example.com";

        $result = $this->callPrivateMethod('_apply_email_as_username_fallback', array(true));

        $this->assertTrue($result);
        $this->assertSame('chosen-username', $_POST['username']);
    }

    public function testApplyEmailAsUsernameFallbackSetsSanitizedUsername()
    {
        $this->setBaseMocks([
            'un_min_len' => 3,
        ]);

        $_POST['username'] = '';
        $_POST['email'] = "o'!@x.io";

        $result = $this->callPrivateMethod('_apply_email_as_username_fallback', array(true));

        $this->assertTrue($result);
        $this->assertSame('o@x.io', $_POST['username']);
    }

    public function testRegisterMemberReturnsEmailErrorWhenSanitizedFallbackTooShort()
    {
        $this->setBaseMocks(
            [
                'allow_member_registration' => 'y',
                'un_min_len' => 10,
            ],
            [
                'email_as_username' => 'yes',
            ]
        );

        $_POST['username'] = '';
        $_POST['email'] = "o'!@x.io";

        $result = $this->memberRegister->register_member();

        $this->assertSame('o@x.io', $_POST['username']);
        $this->assertCount(1, $this->output->errors);
        $this->assertSame(
            ['email' => 'mbr_email_cannot_be_used_as_username'],
            $this->output->errors[0]['errors']
        );
        $this->assertSame('submission', $this->output->errors[0]['type']);
        $this->assertSame($this->output->errors[0], $result);
    }

    private function setBaseMocks(array $configItems = [], array $protected = [])
    {
        $defaultConfig = [
            'allow_member_registration' => 'y',
            'un_min_len' => 4,
        ];

        ee()->setMock('config', new MemberRegisterEmailAsUsernameConfigMock(array_merge($defaultConfig, $configItems)));
        ee()->setMock('functions', new MemberRegisterEmailAsUsernameFunctionsMock($protected));
        ee()->setMock('session', new MemberRegisterEmailAsUsernameSessionMock());
        ee()->setMock('load', new MemberRegisterEmailAsUsernameLoadMock());
        ee()->setMock('extensions', new MemberRegisterEmailAsUsernameExtensionsMock());
        ee()->setMock('input', new MemberRegisterEmailAsUsernameInputMock());
        ee()->setMock('blockedlist', (object) ['blocked' => 'n', 'allowed' => 'y']);

        $this->output = new MemberRegisterEmailAsUsernameOutputMock();
        ee()->setMock('output', $this->output);
    }

    private function callPrivateMethod($methodName, array $args = array())
    {
        $method = new ReflectionMethod($this->memberRegister, $methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->memberRegister, $args);
    }
}
