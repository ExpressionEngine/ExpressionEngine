<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_5_0;

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
            [
                'updateSpecialtyTemplates',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    // if the specialty templates are using EE2 HTML, update those
    public function updateSpecialtyTemplates()
    {
        $file = SYSPATH . 'ee/language/' . ee()->lang->getIdiom() . '/email_data.php';
        if (!file_exists($file)) {
            return;
        }
        require_once $file;
        $EE2Hashes = [
            "offline_template" => "68ce4af17fad66887d26be7ec362b39f",
            "message_template" => "478bfbf1cd29a113e992cdeede826739",
            "admin_notify_reg" => "dedcd84fb5a949984f272e7992e76f00",
            "admin_notify_entry" => "e79c055ba45a622b66076a4315cc0691",
            "admin_notify_mailinglist" => "505f2ec45a4dc3fe11422b198975a9fd",
            "admin_notify_comment" => "120dc9265dab4c254711d7c9327c9a44",
            "mbr_activation_instructions" => "231a4da360cf31c1cc43de2abfd11212",
            "forgot_password_instructions" => "d3571a3cf9f4636ba92d9ee2af15bfa3",
            "validated_member_notify" => "6f0224b0dba522b8f9b5bfddcbbd4956",
            "decline_member_validation" => "241fcf2139373b52770710b29f19dda1",
            "mailinglist_activation_instructions" => "5233569223d3a8aff910e95fd690ace6",
            "comment_notification" => "5beaf573c2dac8c1726a7e5bc17a955f",
            "comments_opened_notification" => "5b1b04b5c2ecbb7bad4e93a4323503a4",
            "private_message_notification" => "7fc7632fefbfb49f59e126997654df99",
            "pm_inbox_full" => "3d10bc476f06476335ebd306a651df5b"
        ];
        $templatesQuery = ee()->db->get('specialty_templates');
        foreach ($templatesQuery->result_array() as $row) {
            if (isset($EE2Hashes[$row['template_name']])) {
                $hash = md5($row['template_data']);
                if ($hash === $EE2Hashes[$row['template_name']]) {
                    continue;
                }
                if (function_exists($row['template_name'])) {
                    $fn = $row['template_name'];
                    ee()->db->where('template_id', $row['template_id']);
                    ee()->db->update('specialty_templates', ['template_data' => $fn()]);
                }
            }
        }
    }
}

// EOF
