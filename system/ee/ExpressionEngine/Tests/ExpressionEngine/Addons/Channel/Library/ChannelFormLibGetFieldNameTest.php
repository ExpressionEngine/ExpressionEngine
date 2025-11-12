<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibGetFieldNameTest extends ChannelFormLibTestBase
{
    public function testGetFieldNameReturnsCorrectFieldNameForValidId()
    {
        $this->channelFormLib->custom_field_names = [
            1 => 'title_field',
            2 => 'content_field',
            3 => 'date_field'
        ];

        $result = $this->channelFormLib->get_field_name(1);
        $this->assertEquals('title_field', $result);

        $result = $this->channelFormLib->get_field_name(2);
        $this->assertEquals('content_field', $result);

        $result = $this->channelFormLib->get_field_name(3);
        $this->assertEquals('date_field', $result);
    }

    public function testGetFieldNameReturnsFalseForInvalidId()
    {
        $this->channelFormLib->custom_field_names = [
            1 => 'existing_field'
        ];

        $result = $this->channelFormLib->get_field_name(999);
        $this->assertFalse($result);

        $result = $this->channelFormLib->get_field_name(0);
        $this->assertFalse($result);

        $result = $this->channelFormLib->get_field_name(-1);
        $this->assertFalse($result);
    }

    public function testGetFieldNameHandlesNumericVsStringIds()
    {
        $this->channelFormLib->custom_field_names = [
            1 => 'field_one',
            '2' => 'field_two',
            3 => 'field_three'
        ];

        // Test with integer ID
        $result = $this->channelFormLib->get_field_name(1);
        $this->assertEquals('field_one', $result);

        // Test with string numeric ID
        $result = $this->channelFormLib->get_field_name('2');
        $this->assertEquals('field_two', $result);

        // Test with integer ID that was stored as string
        $result = $this->channelFormLib->get_field_name(3);
        $this->assertEquals('field_three', $result);
    }

    public function testGetFieldNameWithEmptyCustomFieldNames()
    {
        $this->channelFormLib->custom_field_names = [];

        $result = $this->channelFormLib->get_field_name(1);
        $this->assertFalse($result);
    }

    public function testGetFieldNameWithNullCustomFieldNames()
    {
        $this->channelFormLib->custom_field_names = null;

        $result = $this->channelFormLib->get_field_name(1);
        $this->assertFalse($result);
    }

    public function testGetFieldNameWithLargeFieldIds()
    {
        $this->channelFormLib->custom_field_names = [
            1000 => 'large_id_field',
            9999 => 'very_large_id_field'
        ];

        $result = $this->channelFormLib->get_field_name(1000);
        $this->assertEquals('large_id_field', $result);

        $result = $this->channelFormLib->get_field_name(9999);
        $this->assertEquals('very_large_id_field', $result);
    }

    public function testGetFieldNameWithFieldNamesContainingSpecialCharacters()
    {
        $this->channelFormLib->custom_field_names = [
            1 => 'field-with-dashes',
            2 => 'field_with_underscores',
            3 => 'field.with.dots',
            4 => 'field[with]brackets'
        ];

        $result = $this->channelFormLib->get_field_name(1);
        $this->assertEquals('field-with-dashes', $result);

        $result = $this->channelFormLib->get_field_name(2);
        $this->assertEquals('field_with_underscores', $result);

        $result = $this->channelFormLib->get_field_name(3);
        $this->assertEquals('field.with.dots', $result);

        $result = $this->channelFormLib->get_field_name(4);
        $this->assertEquals('field[with]brackets', $result);
    }

    public function testGetFieldNameWithDuplicateFieldIds()
    {
        // Test behavior when there are duplicate IDs (should return first match)
        $this->channelFormLib->custom_field_names = [
            1 => 'first_field',
            1 => 'duplicate_field' // This would overwrite in PHP
        ];

        $result = $this->channelFormLib->get_field_name(1);
        $this->assertEquals('duplicate_field', $result); // PHP array behavior
    }

    public function testGetFieldNameWithNonSequentialIds()
    {
        $this->channelFormLib->custom_field_names = [
            5 => 'field_five',
            10 => 'field_ten',
            100 => 'field_hundred'
        ];

        $result = $this->channelFormLib->get_field_name(5);
        $this->assertEquals('field_five', $result);

        $result = $this->channelFormLib->get_field_name(10);
        $this->assertEquals('field_ten', $result);

        $result = $this->channelFormLib->get_field_name(100);
        $this->assertEquals('field_hundred', $result);

        $result = $this->channelFormLib->get_field_name(50); // Non-existent ID
        $this->assertFalse($result);
    }

    public function testGetFieldNameWithZeroAndNegativeIds()
    {
        $this->channelFormLib->custom_field_names = [
            0 => 'zero_field',
            -1 => 'negative_field'
        ];

        $result = $this->channelFormLib->get_field_name(0);
        $this->assertEquals('zero_field', $result);

        $result = $this->channelFormLib->get_field_name(-1);
        $this->assertEquals('negative_field', $result);
    }
}
