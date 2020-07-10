<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\Advisor;

class Advisor
{

    public function postUpdateChecks() {

        $messages = [];

        ee()->lang->loadfile('utilities');

        $templateAdvisor = new \EllisLab\ExpressionEngine\Library\Advisor\TemplateAdvisor();
        $bad_tags_count = $templateAdvisor->getBadTagCount();
        if ($bad_tags_count > 0) {
            $messages[] = sprintf(lang('debug_tools_broken_tags_found'), $bad_tags_count);
        }

        $ftAdvisor = new \EllisLab\ExpressionEngine\Library\Advisor\FieldtypeAdvisor();
        $missing_fieldtype_count = $ftAdvisor->getMissingFieldtypeCount();
        if ($missing_fieldtype_count > 0) {
            $messages[] = sprintf(lang('debug_tools_found_missing_fieldtypes'), $missing_fieldtype_count);
        }

        return $messages;
    }

}
// EOF
