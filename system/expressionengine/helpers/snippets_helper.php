<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */

// ------------------------------------------------------------------------

/**
 * Required field indicator
 *
 * @param string
 */
function required($blurb = '')
{
	if ($blurb != '')
	{
		$blurb = lang($blurb);
	}

	return "<em class='required'>* </em>".$blurb."\n";
}

// ------------------------------------------------------------------------

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
	$EE =& get_instance();

	$layout_preview_links = "<p>".$EE->lang->line('choose_layout_group_preview').NBS."<span class='notice'>".$EE->lang->line('layout_save_warning')."</span></p>";
	$layout_preview_links .= "<ul class='bullets'>";
	foreach($data->result() as $group)
	{
		$layout_preview_links .= '<li><a href=\"'.BASE.AMP.'C=content_publish'.AMP."M=entry_form".AMP."channel_id=".$channel_id.AMP."layout_preview=".$group->group_id.'\">'.$group->group_title."</a></li>";
	}
	$layout_preview_links .= "</ul>";

	return $layout_preview_links;
}

/* End of file snippets_helper.php */
/* Location: ./system/expressionengine/helpers/snippets_helper.php */
