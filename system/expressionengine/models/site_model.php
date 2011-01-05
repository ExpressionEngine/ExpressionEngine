<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Site Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Site_model extends CI_Model {

	/**
	 * Get Site
	 *
	 * Returns all info on a site, or all sites
	 *
	 * @access	public
	 * @param	id		Site Id
	 * @return	object
	 */
	function get_site($site_id = '')
	{
		if ($site_id != '')
		{
			$this->db->where('site_id', $site_id);
		}

		$this->db->order_by('site_label');

		return $this->db->get('sites');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Site System Preferences List
	 *
	 * Returns the site system preferences
	 *
	 * @access	public
	 * @param	id		Site Id
	 * @return	object
	 */
	function get_site_system_preferences($site_id = '')
	{
		if ($site_id != '')
		{
			$this->db->where('site_id', $site_id);
		}

		$this->db->select('site_id, site_system_preferences');

		return $this->db->get('sites');
	}

	// --------------------------------------------------------------------

	/**
	 * Update Site System Preferences List
	 *
	 * Updates the site system preferences
	 *
	 * @access	public
	 * @param	Array	Preferences
	 * @param	id		Site Id
	 * @return	void
	 */
	function update_site_system_preferences($prefs, $site_id = '')
	{
		if ($site_id != '')
		{
			$this->db->where('site_id', $site_id);
		}

		$this->db->set('site_system_preferences', base64_encode(serialize($prefs)));
		$this->db->update('sites');
	}

}

/* End of site_model.php */
/* Location: ./system/expressionengine/models/site_model.php */