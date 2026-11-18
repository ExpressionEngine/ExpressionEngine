<?php

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Libraries;

use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/installer/libraries/Installer_Session.php';

class InstallerSessionTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testCacheReturnsDefaultWhenValueDoesNotExist()
    {
        $session = new \Installer_Session();

        $this->assertSame('fallback', $session->cache('installer', 'missing', 'fallback'));
    }

    public function testSetCacheCreatesBucketsAndAllowsOverwriteWithoutReinitializing()
    {
        $session = new \Installer_Session();

        $return = $session->set_cache('installer', 'step', 'one');
        $session->set_cache('installer', 'step', 'two');
        $session->set_cache('wizard', 'stage', 'ready');

        $this->assertSame($session, $return);
        $this->assertSame('two', $session->cache('installer', 'step'));
        $this->assertSame('ready', $session->cache('wizard', 'stage'));
    }

    public function testUserdataReturnsDefaultForMissingKeyAndValueForExistingKey()
    {
        $session = new \Installer_Session();
        $session->userdata = ['member_id' => 7];

        $this->assertSame('unknown', $session->userdata('group_id', 'unknown'));
        $this->assertSame(7, $session->userdata('member_id', 0));
    }

    public function testAllUserdataReturnsCurrentUserdataArray()
    {
        $session = new \Installer_Session();
        $session->userdata = ['member_id' => 12, 'group_id' => 1];

        $this->assertSame(['member_id' => 12, 'group_id' => 1], $session->all_userdata());
    }

    public function testGetMemberReturnsFirstMemberFromModelService()
    {
        $member = (object) ['member_id' => 1, 'screen_name' => 'Installer'];
        $modelMock = new class($member) {
            public $calls = [];
            private $member;

            public function __construct($member)
            {
                $this->member = $member;
            }

            public function get($model, $id)
            {
                $this->calls[] = [$model, $id];

                return new class($this->member) {
                    private $member;

                    public function __construct($member)
                    {
                        $this->member = $member;
                    }

                    public function first()
                    {
                        return $this->member;
                    }
                };
            }
        };

        ee()->setMock('Model', $modelMock);

        $session = new \Installer_Session();
        $result = $session->getMember();

        $this->assertSame($member, $result);
        $this->assertSame([['Member', 1]], $modelMock->calls);
    }

    public function testSessionIdAlwaysReturnsZero()
    {
        $session = new \Installer_Session();

        $this->assertSame(0, $session->session_id());
        $this->assertSame(0, $session->session_id('ignored'));
    }

    public function testSetSessionCookiesReturnsNull()
    {
        $session = new \Installer_Session();

        $this->assertNull($session->setSessionCookies());
    }
}
