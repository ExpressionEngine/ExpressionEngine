<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelInitializeTest extends ChannelTestBase
{
    public function testInitializeResetsSqlAndReturnData()
    {
        // Set some initial values
        $this->channel->sql = 'SELECT * FROM test';
        $this->channel->return_data = 'some data';

        // Call initialize
        $this->channel->initialize();

        // Verify they are reset
        $this->assertEquals('', $this->channel->sql);
        $this->assertEquals('', $this->channel->return_data);
    }

    public function testInitializePreservesOtherProperties()
    {
        // Set some other properties
        $this->channel->limit = '50';
        $this->channel->entry_id = '123';
        $this->channel->uristr = 'test-uri';

        // Call initialize
        $this->channel->initialize();

        // Verify other properties are preserved
        $this->assertEquals('50', $this->channel->limit);
        $this->assertEquals('123', $this->channel->entry_id);
        $this->assertEquals('test-uri', $this->channel->uristr);
    }
}

