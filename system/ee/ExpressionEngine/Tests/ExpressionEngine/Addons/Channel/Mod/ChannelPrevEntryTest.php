<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelPrevEntryTest extends ChannelTestBase
{
    public function testPrevEntryCallsNextPrevEntryWithPrev()
    {
        $expectedResult = 'prev_entry_data';

        // Mock the next_prev_entry method
        $this->channel = $this->getMockBuilder('Channel')
            ->disableOriginalConstructor()
            ->setMethods(['next_prev_entry'])
            ->getMock();

        $this->channel->expects($this->once())
            ->method('next_prev_entry')
            ->with('prev')
            ->willReturn($expectedResult);

        $result = $this->channel->prev_entry();

        $this->assertEquals($expectedResult, $result);
    }
}

