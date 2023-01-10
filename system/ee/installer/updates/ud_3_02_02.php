<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_3_2_2;

/**
 * Update
 */
class Updater
{
    public $version_suffix = '';

    /**
     * Do Update
     *
     * @return TRUE
     */
    public function do_update()
    {
        $steps = new \ProgressIterator(
            array(
                'install_required_fieldtypes',
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * Ensure required modules are installed
     * @return void
     */
    private function install_required_fieldtypes()
    {
        ee()->load->library('addons/addons_installer');

        $installed_fieldtypes = ee('Model')->get('Fieldtype')->all()->pluck('name');

        $required_fieldtypes = array('select', 'text', 'textarea', 'date', 'file', 'grid', 'multi_select', 'checkboxes', 'radio', 'relationship', 'rte');

        foreach ($required_fieldtypes as $fieldtype) {
            if (! in_array($fieldtype, $installed_fieldtypes)) {
                ee()->addons_installer->install($fieldtype, 'fieldtype', false);
            }
        }
    }
}

// EOF
