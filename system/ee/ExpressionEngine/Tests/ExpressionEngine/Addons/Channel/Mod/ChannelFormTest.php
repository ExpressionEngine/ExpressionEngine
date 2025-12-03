<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFormTest extends ChannelTestBase
{
    public function testFormDelegatesToLibraryWhenTemplatePresent()
    {
        $lib = new class {
            public $called = 0;
            public function entry_form(){ $this->called++; return '<form>ok</form>'; }
        };
        $this->setMock('channel_form_lib', $lib);

        // loader should return our lib instance when asked
        $this->setMock('load', new class($lib) {
            private $lib; public function __construct($l){$this->lib=$l;}
            public function library($name){ /* no-op */ }
            public function add_package_path($p){}
            public function helper($n){}
        });

        $out = $this->channel->form();
        $this->assertSame('<form>ok</form>', $out);
    }

    public function testFormReturnsEmptyWhenNoTemplate()
    {
        // Simulate missing ee()->TMPL by temporarily unsetting the mock
        $this->setMock('TMPL', null);
        $out = $this->channel->form();
        $this->assertSame('', $out);
    }
}

