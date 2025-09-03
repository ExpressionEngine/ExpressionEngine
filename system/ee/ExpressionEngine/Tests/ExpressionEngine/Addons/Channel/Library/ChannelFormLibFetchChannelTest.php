<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibFetchChannelTest extends ChannelFormLibTestBase
{
    public function testFetchChannelByChannelId()
    {
        $mockChannel = $this->createMockChannel(['channel_id' => 5, 'channel_name' => 'test_channel']);

        // Mock the Model query
        $mockQuery = new class($mockChannel) {
            private $channel;
            public function __construct($channel) { $this->channel = $channel; }
            public function with($relation) { return $this; }
            public function filter($field, $value) { return $this; }
            public function all() { return $this; }
            public function first() { return $this->channel; }
        };

        // Mock ee('Model')
        $this->setMock('Model', new class($mockQuery) {
            private $query;
            public function __construct($query) { $this->query = $query; }
            public function get($model) {
                return $this->query;
            }
        });

        $this->channelFormLib->fetch_channel(5);

        $this->assertEquals(5, $this->channelFormLib->channel('channel_id'));
        $this->assertEquals('test_channel', $this->channelFormLib->channel('channel_name'));
    }

    public function testFetchChannelByChannelName()
    {
        $mockChannel = $this->createMockChannel(['channel_id' => 7, 'channel_name' => 'my_channel']);

        // Mock the Model query
        $mockQuery = new class($mockChannel) {
            private $channel;
            private $query;
            public function __construct($channel) { $this->query = $this; $this->channel = $channel; }
            public function with($relation) { return $this; }
            public function filter($field, $value) { return $this; }
            public function all() { return $this; }
            public function first() { return $this->channel; }
        };

        // Mock ee('Model')
        $this->setMock('Model', new class($mockQuery) {
            private $query;
            public function __construct($query) { $this->query = $query; }
            public function get($model) {
                return $this->query;
            }
        });

        $this->channelFormLib->fetch_channel(null, 'my_channel');

        $this->assertEquals(7, $this->channelFormLib->channel('channel_id'));
        $this->assertEquals('my_channel', $this->channelFormLib->channel('channel_name'));
    }

    public function testFetchChannelThrowsExceptionWhenNoChannelFound()
    {
        // Mock the Model query to return null
        $mockQuery = new class {
            public function with($relation) { return $this; }
            public function filter($field, $value) { return $this; }
            public function all() { return $this; }
            public function first() { return null; }
        };

        // Mock ee('Model')
        $this->setMock('Model', new class($mockQuery) {
            private $query;
            public function __construct($query) { $this->query = $query; }
            public function get($model) {
                return $this->query;
            }
        });

        $this->expectException('Channel_form_exception');
        $this->expectExceptionMessage('channel_form_unknown_channel');

        $this->channelFormLib->fetch_channel(999);
    }

    public function testFetchChannelThrowsExceptionWhenNoParameters()
    {
        // Mock the Model query to return null
        $mockQuery = new class {
            public function with($relation) { return $this; }
            public function filter($field, $value) { return $this; }
            public function all() { return $this; }
            public function first() { return null; }
        };

        // Mock ee('Model')
        $this->setMock('Model', new class($mockQuery) {
            private $query;
            public function __construct($query) { $this->query = $query; }
            public function get($model) {
                return $this->query;
            }
        });

        $this->expectException('Channel_form_exception');
        $this->expectExceptionMessage('channel_form_no_channel');

        $this->channelFormLib->fetch_channel(null, null);
    }

    public function testFetchChannelSetsTemplateParameter()
    {
        $mockChannel = $this->createMockChannel(['channel_id' => 3, 'channel_name' => 'template_channel']);

        // Mock the Model query
        $mockQuery = new class($mockChannel) {
            private $channel;
            public function __construct($channel) { $this->channel = $channel; }
            public function with($relation) { return $this; }
            public function filter($field, $value) { return $this; }
            public function all() { return $this; }
            public function first() { return $this->channel; }
        };

        // Mock ee('Model')
        $this->setMock('Model', new class($mockQuery) {
            private $query;
            public function __construct($query) { $this->query = $query; }
            public function get($model) {
                return $this->query;
            }
        });

        $this->channelFormLib->fetch_channel(3);

        // Verify that ee()->TMPL->tagparams was set
        $this->assertEquals('template_channel', ee()->TMPL->tagparams['channel']);
    }

    public function testFetchChannelHandlesChannelWithChannelFormSettings()
    {
        $mockChannel = $this->createMockChannel(['channel_id' => 10, 'channel_name' => 'settings_channel']);
        $mockChannel->ChannelFormSettings = new class {
            public $default_status = 'pending';
            public $allow_guest_posts = 'y';
        };

        // Mock the Model query
        $mockQuery = new class($mockChannel) {
            private $channel;
            public function __construct($channel) { $this->channel = $channel; }
            public function with($relation) { return $this; }
            public function filter($field, $value) { return $this; }
            public function all() { return $this; }
            public function first() { return $this->channel; }
        };

        // Mock ee('Model')
        $this->setMock('Model', new class($mockQuery) {
            private $query;
            public function __construct($query) { $this->query = $query; }
            public function get($model) {
                return $this->query;
            }
        });

        $this->channelFormLib->fetch_channel(10);

        $this->assertEquals(10, $this->channelFormLib->channel('channel_id'));
        $this->assertEquals('settings_channel', $this->channelFormLib->channel('channel_name'));
        $this->assertNotNull($this->channelFormLib->channel->ChannelFormSettings);
    }

    public function testFetchChannelHandlesChannelWithCategoryGroups()
    {
        $mockChannel = $this->createMockChannel(['channel_id' => 15, 'channel_name' => 'category_channel']);
        $mockChannel->CategoryGroups = ['group1', 'group2'];

        // Mock the Model query
        $mockQuery = new class($mockChannel) {
            private $channel;
            public function __construct($channel) { $this->channel = $channel; }
            public function with($relation) { return $this; }
            public function filter($field, $value) { return $this; }
            public function all() { return $this; }
            public function first() { return $this->channel; }
        };

        // Mock ee('Model')
        $this->setMock('Model', new class($mockQuery) {
            private $query;
            public function __construct($query) { $this->query = $query; }
            public function get($model) {
                return $this->query;
            }
        });

        $this->channelFormLib->fetch_channel(15);

        $this->assertEquals(15, $this->channelFormLib->channel('channel_id'));
        $this->assertEquals(['group1', 'group2'], $this->channelFormLib->channel->CategoryGroups);
    }

    public function testFetchChannelHandlesNullChannelName()
    {
        $mockChannel = $this->createMockChannel(['channel_id' => 20, 'channel_name' => null]);

        // Mock the Model query
        $mockQuery = new class($mockChannel) {
            private $channel;
            public function __construct($channel) { $this->channel = $channel; }
            public function with($relation) { return $this; }
            public function filter($field, $value) { return $this; }
            public function all() { return $this; }
            public function first() { return $this->channel; }
        };

        // Mock ee('Model')
        $this->setMock('Model', new class($mockQuery) {
            private $query;
            public function __construct($query) { $this->query = $query; }
            public function get($model) {
                return $this->query;
            }
        });

        $this->channelFormLib->fetch_channel(20);

        $this->assertEquals(20, $this->channelFormLib->channel('channel_id'));
        $this->assertNull($this->channelFormLib->channel('channel_name'));
    }

    public function testFetchChannelHandlesEmptyChannelName()
    {
        $mockChannel = $this->createMockChannel(['channel_id' => 25, 'channel_name' => '']);

        // Mock the Model query
        $mockQuery = new class($mockChannel) {
            private $channel;
            public function __construct($channel) { $this->channel = $channel; }
            public function with($relation) { return $this; }
            public function filter($field, $value) { return $this; }
            public function all() { return $this; }
            public function first() { return $this->channel; }
        };

        // Mock ee('Model')
        $this->setMock('Model', new class($mockQuery) {
            private $query;
            public function __construct($query) { $this->query = $query; }
            public function get($model) {
                return $this->query;
            }
        });

        $this->channelFormLib->fetch_channel(25);

        $this->assertEquals(25, $this->channelFormLib->channel('channel_id'));
        $this->assertEquals('', $this->channelFormLib->channel('channel_name'));
    }

    public function testFetchChannelHandlesMissingTemplateParameter()
    {
        $mockChannel = $this->createMockChannel(['channel_id' => 30, 'channel_name' => 'no_template_channel']);

        // Mock the Model query
        $mockQuery = new class($mockChannel) {
            private $channel;
            public function __construct($channel) { $this->channel = $channel; }
            public function with($relation) { return $this; }
            public function filter($field, $value) { return $this; }
            public function all() { return $this; }
            public function first() { return $this->channel; }
        };

        // Mock ee('Model')
        $this->setMock('Model', new class($mockQuery) {
            private $query;
            public function __construct($query) { $this->query = $query; }
            public function get($model) {
                return $this->query;
            }
        });

        // Mock TMPL to be null
        ee()->TMPL = null;

        $this->channelFormLib->fetch_channel(30);

        // Should not throw an exception when TMPL is null
        $this->assertEquals(30, $this->channelFormLib->channel('channel_id'));
        $this->assertEquals('no_template_channel', $this->channelFormLib->channel('channel_name'));
    }

    public function testFetchChannelHandlesChannelWithNullProperties()
    {
        $mockChannel = $this->createMockChannel(['channel_id' => 35, 'channel_name' => 'null_props_channel']);
        // Simulate null ChannelFormSettings and CategoryGroups
        $mockChannel->ChannelFormSettings = null;
        $mockChannel->CategoryGroups = null;

        // Mock the Model query
        $mockQuery = new class($mockChannel) {
            private $channel;
            public function __construct($channel) { $this->channel = $channel; }
            public function with($relation) { return $this; }
            public function filter($field, $value) { return $this; }
            public function all() { return $this; }
            public function first() { return $this->channel; }
        };

        // Mock ee('Model')
        $this->setMock('Model', new class($mockQuery) {
            private $query;
            public function __construct($query) { $this->query = $query; }
            public function get($model) {
                return $this->query;
            }
        });

        $this->channelFormLib->fetch_channel(35);

        $this->assertEquals(35, $this->channelFormLib->channel('channel_id'));
        $this->assertNull($this->channelFormLib->channel->ChannelFormSettings);
        $this->assertNull($this->channelFormLib->channel->CategoryGroups);
    }
}
