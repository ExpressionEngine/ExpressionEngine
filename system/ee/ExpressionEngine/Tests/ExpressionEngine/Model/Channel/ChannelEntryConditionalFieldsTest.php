<?php

namespace ExpressionEngine\Tests\Model\Channel;

use ExpressionEngine\Model\Channel\Channel;
use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Model\Channel\ChannelField;
use ExpressionEngine\Model\Content\FieldFacade;
use ExpressionEngine\Service\ConditionalFields\Evaluator;
use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Service\Model\Facade;
use ExpressionEngine\Service\Model\MetaDataReader;
use ExpressionEngine\Service\Model\Query\Builder;
use ExpressionEngine\Service\Model\Relation\BelongsTo;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use TestReflectionHelper;

class ChannelEntryConditionalFieldsTest extends TestCase
{
    /**
     * Reset the evaluator registered for the test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * Finish loading the channel before evaluating field rules on a new entry.
     *
     * @return void
     */
    public function testEvaluatesConditionalFieldsAfterReloadingTheChannel(): void
    {
        $conditionSets = new Collection();
        $field = $this->createMock(ChannelField::class);
        $field->method('getId')->willReturn(12);
        $field->method('__get')->with('FieldConditionSets')->willReturn($conditionSets);

        $channel = $this->getMockBuilder(Channel::class)
            ->setConstructorArgs([['channel_id' => 4]])
            ->onlyMethods(['getAllCustomConditionalFields'])
            ->getMock();
        $channel->expects($this->once())->method('getAllCustomConditionalFields')->willReturn(new Collection([$field]));

        $channelIdField = $this->createMock(FieldFacade::class);
        $channelIdField->expects($this->once())->method('setData')->with(4);
        $conditionalField = $this->createMock(FieldFacade::class);
        $conditionalField->expects($this->once())->method('setConditionSets')->with($conditionSets);
        $conditionalField->expects($this->once())->method('setHidden')->with('y');

        $evaluator = $this->createMock(Evaluator::class);
        $evaluator->expects($this->once())->method('evaluate')->with($conditionalField)->willReturn(false);
        ee()->setMock('ee:ConditionalFieldEvaluator', $evaluator);

        $entry = $this->getMockBuilder(ChannelEntry::class)
            ->setConstructorArgs([['channel_id' => 4]])
            ->onlyMethods(['getDefaultFields', 'getHiddenFieldIds'])
            ->getMock();
        $entry->method('getDefaultFields')->willReturn(['channel_id' => []]);
        $entry->method('getHiddenFieldIds')->willReturn([12]);

        $facades = new ReflectionProperty(ChannelEntry::class, '_field_facades');
        TestReflectionHelper::makePropertyAccessible($facades);
        $facades->setValue($entry, ['channel_id' => $channelIdField, 'field_id_12' => $conditionalField]);
        $loaded = new ReflectionProperty(ChannelEntry::class, '_custom_fields_loaded');
        TestReflectionHelper::makePropertyAccessible($loaded);
        $loaded->setValue($entry, true);

        $relation = new BelongsTo(
            new MetaDataReader('ee:ChannelEntry', ChannelEntry::class),
            new MetaDataReader('ee:Channel', Channel::class),
            'Channel',
            ['key' => 'channel_id']
        );
        $query = $this->createMock(Builder::class);
        $query->expects($this->once())->method('setLazyConstraint')->with($relation, $entry);
        $query->expects($this->once())->method('all')->willReturn(new Collection([$channel]));
        $facade = $this->createMock(Facade::class);
        $facade->expects($this->once())->method('get')->with('ee:Channel')->willReturn($query);
        $entry->setFacade($facade);
        $entry->setAssociation('Channel', $relation->createAssociation());

        $this->assertSame([12], $entry->evaluateConditionalFields());
        $this->assertSame($channel, $entry->Channel);
    }
}
