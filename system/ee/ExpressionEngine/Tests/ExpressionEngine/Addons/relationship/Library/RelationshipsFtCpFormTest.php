<?php
 /**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/RelationshipTestBase.php';

use Mockery as m;

/**
 * Test Relationships_ft_cp::form() method
 */
class RelationshipsFtCpFormTest extends RelationshipTestBase
{
    /**
     * Test form() creates Relationship_settings_form instance with empty data and no prefix
     */
    public function testFormCreatesSettingsFormInstanceWithEmptyDataAndNoPrefix()
    {
        $data = [];
        $prefix = '';

        $result = $this->relationships_ft_cp->form($data, $prefix);

        $this->assertInstanceOf('Relationship_settings_form', $result);
    }

    /**
     * Test form() creates Relationship_settings_form instance with data and no prefix
     */
    public function testFormCreatesSettingsFormInstanceWithDataAndNoPrefix()
    {
        $data = ['field_name' => 'test_value'];
        $prefix = '';

        $result = $this->relationships_ft_cp->form($data, $prefix);

        $this->assertInstanceOf('Relationship_settings_form', $result);
    }

    /**
     * Test form() creates Relationship_settings_form instance with data and prefix
     */
    public function testFormCreatesSettingsFormInstanceWithDataAndPrefix()
    {
        $data = ['field_name' => 'test_value'];
        $prefix = 'test_prefix';

        $result = $this->relationships_ft_cp->form($data, $prefix);

        $this->assertInstanceOf('Relationship_settings_form', $result);
    }

    /**
     * Test form() creates Relationship_settings_form instance with empty prefix (default)
     */
    public function testFormCreatesSettingsFormInstanceWithDefaultPrefix()
    {
        $data = ['field_name' => 'test_value'];

        $result = $this->relationships_ft_cp->form($data);

        $this->assertInstanceOf('Relationship_settings_form', $result);
    }
}
