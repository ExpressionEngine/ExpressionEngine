<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\ChannelSet;

use ExpressionEngine\Service\ChannelSet\Structure;
use PHPUnit\Framework\TestCase;

class StructureTest extends TestCase
{
    public function testHierarchyArrayContainsExpectedKeys()
    {
        $expectedKeys = ['ee:Channel', 'ee:ChannelFieldGroup', 'ee:CategoryGroup', 'ee:UploadDestination'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, Structure::$hierarchy);
        }
    }

    public function testHierarchyChannelFieldGroupHasChannelFields()
    {
        $this->assertArrayHasKey('ChannelFields', Structure::$hierarchy['ee:ChannelFieldGroup']);
        $this->assertEquals('ee:ChannelField', Structure::$hierarchy['ee:ChannelFieldGroup']['ChannelFields']);
    }

    public function testHierarchyCategoryGroupHasCategories()
    {
        $this->assertArrayHasKey('Categories', Structure::$hierarchy['ee:CategoryGroup']);
        $this->assertEquals('ee:Category', Structure::$hierarchy['ee:CategoryGroup']['Categories']);
    }

    public function testHumanModelNamesContainsAllExpectedModels()
    {
        $expectedNames = [
            'ee:Channel' => 'Channel',
            'ee:ChannelFieldGroup' => 'Channel Field Group',
            'ee:ChannelField' => 'Channel Field',
            'ee:CategoryGroup' => 'Category Group',
            'ee:Category' => 'Category',
            'ee:StatusGroup' => 'Status Group',
            'ee:Status' => 'Status',
            'ee:UploadDestination' => 'Upload Destination'
        ];

        foreach ($expectedNames as $modelName => $humanName) {
            $this->assertEquals($humanName, Structure::$human_model_names[$modelName]);
        }
    }

    public function testTitleFieldsContainsExpectedMappings()
    {
        $expectedMappings = [
            'ee:Channel' => 'channel_title',
            'ee:ChannelFieldGroup' => 'group_name',
            'ee:CategoryGroup' => 'group_name',
            'ee:ChannelField' => 'field_label',
            'ee:UploadDestination' => 'name'
        ];

        foreach ($expectedMappings as $modelName => $fieldName) {
            $this->assertEquals($fieldName, Structure::$title_fields[$modelName]);
        }
    }

    public function testIdentityFieldsContainsExpectedMappings()
    {
        $expectedMappings = [
            'ee:Channel' => 'channel_name',
            'ee:ChannelFieldGroup' => 'group_name',
            'ee:CategoryGroup' => 'group_name',
            'ee:ChannelField' => 'field_name',
            'ee:UploadDestination' => 'name'
        ];

        foreach ($expectedMappings as $modelName => $fieldName) {
            $this->assertEquals($fieldName, Structure::$identity_fields[$modelName]);
        }
    }

    public function testShortNamesContainsExpectedMappings()
    {
        $expectedMappings = [
            'ee:Channel' => ['channel_name' => 'channel_title'],
            'ee:ChannelField' => ['field_name' => 'field_label']
        ];

        foreach ($expectedMappings as $modelName => $mapping) {
            $this->assertEquals($mapping, Structure::$short_names[$modelName]);
        }
    }

    public function testGetHumanNameReturnsCorrectNames()
    {
        $testCases = [
            'ee:Channel' => 'Channel',
            'ee:ChannelField' => 'Channel Field',
            'ee:CategoryGroup' => 'Category Group',
            'ee:Status' => 'Status',
            'ee:UploadDestination' => 'Upload Destination'
        ];

        foreach ($testCases as $modelName => $expectedName) {
            $mockModel = $this->createMockModel($modelName);
            $this->assertEquals($expectedName, Structure::getHumanName($mockModel));
        }
    }

    public function testGetValidateRelationshipsReturnsCorrectRelationships()
    {
        // Test Channel - should have no relationships to validate
        $channelModel = $this->createMockModel('ee:Channel');
        $this->assertEquals([], Structure::getValidateRelationships($channelModel));

        // Test ChannelFieldGroup - should have ChannelFields relationship
        $fieldGroupModel = $this->createMockModel('ee:ChannelFieldGroup');
        $this->assertEquals(['ChannelFields'], Structure::getValidateRelationships($fieldGroupModel));

        // Test CategoryGroup - should have Categories relationship
        $categoryGroupModel = $this->createMockModel('ee:CategoryGroup');
        $this->assertEquals(['Categories'], Structure::getValidateRelationships($categoryGroupModel));

        // Test UploadDestination - should have no relationships to validate
        $uploadModel = $this->createMockModel('ee:UploadDestination');
        $this->assertEquals([], Structure::getValidateRelationships($uploadModel));
    }

    public function testGetTitleFieldForReturnsCorrectFields()
    {
        $testCases = [
            'ee:Channel' => 'channel_title',
            'ee:ChannelFieldGroup' => 'group_name',
            'ee:CategoryGroup' => 'group_name',
            'ee:ChannelField' => 'field_label',
            'ee:UploadDestination' => 'name'
        ];

        foreach ($testCases as $modelName => $expectedField) {
            $mockModel = $this->createMockModel($modelName);
            $this->assertEquals($expectedField, Structure::getTitleFieldFor($mockModel));
        }
    }

    public function testGetTitleFieldForThrowsExceptionForUnknownModel()
    {
        $this->expectException(\Exception::class);

        $mockModel = $this->createMockModel('ee:UnknownModel');
        Structure::getTitleFieldFor($mockModel);
    }

    public function testGetIdentityFieldForReturnsCorrectFields()
    {
        $testCases = [
            'ee:Channel' => 'channel_name',
            'ee:ChannelFieldGroup' => 'group_name',
            'ee:CategoryGroup' => 'group_name',
            'ee:ChannelField' => 'field_name',
            'ee:UploadDestination' => 'name'
        ];

        foreach ($testCases as $modelName => $expectedField) {
            $mockModel = $this->createMockModel($modelName);
            $this->assertEquals($expectedField, Structure::getIdentityFieldFor($mockModel));
        }
    }

    public function testGetIdentityFieldForThrowsExceptionForUnknownModel()
    {
        $this->expectException(\Exception::class);

        $mockModel = $this->createMockModel('ee:UnknownModel');
        Structure::getIdentityFieldFor($mockModel);
    }

    public function testGetLongFieldIfShortenedReturnsCorrectMappings()
    {
        $channelModel = $this->createMockModel('ee:Channel');
        $fieldModel = $this->createMockModel('ee:ChannelField');

        // Test channel short name mapping
        $this->assertEquals('channel_title', Structure::getLongFieldIfShortened($channelModel, 'channel_name'));

        // Test field short name mapping
        $this->assertEquals('field_label', Structure::getLongFieldIfShortened($fieldModel, 'field_name'));

        // Test non-shortened field returns null
        $this->assertNull(Structure::getLongFieldIfShortened($channelModel, 'channel_title'));

        // Test unknown model returns null
        $unknownModel = $this->createMockModel('ee:UnknownModel');
        $this->assertNull(Structure::getLongFieldIfShortened($unknownModel, 'some_field'));
    }

    public function testGetLongFieldIfShortenedWithUnknownModel()
    {
        $unknownModel = $this->createMockModel('ee:UnknownModel');
        $this->assertNull(Structure::getLongFieldIfShortened($unknownModel, 'some_field'));
    }

    public function testGetLongFieldIfShortenedWithUnmappedField()
    {
        $channelModel = $this->createMockModel('ee:Channel');
        $this->assertNull(Structure::getLongFieldIfShortened($channelModel, 'unknown_field'));
    }

    /**
     * Helper method to create a mock model with the specified name
     */
    private function createMockModel($modelName)
    {
        $mock = $this->getMockBuilder('stdClass')->addMethods(['getName'])->getMock();
        $mock->method('getName')->willReturn($modelName);
        return $mock;
    }
}
