<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once __DIR__ . '/../ChannelTestBase.php';

/**
 * Tests for Channel_form_session class
 *
 * Note: This class appears to be unused in the current codebase but is kept
 * for potential future use in guest user session handling.
 */
class ChannelFormSessionTest extends ChannelTestBase
{
    protected $channelFormSession;

    protected function setUp(): void
    {
        parent::setUp();

        // Check if EE_Session class exists, skip tests if it doesn't
        if (!class_exists('EE_Session')) {
            $this->markTestSkipped('EE_Session class not available in test environment');
            return;
        }

        // Include the Channel_form_session class
        require_once PATH_ADDONS . 'channel/libraries/channel_form/Channel_form_session.php';
    }

    public function testConstructorInitializesPropertiesWithValidConfig()
    {
        // Set up ee()->session mock with getMember method
        $this->setMock('session', new class {
            public function getMember() {
                return new class {
                    public function getAssignedChannels() {
                        return new class {
                            public function getDictionary($key, $value) {
                                return ['channel_1' => 'Channel One', 'channel_2' => 'Channel Two'];
                            }
                        };
                    }
                };
            }
        });

        // Mock session object with getMember method
        $mockSession = new class {
            public $test_property = 'test_value';
            public function userdata($key) {
                return 'session_' . $key;
            }
            public function getMember() {
                return new class {
                    public function getAssignedChannels() {
                        return new class {
                            public function getDictionary($key, $value) {
                                return ['channel_1' => 'Channel One', 'channel_2' => 'Channel Two'];
                            }
                        };
                    }
                };
            }
        };

        $config = [
            'session_object' => $mockSession,
            'logged_out_member_id' => 123,
            'logged_out_group_id' => 5
        ];

        $session = new Channel_form_session($config);

        // Test that properties are set correctly
        $this->assertEquals(123, $session->logged_out_member_id);
        $this->assertEquals(5, $session->logged_out_group_id);
        $this->assertSame($mockSession, $session->session_object);

        // Test that session object properties are copied
        $this->assertEquals('test_value', $session->test_property);
    }

    public function testConstructorHandlesNullConfig()
    {
        // Set up ee()->session mock
        $this->setMock('session', new class {
            public function getMember() {
                return new class {
                    public function getAssignedChannels() {
                        return new class {
                            public function getDictionary($key, $value) {
                                return [];
                            }
                        };
                    }
                };
            }
        });

        $config = [];

        $session = new Channel_form_session($config);

        // Test that properties are null when not provided
        $this->assertNull($session->logged_out_member_id);
        $this->assertNull($session->logged_out_group_id);
        $this->assertNull($session->session_object);
    }

    public function testConstructorSetsUserdataForLoggedOutMember()
    {
        // Mock session with getMember method
        $this->setMock('session', new class {
            public function getMember() {
                return new class {
                    public function getAssignedChannels() {
                        return new class {
                            public function getDictionary($key, $value) {
                                return ['channel_1' => 'Channel One', 'channel_2' => 'Channel Two'];
                            }
                        };
                    }
                };
            }
        });

        $config = [
            'logged_out_member_id' => 456,
            'logged_out_group_id' => 10
        ];

        $session = new Channel_form_session($config);

        // Test that userdata is set correctly
        $this->assertEquals(456, $session->userdata['member_id']);
        $this->assertEquals(10, $session->userdata['group_id']);
        $this->assertArrayHasKey('assigned_channels', $session->userdata);
    }

    public function testUserdataReturnsLoggedOutMemberId()
    {
        // Set up ee()->session mock
        $this->setMock('session', new class {
            public function getMember() {
                return new class {
                    public function getAssignedChannels() {
                        return new class {
                            public function getDictionary($key, $value) {
                                return [];
                            }
                        };
                    }
                };
            }
        });

        $mockSession = new class {
            public function userdata($key) {
                return 'fallback_' . $key;
            }
            public function getMember() {
                return new class {
                    public function getAssignedChannels() {
                        return new class {
                            public function getDictionary($key, $value) {
                                return [];
                            }
                        };
                    }
                };
            }
        };

        $config = [
            'session_object' => $mockSession,
            'logged_out_member_id' => 789
        ];

        $session = new Channel_form_session($config);

        // Test that logged_out_member_id is returned for member_id key
        $this->assertEquals(789, $session->userdata('member_id'));
    }

    public function testUserdataReturnsLoggedOutGroupId()
    {
        // Set up ee()->session mock
        $this->setMock('session', new class {
            public function getMember() {
                return new class {
                    public function getAssignedChannels() {
                        return new class {
                            public function getDictionary($key, $value) {
                                return [];
                            }
                        };
                    }
                };
            }
        });

        $mockSession = new class {
            public function userdata($key) {
                return 'fallback_' . $key;
            }
            public function getMember() {
                return new class {
                    public function getAssignedChannels() {
                        return new class {
                            public function getDictionary($key, $value) {
                                return [];
                            }
                        };
                    }
                };
            }
        };

        $config = [
            'session_object' => $mockSession,
            'logged_out_group_id' => 15
        ];

        $session = new Channel_form_session($config);

        // Test that logged_out_group_id is returned for group_id key
        $this->assertEquals(15, $session->userdata('group_id'));
    }

    public function testUserdataFallsBackToSessionObject()
    {
        // Set up ee()->session mock
        $this->setMock('session', new class {
            public function getMember() {
                return new class {
                    public function getAssignedChannels() {
                        return new class {
                            public function getDictionary($key, $value) {
                                return [];
                            }
                        };
                    }
                };
            }
        });

        $mockSession = new class {
            public function userdata($key) {
                return 'fallback_' . $key;
            }
            public function getMember() {
                return new class {
                    public function getAssignedChannels() {
                        return new class {
                            public function getDictionary($key, $value) {
                                return [];
                            }
                        };
                    }
                };
            }
        };

        $config = [
            'session_object' => $mockSession,
            'logged_out_member_id' => null,
            'logged_out_group_id' => null
        ];

        $session = new Channel_form_session($config);

        // Test that fallback works for other keys
        $this->assertEquals('fallback_email', $session->userdata('email'));
        $this->assertEquals('fallback_username', $session->userdata('username'));
    }

    public function testUserdataHandlesNullSessionObject()
    {
        // Set up ee()->session mock
        $this->setMock('session', new class {
            public function getMember() {
                return new class {
                    public function getAssignedChannels() {
                        return new class {
                            public function getDictionary($key, $value) {
                                return [];
                            }
                        };
                    }
                };
            }
        });

        $config = [
            'session_object' => null,
            'logged_out_member_id' => 111,
            'logged_out_group_id' => 222
        ];

        $session = new Channel_form_session($config);

        // Test that logged out values are still returned even with null session
        $this->assertEquals(111, $session->userdata('member_id'));
        $this->assertEquals(222, $session->userdata('group_id'));
    }

    public function testConstructorHandlesNonObjectSession()
    {
        // Set up ee()->session mock
        $this->setMock('session', new class {
            public function getMember() {
                return new class {
                    public function getAssignedChannels() {
                        return new class {
                            public function getDictionary($key, $value) {
                                return [];
                            }
                        };
                    }
                };
            }
        });

        $config = [
            'session_object' => 'not_an_object',
            'logged_out_member_id' => 333
        ];

        $session = new Channel_form_session($config);

        // Should not crash and should still set logged out member id
        $this->assertEquals(333, $session->userdata('member_id'));
    }
}
