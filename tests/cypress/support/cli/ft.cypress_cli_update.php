<?php

class Cypress_cli_update_ft extends EE_Fieldtype
{
    public $info = ['name' => 'Cypress CLI Update', 'version' => '2.0.0'];

    /**
     * Render the fixture field.
     *
     * @param string $data
     * @return string
     */
    public function display_field($data)
    {
        return $data;
    }
}
