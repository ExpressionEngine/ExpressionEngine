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

require_once __DIR__ . '/../../../eeObjectMock.php';
require_once SYSPATH . 'ee/legacy/libraries/Session.php';

use PHPUnit\Framework\TestCase;

if (! defined('REQ')) {
    define('REQ', 'CP');
}

class SessionMemberQueryFallbackShim extends \EE_Session
{
    public function callDoMemberQuery()
    {
        return $this->_do_member_query();
    }

    public function setMemberModelForTest($memberModel): void
    {
        $property = new \ReflectionProperty(\EE_Session::class, 'member_model');
        $property->setAccessible(true);
        $property->setValue($this, $memberModel);
    }
}

class SessionMemberQueryFallbackDbMock
{
    public $getCalls = 0;

    private $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function from($table)
    {
        return $this;
    }

    public function where($field, $value = null, $escape = true)
    {
        return $this;
    }

    public function get($table = null)
    {
        $this->getCalls++;

        return array_shift($this->results);
    }
}

class SessionMemberQueryFallbackResultStub
{
    private $numRows;

    public function __construct(int $numRows)
    {
        $this->numRows = $numRows;
    }

    public function num_rows()
    {
        return $this->numRows;
    }
}

class SessionMemberQueryFallbackMemberModelStub
{
    public $member_id;

    private $roleSetting;

    public function __construct(int $memberId, $roleSetting)
    {
        $this->member_id = $memberId;
        $this->roleSetting = $roleSetting;
    }

    public function getRoleSettingsForSite($siteId, $requireCpAccess = true)
    {
        return $this->roleSetting;
    }
}

class SessionMemberQueryFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        ee()->config->resetConfig();
        ee()->config->setItem('site_id', 2);
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
        ee()->config->resetConfig();
    }

    public function testFallbackReturnsEmptyArrayWhenNoRoleSettingsCanBeResolved()
    {
        $db = new SessionMemberQueryFallbackDbMock([
            new SessionMemberQueryFallbackResultStub(0),
            new SessionMemberQueryFallbackResultStub(1),
        ]);
        ee()->setMock('db', $db);

        $session = $this->newSessionWithoutConstructor();
        $session->sdata = ['member_id' => 42];
        $session->validation = 's';
        $session->setMemberModelForTest(new SessionMemberQueryFallbackMemberModelStub(42, null));

        $result = $session->callDoMemberQuery();

        $this->assertSame([], $result);
        $this->assertSame(2, $db->getCalls);
    }

    public function testFallbackReturnsMemberQueryWhenRoleSettingsResolve()
    {
        $fallbackResult = new SessionMemberQueryFallbackResultStub(1);

        $db = new SessionMemberQueryFallbackDbMock([
            new SessionMemberQueryFallbackResultStub(0),
            $fallbackResult,
        ]);
        ee()->setMock('db', $db);

        $session = $this->newSessionWithoutConstructor();
        $session->sdata = ['member_id' => 42];
        $session->validation = 's';
        $session->setMemberModelForTest(
            new SessionMemberQueryFallbackMemberModelStub(42, new \stdClass())
        );

        $result = $session->callDoMemberQuery();

        $this->assertSame($fallbackResult, $result);
        $this->assertSame(2, $db->getCalls);
    }

    private function newSessionWithoutConstructor(): SessionMemberQueryFallbackShim
    {
        return (new \ReflectionClass(SessionMemberQueryFallbackShim::class))
            ->newInstanceWithoutConstructor();
    }
}

// EOF
