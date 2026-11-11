<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

use ExpressionEngine\Model\Member\Member;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MfaSessionTest extends TestCase
{
    private $db;
    private $config;
    private $clock;
    private $input;
    private $member;
    private $mfa;
    private $cookie;
    private $cookies = [];

    protected function setUp(): void
    {
        ee()->resetMocks();
        if (!defined('REQ')) {
            define('REQ', 'PAGE');
        }
        require_once BASEPATH . 'libraries/Mfa_session.php';
        $this->clock = (object) ['now' => 2000000];
        $this->config = new \eeSingletonConfigMock();
        $this->config->resetConfig();
        $this->config->setItem('mfa_session_length', 86400);
        $this->config->setItem('enable_mfa', 'y');
        $this->config->setItem('site_id', 1);
        $this->input = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['cookie', 'set_cookie', 'delete_cookie'])->getMock();
        $this->input->method('cookie')->with('mfa_session')->willReturnCallback(function () {
            return $this->cookie;
        });
        $this->input->method('set_cookie')->willReturnCallback(function ($name, $value, $length) {
            $this->cookies[] = [$name, $value, $length];
            $this->cookie = $value;
        });
        $this->input->method('delete_cookie')->willReturnCallback(function ($name) {
            $this->assertSame('mfa_session', $name);
            $this->cookie = false;
        });
        // SQL behavior is covered by the Cypress MySQL fixture. Unit tests
        // only stub write outcomes, never reimplement authorization predicates.
        $this->db = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['dbprefix', 'query', 'affected_rows'])->getMock();
        $this->db->method('dbprefix')->willReturnCallback(function ($table) {
            return 'exp_' . $table;
        });
        $this->cookie = str_repeat('a', 64);
        $this->member = (object) ['member_id' => 42, 'enable_mfa' => true,
            'password' => 'password-hash', 'unique_id' => 'member-secret', 'backup_mfa_code' => 'mfa-secret'];
        ee()->setMock('db', $this->db);
        ee()->setMock('config', $this->config);
        ee()->setMock('input', $this->input);
        ee()->setMock('localize', $this->clock);
        $this->mfa = new \Mfa_session();
        ee()->setMock('mfa_session', $this->mfa);
    }

    protected function tearDown(): void
    {
        $this->config->resetConfig();
        ee()->resetMocks();
        parent::tearDown();
    }

    /** @dataProvider disabledLengthProvider */
    public function testDisabledOrInvalidLengthDoesNotIssueOrRestore($value)
    {
        $this->config->setItem('mfa_session_length', $value);
        $this->db->expects($this->never())->method('query');
        $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
        $this->assertFalse($this->mfa->restore(42, 'new-session'));
        $this->assertSame([], $this->cookies);
    }

    public static function disabledLengthProvider()
    {
        return [[false], [null], [0], [-1], ['0'], ['invalid'], ['1.5'], [1.5], [true], [[]], [PHP_INT_MAX]];
    }

    public function testOldSettingDoesNotEnableReuse()
    {
        $this->config->setItem('mfa_session_length', false);
        $this->config->setItem('mfa_remember_ttl', 86400);
        $this->db->expects($this->never())->method('query');
        $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
        $this->assertSame([], $this->cookies);
    }

    /** @dataProvider malformedCookieProvider */
    public function testMalformedOrMissingCookieCannotRestore($cookie)
    {
        $this->db->expects($this->never())->method('query');
        $this->cookie = $cookie;
        $this->assertFalse((new \Mfa_session())->restore(42, 'new-session'));
    }

    public static function malformedCookieProvider()
    {
        return [[false], [''], ['short'], [str_repeat('z', 64)], [str_repeat('a', 65)], [str_repeat('a', 64) . "\n"], [['token']]];
    }

    /** @dataProvider failedWriteProvider */
    public function testFailedDatabaseWritesNeverApproveOrSendACookie($operation, $queryResult)
    {
        $this->db->expects($this->once())->method('query')->willReturn($queryResult);
        $this->db->expects($queryResult ? $this->once() : $this->never())
            ->method('affected_rows')->willReturn(0);
        $result = $operation === 'create'
            ? $this->mfa->create($this->member, 'issuing-session')
            : $this->mfa->restore(42, 'new-session');
        $this->assertFalse($result);
        $this->assertSame([], $this->cookies);
    }

    public static function failedWriteProvider()
    {
        return [['create', false], ['create', true], ['restore', false], ['restore', true]];
    }

    public function testSessionOnlyModeDoesNotUsePersistentMfaCookies()
    {
        $this->config->setItem('website_session_type', 's');
        $this->db->expects($this->never())->method('query');
        $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
        $this->assertSame([], $this->cookies);
    }

    public function testDisabledGlobalMfaDoesNotIssueApproval()
    {
        $this->config->setItem('enable_mfa', 'n');
        $this->db->expects($this->never())->method('query');
        $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
    }

    /** @dataProvider memberChangeProvider */
    public function testMemberModelRevokesOnlyForCredentialChanges($field, $revoked)
    {
        $mfa = $this->getMockBuilder(\Mfa_session::class)->disableOriginalConstructor()
            ->onlyMethods(['revoke_member'])->getMock();
        $mfa->expects($revoked ? $this->once() : $this->never())->method('revoke_member')->with(42);
        ee()->setMock('mfa_session', $mfa);
        $query = $this->getMockBuilder(\stdClass::class)->addMethods(['filter', 'delete'])->getMock();
        $query->method('filter')->willReturnSelf();
        $facade = $this->getMockBuilder(\stdClass::class)->addMethods(['get'])->getMock();
        $facade->method('get')->willReturn($query);
        $member = $this->getMockBuilder(Member::class)->disableOriginalConstructor()
            ->onlyMethods(['saveFieldData', 'getModelFacade', 'notifyOfChanges', '__get'])->getMock();
        $member->method('getModelFacade')->willReturn($facade);
        $member->method('__get')->willReturnMap([['member_id', 42], ['password', 'new-password'], ['email', 'test@example.com']]);
        $property = new \ReflectionProperty(Member::class, 'member_id');
        \TestReflectionHelper::makePropertyAccessible($property);
        $property->setValue($member, 42);
        $member->onAfterUpdate([$field => 'old-value']);
    }

    public static function memberChangeProvider()
    {
        return [['password', true], ['enable_mfa', true], ['backup_mfa_code', true], ['unique_id', true], ['bio', false]];
    }

    public function testMemberDeletionRevokesApproval()
    {
        $mfa = $this->getMockBuilder(\Mfa_session::class)->disableOriginalConstructor()
            ->onlyMethods(['revoke_member'])->getMock();
        $mfa->expects($this->once())->method('revoke_member')->with(42);
        ee()->setMock('mfa_session', $mfa);
        $member = $this->getMockBuilder(Member::class)->disableOriginalConstructor()
            ->onlyMethods(['updateRoleTotalMembers'])->getMock();
        $property = new \ReflectionProperty(Member::class, 'member_id');
        \TestReflectionHelper::makePropertyAccessible($property);
        $property->setValue($member, 42);
        $member->onAfterDelete();
    }

    /** @dataProvider cookieLifetimeProvider */
    public function testMfaCookieLifetimeUsesTheNewWindowUnlessExplicitlyOverridden($name, $enforced, $expected)
    {
        require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
        require_once BASEPATH . 'core/Input.php';
        $registry = $this->getMockBuilder(\stdClass::class)->addMethods(['getCookieSettings'])->getMock();
        $registry->method('getCookieSettings')->willReturn([
            'cookie_lifetime' => 86400, 'cookie_enforced_lifetime' => $enforced
        ]);
        ee()->setMock('CookieRegistry', $registry);
        $input = new class extends \EE_Input {
            public $sent;
            public function __construct()
            {
            }
            protected function _set_cookie(array $data)
            {
                $this->sent = $data;
                return true;
            }
        };
        $input->set_cookie($name, str_repeat('a', 64), 172800);
        $this->assertSame($this->clock->now + $expected, $input->sent['expire']);
    }

    public static function cookieLifetimeProvider()
    {
        return [['mfa_session', null, 172800], ['mfa_session', 3600, 3600], ['ordinary', null, 86400]];
    }

    /** @dataProvider cookiePolicyProvider */
    public function testMfaCookiePolicyProtectsTheCredential($encrypted, $sameSite, $secure, $expectedSameSite)
    {
        require_once BASEPATH . 'core/Input.php';
        $request = $this->getMockBuilder(\stdClass::class)->addMethods(['isEncrypted'])->getMock();
        $request->method('isEncrypted')->willReturn($encrypted);
        ee()->setMock('Request', $request);
        $this->config->setItem('cookie_samesite', $sameSite);
        $reflection = new \ReflectionClass(\EE_Input::class);
        $method = $reflection->getMethod('validateCookieData');
        \TestReflectionHelper::makeMethodAccessible($method);
        $result = $method->invoke($reflection->newInstanceWithoutConstructor(), [
            'name' => 'mfa_session', 'domain' => '.example.test',
            'httponly' => false, 'secure_cookie' => $secure
        ]);
        $this->assertTrue($result['httponly']);
        $this->assertSame('', $result['domain']);
        $this->assertSame($expectedSameSite, $result['samesite']);
        $this->assertSame($encrypted || $secure || $sameSite === 'None', $result['secure_cookie']);
    }

    public static function cookiePolicyProvider()
    {
        return [[true, 'Lax', false, 'Lax'], [true, 'Strict', false, 'Strict'],
            [true, 'None', false, 'Lax'], [false, 'Lax', true, 'Lax'],
            [false, 'Lax', false, 'Lax']];
    }

    public function testOrdinaryCookiePolicyIsUnchanged()
    {
        require_once BASEPATH . 'core/Input.php';
        $reflection = new \ReflectionClass(\EE_Input::class);
        $method = $reflection->getMethod('validateCookieData');
        \TestReflectionHelper::makeMethodAccessible($method);
        $result = $method->invoke($reflection->newInstanceWithoutConstructor(), [
            'name' => 'ordinary', 'domain' => '.example.test',
            'httponly' => false, 'secure_cookie' => false
        ]);
        $this->assertFalse($result['httponly']);
        $this->assertFalse($result['secure_cookie']);
        $this->assertSame('.example.test', $result['domain']);
    }

    /** @dataProvider migrationProvider */
    public function testMigrationIsRepeatableAndReportsFailures($exists, $created, $expected)
    {
        require_once SYSPATH . 'ee/installer/libraries/Progress.php';
        require_once SYSPATH . 'ee/installer/updates/ud_7_05_29.php';
        $db = $this->getMockBuilder(\stdClass::class)->addMethods(['table_exists', 'field_exists'])->getMock();
        $db->data_cache = ['table_names' => []];
        $db->method('table_exists')->with('mfa_sessions')->willReturn($exists);
        $db->expects($this->never())->method('field_exists');
        $forge = $this->getMockBuilder(\stdClass::class)->addMethods(['add_field', 'add_key', 'create_table', 'drop_column'])->getMock();
        $forge->expects($exists ? $this->never() : $this->once())->method('create_table')->with('mfa_sessions')->willReturn($created);
        $forge->expects($this->never())->method('drop_column');
        ee()->setMock('db', $db);
        ee()->setMock('dbforge', $forge);
        ee()->setMock('smartforge', $forge);
        $updater = new \ExpressionEngine\Updater\Version_7_5_29\Updater();
        $this->assertSame($expected, $updater->do_update());
    }

    public static function migrationProvider()
    {
        return [[false, true, true], [true, false, true], [false, false, false]];
    }
}
