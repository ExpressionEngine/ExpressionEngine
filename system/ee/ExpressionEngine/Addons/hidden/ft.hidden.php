<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Hidden Fieldtype Class
 */
class Hidden_ft extends EE_Fieldtype
{
    public $info = array(
        'name' => 'Hidden Field',
        'version' => '1.0.0'
    );

    // Parser Flag (preparse pairs?)
    public $has_array_data = false;

    public function display_field($data)
    {
        ee()->javascript->set_global('publish.hidden_fields', array($this->field_id => $this->field_name));

        return form_hidden($this->field_name, $data);
    }

    /**
     * Update the fieldtype
     *
     * @param string $version The version being updated to
     * @return boolean TRUE if successful, FALSE otherwise
     */
    public function update($version)
    {
        return true;
    }
}

// END Hidden_Ft class

// EOF
