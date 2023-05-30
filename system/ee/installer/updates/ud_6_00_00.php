<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_0_0;

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
        $steps = new \ProgressIterator([
            'removeExtraPubishControlSetting',
            'addPostInstallMessageTemplate',
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function removeExtraPubishControlSetting()
    {
        ee()->smartforge->drop_column('channels', 'extra_publish_controls');
    }

    protected function addPostInstallMessageTemplate()
    {
        $sites = ee('Model')->get('Site')->fields('site_id')->all();
        require_once SYSPATH . 'ee/language/' . (ee()->config->item('deft_lang') ?: 'english') . '/email_data.php';

        foreach ($sites as $site) {
            ee('Model')->make('SpecialtyTemplate')
                ->set([
                    'template_name' => 'post_install_message_template',
                    'template_type' => 'system',
                    'template_subtype' => null,
                    'data_title' => '',
                    'template_data' => post_install_message_template(),
                    'site_id' => $site->site_id,
                ])->save();
        }
    }
}

// EOF
