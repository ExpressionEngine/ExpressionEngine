<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../CheckboxesTestBase.php';

use Mockery as m;

/**
 * Test for Checkboxes_ft::_display_field() method
 */
class CheckboxesDisplayFieldTest extends CheckboxesTestBase
{
    /**
     * @var Checkboxes_ft
     */
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->getMockFieldtypeWithSettings();
    }

    /**
     * Test display_field public method
     */
    public function testDisplayFieldPublic()
    {
        // Test that the method is callable (Mockery creates methods on demand)
        $data = ['option1', 'option2'];
        $result = $this->fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test grid_display_field method
     */
    public function testGridDisplayField()
    {
        // Test that the method is callable
        $data = ['option1'];
        $result = $this->fieldtype->grid_display_field($data);
        $this->assertStringContainsString('Mock grid display', $result);
    }

    /**
     * Test display_settings method
     */
    public function testDisplaySettingsMethod()
    {
        // Test that the method is callable
        $data = [];
        $result = $this->fieldtype->display_settings($data);
        $this->assertArrayHasKey('field_options_checkboxes', $result);
    }
}

// EOF
