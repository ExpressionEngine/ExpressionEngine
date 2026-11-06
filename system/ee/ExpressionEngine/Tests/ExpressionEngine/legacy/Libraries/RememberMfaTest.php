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
    private $affectedRows = 0;

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
