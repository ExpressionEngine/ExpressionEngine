<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Admin Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class File_model extends CI_Model {
	

	/**
	 * Get Files
	 *
	 * Get a collection of files
	 *
	 * @access	public
	 * @param	int
	 * @param	int
	 * @param	int
	 * @param	string
	 * @return	mixed
	 */
	function get_files($dir_id = array(), $cat_id = '', $type = 'all', $limit = '', $offset = '', $search_value = '', $order = array(), $do_count = TRUE)
	{
		// If we add a dir col- will need a join
		
		$dir_id = ( ! is_array($dir_id)) ? array($dir_id) : $dir_id;

		// We run most of this twice to get a total filter count
		$this->db->start_cache();
		
		if ( ! empty($dir_id))
		{
			$this->db->where_in("upload_location_id", $dir_id);
		}

		if ($type !== 'all')
		{
			$this->db->where("id", $dir_id);
		}
		
		$this->db->where('site_id', $this->config->item('site_id'));
		
		if (($cat_id == 'none' OR $cat_id != "") && is_numeric($cat_id))					 
		{
			$this->db->join('file_categories', 'exp_files.file_id = exp_file_categories.file_id', 'left');
			$this->db->where('cat_id', $cat_id);				
		}		

		if ($search_value != '')
		{
			
		}


		$this->db->stop_cache();
		
		$return_data['filter_count'] = $this->db->count_all_results('files');
		
		if ($return_data['filter_count'] == 0)
		{
			$this->db->flush_cache();
			$return_data['results'] = FALSE;
			return $return_data;
		}


		if ($limit != '')
		{
			$this->db->limit($limit);
		}

		if ($offset != '')
		{
			$this->db->offset($offset);
		}

		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				$this->db->order_by($key, $val);
			}
		}
		else
		{
			$this->db->order_by('upload_date');
		}
		
		$return_data['results'] = $this->db->get('files');
		
		$this->db->flush_cache();

		return $return_data;
	
	}
	
	function count_files($dir_id = array())
	{
		if ( ! empty($dir_id))
		{
			$this->db->where_in('upload_location_id', $dir_id);
		}
		
		return $this->db->count_all_results('files');
	}
	
	function get_files_by_id($file_id = array(), $dir_id = array())
	{
		if ( ! empty($dir_id))
		{
			$this->db->where_in('upload_location_id', $dir_id);
		}

		$this->db->where_in('file_id', $file_id);
		
		return $this->db->get('files');
	}
}

/* End of file file_model.php */
/* Location: ./system/expressionengine/models/file_model.php */