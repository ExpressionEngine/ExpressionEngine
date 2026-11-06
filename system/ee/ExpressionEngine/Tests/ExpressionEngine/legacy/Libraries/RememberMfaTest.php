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
class RememberMfaTest extends TestCase
{
    private $db;
    private $config;
    private $clock;
    private $input;
    private $remember;
    private $cookieValue = 'token-a';
    private $cookieExpiration;

    /**
     * Load the real authentication libraries with isolated request services.
     *
     * @return void
     */
    protected function setUp(): void
    {
        ee()->resetMocks();
        if (!defined('REQ')) {
            define('REQ', 'PAGE');
        }
        require_once BASEPATH . 'libraries/Remember.php';

        $this->clock = (object) ['now' => 2000000];
        $this->config = new \eeSingletonConfigMock();
        $this->config->resetConfig();
        $this->config->setItem('mfa_remember_ttl', 604800);
        $this->config->setItem('site_id', 1);
        $this->input = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['cookie', 'ip_address', 'user_agent', 'delete_cookie', 'set_cookie'])
            ->getMock();
        $this->input->method('cookie')->with('remember')->willReturnCallback(function () {
            return $this->cookieValue;
        });
        $this->input->method('set_cookie')->willReturnCallback(function ($name, $value, $expiration) {
            $this->cookieValue = $value;
            $this->cookieExpiration = $expiration;
        });
        $this->input->method('delete_cookie')->willReturnCallback(function () {
            $this->cookieValue = false;
        });
        $this->input->method('user_agent')->willReturn('test-browser');
        $this->input->method('ip_address')->willReturn('127.0.0.1');

        $this->db = new RememberMfaDatabase();
        $this->db->tables['remember_me'] = [[
            'remember_me_id' => 'token-a',
            'member_id' => 42,
            'site_id' => 1,
            'admin_sess' => 1,
            'user_agent' => 'test-browser',
            'expiration' => $this->clock->now + 1209600,
            'last_refresh' => $this->clock->now - 86401,
            'mfa_verified_at' => 0
        ]];

