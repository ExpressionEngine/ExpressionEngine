<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Quicktab Helper
 *
 * @package		ExpressionEngine
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

// ------------------------------------------------------------------------

/**
  *  Create the "quick add" link
  */
function generate_quicktab($title = '')
{
	$EE =& get_instance();

	$link  = '';
	$linkt = '';
	$top_level_items = array('content', 'design', 'addons', 'members', 'admin', 'tools', 'help');

	if ($EE->input->get_post('M', TRUE) != 'main_menu_manager' 
		OR in_array($EE->input->get_post('Cdis', TRUE), $top_level_items))
	{
		foreach ($_GET as $key => $val)
		{
			if ($key == 'S' OR $key == 'D')
			{
				continue;
			}

			$link .= htmlentities($key).'--'.htmlentities($val).'/';
		}

		$link = substr($link, 0, -1);
	}

	// Does the link already exist as a tab?
	// If so, we'll make the link blank so that the
	// tab manager won't let the user create another tab.

	$show_link = TRUE;

	if ($EE->session->userdata('quick_tabs') !== FALSE)
	{
		$newlink = str_replace('/', '&', str_replace('--', '=', $link)).'|';

		if (strpos($EE->session->userdata('quick_tabs'), $newlink))
		{
			$show_link = FALSE;
		}
	}

	// We do not normally allow semicolons in GET variables,
	// so we protect it in this rare instance.
	$tablink = ($link != '' AND $show_link == TRUE) ? AMP.'link='.$link.AMP.'linkt='.base64_encode($title) : '';

	return BASE.AMP.'C=myaccount'.AMP.'M=main_menu_manager_add'.$tablink;
}


/* End of file quicktab_helper.php */
/* Location: ./system/expressionengine/helpers/quicktab_helper.php */
