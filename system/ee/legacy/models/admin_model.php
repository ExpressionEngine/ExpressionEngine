<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Admin Model
 */
class Admin_model extends CI_Model {

	/**
	 * Get XML Encodings
	 *
	 * Returns an associative array of XML language keys and values
	 *
	 * @access	public
	 * @return	array
	 */
	function get_xml_encodings()
	{
		$languages = ee()->config->loadFile('languages');
		$encodings = array_flip($languages);

		return $encodings;
	}

	/**
	 * Get Installed Language Packs
	 *
	 * Returns an array of installed language packs
	 *
	 * @access	public
	 * @return	array
	 */
	function get_installed_language_packs()
	{
		ee()->logger->deprecated('3.0', 'EE_lang::language_pack_names()');
		ee()->load->model('language_model');
		return ee()->lang->language_pack_names();
	}

	/**
	 * Theme List
	 *
	 * Fetch installed CP Theme list
	 *
	 * @access	public
	 * @return	array
	 */
	function get_cp_theme_list()
	{
		static $themes;

		if ( ! isset($themes))
		{
			$this->load->helper('directory');

			$theme_paths = array(
				PATH_THEMES.'cp/',
				PATH_THIRD_THEMES.'cp/'
			);

			foreach ($theme_paths as $theme_path)
			{
				$map = directory_map($theme_path, TRUE);
				$map = array_filter($map, function($item) {
					return ! empty($item) && $item !== 'index.html' && $item !== '.';
				});
				ksort($map);

				foreach ($map as $theme_name)
				{
					if (is_dir($theme_path.$theme_name))
					{
						$themes[$theme_name] = ucfirst(str_replace('_', ' ', $theme_name));
					}
				}
			}
		}

		return $themes;
	}

	/**
	 * Template List
	 *
	 * Generates an array for the site template selection lists
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function get_template_list()
	{
		static $templates;

		if ( ! isset($templates))
		{
			$sql = "SELECT exp_template_groups.group_name, exp_templates.template_name
					FROM	exp_template_groups, exp_templates
					WHERE  exp_template_groups.group_id =  exp_templates.group_id
					AND exp_template_groups.site_id = '".$this->db->escape_str($this->config->item('site_id'))."' ";

			$sql .= " ORDER BY exp_template_groups.group_name, exp_templates.template_name";

			$query = $this->db->query($sql);

			foreach ($query->result_array() as $row)
			{
				$templates[$row['group_name'].'/'.$row['template_name']] = $row['group_name'].'/'.$row['template_name'];
			}
		}

		return $templates;
	}

	/**
	 * Get HTML Buttons
	 *
	 * @access	public
	 * @param	int		member_id
	 * @param	bool	if the default button set should be loaded if user has no buttons
	 * @return	object
	 */
	function get_html_buttons($member_id = 0, $load_default_buttons = TRUE)
	{
		$this->db->from('html_buttons');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('member_id', $member_id);
		$this->db->order_by('tag_order');
		$buttons = $this->db->get();

		// count the buttons, if there aren't any, return the default button set
		if ($buttons->num_rows() == 0 AND $load_default_buttons === TRUE)
		{
			$this->db->from('html_buttons');
			$this->db->where('site_id', $this->config->item('site_id'));
			$this->db->where('member_id', 0);
			$this->db->order_by('tag_order');
			$buttons = $this->db->get();
		}

		return $buttons;
	}

	/**
	 * Delete HTML Button
	 *
	 * @access	public
	 * @return	NULL
	 */
	function delete_html_button($id)
	{
		$this->db->from('html_buttons');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('id', $id);
		$this->db->delete();
	}

	/**
	 * Update HTML Buttons
	 *
	 * @access	public
	 * @return	object
	 */
	function update_html_buttons($member_id, $buttons, $remove_buttons = TRUE)
	{
		if ($remove_buttons != FALSE)
		{
			// remove all buttons for this member
			$this->db->where('site_id', $this->config->item('site_id'));
			$this->db->where('member_id', $member_id);
			$this->db->from('html_buttons');
			$this->db->delete();
		}

		// now add in the new buttons
		foreach ($buttons as $button)
		{
			$this->db->insert('html_buttons', $button);
		}

	}

	/**
	 * Unique Upload Name
	 *
	 * @access	public
	 * @return	boolean
	 */
	function unique_upload_name($name, $cur_name, $edit)
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('name', $name);
		$this->db->from('upload_prefs');

		$count = $this->db->count_all_results();

		if (($edit == FALSE OR ($edit == TRUE && $name != $cur_name)) && $count > 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}

// EOF
