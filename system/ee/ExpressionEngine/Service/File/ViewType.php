<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\File;

/**
 * View Type class
 */
class ViewType
{
    public function __construct()
    {
    }

    /**
     * Determine view type for given destination (directory or 'all')
     * Checks $_GET and Cookie, sets cookie if required
     */
    public function determineViewType($destination = 'all', $viewtype = 'list')
    {
        $views = ['list', 'thumb'];
        $viewtype_prefs = [];

        if (ee()->input->cookie('viewtype')) {
            $viewtype_prefs = unserialize(ee()->input->cookie('viewtype'));
            if (isset($viewtype_prefs[$destination]) && in_array($viewtype_prefs[$destination], $views)) {
                $viewtype = $viewtype_prefs[$destination];
            }
        }

        if (in_array(ee()->input->get('viewtype'), $views)) {
            if (!isset($viewtype_prefs[$destination]) || $viewtype != ee()->input->get('viewtype')) {
                $viewtype_prefs[$destination] = ee()->input->get('viewtype');
                ee()->input->set_cookie('viewtype', serialize($viewtype_prefs), 31104000);
            }
            $viewtype = ee()->input->get('viewtype');
        }

        return $viewtype;
    }
}

// EOF
