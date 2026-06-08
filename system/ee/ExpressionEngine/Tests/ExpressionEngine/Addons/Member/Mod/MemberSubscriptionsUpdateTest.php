<?php

use PHPUnit\Framework\TestCase;

require_once PATH_ADDONS . 'member/mod.member.php';
require_once PATH_ADDONS . 'member/mod.member_subscriptions.php';

class MemberSubscriptionsUpdateInputMock
{
    private $post;

    public function __construct(array $post)
    {
        $this->post = $post;
    }

    public function post($key)
    {
        return $this->post[$key] ?? false;
    }
}

class MemberSubscriptionsUpdateLoadMock
{
    public function library($name)
    {
        return;
    }
}

class MemberSubscriptionsUpdateSessionMock
{
    private $memberId;

    public function __construct($memberId)
    {
        $this->memberId = $memberId;
    }

    public function userdata($key, $default = false)
    {
        if ($key === 'member_id') {
            return $this->memberId;
        }

        return $default;
    }
}

class MemberSubscriptionsUpdateDbMock
{
    public $queries = [];

    public function query($sql, $binds = false)
    {
        $this->queries[] = [
            'sql' => $sql,
            'binds' => $binds,
        ];

        return true;
    }
}

class MemberSubscriptionsUpdateHarness extends Member_subscriptions
{
    public function __construct()
    {
    }

    public function _load_element($which)
    {
        return '{lang:heading}:{lang:message}';
    }
}

class MemberSubscriptionsUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
    }

    protected function tearDown(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
    }

    public function testUpdateSubscriptionsBindsForumSubscriptionIds()
    {
        $db = new MemberSubscriptionsUpdateDbMock();

        ee()->setMock('db', $db);
        ee()->setMock('load', new MemberSubscriptionsUpdateLoadMock());
        ee()->setMock('session', new MemberSubscriptionsUpdateSessionMock('42'));
        ee()->setMock('input', new MemberSubscriptionsUpdateInputMock([
            'toggle' => [
                "f99999' OR SLEEP(2)-- -",
            ],
        ]));

        $memberSubscriptions = new MemberSubscriptionsUpdateHarness();
        $result = $memberSubscriptions->update_subscriptions();

        $this->assertSame('subscriptions:subscriptions_removed', $result);
        $this->assertCount(1, $db->queries);
        $this->assertSame(
            'DELETE FROM exp_forum_subscriptions WHERE topic_id = ? AND member_id = ?',
            $db->queries[0]['sql']
        );
        $this->assertSame([99999, 42], $db->queries[0]['binds']);
        $this->assertStringNotContainsString('SLEEP', $db->queries[0]['sql']);
    }
}
