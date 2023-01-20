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
 * Snippets Helper
 */

/**
 * Required field indicator
 *
 * @param string
 */
function required($blurb = '')
{
    if ($blurb != '') {
        $blurb = lang($blurb);
    }

    return "<em class='required'>* </em>" . $blurb . "\n";
}

/**
 * Get Layout Preview Links
 *
 * Creates the proper html list for the layout preview options.
 *
 * @access	public
 * @return	string
 */
function layout_preview_links($data, $channel_id)
{
    $layout_preview_links = "<p>" . ee()->lang->line('choose_layout_group_preview') . NBS . "<span class='notice'>" . ee()->lang->line('layout_save_warning') . "</span></p>";
    $layout_preview_links .= "<ul class='bullets'>";
    foreach ($data->result() as $group) {
        $layout_preview_links .= '<li><a href=\"' . BASE . AMP . 'C=content_publish' . AMP . "M=entry_form" . AMP . "channel_id=" . $channel_id . AMP . "layout_preview=" . $group->group_id . '\">' . $group->group_title . "</a></li>";
    }
    $layout_preview_links .= "</ul>";

    return $layout_preview_links;
}

// EOF
