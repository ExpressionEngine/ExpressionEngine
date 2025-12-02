<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelOverrideWithPreviewDataTest extends ChannelTestBase
{
    private $method;

    protected function setUp(): void
    {
        parent::setUp();

        // Make the private method accessible
        $ref = new ReflectionClass($this->channel);
        $this->method = $ref->getMethod('overrideWithPreviewData');
        \TestReflectionHelper::makeMethodAccessible($this->method);
    }

    public function testReturnsOriginalArrayWhenNoSession()
    {
        // Mock session to return null
        $this->setMock('session', null);

        $original = [['entry_id' => 1, 'title' => 'Test']];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertSame($original, $result);
    }

    public function testReturnsOriginalArrayWhenNoLivePreviewData()
    {
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return false; }
            public function getEntryData(){ return []; }
        });

        $original = [['entry_id' => 1, 'title' => 'Test']];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertSame($original, $result);
    }

    public function testReplacesMatchingRow()
    {
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return true; }
            public function getEntryData(){
                return [
                    'entry_id' => 7,
                    'status' => 'open',
                    'expiration_date' => 0,
                    'url_title' => 'preview-entry',
                    'channel_name' => 'news',
                    'title' => 'Preview Title'
                ];
            }
        });

        $this->setPreviewConditions([]);
        $original = [['entry_id' => 7, 'status' => 'open', 'expiration_date' => 0, 'title' => 'Original Title']];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertCount(1, $result);
        $this->assertSame(7, $result[0]['entry_id']);
        $this->assertSame('Preview Title', $result[0]['title']);
        $this->assertArrayHasKey(7, $this->channel->hidden_fields);
    }

    public function testRemovesClosedEntryWhenShowClosedIsFalse()
    {
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return true; }
            public function getEntryData(){
                return [
                    'entry_id' => 7,
                    'status' => 'closed',
                    'expiration_date' => 0,
                    'url_title' => 'preview-entry'
                ];
            }
        });

        $this->setPreviewConditions([]);
        // Don't set show_closed parameter, so it should default to false
        $original = [['entry_id' => 7, 'status' => 'open', 'expiration_date' => 0]];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertEmpty($result);
    }

    public function testKeepsClosedEntryWhenShowClosedIsTrue()
    {
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return true; }
            public function getEntryData(){
                return [
                    'entry_id' => 7,
                    'status' => 'closed',
                    'expiration_date' => 0,
                    'url_title' => 'preview-entry',
                    'title' => 'Closed Preview'
                ];
            }
        });

        $this->setPreviewConditions([]);
        ee()->TMPL->setMap(['status' => 'closed']);
        $original = [['entry_id' => 7, 'status' => 'open', 'expiration_date' => 0]];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertCount(1, $result);
        $this->assertSame('Closed Preview', $result[0]['title']);
    }

    public function testRemovesExpiredEntryWhenShowExpiredIsFalse()
    {
        $pastTimestamp = time() - (24 * 60 * 60); // 1 day ago

        $this->setMock('LivePreview', new class($pastTimestamp) {
            private $pastTimestamp;
            public function __construct($pastTimestamp) {
                $this->pastTimestamp = $pastTimestamp;
            }
            public function hasEntryData(){ return true; }
            public function getEntryData(){
                return [
                    'entry_id' => 7,
                    'status' => 'open',
                    'expiration_date' => $this->pastTimestamp,
                    'url_title' => 'preview-entry'
                ];
            }
        });

        $this->setPreviewConditions([]);
        // Don't set show_expired parameter, so it should default to false
        $original = [['entry_id' => 7, 'status' => 'open', 'expiration_date' => 0]];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertEmpty($result);
    }

    public function testKeepsExpiredEntryWhenShowExpiredIsTrue()
    {
        $pastTimestamp = time() - (24 * 60 * 60); // 1 day ago

        $this->setMock('LivePreview', new class($pastTimestamp) {
            private $pastTimestamp;
            public function __construct($pastTimestamp) {
                $this->pastTimestamp = $pastTimestamp;
            }
            public function hasEntryData(){ return true; }
            public function getEntryData(){
                return [
                    'entry_id' => 7,
                    'status' => 'open',
                    'expiration_date' => $this->pastTimestamp,
                    'url_title' => 'preview-entry',
                    'title' => 'Expired Preview'
                ];
            }
        });

        $this->setPreviewConditions([]);
        ee()->TMPL->setMap(['show_expired' => 'yes']);
        $original = [['entry_id' => 7, 'status' => 'open', 'expiration_date' => 0]];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertCount(1, $result);
        $this->assertSame('Expired Preview', $result[0]['title']);
    }

    public function testAddsNewEntryWhenNotFoundInResults()
    {
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return true; }
            public function getEntryData(){
                return [
                    'entry_id' => 999, // New entry not in original results
                    'status' => 'open',
                    'expiration_date' => 0,
                    'url_title' => 'new-preview-entry',
                    'title' => 'New Preview Entry'
                ];
            }
        });

        $this->setPreviewConditions([]);
        $this->channel->query_string = '999'; // Set query string to match the new entry
        $original = [['entry_id' => 1, 'title' => 'Existing Entry']];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertCount(2, $result);
        $this->assertSame(999, $result[0]['entry_id']); // Should be added at the beginning
        $this->assertSame('New Preview Entry', $result[0]['title']);
        $this->assertSame(1, $result[1]['entry_id']); // Original entry should still be there
    }

    public function testAddsNewEntryByUrlTitle()
    {
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return true; }
            public function getEntryData(){
                return [
                    'entry_id' => 999,
                    'status' => 'open',
                    'expiration_date' => 0,
                    'url_title' => 'new-preview-entry',
                    'title' => 'New Preview Entry'
                ];
            }
        });

        $this->setPreviewConditions([]);
        $this->channel->query_string = 'new-preview-entry'; // Set query string to match URL title
        $original = [['entry_id' => 1, 'title' => 'Existing Entry']];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertCount(2, $result);
        $this->assertSame(999, $result[0]['entry_id']);
        $this->assertSame('New Preview Entry', $result[0]['title']);
    }

    public function testDoesNotAddNewEntryWhenConditionsNotMet()
    {
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return true; }
            public function getEntryData(){
                return [
                    'entry_id' => 999,
                    'status' => 'open',
                    'expiration_date' => 0,
                    'url_title' => 'new-preview-entry',
                    'title' => 'New Preview Entry'
                ];
            }
        });

        $this->setPreviewConditions([]);
        $this->channel->query_string = 'different-url'; // Query string doesn't match
        $original = [['entry_id' => 1, 'title' => 'Existing Entry']];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['entry_id']); // Only original entry should be there
    }

    public function testHandlesPreviewConditions()
    {
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return true; }
            public function getEntryData(){
                return [
                    'entry_id' => 999,
                    'status' => 'open',
                    'expiration_date' => 0,
                    'url_title' => 'new-preview-entry',
                    'title' => 'New Preview Entry',
                    'channel_name' => 'news'
                ];
            }
        });

        // Set preview conditions that should pass
        $this->setPreviewConditions(['t.channel_name = \'news\'']);
        $this->channel->query_string = '999';
        $original = [['entry_id' => 1, 'title' => 'Existing Entry']];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertCount(2, $result);
        $this->assertSame(999, $result[0]['entry_id']);
    }

    public function testSkipsEntryWhenPreviewConditionsFail()
    {
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return true; }
            public function getEntryData(){
                return [
                    'entry_id' => 999,
                    'status' => 'open',
                    'expiration_date' => 0,
                    'url_title' => 'new-preview-entry',
                    'title' => 'New Preview Entry',
                    'channel_name' => 'blog' // Different channel
                ];
            }
        });

        // Set preview conditions that should fail
        $this->setPreviewConditions(['t.channel_name = \'news\'']);
        $this->channel->query_string = '999';
        $original = [['entry_id' => 1, 'title' => 'Existing Entry']];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['entry_id']); // Only original entry should be there
    }

    public function testHandlesHiddenFields()
    {
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return true; }
            public function getEntryData(){
                return [
                    'entry_id' => 7,
                    'status' => 'open',
                    'expiration_date' => 0,
                    'url_title' => 'preview-entry',
                    'field_hide_title' => '1',
                    'field_hide_summary' => '1',
                    'regular_field' => 'value'
                ];
            }
        });

        $this->setPreviewConditions([]);
        $original = [['entry_id' => 7, 'status' => 'open', 'expiration_date' => 0]];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertArrayHasKey(7, $this->channel->hidden_fields);
        $this->assertContains('title', $this->channel->hidden_fields[7]);
        $this->assertContains('summary', $this->channel->hidden_fields[7]);
        $this->assertNotContains('regular_field', $this->channel->hidden_fields[7]);
    }

    public function testHandlesComplexPreviewConditionsWithOR()
    {
        $this->setMock('LivePreview', new class {
            public function hasEntryData(){ return true; }
            public function getEntryData(){
                return [
                    'entry_id' => 999,
                    'status' => 'open',
                    'expiration_date' => 0,
                    'url_title' => 'new-preview-entry',
                    'channel_name' => 'blog'
                ];
            }
        });

        // Set preview conditions with OR logic - OR conditions are within a single string
        $this->setPreviewConditions(['(t.channel_name = \'news\' OR t.channel_name = \'blog\')']);
        $this->channel->query_string = '999';
        $original = [['entry_id' => 1, 'title' => 'Existing Entry']];
        $result = $this->method->invoke($this->channel, $original);

        $this->assertCount(2, $result);
        $this->assertSame(999, $result[0]['entry_id']);
    }

    public function testHandlesPreviewDataPassesConditionMethod()
    {
        $ref = new ReflectionClass($this->channel);
        $conditionMethod = $ref->getMethod('previewDataPassesCondition');
        \TestReflectionHelper::makeMethodAccessible($conditionMethod);

        $data = ['channel_name' => 'news', 'status' => 'open'];

        // Test equality condition
        $result = $conditionMethod->invoke($this->channel, 't.channel_name = \'news\'', $data);
        $this->assertTrue($result);

        // Test inequality condition
        $result = $conditionMethod->invoke($this->channel, 't.channel_name != \'blog\'', $data);
        $this->assertTrue($result);

        // Test greater than condition
        $data['entry_date'] = 1609459200;
        $result = $conditionMethod->invoke($this->channel, 't.entry_date > 1609459100', $data);
        $this->assertTrue($result);

        // Test IN condition
        $result = $conditionMethod->invoke($this->channel, 't.status IN (\'open\', \'draft\')', $data);
        $this->assertTrue($result);

        // Test OR condition
        $result = $conditionMethod->invoke($this->channel, '(t.channel_name = \'news\' OR t.channel_name = \'blog\')', $data);
        $this->assertTrue($result);
    }

    public function testHandlesArrayValuesInConditions()
    {
        $ref = new ReflectionClass($this->channel);
        $conditionMethod = $ref->getMethod('previewDataPassesCondition');
        \TestReflectionHelper::makeMethodAccessible($conditionMethod);

        $data = ['categories' => [1, 2, 3]];

        // Test array IN condition
        $result = $conditionMethod->invoke($this->channel, 't.categories IN (1,2,4)', $data);
        $this->assertTrue($result);

        // Test array NOT IN condition
        $result = $conditionMethod->invoke($this->channel, 't.categories != 5', $data);
        $this->assertTrue($result);
    }

    private function setPreviewConditions(array $conditions)
    {
        $previewConditionsProp = new ReflectionProperty($this->channel, 'preview_conditions');
        \TestReflectionHelper::makePropertyAccessible($previewConditionsProp);
        $previewConditionsProp->setValue($this->channel, $conditions);
    }
}


