<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFindParentTest extends ChannelTestBase
{
    public function testNoParentReturnsEmpty()
    {
        $this->channel->cat_full_array = [];
        $this->channel->find_parent(99, [1=>0, 2=>1]);
        $this->assertSame([], $this->channel->cat_full_array);
    }

    public function testSingleLevelAddsParent()
    {
        $this->channel->cat_full_array = [];
        $this->channel->find_parent(2, [1=>0, 2=>1]);
        $this->assertSame([2,1], $this->channel->cat_full_array);
    }
}


