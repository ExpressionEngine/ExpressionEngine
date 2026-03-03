<?php

use PHPUnit\Framework\TestCase;

require_once PATH_ADDONS . 'member/mod.member.php';
require_once PATH_ADDONS . 'member/mod.member_images.php';

if (! function_exists('reduce_double_slashes')) {
    function reduce_double_slashes($str)
    {
        return preg_replace('#(^|[^:])/+#', '\\1/', $str);
    }
}

class MemberImagesSelectAvatarTest extends TestCase
{
    private $memberImages;
    private $input;
    private $redirectUrl;

    protected function setUp(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }

        $config = new FakeConfig();
        $config->setItem('site_url', 'https://example.com/');
        ee()->setMock('config', $config);

        $this->input = new class {
            public $values = [];
            public function get_post($key)
            {
                return array_key_exists($key, $this->values) ? $this->values[$key] : false;
            }
        };
        ee()->setMock('input', $this->input);

        $functions = $this->getMockBuilder('stdClass')
            ->addMethods(['create_url', 'redirect'])
            ->getMock();
        $functions->method('create_url')->willReturnCallback(function ($path = '') {
            return 'https://example.com/' . ltrim((string) $path, '/');
        });
        $functions->method('redirect')->willReturnCallback(function ($url) {
            $this->redirectUrl = $url;
            return $url;
        });
        ee()->setMock('functions', $functions);

        $reflection = new ReflectionClass(Member_images::class);
        $this->memberImages = $reflection->newInstanceWithoutConstructor();
        $this->memberImages->basepath = 'https://example.com/member';
    }

    protected function tearDown(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
    }

    public function testSelectAvatarFallsBackToEditAvatarWhenReferrerIsExternal()
    {
        $this->input->values = [
            'referrer' => 'https://other.example/member/browse_avatars/default/',
        ];

        $this->memberImages->select_avatar();

        $this->assertSame('https://example.com/member/edit_avatar', $this->redirectUrl);
    }

    public function testSelectAvatarAllowsSameOriginAbsoluteReferrer()
    {
        $this->input->values = [
            'referrer' => 'https://example.com/member/browse_avatars/default/',
        ];

        $this->memberImages->select_avatar();

        $this->assertSame('https://example.com/member/browse_avatars/default/', $this->redirectUrl);
    }

    public function testSelectAvatarAllowsInternalPathReferrer()
    {
        $this->input->values = [
            'referrer' => 'member/browse_avatars/default/',
        ];

        $this->memberImages->select_avatar();

        $this->assertSame('https://example.com/member/browse_avatars/default/', $this->redirectUrl);
    }

    public function testSelectAvatarFallsBackWhenReferrerIsMissing()
    {
        $this->input->values = [];

        $this->memberImages->select_avatar();

        $this->assertSame('https://example.com/member/edit_avatar', $this->redirectUrl);
    }
}
