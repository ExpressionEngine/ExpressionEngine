<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Quicktab Helper
 */

/**
  *  Create the "quick add" link
  */
function generate_quicktab($title = '')
{
    $link = '';
    $linkt = '';
    $top_level_items = array('content', 'design', 'addons', 'members', 'admin', 'tools', 'help');

    if (ee()->input->get_post('M', true) != 'main_menu_manager'
        or in_array(ee()->input->get_post('Cdis', true), $top_level_items)) {
        foreach ($_GET as $key => $val) {
            if ($key == 'S' or $key == 'D') {
                continue;
            }

            $link .= htmlentities($key) . '--' . htmlentities($val) . '/';
        }

        $link = substr($link, 0, -1);
    }

    // Does the link already exist as a tab?
    // If so, we'll make the link blank so that the
    // tab manager won't let the user create another tab.

    $show_link = true;

    if (ee()->session->userdata('quick_tabs') !== false) {
        $newlink = str_replace('/', '&', str_replace('--', '=', $link)) . '|';

        if (strpos(ee()->session->userdata('quick_tabs'), $newlink)) {
            $show_link = false;
        }
    }

    // We do not normally allow semicolons in GET variables,
    // so we protect it in this rare instance.
    $tablink = ($link != '' and $show_link == true) ? AMP . 'link=' . $link . AMP . 'linkt=' . base64_encode($title) : '';

    return BASE . AMP . 'C=myaccount' . AMP . 'M=main_menu_manager_add' . $tablink;
}

// EOF
