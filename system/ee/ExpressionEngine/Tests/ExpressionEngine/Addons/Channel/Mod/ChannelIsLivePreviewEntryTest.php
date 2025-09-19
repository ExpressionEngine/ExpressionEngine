<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelIsLivePreviewEntryTest extends ChannelTestBase
{
    public function testMatchesEntryId()
    {
        $this->channel->query_string = '123';
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return true; }
            public function getEntryData(){ return ['entry_id'=>'123','url_title'=>'foo','channel_name'=>'blog']; }
        });
        $this->setTemplateParams([]);

        $ref = new ReflectionClass($this->channel);
        $m = $ref->getMethod('isLivePreviewEntry');
        $m->setAccessible(true);
        $this->assertTrue($m->invoke($this->channel));
    }

    public function testChannelFilterMismatchReturnsFalse()
    {
        $this->channel->query_string = 'foo';
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return true; }
            public function getEntryData(){ return ['entry_id'=>'456','url_title'=>'foo','channel_name'=>'blog']; }
        });
        $this->setTemplateParams(['channel' => 'news']);

        $ref = new ReflectionClass($this->channel);
        $m = $ref->getMethod('isLivePreviewEntry');
        $m->setAccessible(true);
        $this->assertFalse($m->invoke($this->channel));
    }
}


