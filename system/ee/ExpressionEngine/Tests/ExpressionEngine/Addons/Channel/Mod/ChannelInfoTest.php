<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelInfoTest extends ChannelTestBase
{
    public function testReturnsRequiredMetadata()
    {
        $this->setTemplateParams(['channel' => 'news']);
        ee()->TMPL->var_single = ['channel_title'];
        $this->setTemplateTagdata('{channel_title}');
        // Mock DB to return one row
        $this->setDbRows([['channel_title' => 'News', 'channel_url' => 'http://ex', 'channel_description' => 'd', 'channel_lang' => 'en']]);

        $out = $this->channel->info();
        $this->assertIsString($out);
        $this->assertStringContainsString('News', $out);
    }
}