        ee()->setMock('db', $this->db);
        ee()->setMock('config', $this->config);
        ee()->setMock('input', $this->input);
        ee()->setMock('localize', $this->clock);
        $functions = $this->getMockBuilder(\stdClass::class)->addMethods(['random'])->getMock();
        $tokenNumber = 0;
        $functions->method('random')->willReturnCallback(function () use (&$tokenNumber) {
            return 'generated-token-' . ++$tokenNumber;
        });
        ee()->setMock('functions', $functions);
        $this->remember = new \Remember();
        ee()->setMock('remember', $this->remember);
    }

    /**
     * Release request state for subsequent tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->config->resetConfig();
        ee()->resetMocks();
        parent::tearDown();
    }

    /**
     * Treat unsupported configuration values as opting out of MFA reuse.
     *
     * @dataProvider disabledTtlProvider
     * @param mixed $ttl The configured value.
     * @return void
     */
    public function testDisabledOrInvalidTtlDoesNotRememberMfa($ttl)
    {
        $this->config->setItem('mfa_remember_ttl', $ttl);
        $this->remember->remember_mfa(42);
        $this->assertFalse($this->remember->is_mfa_verified(42));
        $this->assertSame(0, $this->db->tables['remember_me'][0]['mfa_verified_at']);
        $this->assertSame([], $this->db->updates);
    }

    /**
     * Supply disabled, malformed, and non-integral durations.
     *
     * @return array
     */
    public static function disabledTtlProvider()
    {
        return [[false], [null], [0], ['0'], [-1], ['-1'], [''], ['seven days'], [true], [1.5], ['1.5'], [[]], ['99999999999999999999999999999']];
    }

    /**
     * Bound reuse by the last code verification, rather than the last request.
     *
     * @dataProvider verificationWindowProvider
     * @param int|null $verifiedAt Stored verification time, or a legacy missing field.
     * @param bool $expected Whether MFA can be reused.
     * @return void
     */
    public function testVerificationWindow($verifiedAt, $expected)
    {
        $this->config->setItem('mfa_remember_ttl', '604800');
        $this->db->tables['remember_me'][0]['mfa_verified_at'] = $verifiedAt;
        if ($verifiedAt === null) {
            unset($this->db->tables['remember_me'][0]['mfa_verified_at']);
        }
        $this->assertSame($expected, $this->remember->is_mfa_verified(42));
    }

    /**
     * Supply verification-window boundaries and legacy values.
     *
     * @return array
     */
    public static function verificationWindowProvider()
    {
        return [
            'not verified' => [0, false],
            'legacy row' => [null, false],
            'just verified' => [2000000, true],
            'one second remaining' => [2000000 - 604799, true],
            'exact expiry' => [2000000 - 604800, false],
            'expired' => [2000000 - 604801, false],
            'future timestamp' => [2000001, false]
        ];
    }

    /**
     * Require a valid remembered token for the same browser, member, and site.
     *
     * @dataProvider invalidTokenProvider
     * @param array $changes Changes to the remembered record.
     * @param int $memberId The member requesting MFA reuse.
     * @return void
     */
    public function testUnrelatedOrInvalidRememberedLoginsCannotGainMfa(array $changes, $memberId)
    {
        $this->db->tables['remember_me'][0] = array_merge($this->db->tables['remember_me'][0], $changes);
        $this->remember->remember_mfa($memberId);
        $this->assertFalse($this->remember->is_mfa_verified($memberId));
        $this->assertSame([], $this->db->updates);
    }

    /**
     * Supply mismatched identities and invalid remember-token states.
     *
     * @return array
     */
    public static function invalidTokenProvider()
    {
        return [
            'different member' => [[], 43],
            'guest' => [[], 0],
            'different site' => [['site_id' => 2], 42],
            'different browser' => [['user_agent' => 'other-browser'], 42],
            'unknown token' => [['remember_me_id' => 'other-token'], 42],
            'expired token' => [['expiration' => 1999999], 42],
            'token expiry boundary' => [['expiration' => 2000000], 42]
        ];
    }

    /**
     * Keep verification scoped to the token whose code was completed.
     *
     * @return void
     */
    public function testSuccessfulVerificationOnlyRemembersTheCurrentToken()
    {
        $other = $this->db->tables['remember_me'][0];
        $other['remember_me_id'] = 'token-b';
        $this->db->tables['remember_me'][] = $other;
        $this->remember->remember_mfa(42);
        $this->assertSame(2000000, $this->db->tables['remember_me'][0]['mfa_verified_at']);
        $this->assertSame(0, $this->db->tables['remember_me'][1]['mfa_verified_at']);
        $this->assertTrue($this->remember->is_mfa_verified(42));

        $this->clock->now += 604800;
        $this->assertFalse($this->remember->is_mfa_verified(42));
    }


    /**
     * A token copied before the code was verified must never gain MFA approval.
     */
    public function testPreVerificationTokenCannotRestoreLoginAfterMfa()
    {
        $oldToken = $this->cookieValue;
        $this->remember->remember_mfa(42);
        $newToken = $this->cookieValue;

        $this->assertNotSame($oldToken, $newToken);
        $this->assertSame($newToken, $this->db->tables['remember_me'][0]['remember_me_id']);

        $this->cookieValue = $oldToken;
        $stale = new \Remember();
        $this->assertFalse((bool) $stale->exists());
        $this->assertFalse($stale->is_mfa_verified(42));

        $this->cookieValue = $newToken;
        $this->assertTrue((new \Remember())->is_mfa_verified(42));
    }

    /**
     * Completing MFA changes the token, not the remembered login's expiration.
     */
    public function testVerificationRotationDoesNotExtendRememberExpiration()
    {
        $expires = $this->db->tables['remember_me'][0]['expiration'];
        $this->clock->now += 3600;
        $this->remember->remember_mfa(42);

        $this->assertSame($expires, $this->db->tables['remember_me'][0]['expiration']);
        $this->assertSame($expires - $this->clock->now, $this->cookieExpiration);
        $this->assertSame($this->clock->now, $this->db->tables['remember_me'][0]['mfa_verified_at']);
    }

    /**
     * MFA must use the token rotated earlier in the same request.
     *
     * @return void
     */
    public function testVerificationAfterRefreshUsesTheRotatedToken()
    {
        $functions = $this->getMockBuilder(\stdClass::class)->addMethods(['random'])->getMock();
        $functions->expects($this->exactly(2))->method('random')
            ->willReturnOnConsecutiveCalls('refreshed-token', 'verified-token');
        ee()->setMock('functions', $functions);

        // The fixture's token is old enough for the daily refresh to rotate it.
        $this->remember->refresh();
        $this->assertSame('refreshed-token', $this->cookieValue);
        $this->assertSame('refreshed-token', $this->db->tables['remember_me'][0]['remember_me_id']);
        $expires = $this->db->tables['remember_me'][0]['expiration'];

        $this->remember->remember_mfa(42);

        $this->assertSame('verified-token', $this->cookieValue);
        $this->assertSame('verified-token', $this->db->tables['remember_me'][0]['remember_me_id']);
        $this->assertSame($this->clock->now, $this->db->tables['remember_me'][0]['mfa_verified_at']);
        $this->assertSame($expires, $this->db->tables['remember_me'][0]['expiration']);
        $this->assertTrue((new \Remember())->is_mfa_verified(42));

        $this->cookieValue = 'refreshed-token';
        $this->assertFalse((bool) (new \Remember())->exists());
    }

    /**
     * An overlapping token revocation must not issue an approved replacement.
     */
    public function testConcurrentRevocationDoesNotIssueAVerifiedCookie()
    {
        $this->remember->exists();
        $this->db->tables['remember_me'] = [];
        $this->remember->remember_mfa(42);

        $this->assertSame('token-a', $this->cookieValue);
        $this->assertNull($this->cookieExpiration);
        $this->assertFalse($this->remember->is_mfa_verified(42));
    }

    /**
     * Revocation preserves only a new, unverified token for the current browser.
     *
     * @return void
     */
    public function testRevocationClearsAllMemberTokensAcrossSites()
    {
        $this->remember->remember_mfa(42);
        $oldToken = $this->cookieValue;
        $expires = $this->remember->data('expiration');
        $other = $this->db->tables['remember_me'][0];
        $other['remember_me_id'] = 'token-b';
        $other['site_id'] = 2;
        $this->db->tables['remember_me'][] = $other;
        $other['remember_me_id'] = 'token-c';
        $other['member_id'] = 43;
        $this->db->tables['remember_me'][] = $other;

        $this->clock->now += 3600;
        $this->remember->clear_mfa(42);

        $this->assertFalse($this->remember->is_mfa_verified(42));
        $this->assertNotSame($oldToken, $this->cookieValue);
        $this->assertSame($expires - $this->clock->now, $this->cookieExpiration);
        $this->assertSame([42, 43], array_column($this->db->tables['remember_me'], 'member_id'));
        $this->assertSame([0, 2000000], array_column($this->db->tables['remember_me'], 'mfa_verified_at'));
        $this->assertSame($other, $this->db->tables['remember_me'][1]);

        $this->cookieValue = $oldToken;
        $this->assertFalse((bool) (new \Remember())->exists());
    }

    /**
     * A security change must win over MFA completed by an overlapping request.
     *
     * @dataProvider overlappingVerificationProvider
     * @param bool $verificationFirst Whether MFA rotates the token before revocation.
     * @return void
     */
    public function testRevocationPreventsOverlappingVerification($verificationFirst)
    {
        $this->assertTrue((bool) $this->remember->exists());
        $revokingRequest = new \Remember();
        $this->assertTrue((bool) $revokingRequest->exists());

        if ($verificationFirst) {
            $this->remember->remember_mfa(42);
        }
        $revokingRequest->clear_mfa(42);
        if (!$verificationFirst) {
            $this->remember->remember_mfa(42);
        }

        // Inspect every surviving token, not just the last response's cookie.
        foreach ($this->db->tables['remember_me'] as $row) {
            $this->cookieValue = $row['remember_me_id'];
            $this->assertFalse((new \Remember())->is_mfa_verified(42));
        }
        $this->assertCount($verificationFirst ? 0 : 1, $this->db->tables['remember_me']);
        $this->cookieValue = 'token-a';
        $this->assertFalse((bool) (new \Remember())->exists());
    }

    /**
     * Cover both orders in which the overlapping requests write their tokens.
     *
     * @return array
     */
    public static function overlappingVerificationProvider()
    {
        return ['revocation first' => [false], 'verification first' => [true]];
    }

    /**
     * Enrollment can remember a valid code after saving the new MFA settings.
     *
     * @return void
     */
    public function testEnrollmentRemembersMfaAfterRevocation()
    {
        $this->remember->exists();
        $expires = $this->remember->data('expiration');
        $this->remember->clear_mfa(42);
        $unverifiedToken = $this->cookieValue;

        $this->assertTrue((bool) (new \Remember())->exists());
        $this->assertFalse((new \Remember())->is_mfa_verified(42));
        $this->remember->remember_mfa(42);

        $this->assertNotSame($unverifiedToken, $this->cookieValue);
        $this->assertTrue((new \Remember())->is_mfa_verified(42));
        $this->assertSame($expires, $this->remember->data('expiration'));
        $this->assertSame($expires - $this->clock->now, $this->cookieExpiration);

        $this->cookieValue = $unverifiedToken;
        $this->assertFalse((bool) (new \Remember())->exists());
    }

    /**
     * Changing another member's security settings leaves the administrator alone.
     *
     * @return void
     */
    public function testRevokingAnotherMemberPreservesCurrentLogin()
    {
        $this->remember->remember_mfa(42);
        $currentToken = $this->cookieValue;
        $other = $this->db->tables['remember_me'][0];
        $other['remember_me_id'] = 'token-b';
        $other['member_id'] = 43;
        $this->db->tables['remember_me'][] = $other;

        $this->remember->clear_mfa(43);

        $this->assertSame($currentToken, $this->cookieValue);
        $this->assertTrue((new \Remember())->is_mfa_verified(42));
        $this->assertSame([42], array_column($this->db->tables['remember_me'], 'member_id'));
    }

    /**
     * Restore only the requested session without renewing MFA or setting a cookie.
     */
    public function testRestorationApprovesOnlyTheMatchingSession()
    {
        $this->prepareRestoration();
        $token = $this->db->tables['remember_me'][0];
        $this->input->expects($this->never())->method('set_cookie');
        $this->input->expects($this->never())->method('delete_cookie');

        $this->assertTrue($this->remember->restore_mfa(42, 'new-session'));
        $this->assertSame('skip', $this->db->tables['sessions'][0]['mfa_flag']);
        $this->assertSame('show', $this->db->tables['sessions'][1]['mfa_flag']);
        $this->assertSame($token, $this->db->tables['remember_me'][0]);
    }

    /**
     * An approved row cached before revocation cannot authorize a new session.
     */
    public function testRestorationRejectsCachedApprovalAfterRevocation()
    {
        $this->prepareRestoration();
        $restoringRequest = new \Remember();
        $this->assertTrue((bool) $restoringRequest->exists());
        $this->remember->clear_mfa(42);
        $replacement = $this->cookieValue;
        $this->input->expects($this->never())->method('set_cookie');
        $this->input->expects($this->never())->method('delete_cookie');

        $this->assertFalse($restoringRequest->restore_mfa(42, 'new-session'));
        $this->assertSame('show', $this->db->tables['sessions'][0]['mfa_flag']);
        $this->assertSame($replacement, $this->cookieValue);
    }

    /**
     * Validate persisted authorization in the same operation that approves MFA.
     *
     * @dataProvider changedRestorationStateProvider
     */
    public function testRestorationRejectsChangesImmediatelyBeforeApproval($table, array $changes)
    {
        $this->prepareRestoration();
        $this->assertTrue($this->remember->is_mfa_verified(42));
        $this->db->beforeQuery = function () use ($table, $changes) {
            $this->db->tables[$table][0] = array_merge($this->db->tables[$table][0], $changes);
        };

        $this->assertFalse($this->remember->restore_mfa(42, 'new-session'));
        $this->assertNotSame('skip', $this->db->tables['sessions'][0]['mfa_flag']);
    }

    public static function changedRestorationStateProvider()
    {
        return [
            'rotated token' => ['remember_me', ['remember_me_id' => 'replacement-token']],
            'revoked approval' => ['remember_me', ['mfa_verified_at' => 0]],
            'expired approval' => ['remember_me', ['mfa_verified_at' => 1395200]],
            'future approval' => ['remember_me', ['mfa_verified_at' => 2000001]],
            'expired token' => ['remember_me', ['expiration' => 2000000]],
            'different token member' => ['remember_me', ['member_id' => 43]],
            'different site' => ['remember_me', ['site_id' => 2]],
            'different browser' => ['remember_me', ['user_agent' => 'other-browser']],
            'deleted session' => ['sessions', ['session_id' => 'removed-session']],
            'different session member' => ['sessions', ['member_id' => 43]],
            'session awaiting enrollment' => ['sessions', ['mfa_flag' => 'setup']]
        ];
    }

    public function testRestorationRejectsRevocationBetweenValidationAndApproval()
    {
        $this->prepareRestoration();
        $revokingRequest = new \Remember();
        $this->assertTrue((bool) $revokingRequest->exists());
        $this->db->beforeQuery = function () use ($revokingRequest) {
            $revokingRequest->clear_mfa(42);
        };

        $this->assertFalse($this->remember->restore_mfa(42, 'new-session'));
        $this->assertSame('show', $this->db->tables['sessions'][0]['mfa_flag']);
        $this->assertSame(0, $this->db->tables['remember_me'][0]['mfa_verified_at']);
    }

    public function testFailedRestorationWriteDoesNotApproveMfa()
    {
        $this->prepareRestoration();
        $this->db->failQuery = true;

        $this->assertFalse($this->remember->restore_mfa(42, 'new-session'));
        $this->assertSame('show', $this->db->tables['sessions'][0]['mfa_flag']);
    }

    private function prepareRestoration()
    {
        $db = new RememberMfaSqlDatabase();
        $db->tables = $this->db->tables;
        $db->tables['sessions'] = [
            ['session_id' => 'new-session', 'member_id' => 42, 'mfa_flag' => 'show'],
            ['session_id' => 'other-session', 'member_id' => 42, 'mfa_flag' => 'show']
        ];
        $this->db = $db;
        ee()->setMock('db', $db);
        $this->remember->remember_mfa(42);
    }

    /**
     * A losing refresh must not overwrite the winning request's cookie.
     *
     * @dataProvider overlappingRefreshProvider
     */
    public function testLosingRefreshPreservesReplacementCookie($operation, array $arguments)
    {
        $this->remember->remember_mfa(42);
        $refreshingRequest = new \Remember();
        $this->assertTrue((bool) $refreshingRequest->exists());

        $this->remember->$operation(...$arguments);
        $replacement = $this->cookieValue;
        $expires = $this->cookieExpiration;
        $this->input->expects($this->never())->method('set_cookie');
        $this->input->expects($this->never())->method('delete_cookie');

        $refreshingRequest->refresh();

        $this->assertSame($replacement, $this->cookieValue);
        $this->assertSame($expires, $this->cookieExpiration);
        if ($operation === 'delete') {
            $this->assertSame([], $this->db->tables['remember_me']);
        } else {
            $this->assertSame($replacement, $this->db->tables['remember_me'][0]['remember_me_id']);
        }
        $this->assertFalse((bool) $refreshingRequest->exists());
        $this->assertFalse($refreshingRequest->is_mfa_verified(42));
    }

    public static function overlappingRefreshProvider()
    {
        return [
            'revocation wins' => ['clear_mfa', [42]],
            'daily refresh wins' => ['refresh', []],
            'code verification wins' => ['remember_mfa', [42]],
            'logout wins' => ['delete', []]
        ];
    }

    /**
     * Revocation must win whether the daily refresh rotates before or after it.
     *
     * @dataProvider refreshRevocationOrderProvider
     */
    public function testRevocationPreventsRestorationAfterOverlappingRefresh($refreshFirst)
    {
        $this->prepareRestoration();
        $refreshingRequest = new \Remember();
        $this->assertTrue((bool) $refreshingRequest->exists());

        if ($refreshFirst) {
            $refreshingRequest->refresh();
        }
        $this->remember->clear_mfa(42);
        if (!$refreshFirst) {
            $refreshingRequest->refresh();
        }

        // A stale current token makes revocation discard its replacement too.
        $this->assertCount($refreshFirst ? 0 : 1, $this->db->tables['remember_me']);
        foreach ($this->db->tables['remember_me'] as $row) {
            $this->assertSame(0, $row['mfa_verified_at']);
        }
        $this->assertFalse($refreshingRequest->restore_mfa(42, 'new-session'));
        $this->assertSame('show', $this->db->tables['sessions'][0]['mfa_flag']);
    }

    /**
     * A competing refresh cannot confer MFA on a token issued before the code.
     *
     * @dataProvider verificationRefreshOrderProvider
     */
    public function testOverlappingVerificationAndRefreshRespectTokenOwnership($verificationFirst)
    {
        $this->remember->exists();
        $refreshingRequest = new \Remember();
        $this->assertTrue((bool) $refreshingRequest->exists());

        if ($verificationFirst) {
            $this->remember->remember_mfa(42);
        }
        $refreshingRequest->refresh();
        if (!$verificationFirst) {
            $winner = $this->cookieValue;
            $this->input->expects($this->never())->method('set_cookie');
            $this->input->expects($this->never())->method('delete_cookie');
            $this->remember->remember_mfa(42);
            $this->assertSame($winner, $this->cookieValue);
        }

        $this->assertCount(1, $this->db->tables['remember_me']);
        $this->assertSame($this->cookieValue, $this->db->tables['remember_me'][0]['remember_me_id']);
        $this->assertNotSame('token-a', $this->cookieValue);
        $this->assertSame($verificationFirst ? $this->clock->now : 0, $this->db->tables['remember_me'][0]['mfa_verified_at']);
        $this->assertSame($verificationFirst, (new \Remember())->is_mfa_verified(42));
    }

    public static function refreshRevocationOrderProvider()
    {
        return ['refresh first' => [true], 'revocation first' => [false]];
    }

    public static function verificationRefreshOrderProvider()
    {
        return ['verification first' => [true], 'refresh first' => [false]];
    }

    /**
     * Restoring during a refresh must reject the old token and accept its successor.
     */
    public function testRestorationDuringRefreshDoesNotExtendVerification()
    {
        $this->prepareRestoration();
        $verifiedAt = $this->remember->data('mfa_verified_at');
        $refreshingRequest = new \Remember();
        $this->assertTrue((bool) $refreshingRequest->exists());
        $this->clock->now += 86401;
        $this->db->beforeQuery = function () use ($refreshingRequest) {
            $refreshingRequest->refresh();
        };

        $this->assertFalse($this->remember->restore_mfa(42, 'new-session'));
        $this->assertSame('show', $this->db->tables['sessions'][0]['mfa_flag']);
        $this->assertSame($verifiedAt, $this->db->tables['remember_me'][0]['mfa_verified_at']);
        $this->input->expects($this->never())->method('set_cookie');
        $this->input->expects($this->never())->method('delete_cookie');
        $this->assertTrue((new \Remember())->restore_mfa(42, 'new-session'));
        $this->assertSame('skip', $this->db->tables['sessions'][0]['mfa_flag']);

        $this->clock->now = $verifiedAt + 604800;
        $this->assertFalse((new \Remember())->restore_mfa(42, 'other-session'));
        $this->assertSame('show', $this->db->tables['sessions'][1]['mfa_flag']);
    }

    /**
     * Ordinary remembered logins retain their rolling expiration without MFA.
     */
    public function testDailyRefreshWithoutMfaStillExtendsRememberedLogin()
    {
        $this->config->setItem('mfa_remember_ttl', 0);
        $before = $this->db->tables['remember_me'][0];
        $this->remember->refresh();

        $this->assertNotSame($before['remember_me_id'], $this->cookieValue);
        $this->assertSame($this->cookieValue, $this->remember->data('remember_me_id'));
        $this->assertSame($before['expiration'] + 86401, $this->remember->data('expiration'));
        $this->assertSame($this->clock->now, $this->remember->data('last_refresh'));
        $this->assertSame(0, $this->remember->data('mfa_verified_at'));
        $this->assertTrue((bool) (new \Remember())->exists());
    }

    /**
     * Token rotation neither renews verification nor restores concurrent revocations.
     *
     * @dataProvider revocationProvider
     * @param bool $revoke Whether another request revoked the stored verification.
     * @return void
     */
    public function testTokenRefreshPreservesVerificationLifetime($revoke)
    {
        $this->remember->remember_mfa(42);
        if ($revoke) {
            $this->db->tables['remember_me'][0]['mfa_verified_at'] = 0;
        }
        $functions = $this->getMockBuilder(\stdClass::class)->addMethods(['random'])->getMock();
        $functions->method('random')->willReturn('rotated-token');
        ee()->setMock('functions', $functions);
        $this->clock->now += 86401;
        $this->remember->refresh();

        $this->assertSame('rotated-token', $this->db->tables['remember_me'][0]['remember_me_id']);
        $this->assertSame($revoke ? 0 : 2000000, $this->db->tables['remember_me'][0]['mfa_verified_at']);
        $this->assertArrayNotHasKey('mfa_verified_at', end($this->db->updates)['changes']);
    }

    /**
     * Exercise ordinary refresh and an overlapping revocation.
     *
     * @return array
     */
    public static function revocationProvider()
    {
        return [[false], [true]];
    }

    /**
     * Explicit logout removes the current token and its MFA verification.
     *
     * @return void
     */
    public function testLogoutRemovesRememberedMfa()
    {
        $this->remember->remember_mfa(42);
        $this->remember->delete();
        $this->assertFalse($this->remember->is_mfa_verified(42));
        $this->assertSame([], $this->db->tables['remember_me']);
    }

    /**
     * Replacing an old remembered login must not inherit its verified timestamp.
     *
     * @return void
     */
    public function testReplacingOldestTokenStartsUnverified()
    {
        $this->db->tables['remember_me'][0]['mfa_verified_at'] = 1999999;
        for ($index = 1; $index < 5; $index++) {
            $row = $this->db->tables['remember_me'][0];
            $row['remember_me_id'] = 'token-' . $index;
            $this->db->tables['remember_me'][] = $row;
        }
        $session = $this->getMockBuilder(\stdClass::class)->addMethods(['userdata'])->getMock();
        $session->method('userdata')->willReturnMap([['member_id', 42], ['admin_sess', 1]]);
        $functions = $this->getMockBuilder(\stdClass::class)->addMethods(['random'])->getMock();
        $functions->method('random')->willReturn('new-token');
        ee()->setMock('session', $session);
        ee()->setMock('functions', $functions);
        $this->remember->create();

        $this->assertSame('new-token', $this->db->tables['remember_me'][0]['remember_me_id']);
        $this->assertSame(0, $this->db->tables['remember_me'][0]['mfa_verified_at']);
        $this->assertFalse($this->remember->is_mfa_verified(42));
    }

    /**
     * Member credential changes revoke trust through the real model event.
     *
     * @dataProvider memberChangeProvider
     * @param string $field The changed member property.
     * @param bool $revoked Whether the change must revoke remembered verification.
     * @return void
     */
    public function testMemberChangesRevokeMfa($field, $revoked)
    {
        $this->remember->remember_mfa(42);
        $query = $this->getMockBuilder(\stdClass::class)->addMethods(['filter', 'delete'])->getMock();
        $query->method('filter')->willReturnSelf();
        $facade = $this->getMockBuilder(\stdClass::class)->addMethods(['get'])->getMock();
        $facade->method('get')->willReturn($query);
        $member = $this->getMockBuilder(Member::class)->disableOriginalConstructor()
            ->onlyMethods(['saveFieldData', 'getModelFacade', 'notifyOfChanges', '__get'])->getMock();
        $member->method('getModelFacade')->willReturn($facade);
        $member->method('__get')->willReturnMap([['member_id', 42], ['password', 'new-password'], ['email', 'test@example.com']]);
        $memberId = new \ReflectionProperty(Member::class, 'member_id');
        \TestReflectionHelper::makePropertyAccessible($memberId);
        $memberId->setValue($member, 42);
        $member->onAfterUpdate([$field => 'old-value']);
        $this->assertSame(!$revoked, $this->remember->is_mfa_verified(42));
    }

    /**
     * Distinguish authentication changes from ordinary profile edits.
     *
     * @return array
     */
    public static function memberChangeProvider()
    {
        return [['password', true], ['enable_mfa', true], ['backup_mfa_code', true], ['unique_id', true], ['bio', false]];
    }

    /**
     * Avoid repeated schema changes and report a failed column addition.
     *
     * @dataProvider migrationProvider
     * @param bool $exists Whether the schema has already been migrated.
     * @param bool $added Whether the database accepts the new column.
     * @param bool $expected Whether the updater should report success.
     * @return void
     */
    public function testMigrationReportsItsActualResult($exists, $added, $expected)
    {
        require_once SYSPATH . 'ee/installer/updates/ud_7_05_29.php';
        $db = $this->getMockBuilder(\stdClass::class)->addMethods(['field_exists'])->getMock();
        $db->data_cache = ['field_names' => ['remember_me' => ['remember_me_id']]];
        $db->expects($this->once())->method('field_exists')->with('mfa_verified_at', 'remember_me')
            ->willReturnCallback(function () use ($db, $exists) {
                $this->assertArrayNotHasKey('remember_me', $db->data_cache['field_names']);
                return $exists;
            });
        $forge = $this->getMockBuilder(\stdClass::class)->addMethods(['add_column'])->getMock();
        $forge->expects($exists ? $this->never() : $this->once())->method('add_column')->willReturn($added);
        ee()->setMock('db', $db);
        ee()->setMock('smartforge', $forge);

        $updater = new \ExpressionEngine\Updater\Version_7_5_29\Updater();
        $this->assertSame($expected, $updater->do_update());
    }

    /**
     * Cover first-time, repeated, and failed migrations.
     *
     * @return array
     */
    public static function migrationProvider()
    {
        return [[false, true, true], [true, false, true], [false, false, false]];
    }
}

