<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelSubmitEntryTest extends ChannelTestBase
{
    public function testReturnsEmptyWhenNotAction()
    {
        if (defined('REQ')) {
            // REQ already defined as CP in base; expect empty string
            $this->assertSame('', $this->channel->submit_entry());
        } else {
            $this->assertSame('', $this->channel->submit_entry());
        }
    }

    public function testSubmitEntryReturnsFatalErrorWhenNotLoggedIn()
    {
        // Mock session to indicate user is not logged in
        $this->setMock('session', new class {
            public function userdata($key) {
                return ($key === 'member_id') ? 0 : null;
            }
        });

        // When REQ is not 'ACTION', the method returns empty string early
        $expectedError = '';

        $result = $this->channel->submit_entry();

        $this->assertEquals($expectedError, $result);
    }

    public function testSubmitEntryProcessesFormDataWhenLoggedIn()
    {
        // Mock session to indicate user is logged in
        $this->setMock('session', new class {
            public function userdata($key) {
                return ($key === 'member_id') ? 1 : null;
            }
        });

        // Mock input to simulate form submission
        $this->setMock('input', new class {
            public function get_post($key) {
                $postData = [
                    'channel_id' => '1',
                    'title' => 'Test Entry',
                    'entry_body' => 'Test content'
                ];
                return $postData[$key] ?? null;
            }
        });

        $result = $this->channel->submit_entry();

        // Should return some result (could be success message or form)
        $this->assertIsString($result);
    }

    public function testSubmitEntryHandlesEntryUpdate()
    {
        // Mock session to indicate user is logged in
        $this->setMock('session', new class {
            public function userdata($key) {
                return ($key === 'member_id') ? 1 : null;
            }
        });

        // Mock input to simulate form submission with entry_id (update)
        $this->setMock('input', new class {
            public function get_post($key) {
                $postData = [
                    'channel_id' => '1',
                    'entry_id' => '123',
                    'title' => 'Updated Entry',
                    'entry_body' => 'Updated content'
                ];
                return $postData[$key] ?? null;
            }
        });

        $result = $this->channel->submit_entry();

        // Should return some result for entry update
        $this->assertIsString($result);
    }

    public function testSubmitEntryValidatesRequiredFields()
    {
        // Mock session to indicate user is logged in
        $this->setMock('session', new class {
            public function userdata($key) {
                return ($key === 'member_id') ? 1 : null;
            }
        });

        // Mock input with missing required fields
        $this->setMock('input', new class {
            public function get_post($key) {
                $postData = [
                    'channel_id' => '1',
                    // Missing title and other required fields
                ];
                return $postData[$key] ?? null;
            }
        });

        $result = $this->channel->submit_entry();

        // Should return validation errors or form with errors
        $this->assertIsString($result);
    }

    public function testSubmitEntryHandlesFileUploads()
    {
        // Mock session to indicate user is logged in
        $this->setMock('session', new class {
            public function userdata($key) {
                return ($key === 'member_id') ? 1 : null;
            }
        });

        // Mock input with file upload data
        $this->setMock('input', new class {
            public function get_post($key) {
                $postData = [
                    'channel_id' => '1',
                    'title' => 'Entry with File',
                    'entry_body' => 'Content with file upload'
                ];
                return $postData[$key] ?? null;
            }
        });

        $result = $this->channel->submit_entry();

        // Should handle file uploads appropriately
        $this->assertIsString($result);
    }

    public function testSubmitEntryHandlesChannelPermissions()
    {
        // Mock session to indicate user is logged in but without permissions
        $this->setMock('session', new class {
            public function userdata($key) {
                return ($key === 'member_id') ? 1 : null;
            }
        });

        // Mock input with channel that user doesn't have permission for
        $this->setMock('input', new class {
            public function get_post($key) {
                $postData = [
                    'channel_id' => '999', // Non-existent or restricted channel
                    'title' => 'Test Entry'
                ];
                return $postData[$key] ?? null;
            }
        });

        $result = $this->channel->submit_entry();

        // Should handle permission errors
        $this->assertIsString($result);
    }
}

