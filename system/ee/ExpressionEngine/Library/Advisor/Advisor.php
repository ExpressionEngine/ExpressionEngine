<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Advisor;

class Advisor
{
    public function postUpdateChecks()
    {
        // if we call this from updater, site_short_name might be not set
        // grab the first site and set it
        if (empty(ee()->config->item('site_short_name'))) {
            $siteQuery = ee('db')->select('site_name')->from('sites')->order_by('site_id', 'asc')->limit(1)->get();
            ee()->config->set_item('site_short_name', $siteQuery->row('site_name'));
        }

        $messages = [];

        ee()->lang->loadfile('utilities');

        $templateAdvisor = new \ExpressionEngine\Library\Advisor\TemplateAdvisor();
        $bad_tags_count = $templateAdvisor->getBadTagCount();
        if ($bad_tags_count > 0) {
            $messages[] = sprintf(lang('debug_tools_broken_tags_found'), $bad_tags_count);
        }

        $ftAdvisor = new \ExpressionEngine\Library\Advisor\FieldtypeAdvisor();
        $missing_fieldtype_count = $ftAdvisor->getMissingFieldtypeCount();
        if ($missing_fieldtype_count > 0) {
            $messages[] = sprintf(lang('debug_tools_found_missing_fieldtypes'), $missing_fieldtype_count);
        }

        return $messages;
    }
}
// EOF
