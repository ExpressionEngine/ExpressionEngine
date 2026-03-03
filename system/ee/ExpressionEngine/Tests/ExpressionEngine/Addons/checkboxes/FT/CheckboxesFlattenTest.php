<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../CheckboxesTestBase.php';

/**
 * Test for Checkboxes_ft::_flatten() method
 */
class CheckboxesFlattenTest extends CheckboxesTestBase
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
     * Test flatten with flat options
     */
    public function testFlattenFlatOptions()
    {
        $options = [
            'option1' => 'Option 1',
            'option2' => 'Option 2',
            'option3' => 'Option 3'
        ];

        $result = $this->fieldtype->_flatten($options);

        $expected = [
            'option1' => 'Option 1',
            'option2' => 'Option 2',
            'option3' => 'Option 3'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test flatten with nested options
     */
    public function testFlattenNestedOptions()
    {
        $options = [
            'group1' => [
                'name' => 'Group 1',
                'children' => [
                    'option1' => 'Option 1',
                    'option2' => 'Option 2'
                ]
            ],
            'group2' => [
                'name' => 'Group 2',
                'children' => [
                    'option3' => 'Option 3',
                    'option4' => 'Option 4'
                ]
            ]
        ];

        $result = $this->fieldtype->_flatten($options);

        $expected = [
            'group1' => 'Group 1',
            'option1' => 'Option 1',
            'option2' => 'Option 2',
            'group2' => 'Group 2',
            'option3' => 'Option 3',
            'option4' => 'Option 4'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test flatten with deeply nested options
     */
    public function testFlattenDeeplyNestedOptions()
    {
        $options = [
            'top_group' => [
                'name' => 'Top Group',
                'children' => [
                    'sub_group1' => [
                        'name' => 'Sub Group 1',
                        'children' => [
                            'option1' => 'Option 1',
                            'option2' => 'Option 2'
                        ]
                    ],
                    'sub_group2' => [
                        'name' => 'Sub Group 2',
                        'children' => [
                            'option3' => 'Option 3'
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->fieldtype->_flatten($options);

        $expected = [
            'top_group' => 'Top Group',
            'sub_group1' => 'Sub Group 1',
            'option1' => 'Option 1',
            'option2' => 'Option 2',
            'sub_group2' => 'Sub Group 2',
            'option3' => 'Option 3'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test flatten with empty options
     */
    public function testFlattenEmptyOptions()
    {
        $options = [];

        $result = $this->fieldtype->_flatten($options);

        $this->assertEquals([], $result);
    }

    /**
     * Test flatten with mixed flat and nested options
     */
    public function testFlattenMixedOptions()
    {
        $options = [
            'flat_option1' => 'Flat Option 1',
            'group1' => [
                'name' => 'Group 1',
                'children' => [
                    'option1' => 'Option 1',
                    'option2' => 'Option 2'
                ]
            ],
            'flat_option2' => 'Flat Option 2'
        ];

        $result = $this->fieldtype->_flatten($options);

        $expected = [
            'flat_option1' => 'Flat Option 1',
            'group1' => 'Group 1',
            'option1' => 'Option 1',
            'option2' => 'Option 2',
            'flat_option2' => 'Flat Option 2'
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test flatten with nested options that have empty children
     */
    public function testFlattenNestedOptionsWithEmptyChildren()
    {
        $options = [
            'group1' => [
                'name' => 'Group 1',
                'children' => []
            ],
            'option1' => 'Option 1'
        ];

        $result = $this->fieldtype->_flatten($options);

        $expected = [
            'group1' => 'Group 1',
            'option1' => 'Option 1'
        ];

        $this->assertEquals($expected, $result);
    }
}

// EOF
