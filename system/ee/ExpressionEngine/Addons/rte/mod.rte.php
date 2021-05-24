<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\Rte\RteHelper;

/**
 * Rte Module
 */
class Rte
{
    public function pages_autocomplete()
    {
        $search = ee()->input->get('search');
        $modified = ee()->input->get('t');
        if ($modified == 0) {
            $modified = ee()->localize->now;
        }

        ee()->output->set_status_header(200);
        @header("Cache-Control: max-age=172800, must-revalidate");
        @header('Vary: Accept-Encoding');
        @header('Last-Modified: ' . ee()->localize->format_date('%D, %d %M %Y %H:%i:%s', $modified, false) . ' GMT');
        @header('Expires: ' . ee()->localize->format_date('%D, %d %M %Y %H:%i:%s', ee()->localize->now + 172800, false) . ' GMT');

        $pages = RteHelper::getSitePages($search);

        ee()->output->send_ajax_response($pages);
    }
}
