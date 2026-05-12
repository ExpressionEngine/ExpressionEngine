<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateGetMemberVariablesTest extends EE_TemplateTestBase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMemberVariablesReturnsLoggedOutDefaultsWhenSessionMissing()
    {
        ee()->setMock('session', null);

        $reflection = new \ReflectionMethod($this->template, 'getMemberVariables');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        $result = $reflection->invoke($this->template);

        $this->assertSame([
            'logged_out' => true,
            'logged_in' => false,
        ], $result);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMemberVariablesMarksPrimaryRoleWhenMemberObjectMissing()
    {
        $userVars = $this->template->getUserVars();
        $userdata = array_fill_keys($userVars, '');
        $userdata['member_id'] = 0;
        $userdata['primary_role_short_name'] = 'guest';

        $sessionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['getMember', 'userdata'])
            ->getMock();
        $sessionMock->userdata = $userdata;
        $sessionMock->method('getMember')->willReturn(false);
        $sessionMock->method('userdata')
            ->willReturnCallback(function($key, $default = false) use ($sessionMock) {
                return $sessionMock->userdata[$key] ?? $default;
            });
        ee()->setMock('session', $sessionMock);

        $reflection = new \ReflectionMethod($this->template, 'getMemberVariables');
        \TestReflectionHelper::makeMethodAccessible($reflection);

        $result = $reflection->invoke($this->template);

        $this->assertArrayHasKey('has_role_guest', $result);
        $this->assertTrue($result['has_role_guest']);
    }
}