/**
 * Minimal in-memory Active Record adapter for remembered-login persistence.
 */
class RememberMfaDatabase
{
    public $tables = [];
    public $updates = [];
    private $where = [];
    private $changes = [];
    protected $affectedRows = 0;

    /**
     * Restrict the next write to matching fixture rows.
     *
     * @param string $key Column to compare.
     * @param mixed $value Required column value.
     * @return self
     */
    public function where($key, $value)
    {
        $this->where[$key] = $value;
        return $this;
    }

    /**
     * Accept ordering for fixtures already arranged oldest first.
     *
     * @param string $key Column to sort.
     * @param string $direction Sort direction.
     * @return self
     */
    public function order_by($key, $direction)
    {
        return $this;
    }

    /**
     * Stage values for the next fixture update.
     *
     * @param array $changes Column values to write.
     * @return self
     */
    public function set($changes)
    {
        $this->changes = $changes;
        return $this;
    }

    /**
     * Return fixture rows matching an equality query.
     *
     * @param string $table Fixture table name.
     * @param array $where Column values to match.
     * @return object
     */
    public function get_where($table, $where)
    {
        $rows = array_values(array_filter($this->tables[$table], function ($row) use ($where) {
            return !array_diff_assoc($where, $row);
        }));
        return new class($rows) {
            private $rows;

            /**
             * Capture the matching fixture rows.
             *
             * @param array $rows Query result rows.
             * @return void
             */
            public function __construct($rows)
            {
                $this->rows = $rows;
            }

            /**
             * Count matching rows.
             *
             * @return int
             */
            public function num_rows()
            {
                return count($this->rows);
            }

            /**
             * Return the first matching row.
             *
             * @return array
             */
            public function row_array()
            {
                return $this->rows[0];
            }

            /**
             * Return rows in the legacy object format.
             *
             * @return object[]
             */
            public function result()
            {
                return array_map(function ($row) { return (object) $row; }, $this->rows);
            }

            /**
             * Match the database result's release interface.
             *
             * @return void
             */
            public function free_result()
            {
            }
        };
    }

