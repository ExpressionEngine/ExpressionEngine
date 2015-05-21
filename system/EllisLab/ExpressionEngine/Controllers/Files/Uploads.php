<?php

namespace EllisLab\ExpressionEngine\Controllers\Files;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Controllers\Files\AbstractFiles as AbstractFilesController;

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
class Uploads extends AbstractFilesController {

	// We'll keep Grid validation errors in here
	private $image_sizes_errors = array();
	private $_upload_dirs = array();

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

		$this->sidebarMenu(NULL);
		$this->stdHeader();

		ee()->load->library('form_validation');
	}

	/**
	 * New upload destination
	 */
	public function newUpload()
	{
		return $this->form();
	}

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

	/**
	 * Edit upload destination
	 *
	 * @param int	$upload_id	ID of upload destination to edit
	 */
	private function form($upload_id = NULL)
	{
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

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		$base_url = 'files/uploads/';
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

				ee()->functions->redirect(cp_url('files/uploads/edit/' . $new_upload_id));
			}

			ee()->view->set_message('issue', lang('directory_not_saved'), lang('directory_not_saved_desc'));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('directory_not_saved'), lang('directory_not_saved_desc'));
		}

		// Do not use to access attributes of directory, use $upload_dir
		// so that config.php overrides take place
		$upload_destination = ee('Model')->get('UploadDestination')
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
					'value' => ($upload_destination) ? explode('|', $upload_dir['cat_group']) : array()
				)
			)
		);

		// Set current name hidden input for duplicate-name-checking in validation later
		if ($upload_destination !== NULL)
		{
			ee()->view->form_hidden = array('cur_name' => $upload_dir['name']);
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->base_url = $base_url;
		ee()->view->cp_page_title = (empty($upload_id)) ? lang('create_upload_directory') : lang('edit_upload_directory');
		ee()->view->save_btn_text = (empty($upload_id)) ? 'btn_create_directory' : 'btn_edit_directory';
		ee()->view->save_btn_text_working = (empty($upload_id)) ? 'btn_create_directory_working' : 'btn_saving';

		ee()->cp->set_breadcrumb(cp_url('files'), lang('file_manager'));
		ee()->cp->set_breadcrumb(cp_url('files/uploads'), lang('upload_directories'));

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
			$sizes = $upload_destination->getFileDimensions();

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
		$groups = ee('Model')->get('MemberGroup')
			->filter('group_id', 'NOT IN', array(1,2,3,4))
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_title')
			->all();

		$member_groups = array();
		foreach ($groups as $group)
		{
			$member_groups[$group->group_id] = $group->group_title;
		}

		if ( ! empty($_POST))
		{
			if (isset($_POST['upload_member_groups']))
			{
				return array($_POST['upload_member_groups'], $member_groups);
			}

			return array(array(), $member_groups);
		}

		$no_access = array();
		if ($upload_destination !== NULL)
		{
			$no_access = $upload_destination->getNoAccess()->pluck('group_id');
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
				->with('FileDimensions')
				->filter('id', $id)
				->first();
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

		$no_access = ee('Model')->get('MemberGroup')
			->filter('group_id', 'NOT IN', array_merge(array(1,2,3,4), $access))
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		if ($no_access->count() > 0)
		{
			$upload_destination->setNoAccess($no_access);
		}
		else
		{
			// Remove all member groups from this upload destination
			$upload_destination->removeNoAccess();
		}

		$upload_destination->save();

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

	/**
	 * Sync upload directory
	 *
	 * @param	int		$id	ID of upload destination to sync
	 */
	public function sync($upload_id = NULL)
	{
		if (empty($upload_id))
		{
			ee()->functions->redirect(cp_url('files/uploads'));
		}

		ee()->load->model('file_upload_preferences_model');

		// Get upload destination with config.php overrides in place
		$upload_destination = ee()->file_upload_preferences_model->get_file_upload_preferences(
			ee()->session->userdata('group_id'),
			$upload_id
		);

		// Get a listing of raw files in the directory
		ee()->load->library('filemanager');
		$files = ee()->filemanager->directory_files_map(
			$upload_destination['server_path'],
			1,
			FALSE,
			$upload_destination['allowed_types']
		);
		$files_count = count($files);

		// Change the decription of this first field depending on the
		// type of files allowed
		$file_sync_desc = ($upload_destination['allowed_types'] == 'all')
			? lang('file_sync_desc') : lang('file_sync_desc_images');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'file_sync',
					'desc' => sprintf($file_sync_desc, $files_count),
					'fields' => array(
						'progress' => array(
							'type' => 'html',
							'content' => ee()->load->view('_shared/progress_bar', array('percent' => 0), TRUE)
						)
					)
				)
			)
		);

		$sizes = ee('Model')->get('FileDimension')
			->filter('upload_location_id', $upload_id)->all();

		$size_choices = array();
		$js_size = array($upload_id => '');
		foreach ($sizes as $size)
		{
			// For checkboxes
			$size_choices[$size->id] = $size->short_name .
				' <i>' . lang($size->resize_type) . ', ' . $size->width . 'px ' . lang('by') . ' ' . $size->height . 'px</i>';

			// For JS sync script
			$js_size[$size->upload_location_id][$size->id] = array('short_name' => $size->short_name, 'resize_type' => $size->resize_type, 'width' => $size->width, 'height' => $size->height, 'watermark_id' => $size->watermark_id);
		}

		// Only show the manipulations section if there are manipulations
		if ( ! empty($size_choices))
		{
			$vars['sections'][0][] = array(
				'title' => 'apply_manipulations',
				'desc' => 'apply_manipulations_desc',
				'fields' => array(
					'sizes' => array(
						'type' => 'checkbox',
						'choices' => $size_choices
					)
				)
			);
		}

		$base_url = cp_url('files/uploads/sync/'.$upload_id);

		ee()->cp->add_js_script('file', 'cp/files/synchronize');

		// Globals needed for JS script
		ee()->javascript->set_global(array(
			'file_manager' => array(
				'sync_files'      => $files,
				'sync_file_count' => $files_count,
				'sync_sizes'      => $js_size,
				'sync_baseurl'    => $base_url,
				'sync_endpoint'   => cp_url('files/uploads/do_sync_files'),
				'sync_dir_name'   => $upload_destination['name'],
			)
		));

		ee()->view->base_url = $base_url;
		ee()->view->cp_page_title = lang('sync_title');
		ee()->view->cp_page_title_alt = sprintf(lang('sync_alt_title'), $upload_destination['name']);
		ee()->view->save_btn_text = 'btn_sync_directory';
		ee()->view->save_btn_text_working = 'btn_sync_directory_working';

		ee()->cp->set_breadcrumb(cp_url('files'), lang('file_manager'));

		// Errors are given through a POST to this same page
		$errors = ee()->input->post('errors');
		if ( ! empty($errors))
		{
			ee()->view->set_message('warn', lang('directory_sync_warning'), json_decode($errors));
		}

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Sync process, largely copied from old content_files controller
	 */
	public function doSyncFiles()
	{
		$type = 'insert';
		$errors = array();
		$file_data = array();
		$replace_sizes = array();
		$db_sync = (ee()->input->post('db_sync') == 'y') ? 'y' : 'n';

		// If file exists- make sure it exists in db - otherwise add it to db and generate all child sizes
		// If db record exists- make sure file exists -  otherwise delete from db - ?? check for child sizes??

		if (
			(($sizes = ee()->input->post('sizes')) === FALSE OR
			($current_files = ee()->input->post('files')) === FALSE) AND
			$db_sync != 'y'
		)
		{
			return FALSE;
		}

		ee()->load->library('filemanager');
		ee()->load->model('file_model');

		$upload_dirs = ee()->filemanager->fetch_upload_dirs(array('ignore_site_id' => FALSE));

		foreach ($upload_dirs as $row)
		{
			$this->_upload_dirs[$row['id']] = $row;
		}

		$id = key($sizes);

		// Final run through, it syncs the db, removing stray records and thumbs
		if ($db_sync == 'y')
		{
			ee()->filemanager->sync_database($id);

			if (AJAX_REQUEST)
			{
				$errors = ee()->input->post('errors');
				if (empty($errors))
				{
					ee()->view->set_message('success', lang('directory_synced'), lang('directory_synced_desc'), TRUE);
				}

				return ee()->output->send_ajax_response(array(
					'message_type'	=> 'success'
				));
			}

			return;
		}

		$dir_data = $this->_upload_dirs[$id];

		ee()->filemanager->xss_clean_off();
		$dir_data['dimensions'] = (is_array($sizes[$id])) ? $sizes[$id] : array();
		ee()->filemanager->set_upload_dir_prefs($id, $dir_data);

		// Now for everything NOT forcably replaced

		$missing_only_sizes = (is_array($sizes[$id])) ? $sizes[$id] : array();

		// Check for resize_ids
		$resize_ids = ee()->input->post('resize_ids');

		if (is_array($resize_ids))
		{
			foreach ($resize_ids as $resize_id)
			{
				$replace_sizes[$resize_id] = $sizes[$id][$resize_id];
				unset($missing_only_sizes[$resize_id]);
			}
		}

		// @todo, bail if there are no files in the directory!  :D

		$files = ee()->filemanager->fetch_files($id, $current_files, TRUE);

		// Setup data for batch insert
		foreach ($files->files[$id] as $file)
		{
			if ( ! $file['mime'])
			{
				$errors[$file['name']] = lang('invalid_mime');
				continue;
			}

			// Clean filename
			$clean_filename = basename(ee()->filemanager->clean_filename(
				$file['name'],
				$id,
				array('convert_spaces' => FALSE)
			));

			if ($file['name'] != $clean_filename)
			{
				// It is just remotely possible the new clean filename already exists
				// So we check for that and increment if such is the case
				if (file_exists($this->_upload_dirs[$id]['server_path'].$clean_filename))
				{
					$clean_filename = basename(ee()->filemanager->clean_filename(
						$clean_filename,
						$id,
						array(
							'convert_spaces' => FALSE,
							'ignore_dupes' => FALSE
						)
					));
				}

				// Rename the file
				if ( ! @copy(ee()->_upload_dirs[$id]['server_path'].$file['name'],
							ee()->_upload_dirs[$id]['server_path'].$clean_filename))
				{
					$errors[$file['name']] = lang('invalid_filename');
					continue;
				}

				unlink($this->_upload_dirs[$id]['server_path'].$file['name']);
				$file['name'] = $clean_filename;
			}

			// Does it exist in DB?
			$query = ee()->file_model->get_files_by_name($file['name'], $id);

			if ($query->num_rows() > 0)
			{
				// It exists, but do we need to change sizes or add a missing thumb?

				if ( ! ee()->filemanager->is_editable_image($this->_upload_dirs[$id]['server_path'].$file['name'], $file['mime']))
				{
					continue;
				}

				// Note 'Regular' batch needs to check if file exists- and then do something if so
				if ( ! empty($replace_sizes))
				{
					$thumb_created = ee()->filemanager->create_thumb(
						$this->_upload_dirs[$id]['server_path'].$file['name'],
						array(
							'server_path'	=> $this->_upload_dirs[$id]['server_path'],
							'file_name'		=> $file['name'],
							'dimensions'	=> $replace_sizes,
							'mime_type'		=> $file['mime']
						),
						TRUE,	// Create thumb
						FALSE	// Overwrite existing thumbs
					);

					if ( ! $thumb_created)
					{
						$errors[$file['name']] = lang('thumb_not_created');
					}
				}

				// Now for anything that wasn't forcably replaced- we make sure an image exists
				$thumb_created = ee()->filemanager->create_thumb(
					$this->_upload_dirs[$id]['server_path'].$file['name'],
					array(
						'server_path'	=> $this->_upload_dirs[$id]['server_path'],
						'file_name'		=> $file['name'],
						'dimensions'	=> $missing_only_sizes,
						'mime_type'		=> $file['mime']
					),
					TRUE, 	// Create thumb
					TRUE 	// Don't overwrite existing thumbs
				);

				$file_path_name = ee()->_upload_dirs[$id]['server_path'].$file['name'];

				// Update dimensions
				$image_dimensions = ee()->filemanager->get_image_dimensions($file_path_name);

				$file_data = array(
					'file_id'				=> $query->row('file_id'),
					'file_size'				=> filesize($file_path_name),
					'file_hw_original'		=> $image_dimensions['height'] . ' ' . $image_dimensions['width']
				);
				ee()->file_model->save_file($file_data);

				continue;
			}

			$file_location = reduce_double_slashes(
				$dir_data['url'].'/'.$file['name']
			);

			$file_path = reduce_double_slashes(
				$dir_data['server_path'].'/'.$file['name']
			);

			$file_dim = (isset($file['dimensions']) && $file['dimensions'] != '') ? str_replace(array('width="', 'height="', '"'), '', $file['dimensions']) : '';

			$image_dimensions = ee()->filemanager->get_image_dimensions($file_path);

			$file_data = array(
				'upload_location_id'	=> $id,
				'site_id'				=> $this->config->item('site_id'),
				'mime_type'				=> $file['mime'],
				'file_name'				=> $file['name'],
				'file_size'				=> $file['size'],
				'uploaded_by_member_id'	=> ee()->session->userdata('member_id'),
				'modified_by_member_id' => ee()->session->userdata('member_id'),
				'file_hw_original'		=> $image_dimensions['height'] . ' ' . $image_dimensions['width'],
				'upload_date'			=> $file['date'],
				'modified_date'			=> $file['date']
			);


			$saved = ee()->filemanager->save_file($this->_upload_dirs[$id]['server_path'].$file['name'], $id, $file_data, FALSE);

			if ( ! $saved['status'])
			{
				$errors[$file['name']] = $saved['message'];
			}
		}

		if ($db_sync == 'y')
		{
			ee()->filemanager->sync_database($id);
		}

		if (AJAX_REQUEST)
		{
			if (count($errors))
			{
				return ee()->output->send_ajax_response(array(
					'message_type'	=> 'failure',
					'errors'		=> $errors
				));
			}

			return ee()->output->send_ajax_response(array(
				'message_type'	=> 'success'
			));
		}
	}
}
// EOF