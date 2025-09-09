<?php

require_once __DIR__ . '/ChannelFormJavascriptTest.php';

/**
 * Tests for Channel_form_javascript constructor
 */
class ChannelFormJavascriptConstructorTest extends ChannelFormJavascriptTest
{
    public function testConstructorInitializesProperties()
    {
        // Test that constructor sets up js_path property
        $this->assertObjectHasAttribute('js_path', $this->channelFormJavascript);

        // Test that PATH_JQUERY constant is defined
        $this->assertTrue(defined('PATH_JQUERY'));

        // Test that js_path contains expected path structure
        $reflection = new ReflectionClass($this->channelFormJavascript);
        $jsPathProperty = $reflection->getProperty('js_path');
        $jsPathProperty->setAccessible(true);
        $jsPath = $jsPathProperty->getValue($this->channelFormJavascript);

        // The js_path should be constructed as themes/ee/asset/javascript/' . PATH_JS . '/'
        $this->assertTrue(strpos($jsPath, '/themes/') !== false);
        $this->assertTrue(strpos($jsPath, '/asset/javascript/') !== false);
        $this->assertStringEndsWith('src/', $jsPath);
    }

    public function testConstructorWithParameters()
    {
        // Test constructor with parameters (though it currently doesn't use them)
        $params = ['test' => 'value'];
        $javascript = new Channel_form_javascript($params);

        // Should still initialize properly
        $this->assertInstanceOf('Channel_form_javascript', $javascript);
    }
}