    /**
     * Apply a write to matching rows and record its query constraints.
     *
     * @param string $table Fixture table name.
     * @param array|null $changes Column values, or previously staged values.
     * @return void
     */
    public function update($table, $changes = null)
    {
        $this->affectedRows = 0;
        $changes = $changes === null ? $this->changes : $changes;
        $this->updates[] = ['table' => $table, 'where' => $this->where, 'changes' => $changes];
        foreach ($this->tables[$table] as &$row) {
            if (!array_diff_assoc($this->where, $row)) {
                $row = array_merge($row, $changes);
                $this->affectedRows++;
            }
        }
        $this->where = [];
        $this->changes = [];
    }

    public function affected_rows()
    {
        return $this->affectedRows;
    }

    /**
     * Remove only fixture rows matching the current query.
     *
     * @param string $table Fixture table name.
     * @return void
     */
    public function delete($table)
    {
        $where = $this->where;
        $except = $where['remember_me_id !='] ?? null;
        unset($where['remember_me_id !=']);
        $this->tables[$table] = array_values(array_filter($this->tables[$table], function ($row) use ($where, $except) {
            return (bool) array_diff_assoc($where, $row) || $row['remember_me_id'] === $except;
        }));
        $this->where = [];
    }
}

/**
 * Execute the approval SQL against real temporary tables instead of emulating
 * its predicates. Ordinary token operations use the shared fixture adapter.
 */
