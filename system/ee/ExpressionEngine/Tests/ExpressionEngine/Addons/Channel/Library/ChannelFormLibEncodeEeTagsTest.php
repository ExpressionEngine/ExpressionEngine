<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibEncodeEeTagsTest extends ChannelFormLibTestBase
{
    private function getEncodeEeTagsMethod()
    {
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('encode_ee_tags');
        TestReflectionHelper::makeMethodAccessible($method);
        return $method;
    }

    public function testEncodeEeTagsReplacesLeftDelimiter()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = '{exp:channel:entries}';
        $result = $method->invoke($this->channelFormLib, $input);

        $this->assertStringContainsString('CFORM-ENCODE-LEFT-BRACKET', $result);
        $this->assertStringNotContainsString('{', $result);
        $this->assertEquals('CFORM-ENCODE-LEFT-BRACKETexp:channel:entriesCFORM-ENCODE-RIGHT-BRACKET', $result);
    }

    public function testEncodeEeTagsReplacesRightDelimiter()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = '{exp:channel:entries}';
        $result = $method->invoke($this->channelFormLib, $input);

        $this->assertStringContainsString('CFORM-ENCODE-RIGHT-BRACKET', $result);
        $this->assertStringNotContainsString('}', $result);
        $this->assertEquals('CFORM-ENCODE-LEFT-BRACKETexp:channel:entriesCFORM-ENCODE-RIGHT-BRACKET', $result);
    }

    public function testEncodeEeTagsReplacesBothDelimiters()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = '{exp:channel:entries}';
        $result = $method->invoke($this->channelFormLib, $input);

        $expected = 'CFORM-ENCODE-LEFT-BRACKETexp:channel:entriesCFORM-ENCODE-RIGHT-BRACKET';
        $this->assertEquals($expected, $result);
    }

    public function testEncodeEeTagsHandlesMultipleTags()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = '{exp:channel:entries}{title}{/exp:channel:entries}';
        $result = $method->invoke($this->channelFormLib, $input);

        $expected = 'CFORM-ENCODE-LEFT-BRACKETexp:channel:entriesCFORM-ENCODE-RIGHT-BRACKETCFORM-ENCODE-LEFT-BRACKETtitleCFORM-ENCODE-RIGHT-BRACKETCFORM-ENCODE-LEFT-BRACKET/exp:channel:entriesCFORM-ENCODE-RIGHT-BRACKET';
        $this->assertEquals($expected, $result);
    }

    public function testEncodeEeTagsHandlesNestedTags()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = '{exp:channel:entries channel="{exp:channel:categories}"}';
        $result = $method->invoke($this->channelFormLib, $input);

        // All delimiters should be encoded, including nested ones
        $this->assertStringNotContainsString('{', $result);
        $this->assertStringNotContainsString('}', $result);
        $this->assertStringContainsString('CFORM-ENCODE-LEFT-BRACKET', $result);
        $this->assertStringContainsString('CFORM-ENCODE-RIGHT-BRACKET', $result);
    }

    public function testEncodeEeTagsHandlesEmptyString()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = '';
        $result = $method->invoke($this->channelFormLib, $input);

        $this->assertEquals('', $result);
    }

    public function testEncodeEeTagsHandlesStringWithoutTags()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = 'This is plain text without any tags.';
        $result = $method->invoke($this->channelFormLib, $input);

        $this->assertEquals('This is plain text without any tags.', $result);
    }

    public function testEncodeEeTagsHandlesOnlyLeftBrackets()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = 'Text with { only left bracket';
        $result = $method->invoke($this->channelFormLib, $input);

        $expected = 'Text with CFORM-ENCODE-LEFT-BRACKET only left bracket';
        $this->assertEquals($expected, $result);
    }

    public function testEncodeEeTagsHandlesOnlyRightBrackets()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = 'Text with } only right bracket';
        $result = $method->invoke($this->channelFormLib, $input);

        $expected = 'Text with CFORM-ENCODE-RIGHT-BRACKET only right bracket';
        $this->assertEquals($expected, $result);
    }

    public function testEncodeEeTagsHandlesComplexTemplateCode()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = '{exp:channel:entries channel="news" limit="10"}{title}{exp:channel:categories}{category_name}{/exp:channel:categories}{if no_results}No entries found{/if}{/exp:channel:entries}';
        $result = $method->invoke($this->channelFormLib, $input);

        // Verify all tags are encoded
        $this->assertStringNotContainsString('{', $result);
        $this->assertStringNotContainsString('}', $result);

        // Count the number of encoded delimiters (should be even number)
        $leftCount = substr_count($result, 'CFORM-ENCODE-LEFT-BRACKET');
        $rightCount = substr_count($result, 'CFORM-ENCODE-RIGHT-BRACKET');
        $this->assertEquals($leftCount, $rightCount);

        // Verify content is preserved between encoded delimiters
        $this->assertStringContainsString('exp:channel:entries', $result);
        $this->assertStringContainsString('title', $result);
        $this->assertStringContainsString('category_name', $result);
    }

    public function testEncodeEeTagsHandlesSpecialCharacters()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = '{exp:channel:entries}©®™{/exp:channel:entries}';
        $result = $method->invoke($this->channelFormLib, $input);

        $expected = 'CFORM-ENCODE-LEFT-BRACKETexp:channel:entriesCFORM-ENCODE-RIGHT-BRACKET©®™CFORM-ENCODE-LEFT-BRACKET/exp:channel:entriesCFORM-ENCODE-RIGHT-BRACKET';
        $this->assertEquals($expected, $result);
    }

    public function testEncodeEeTagsHandlesMultilineContent()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = "{exp:channel:entries}\n{if title}\n{title}\n{/if}\n{/exp:channel:entries}";
        $result = $method->invoke($this->channelFormLib, $input);

        // Verify multiline structure is preserved
        $this->assertStringContainsString("\n", $result);

        // Verify all tags are still encoded
        $this->assertStringNotContainsString('{', $result);
        $this->assertStringNotContainsString('}', $result);

        // Verify content structure
        $this->assertStringContainsString('exp:channel:entries', $result);
        $this->assertStringContainsString('if title', $result);
        $this->assertStringContainsString('title', $result);
    }

    public function testEncodeEeTagsHandlesLargeContent()
    {
        $method = $this->getEncodeEeTagsMethod();

        // Create a large string with many tags
        $largeContent = str_repeat('{exp:channel:entries}{title}{/exp:channel:entries}', 100);
        $result = $method->invoke($this->channelFormLib, $largeContent);

        // Verify all tags are encoded
        $this->assertStringNotContainsString('{', $result);
        $this->assertStringNotContainsString('}', $result);

        // Verify correct number of encodings
        $leftCount = substr_count($result, 'CFORM-ENCODE-LEFT-BRACKET');
        $rightCount = substr_count($result, 'CFORM-ENCODE-RIGHT-BRACKET');
        $this->assertEquals(300, $leftCount); // 100 * 3 left brackets
        $this->assertEquals(300, $rightCount); // 100 * 3 right brackets
    }

    public function testEncodeEeTagsPreservesWhitespace()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = '{ exp:channel:entries } { title } { /exp:channel:entries }';
        $result = $method->invoke($this->channelFormLib, $input);

        $expected = 'CFORM-ENCODE-LEFT-BRACKET exp:channel:entries CFORM-ENCODE-RIGHT-BRACKET CFORM-ENCODE-LEFT-BRACKET title CFORM-ENCODE-RIGHT-BRACKET CFORM-ENCODE-LEFT-BRACKET /exp:channel:entries CFORM-ENCODE-RIGHT-BRACKET';
        $this->assertEquals($expected, $result);
    }

    public function testEncodeEeTagsHandlesModuleTags()
    {
        $method = $this->getEncodeEeTagsMethod();

        $input = '{exp:comment:entries}{exp:member:custom_profile_data}{screen_name}{/exp:member:custom_profile_data}{/exp:comment:entries}';
        $result = $method->invoke($this->channelFormLib, $input);

        // All tags should be encoded
        $this->assertStringNotContainsString('{', $result);
        $this->assertStringNotContainsString('}', $result);

        // Content should be preserved
        $this->assertStringContainsString('exp:comment:entries', $result);
        $this->assertStringContainsString('exp:member:custom_profile_data', $result);
        $this->assertStringContainsString('screen_name', $result);
    }
}
