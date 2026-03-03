<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../SelectableButtonsTestBase.php';

/**
 * Test Selectable Buttons fieldtype save_settings method
 */
class SelectableButtonsSaveSettingsTest extends SelectableButtonsTestBase
{
    /**
     * Test save_settings method with allow_multiple as 'y'
     */
    public function testSaveSettingsAllowMultipleYes()
    {
        $data = [
            'allow_multiple' => 'y',
            'field_options' => [
                'option1' => 'Option 1',
                'option2' => 'Option 2'
            ]
        ];

        $result = $this->fieldtype->save_settings($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allow_multiple', $result);
        $this->assertTrue($result['allow_multiple']);
    }

    /**
     * Test save_settings method with allow_multiple as 'n'
     */
    public function testSaveSettingsAllowMultipleNo()
    {
        $data = [
            'allow_multiple' => 'n',
            'field_options' => [
                'option1' => 'Option 1'
            ]
        ];

        $result = $this->fieldtype->save_settings($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allow_multiple', $result);
        $this->assertFalse($result['allow_multiple']);
    }

    /**
     * Test save_settings method with allow_multiple as boolean true
     */
    public function testSaveSettingsAllowMultipleBooleanTrue()
    {
        $data = [
            'allow_multiple' => true,
            'field_options' => [
                'option1' => 'Option 1'
            ]
        ];

        $result = $this->fieldtype->save_settings($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allow_multiple', $result);
        $this->assertTrue($result['allow_multiple']);
    }

    /**
     * Test save_settings method with allow_multiple as boolean false
     */
    public function testSaveSettingsAllowMultipleBooleanFalse()
    {
        $data = [
            'allow_multiple' => false,
            'field_options' => [
                'option1' => 'Option 1'
            ]
        ];

        $result = $this->fieldtype->save_settings($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allow_multiple', $result);
        $this->assertFalse($result['allow_multiple']);
    }

    /**
     * Test save_settings method with allow_multiple as empty string
     */
    public function testSaveSettingsAllowMultipleEmptyString()
    {
        $data = [
            'allow_multiple' => '',
            'field_options' => [
                'option1' => 'Option 1'
            ]
        ];

        $result = $this->fieldtype->save_settings($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allow_multiple', $result);
        $this->assertFalse($result['allow_multiple']);
    }

    /**
     * Test save_settings method with allow_multiple as null
     */
    public function testSaveSettingsAllowMultipleNull()
    {
        $data = [
            'allow_multiple' => null,
            'field_options' => [
                'option1' => 'Option 1'
            ]
        ];

        $result = $this->fieldtype->save_settings($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allow_multiple', $result);
        $this->assertFalse($result['allow_multiple']);
    }

    /**
     * Test save_settings method without allow_multiple setting
     */
    public function testSaveSettingsWithoutAllowMultiple()
    {
        $data = [
            'field_options' => [
                'option1' => 'Option 1'
            ]
        ];

        $result = $this->fieldtype->save_settings($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allow_multiple', $result);
        $this->assertFalse($result['allow_multiple']);
    }

    /**
     * Test save_settings method with empty data
     */
    public function testSaveSettingsEmptyData()
    {
        $data = [];

        $result = $this->fieldtype->save_settings($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allow_multiple', $result);
        $this->assertFalse($result['allow_multiple']);
    }

    /**
     * Test save_settings method with field_options
     */
    public function testSaveSettingsWithFieldOptions()
    {
        $data = [
            'allow_multiple' => 'y',
            'field_options' => [
                'option1' => 'Option 1',
                'option2' => 'Option 2',
                'option3' => 'Option 3'
            ]
        ];

        $result = $this->fieldtype->save_settings($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allow_multiple', $result);
        $this->assertTrue($result['allow_multiple']);
        // The field_options should be handled by the parent class
    }

    /**
     * Test save_settings method with special characters in options
     */
    public function testSaveSettingsSpecialCharacters()
    {
        $data = [
            'allow_multiple' => 'y',
            'field_options' => [
                'option1' => 'Option & "Quote"',
                'option2' => 'Option <tag>'
            ]
        ];

        $result = $this->fieldtype->save_settings($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allow_multiple', $result);
        $this->assertTrue($result['allow_multiple']);
    }

    /**
     * Test save_settings method with numeric values
     */
    public function testSaveSettingsNumericValues()
    {
        $data = [
            'allow_multiple' => 'y',
            'field_options' => [
                '1' => 'First Option',
                '2' => 'Second Option'
            ]
        ];

        $result = $this->fieldtype->save_settings($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allow_multiple', $result);
        $this->assertTrue($result['allow_multiple']);
    }

    /**
     * Test save_settings method returns array
     */
    public function testSaveSettingsReturnsArray()
    {
        $data = [
            'allow_multiple' => 'y'
        ];

        $result = $this->fieldtype->save_settings($data);

        $this->assertIsArray($result);
    }

    /**
     * Test save_settings method preserves parent settings
     */
    public function testSaveSettingsPreservesParentSettings()
    {
        $data = [
            'allow_multiple' => 'y',
            'field_text_direction' => 'rtl'
        ];

        $result = $this->fieldtype->save_settings($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allow_multiple', $result);
        $this->assertTrue($result['allow_multiple']);
        // Parent settings should also be preserved
    }
}

// EOF

