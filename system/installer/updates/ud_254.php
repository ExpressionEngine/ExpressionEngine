<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.5.4
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://expressionengine.com
 */
class Updater {
	
	var $version_suffix = '';
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$this->EE->load->dbforge();
		
		$this->_change_member_totals_length();
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Changes column type for `total_entries` and `total_comments` in the
	 * members table from smallint to mediumint to match the columns in the
	 * channels table and stats table.
	 */
	private function _change_member_totals_length()
	{
		$this->EE->dbforge->modify_column(
			'members',
			array(
				'total_entries' => array(
					'name' => 'total_entries',
					'type' => 'mediumint(8)'
				),
				'total_comments' => array(
					'name' => 'total_comments',
					'type' => 'mediumint(8)'
				),
			)
		);
	}

	// ---------------------------------------------------------------------

	/**
	 * Clean up the menu and quicklink items so there's no XSS funny business
	 */
	private function _xss_clean_custom_links()
	{
		$members = $this->EE->db->select('member_id, quick_links, quick_tabs')
			->where('quick_links IS NOT NULL')
			->or_where('quick_tabs IS NOT NULL')
			->get('members')
			->result_array();
		
		// Sanitize quick_links and quick_tabs		
		foreach ($members as $index => $data)
		{
			// Sanitize quick_links and quick_tabs
			$members[$index]['quick_links'] = $this->_sanitize_custom_links($data['quick_links']);
			$members[$index]['quick_tabs'] = $this->_sanitize_custom_links($data['quick_tabs']);
		}

		$this->EE->db->update_batch('members', $members, 'member_id');
	}

	/**
	 * Clean up custom links given a string containing multiple links broken up 
	 * by newlines, with links broken up by pipes. 
	 *
	 * This does not remove the [removed] replacements.
	 * 
	 * @param  String $string String containing multiple custom links separated 
	 *                        by newlines with links broken up by pipes
	 * @return String         Sanitized string containing custom links
	 */
	private function _sanitize_custom_links($string)
	{
		// Each string is comprised of multiple links broken up by newlines
		$lines = explode("\n", $string);
		
		foreach ($lines as $index => $line)
		{
			// Each link is three parts, the first being the name (which is 
			// where we're concerned about XSS cleaning), the link, the order
			$links = explode('|', $line);
			$links[0] = $this->EE->security->xss_clean($links[0]);	
			$lines[$index] = implode('|', $links);
		}
		
		return implode("\n", $lines);
	}
}	
/* END CLASS */

/* End of file ud_254.php */
/* Location: ./system/expressionengine/installer/updates/ud_254.php */
