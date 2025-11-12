<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelPrevEntryAndSubcategoriesTest extends ChannelTestBase
{
    public function testPrevEntryEarlyReturnWhenNoQueryString()
    {
        $this->channel->query_string = '';
        $this->setTemplateParams([]);
        $res = $this->channel->prev_entry();
        $this->assertNull($res); // method returns null/void on early return
    }

    public function testProcessSubcategoriesRecursesChildren()
    {
        // temp_array format: key => [cat_id, parent_id, name, image, desc, url_title] (we only use parent checks)
        $this->channel->temp_array = [
            10 => [10, 0, 'Root', '', '', 'root'],
            20 => [20, 10, 'ChildA', '', '', 'child-a'],
            30 => [30, 20, 'GrandChild', '', '', 'grand'],
            40 => [40, 10, 'ChildB', '', '', 'child-b'],
        ];
        $this->channel->cat_array = [];
        $this->channel->process_subcategories(10);
        // Expect children (20,30,40) included; order is insertion order via recursion
        $ids = array_map(function($v){ return $v[0]; }, $this->channel->cat_array);
        $this->assertEquals([20,30,40], $ids);
    }
}


