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
use ExpressionEngine\Service\Consent\CookieRegistry;
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
    private $logoutCookie;
    private $cookies = [];
    private $deletedCookies = [];

    /**
     * Prepare independent approval and revocation cookie inputs.
     *
     * @return void
     */
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
        $this->input->method('cookie')->willReturnCallback(function ($name) {
            return $name === 'mfa_session_1' ? $this->cookie : $this->logoutCookie;
        });
        $this->input->method('set_cookie')->willReturnCallback(function ($data) {
            $this->cookies[] = $data;
            if ($data['name'] === 'mfa_session_logout') {
                $this->logoutCookie = $data['value'];
                return;
            }
            $this->assertSame('mfa_session_1', $data['name']);
            $this->cookie = $data['value'];
        });
        $this->input->method('delete_cookie')->willReturnCallback(function ($data) {
            $this->assertSame('mfa_session', $data['policy_name']);
            $this->deletedCookies[] = $data['name'];
            if ($data['name'] === 'mfa_session_logout') {
                $this->logoutCookie = false;
                return;
            }
            $this->assertSame('mfa_session_1', $data['name']);
            $this->cookie = false;
        });
        // SQL behavior is covered by the Cypress MySQL fixture. Unit tests
        // only stub write outcomes, never reimplement authorization predicates.
        $this->db = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['dbprefix', 'query', 'affected_rows', 'delete', 'table_exists',
                'select', 'get_where', 'row', 'row_array', 'where', 'or_start_group', 'end_group'])->getMock();
        $this->db->method('dbprefix')->willReturnCallback(function ($table) {
            return 'exp_' . $table;
        });
        foreach (['select', 'or_start_group', 'end_group'] as $method) {
            $this->db->method($method)->willReturnSelf();
        }
        $this->cookie = str_repeat('a', 64);
        $this->logoutCookie = false;
        $this->member = (object) ['member_id' => 42, 'enable_mfa' => true,
            'password' => 'password-hash', 'unique_id' => 'member-secret', 'backup_mfa_code' => 'mfa-secret'];
        ee()->setMock('db', $this->db);
        ee()->setMock('config', $this->config);
        ee()->setMock('input', $this->input);
        ee()->setMock('CookieRegistry', new CookieRegistry());
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

    /**
     * Keep both cookie types and database lookups disabled for invalid windows.
     *
     * @param mixed $value Configured MFA lifetime.
     * @return void
     * @dataProvider disabledLengthProvider
     */
    public function testDisabledOrInvalidLengthDoesNotIssueOrRestore($value)
    {
        $this->config->setItem('mfa_session_length', $value);
        $this->db->expects($this->never())->method('query');
        $this->db->expects($this->never())->method('get_where');
        $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
        $this->assertFalse($this->mfa->restore(42, 'new-session'));
        $this->assertSame([], $this->cookies);
    }

    public static function disabledLengthProvider()
    {
        return [[false], [null], [0], [-1], ['0'], ['invalid'], ['1.5'], [1.5], [true], [[]], [PHP_INT_MAX]];
    }

    /**
     * Never authenticate from a logout reference without a valid host credential.
     *
     * @param mixed $cookie Missing or malformed approval cookie.
     * @return void
     * @dataProvider malformedCookieProvider
     */
    public function testMalformedOrMissingCookieCannotRestore($cookie)
    {
        $this->db->expects($this->never())->method('query');
        $this->db->expects($this->never())->method('get_where');
        $this->cookie = $cookie;
        $this->logoutCookie = hash('sha256', str_repeat('a', 64));
        $this->assertFalse((new \Mfa_session())->restore(42, 'new-session'));
        $this->assertSame([], $this->cookies);
    }

    public static function malformedCookieProvider()
    {
        return [[false], [''], ['short'], [str_repeat('z', 64)], [str_repeat('a', 65)], [str_repeat('a', 64) . "\n"], [['token']]];
    }

    /**
     * Ignore malformed revocation references during guest logout.
     *
     * @param mixed $reference Missing or malformed logout cookie.
     * @return void
     * @dataProvider malformedCookieProvider
     */
    public function testMalformedLogoutReferenceDoesNotLookupApproval($reference)
    {
        $this->assertLogoutScopes(0, false, $reference, [], [], []);
    }

    /**
     * Revoke shared references for guests without expanding authenticated logout.
     *
     * @param int $memberId Current authenticated member, or zero for a guest.
     * @param string|false $cookie Host approval credential.
     * @param string $reference Shared revocation reference.
     * @param array $approvals Approval rows returned by the database.
     * @param array $lookups Expected token hashes looked up.
     * @param array $scopes Approval login identities included in revocation.
     * @return void
     * @dataProvider logoutReferenceProvider
     */
    public function testLogoutResolvesOnlyTheApplicableReferences($memberId, $cookie, $reference, $approvals, $lookups, $scopes)
    {
        $this->assertLogoutScopes($memberId, $cookie, $reference, $approvals, $lookups, $scopes);
    }

    /**
     * Provide independent local and shared approval lookup outcomes.
     *
     * @return array
     */
    public static function logoutReferenceProvider()
    {
        $cookie = str_repeat('a', 64);
        $hash = hash('sha256', $cookie);
        $reference = str_repeat('b', 64);
        $local = ['member_id' => 42, 'mfa_login_id' => 'local-login'];
        $shared = ['member_id' => 77, 'mfa_login_id' => 'shared-login'];

        return [
            'guest without host cookie' => [0, false, $reference, [$shared], [$reference], ['shared-login']],
            'guest with stale host cookie' => [0, $cookie, $reference, [[], $shared], [$hash, $reference], ['shared-login']],
            'guest with both approvals' => [0, $cookie, $reference, [$local, $shared], [$hash, $reference], ['local-login', 'shared-login']],
            'guest with matching reference' => [0, $cookie, $hash, [$local], [$hash], ['local-login']],
            'member without host cookie' => [42, false, $reference, [], [], []],
            'member with stale host cookie' => [42, $cookie, $reference, [[]], [$hash], []],
            'member with both cookies' => [42, $cookie, $reference, [$local], [$hash], ['local-login']],
        ];
    }

    /**
     * Observe lookup and revocation targets without simulating database predicates.
     *
     * @param int $memberId Current member identifier.
     * @param mixed $cookie Host approval cookie.
     * @param mixed $reference Shared revocation cookie.
     * @param array $approvals Database lookup results.
     * @param array $lookups Expected token hash lookups.
     * @param array $scopes Expected approval login identities.
     * @return void
     */
    private function assertLogoutScopes($memberId, $cookie, $reference, $approvals, $lookups, $scopes)
    {
        $this->cookie = $cookie;
        $this->logoutCookie = $reference;
        $this->db->method('table_exists')->willReturn(true);
        $actualLookups = [];
        $this->db->expects($this->exactly(count($lookups)))->method('get_where')
            ->willReturnCallback(function ($table, $where) use (&$actualLookups) {
                $this->assertSame('mfa_sessions', $table);
                $actualLookups[] = $where['token_hash'];
                return $this->db;
            });
        if ($approvals) {
            $this->db->expects($this->exactly(count($approvals)))->method('row_array')
                ->willReturnOnConsecutiveCalls(...$approvals);
        }
        if (!$approvals) {
            $this->db->expects($this->never())->method('row_array');
        }
        $revokedLogins = [];
        $this->db->method('where')->willReturnCallback(function ($field, $value) use (&$revokedLogins) {
            if ($field === 'mfa_login_id') {
                $revokedLogins[] = $value;
            }
            return $this->db;
        });
        $this->db->expects($this->once())->method('delete')->with('mfa_sessions');

        (new \Mfa_session())->delete($memberId, '');

        $this->assertSame($lookups, $actualLookups);
        foreach (['local-login', 'shared-login'] as $loginId) {
            $this->assertSame(in_array($loginId, $scopes, true), in_array($loginId, $revokedLogins, true));
        }
        $this->assertSame(['mfa_session_logout', 'mfa_session_1'], $this->deletedCookies);
        $this->assertFalse($this->cookie);
        $this->assertFalse($this->logoutCookie);
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

    /**
     * Avoid approval and reference writes when cookies are disabled for sessions.
     *
     * @return void
     */
    public function testSessionOnlyModeDoesNotUsePersistentMfaCookies()
    {
        $this->config->setItem('website_session_type', 's');
        $this->db->expects($this->never())->method('query');
        $this->db->expects($this->never())->method('get_where');
        $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
        $this->assertFalse($this->mfa->restore(42, 'new-session'));
        $this->assertSame([], $this->cookies);
    }

    /**
     * Avoid approval and reference writes when MFA is globally disabled.
     *
     * @return void
     */
    public function testDisabledGlobalMfaDoesNotIssueApproval()
    {
        $this->config->setItem('enable_mfa', 'n');
        $this->db->expects($this->never())->method('query');
        $this->db->expects($this->never())->method('get_where');
        $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
        $this->assertFalse($this->mfa->restore(42, 'new-session'));
        $this->assertSame([], $this->cookies);
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
    public function testCookieLifetimeKeepsMfaIndependentOfGenericSettings($name, $stored, $enforced, $expected)
    {
        $registry = $this->getMockBuilder(CookieRegistry::class)->onlyMethods(['getCookieSettings'])->getMock();
        $registry->method('getCookieSettings')->willReturn([
            'cookie_lifetime' => $stored, 'cookie_enforced_lifetime' => $enforced
        ]);
        ee()->setMock('CookieRegistry', $registry);
        $input = $this->capturingInput();
        $data = ['name' => $name, 'value' => str_repeat('a', 64), 'expire' => 172800];
        if ($name !== 'ordinary') {
            $data['policy_name'] = 'mfa_session';
        }
        $input->set_cookie($data);
        $this->assertSame($expected > 0 ? $this->clock->now + $expected : 0, $input->sent['expire']);
    }

    /**
     * Keep both MFA cookie lifetimes independent of generic cookie overrides.
     *
     * @return array
     */
    public static function cookieLifetimeProvider()
    {
        $cases = [];
        foreach (['mfa_session', 'mfa_session_1', 'mfa_session_27', 'mfa_session_logout'] as $name) {
            foreach ([86400, null] as $stored) {
                foreach ([3600, 0, 345600, null] as $enforced) {
                    $cases[] = [$name, $stored, $enforced, 172800];
                }
            }
        }
        return array_merge($cases, [
            ['ordinary', 86400, null, 86400], ['ordinary', 86400, 0, 0],
            ['ordinary', 86400, 3600, 3600], ['ordinary', 86400, 345600, 345600]
        ]);
    }

    /**
     * Share the revocation reference while keeping approval credentials host-only.
     *
     * @param bool $encrypted Whether the request uses HTTPS.
     * @param string $sameSite Configured SameSite policy.
     * @param bool $secure Configured Secure flag.
     * @param string $expectedSameSite Effective MFA SameSite policy.
     * @param string $name Browser cookie name.
     * @param string $expectedDomain Effective cookie domain.
     * @return void
     * @dataProvider cookiePolicyProvider
     */
    public function testMfaCookiePolicyProtectsTheCredential($encrypted, $sameSite, $secure, $expectedSameSite, $name, $expectedDomain)
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
            'name' => $name, 'policy_name' => 'mfa_session', 'domain' => '.example.test',
            'httponly' => false, 'secure_cookie' => $secure
        ]);
        $this->assertTrue($result['httponly']);
        $this->assertSame($expectedDomain, $result['domain']);
        $this->assertSame($expectedSameSite, $result['samesite']);
        $this->assertSame($encrypted || $secure || $sameSite === 'None', $result['secure_cookie']);
    }

    /**
     * Provide transport policies for credentials and their shared references.
     *
     * @return array
     */
    public static function cookiePolicyProvider()
    {
        $cases = [];
        foreach ([['mfa_session', ''], ['mfa_session_1', ''], ['mfa_session_27', ''],
            ['mfa_session_logout', '.example.test']] as $cookie) {
            foreach ([[true, 'Lax', false, 'Lax'], [true, 'Strict', false, 'Strict'],
                [true, 'None', false, 'Lax'], [false, 'Lax', true, 'Lax'],
                [false, 'Lax', false, 'Lax']] as $policy) {
                $cases[] = array_merge($policy, $cookie);
            }
        }
        return $cases;
    }

    /**
     * Issue the revocation reference before its host credential with one lifetime.
     *
     * @param int $siteId Site whose approval is being issued.
     * @return void
     * @dataProvider siteCookieProvider
     */
    public function testMfaCreationUsesTheSharedPolicyAndSiteCookieName($siteId)
    {
        $this->config->setItem('site_id', $siteId);
        $registry = $this->getMockBuilder(CookieRegistry::class)->onlyMethods(['getCookieSettings'])->getMock();
        $registry->registerNecessary('mfa_session');
        $registry->method('getCookieSettings')
            ->willReturn(['cookie_lifetime' => 86400, 'cookie_enforced_lifetime' => 3600]);
        ee()->setMock('CookieRegistry', $registry);
        $input = $this->capturingInput();
        ee()->setMock('input', $input);
        $this->db->method('query')->willReturn(true);
        $this->db->method('affected_rows')->willReturn(1);

        $this->assertTrue((new \Mfa_session())->create($this->member, 'issuing-session'));
        $this->assertSame(['mfa_session_logout', 'mfa_session_' . $siteId], array_column($input->sentCookies, 'name'));
        $this->assertSame(hash('sha256', $input->sent['value']), $input->sentCookies[0]['value']);
        $this->assertSame('mfa_session', $input->sentCookies[0]['policy_name']);
        $this->assertSame($this->clock->now + 86400, $input->sentCookies[0]['expire']);
        $this->assertSame('mfa_session_' . $siteId, $input->sent['name']);
        $this->assertArrayHasKey('policy_name', $input->sent);
        $this->assertSame('mfa_session', $input->sent['policy_name']);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $input->sent['value']);
        $this->assertSame($this->clock->now + 86400, $input->sent['expire']);
    }

    /**
     * Delete both cookie types without applying their configured lifetime.
     *
     * @param int $siteId Site whose approval is being removed.
     * @return void
     * @dataProvider siteCookieProvider
     */
    public function testMfaDeletionUsesTheSamePolicyWithoutApplyingLifetimeOverrides($siteId)
    {
        $this->config->setItem('site_id', $siteId);
        $registry = $this->getMockBuilder(CookieRegistry::class)->onlyMethods(['getCookieSettings'])->getMock();
        $registry->expects($this->never())->method('getCookieSettings');
        ee()->setMock('CookieRegistry', $registry);
        $input = $this->capturingInput();
        ee()->setMock('input', $input);

        (new \Mfa_session())->delete(0, '');
        $this->assertSame(['mfa_session_logout', 'mfa_session_' . $siteId], array_column($input->sentCookies, 'name'));
        $this->assertSame('mfa_session', $input->sentCookies[0]['policy_name']);
        $this->assertSame('', $input->sentCookies[0]['value']);
        $this->assertLessThan($this->clock->now, $input->sentCookies[0]['expire']);
        $this->assertSame('mfa_session_' . $siteId, $input->sent['name']);
        $this->assertArrayHasKey('policy_name', $input->sent);
        $this->assertSame('mfa_session', $input->sent['policy_name']);
        $this->assertSame('', $input->sent['value']);
        $this->assertLessThan($this->clock->now, $input->sent['expire']);
    }

    public static function siteCookieProvider()
    {
        return [[1], [27]];
    }

    /** @dataProvider ordinaryCookieProvider */
    public function testOrdinaryCookieCreationAndDeletionRemainCompatible($arrayArgument)
    {
        $input = $this->capturingInput();
        $data = ['name' => 'ordinary', 'value' => 'value', 'expire' => 60];
        if ($arrayArgument) {
            $input->set_cookie($data);
        } else {
            $input->set_cookie('ordinary', 'value', 60);
        }
        $this->assertSame('ordinary', $input->sent['name']);
        $this->assertSame('value', $input->sent['value']);
        $this->assertSame($this->clock->now + 60, $input->sent['expire']);

        // Deletion must ignore any value or expiry supplied in the array.
        $this->assertTrue($input->delete_cookie($arrayArgument ? $data : 'ordinary'));
        $this->assertSame('ordinary', $input->sent['name']);
        $this->assertSame('', $input->sent['value']);
        $this->assertLessThan($this->clock->now, $input->sent['expire']);
    }

    public static function ordinaryCookieProvider()
    {
        return [[false], [true]];
    }

    public function testCookieRegistryDoesNotInterpretBrowserNames()
    {
        $registry = new CookieRegistry();
        $registry->registerNecessary('mfa_session');
        $registry->registerFunctionality('mfa_session_1');
        $this->assertSame($registry::NECESSARY, $registry->getType('mfa_session'));
        $this->assertSame($registry::FUNCTIONALITY, $registry->getType('mfa_session_1'));
        $this->assertFalse($registry->isNecessary('mfa_session_1'));
        $this->assertFalse($registry->isRegistered('mfa_session_27'));
    }

    /**
     * Capture normalized cookie writes without sending HTTP headers.
     *
     * @return \EE_Input
     */
    private function capturingInput()
    {
        require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
        require_once BASEPATH . 'core/Input.php';
        return new class extends \EE_Input {
            public $sent;
            public $sentCookies = [];
            public function __construct() {}
            public function cookie($index = '', $xss_clean = false) { return false; }

            /**
             * Record normalized cookie data for assertions.
             *
             * @param array $data Cookie data prepared by the public input API.
             * @return bool
             */
            protected function _set_cookie(array $data)
            {
                $this->sent = $data;
                $this->sentCookies[] = $data;
                return true;
            }
        };
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

    /**
     * Refresh only the logout reference when the restored login still exists.
     *
     * @param string|null $loginId Login identity remaining after restoration.
     * @param string|false $reference Incoming logout reference.
     * @return void
     * @dataProvider restoredLoginProvider
     */
    public function testRestoreRefreshesTheLogoutReferenceForSurvivingLogins($loginId, $reference)
    {
        $this->config->setItem('mfa_session_length', 3600);
        $this->logoutCookie = $reference;
        $this->db->expects($this->once())->method('query')->willReturn(true);
        $this->db->method('affected_rows')->willReturn(1);
        $this->db->expects($this->once())->method('get_where')
            ->with('sessions', ['session_id' => 'new-session'])->willReturnSelf();
        $this->db->expects($this->once())->method('row')->with('mfa_login_id')->willReturn($loginId);
        $this->db->expects($this->never())->method('row_array');

        $this->assertSame($loginId ?: false, (new \Mfa_session())->restore(42, 'new-session'));
        if (!$loginId) {
            $this->assertSame([], $this->cookies);
            return;
        }
        $this->assertSame([[
            'name' => 'mfa_session_logout',
            'policy_name' => 'mfa_session',
            'value' => hash('sha256', $this->cookie),
            'expire' => 3600
        ]], $this->cookies);
    }

    /**
     * Cover missing, stale, and matching references plus concurrent logout.
     *
     * @return array
     */
    public static function restoredLoginProvider()
    {
        $login = hash('sha256', 'original-login');
        return [
            'missing reference' => [$login, false],
            'stale reference' => [$login, str_repeat('b', 64)],
            'matching reference' => [$login, hash('sha256', str_repeat('a', 64))],
            'concurrent logout' => [null, false],
        ];
    }

    /**
     * Recompile routes only after the MFA migration steps succeed.
     *
     * @param bool $exists Whether the MFA approvals table already exists.
     * @param bool $columnExists Whether sessions already have a login identifier.
     * @param bool $written Whether schema changes succeed.
     * @param bool $expected Expected update result.
     * @return void
     * @dataProvider migrationProvider
     */
    public function testMigrationIsRepeatableAndReportsFailures($exists, $columnExists, $written, $expected)
    {
        require_once SYSPATH . 'ee/installer/libraries/Progress.php';
        require_once SYSPATH . 'ee/installer/updates/ud_7_05_29.php';
        require_once BASEPATH . 'libraries/template_router/Route.php';
        $db = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['table_exists', 'field_exists', 'select', 'join', 'where', 'get'])->getMock();
        $db->method('select')->willReturnSelf();
        $db->method('join')->willReturnSelf();
        $db->method('where')->willReturnSelf();
        $db->expects($expected ? $this->once() : $this->never())->method('get')
            ->with('template_routes')->willReturn(new \eeDbResultMock([]));
        $db->data_cache = ['table_names' => []];
        $db->method('table_exists')->with('mfa_sessions')->willReturn($exists);
        $db->expects(!$exists && !$written ? $this->never() : $this->once())->method('field_exists')
            ->with('mfa_login_id', 'sessions')->willReturn($columnExists);
        $forge = $this->getMockBuilder(\stdClass::class)->addMethods(['add_field', 'add_key', 'create_table', 'drop_column', 'add_column'])->getMock();
        $forge->expects($exists ? $this->never() : $this->once())
            ->method('create_table')->with('mfa_sessions')->willReturn($written);
        $forge->expects($this->never())->method('drop_column');
        $forge->expects($columnExists || (!$exists && !$written) ? $this->never() : $this->once())->method('add_column')
            ->with('sessions', ['mfa_login_id' => ['type' => 'char', 'constraint' => 64, 'null' => false, 'default' => '']])
            ->willReturn($written);
        ee()->setMock('db', $db);
        ee()->setMock('dbforge', $forge);
        ee()->setMock('smartforge', $forge);
        $updater = new \ExpressionEngine\Updater\Version_7_5_29\Updater();
        $this->assertSame($expected, $updater->do_update());
    }

    public static function migrationProvider()
    {
        return [[false, false, true, true], [true, true, false, true],
            [false, true, false, false], [true, false, false, false]];
    }
}
