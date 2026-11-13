<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

use ExpressionEngine\Service\Encrypt\Encrypt;
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'helpers/multibyte_helper.php';
require_once SYSPATH . 'ee/legacy/libraries/Functions.php';

class FunctionsProtectedDataTest extends TestCase
{
    protected function tearDown(): void
    {
        \EE_Functions::$protected_data = null;
        ee()->resetMocks();
    }

    public function testInvalidProtectedDataShowsFormError()
    {
        ee()->resetMocks();
        ee()->setMock('Encrypt', new Encrypt('form-key'));

        $functions = new \EE_Functions();
        $token = $functions->protect_data(['setting' => 'enabled']);
        $input = new class($token) {
            public $token;

            public function __construct($token)
            {
                $this->token = $token;
            }

            public function get_post($key)
            {
                return $this->token;
            }
        };
        ee()->setMock('input', $input);

        $this->assertSame(['setting' => 'enabled'], $functions->handle_protected());

        $bytes = base64_decode($token, true);
        $bytes[strlen($bytes) - 1] = chr(ord($bytes[strlen($bytes) - 1]) ^ 1);
        $input->token = base64_encode($bytes);
        \EE_Functions::$protected_data = null;

        ee()->setMock('output', new class {
            public function show_form_error($errors, $type = 'general')
            {
                throw new \RuntimeException('Form error shown');
            }
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Form error shown');
        $functions->handle_protected();
    }

    public function testMissingProtectedDataReturnsNull()
    {
        ee()->resetMocks();
        ee()->setMock('input', new class {
            public function get_post($key)
            {
                return false;
            }
        });

        $this->assertNull((new \EE_Functions())->handle_protected());
    }
}
