<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateParseSimpleSegmentConditionalsTest extends EE_TemplateTestBase
{
    /**
     * Test parse_simple_segment_conditionals method exists
     */
    public function testParseSimpleSegmentConditionalsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'parse_simple_segment_conditionals'));
        $this->assertTrue(is_callable([$this->template, 'parse_simple_segment_conditionals']));
    }

    /**
     * Test parse_simple_segment_conditionals with basic segment conditional
     */
    public function testParseSimpleSegmentConditionalsBasicConditional()
    {
        // Mock URI to return specific segments
        $uriMock = $this->getMockBuilder('stdClass')
            ->setMethods(['segment'])
            ->getMock();
        $uriMock->method('segment')
            ->willReturnCallback(function($n) {
                $segments = ['', 'about', 'us', 'contact'];
                return isset($segments[$n]) ? $segments[$n] : false;
            });
        ee()->setMock('uri', $uriMock);

        // Mock functions to return processed conditional
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['prep_conditionals'])
            ->getMock();
        $functionsMock->method('prep_conditionals')
            ->with(
                '{if segment_2 == "us"}About Us Content{/if}',
                [
                    'segment_1' => 'about',
                    'segment_2' => 'us',
                    'segment_3' => 'contact',
                    'segment_4' => false,
                    'segment_5' => false,
                    'segment_6' => false,
                    'segment_7' => false,
                    'segment_8' => false,
                    'segment_9' => false
                ]
            )
            ->willReturn('About Us Content');
        ee()->setMock('functions', $functionsMock);

        $input = '{if segment_2 == "us"}About Us Content{/if}';
        $expected = 'About Us Content';

        $result = $this->template->parse_simple_segment_conditionals($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test parse_simple_segment_conditionals with false conditional
     */
    public function testParseSimpleSegmentConditionalsFalseConditional()
    {
        // Mock URI to return specific segments
        $uriMock = $this->getMockBuilder('stdClass')
            ->setMethods(['segment'])
            ->getMock();
        $uriMock->method('segment')
            ->willReturnCallback(function($n) {
                $segments = ['', 'about', 'team', 'contact'];
                return isset($segments[$n]) ? $segments[$n] : false;
            });
        ee()->setMock('uri', $uriMock);

        // Mock functions to return unprocessed conditional (false condition)
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['prep_conditionals'])
            ->getMock();
        $functionsMock->method('prep_conditionals')
            ->with(
                '{if segment_2 == "us"}About Us Content{/if}',
                [
                    'segment_1' => 'about',
                    'segment_2' => 'team', // This doesn't match "us"
                    'segment_3' => 'contact',
                    'segment_4' => false,
                    'segment_5' => false,
                    'segment_6' => false,
                    'segment_7' => false,
                    'segment_8' => false,
                    'segment_9' => false
                ]
            )
            ->willReturn('{if segment_2 == "us"}About Us Content{/if}'); // Return unchanged
        ee()->setMock('functions', $functionsMock);

        $input = '{if segment_2 == "us"}About Us Content{/if}';
        $expected = '{if segment_2 == "us"}About Us Content{/if}';

        $result = $this->template->parse_simple_segment_conditionals($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test parse_simple_segment_conditionals with no conditionals
     */
    public function testParseSimpleSegmentConditionalsNoConditionals()
    {
        // Mock functions to return string unchanged when no conditionals
        $functionsMock = $this->getMockBuilder('stdClass')
            ->setMethods(['prep_conditionals'])
            ->getMock();
        $functionsMock->method('prep_conditionals')
            ->willReturnArgument(0); // Return the input unchanged
        ee()->setMock('functions', $functionsMock);

        $input = '<html><body>Regular content without conditionals</body></html>';
        $expected = '<html><body>Regular content without conditionals</body></html>';

        $result = $this->template->parse_simple_segment_conditionals($input);

        $this->assertEquals($expected, $result);
    }
}
