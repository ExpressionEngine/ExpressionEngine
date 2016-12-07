<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\Addons\FilePicker\FilePicker;

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

// --------------------------------------------------------------------

/**
 * ExpressionEngine File Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class File_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'File',
		'version'	=> '1.0.0'
	);

	var $has_array_data = TRUE;

	var $_dirs = array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		parent::__construct();
		ee()->load->library('file_field');
	}

	// --------------------------------------------------------------------

	/**
	 * Validate the upload
	 *
	 * @access	public
	 */
	function validate($data)
	{
		// Is it required but empty?
		if (($this->settings['field_required'] === TRUE
			|| $this->settings['field_required'] == 'y')
				&& empty($data))
		{
			return array('value' => '', 'error' => lang('required'));
		}

		// Is it optional and empty?
		if (($this->settings['field_required'] === FALSE
			|| $this->settings['field_required'] == 'n')
				&& empty($data))
		{
			return array('value' => '');
		}


		// Does it look like '{filedir_n}file_name.ext'?
		if (preg_match('/^{filedir_(\d+)}/', $data, $matches))
		{
			$upload_location_id = $matches[1];
			$file_name = str_replace($matches[0], '', $data);

			$file = ee('Model')->get('File')
				->filter('site_id', ee()->config->item('site_id'))
				->filter('upload_location_id', $upload_location_id)
				->filter('file_name', $file_name)
				->first();

			if ($file)
			{
				$check_permissions = FALSE;

				// Is this an edit?
				if ($this->content_id)
				{
					// Are we validating on grid data?
					if (isset($this->settings['grid_row_id']))
					{
						ee()->load->model('grid_model');
						$rows = ee()->grid_model->get_entry_rows($this->content_id, $this->settings['grid_field_id'], $this->settings['grid_content_type']);

						// If this filed was we need to check permissions.
						if ($rows[$this->content_id][$this->settings['grid_row_id']] != $data)
						{
							$check_permissions = TRUE;
						}
					}
					else
					{
						$entry = ee('Model')->get('ChannelEntry', $this->content_id)->first();
						$field_name = $this->name();

						// If this filed was we need to check permissions.
						if ($entry && $entry->$field_name != $data)
						{
							$check_permissions = TRUE;
						}
					}
				}
				else
				{
					$check_permissions = TRUE;
				}

				if ($check_permissions &&
					$file->memberGroupHasAccess(ee()->session->userdata['group_id']) == FALSE)
				{
					return array('value' => '', 'error' => lang('directory_no_access'));
				}

				return array('value' => $data);
			}
		}

		return array('value' => '', 'error' => lang('invalid_selection'));
	}

	// --------------------------------------------------------------------

	/**
	 * Save the correct value {fieldir_\d}filename.ext
	 *
	 * @access	public
	 */
	function save($data)
	{
		// validate does all of the work.
		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Show the publish field
	 *
	 * @access	public
	 */
	function display_field($data)
	{
		$allowed_file_dirs		= (isset($this->settings['allowed_directories']) && $this->settings['allowed_directories'] != 'all')
			? $this->settings['allowed_directories']
			: '';
		$content_type			= (isset($this->settings['field_content_type'])) ? $this->settings['field_content_type'] : 'all';
		$existing_limit			= (isset($this->settings['num_existing'])) ? $this->settings['num_existing'] : 0;
		$show_existing			= (isset($this->settings['show_existing'])) ? $this->settings['show_existing'] : 'n';
		$filebrowser			= (REQ == 'CP');

		if (REQ == 'CP')
		{
			ee()->lang->loadfile('fieldtypes');

			if ($allowed_file_dirs == '')
			{
				$allowed_file_dirs = 'all';
			}

			$fp = ee('CP/FilePicker')->make($allowed_file_dirs);

			$fp_link = $fp->getLink()
				->withValueTarget($this->field_name)
				->withNameTarget($this->field_name)
				->withImage($this->field_name);

			// If we are showing a single directory respect its default modal view
			if ($allowed_file_dirs != 'all' && (int) $allowed_file_dirs)
			{
				$dir = ee('Model')->get('UploadDestination', $allowed_file_dirs)
					->first();

				switch ($dir->default_modal_view)
				{
					case 'thumb':
						$fp_link->asThumbs();
						break;

					default:
						$fp_link->asList();
						break;
				}
			}

			$fp_upload = clone $fp_link;
			$fp_upload
				->setText(lang('upload_file'))
				->setAttribute('class', 'btn action file-field-filepicker');

			$fp_edit = clone $fp_link;
			$fp_edit
				->setText('')
				->setAttribute('title', lang('edit'))
				->setAttribute('class', 'file-field-filepicker');

			$file = $this->_parse_field($data);

			if ($file)
			{
				$fp_edit->setSelected($file->file_id);
			}

			ee()->cp->add_js_script(array(
				'file' => array(
					'fields/file/cp'
				),
			));

			return ee('View')->make('file:publish')->render(array(
				'field_name' => $this->field_name,
				'value' => $data,
				'file' => $file,
				'is_image' => ($file && $file->isImage()),
				'thumbnail' => ee('Thumbnail')->get($file)->url,
				'fp_url' => $fp->getUrl(),
				'fp_upload' => $fp_upload,
				'fp_edit' => $fp_edit
			));
		}

		$this->_frontend_js();

		return ee()->file_field->field(
			$this->field_name,
			$data,
			$allowed_file_dirs,
			$content_type,
			$filebrowser,
			($show_existing == 'y') ? $existing_limit : NULL
		);
	}

	/**
	 * Return a status of "warning" if the file is missing, otherwise "ok"
	 *
	 * @return string "warning" if the file is missing, "ok" otherwise
	 */
	public function get_field_status($data)
	{
		$status = 'ok';

		$file = $this->_parse_field($data);

		if ( $file && ! $file->exists())
		{
			$status = 'warning';
		}

		return $status;
	}

	private function _parse_field($data)
	{
		$file = NULL;

		// If the file field is in the "{filedir_n}image.jpg" format
		if (preg_match('/^{filedir_(\d+)}/', $data, $matches))
		{
			// Set upload directory ID and file name
			$dir_id = $matches[1];
			$file_name = str_replace($matches[0], '', $data);

			$file = ee('Model')->get('File')
				->filter('file_name', $file_name)
				->filter('upload_location_id', $dir_id)
				->filter('site_id', ee()->config->item('site_id'))
				->first();
		}
		// If file field is just a file ID
		else if (! empty($data) && is_numeric($data))
		{
			$file = ee('Model')->get('File', $data)->first();
		}

		return $file;
	}

	// --------------------------------------------------------------------

	/**
	 * Basic javascript interaction on the frontend
	 *
	 * @access	public
	 */
	protected function _frontend_js()
	{
		ee()->load->library('javascript');

		if (empty(ee()->session->cache['file_field']['js']))
		{
			ee()->session->cache['file_field']['js'] = TRUE;

			$script = <<<JSC
			$(document).ready(function() {
				function setupFileField(container) {
					var last_value = [],
						fileselector = container.find('.no_file'),
						hidden_name = container.find('input[name*="_hidden_file"]').prop('name'),
						placeholder;

					if ( ! hidden_name) {
						return;
					}

					remove = $('<input/>', {
						'type': 'hidden',
						'value': '',
						'name': hidden_name.replace('_hidden_file', '')
					});

					container.find(".remove_file").click(function() {
						container.find("input[type=hidden][name*='hidden']").val(function(i, current_value) {
							last_value[i] = current_value;
							return '';
						});
						container.find(".file_set").hide();
						container.find('.sub_filename a').show();
						fileselector.show();
						container.append(remove);

						return false;
					});

					container.find('.undo_remove').click(function() {
						container.find("input[type=hidden]").val(function(i) {
							return last_value.length ? last_value[i] : '';
						});
						container.find(".file_set").show();
						container.find('.sub_filename a').hide();
						fileselector.hide();
						remove.remove();

						return false;
					});
				}
				// most of them
				$('.file_field').not('.grid_field .file_field').each(function() {
					setupFileField($(this));
				});

				// in grid
				Grid.bind('file', 'display', function(cell) {
					setupFileField(cell);
				})
			});
JSC;
			ee()->javascript->output($script);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Prep the publish data
	 *
	 * @access	public
	 */
	function pre_process($data)
	{
		return ee()->file_field->parse_field($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Runs before the channel entries loop on the front end
	 *
	 * @param array $data	All custom field data about to be processed for the front end
	 * @return void
	 */
	function pre_loop($data)
	{
		ee()->file_field->cache_data($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Replace frontend tag
	 *
	 * @access	public
	 */
	function replace_tag($file_info, $params = array(), $tagdata = FALSE)
	{
		// Make sure we have file_info to work with
		if ($tagdata !== FALSE && $file_info === FALSE)
		{
			$tagdata = ee()->TMPL->parse_variables($tagdata, array());
		}

		// Experimental parameter, do not use
		if (isset($params['raw_output']) && $params['raw_output'] == 'yes')
		{
			return $file_info['raw_output'];
		}

		// Let's allow our default thumbs to be used inside the tag pair
		if (isset($file_info['path']) && isset($file_info['filename']) && isset($file_info['extension']))
		{
			$file_info['url:thumbs'] = $file_info['path'].'_thumbs/'.$file_info['filename'].'.'.$file_info['extension'];
		}

		$file_info['id_path'] = array('/'.$file_info['file_id'], array('path_variable' => TRUE));

		// Make sure we have file_info to work with
		if ($tagdata !== FALSE && isset($file_info['file_id']))
		{
			return ee()->TMPL->parse_variables($tagdata, array($file_info));
		}

		if ( ! empty($file_info['path'])
			&& ! empty($file_info['filename'])
			&& $file_info['extension'] !== FALSE)
		{
			$full_path = $file_info['path'].$file_info['filename'].'.'.$file_info['extension'];

			if (isset($params['wrap']))
			{
				return $this->_wrap_it($file_info, $params['wrap'], $full_path);
			}

			return $full_path;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Replace frontend tag (with a modifier catchall)
	 *
	 * Here, the modifier is the short name of the image manipulation,
	 * e.g. "small" in {about_image:small}
	 *
	 * @access	public
	 */
	function replace_tag_catchall($file_info, $params = array(), $tagdata = FALSE, $modifier)
	{
		// These are single variable tags only, so no need for replace_tag
		if ($modifier)
		{
			$key = 'url:'.$modifier;

			if ($modifier == 'thumbs')
			{
				if (isset($file_info['path']) && isset($file_info['filename']) && isset($file_info['extension']))
				{
			 		$data = $file_info['path'].'_thumbs/'.$file_info['filename'].'.'.$file_info['extension'];
				}
			}
			elseif (isset($file_info[$key]))
			{
				$data = $file_info[$key];
			}

			if (empty($data))
			{
				return $tagdata;
			}

			if (isset($params['wrap']))
			{
				return $this->_wrap_it($file_info, $params['wrap'], $data);
			}

			return $data;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Wrap it helper function
	 *
	 * @access	private
	 */
	function _wrap_it($file_info, $type, $full_path)
	{
		if ($type == 'link')
		{
			ee()->load->helper('url_helper');

			return $file_info['file_pre_format']
				.anchor($full_path, $file_info['filename'], $file_info['file_properties'])
				.$file_info['file_post_format'];
		}
		elseif ($type == 'image')
		{
			$properties = ( ! empty($file_info['image_properties'])) ? ' '.$file_info['image_properties'] : '';

			return $file_info['image_pre_format']
				.'<img src="'.$full_path.'"'.$properties.' alt="'.$file_info['filename'].'" />'
				.$file_info['image_post_format'];
		}

		return $full_path;
	}

	// --------------------------------------------------------------------

	/**
	 * Display settings screen
	 *
	 * @access	public
	 */
	function display_settings($data)
	{
		ee()->lang->loadfile('fieldtypes');
		ee()->load->model('file_upload_preferences_model');

		// And now the directory
		$allowed_directories = ( ! isset($data['allowed_directories'])) ? 'all' : $data['allowed_directories'];

		// Show existing files? checkbox, default to yes
		$show_existing = ( ! isset($data['show_existing'])) ? 'y' : $data['show_existing'];

		// Number of existing files to show? 0 means all
		$num_existing = ( ! isset($data['num_existing'])) ? 50 : $data['num_existing'];

		$directory_choices = array('all' => lang('all'));
		$directory_choices += ee('Model')->get('UploadDestination')
			->fields('id', 'name')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('module_id', 0)
			->all()
			->getDictionary('id', 'name');

		$settings = array(
			'field_options_file' => array(
				'label' => 'field_options',
				'group' => 'file',
				'settings' => array(
					array(
						'title' => 'file_ft_content_type',
						'desc' => 'file_ft_content_type_desc',
						'fields' => array(
							'field_content_type' => array(
								'type' => 'select',
								'choices' => $this->_field_content_options(),
								'value' => isset($data['field_content_type']) ? $data['field_content_type'] : 'all'
							)
						)
					),
					array(
						'title' => 'file_ft_allowed_dirs',
						'desc' => 'file_ft_allowed_dirs_desc',
						'fields' => array(
							'allowed_directories' => array(
								'type' => 'select',
								'choices' => $directory_choices,
								'value' => $allowed_directories
							)
						)
					)
				)
			),
			'channel_form_settings_file' => array(
				'label' => 'channel_form_settings',
				'group' => 'file',
				'settings' => array(
					array(
						'title' => 'file_ft_show_files',
						'desc' => 'file_ft_show_files_desc',
						'fields' => array(
							'show_existing' => array(
								'type' => 'yes_no',
								'value' => $show_existing
							)
						)
					),
					array(
						'title' => 'file_ft_limit',
						'desc' => 'file_ft_limit_desc',
						'fields' => array(
							'num_existing' => array(
								'type' => 'text',
								'value' => $num_existing
							)
						)
					)
				)
			)
		);

		return $settings;
	}

	// --------------------------------------------------------------------

	public function grid_display_settings($data)
	{
		$settings = $this->display_settings($data);

		$grid_settings = array();

		foreach ($settings as $value) {
			$grid_settings[$value['label']] = $value['settings'];
		}

		return $grid_settings;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns dropdown-ready array of allowed file types for upload
	 */
	private function _field_content_options()
	{
		return array('all' => lang('all'), 'image' => lang('type_image'));
	}

	// --------------------------------------------------------------------

	/**
	 * Table row helper
	 *
	 * Help simplify the form building and enforces a strict layout. If
	 * you think this table needs to look different, go bug James.
	 *
	 * @param	left cell content
	 * @param	right cell content
	 * @param	vertical alignment of left column
	 *
	 * @return	void - adds a row to the EE table class
	 */
	protected function _row($cell1, $cell2 = '', $valign = 'center')
	{
		if ( ! $cell2)
		{
			ee()->table->add_row(
				array('data' => $cell1, 'colspan' => 2)
			);
		}
		else
		{
			ee()->table->add_row(
				array('data' => '<strong>'.$cell1.'</strong>', 'width' => '170px', 'valign' => $valign),
				array('data' => $cell2, 'class' => 'id')
			);
		}
	}

	// --------------------------------------------------------------------

	function validate_settings($settings)
	{
		$validator = ee('Validation')->make(array(
			'allowed_directories' => 'required|allowedDirectories'
		));

		$validator->defineRule('allowedDirectories', array($this, '_validate_file_settings'));

		return $validator->validate($settings);
	}

	// --------------------------------------------------------------------

	function save_settings($data)
	{
		$defaults = array(
			'field_content_type'	=> 'all',
			'allowed_directories'	=> '',
			'show_existing'			=> '',
			'num_existing'			=> 0,
			'field_fmt' 			=> 'none'
		);

		$all = array_merge($defaults, $data);

		return array_intersect_key($all, $defaults);
	}

	// --------------------------------------------------------------------

	/**
	 * Form Validation callback
	 *
	 * @return	boolean	Whether or not to pass validation
	 */
	public function _validate_file_settings($key, $value, $params, $rule)
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Accept all content types.
	 *
	 * @param string  The name of the content type
	 * @return bool   Accepts all content types
	 */
	public function accepts_content_type($name)
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update the fieldtype
	 *
	 * @param string $version The version being updated to
	 * @return boolean TRUE if successful, FALSE otherwise
	 */
	public function update($version)
	{
		return TRUE;
	}
}

// END File_ft class

// EOF
