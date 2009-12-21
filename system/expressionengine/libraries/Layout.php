<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2009, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Publish Layout Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Layout {
	
	var $custom_layout_fields = array();
	
	/**
	 * Constructor
	 *
	 * Get reference to EE SuperObject
	 */
	function EE_Layout()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update Layout
	 *
	 *
	 *
	 *
	 *
	 */
	function update_layout($edit = FALSE)
	{
		if ($edit === FALSE)
		{
			return;
		}
		
		$this->EE->load->model('member_model');

		// Grab each member group that's allowed to publish
		$member_groups = $this->EE->member_model->get_member_groups('can_access_publish', 
																	array('can_access_publish'=>'y'));

		// Do we have a channel id?
		if ($this->EE->input->post('channel_id'))
		{
			$channel_id = $this->EE->input->post('channel_id');
		}
		else
		{
			$this->EE->db->select('channel_id');
			$this->EE->db->where('field_group', $this->EE->input->post('group_id'));
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$query = $this->EE->db->get('channels');
			
			$channel_id = $query->row('channel_id');
		}

		// Loop through each member group, looking for a custom layout
		// Counting results isn't needed here, at least super admin will be here
		foreach ($member_groups->result() as $group)
		{
			// Get any custom layout
			$this->custom_layout_fields = $this->EE->member_model->get_group_layout($group->group_id,
				 																	$channel_id);

			// If there is a layout, we need to re-create it, as the channel prefs
			// might be hiding the url_title or something.
			if ( ! empty($this->custom_layout_fields))
			{
				// This is a list of everything that an admin could choose to hide in Channel Prefs
				// with a corresponding list of which fields need to be stricken from a custom layout
				$check_field = array(
								'show_url_title' => array('url_title'),
								'show_author_menu' => array('author'),
								'show_status_menu' => array('status'),
								'show_date_menu' => array('entry_date', 'expiration_date', 'comment_expiration_date'),
								'show_options_cluster' => array('options'),
								'show_ping_cluster' => array('ping'),
								'show_categories_menu' => array('category'),
								'show_forum_cluster' => array('forum_title', 'forum_body', 'forum_id', 'forum_topic_id')
							);

				foreach ($check_field as $post_key => $fields_to_remove)
				{
					// If the field is set to 'n', then we need it stripped from the custom layout
					if ($this->EE->input->post($post_key) == 'n')
					{
						foreach ($this->custom_layout_fields as $tab => $fields)
						{
							foreach ($fields as $field => $data)
							{
								if (array_search($field, $fields_to_remove) !== FALSE)
								{
									unset($this->custom_layout_fields[$tab][$field]);
								}
							}
						}
					}
				}

				// All fields have been removed that need to be, reconstruct the layout
				$this->EE->member_model->insert_group_layout($group->group_id, 
														 	 $channel_id, 
														 	 $this->custom_layout_fields
														);
			}
		}	
	}
	
	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file layout.php */
/* Location: ./system/expressionengine/libraries/layout.php */