class RememberMfaSqlDatabase extends RememberMfaDatabase
{
    public $beforeQuery;
    public $failQuery = false;

    public function dbprefix($table)
    {
        return 'test_' . $table;
    }

    public function query($sql, $binds)
    {
        if ($this->beforeQuery) {
            $callback = $this->beforeQuery;
            $this->beforeQuery = null;
            $callback();
        }
        if ($this->failQuery) {
            return false;
        }

        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE test_remember_me (
            remember_me_id TEXT PRIMARY KEY, member_id INTEGER, site_id INTEGER,
            admin_sess INTEGER, user_agent TEXT, expiration INTEGER,
            last_refresh INTEGER, mfa_verified_at INTEGER
        )');
        $pdo->exec('CREATE TABLE test_sessions (
            session_id TEXT PRIMARY KEY, member_id INTEGER, mfa_flag TEXT
        )');
        foreach (['remember_me', 'sessions'] as $table) {
            foreach ($this->tables[$table] as $row) {
                $columns = implode(', ', array_keys($row));
                $placeholders = implode(', ', array_fill(0, count($row), '?'));
                $pdo->prepare("INSERT INTO test_{$table} ({$columns}) VALUES ({$placeholders})")
                    ->execute(array_values($row));
            }
        }

        $query = $pdo->prepare($sql);
        $result = $query->execute($binds);
        $this->affectedRows = $query->rowCount();
        $this->tables['sessions'] = $pdo->query('SELECT * FROM test_sessions')->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }
}
