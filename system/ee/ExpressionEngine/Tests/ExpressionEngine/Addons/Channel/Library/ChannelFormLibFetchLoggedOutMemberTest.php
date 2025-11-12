<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibFetchLoggedOutMemberTest extends ChannelFormLibTestBase
{
    public function testFetchLoggedOutMemberReturnsEarlyWhenUserIsLoggedIn()
    {
        // Set up logged in user
        ee()->session->userdata['member_id'] = 5;

        // Mock the sanitize_int method
        $this->channelFormLib = $this->getMockBuilder('Channel_form_lib')
            ->onlyMethods(['sanitize_int'])
            ->getMock();

        $this->channelFormLib->expects($this->never())
            ->method('sanitize_int');

        // Call the method
        $this->channelFormLib->fetch_logged_out_member();

        // Should not set logged_out_member_id
        $this->assertNull($this->channelFormLib->logged_out_member_id);
    }

    public function testFetchLoggedOutMemberReturnsEarlyWhenAlreadySet()
    {
        // Create mock first
        $this->channelFormLib = $this->getMockBuilder('Channel_form_lib')
            ->onlyMethods(['sanitize_int'])
            ->getMock();

        // Set existing logged_out_member_id on the mock
        $this->channelFormLib->logged_out_member_id = 10;

        $this->channelFormLib->expects($this->never())
            ->method('sanitize_int');

        // Call the method
        $this->channelFormLib->fetch_logged_out_member();

        // Should not change existing logged_out_member_id
        $this->assertEquals(10, $this->channelFormLib->logged_out_member_id);
    }

    public function testFetchLoggedOutMemberUsesDefaultAuthorFromSettings()
    {
        // Set up guest user (not logged in)
        ee()->session->userdata['member_id'] = 0;

        // Set up channel
        $mockChannel = $this->createMockChannel(['channel_id' => 3]);
        $this->channelFormLib->channel = $mockChannel;
        $this->channelFormLib->site_id = 1;

        // Set up settings to allow guest posts with default author
        $this->channelFormLib->settings = [
            'allow_guest_posts' => [
                1 => [3 => 'y']
            ],
            'default_author' => [
                1 => [3 => 7]
            ]
        ];

        // Mock the Model to return a valid member
        $mockMember = $this->createMockMember(['member_id' => 7]);
        $this->setMock('Model', new class($mockMember) {
            private $member;
            public function __construct($member) { $this->member = $member; }
            public function get($model, $id) {
                if ($model === 'Member' && $id === 7) {
                    return new class($this->member) {
                        private $member;
                        public function __construct($member) { $this->member = $member; }
                        public function with($relation) { return $this; }
                        public function first() { return $this->member; }
                    };
                }
            }
        });

        // Call the method without providing member_id
        $this->channelFormLib->fetch_logged_out_member();

        // Should set logged_out_member_id to the default author
        $this->assertEquals(7, $this->getProtectedPropertyValue('logged_out_member_id'));
        $this->assertEquals(1, $this->getProtectedPropertyValue('logged_out_group_id'));
    }

    public function testFetchLoggedOutMemberWithValidProvidedMemberId()
    {
        // Set up guest user (not logged in)
        ee()->session->userdata['member_id'] = 0;

        // Set up channel
        $mockChannel = $this->createMockChannel(['channel_id' => 3]);
        $this->channelFormLib->channel = $mockChannel;
        $this->channelFormLib->site_id = 1;

        // Mock the Model to return a valid member
        $mockMember = $this->createMockMember(['member_id' => 15]);
        $this->setMock('Model', new class($mockMember) {
            private $member;
            public function __construct($member) { $this->member = $member; }
            public function get($model, $id) {
                if ($model === 'Member' && $id === 15) {
                    return new class($this->member) {
                        private $member;
                        public function __construct($member) { $this->member = $member; }
                        public function with($relation) { return $this; }
                        public function first() { return $this->member; }
                    };
                }
            }
        });

        // Call the method with a specific member_id
        $this->channelFormLib->fetch_logged_out_member(15);

        // Should set logged_out_member_id to the provided value
        $this->assertEquals(15, $this->getProtectedPropertyValue('logged_out_member_id'));
        $this->assertEquals(1, $this->getProtectedPropertyValue('logged_out_group_id'));
    }

    public function testFetchLoggedOutMemberThrowsExceptionForInvalidMemberId()
    {
        // Set up guest user (not logged in)
        ee()->session->userdata['member_id'] = 0;

        // Mock the Model to return null (member not found)
        $this->setMock('Model', new class {
            public function get($model, $id) {
                return new class {
                    public function with($relation) { return $this; }
                    public function first() { return null; }
                };
            }
        });

        $this->expectException('Channel_form_exception');
        $this->expectExceptionMessage('channel_form_invalid_guest_member_id');

        // Call the method with invalid member_id
        $this->channelFormLib->fetch_logged_out_member(999);
    }

    public function testFetchLoggedOutMemberHandlesMetaSiteId()
    {
        // Set up guest user (not logged in)
        ee()->session->userdata['member_id'] = 0;

        // Set up channel
        $mockChannel = $this->createMockChannel(['channel_id' => 5]);
        $this->channelFormLib->channel = $mockChannel;
        $this->channelFormLib->site_id = 1;

        // Set up _meta with different site_id
        $this->setProtectedProperty('_meta', ['site_id' => 2]);

        // Set up settings for site_id 2
        $this->channelFormLib->settings = [
            'allow_guest_posts' => [
                2 => [5 => 'y']
            ],
            'default_author' => [
                2 => [5 => 8]
            ]
        ];

        // Mock the Model to return a valid member
        $mockMember = $this->createMockMember(['member_id' => 8]);
        $this->setMock('Model', new class($mockMember) {
            private $member;
            public function __construct($member) { $this->member = $member; }
            public function get($model, $id) {
                if ($model === 'Member' && $id === 8) {
                    return new class($this->member) {
                        private $member;
                        public function __construct($member) { $this->member = $member; }
                        public function with($relation) { return $this; }
                        public function first() { return $this->member; }
                    };
                }
            }
        });

        // Call the method without providing member_id
        $this->channelFormLib->fetch_logged_out_member();

        // Should use site_id from _meta
        $this->assertEquals(8, $this->getProtectedPropertyValue('logged_out_member_id'));
    }

    public function testFetchLoggedOutMemberHandlesInvalidInputSanitization()
    {
        // Set up guest user (not logged in)
        ee()->session->userdata['member_id'] = 0;

        // Mock sanitize_int to return 0 for invalid input
        $this->channelFormLib = $this->getMockBuilder('Channel_form_lib')
            ->onlyMethods(['sanitize_int'])
            ->getMock();

        $this->channelFormLib->method('sanitize_int')
            ->with('invalid')
            ->willReturn(0);

        // Call the method with invalid input
        $this->channelFormLib->fetch_logged_out_member('invalid');

        // Should not set logged_out_member_id when sanitized to 0
        $this->assertNull($this->channelFormLib->logged_out_member_id);
    }

    public function testFetchLoggedOutMemberDoesNotUseDefaultAuthorWhenGuestPostsDisabled()
    {
        // Set up guest user (not logged in)
        ee()->session->userdata['member_id'] = 0;

        // Set up channel
        $mockChannel = $this->createMockChannel(['channel_id' => 3]);
        $this->channelFormLib->channel = $mockChannel;
        $this->channelFormLib->site_id = 1;

        // Set up settings to NOT allow guest posts
        $this->channelFormLib->settings = [
            'allow_guest_posts' => [
                1 => [3 => false] // Use false instead of 'n' to make empty() return true
            ],
            'default_author' => [
                1 => [3 => 7]
            ]
        ];

        // Since guest posts are disabled, no member ID should be processed
        // The method should return early without any Model calls
        $this->channelFormLib->fetch_logged_out_member();

        // Should not set logged_out_member_id since guest posts are disabled
        $this->assertNull($this->channelFormLib->logged_out_member_id);
    }

    public function testFetchLoggedOutMemberDoesNotUseDefaultAuthorWhenNoChannel()
    {
        // Set up guest user (not logged in)
        ee()->session->userdata['member_id'] = 0;

        // No channel set
        $this->channelFormLib->channel = null;
        $this->channelFormLib->site_id = 1;

        // Set up settings
        $this->channelFormLib->settings = [
            'allow_guest_posts' => [
                1 => [3 => 'y']
            ],
            'default_author' => [
                1 => [3 => 7]
            ]
        ];

        // Mock the channel method to return null when no channel is set
        $this->channelFormLib = $this->getMockBuilder('Channel_form_lib')
            ->onlyMethods(['channel'])
            ->getMock();

        $this->channelFormLib->method('channel')
            ->willReturn(null);

        // Call the method without providing member_id
        $this->channelFormLib->fetch_logged_out_member();

        // Should not set logged_out_member_id since no channel is set
        $this->assertNull($this->channelFormLib->logged_out_member_id);
    }

    public function testFetchLoggedOutMemberHandlesMalformedSettings()
    {
        // Set up guest user (not logged in)
        ee()->session->userdata['member_id'] = 0;

        // Set up channel
        $mockChannel = $this->createMockChannel(['channel_id' => 3]);
        $this->channelFormLib->channel = $mockChannel;
        $this->channelFormLib->site_id = 1;

        // Set up malformed settings (missing default_author)
        $this->channelFormLib->settings = [
            'allow_guest_posts' => [
                1 => [3 => 'y']
            ]
            // missing default_author
        ];

        // Call the method without providing member_id
        $this->channelFormLib->fetch_logged_out_member();

        // Should not set logged_out_member_id since default_author is missing
        $this->assertNull($this->getProtectedPropertyValue('logged_out_member_id'));
    }
}
