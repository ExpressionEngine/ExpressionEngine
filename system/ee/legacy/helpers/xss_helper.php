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
 * XSS Helper
 */

/**
  * Checks to see if the current user needs to have XSS filtering or not
  *
  * @return	boolean	TRUE if they need XSS cleaning on, FALSE otherwise
  */
function xss_check()
{
    $xss_clean = true;

    /* -------------------------------------------
    /*	Hidden Configuration Variables
    /*	- xss_clean_member_exception 		=> a comma separated list of members who will not be subject to xss filtering
    /*  - xss_clean_member_group_exception 	=> a comma separated list of member groups who will not be subject to xss filtering
    /* -------------------------------------------*/

    // There are a few times when xss cleaning may not be wanted, and
    // xss_clean should be changed to FALSE from the default TRUE
    // 1. Super admin uplaods (never filtered)
    if (ee('Permission')->isSuperAdmin()) {
        $xss_clean = false;
    }

    // 2. If XSS cleaning is turned of in the security preferences
    if (ee()->config->item('xss_clean_uploads') == 'n') {
        $xss_clean = false;
    }

    // 3. If a member has been added to the list of exceptions.
    if (ee()->config->item('xss_clean_member_exception') !== false) {
        $xss_clean_member_exception = preg_split('/[\s|,]/', ee()->config->item('xss_clean_member_exception'), -1, PREG_SPLIT_NO_EMPTY);
        $xss_clean_member_exception = is_array($xss_clean_member_exception) ? $xss_clean_member_exception : array($xss_clean_member_exception);

        if (in_array(ee()->session->userdata('member_id'), $xss_clean_member_exception)) {
            $xss_clean = false;
        }
    }

    // 4. If a member's usergroup has been added to the list of exceptions.
    if (ee()->config->item('xss_clean_member_group_exception') !== false) {
        $xss_clean_member_group_exception = preg_split('/[\s|,]/', ee()->config->item('xss_clean_member_group_exception'), -1, PREG_SPLIT_NO_EMPTY);
        $xss_clean_member_group_exception = is_array($xss_clean_member_group_exception) ? $xss_clean_member_group_exception : array($xss_clean_member_group_exception);

        if (ee('Permission')->hasAnyRole($xss_clean_member_group_exception)) {
            $xss_clean = false;
        }
    }

    return $xss_clean;
}

// EOF
