<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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
            $viewtype_prefs = json_decode(ee()->input->cookie('viewtype'), true);

            // Cookie was not valid JSON - we can assume it was from before we made this change
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Lets try to get the preferences from the cookie still
                $viewtype_prefs = $this->rebuildViewtypeFromSerializedCookie();
                ee()->input->set_cookie('viewtype', json_encode($viewtype_prefs), 31104000);
            }

            // If the viewtype is set, and its one of our available viewtypes, we set it and return it
            if (isset($viewtype_prefs[$destination]) && in_array($viewtype_prefs[$destination], $views)) {
                $viewtype = $viewtype_prefs[$destination];
            }
        }

        if (in_array(ee()->input->get('viewtype'), $views)) {
            if (!isset($viewtype_prefs[$destination]) || $viewtype != ee()->input->get('viewtype')) {
                $viewtype_prefs[$destination] = ee()->input->get('viewtype');
                ee()->input->set_cookie('viewtype', json_encode($viewtype_prefs), 31104000);
            }
            $viewtype = ee()->input->get('viewtype');
        }

        return $viewtype;
    }

    /**
     * Determine view type for given destination (directory or 'all')
     * Checks $_GET and Cookie, sets cookie if required
     */
    private function rebuildViewtypeFromSerializedCookie()
    {
        $regex = '/"(?P<destination>[A-z_0-9\-]*)";s:[4,5]:"(?P<viewtype>list|thumb)"/';
        $matchCount = preg_match_all($regex, ee()->input->cookie('viewtype'), $matches);

        // If there are no matches, return
        if ($matchCount == 0) {
            return [];
        }

        // Get the viewtype preferences combined back into an array
        return array_combine($matches['destination'], $matches['viewtype']);
    }
}

// EOF
