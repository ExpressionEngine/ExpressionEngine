<?php

namespace EllisLab\ExpressionEngine\Controllers\Settings;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

use EllisLab\ExpressionEngine\Library\CP;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Uploads Directories Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Uploads extends Settings {

	// We'll keep Grid validation errors in here
	private $image_sizes_errors = array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}
	}

	/**
	 * Main screen
	 */
	public function index()
	{
		ee()->load->model('file_upload_preferences_model');

		$upload_dirs = ee()->file_upload_preferences_model->get_file_upload_preferences(
			ee()->session->userdata('group_id')
		);

		$data = array();
		foreach ($upload_dirs as $id => $dir)
		{
			$data[] = array(
				$dir['id'],
				htmlentities($dir['name'], ENT_QUOTES),
				array('toolbar_items' => array(
					'view' => array(
						'href' => cp_url(''),
						'title' => lang('upload_btn_view')
					),
					'edit' => array(
						'href' => cp_url('settings/uploads/edit/'.$dir['id']),
						'title' => lang('upload_btn_edit')
					),
					'sync' => array(
						'href' => cp_url(''),
						'title' => lang('upload_btn_sync')
					)
				)),
				array(
					'name' => 'uploads[]',
					'value' => $dir['id']
				)
			);
		}

		$table = CP\Table::create(array('autosort' => TRUE));
		$table->setColumns(
			array(
				'upload_id' => array(
					'type'	=> CP\Table::COL_ID
				),
				'upload_name',
				'upload_manage' => array(
					'type'	=> CP\Table::COL_TOOLBAR
				),
				array(
					'type'	=> CP\Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_upload_directories', 'create_upload_directory', cp_url('settings/uploads/new-upload'));
		$table->setData($data);

		$base_url = new CP\URL('settings/uploads', ee()->session->session_id());
		$vars['table'] = $table->viewData($base_url);

		$pagination = new CP\Pagination(
			$vars['table']['limit'],
			$vars['table']['total_rows'],
			$vars['table']['page']
		);
		$vars['pagination'] = $pagination->cp_links($vars['table']['base_url']);

		ee()->view->cp_page_title = lang('upload_directories');
		ee()->view->table_heading = lang('all_upload_dirs');

		ee()->cp->set_breadcrumb(cp_url('files'), lang('file_manager'));

		ee()->cp->render('settings/uploads', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * New upload destination
	 */
	public function newUpload()
	{
		return $this->form();
	}

	// --------------------------------------------------------------------

	/**
	 * Edit upload destination
	 *
	 * @param int	$upload_id	Table name, used when coming from SQL Manager
	 *                      	for proper page-naming and breadcrumb-setting
	 */
	public function edit($upload_id)
	{
		return $this->form($upload_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Edit upload destination
	 *
	 * @param int	$upload_id	ID of upload destination to edit
	 */
	private function form($upload_id = NULL)
	{
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'name',
				'label' => 'lang:upload_name',
				'rules' => 'required|strip_tags|valid_xss_check|callback_validateName'
			),
			array(
				'field' => 'server_path',
				'label' => 'lang:upload_path',
				'rules' => 'required|strip_tags|valid_xss_check|file_exists|writable'
			),
			array(
				'field' => 'url',
				'label' => 'lang:upload_url',
				'rules' => 'required|strip_tags|valid_xss_check|callback_notHttp'
			),
			array(
				'field' => 'allowed_types',
				'label' => 'lang:upload_allowed_types',
				'rules' => 'required|strip_tags|valid_xss_check'
			),
			array(
				'field' => 'max_size',
				'label' => 'lang:upload_file_size',
				'rules' => 'integer'
			),
			array(
				'field' => 'max_width',
				'label' => 'lang:upload_image_width',
				'rules' => 'integer'
			),
			array(
				'field' => 'max_height',
				'label' => 'lang:upload_image_height',
				'rules' => 'integer'
			),
			array(
				'field' => 'image_manipulations',
				'label' => 'lang:constrain_or_crop',
				'rules' => 'callback_validateImageSizes'
			)
		));
		
		$base_url = 'settings/uploads/';
		$base_url .= ($upload_id) ? 'edit/' . $upload_id : 'new-upload';
		$base_url = cp_url($base_url);

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if ($new_upload_id = $this->saveUploadPreferences($upload_id))
			{
				ee()->view->set_message('success', lang('directory_saved'), lang('directory_saved_desc'), TRUE);

				ee()->functions->redirect(cp_url('settings/uploads/edit/' . $new_upload_id));
			}

			ee()->view->set_message('issue', lang('directory_not_saved'), lang('directory_not_saved_desc'));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('directory_not_saved'), lang('directory_not_saved_desc'));
		}

		// Get the upload directory
		$upload_dir = array();
		if ( ! empty($upload_id))
		{
			ee()->load->model('file_upload_preferences_model');
			$upload_dir = ee()->file_upload_preferences_model->get_file_upload_preferences(
				ee()->session->userdata('group_id'),
				$upload_id
			);

			if (empty($upload_dir))
			{
				show_error(lang('unauthorized_access'));
			}
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'upload_name',
					'desc' => 'upload_name_desc',
					'fields' => array(
						'name' => array(
							'type' => 'text',
							'value' => (isset($upload_dir['name'])) ? $upload_dir['name'] : '',
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'upload_url',
					'desc' => 'upload_url_desc',
					'fields' => array(
						'url' => array(
							'type' => 'text',
							'value' => (isset($upload_dir['url'])) ? $upload_dir['url'] : 'http://',
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'upload_path',
					'desc' => 'upload_path_desc',
					'fields' => array(
						'server_path' => array(
							'type' => 'text',
							'value' => (isset($upload_dir['server_path'])) ? $upload_dir['server_path'] : '',
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'upload_allowed_types',
					'desc' => '',
					'fields' => array(
						'allowed_types' => array(
							'type' => 'dropdown',
							'choices' => array(
								'img' => lang('upload_allowed_types_opt_images'),
								'all' => lang('upload_allowed_types_opt_all')
							),
							'value' => (isset($upload_dir['allowed_types'])) ? $upload_dir['allowed_types'] : 'images'
						)
					)
				)
			),
			'file_limits' => array(
				array(
					'title' => 'upload_file_size',
					'desc' => 'upload_file_size_desc',
					'fields' => array(
						'max_size' => array(
							'type' => 'text',
							'value' => (isset($upload_dir['max_size'])) ? $upload_dir['max_size'] : ''
						)
					)
				),
				array(
					'title' => 'upload_image_width',
					'desc' => 'upload_image_width_desc',
					'fields' => array(
						'max_width' => array(
							'type' => 'text',
							'value' => (isset($upload_dir['max_width'])) ? $upload_dir['max_width'] : ''
						)
					)
				),
				array(
					'title' => 'upload_image_height',
					'desc' => 'upload_image_height_desc',
					'fields' => array(
						'max_height' => array(
							'type' => 'text',
							'value' => (isset($upload_dir['max_height'])) ? $upload_dir['max_height'] : ''
						)
					)
				)
			)
		);

		$upload_destination = ee('Model')->get('UploadDestination')
			->with('FileDimension')
			->filter('id', $upload_id)
			->first();
	
		// Image manipulations Grid
		$grid = $this->getImageSizesGrid($upload_destination);

		$vars['sections']['upload_image_manipulations'] = array(
			array(
				'title' => 'constrain_or_crop',
				'desc' => 'constrain_or_crop_desc',
				'wide' => TRUE,
				'grid' => TRUE,
				'fields' => array(
					'image_manipulations' => array(
						'type' => 'html',
						'content' => ee()->load->view('_shared/table', $grid->viewData(), TRUE)
					)
				)
			)
		);

		// Member IDs NOT in $no_access have access...
		list($allowed_groups, $member_groups) = $this->getAllowedGroups($upload_destination);

		$vars['sections']['upload_privileges'] = array(
			array(
				'title' => 'upload_member_groups',
				'desc' => 'upload_member_groups_desc',
				'fields' => array(
					'upload_member_groups' => array(
						'type' => 'checkbox',
						'choices' => $member_groups,
						'value' => $allowed_groups
					)
				)
			)
		);

		// Grid validation results
		ee()->view->image_sizes_errors = $this->image_sizes_errors;

		// Category group assignment
		$this->load->model('category_model');
		$query = $this->category_model->get_category_groups('', FALSE, 1);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$cat_group_options[$row->group_id] = $row->group_name;
			}
		}

		$vars['sections']['upload_privileges'][] = array(
			'title' => 'upload_category_groups',
			'desc' => 'upload_category_groups_desc',
			'fields' => array(
				'cat_group' => array(
					'type' => 'checkbox',
					'choices' => $cat_group_options,
					'value' => ($upload_destination) ? explode('|', $upload_destination->cat_group) : array()
				)
			)
		);

		// Set current name hidden input for duplicate-name-checking in validation later
		if ($upload_destination !== NULL)
		{
			ee()->view->form_hidden = array('cur_name' => $upload_destination->name);
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->base_url = $base_url;
		ee()->view->cp_page_title = (empty($upload_id)) ? lang('create_upload_directory') : lang('edit_upload_directory');
		ee()->view->save_btn_text = (empty($upload_id)) ? 'btn_create_directory' : 'btn_edit_directory';
		ee()->view->save_btn_text_working = (empty($upload_id)) ? 'btn_create_directory_working' : 'btn_edit_directory_working';

		ee()->cp->set_breadcrumb(cp_url('files'), lang('file_manager'));
		ee()->cp->set_breadcrumb(cp_url('settings/uploads'), lang('upload_directories'));

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Not Http
	 *
	 * Custom validation, not for public access
	 *
	 * @return	boolean	Whether or not it passed validation
	 */
	public function notHttp($str = '')
	{
		if ($str == 'http://' OR $str == '')
		{
			ee()->form_validation->set_message('notHttp', lang('no_upload_dir_url'));
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Custom validation for the image sizes Grid
	 *
	 * @return	boolean	Whether or not it passed validation
	 */
	public function validateImageSizes($image_sizes = NULL)
	{
		if (empty($image_sizes))
		{
			return TRUE;
		}

		// Create an array of row names for counting to see if there are
		// duplicate column names; they should be unique
		foreach ($image_sizes['rows'] as $row_id => $columns)
		{
			$row_names[] = $columns['short_name'];
		}

		$row_name_count = array_count_values($row_names);

		foreach ($image_sizes['rows'] as $row_id => $columns)
		{
			// Short name is required
			if (trim($columns['short_name']) == '')
			{
				$this->image_sizes_errors[$row_id]['short_name'] = lang('required');
			}
			// There cannot be duplicate image manipulation names
			elseif ($row_name_count[$columns['short_name']] > 1)
			{
				$this->image_sizes_errors[$row_id]['short_name'] = lang('duplicate_image_size_name');
			}
			// Column names must contain only alpha-numeric characters and no spaces
			elseif (preg_match('/[^a-z0-9\-\_]/i', $columns['short_name']))
			{
				$this->image_sizes_errors[$row_id]['short_name'] = lang('alpha_dash');
			}

			// Double-check for form tampering (why would you tamper this?)
			if ( ! in_array($columns['resize_type'], array('crop', 'constrain')))
			{
				$this->image_sizes_errors[$row_id]['resize_type'] = lang('required');
			}
			
			foreach (array('width', 'height') as $dimension)
			{
				// Height and width are required
				if (trim($columns[$dimension]) == '')
				{
					$this->image_sizes_errors[$row_id][$dimension] = lang('required');
				}

				// Make sure height and width are positive numbers
				if (( ! is_numeric($columns[$dimension]) OR $columns[$dimension] < 0)
					AND ! isset($this->image_sizes_errors[$row_id][$dimension]))
				{
					$this->image_sizes_errors[$row_id][$dimension] = lang('is_natural');
				}
			}
		}

		// TODO: Abstract into service?
		if ( ! empty($this->image_sizes_errors))
		{
			// For AJAX validation, only send back the relvant error message
			if (AJAX_REQUEST)
			{
				// Figure out which field we need to grab out of the array
				$field = ee()->input->post('ee_fv_field');
				preg_match("/\[rows\]\[(\w+)\]\[(\w+)\]/", $field, $matches);

				// Error is present for the validating field, send it back
				if (isset($this->image_sizes_errors[$matches[1]][$matches[2]]))
				{
					ee()->form_validation->set_message('validateImageSizes', $this->image_sizes_errors[$matches[1]][$matches[2]]);

					return FALSE;
				}

				// This particular field is fine!
				return TRUE;
			}

			// Dummy error message
			ee()->form_validation->set_message('validateImageSizes', 'asdf');

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Custom validation for the directory names to ensure there
	 * are no duplicate names
	 *
	 * @return	boolean	Whether or not it passed validation
	 */
	public function validateName($name)
	{
		ee()->load->model('admin_model');

		if (ee()->admin_model->unique_upload_name(
				strtolower(strip_tags(ee()->input->post('name'))),
				strtolower(ee()->input->post('cur_name')),
				(ee()->input->post('cur_name') !== FALSE)
			))
		{
			ee()->form_validation->set_message('validateName', lang('duplicate_dir_name'));

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Sets up a GridInput object populated with image manipulation data
	 *
	 * @param	int	$upload_id		ID of upload destination to get image sizes for
	 * @return	GridInput object
	 */
	private function getImageSizesGrid($upload_destination = NULL)
	{
		// Image manipulations Grid
		$grid = ee('Grid')->make(array(
			'field_name' => 'image_manipulations',
			'reorder'    => FALSE, // Order doesn't matter here
		));
		$grid->setColumns(
			array(
				'image_manip_name' => array(
					'desc'  => 'image_manip_name_desc'
				),
				'image_manip_type' => array(
					'desc'  => 'image_manip_type_desc'
				),
				'image_manip_width' => array(
					'desc'  => 'image_manip_width_desc'
				),
				'image_manip_height' => array(
					'desc'  => 'image_manip_height_desc'
				)
			)
		);
		$grid->setNoResultsText('no_manipulations', 'add_manipulation');
		$grid->setBlankRow($this->getGridRow());

		$validation_data = ee()->input->post('image_manipulations');
		$image_sizes = array();

		// If we're coming back on a validation error, load the Grid from
		// the POST data
		if ( ! empty($validation_data))
		{
			foreach ($validation_data['rows'] as $row_id => $columns)
			{
				$image_sizes[$row_id] = array(
					// Fix this, multiple new rows won't namespace right
					'id'          => str_replace('row_id_', '', $row_id),
					'short_name'  => $columns['short_name'],
					'resize_type' => $columns['resize_type'],
					'width'       => $columns['width'],
					'height'      => $columns['height']
				);
			}

			foreach ($this->image_sizes_errors as $row_id => $columns)
			{
				$image_sizes[$row_id]['errors'] = $columns;
			}
		}
		// Otherwise, pull from the database if we're editing
		elseif ($upload_destination !== NULL)
		{
			$sizes = ee('Model')->get('FileDimension')
				->filter('upload_location_id', $upload_destination->id)->all();

			if ($sizes->count() != 0)
			{
				$image_sizes = $sizes->toArray();
			}
		}

		// Populate image manipulations Grid
		if ( ! empty($image_sizes))
		{
			$data = array();

			foreach($image_sizes as $size)
			{
				$data[] = array(
					'attrs' => array('row_id' => $size['id']),
					'columns' => $this->getGridRow($size),
				);
			}

			$grid->setData($data);
		}

		return $grid;
	}

	/**
	 * Returns an array of HTML representing a single Grid row, populated by data
	 * passed in the $size array: ('short_name', 'resize_type', 'width', 'height')
	 *
	 * @param	array	$size	Array of image size information to populate Grid row
	 * @return	array	Array of HTML representing a single Grid row
	 */
	private function getGridRow($size = array())
	{
		$defaults = array('short_name' => '', 'resize_type' => '', 'width' => '', 'height' => '');

		$size = array_merge($defaults, $size);

		return array(
			array(
				'html' => form_input('short_name', $size['short_name']),
				'error' => $this->getGridFieldError($size, 'short_name')
			),
			array(
				'html' => form_dropdown(
					'resize_type',
					array(
						'constrain' => lang('image_manip_type_opt_constrain'),
						'crop' => lang('image_manip_type_opt_crop'),
					),
					$size['resize_type']
				),
				'error' => $this->getGridFieldError($size, 'resize_type')
			),
			array(
				'html' => form_input('width', $size['width']),
				'error' => $this->getGridFieldError($size, 'width')
			),
			array(
				'html' => form_input('height', $size['height']),
				'error' => $this->getGridFieldError($size, 'height')
			)
		);
	}

	/**
	 * Returns the validation error for a specific Grid cell
	 *
	 * @param	array	$size	Array of image size information to populate Grid row
	 * @param	string	$column	Name of column to get an error for
	 * @return	array	Array of HTML representing a single Grid row
	 */
	private function getGridFieldError($size, $column)
	{
		if (isset($size['errors'][$column]))
		{
			return $size['errors'][$column];
		}

		return NULL;
	}

	/**
	 * Returns an array of member group IDs allowed to upload to this
	 * upload destination in the form of id => title, along with an
	 * array of all member groups in the same format
	 *
	 * @param	model	$upload_destination		Model object for upload destination
	 * @return	array	Array containing each of the arrays mentioned above
	 */
	private function getAllowedGroups($upload_destination = NULL)
	{
		ee()->load->model('member_model');
		$groups = ee()->member_model->get_upload_groups()->result();

		$member_groups = array();
		foreach ($groups as $group)
		{
			$member_groups[$group->group_id] = $group->group_title;
		}

		if ( ! empty($_POST))
		{
			if (isset($_POST['cat_group']))
			{
				return array($_POST['cat_group'], $member_groups);
			}

			return array(array(), $member_groups);
		}

		$no_access = array();
		if ($upload_destination !== NULL)
		{
			// Relationships aren't working
			//$no_access = $upload_destination->getNoAccess()->pluck('group_id');
			
			$no_access_query = ee()->db->get_where('upload_no_access', array('upload_id' => $upload_destination->id));

			foreach ($no_access_query->result() as $row)
			{
				$no_access[] = $row->member_group;
			}
		}

		$allowed_groups = array_diff(array_keys($member_groups), $no_access);

		// Member IDs NOT in $no_access have access...
		return array($allowed_groups, $member_groups);
	}

	/**
	 * Saves the upload destination or reports any errors from saving
	 *
	 * @param	int		$id	ID of upload destination to save
	 * @return	bool	Success or failure
	 */
	private function saveUploadPreferences($id = NULL)
	{
		// If the $id variable is present we are editing an
		// existing field, otherwise we are creating a new one
		$edit = ! empty($id);

		if ($edit)
		{
			$upload_destination = ee('Model')->get('UploadDestination')
				->with('FileDimension')
				->filter('id', $id)
				->first();

			// Reset upload destination access, we'll add it back later
			// TODO: Switch to models when we are able to delete relationships
			// based on pivot table
			ee()->db->delete('upload_no_access', array('upload_id' => $id));
		}
		else
		{
			$upload_destination = ee('Model')->make('UploadDestination');
			$upload_destination->site_id = ee()->config->item('site_id');
		}

		$server_path = ee()->input->post('server_path');
		$url = ee()->input->post('url');

		$upload_destination->name = ee()->input->post('name');
		$upload_destination->server_path = ee()->input->post('server_path');
		$upload_destination->url = ee()->input->post('url');
		$upload_destination->max_height = ee()->input->post('max_height');
		$upload_destination->max_width = ee()->input->post('max_width');
		$upload_destination->max_size = ee()->input->post('max_size');
		$upload_destination->allowed_types = ee()->input->post('allowed_types');

		if (substr($server_path, -1) != '/' AND substr($server_path, -1) != '\\')
		{
			$upload_destination->server_path .= '/';
		}

		if (substr($url, -1) != '/')
		{
			$upload_destination->url .= '/';
		}

		if ((count($this->input->post('cat_group')) > 0) && $this->input->post('cat_group'))
		{
			if ($_POST['cat_group'][0] == 0)
			{
				unset($_POST['cat_group'][0]);
			}

			$upload_destination->cat_group = implode('|', $this->input->post('cat_group'));
		}
		else
		{
			$upload_destination->cat_group = '';
		}

		$access = ee()->input->post('upload_member_groups') ?: array();

		ee()->load->model('member_model');
		$groups = ee()->member_model->get_upload_groups()->result();

		$no_access = array();
		foreach ($groups as $group)
		{
			if ( ! in_array($group->group_id, $access))
			{
				$no_access[] = $group->group_id;
			}
		}
		
		if ( ! empty($no_access))
		{
			$groups = ee('Model')->get('MemberGroup')->filter('group_id', 'IN', $no_access)->all();
			//$upload_destination->setNoAccess($groups);
		}
		
		$upload_destination->save();

		// TODO: Delete when relationships (setNoAccess) works
		if (count($no_access) > 0)
		{
			foreach($no_access as $member_group)
			{
				ee()->db->insert(
					'upload_no_access',
					array(
						'upload_id'		=> $upload_destination->id,
						'upload_loc'	=> 'cp',
						'member_group'	=> $member_group
					)
				);
			}
		}

		$image_sizes = ee()->input->post('image_manipulations');
		$row_ids = array();

		if ( ! empty($image_sizes))
		{
			foreach ($image_sizes['rows'] as $row_id => $columns)
			{
				// Existing rows
				if (strpos($row_id, 'row_id_') !== FALSE)
				{
					$image_size = ee('Model')->get('FileDimension')
						->filter('id', str_replace('row_id_', '', $row_id))
						->first();
				}
				else
				{
					$image_size = ee('Model')->make('FileDimension');
					$image_size->upload_location_id = $upload_destination->id;
				}

				$image_size->title = $columns['short_name'];
				$image_size->short_name = $columns['short_name'];
				$image_size->resize_type = $columns['resize_type'];
				$image_size->width = $columns['width'];
				$image_size->height = $columns['height'];
				$image_size->save();

				$row_ids[] = $image_size->id;
			}
		}

		// Delete deleted image size rows
		$image_sizes = ee('Model')->get('FileDimension');

		if ( ! empty($row_ids))
		{
			$image_sizes->filter('id', 'NOT IN', $row_ids);
		}

		$image_sizes->filter('upload_location_id', $upload_destination->id)->delete();

		return $upload_destination->id;
	}
}
// END CLASS

/* End of file Uploads.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Settings/Uploads.php */