<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_1_3;

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
        $steps = new \ProgressIterator (
            [
                'addForgotUsernameTemplate'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addForgotUsernameTemplate()
    {
        require_once SYSPATH . 'ee/language/' . (ee()->config->item('deft_lang') ?: 'english') . '/email_data.php';
        
        if (ee()->db->where('template_name', 'forgot_username_instructions')->get('specialty_templates')->num_rows() > 0) {
            return;
        }

        ee()->db->insert(
            'specialty_templates',
            [
                'template_name' => 'forgot_username_instructions',
                'template_type' => 'email',
                'template_subtype' => 'members',
                'edit_date' => time(),
                'data_title' => addslashes(trim(forgot_username_instructions_title())),
                'template_data' => addslashes(forgot_username_instructions())
            ]
        );
    }
}

// EOF
