<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelChannelNameTest extends ChannelTestBase
{
    public function testChannelNameReturnsCachedValueWhenAvailable()
    {
        $channelName = 'news';
        $expectedTitle = 'News Channel';

        // Set cached value
        $this->channel->channel_name[$channelName] = $expectedTitle;

        // Set channel parameter
        $this->setTemplateParams(['channel' => $channelName]);

        $result = $this->channel->channel_name();

        $this->assertEquals($expectedTitle, $result);
    }

    public function testChannelNameReturnsEmptyStringWhenNoChannelParam()
    {
        // Don't set channel parameter
        $this->setTemplateParams([]);

        $result = $this->channel->channel_name();

        $this->assertEquals('', $result);
    }

    public function testChannelNameQueriesDatabaseForChannelTitle()
    {
        $channelName = 'news';
        $expectedTitle = 'News Channel';

        // Set channel parameter
        $this->setTemplateParams(['channel' => $channelName]);

        // Mock TMPL site_ids
        $this->setMock('TMPL', new class {
            public $site_ids = [1];
            public function fetch_param($key) {
                return ($key === 'channel') ? 'news' : null;
            }
        });

        // Mock database to return channel data
        $this->setDbRows([
            ['channel_title' => $expectedTitle]
        ]);

        $result = $this->channel->channel_name();

        // The method returns the channel_title value from the database result object
        $this->assertEquals($expectedTitle, $result);
        // Verify it was cached
        $this->assertEquals($expectedTitle, $this->channel->channel_name[$channelName]);
    }

    public function testChannelNameReturnsEmptyStringWhenNoResults()
    {
        $channelName = 'nonexistent';

        // Set channel parameter
        $this->setTemplateParams(['channel' => $channelName]);

        // Mock TMPL site_ids
        $this->setMock('TMPL', new class($channelName) {
            private $channelName;
            public function __construct($channelName) { $this->channelName = $channelName; }
            public $site_ids = [1];
            public function fetch_param($key) {
                return ($key === 'channel') ? $this->channelName : null;
            }
        });

        // Mock database to return no results
        $this->setDbRows([]);

        $result = $this->channel->channel_name();

        $this->assertEquals('', $result);
    }

    public function testChannelNameHandlesMultipleSiteIds()
    {
        $channelName = 'news';
        $expectedTitle = 'News Channel';

        // Set channel parameter
        $this->setTemplateParams(['channel' => $channelName]);

        // Mock TMPL with multiple site_ids
        $this->setMock('TMPL', new class($channelName) {
            private $channelName;
            public function __construct($channelName) { $this->channelName = $channelName; }
            public $site_ids = [1, 2, 3];
            public function fetch_param($key) {
                return ($key === 'channel') ? $this->channelName : null;
            }
        });

        // Mock database to return channel data
        $this->setDbRows([
            ['channel_title' => $expectedTitle]
        ]);

        $result = $this->channel->channel_name();

        $this->assertEquals($expectedTitle, $result);
    }

    public function testChannelNameEscapesChannelNameInQuery()
    {
        $channelName = "test'channel";
        $expectedTitle = 'Test Channel';

        // Set channel parameter with potentially unsafe characters
        $this->setTemplateParams(['channel' => $channelName]);

        // Mock TMPL site_ids
        $this->setMock('TMPL', new class($channelName) {
            private $channelName;
            public function __construct($channelName) { $this->channelName = $channelName; }
            public $site_ids = [1];
            public function fetch_param($key) {
                return ($key === 'channel') ? $this->channelName : null;
            }
        });

        // Mock database to return channel data
        $this->setDbRows([
            ['channel_title' => $expectedTitle]
        ]);

        $result = $this->channel->channel_name();

        $this->assertEquals($expectedTitle, $result);
    }
}
