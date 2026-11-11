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
    private $cookie = false;
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
        $this->db = new MfaSessionDatabase();
        $this->member = (object) ['member_id' => 42, 'enable_mfa' => true,
            'password' => 'password-hash', 'unique_id' => 'member-secret', 'backup_mfa_code' => 'mfa-secret'];
        $this->db->query("INSERT INTO test_members VALUES (42, 'y', 'password-hash', 'member-secret', 'mfa-secret')");
        $this->db->query("INSERT INTO test_sessions VALUES ('issuing-session', 42, 'skip')");
        $this->db->query("INSERT INTO test_sessions VALUES ('new-session', 42, 'show')");
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

    private function approve()
    {
        $this->assertTrue($this->mfa->create($this->member, 'issuing-session'));
        return $this->cookie;
    }

    private function approvals()
    {
        return $this->db->query('SELECT * FROM test_mfa_sessions')->result_array();
    }

    private function sessionFlag($id = 'new-session')
    {
        return $this->db->query('SELECT mfa_flag FROM test_sessions WHERE session_id = ?', [$id])->row_array()['mfa_flag'];
    }

    public function testIssuesIndependentHashedApprovalWithoutRememberMe()
    {
        $token = $this->approve();
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $token);
        $this->assertSame([[
            'token_hash' => hash('sha256', $token), 'member_id' => 42, 'site_id' => 1,
            'verified_at' => 2000000, 'expires_at' => 2086400, 'issued_session_hash' => hash('sha256', 'issuing-session')
        ]], $this->approvals());
        $this->assertSame([['mfa_session', $token, 86400]], $this->cookies);
        $this->assertTrue($this->mfa->restore(42, 'new-session'));
        $this->assertSame('skip', $this->sessionFlag());
    }

    public function testApprovalDoesNotStoreTheLoginSessionCredential()
    {
        $this->approve();
        $this->assertNotContains('issuing-session', array_values($this->approvals()[0]));
    }

    public function testOverlappingVerificationsLeaveOnlyOneApprovalPerLogin()
    {
        $otherRequest = new \Mfa_session();
        $oldToken = $this->approve();
        $this->assertTrue($otherRequest->create($this->member, 'issuing-session'));
        $this->assertCount(1, $this->approvals());
        $this->cookie = $oldToken;
        $this->assertFalse((new \Mfa_session())->restore(42, 'new-session'));
    }

    /** @dataProvider disabledLengthProvider */
    public function testDisabledOrInvalidLengthDoesNotIssueOrRestore($value)
    {
        $this->approve();
        $this->config->setItem('mfa_session_length', $value);
        $before = $this->cookies;
        $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
        $this->assertFalse($this->mfa->restore(42, 'new-session'));
        $this->assertSame($before, $this->cookies);
        $this->assertSame('show', $this->sessionFlag());
    }

    public static function disabledLengthProvider()
    {
        return [[false], [null], [0], [-1], ['0'], ['invalid'], ['1.5'], [1.5], [true], [[]], [PHP_INT_MAX]];
    }

    public function testOldSettingDoesNotEnableReuse()
    {
        $this->config->setItem('mfa_session_length', false);
        $this->config->setItem('mfa_remember_ttl', 86400);
        $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
        $this->assertSame([], $this->approvals());
    }

    /** @dataProvider malformedCookieProvider */
    public function testMalformedOrMissingCookieCannotRestore($cookie)
    {
        $this->approve();
        $this->cookie = $cookie;
        $this->assertFalse((new \Mfa_session())->restore(42, 'new-session'));
        $this->assertSame('show', $this->sessionFlag());
    }

    public static function malformedCookieProvider()
    {
        return [[false], [''], ['short'], [str_repeat('z', 64)], [str_repeat('a', 65)], [str_repeat('a', 64) . "\n"], [['token']], [str_repeat('b', 64)]];
    }

    /** @dataProvider invalidApprovalProvider */
    public function testRestoreRejectsAnInvalidApproval($sql)
    {
        $this->approve();
        $this->db->query($sql);
        $this->assertFalse($this->mfa->restore(42, 'new-session'));
        $this->assertSame('show', $this->sessionFlag());
    }

    public static function invalidApprovalProvider()
    {
        return [
            ['DELETE FROM test_mfa_sessions'],
            ['UPDATE test_mfa_sessions SET member_id = 43'],
            ['UPDATE test_mfa_sessions SET site_id = 2'],
            ['UPDATE test_mfa_sessions SET expires_at = 2000000'],
            ['UPDATE test_mfa_sessions SET expires_at = 1999999'],
            ['UPDATE test_mfa_sessions SET verified_at = 2000001'],
            ['UPDATE test_mfa_sessions SET verified_at = 1913600'],
            ["UPDATE test_members SET enable_mfa = 'n'"],
            ['DELETE FROM test_members']
        ];
    }

    public function testRestorationCannotApproveAnotherMemberOrEnrollmentSession()
    {
        $this->approve();
        $this->db->query("INSERT INTO test_sessions VALUES ('other-member', 43, 'show')");
        $this->db->query("INSERT INTO test_sessions VALUES ('enrollment', 42, 'required')");
        $this->assertFalse($this->mfa->restore(43, 'other-member'));
        $this->assertFalse($this->mfa->restore(42, 'other-member'));
        $this->assertFalse($this->mfa->restore(42, 'enrollment'));
        $this->assertFalse($this->mfa->restore(42, 'missing-session'));
        $this->assertSame('show', $this->sessionFlag('other-member'));
        $this->assertSame('required', $this->sessionFlag('enrollment'));
    }

    public function testPasswordReauthenticationDoesNotExtendTheWindow()
    {
        $this->approve();
        $before = $this->approvals();
        $this->clock->now += 86399;
        $this->assertTrue($this->mfa->restore(42, 'new-session'));
        $this->assertSame($before, $this->approvals());
        $this->assertCount(1, $this->cookies);
        $this->clock->now++;
        $this->db->query("UPDATE test_sessions SET mfa_flag = 'show' WHERE session_id = 'new-session'");
        $this->assertFalse($this->mfa->restore(42, 'new-session'));
    }

    public function testConfigurationCannotExtendAnAlreadyIssuedApproval()
    {
        $this->approve();
        $this->config->setItem('mfa_session_length', 604800);
        $this->clock->now += 86400;
        $this->assertFalse($this->mfa->restore(42, 'new-session'));
    }

    public function testShorterConfiguredWindowAppliesToExistingApprovals()
    {
        $this->approve();
        $this->config->setItem('mfa_session_length', '3600');
        $this->clock->now += 3600;
        $this->assertFalse($this->mfa->restore(42, 'new-session'));
    }

    public function testApprovalSurvivesDeletionOfItsIssuingSession()
    {
        $this->approve();
        $this->db->query("DELETE FROM test_sessions WHERE session_id = 'issuing-session'");
        $this->assertTrue($this->mfa->restore(42, 'new-session'));
    }

    public function testNewVerificationReplacesTheTokenRatherThanPromotingIt()
    {
        $oldToken = $this->approve();
        $this->clock->now += 10;
        $newToken = $this->approve();
        $this->assertNotSame($oldToken, $newToken);
        $this->assertCount(1, $this->approvals());
        $this->cookie = $oldToken;
        $this->assertFalse((new \Mfa_session())->restore(42, 'new-session'));
        $this->cookie = $newToken;
        $this->assertTrue((new \Mfa_session())->restore(42, 'new-session'));
        $this->assertSame(2086410, $this->approvals()[0]['expires_at']);
    }

    /** @dataProvider changedCredentialProvider */
    public function testVerificationCannotOutliveCredentialChange($column, $value)
    {
        $this->db->beforeQuery = function () use ($column, $value) {
            $this->db->query("UPDATE test_members SET {$column} = ? WHERE member_id = 42", [$value]);
            $this->mfa->revoke_member(42);
        };
        $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
        $this->assertSame([], $this->approvals());
        $this->assertSame([], $this->cookies);
    }

    public static function changedCredentialProvider()
    {
        return [['password', 'changed-password'], ['unique_id', 'changed-uid'],
            ['backup_mfa_code', 'changed-mfa'], ['enable_mfa', 'n']];
    }

    public function testUnverifiedSessionCannotIssueApproval()
    {
        $this->assertFalse($this->mfa->create($this->member, 'new-session'));
        $this->assertFalse($this->mfa->create($this->member, 'nonexistent'));
        $this->assertSame([], $this->approvals());
    }

    public function testRevocationBetweenRequestStartAndRestorationIsAuthoritative()
    {
        $this->approve();
        $restoring = new \Mfa_session();
        $this->db->beforeQuery = function () {
            $this->mfa->revoke_member(42);
        };
        $this->assertFalse($restoring->restore(42, 'new-session'));
        $this->assertSame('show', $this->sessionFlag());
    }

    /** @dataProvider operationOrderProvider */
    public function testLogoutAndVerificationCannotLeaveAReplacementApproval($verificationFirst)
    {
        $this->approve();
        $logoutRequest = new \Mfa_session();
        if ($verificationFirst) {
            $this->approve();
        }
        $this->db->query("DELETE FROM test_sessions WHERE session_id = 'issuing-session'");
        $logoutRequest->delete(42, 'issuing-session');
        if (!$verificationFirst) {
            $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
        }
        $this->assertSame([], $this->approvals());
        $this->assertFalse($this->mfa->restore(42, 'new-session'));
    }

    public static function operationOrderProvider()
    {
        return [[true], [false]];
    }

    public function testLogoutPreservesOtherBrowsersButMemberRevocationDoesNot()
    {
        $first = $this->approve();
        $this->cookie = false;
        $this->db->query("INSERT INTO test_sessions VALUES ('other-browser', 42, 'skip')");
        $other = new \Mfa_session();
        $this->assertTrue($other->create($this->member, 'other-browser'));
        $second = $this->cookie;
        $this->cookie = $first;
        $this->db->query("DELETE FROM test_sessions WHERE session_id = 'issuing-session'");
        (new \Mfa_session())->delete(42, 'issuing-session');
        $this->assertSame([hash('sha256', $second)], array_column($this->approvals(), 'token_hash'));
        $this->mfa->revoke_member(42);
        $this->assertSame([], $this->approvals());
    }

    public function testAnotherMembersRevocationDoesNotAffectThisApproval()
    {
        $this->approve();
        $this->mfa->revoke_member(43);
        $this->assertTrue($this->mfa->restore(42, 'new-session'));
    }

    public function testFailedDatabaseWritesNeverApproveOrSendACookie()
    {
        $this->db->failNext = true;
        $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
        $this->assertSame([], $this->cookies);
        $this->approve();
        $this->db->failNext = true;
        $this->assertFalse($this->mfa->restore(42, 'new-session'));
        $this->assertSame('show', $this->sessionFlag());
    }

    public function testSessionOnlyModeDoesNotUsePersistentMfaCookies()
    {
        $this->config->setItem('website_session_type', 's');
        $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
        $this->assertSame([], $this->cookies);
    }

    public function testDisabledGlobalMfaDoesNotIssueApproval()
    {
        $this->config->setItem('enable_mfa', 'n');
        $this->assertFalse($this->mfa->create($this->member, 'issuing-session'));
    }

    /** @dataProvider memberChangeProvider */
    public function testMemberModelRevokesOnlyForCredentialChanges($field, $revoked)
    {
        $this->approve();
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
        $this->assertCount($revoked ? 0 : 1, $this->approvals());
    }

    public static function memberChangeProvider()
    {
        return [['password', true], ['enable_mfa', true], ['backup_mfa_code', true], ['unique_id', true], ['bio', false]];
    }

    public function testMemberDeletionRevokesApproval()
    {
        $this->approve();
        $member = $this->getMockBuilder(Member::class)->disableOriginalConstructor()
            ->onlyMethods(['updateRoleTotalMembers'])->getMock();
        $property = new \ReflectionProperty(Member::class, 'member_id');
        \TestReflectionHelper::makePropertyAccessible($property);
        $property->setValue($member, 42);
        $member->onAfterDelete();
        $this->assertSame([], $this->approvals());
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

/**
 * Execute SQL rather than duplicating its authorization predicates in a fake.
 * SQLite is test-only; browser integration exercises the real MySQL driver.
 */
class MfaSessionDatabase
{
    private $pdo;
    private $affected = 0;
    public $beforeQuery;
    public $failNext = false;

    public function __construct()
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE test_mfa_sessions (token_hash TEXT PRIMARY KEY, member_id INTEGER,
            site_id INTEGER, verified_at INTEGER, expires_at INTEGER, issued_session_hash TEXT)');
        $this->pdo->exec('CREATE TABLE test_sessions (session_id TEXT PRIMARY KEY, member_id INTEGER, mfa_flag TEXT)');
        $this->pdo->exec('CREATE TABLE test_members (member_id INTEGER PRIMARY KEY, enable_mfa TEXT,
            password TEXT, unique_id TEXT, backup_mfa_code TEXT)');
    }

    public function dbprefix($table)
    {
        return 'test_' . $table;
    }

    public function table_exists($table)
    {
        return $table === 'mfa_sessions';
    }

    public function affected_rows()
    {
        return $this->affected;
    }

    public function query($sql, $binds = [])
    {
        if ($this->beforeQuery) {
            $callback = $this->beforeQuery;
            $this->beforeQuery = null;
            $callback();
        }
        if ($this->failNext) {
            $this->failNext = false;
            return false;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($binds);
        $this->affected = $statement->rowCount();
        $rows = $statement->columnCount() ? $statement->fetchAll(\PDO::FETCH_ASSOC) : [];
        return new class($rows) {
            private $rows;
            public function __construct($rows)
            {
                $this->rows = $rows;
            }
            public function row_array()
            {
                return $this->rows[0] ?? [];
            }
            public function result_array()
            {
                return $this->rows;
            }
        };
    }
}
