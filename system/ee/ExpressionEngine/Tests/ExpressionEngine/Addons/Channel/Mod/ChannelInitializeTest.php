<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelInitializeTest extends ChannelTestBase
{
    public function testResetsStateToDefaults()
    {
        $this->channel->sql = 'x';
        $this->channel->return_data = 'y';
        $this->channel->initialize();
        $this->assertSame('', $this->channel->sql);
        $this->assertSame('', $this->channel->return_data);
    }
}

