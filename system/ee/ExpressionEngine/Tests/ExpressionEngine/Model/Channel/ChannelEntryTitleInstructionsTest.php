<?php

namespace ExpressionEngine\Tests\Model\Channel;

use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Model\Content\Display\LayoutInterface;
use ExpressionEngine\Model\Content\FieldFacade;
use ExpressionEngine\Service\Formatter\FormatterFactory;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ChannelEntryTitleInstructionsTest extends TestCase
{
    /**
     * Register the real formatter with isolated language and session dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $lang = m::mock('EE_Lang');
        $lang->shouldReceive('load');

        ee()->setMock('Format', new FormatterFactory($lang, m::mock('EE_Session'), [], 0));
    }

    /**
     * Release formatter dependencies after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
        m::close();
    }

    /**
     * Format title instructions without changing their stored value.
     *
     * @param string|null $stored The saved title instructions.
     * @param string $expected The HTML prepared for display.
     * @return void
     *
     * @dataProvider titleInstructionsProvider
     */
    public function testTitleInstructionsAreFormattedOnlyForDisplay($stored, $expected): void
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

    /**
     * Provide saved instructions and their expected display markup.
     *
     * @return array
     */
    public static function titleInstructionsProvider(): array
    {
        return [
            'ordinary instructions' => [
                'Enter a short descriptive title.',
                'Enter a short descriptive title.',
            ],
            'basic formatting is preserved' => [
                '<strong>Title</strong><br><u>Required</u> <em>Short</em>',
                '<strong>Title</strong><br><u>Required</u> <i>Short</i>',
            ],
            'formatting attributes are removed' => [
                '<b class="example" onclick="">Title</b>',
                '<b>Title</b>',
            ],
            'unsupported markup remains literal' => [
                '<button type="button">Example</button>',
                '&lt;button type=&quot;button&quot;&gt;Example&lt;/button&gt;',
            ],
            'formatting is contained within the instructions' => [
                '</em><b>Title',
                '<b>Title</b>',
            ],
            'Unicode, quotes, ampersands and entity-shaped text' => [
                '東京 "Title" \'Example\' & &lt;strong&gt;',
                '東京 &quot;Title&quot; &#039;Example&#039; &amp; &amp;lt;strong&amp;gt;',
            ],
            'empty instructions' => ['', ''],
            'unset instructions' => [null, ''],
        ];
    }
}
