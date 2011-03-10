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
	
	private $_image_types = array('image/png', 'image/jpeg', 'image/gif');

	/**
	 * Get Files
	 *
	 * Get a collection of files
	 *
	 * Parameter array takes an associative array with the following keys
	 * - cat_id
	 * - type
	 * - limit
	 * - offset
	 * - search_value
	 * - order
	 * - do_count
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_files($dir_id = array(), $parameters)
	{
		// Setup default parameters
		$parameters = array_merge(array(
			'type' => 'all',
			'do_count' => TRUE			
		), $parameters);
		
		$this->load->helper('text');
		// If we add a dir col- will need a join
		
		$dir_id = ( ! is_array($dir_id)) ? array($dir_id) : $dir_id;

		// We run most of this twice to get a total filter count
		$this->db->start_cache();
		
		if ( ! empty($dir_id))
		{
			$this->db->where_in("upload_location_id", $dir_id);
		}

		if ($parameters['type'] == 'image')
		{
			$this->db->where_in('mime_type', $this->_image_types);
		}
		elseif ($parameters['type'] == 'non-image')
		{
			$this->db->where_not_in('mime_type', $this->_image_types);
		}
		
		$this->db->where('site_id', $this->config->item('site_id'));
		
		if (isset($parameters['cat_id']) && ($parameters['cat_id'] == 'none' OR $parameters['cat_id']) && is_numeric($parameters['cat_id']))					 
		{
			$this->db->join('file_categories', 'exp_files.file_id = exp_file_categories.file_id', 'left');
			$this->db->where('cat_id', $parameters['cat_id']);				
		}		

		if (isset($parameters['search_value']))
		{
			switch ($search_in)
			{
				case ('file_name'):
					$this->db->like('file_name', $search_value);
					break;
				case ('file_title'):
					$this->db->like('file_title', $search_value);
					break;
				case ('custom_field'):
					$this->db->like('field_1', $search_value);

					// there are a total of 6 custom fields, so cycle through the rest of them
					for ($i = 2; $i < 6; $i++)
					{
						$this->db->or_like(sprintf('field_%s', $i), $search_value);
					}

					break;
				default:
					$this->db->like('title', $search_value)
							 ->or_like('file_name', $search_value);
			}
		}

		$this->db->stop_cache();
		
		$return_data['filter_count'] = $this->db->count_all_results('files');
		
		if ($return_data['filter_count'] === 0)
		{
			$this->db->flush_cache();
			$return_data['results'] = FALSE;
			return $return_data;
		}

		if (isset($parameters['limit']))
		{
			$this->db->limit($parameters['limit']);
		}

		if (isset($parameters['offset']))
		{
			$this->db->offset($parameters['offset']);
		}

		if (isset($parameters['order']) && is_array($parameters['order']) && count($parameters['order']) > 0)
		{
			foreach ($parameters['order'] as $key => $val)
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

	// ------------------------------------------------------------------------	

	/**
	 * Save a file
	 *
	 * @param array $data Associative array of data to save, if ID exists, the item
	 *		will be updated, not added
	 * @return bool|int Either FALSE if something went wrong or the ID of the item
	 */
	function save_file($data = array())
	{
		$successful = TRUE;

		// Define valid array keys as keys to use in array_intersect_key
		$valid_keys = array(
			'file_id' => '',
			'site_id' => '',
			'title' => '',
			'upload_location_id' => '',
			'path' => '',
			'status' => '',
			'mime_type' => '',
			'file_name' => '',
			'file_size' => '',
			'metadata' => '',
			'uploaded_by_member_id' => '',
			'upload_date' => '',
			'modified_by_member_id' => '',
			'modified_date' => '',
			'file_hw_original' => ''
		);

		// Add 6 custom fields
		for ($i = 1; $i <= 6; $i++)
		{
			$valid_keys["field_{$i}"] = '';
			$valid_keys["field_{$i}_fmt"] = '';
		}
		
		// Remove data that can't exist in the database
		$data = array_intersect_key($data, $valid_keys);	


		// Insert/update the data
		if (isset($data['file_id']))
		{
			$this->db->update('files', $data, array('id' => $data['file_id']));
		}
		else
		{
			$this->db->insert('files', $data);
		}

		// Figure out the file_id
		$file_id = (isset($data['file_id'])) ? $data['file_id'] : $this->db->insert_id();

		// Check to see if the file_id is valid
		$sucessful = ( ! is_int($file_id) AND ! $file_id > 0) ? $file_id : FALSE;

		// Deal with categories
		$this->load->model('file_category_model');
		
		foreach ($data['categories'] as $cat_id)
		{
			$result = $this->file_category_model->set_category($file_id, $cat_id);

			// If the result is a failure then set $successful to false, otherwise
			// leave it alone
			$successful = ($result) ? $successful : FALSE;
		}

		return $successful;
	}

	// ------------------------------------------------------------------------	

	/**
	 * Count Files
	 *
	 * @param 	array
	 */
	function count_files($dir_id = array())
	{
		if ( ! empty($dir_id))
		{
			$this->db->where_in('upload_location_id', $dir_id);
		}
		
		return $this->db->count_all_results('files');
	}
	
	// ------------------------------------------------------------------------	
	
	/**
	 * Get files by id
	 * 
	 * 
	 */
	function get_files_by_id($file_id = array(), $dir_id = array())
	{
		if ( ! empty($dir_id))
		{
			$this->db->where_in('upload_location_id', $dir_id);
		}

		return $this->db->where_in('file_id', $file_id)
						->get('files');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Get watermark preference
	 *
	 * @param 	array
	 */
	function get_watermark_preferences($id = array())
	{
		if ( ! empty($id))
		{
			$this->db->where_in('wm_id', $id);
		}

		return $this->db->get('file_watermarks');
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Delete Watermark Preference
	 *
	 * @param 	int		watermark ID
	 */
	function delete_watermark_preferences($id)
	{
		$this->db->where('wm_id', $id);
		$this->db->delete('file_watermarks');

		// get the name we're going to delete so that we can return it when we're done
		$this->db->select('wm_name');
		$this->db->where('wm_id', $id);
		$deleting = $this->db->get('file_watermarks');

		// ok, now remove the pref
		$this->db->where('wm_id', $id);
		$this->db->delete('file_watermarks');

		return $deleting->row('wm_name');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Select Max
	 *
	 * @param string	field to select
	 * @param string	field alias eg:  SELECT MAX(field_id) as max
	 * @param string	table to select from
	 * @return object
	 */
	function select_max($field, $as = NULL, $table)
	{
		$this->db->select_max($field, $as);

		return $this->db->get($table);
	}
	
	
	// ------------------------------------------------------------------------

	/**
	 * Update Dimensions
	 *
	 * @param array	data array
	 * @param array	field alias eg:  SELECT MAX(field_id) as max
	 * @return null
	 */
	function update_dimensions($data, $where_in = array())
	{
		if ($where_in)
		{
			foreach ($where_in as $k => $v)
			{
				$this->db->where_in($k, $v);
			}
		}
		
		$this->db->update('file_dimensions', $data); 
	}	
	
}

/* End of file file_model.php */
/* Location: ./system/expressionengine/models/file_model.php */
