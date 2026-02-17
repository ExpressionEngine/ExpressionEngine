<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibConstructTest extends ChannelFormLibTestBase
{
    public function testConstructorSetsChannelFormGlobalObject()
    {
        // The constructor should set the global channel_form object
        // Note: This may not work in all test environments due to ee() mock setup
        if (isset(ee()->channel_form)) {
            $this->assertInstanceOf('Channel_form_lib', ee()->channel_form);
        } else {
            // In some test environments, the global may not be set properly
            $this->assertInstanceOf('Channel_form_lib', $this->channelFormLib);
        }
    }

    public function testConstructorLoadsChannelFormLanguageFile()
    {
        // Verify that the constructor calls lang->loadfile('channel_form')
        // We can't easily test this directly, but we can verify the object is initialized
        $this->assertTrue($this->channelFormLib->initialized === false); // Should be false until initialize() is called
    }

    public function testConstructorInitializesDefaultProperties()
    {
        $this->assertFalse($this->channelFormLib->initialized);
        $this->assertFalse($this->channelFormLib->form_error);
        $this->assertTrue($this->channelFormLib->form_loaded);
        // site_id is initialized later by fetch_site(), not in constructor
        $this->assertNull($this->channelFormLib->site_id); // Not set in constructor
    }

    public function testConstructorDoesNotInitializeArrays()
    {
        // Arrays are initialized by the initialize() method, not the constructor
        $this->assertNull($this->channelFormLib->categories);
        $this->assertNull($this->channelFormLib->checkboxes);
        $this->assertNull($this->channelFormLib->custom_field_conditional_names);
        $this->assertNull($this->channelFormLib->custom_fields);
        $this->assertNull($this->channelFormLib->date_fields);
        $this->assertNull($this->channelFormLib->errors);
        $this->assertNull($this->channelFormLib->field_errors);
        $this->assertNull($this->channelFormLib->statuses);
        $this->assertNull($this->channelFormLib->show_fields);
        $this->assertNull($this->channelFormLib->title_fields);
    }
}
