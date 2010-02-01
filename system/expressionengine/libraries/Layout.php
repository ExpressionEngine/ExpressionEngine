<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
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
class Layout {
	
	var $custom_layout_fields = array();
	
	/**
	 * Constructor
	 *
	 * Get reference to EE SuperObject
	 */
	function Layout()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Remove Module Layout
	 *
	 * Removes fields created by module tabs from all layouts for all channels in all member groups
	 *
	 * @access	private
	 * @param	array
	 * @return	boolean
	 */
	function remove_module_layout($module = '', $remove_fields = array())
	{
		// No module declared or fields to remove? We're done.
		if ($module == '' OR empty($remove_fields))
		{
			return TRUE;
		}

		$this->EE->load->model('member_model');

		// Retrieve every custom layout, we need to inspect it for the set fields
		$all_layouts = $this->EE->member_model->get_all_group_layouts();

		// No layouts? We're done.
		if (empty($all_layouts))
		{
			return TRUE;
		}

		// The tab will be capitalized
		$module = ucfirst($module);

		// open each one
		foreach ($all_layouts as $layout)
		{
			$tabs = unserialize($layout['field_layout']);
			$changes = 0; // This is a marker to keep track of the number of changes. If its zero at the end, then no db entry is needed

			foreach ($tabs as $tab => $fields)
			{
				foreach ($fields as $field => $data)
				{
					if (array_search($field, $remove_fields) !== FALSE)
					{
						$changes++;
						unset($tabs[$tab][$field]);
					}
				}

				// Fields were removed, but the tab may still be there. Since we can't account for what might have
				// been moved there, let's check if its there, and if its empty. Assuming it is, remove it.
				if ($tab == $module AND count($tabs[$tab]) == 0)
				{
					unset($tabs[$tab]);
				}
			}

			// All done looping through the custom layout fields. Did anything change?
			if ($changes == 0)
			{
				return TRUE;
			}
			else
			{
				// Something changed, so we need to update this entry (model takes care of removing any already there)
				$this->EE->member_model->insert_group_layout($layout['member_group'], $layout['channel_id'], $tabs);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Update Layout
	 */
	function update_layout($edit = FALSE, $comment_date_fields = TRUE)
	{
		if ($edit === FALSE)
		{
			return;
		}

		$this->EE->load->model('member_model');

		// Grab each member group that's allowed to publish
		$member_groups = $this->EE->member_model->get_member_groups('can_access_publish', array('can_access_publish'=>'y'));

		// Do we have a channel id?
		if ($this->EE->input->post('channel_id'))
		{
			$channel_id = $this->EE->input->post('channel_id');
		}
		else
		{
			$this->EE->db->select('channel_id, field_group');
			
			if ($this->EE->input->post('group_id'))
			{
				$this->EE->db->where('field_group', $this->EE->input->post('group_id'));				
			}
			
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$query = $this->EE->db->get('channels');
			
			$channel_id = $query->row('channel_id');
		}
		
		// No channel id? Happens when you edit field group that ins't
		// in use by any channels.
		
		if ( ! is_numeric($channel_id))
		{
			return;
		}

		// Loop through each member group, looking for a custom layout
		// Counting results isn't needed here, at least super admin will be here
		foreach ($member_groups->result() as $group)
		{
			// Get any custom layout
			$this->custom_layout_fields = $this->EE->member_model->get_group_layout($group->group_id, $channel_id);

			// If there is a layout, we need to re-create it, as the channel prefs
			// might be hiding the url_title or something.
			if ( ! empty($this->custom_layout_fields))
			{
				// This is a list of everything that an admin could choose to hide in Channel Prefs
				// with a corresponding list of which fields need to be stricken from a custom layout
				$check_field = array(
								'show_url_title'		=> array('url_title'),
								'show_author_menu'		=> array('author'),
								'show_status_menu'		=> array('status'),
								'show_date_menu'		=> array('entry_date', 'expiration_date', 'comment_expiration_date'),
								'show_options_cluster'	=> array('options'),
								'show_ping_cluster'		=> array('ping'),
								'show_categories_menu'	=> array('category'),
								'show_forum_cluster'	=> array('forum_title', 'forum_body', 'forum_id', 'forum_topic_id')
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

					if ( ! $comment_date_fields)
					{
						unset($this->custom_layout_fields['date']['comment_expiration_date']);
					}
					else
					{
						$this->custom_layout_fields['date']['comment_expiration_date'] = array(
										'visible'		=> 'TRUE',
										'collapse'		=> 'FALSE',
										'htmlbuttons'	=> 'FALSE',
										'width'			=> '100%'
							
							);
					}
				}

				// All fields have been removed that need to be, reconstruct the layout
				$test = $this->EE->member_model->insert_group_layout($group->group_id, $channel_id, $this->custom_layout_fields);
			}
		}
	}
	
	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file layout.php */
/* Location: ./system/expressionengine/libraries/layout.php */