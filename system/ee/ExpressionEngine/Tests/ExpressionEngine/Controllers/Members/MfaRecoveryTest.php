<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Controllers\Members;

use ExpressionEngine\Controller\Members\Profile\Pro;
use ExpressionEngine\Core\Request;
use ExpressionEngine\Library\Security\XSS;
use ExpressionEngine\Service\Alert\AlertCollection;
use ExpressionEngine\Service\Model\Facade;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

class MfaRecoveryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Recover an enrolled account with a valid backup code when Pro is unavailable.
     *
     * @return void
     */
    public function testBackupCodeRecoveryWorksWithoutCurrentLicenseVerification()
    {
        require_once APPPATH . 'core/Controller.php';
        require_once APPPATH . 'controllers/cp/login.php';

        $backupCode = 'ExampleBackup123';
        $home = '/admin.php?/cp/homepage';
        $member = Mockery::mock();
        $member->enable_mfa = true;
        $member->backup_mfa_code = md5($backupCode);
        $member->shouldReceive('getCPHomepageURL')->once()->andReturn($home);
        $member->shouldReceive('set')->once()->with(['backup_mfa_code' => '', 'enable_mfa' => false]);
        $member->shouldReceive('save')->once();

        $session = Mockery::mock();
        $session->shouldReceive('getMember')->once()->andReturn($member);
        $session->shouldReceive('userdata')->with('member_id')->andReturn(5);
        $session->shouldReceive('userdata')->with('mfa_flag')->andReturn('show');
        $session->shouldReceive('userdata')->with('fingerprint')->andReturn('test-session');
        $session->shouldReceive('delete_password_lockout')->once();
        ee()->setMock('session', $session);

        $storedSession = Mockery::mock();
        $storedSession->mfa_flag = 'show';
        $storedSession->shouldReceive('save')->once();
        $model = Mockery::mock(Facade::class);
        $model->shouldReceive('get->filter->filter->all')->once()->andReturn([$storedSession]);
        ee()->setMock('Model', $model);

        $access = $this->getMockBuilder(\stdClass::class)->addMethods(['hasRequiredLicense'])->getMock();
        $access->expects($this->once())->method('hasRequiredLicense')->willReturn(false);
        ee()->setMock('pro:Access', $access);
        $config = Mockery::mock();
        $config->shouldReceive('item')->with('enable_mfa')->andReturn('y');
        ee()->setMock('config', $config);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('post')->with('backup_mfa_code')->andReturn($backupCode);
        ee()->setMock('Request', $request);
        $sanitizer = Mockery::mock(XSS::class);
        $sanitizer->shouldReceive('clean')->with($backupCode)->andReturn($backupCode);
        ee()->setMock('Security/XSS', $sanitizer);

        $alerts = Mockery::mock(AlertCollection::class);
        $alerts->shouldReceive('makeInline->asIssue->withTitle->addToBody->defer')->once();
        ee()->setMock('CP/Alert', $alerts);
        $redirect = new RuntimeException('Redirect to the control panel');
        $functions = Mockery::mock();
        $functions->shouldReceive('redirect')->once()->with($home)->andThrow($redirect);
        $controller = $this->getMockBuilder(\Login::class)->disableOriginalConstructor()->onlyMethods(['__get'])->getMock();
        $controller->method('__get')->with('functions')->willReturn($functions);

        $post = $_POST;
        $_POST = ['backup_mfa_code' => $backupCode];

        try {
            $controller->mfa_reset();
            $this->fail('Successful recovery should redirect to the control panel');
        } catch (RuntimeException $exception) {
            $this->assertSame($redirect, $exception, $exception->getMessage());
            $this->assertSame('skip', $storedSession->mfa_flag);
        } finally {
            $_POST = $post;
            ee()->resetMocks();
        }
    }

    /**
     * Keep mandatory enrollment available only on the current member's MFA form and its QR code.
     *
     * @return void
     */
    public function testRequiredEnrollmentIsAvailableForTheCurrentMember()
    {
        require_once APPPATH . 'core/Controller.php';

        $settings = (object) ['require_mfa' => 'y'];
        $roleSettings = Mockery::mock();
        $roleSettings->shouldReceive('filter')->with('site_id', 1)->andReturnSelf();
        $roleSettings->shouldReceive('first')->andReturn($settings);
        $member = (object) [
            'member_id' => 5,
            'enable_mfa' => false,
            'PrimaryRole' => (object) ['RoleSettings' => $roleSettings],
        ];

        $session = Mockery::mock();
        $session->shouldReceive('userdata')->with('member_id')->andReturn(5);
        ee()->setMock('session', $session);
        $config = Mockery::mock();
        $config->shouldReceive('item')->with('site_id')->andReturn(1);
        ee()->setMock('config', $config);
        $uri = Mockery::mock();
        $uri->shouldReceive('segment')->with(5)->andReturn('mfa');
        $page = false;
        $uri->shouldReceive('segment')->with(6)->andReturnUsing(function () use (&$page) {
            return $page;
        });
        ee()->setMock('uri', $uri);

        $controller = (new ReflectionClass(Pro::class))->newInstanceWithoutConstructor();
        $property = new ReflectionProperty(Pro::class, 'member');
        \TestReflectionHelper::makePropertyAccessible($property);
        $property->setValue($controller, $member);
        $method = new ReflectionMethod(Pro::class, 'isRequiredMfaEnrollment');
        \TestReflectionHelper::makeMethodAccessible($method);

        try {
            $this->assertTrue($method->invoke($controller));
            $page = 'qrCode';
            $this->assertTrue($method->invoke($controller), 'The form cannot be completed without its QR code');
            $page = 'anything_else';
            $this->assertFalse($method->invoke($controller));
            $page = false;
            $member->member_id = 6;
            $this->assertFalse($method->invoke($controller));
            $member->member_id = 5;
            $member->enable_mfa = true;
            $this->assertFalse($method->invoke($controller));
            $member->enable_mfa = false;
            $settings->require_mfa = 'n';
            $this->assertFalse($method->invoke($controller));
        } finally {
            ee()->resetMocks();
        }
    }
}
