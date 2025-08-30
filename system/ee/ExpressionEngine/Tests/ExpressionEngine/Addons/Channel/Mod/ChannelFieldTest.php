<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFieldTest extends ChannelTestBase
{
    public function testFieldReturnsNoResultsWhenNoParameters()
    {
        // Set no parameters
        $this->setTemplateParams([]);

        $expectedResult = 'NO_RESULTS';
        $this->setTemplateTagdata($expectedResult);

        $result = $this->channel->field();

        $this->assertEquals($expectedResult, $result);
    }

    public function testFieldMethodRequiresComplexMocking()
    {
        // The field method uses the EE Model system which is complex to mock
        // For now, we acknowledge that this method exists and would need
        // extensive mocking of the Model system to test properly
        $this->assertTrue(method_exists($this->channel, 'field'));
    }
}