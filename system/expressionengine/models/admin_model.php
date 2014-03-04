<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Admin Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
		static $encodings;

		if ( ! isset($encodings))
		{
			$file = APPPATH.'config/languages.php';

			if ( ! file_exists($file))
			{
				return FALSE;
			}

			require_once $file;

			$encodings = array_flip($languages);
			unset($languages);
		}

		return $encodings;
	}

	// --------------------------------------------------------------------

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
		static $languages;

		if ( ! isset($languages))
		{
			$this->load->helper('directory');

			$source_dir = APPPATH.'language/';

			if (($list = directory_map($source_dir, TRUE)) !== FALSE)
			{
				foreach ($list as $file)
				{
					if (is_dir($source_dir.$file) && $file[0] != '.')
					{
						$languages[$file] = ucfirst($file);
					}
				}

				ksort($languages);
			}
		}

		return $languages;
	}

	// --------------------------------------------------------------------

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
		$this->load->library('user_agent');

		static $themes;

		if ( ! isset($themes))
		{
			$this->load->helper('directory');

			if (($list = directory_map(PATH_CP_THEME, TRUE)) !== FALSE)
			{
				foreach ($list as $file)
				{
					if (is_dir(PATH_CP_THEME.$file) && $file[0] != '.')
					{
						if (substr($file, 0, 6) == 'mobile' && ! $this->agent->is_mobile())
						{
							continue;
						}
						else
						{
							$themes[$file] = ucfirst(str_replace('_', ' ', $file));

						}
					}
				}
				ksort($themes);
			}
		}

		return $themes;
	}

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

/* End of file admin_model.php */
/* Location: ./system/expressionengine/models/admin_model.php */
