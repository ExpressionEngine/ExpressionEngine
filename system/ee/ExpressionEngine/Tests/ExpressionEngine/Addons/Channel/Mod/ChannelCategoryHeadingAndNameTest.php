<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelCategoryHeadingAndNameTest extends ChannelTestBase
{
    public function testCategoryHeadingReturnsNoResultsWhenNoCriteria()
    {
        $this->channel->query_string = '';
        $this->setTemplateParams([]);
        $this->setTemplateTagdata('x');
        $out = $this->channel->category_heading();
        $this->assertEquals('NO_RESULTS', $out);
    }

    public function testCategoryHeadingHookOverrides()
    {
        $this->channel->query_string = 'C10';
        $this->setTemplateParams(['channel' => 'news']);
        $this->setTemplateTagdata('x');
        $this->setMock('extensions', new class {
            public $end_script = true;
            public function active_hook($name){ return $name === 'channel_module_category_heading_start'; }
            public function call($name){ return 'HOOKED'; }
        });
        $out = $this->channel->category_heading();
        $this->assertEquals('HOOKED', $out);
    }

    public function testChannelNameNoParamReturnsEmpty()
    {
        $this->setTemplateParams(['channel' => '']);
        $out = $this->channel->channel_name();
        $this->assertEquals('', $out);
    }

    public function testChannelNameResolvesTitleByName()
    {
        $this->setTemplateParams(['channel' => 'blog']);
        // exp_channels.channel_title fetched
        ee()->db->setRows([['channel_title' => 'Blog Title']]);
        $out = $this->channel->channel_name();
        $this->assertEquals('Blog Title', $out);
    }
}


