<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelLivePreviewTest extends ChannelTestBase
{
    public function testNoPreviewDataEarlyExit()
    {
        $this->setMock('LivePreview', new class { public function preview(){ return 'NO_DATA'; } public function hasEntryData(){ return false; } });
        // live_preview uses get_post/get and Request service
        $this->setMock('input', new class {
            public function get_post($k){ return null; }
            public function get($k){ if($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php'; return $c; })());
        $out = $this->channel->live_preview();
        $this->assertIsString($out);
    }
}

