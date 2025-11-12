<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateParseInlineErrorsTest extends EE_TemplateTestBase
{
    public function testParseInlineErrorsNoInlineErrorsParam()
    {
        // Test when inline_errors parameter is not 'yes'
        $str = 'Normal content {error:username} without inline errors enabled';

        // Mock TMPL fetch_param to return something other than 'yes'
        $tmplMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_param'])
            ->getMock();
        $tmplMock->method('fetch_param')->with('inline_errors')->willReturn('no');
        ee()->setMock('TMPL', $tmplMock);

        $result = $this->template->parse_inline_errors($str);

        $this->assertEquals($str, $result);
    }

    public function testParseInlineErrorsNoErrorMarker()
    {
        // Test when inline_errors is enabled but no {error:} markers exist
        $str = 'Content without error markers but inline errors enabled';

        $tmplMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_param'])
            ->getMock();
        $tmplMock->method('fetch_param')->with('inline_errors')->willReturn('yes');
        ee()->setMock('TMPL', $tmplMock);

        $result = $this->template->parse_inline_errors($str);

        $this->assertEquals($str, $result);
    }

    public function testParseInlineErrorsNoSession()
    {
        // Test when session is not available
        $str = 'Content {error:username} with no session';

        $tmplMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_param'])
            ->getMock();
        $tmplMock->method('fetch_param')->with('inline_errors')->willReturn('yes');
        ee()->setMock('TMPL', $tmplMock);

        // Mock session as null/empty
        ee()->setMock('session', null);

        $result = $this->template->parse_inline_errors($str);

        $this->assertEquals($str, $result);
    }

    public function testParseInlineErrorsNoFlashdata()
    {
        // Test when session exists but no flashdata
        $str = 'Content {error:username} with session but no flashdata';

        $tmplMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_param'])
            ->getMock();
        $tmplMock->method('fetch_param')->with('inline_errors')->willReturn('yes');
        ee()->setMock('TMPL', $tmplMock);

        // Mock session with no flashdata
        $sessionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['flashdata'])
            ->getMock();
        $sessionMock->method('flashdata')
            ->willReturnCallback(function($key) {
                if ($key === 'errors') return null;
                return null;
            });
        ee()->setMock('session', $sessionMock);

        $result = $this->template->parse_inline_errors($str);

        $this->assertEquals($str, $result);
    }

    public function testParseInlineErrorsSuccessfulProcessing()
    {
        // Test successful error processing
        $str = 'Form field: {error:username}<br>Another: {error:email}';

        $tmplMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_param', 'parse_variables'])
            ->getMock();
        $tmplMock->method('fetch_param')->with('inline_errors')->willReturn('yes');
        $tmplMock->expects($this->exactly(2))
            ->method('parse_variables')
            ->willReturnOnConsecutiveCalls(
                'Form field: Username is required<br>Another: {error:email}',
                'Form field: Username is required<br>Another: Invalid email'
            );
        ee()->setMock('TMPL', $tmplMock);

        // Mock session with flashdata
        $sessionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['flashdata'])
            ->getMock();
        $sessionMock->method('flashdata')
            ->willReturnCallback(function($key) {
                if ($key === 'errors') return ['username' => 'Username is required', 'email' => 'Invalid email'];
                if ($key === 'old') return ['old_username' => 'previous_value', 'old_email' => 'old@email.com'];
                return null;
            });
        ee()->setMock('session', $sessionMock);

        $result = $this->template->parse_inline_errors($str);

        $this->assertEquals('Form field: Username is required<br>Another: Invalid email', $result);
    }

    public function testParseInlineErrorsMultipleErrorMarkers()
    {
        // Test multiple error markers in content
        $str = '{error:first_name} {error:last_name} {error:email} {error:password}';

        $tmplMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_param', 'parse_variables'])
            ->getMock();
        $tmplMock->method('fetch_param')->with('inline_errors')->willReturn('yes');
        $tmplMock->expects($this->exactly(2))
            ->method('parse_variables')
            ->willReturnOnConsecutiveCalls(
                'First name error {error:last_name} {error:email} {error:password}',
                'First name error Last name error Email error Password error'
            );
        ee()->setMock('TMPL', $tmplMock);

        $errors = [
            'first_name' => 'First name error',
            'last_name' => 'Last name error',
            'email' => 'Email error',
            'password' => 'Password error'
        ];

        $sessionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['flashdata'])
            ->getMock();
        $sessionMock->method('flashdata')
            ->willReturnCallback(function($key) use ($errors) {
                if ($key === 'errors') return $errors;
                if ($key === 'old') return [];
                return null;
            });
        ee()->setMock('session', $sessionMock);

        $result = $this->template->parse_inline_errors($str);

        $this->assertEquals('First name error Last name error Email error Password error', $result);
    }

    public function testParseInlineErrorsEmptyErrorValues()
    {
        // Test with empty error messages
        $str = 'Field: {error:empty_field}';

        $tmplMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_param', 'parse_variables'])
            ->getMock();
        $tmplMock->method('fetch_param')->with('inline_errors')->willReturn('yes');
        $tmplMock->expects($this->exactly(2))
            ->method('parse_variables')
            ->willReturn('Field: ');
        ee()->setMock('TMPL', $tmplMock);

        $sessionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['flashdata'])
            ->getMock();
        $sessionMock->method('flashdata')
            ->willReturnCallback(function($key) {
                if ($key === 'errors') return ['empty_field' => ''];
                if ($key === 'old') return [];
                return null;
            });
        ee()->setMock('session', $sessionMock);

        $result = $this->template->parse_inline_errors($str);

        $this->assertEquals('Field: ', $result);
    }

    public function testParseInlineErrorsSpecialCharacters()
    {
        // Test with HTML entities and special characters in errors
        $str = 'Error: {error:special}';

        $tmplMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_param', 'parse_variables'])
            ->getMock();
        $tmplMock->method('fetch_param')->with('inline_errors')->willReturn('yes');
        $tmplMock->expects($this->exactly(2))
            ->method('parse_variables')
            ->willReturn('Error: Special chars: <>&"\' café 🚀');
        ee()->setMock('TMPL', $tmplMock);

        $sessionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['flashdata'])
            ->getMock();
        $sessionMock->method('flashdata')
            ->willReturnCallback(function($key) {
                if ($key === 'errors') return ['special' => 'Special chars: <>&"\' café 🚀'];
                if ($key === 'old') return [];
                return null;
            });
        ee()->setMock('session', $sessionMock);

        $result = $this->template->parse_inline_errors($str);

        $this->assertEquals('Error: Special chars: <>&"\' café 🚀', $result);
    }

    public function testParseInlineErrorsNestedBraces()
    {
        // Test with nested braces in error content
        $str = 'Complex: {error:complex}';

        $tmplMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_param', 'parse_variables'])
            ->getMock();
        $tmplMock->method('fetch_param')->with('inline_errors')->willReturn('yes');
        $tmplMock->expects($this->exactly(2))
            ->method('parse_variables')
            ->willReturn('Complex: Error with {nested} braces and {if condition}logic{/if}');
        ee()->setMock('TMPL', $tmplMock);

        $sessionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['flashdata'])
            ->getMock();
        $sessionMock->method('flashdata')
            ->willReturnCallback(function($key) {
                if ($key === 'errors') return ['complex' => 'Error with {nested} braces and {if condition}logic{/if}'];
                if ($key === 'old') return [];
                return null;
            });
        ee()->setMock('session', $sessionMock);

        $result = $this->template->parse_inline_errors($str);

        $this->assertEquals('Complex: Error with {nested} braces and {if condition}logic{/if}', $result);
    }

    public function testParseInlineErrorsLargeErrorSet()
    {
        // Test performance with many errors
        $str = str_repeat('{error:field_', 100) . str_repeat('} ', 100);

        $errors = [];
        for ($i = 0; $i < 100; $i++) {
            $errors['field_' . $i] = 'Error ' . $i;
        }

        $tmplMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_param', 'parse_variables'])
            ->getMock();
        $tmplMock->method('fetch_param')->with('inline_errors')->willReturn('yes');
        $tmplMock->expects($this->exactly(2))
            ->method('parse_variables')
            ->willReturn(str_repeat('Error X ', 100));
        ee()->setMock('TMPL', $tmplMock);

        $sessionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['flashdata'])
            ->getMock();
        $sessionMock->method('flashdata')
            ->willReturnCallback(function($key) use ($errors) {
                if ($key === 'errors') return $errors;
                if ($key === 'old') return [];
                return null;
            });
        ee()->setMock('session', $sessionMock);

        $result = $this->template->parse_inline_errors($str);

        $this->assertStringContainsString('Error', $result);
        $this->assertStringNotContainsString('{error:', $result);
    }

    public function testParseInlineErrorsRtlText()
    {
        // Test with RTL languages like Arabic/Hebrew
        $str = 'שגיאה: {error:name} خطأ: {error:email}';

        $tmplMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_param', 'parse_variables'])
            ->getMock();
        $tmplMock->method('fetch_param')->with('inline_errors')->willReturn('yes');
        $tmplMock->expects($this->exactly(2))
            ->method('parse_variables')
            ->willReturn('שגיאה: RTL Error خطأ: RTL Error');
        ee()->setMock('TMPL', $tmplMock);

        $sessionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['flashdata'])
            ->getMock();
        $sessionMock->method('flashdata')
            ->willReturnCallback(function($key) {
                if ($key === 'errors') return ['name' => 'RTL Error', 'email' => 'RTL Error'];
                if ($key === 'old') return [];
                return null;
            });
        ee()->setMock('session', $sessionMock);

        $result = $this->template->parse_inline_errors($str);

        // Should handle RTL text correctly
        $this->assertStringContainsString('RTL Error', $result);
        $this->assertStringNotContainsString('{error:', $result);
    }

    public function testParseInlineErrorsMixedScripts()
    {
        // Test with mixed scripts (LTR and RTL)
        $str = 'English: {error:name} العربية: {error:email} 中文: {error:phone}';

        $tmplMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_param', 'parse_variables'])
            ->getMock();
        $tmplMock->method('fetch_param')->with('inline_errors')->willReturn('yes');
        $tmplMock->expects($this->exactly(2))
            ->method('parse_variables')
            ->willReturn('English: Error1 العربية: Error2 中文: Error3');
        ee()->setMock('TMPL', $tmplMock);

        $errors = [
            'name' => 'Error1',
            'email' => 'Error2',
            'phone' => 'Error3'
        ];

        $sessionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['flashdata'])
            ->getMock();
        $sessionMock->method('flashdata')
            ->willReturnCallback(function($key) use ($errors) {
                if ($key === 'errors') return $errors;
                if ($key === 'old') return [];
                return null;
            });
        ee()->setMock('session', $sessionMock);

        $result = $this->template->parse_inline_errors($str);

        // Should handle mixed scripts correctly
        $this->assertStringContainsString('Error1', $result);
        $this->assertStringContainsString('Error2', $result);
        $this->assertStringContainsString('Error3', $result);
        $this->assertStringContainsString('العربية', $result);
        $this->assertStringContainsString('中文', $result);
    }

    public function testParseInlineErrorsUnicodeFieldNames()
    {
        // Test with Unicode characters in field names
        $str = 'Name: {error:nombre} Email: {error:email}';

        $tmplMock = $this->getMockBuilder('stdClass')
            ->setMethods(['fetch_param', 'parse_variables'])
            ->getMock();
        $tmplMock->method('fetch_param')->with('inline_errors')->willReturn('yes');
        $tmplMock->expects($this->exactly(2))
            ->method('parse_variables')
            ->willReturn('Name: José Email: test@example.com');
        ee()->setMock('TMPL', $tmplMock);

        $errors = ['nombre' => 'José'];
        $old = ['email' => 'test@example.com'];

        $sessionMock = $this->getMockBuilder('stdClass')
            ->setMethods(['flashdata'])
            ->getMock();
        $sessionMock->method('flashdata')
            ->willReturnCallback(function($key) use ($errors, $old) {
                if ($key === 'errors') return $errors;
                if ($key === 'old') return $old;
                return null;
            });
        ee()->setMock('session', $sessionMock);

        $result = $this->template->parse_inline_errors($str);

        // Should handle Unicode field names correctly
        $this->assertStringContainsString('José', $result);
        $this->assertStringContainsString('test@example.com', $result);
    }

    public function testParseInlineErrorsMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'parse_inline_errors');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(1, $parameters);

        $this->assertEquals('str', $parameters[0]->getName());
    }
}
