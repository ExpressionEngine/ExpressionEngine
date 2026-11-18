<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelLivePreviewTest extends ChannelTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        unset($_SERVER['HTTP_AUTHORIZATION']);
        unset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        unset($_SERVER['HTTP_EE_LIVE_PREVIEW_TOKEN']);
        unset($_SERVER['REDIRECT_HTTP_EE_LIVE_PREVIEW_TOKEN']);
        ee()->session->set_userdata('session_id', 'test-session');
        $this->setDbRows([
            [
                'entry_id' => 3,
                'channel_id' => 1,
                'author_id' => 1
            ]
        ]);
    }

    private function issuePreviewToken(array $overrides = [], ?int $ttl = null): string
    {
        $defaults = [
            'member_id' => 1,
            'channel_id' => 1,
            'entry_id' => 3,
            'site_id' => 1,
            'origin' => 'http://localhost',
            'return' => 'http://localhost/return'
        ];

        $data = array_merge($defaults, $overrides);

        return ee('LivePreviewToken')->issue(
            (int) $data['member_id'],
            (int) $data['channel_id'],
            isset($data['entry_id']) ? (int) $data['entry_id'] : null,
            $data['origin'],
            $data['return'],
            $ttl,
            (int) $data['site_id']
        );
    }

    private function setPreviewTokenHeader(?string $token): void
    {
        if (empty($token)) {
            unset($_SERVER['HTTP_AUTHORIZATION']);
            unset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
            return;
        }

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    }

    private function setPreviewTokenCompatibilityHeader(?string $token): void
    {
        if (empty($token)) {
            unset($_SERVER['HTTP_EE_LIVE_PREVIEW_TOKEN']);
            unset($_SERVER['REDIRECT_HTTP_EE_LIVE_PREVIEW_TOKEN']);
            return;
        }

        $_SERVER['HTTP_EE_LIVE_PREVIEW_TOKEN'] = $token;
    }

    public function testNoPreviewDataEarlyExit()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);
        $this->setMock('LivePreview', new class { public function preview(){ return 'NO_DATA'; } public function hasEntryData(){ return false; } });
        // live_preview uses get_post/get and Request service
        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} });
        $output = new class {
            public $last = null;
            public function show_user_error(...$args){
                $this->last = $args;
                return 'ERR';
            }
        };
        $this->setMock('output', $output);
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken(['entry_id' => null]));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());
        $out = $this->channel->live_preview();
        $this->assertIsString($out);
    }

    public function testLivePreviewRequiresPermission()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);
        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $output = new class {
            public $last = null;
            public function show_user_error(...$args){
                $this->last = $args;
                return 'ERR';
            }
        };
        $this->setMock('output', $output);
        $this->setMock('Permission', new class { public function can($k){ return false; } public function isSuperAdmin(){ return false; } public function hasAny(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken());
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertFalse($livePreview->called);
        $this->assertSame('ERR', $out);
    }

    public function testLivePreviewRequiresToken()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);
        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $output = new class {
            public $last = null;
            public function show_user_error(...$args){
                $this->last = $args;
                return 'ERR';
            }
        };
        $this->setMock('output', $output);
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader(null);
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertFalse($livePreview->called);
        $this->assertSame('ERR', $out);
    }

    public function testLivePreviewAllowsValidToken()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);
        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $output = new class {
            public $last = null;
            public function show_user_error(...$args){
                $this->last = $args;
                return 'ERR';
            }
        };
        $this->setMock('output', $output);
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken());
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertTrue($livePreview->called, 'unexpected error: ' . json_encode($output->last));
        $this->assertSame('PREVIEW', $out);
    }

    public function testLivePreviewAllowsValidTokenFromCompatibilityHeader()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);
        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $output = new class {
            public $last = null;
            public function show_user_error(...$args){
                $this->last = $args;
                return 'ERR';
            }
        };
        $this->setMock('output', $output);
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $token = $this->issuePreviewToken();
        $this->setPreviewTokenHeader(null);
        $this->setPreviewTokenCompatibilityHeader($token);
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertTrue($livePreview->called, 'unexpected error: ' . json_encode($output->last));
        $this->assertSame('PREVIEW', $out);
    }

    public function testLivePreviewRejectsEntryIdWhenTokenHasNone()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken(['entry_id' => null]));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertFalse($livePreview->called);
        $this->assertSame('ERR', $out);
    }

    public function testLivePreviewRejectsMissingEntryIdWhenTokenHasOne()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken());
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertFalse($livePreview->called);
        $this->assertSame('ERR', $out);
    }

    public function testLivePreviewRejectsMismatchedOriginAndFrom()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class {
            public function get($k){
                if ($k === 'from') { return rawurlencode(base64_encode('http://evil.test')); }
                return rawurlencode(base64_encode('http://localhost'));
            }
            public function isEncrypted(){ return false; }
            public function method(){ return 'POST'; }
        });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken(['origin' => 'http://evil.test']));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertFalse($livePreview->called);
        $this->assertSame('ERR', $out);
    }

    public function testLivePreviewAllowsRefererWhenNoOrigin()
    {
        unset($_SERVER['HTTP_ORIGIN']);
        $_SERVER['HTTP_REFERER'] = 'http://localhost/some/page';

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class {
            public function get($k){ return null; }
            public function isEncrypted(){ return false; }
            public function method(){ return 'POST'; }
        });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken(['origin' => 'http://localhost/some/page']));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertTrue($livePreview->called);
        $this->assertSame('PREVIEW', $out);
    }

    public function testLivePreviewAllowsFromWhenNoOriginOrReferer()
    {
        unset($_SERVER['HTTP_ORIGIN']);
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class {
            public function get($k){
                if ($k === 'from') { return rawurlencode(base64_encode('http://localhost')); }
                return null;
            }
            public function isEncrypted(){ return false; }
            public function method(){ return 'POST'; }
        });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken());
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertTrue($livePreview->called);
        $this->assertSame('PREVIEW', $out);
    }

    public function testLivePreviewAllowsReturnFallbackWhenNoHeadersOrFrom()
    {
        unset($_SERVER['HTTP_ORIGIN']);
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class {
            public function get($k){ return null; }
            public function isEncrypted(){ return false; }
            public function method(){ return 'POST'; }
        });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken(['origin' => 'http://localhost/return']));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertTrue($livePreview->called);
        $this->assertSame('PREVIEW', $out);
    }

    public function testLivePreviewRejectsDisallowedOrigin()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://evil.test';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://evil.test/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class {
            public function get($k){ return rawurlencode(base64_encode('http://evil.test')); }
            public function isEncrypted(){ return false; }
            public function method(){ return 'POST'; }
        });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken(['origin' => 'http://evil.test']));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertFalse($livePreview->called);
        $this->assertSame('ERR', $out);
    }

    public function testLivePreviewAllowsPortInAllowedOrigins()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost:8080';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost:8080/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class {
            public function get($k){
                if ($k === 'from') { return rawurlencode(base64_encode('http://localhost:8080')); }
                return null;
            }
            public function isEncrypted(){ return false; }
            public function method(){ return 'POST'; }
        });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost:8080/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken([
            'origin' => 'http://localhost:8080',
            'return' => 'http://localhost:8080/return'
        ]));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertTrue($livePreview->called);
        $this->assertSame('PREVIEW', $out);
    }

    public function testLivePreviewRejectsExpiredToken()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken([], -120));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertFalse($livePreview->called);
        $this->assertSame('ERR', $out);
    }

    public function testLivePreviewRejectsTokenMemberMismatch()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken(['member_id' => 2]));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertFalse($livePreview->called);
        $this->assertSame('ERR', $out);
    }

    public function testLivePreviewRejectsTokenChannelMismatch()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken(['channel_id' => 2]));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertFalse($livePreview->called);
        $this->assertSame('ERR', $out);
    }

    public function testLivePreviewRejectsTokenEntryMismatch()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken(['entry_id' => 4]));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertFalse($livePreview->called);
        $this->assertSame('ERR', $out);
    }

    public function testLivePreviewRejectsChannelIdMismatch()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);
        $this->setDbRows([
            [
                'entry_id' => 3,
                'channel_id' => 2,
                'author_id' => 1
            ]
        ]);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken());
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertFalse($livePreview->called);
        $this->assertSame('ERR', $out);
    }

    public function testLivePreviewRejectsUnknownEntryId()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);
        $this->setDbRows([]);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 999; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken(['entry_id' => 999]));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertFalse($livePreview->called);
        $this->assertSame('ERR', $out);
    }

    public function testLivePreviewAllowsAllowedPreviewDomainsOverride()
    {
        $_SERVER['HTTP_ORIGIN'] = 'https://preview.example.test';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('https://preview.example.test/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return null; } public function isEncrypted(){ return true; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ if ($k === 'allowed_preview_domains') { return ['preview.example.test']; } return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken([
            'origin' => 'https://preview.example.test',
            'return' => 'https://preview.example.test/return'
        ]));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertTrue($livePreview->called);
        $this->assertSame('PREVIEW', $out);
    }

    public function testLivePreviewAllowsHttpsOriginWhenConfiguredHttp()
    {
        $_SERVER['HTTP_ORIGIN'] = 'https://localhost';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('https://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return null; } public function isEncrypted(){ return true; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken([
            'origin' => 'https://localhost',
            'return' => 'https://localhost/return'
        ]));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertTrue($livePreview->called);
        $this->assertSame('PREVIEW', $out);
    }

    public function testLivePreviewAllowsHttpsRefererWhenNoOrigin()
    {
        unset($_SERVER['HTTP_ORIGIN']);
        $_SERVER['HTTP_REFERER'] = 'https://localhost/some/page';

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('https://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return null; } public function isEncrypted(){ return true; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken([
            'origin' => 'https://localhost/some/page',
            'return' => 'https://localhost/return'
        ]));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertTrue($livePreview->called);
        $this->assertSame('PREVIEW', $out);
    }

    public function testLivePreviewAllowsLoopbackAliasWhenLocalhostAllowed()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://127.0.0.1';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://127.0.0.1/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://127.0.0.1')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://localhost/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken([
            'origin' => 'http://127.0.0.1',
            'return' => 'http://127.0.0.1/return'
        ]));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertTrue($livePreview->called);
        $this->assertSame('PREVIEW', $out);
    }

    public function testLivePreviewAllowsLocalhostWhenLoopbackAllowed()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if ($k==='return'){ return rawurlencode(base64_encode('http://localhost/return')); } if ($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class { public function get($k){ return rawurlencode(base64_encode('http://localhost')); } public function isEncrypted(){ return false; } public function method(){ return 'POST'; } });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://127.0.0.1/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken([
            'origin' => 'http://localhost',
            'return' => 'http://localhost/return'
        ]));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertTrue($livePreview->called);
        $this->assertSame('PREVIEW', $out);
    }

    public function testLivePreviewAllowsIpv6OriginWhenConfiguredWithPort()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://[::1]:8080';
        unset($_SERVER['HTTP_REFERER']);

        $livePreview = new class {
            public $called = false;
            public function preview() { $this->called = true; return 'PREVIEW'; }
            public function hasEntryData(){ return false; }
        };
        $this->setMock('LivePreview', $livePreview);

        $this->setMock('input', new class {
            public function get_post($k){ if ($k === 'entry_id') { return 3; } if ($k === 'channel_id') { return 1; } return null; }
            public function get($k){ if($k==='return'){ return rawurlencode(base64_encode('http://[::1]:8080/return')); } if($k==='prefer_system_preview'){ return 'n'; } return null; }
        });
        $this->setMock('Request', new class {
            public function get($k){
                if ($k === 'from') { return rawurlencode(base64_encode('http://[::1]:8080')); }
                return null;
            }
            public function isEncrypted(){ return false; }
            public function method(){ return 'POST'; }
        });
        $this->setMock('Model', new class { public function get($m){ return new class { public function filter(){ return $this; } public function all(){ return new class { public function pluck($k){ return ['http://[::1]:8080/']; } }; } }; } });
        $this->setMock('Config', new class { public function getFile(){ return new class { public function get($k){ return []; } }; } });
        $this->setMock('lang', new class { public function load($k){} public function line($k){ return $k; } });
        $this->setMock('output', new class { public function show_user_error(){ return 'ERR'; } });
        $this->setMock('Permission', new class { public function can($k){ return true; } public function isSuperAdmin(){ return false; } });
        ee()->session->set_userdata('member_id', 1);
        $this->setPreviewTokenHeader($this->issuePreviewToken([
            'origin' => 'http://[::1]:8080',
            'return' => 'http://[::1]:8080/return'
        ]));
        $this->setMock('config', (function(){ $c = new FakeConfig(); $c->items['cp_url'] = 'http://localhost/admin.php';
            $c->items['site_id'] = 1; return $c; })());

        $out = $this->channel->live_preview();

        $this->assertTrue($livePreview->called);
        $this->assertSame('PREVIEW', $out);
    }
}
