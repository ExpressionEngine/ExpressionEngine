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
 * Pages Model
 */
class Pages_model extends CI_Model {

    /**
     * Fetch Pages Configuration
     *
     * Fetch configuration for sites Pages Module.
     *
     * @access public
     * @return array
     */
    function fetch_configuration()
    {
		$this->db->select('configuration_value, configuration_name');
		$this->db->from('pages_configuration');
		$this->db->where_in('configuration_name', array('homepage_display', 'default_channel'));
		$this->db->where('site_id', $this->config->item('site_id'));

		return $this->db->get();
    }

    /**
     * Fetch Pages Configuration
     *
     * Fetch configuration for sites Pages Module.
     *
     * @access public
     * @return array
     */
	function fetch_site_pages_config()
	{
        $this->db->select();
        $this->db->where('site_id', $this->config->item('site_id'));

        return $this->db->get('pages_configuration');
	}

	/**
	 * Fetch Site Pages
	 *
	 * Return Array of pages for the active site
	 *
	 * @access public
	 * @return array
	 */
	function fetch_site_pages()
	{
		$site = ee('Model')->get('Site', $this->config->item('site_id'))->first();
		return $site->site_pages;
	}

	/**
	 * Update Pages Configuration
	 *
	 * @access public
	 * @param array
	 * @return void
	 */
	function update_pages_configuration($data)
	{
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->delete('pages_configuration');

		foreach($data as $key => $value)
		{
			$config = array(
								'configuration_name'  => $key,
								'configuration_value' => $value,
								'site_id' => $this->config->item('site_id')
							);

			$this->db->insert('pages_configuration', $config);
		}
	}

    /**
     * Update Pages Array
     *
     * @access public
     * @param array     Current Pages Array
     * @param array     Ids of Pages to delete
     * @return mixed    FALSE if there are no site pages or number of pages deleted
     */
	function delete_site_pages($delete_ids)
	{
	    $num = 0;

        $pages = $this->fetch_site_pages();

        if ( ! $pages)
        {
            return FALSE;
        }

		foreach($pages[$this->config->item('site_id')]['uris'] as $entry_id => $value)
		{
			if (isset($delete_ids[$entry_id]))
			{
				unset($pages[$this->config->item('site_id')]['uris'][$entry_id]);
				unset($pages[$this->config->item('site_id')]['templates'][$entry_id]);
				$num++;
			}
		}

		$this->config->set_item('site_pages', $pages);

		$site = ee('Model')->get('Site', $this->config->item('site_id'))->first();
		$site->site_pages = $pages;
		$site->save();

		return $num;
	}
}
// END CLASS

// EOF
