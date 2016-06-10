<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
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
	 * - date_range
	 * - date_start
	 * - date_end
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_files($dir_id = array(), $parameters = array())
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

		// Custom Date Range
		if ( ! empty($parameters['date_start'])
			AND ! empty($parameters['date_end'])
			AND empty($parameters['date_range']))
		{
			$this->db->where('upload_date >=', strtotime($parameters['date_start']));
			$this->db->where('upload_date <=', strtotime($parameters['date_end']));
		}
		// Date range based on number of days
		elseif ( ! empty($parameters['date_range']))
		{
			$this->db->where('upload_date >=', $this->localize->now - ($parameters['date_range'] * 86400));
		}

		$this->db->where('files.site_id', $this->config->item('site_id'));

		if (isset($parameters['cat_id']) && $parameters['cat_id'] != 'none' && is_numeric($parameters['cat_id']))
		{
			$this->db->join('file_categories', 'exp_files.file_id = exp_file_categories.file_id', 'left');
			$this->db->where('cat_id', $parameters['cat_id']);
		}

		if (isset($parameters['search_value']))
		{
			switch ($parameters['search_in'])
			{
				case ('file_name'):
					$this->db->like('file_name', $parameters['search_value']);
					break;
				case ('file_title'):
					$this->db->like('title', $parameters['search_value']);
					break;
				default:
					$this->db->where('(`title` LIKE "%'.$this->db->escape_like_str($parameters['search_value']).'%"
						OR `file_name` LIKE "%'.$this->db->escape_like_str($parameters['search_value']).'%")');
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
			$this->db->limit(intval($parameters['limit']));
		}
		else
		{
			$this->db->limit(100);
		}

		if (isset($parameters['offset']))
		{
			$this->db->offset(intval($parameters['offset']));
		}

		if (isset($parameters['order']) && is_array($parameters['order']) && count($parameters['order']) > 0)
		{
			foreach ($parameters['order'] as $key => $val)
			{
				// If the key is set to upload location name, then we need to
				// join upload_prefs and sort on the name there
				if ($key == 'upload_location_name')
				{
					$this->db->join('upload_prefs', 'upload_prefs.id = files.upload_location_id');
					$this->db->order_by('upload_prefs.name', $val);
					continue;
				}

				$this->db->order_by('files.'.$key, $val);
			}
		}
		else
		{
			$this->db->order_by('upload_date DESC, files.file_id DESC');
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
			'mime_type' => '',
			'file_name' => '',
			'file_size' => '',
			'description' => '',
			'credit' => '',
			'location' => '',
			'uploaded_by_member_id' => '',
			'upload_date' => '',
			'modified_by_member_id' => '',
			'modified_date' => '',
			'file_hw_original' => ''
		);

		// Remove data that can't exist in the database
		$data = array_intersect_key($data, $valid_keys);

		// Set some defaults if missing
		if ( ! isset($data['modified_by_member_id']))
		{
			$data['modified_by_member_id'] = $this->session->userdata('member_id');
		}

		if ( ! isset($data['modified_date']))
		{
			$data['modified_date'] = $this->localize->now;
		}

		if (isset($data['file_name']) OR isset($data['title']))
		{
			$data['title'] = ( ! isset($data['title'])) ? $data['file_name'] : $data['title'];
		}

		// Insert/update the data
		if (isset($data['file_id']))
		{
			$this->db->update('files', $data, array('file_id' => $data['file_id']));
		}
		else
		{
			// Upload date default for new entries
			$data['upload_date'] = ( ! isset($data['upload_date'])) ? $this->localize->now : $data['upload_date'];

			$this->db->insert('files', $data);
		}

		// Figure out the file_id
		$file_id = (isset($data['file_id'])) ? $data['file_id'] : $this->db->insert_id();

		// Check to see if the file_id is valid
		$successful = (is_numeric($file_id) AND $file_id > 0) ? $file_id : FALSE;

		// Deal with categories
		$this->load->model('file_category_model');

		if (isset($data['categories']) AND is_array($data['categories']))
		{
			foreach ($data['categories'] as $cat_id)
			{
				$result = $this->file_category_model->set($file_id, $cat_id);

				// If the result is a failure then set $successful to false, otherwise
				// leave it alone
				if ($result === FALSE)
				{
					$successful = FALSE;
					break;
				}
			}
		}

		/* -------------------------------------------
		/* 'file_after_save' hook.
		/*  - Add additional processing after file is saved
		*/
			$this->extensions->call('file_after_save', $file_id, $data);
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		return $successful;
	}

	// ------------------------------------------------------------------------

	/**
	 * Count Files
	 *
	 * @param 	array
	 */
	function count_files($dir_id = FALSE)
	{
		$dir_func = $this->_where_function($dir_id);

		if ( ! empty($dir_id))
		{
			$this->db->$dir_func('upload_location_id', $dir_id);
		}

		return $this->db->count_all_results('files');
	}

	// ------------------------------------------------------------------------

	/**
	 * Count Images
	 *
	 * @param 	array
	 */
	function count_images($dir_id = FALSE)
	{
		$this->db->like('mime_type', 'image/', 'after');
		return $this->count_files($dir_id);
	}

	// ------------------------------------------------------------------------

	/**
	 * Get files by directory
	 *
	 *
	 */
	function get_files_by_dir($dir_id)
	{
		if (empty($dir_id))
		{
			return FALSE;
		}

		$dir_func = $this->_where_function($dir_id);

		return $this->db->$dir_func('upload_location_id', $dir_id)
						->get('files');
	}

	// ------------------------------------------------------------------------

	/**
	 * Get files by name and directory
	 *
	 * @param mixed $file_name An array or string with the filename/s
	 * @param mixed $dir_id    The image directory of the files
	 * @access public
	 * @return query           The filename query result
	 */
	function get_files_by_name($file_name, $dir_id)
	{
		if (empty($file_name) OR empty($dir_id))
		{
			return FALSE;
		}

		$dir_func = $this->_where_function($dir_id);
		$this->db->$dir_func('upload_location_id', $dir_id);

		if (is_array($file_name))
		{
			foreach($file_name as $key => $file)
			{
				ee()->db->or_where('file_name', $file);
			}
		}
		else
		{
			$this->db->where("file_name = " . $this->db->escape($file_name) . " COLLATE utf8_bin");
		}

		return $this->db->get('files');
	}

	// ------------------------------------------------------------------------

	/**
	 * Get files by id
	 *
	 *
	 */
	function get_files_by_id($file_id, $dir_id = FALSE)
	{
		$dir_func = $this->_where_function($dir_id);
		$file_func = $this->_where_function($file_id);

		if ( ! empty($dir_id))
		{
			$this->db->$dir_func('upload_location_id', $dir_id);
		}


		return $this->db->$file_func('file_id', $file_id)
						->get('files');
	}

	// ------------------------------------------------------------------------

	/**
	 * Get dimensions by dir_id
	 *
	 *
	 */
	function get_dimensions_by_dir_id($dir_id = FALSE, $with_watermarks = FALSE)
	{
		$dir_func = $this->_where_function($dir_id);

		if ($with_watermarks)
		{
			$this->db->join('file_watermarks', 'wm_id = watermark_id', 'left');
		}

		if ( ! empty($dir_id))
		{
			$this->db->$dir_func('upload_location_id', $dir_id);
		}

		return $this->db->get('file_dimensions');
	}


	// ------------------------------------------------------------------------

	/**
	 * Get watermark preference
	 *
	 * @param 	array
	 */
	function get_watermark_preferences($id = array())
	{
		$func = $this->_where_function($id);

		if ( ! empty($id))
		{
			$this->db->$func('wm_id', $id);
		}

		return $this->db->get('file_watermarks');
	}

	// ------------------------------------------------------------------------

	/**
	 * Get the correct db where function depending
	 * on what the datatype is.
	 *
	 * @param 	mixed
	 * @return	string
	 */
	function _where_function($var)
	{
		if (is_array($var))
		{
			return 'where_in';
		}

		return 'where';
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

		// clean up resized
		$this->db->where('watermark_id', $id);
		$this->db->update('file_dimensions', array('watermark_id' => 0));

		// And reset any dimensions using this watermark to 0
		$this->update_dimensions(array('watermark_id' => 0), array('watermark_id' => array($id)));

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


	// --------------------------------------------------------------------

	/**
	 * Get Raw Files
	 *
	 * @access	public
	 * @param	int
	 * @return	mixed
	 */
	function get_raw_files($directories = array(), $allowed_types = array(), $full_server_path = '', $hide_sensitive_data = FALSE, $get_dimensions = FALSE, $files_array = array())
	{
		$files = array();

		if ( ! is_array($directories))
		{
			$directories = array($directories);
		}

		if ( ! is_array($allowed_types))
		{
			$allowed_types = array($allowed_types);
		}

		ee()->load->helper('file');
		ee()->load->helper('text');
		ee()->load->helper('directory');
		ee()->load->library('encrypt');
		ee()->load->library('mime_type');

		if (count($directories) == 0)
		{
			return $files;
		}

		foreach ($directories as $key => $directory)
		{
			if ( ! empty($files_array))
			{
				$source_dir = rtrim(realpath($directory), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

				foreach($files_array as $file)
				{
					$directory_files[] = get_file_info($source_dir.$file);
				}
			}
			else
			{
				$directory_files = get_dir_file_info($directory); //, array('name', 'server_path', 'size', 'date'));
			}

			if ($allowed_types[$key] == 'img')
			{
				$allowed_type = array('image/gif','image/jpeg','image/png');
			}
			elseif ($allowed_types[$key] == 'all')
			{
				$allowed_type = array();
			}

			$dir_name_length = strlen(reduce_double_slashes($directory)); // used to create relative paths below

			if ($directory_files)
			{
				foreach ($directory_files as $file)
				{
					if ($full_server_path != '')
					{
						$file['relative_path'] = $full_server_path; // allow for paths to be passed into this function
					}

					$file['short_name'] = ellipsize($file['name'], 16, .5);

					$file['relative_path'] = (isset($file['relative_path'])) ?
					 	reduce_double_slashes($file['relative_path']) :
						reduce_double_slashes($directory);

					$file['encrypted_path'] = rawurlencode($this->encrypt->encode($file['relative_path'].$file['name'], $this->session->sess_crypt_key));

					$file['mime'] = ee()->mime_type->ofFile($file['relative_path'].$file['name']);

					if ($get_dimensions)
					{
						if (function_exists('getimagesize'))
						{
							if ($D = @getimagesize($file['relative_path'].$file['name']))
							{
								$file['dimensions'] = $D[3];
							}
						}
						else
						{
							// We can't give dimensions, so return a blank string
							$file['dimensions'] = '';
						}
					}

					// Add relative directory path information to name
					$file['name'] = substr($file['relative_path'], $dir_name_length).$file['name'];

					// Don't include server paths - useful for ajax requests
					if ($hide_sensitive_data)
					{
						unset($file['relative_path'], $file['server_path']);
					}

					if (count($allowed_type) == 0 OR in_array($file['mime'], $allowed_type))
					{
						$files[] = $file;
					}
				}
			}
		}

		sort($files);

		return $files;
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes a file that's been stored on the database. Completely removes
	 * database records and the file itself.
	 *
	 * @param array $file_ids An array of file IDs from exp_files
	 * @param boolean $delete_raw_files Set this to FALSE to not delete the files
	 * @return boolean TRUE if successful, FALSE otherwise
	 */
	public function delete_files($file_ids = array(), $delete_raw_files = TRUE)
	{
		$return = TRUE;
		$deleted = array();

		if ( ! is_array($file_ids))
		{
			$file_ids = array($file_ids);
		}

		$file_information = $this->get_files_by_id($file_ids);

		foreach ($file_information->result() as $file)
		{
			// Store deleted file information for hook
			$deleted[] = $file;

			if ($delete_raw_files)
			{
				// Then delete the raw file
				$this->delete_raw_file(
					$file->file_name,
					$file->upload_location_id
				);
			}

			// Remove any related category records
			$this->load->model('file_category_model');
			$this->file_category_model->delete($file->file_id);

			// Now, we can delete the DB record
			$this->db->delete('files', array(
				'file_id' => $file->file_id
			));
		}

		/* -------------------------------------------
		/* 'files_after_delete' hook.
		/*  - Add additional processing after file deletion
		*/
			$this->extensions->call('files_after_delete', $deleted);
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		return $return;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete files by filename.
	 *
	 * Delete files in a single upload location.  This file accepts filenames to delete.
	 * If the user does not belong to the upload group, an error will be thrown.
	 *
	 * @param 	array 		array of files to delete
	 * @param 	boolean		whether or not to delete thumbnails
	 * @return 	boolean 	TRUE on success/FALSE on failure
	 */
	public function delete_files_by_name($dir_id, $files = array())
	{
		$file_ids = array();
		$file_data = $this->get_files_by_name($files, $dir_id);

		foreach ($file_data->result() as $file)
		{
			$file_ids[] = $file->file_id;
		}

		return $this->delete_files($file_ids);
	}


	// --------------------------------------------------------------------

	/**
	 * Deletes all files associated with a file (source, thumb, and dimensions)
	 *
	 * @param string $file_name The name of the file to delete
	 * @param integer $directory_id The directory ID where the file is located
	 * @param boolean $only_thumbs Set this to TRUE if you only want to delete thumbnails
	 * @return boolean TRUE if successful, FALSE otherwise
	 */
	public function delete_raw_file($file_name, $directory_id, $only_thumbs = FALSE)
	{
		$this->load->model('file_upload_preferences_model');
		$this->load->library('filemanager');

		// Get the directory's information
		$upload_dir = $this->file_upload_preferences_model->get_file_upload_preferences(
			$this->session->userdata('group_id'),
			$directory_id
		);

		// Delete the thumb
		$thumb_information = $this->filemanager->get_thumb($file_name, $directory_id);
		@unlink($thumb_information['thumb_path']);

		// Then, delete the dimensions
		$file_dimensions = $this->get_dimensions_by_dir_id($directory_id);

		foreach ($file_dimensions->result() as $file_dimension)
		{
			@unlink($upload_dir['server_path'] . '_' . $file_dimension->short_name . '/' . $file_name);
		}

		if ( ! $only_thumbs)
		{
			// Finally, delete the original
			if ( ! @unlink($upload_dir['server_path'] . $file_name))
			{
				return FALSE;
			}
		}

		return TRUE;
	}
}

// EOF
