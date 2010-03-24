<?php
/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: mod.pages.php
-----------------------------------------------------
 Purpose: Pages class
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}



class Pages {

	/** ----------------------------------------
	/**  Constructor
	/** ----------------------------------------*/

	function Pages()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	function load_site_pages()
	{
		$this->return_data = '';

        $sites	= ( ! $this->EE->TMPL->fetch_param('site')) ? '' : $this->EE->TMPL->fetch_param('site');

		$current_site = $this->EE->config->item('site_short_name');

        if ($sites == '')
        {
        	return $this->return_data;
        }		
		
		$site_names = explode('|', $sites);
		
		if ( ! in_array($current_site, $site_names))
		{
			
		}	$site_names[] = $current_site;

		$names = '(';
				
		foreach ($site_names as $name)
		{
			$names .= "'".$this->EE->db->escape_str($name)."', ";
		}
				
		$names = substr($names, 0, -2).')';
				
		$query = $this->EE->db->query("SELECT site_pages, site_name, site_id, site_system_preferences 
								 FROM exp_sites AS es
								 WHERE es.site_name IN ".$names);

		if ($query->num_rows() > 0)
		{
			$new_pages = array();
			
			foreach($query->result_array() as $row)
			{
				$new_pages += unserialize(base64_decode($row['site_pages']));
			
			print_r(unserialize(base64_decode($row['site_pages'])));
			}
		}
		
		$this->EE->config->set_item('site_pages', $new_pages);
		
		return $this->return_data;
	}
}
// End Pages Class

/* End of file mod.pages.php */
/* Location: ./system/expressionengine/modules/pages/mod.pages.php */