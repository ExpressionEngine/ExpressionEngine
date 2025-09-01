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
}
