<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelNextEntryTest extends ChannelTestBase
{
    public function testNextEntryCallsNextPrevEntryWithNext()
    {
        $expectedResult = 'next_entry_data';

        // Mock the next_prev_entry method
        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['next_prev_entry'])
            ->getMock();

        $this->channel->expects($this->once())
            ->method('next_prev_entry')
            ->with('next')
            ->willReturn($expectedResult);

        $result = $this->channel->next_entry();

        $this->assertEquals($expectedResult, $result);
    }
}

