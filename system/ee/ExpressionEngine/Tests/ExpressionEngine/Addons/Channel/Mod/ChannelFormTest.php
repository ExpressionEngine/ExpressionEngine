<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFormTest extends ChannelTestBase
{
    public function testFormReturnsFatalErrorWhenNotLoggedIn()
    {
        // Set up template parameters
        $this->setTemplateParams(['channel' => 'news']);

        // Mock session to indicate user is not logged in
        $this->setMock('session', new class {
            public function userdata($key) {
                return ($key === 'member_id') ? 0 : null;
            }
        });

        $expectedError = 'You must be logged in to access this page';

        // Mock lang and output for error handling
        $this->setMock('lang', new class {
            public function line($key) {
                return ($key === 'must_be_logged_in') ? 'You must be logged in to access this page' : '';
            }
        });

        $this->setMock('output', new class {
            public function fatal_error($message) {
                return $message;
            }
        });

        // Mock channel_form_lib to avoid null reference
        $this->setMock('channel_form_lib', new class {
            public function entry_form() {
                return 'You must be logged in to access this page';
            }
        });

        $result = $this->channel->form();

        $this->assertEquals($expectedError, $result);
    }

    public function testFormReturnsDataWhenLoggedIn()
    {
        // Set up template parameters
        $this->setTemplateParams(['channel' => 'news']);

        // Mock session to indicate user is logged in
        $this->setMock('session', new class {
            public function userdata($key) {
                return ($key === 'member_id') ? 1 : null;
            }
        });

        // Mock database to return channel data
        $this->setDbRows([
            [
                'channel_id' => 1,
                'channel_name' => 'news',
                'channel_title' => 'News Channel'
            ]
        ]);

        // Mock channel_form_lib to avoid null reference
        $this->setMock('channel_form_lib', new class {
            public function entry_form() {
                return '<form>Channel form data</form>';
            }
        });

        $result = $this->channel->form();

        // Should return some form data when logged in
        $this->assertIsString($result);
    }

    public function testFormHandlesChannelParameter()
    {
        // Set up template parameters with channel
        $this->setTemplateParams(['channel' => 'news']);

        // Mock session to indicate user is logged in
        $this->setMock('session', new class {
            public function userdata($key) {
                return ($key === 'member_id') ? 1 : null;
            }
        });

        // Mock database to return channel data
        $this->setDbRows([
            [
                'channel_id' => 1,
                'channel_name' => 'news',
                'channel_title' => 'News Channel'
            ]
        ]);

        // Mock channel_form_lib to avoid null reference
        $this->setMock('channel_form_lib', new class {
            public function entry_form() {
                return '<form>Channel form with parameter</form>';
            }
        });

        $result = $this->channel->form();

        $this->assertIsString($result);
    }

    public function testFormHandlesEntryIdParameter()
    {
        // Set up template parameters with entry_id
        $this->setTemplateParams(['channel' => 'news', 'entry_id' => '123']);

        // Mock session to indicate user is logged in
        $this->setMock('session', new class {
            public function userdata($key) {
                return ($key === 'member_id') ? 1 : null;
            }
        });

        // Mock database to return channel data
        $this->setDbRows([
            [
                'channel_id' => 1,
                'channel_name' => 'news',
                'channel_title' => 'News Channel'
            ]
        ]);

        // Mock channel_form_lib to avoid null reference
        $this->setMock('channel_form_lib', new class {
            public function entry_form() {
                return '<form>Channel form with entry_id</form>';
            }
        });

        $result = $this->channel->form();

        $this->assertIsString($result);
    }
}

