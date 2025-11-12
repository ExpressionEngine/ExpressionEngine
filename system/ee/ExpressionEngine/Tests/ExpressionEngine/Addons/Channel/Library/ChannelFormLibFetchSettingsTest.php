<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibFetchSettingsTest extends ChannelFormLibTestBase
{
    public function testFetchSettingsReturnsEarlyWhenAlreadyLoaded()
    {
        // Create mock first
        $this->channelFormLib = $this->getMockBuilder('Channel_form_lib')
            ->onlyMethods(['bool_string'])
            ->getMock();

        // Set existing settings on the mock
        $this->channelFormLib->settings = [
            'allow_guest_posts' => [1 => [2 => 'y']]
        ];

        // Mock DB to ensure it's not called
        $mockDb = new class {
            public function get($table) {
                throw new Exception('DB should not be called when settings are already loaded');
            }
        };
        $this->setMock('db', $mockDb);

        $this->channelFormLib->expects($this->never())
            ->method('bool_string');

        // Call the method
        $this->channelFormLib->fetch_settings();

        // Should not modify existing settings
        $this->assertEquals(['allow_guest_posts' => [1 => [2 => 'y']]], $this->channelFormLib->settings);
    }

    public function testFetchSettingsHandlesEmptyDatabaseResults()
    {
        // Mock DB to return empty results
        $mockDbResult = new class {
            public function result_array() { return []; }
        };

        $mockDb = new class($mockDbResult) {
            private $result;
            public function __construct($result) { $this->result = $result; }
            public function get($table) { return $this->result; }
        };
        $this->setMock('db', $mockDb);

        // Call the method
        $this->channelFormLib->fetch_settings();

        // Should initialize empty settings array
        $this->assertEquals([], $this->channelFormLib->settings);
    }

    public function testFetchSettingsTransformsDataIntoLegacyFormat()
    {
        // Mock DB to return settings data
        $mockDbResult = new class {
            public function result_array() {
                return [
                    [
                        'channel_form_settings_id' => 1,
                        'site_id' => 1,
                        'channel_id' => 2,
                        'allow_guest_posts' => '1',
                        'default_author' => '5',
                        'default_status' => 'open'
                    ]
                ];
            }
        };

        $mockDb = new class($mockDbResult) {
            private $result;
            public function __construct($result) { $this->result = $result; }
            public function get($table) { return $this->result; }
        };
        $this->setMock('db', $mockDb);

        // Mock bool_string for allow_guest_posts
        $this->channelFormLib = $this->getMockBuilder('Channel_form_lib')
            ->onlyMethods(['bool_string'])
            ->getMock();

        $this->channelFormLib->method('bool_string')
            ->with('1')
            ->willReturn('y');

        // Call the method
        $this->channelFormLib->fetch_settings();

        // Should transform data into legacy format
        $expected = [
            'allow_guest_posts' => [1 => [2 => 'y']],
            'default_author' => [1 => [2 => '5']],
            'default_status' => [1 => [2 => 'open']]
        ];
        $this->assertEquals($expected, $this->channelFormLib->settings);
    }

    public function testFetchSettingsHandlesMultipleRows()
    {
        // Mock DB to return multiple settings rows
        $mockDbResult = new class {
            public function result_array() {
                return [
                    [
                        'channel_form_settings_id' => 1,
                        'site_id' => 1,
                        'channel_id' => 2,
                        'allow_guest_posts' => '1',
                        'default_author' => '5'
                    ],
                    [
                        'channel_form_settings_id' => 2,
                        'site_id' => 1,
                        'channel_id' => 3,
                        'allow_guest_posts' => '0',
                        'default_author' => '7'
                    ],
                    [
                        'channel_form_settings_id' => 3,
                        'site_id' => 2,
                        'channel_id' => 2,
                        'allow_guest_posts' => '1',
                        'default_author' => '10'
                    ]
                ];
            }
        };

        $mockDb = new class($mockDbResult) {
            private $result;
            public function __construct($result) { $this->result = $result; }
            public function get($table) { return $this->result; }
        };
        $this->setMock('db', $mockDb);

        // Mock bool_string
        $this->channelFormLib = $this->getMockBuilder('Channel_form_lib')
            ->onlyMethods(['bool_string'])
            ->getMock();

        $this->channelFormLib->method('bool_string')
            ->willReturnCallback(function($value) {
                return $value === '1' ? 'y' : 'n';
            });

        // Call the method
        $this->channelFormLib->fetch_settings();

        // Should handle multiple rows correctly
        $expected = [
            'allow_guest_posts' => [
                1 => [2 => 'y', 3 => 'n'],
                2 => [2 => 'y']
            ],
            'default_author' => [
                1 => [2 => '5', 3 => '7'],
                2 => [2 => '10']
            ]
        ];
        $this->assertEquals($expected, $this->channelFormLib->settings);
    }

    public function testFetchSettingsHandlesAllowGuestPostsBooleanConversion()
    {
        // Mock DB to return allow_guest_posts data
        $mockDbResult = new class {
            public function result_array() {
                return [
                    [
                        'channel_form_settings_id' => 1,
                        'site_id' => 1,
                        'channel_id' => 2,
                        'allow_guest_posts' => '1'
                    ],
                    [
                        'channel_form_settings_id' => 2,
                        'site_id' => 1,
                        'channel_id' => 3,
                        'allow_guest_posts' => '0'
                    ]
                ];
            }
        };

        $mockDb = new class($mockDbResult) {
            private $result;
            public function __construct($result) { $this->result = $result; }
            public function get($table) { return $this->result; }
        };
        $this->setMock('db', $mockDb);

        // Mock bool_string
        $this->channelFormLib = $this->getMockBuilder('Channel_form_lib')
            ->onlyMethods(['bool_string'])
            ->getMock();

        $this->channelFormLib->method('bool_string')
            ->willReturnCallback(function($value) {
                return $value === '1' ? 'y' : 'n';
            });

        // Call the method
        $this->channelFormLib->fetch_settings();

        // Should convert allow_guest_posts values using bool_string
        $this->assertEquals('y', $this->channelFormLib->settings['allow_guest_posts'][1][2]);
        $this->assertEquals('n', $this->channelFormLib->settings['allow_guest_posts'][1][3]);
    }

    public function testFetchSettingsPreservesNonGuestPostsColumns()
    {
        // Mock DB to return various column types
        $mockDbResult = new class {
            public function result_array() {
                return [
                    [
                        'channel_form_settings_id' => 1,
                        'site_id' => 1,
                        'channel_id' => 2,
                        'default_author' => '5',
                        'default_status' => 'draft',
                        'custom_field' => 'some_value'
                    ]
                ];
            }
        };

        $mockDb = new class($mockDbResult) {
            private $result;
            public function __construct($result) { $this->result = $result; }
            public function get($table) { return $this->result; }
        };
        $this->setMock('db', $mockDb);

        // Mock bool_string (should not be called for non-guest-posts columns)
        $this->channelFormLib = $this->getMockBuilder('Channel_form_lib')
            ->onlyMethods(['bool_string'])
            ->getMock();

        $this->channelFormLib->expects($this->never())
            ->method('bool_string');

        // Call the method
        $this->channelFormLib->fetch_settings();

        // Should preserve original values for non-allow_guest_posts columns
        $this->assertEquals('5', $this->channelFormLib->settings['default_author'][1][2]);
        $this->assertEquals('draft', $this->channelFormLib->settings['default_status'][1][2]);
        $this->assertEquals('some_value', $this->channelFormLib->settings['custom_field'][1][2]);
    }



    public function testFetchSettingsHandlesDatabaseError()
    {
        // Mock DB to throw an exception
        $mockDb = new class {
            public function get($table) {
                throw new Exception('Database connection error');
            }
        };
        $this->setMock('db', $mockDb);

        // Should handle database errors gracefully
        $this->expectException('Exception');
        $this->expectExceptionMessage('Database connection error');

        $this->channelFormLib->fetch_settings();
    }

    public function testFetchSettingsHandlesEmptyColumns()
    {
        // Mock DB to return row with no data columns (only IDs)
        $mockDbResult = new class {
            public function result_array() {
                return [
                    [
                        'channel_form_settings_id' => 1,
                        'site_id' => 1,
                        'channel_id' => 2
                        // no other columns
                    ]
                ];
            }
        };

        $mockDb = new class($mockDbResult) {
            private $result;
            public function __construct($result) { $this->result = $result; }
            public function get($table) { return $this->result; }
        };
        $this->setMock('db', $mockDb);

        // Call the method
        $this->channelFormLib->fetch_settings();

        // Should handle empty columns gracefully
        $this->assertEquals([], $this->channelFormLib->settings);
    }
}
