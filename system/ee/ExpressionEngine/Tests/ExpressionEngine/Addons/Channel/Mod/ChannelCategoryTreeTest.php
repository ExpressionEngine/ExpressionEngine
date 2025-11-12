<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelCategoryTreeTest extends ChannelTestBase
{
    public function testCategoryTreeReturnsFalseWhenNoGroupId()
    {
        $this->assertFalse($this->channel->category_tree([]));
    }

    public function testCategoryTreeShowEmptyNoWithNoCategoriesReturnsFalse()
    {
        $cdata = [
            'group_id' => '1',
            'show_empty' => 'no',
            'channel_ids' => []
        ];
        $this->setDbRows([]);
        $this->assertFalse($this->channel->category_tree($cdata));
    }
}


