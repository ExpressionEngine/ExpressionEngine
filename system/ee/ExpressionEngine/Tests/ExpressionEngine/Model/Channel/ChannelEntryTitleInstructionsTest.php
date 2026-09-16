<?php

namespace ExpressionEngine\Tests\Model\Channel;

use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Model\Content\Display\LayoutInterface;
use ExpressionEngine\Model\Content\FieldFacade;
use PHPUnit\Framework\TestCase;

class ChannelEntryTitleInstructionsTest extends TestCase
{
    /**
     * @dataProvider titleInstructionsProvider
     */
    public function testTitleInstructionsAreEncodedOnlyForDisplay($stored, $expected): void
    {
        $channel = (object) [
            'title_field_label' => 'Title',
            'title_field_instructions' => $stored,
            'enforce_auto_url_title' => false,
            'sticky_enabled' => false,
        ];

        // Keep real field metadata access without loading the fieldtype API.
        $title = $this->getMockBuilder(FieldFacade::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $title->setItem('field_instructions', '');

        $entry = $this->getMockBuilder(ChannelEntry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get', 'getCustomField', 'getCustomFields', 'usesCustomFields'])
            ->getMock();
        $entry->method('__get')->with('Channel')->willReturn($channel);
        $entry->method('getCustomField')->with('title')->willReturn($title);
        $entry->method('getCustomFields')->willReturn(['title' => $title]);

        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('transform')->willReturnArgument(0);

        for ($render = 0; $render < 2; $render++) {
            $fields = $entry->getDisplay($layout);

            // The publish form emits getInstructions() directly inside an em element.
            $this->assertSame($expected, $fields['title']->getInstructions());
            $this->assertSame($stored, $channel->title_field_instructions);
        }
    }

    public static function titleInstructionsProvider(): array
    {
        return [
            'ordinary instructions' => [
                'Enter a short descriptive title.',
                'Enter a short descriptive title.',
            ],
            'inert markup remains literal' => [
                '<strong>EE-INSTRUCTIONS-CHECK</strong>',
                '&lt;strong&gt;EE-INSTRUCTIONS-CHECK&lt;/strong&gt;',
            ],
            'Unicode, quotes, ampersands and entity-shaped text' => [
                '東京 "Title" \'Example\' & &lt;strong&gt;',
                '東京 &quot;Title&quot; &#039;Example&#039; &amp; &amp;lt;strong&amp;gt;',
            ],
        ];
    }
}
