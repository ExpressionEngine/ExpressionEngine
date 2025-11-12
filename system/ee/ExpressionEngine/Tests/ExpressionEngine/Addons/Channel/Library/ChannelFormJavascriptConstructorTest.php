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
        $this->assertObjectHasProperty('js_path', $this->channelFormJavascript);

        // Test that PATH_JQUERY constant is defined
        $this->assertTrue(defined('PATH_JQUERY'));

        // Test that js_path contains expected path structure
        $reflection = new ReflectionClass($this->channelFormJavascript);
        $jsPathProperty = $reflection->getProperty('js_path');
        TestReflectionHelper::makePropertyAccessible($jsPathProperty);
        $jsPath = $jsPathProperty->getValue($this->channelFormJavascript);

        // The js_path should be constructed as themes/ee/asset/javascript/' . PATH_JS . '/'
        // Handle both Windows (backslashes) and Unix (forward slashes) path separators
        $this->assertTrue(strpos($jsPath, 'themes') !== false, 'js_path should contain "themes"');
        $this->assertTrue(
            strpos($jsPath, 'ee/asset/javascript') !== false || strpos($jsPath, 'ee\\asset\\javascript') !== false,
            'js_path should contain "ee/asset/javascript"'
        );
        $this->assertTrue(strpos($jsPath, 'src') !== false, 'js_path should contain "src"');
        $this->assertTrue(
            substr($jsPath, -4) === 'src/' || substr($jsPath, -4) === 'src\\',
            'js_path should end with "src/" or "src\\"'
        );
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
