<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/** -------------------------------------
 *  Required field indicator
 * --------------------------------------
 */
function required($blurb = '')
{
	if ($blurb != '')
	{
		$blurb = lang($blurb);
	}

	return "<em class='required'>* </em>".$blurb."\n";
}

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