<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Segment Helper
 *
 * @package		ExpressionEngine
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */


/**
  * Checks to see if the current user needs to have XSS filtering or not
  *
  * @return	boolean	TRUE if they need XSS cleaning on, FALSE otherwise
  */
function xss_check()
{
	$xss_clean = TRUE;

	/* -------------------------------------------
	/*	Hidden Configuration Variables
	/*	- xss_clean_member_exception 		=> a comma separated list of members who will not be subject to xss filtering
	/*  - xss_clean_member_group_exception 	=> a comma separated list of member groups who will not be subject to xss filtering
	/* -------------------------------------------*/

	// There are a few times when xss cleaning may not be wanted, and
	// xss_clean should be changed to FALSE from the default TRUE
	// 1. Super admin uplaods (never filtered)
	if (ee()->session->userdata('group_id') == 1)
	{
		$xss_clean = FALSE;
	}

	// 2. If XSS cleaning is turned of in the security preferences
	if (ee()->config->item('xss_clean_uploads') == 'n')
	{
		$xss_clean = FALSE;
	}

	// 3. If a member has been added to the list of exceptions.
	if (ee()->config->item('xss_clean_member_exception') !== FALSE)
	{
		$xss_clean_member_exception = preg_split('/[\s|,]/', ee()->config->item('xss_clean_member_exception'), -1, PREG_SPLIT_NO_EMPTY);
		$xss_clean_member_exception = is_array($xss_clean_member_exception) ? $xss_clean_member_exception : array($xss_clean_member_exception);

		if (in_array(ee()->session->userdata('member_id'), $xss_clean_member_exception))
		{
			$xss_clean = FALSE;
		}
	}

	// 4. If a member's usergroup has been added to the list of exceptions.
	if (ee()->config->item('xss_clean_member_group_exception') !== FALSE)
	{
		$xss_clean_member_group_exception = preg_split('/[\s|,]/', ee()->config->item('xss_clean_member_group_exception'), -1, PREG_SPLIT_NO_EMPTY);
		$xss_clean_member_group_exception = is_array($xss_clean_member_group_exception) ? $xss_clean_member_group_exception : array($xss_clean_member_group_exception);

		if (in_array(ee()->session->userdata('group_id'), $xss_clean_member_group_exception))
		{
			$xss_clean = FALSE;
		}
	}

	return $xss_clean;
}

// EOF
