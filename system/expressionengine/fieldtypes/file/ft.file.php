<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class File_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'File',
		'version'	=> '1.0'
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
		return ee()->file_field->validate(
			$data,
			$this->name(),
			$this->settings['field_required'],
			array(
				'grid_row_id' => isset($this->settings['grid_row_id'])
					? $this->settings['grid_row_id'] : NULL,
				'grid_field_id' => isset($this->settings['grid_field_id'])
					? $this->settings['grid_field_id'] : NULL
			)
		);
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
		$allowed_file_dirs		= (isset($this->settings['allowed_directories']) && $this->settings['allowed_directories'] != 'all') ? $this->settings['allowed_directories'] : '';
		$content_type			= (isset($this->settings['field_content_type'])) ? $this->settings['field_content_type'] : 'all';
		$existing_limit			= (isset($this->settings['num_existing'])) ? $this->settings['num_existing'] : 0;
		$show_existing			= (isset($this->settings['show_existing'])) ? $this->settings['show_existing'] : 'n';
		$filebrowser			= (REQ == 'CP');

		if (REQ != 'CP')
		{
			$this->_frontend_js();
			$this->_frontend_css();
		}

		return ee()->file_field->field(
			$this->field_name,
			$data,
			$allowed_file_dirs,
			$content_type,
			$filebrowser,
			($show_existing == 'y') ? $existing_limit : NULL
		);
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
	 * Basic styles on the frontend
	 *
	 * @access	public
	 */
	protected function _frontend_css()
	{
		if (empty(ee()->session->cache['file_field']['css']))
		{
			ee()->session->cache['file_field']['css'] = TRUE;

			$styles = <<<CSS
			<style type="text/css">
			.file_set {
				color: #5F6C74;
				font-family: Helvetica, Arial, sans-serif;
				font-size: 12px;
				position: relative;
			}
			.filename {
				border: 1px solid #B6C0C2;
				position: relative;
				padding: 5px;
				text-align: center;
				float: left;
				margin: 0 0 5px;
			}
			.undo_remove {
				color: #5F6C74;
				font-family: Helvetica, Arial, sans-serif;
				font-size: 12px;
				text-decoration: underline;
				display: block;
				padding: 0;
				margin: 0 0 8px;
			}
			.filename img {
				display: block;
			}
			.filename p {
				padding: 0;
				margin: 4px 0 0;
			}
			.remove_file {
				position: absolute;
				top: -6px;
				left: -6px;
				z-index: 5;
			}
			.clear {
				clear: both;
			}
			</style>
CSS;
			$styles = preg_replace('/\s+/is', ' ', $styles);
			ee()->cp->add_to_head($styles);
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

		// Make sure we have file_info to work with
		if ($tagdata !== FALSE AND $file_info === FALSE)
		{
			$tagdata = ee()->functions->prep_conditionals($tagdata, array());
		}
		else if ($tagdata !== FALSE)
		{
			$tagdata = ee()->functions->prep_conditionals($tagdata, $file_info);

			$date_vars = array(
				'upload_date' => $file_info['upload_date'],
				'modified_date' => $file_info['modified_date']
			);
			$tagdata = ee()->TMPL->parse_date_variables($tagdata, $date_vars);

			// ---------------
			// Parse the rest!
			// ---------------
			$tagdata = ee()->functions->var_swap($tagdata, $file_info);

			// More an example than anything else - not particularly useful in this context
			if (isset($params['backspace']))
			{
				$tagdata = substr($tagdata, 0, - $params['backspace']);
			}

			return $tagdata;
		}
		else if ( ! empty($file_info['path'])
			AND ! empty($file_info['filename'])
			AND $file_info['extension'] !== FALSE)
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
		$prefix = 'file';

		ee()->lang->loadfile('fieldtypes');
		ee()->load->model('file_upload_preferences_model');

		// And now the directory
		$allowed_directories = ( ! isset($data['allowed_directories'])) ? 'all' : $data['allowed_directories'];

		// Show existing files? checkbox, default to yes
		$show_existing = ( ! isset($data['show_existing'])) ? 'y' :$data['show_existing'];

		// Number of existing files to show? 0 means all
		$num_existing = ( ! isset($data['num_existing'])) ? 50 : $data['num_existing'];


		ee()->table->set_heading(array(
			'data' => lang('file_ft_options'),
			'colspan' => 2
		));

		$this->_row(
			lang('file_ft_content_type', $prefix.'field_content_type'),
			form_dropdown('file_field_content_type', $this->_field_content_options(), $data['field_content_type'], 'id="'.$prefix.'field_content_type"')
		);

		$this->_row(
			lang('file_ft_allowed_dirs', $prefix.'field_allowed_dirs').form_error('file_allowed_directories'),
			form_dropdown('file_allowed_directories', $this->_allowed_directories_options(), $allowed_directories, 'id="'.$prefix.'field_allowed_dirs"')
		);

		$this->_row(
			'<strong>'.lang('file_ft_configure_frontend').'</strong><br><i class="instruction_text">'.lang('file_ft_configure_frontend_subtext').'</i>'
		);

		$this->_row(
			lang('file_ft_show_files'),
			'<label>'.form_checkbox('file_show_existing', 'y', ($show_existing == 'y')).' '.lang('yes').' </label> <i class="instruction_text">('.lang('file_ft_show_files_subtext').')</i>'
		);

		$this->_row(
			lang('file_ft_limit_left'),
			form_input('file_num_existing', $num_existing, 'class="center" id="'.$prefix.'num_existing" style="width: 55px;"').
			NBS.' <strong>'.lang('file_ft_limit_right').'</strong> <i class="instruction_text">('.lang('file_ft_limit_files_subtext').')</i>'
		);

		$script = <<<JSC
		$(document).ready(function() {
			$('[name="file_allowed_directories"]').change(function() {
				var disabled = (this.value == 'all');

				$('[name="file_show_existing"]').attr('disabled', disabled);
				$('[name="file_num_existing"]').attr('disabled', disabled);
			}).trigger('change');
		});
JSC;
		ee()->javascript->output($script);

		return ee()->table->generate();
	}

	// --------------------------------------------------------------------

	public function grid_display_settings($data)
	{
		$allowed_directories = ( ! isset($data['allowed_directories'])) ? 'all' : $data['allowed_directories'];

		// Show existing files? checkbox, default to yes
		$show_existing = ( ! isset($data['show_existing'])) ? 'y' : $data['show_existing'];

		// Number of existing files to show? 0 means all
		$num_existing = ( ! isset($data['num_existing'])) ? 50 : $data['num_existing'];

		return array(
			$this->grid_dropdown_row(
				lang('file_ft_content_type'),
				'field_content_type',
				$this->_field_content_options(),
				isset($data['field_content_type']) ? $data['field_content_type'] : 'all'
			),
			$this->grid_dropdown_row(
				lang('file_ft_allowed_dirs'),
				'allowed_directories',
				$this->_allowed_directories_options(),
				$allowed_directories
			),
			$this->grid_padding_container('<strong>'.lang('file_ft_configure_frontend').'</strong><br><i class="instruction_text">'.lang('file_ft_configure_frontend_subtext').'</i>'),
			$this->grid_checkbox_row(
				lang('file_ft_show_files'),
				'show_existing',
				'y',
				($show_existing == 'y')
			),
			form_label(lang('file_ft_limit_left')).NBS.NBS.NBS.
				form_input(array(
					'name'	=> 'num_existing',
					'value'	=> $num_existing,
					'class'	=> 'grid_input_text_small'
				)).NBS.NBS.NBS.
				'<strong>'.lang('file_ft_limit_right').'</strong>'
		);
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

	/**
	 * Returns dropdown-ready array of allowed upload directories
	 */
	private function _allowed_directories_options()
	{
		ee()->load->model('file_upload_preferences_model');

		$directory_options['all'] = lang('all');

		if (empty($this->_dirs))
		{
			$this->_dirs = ee()->file_upload_preferences_model->get_file_upload_preferences(1);
		}

		foreach($this->_dirs as $dir)
		{
			$directory_options[$dir['id']] = $dir['name'];
		}

		return $directory_options;
	}

	// --------------------------------------------------------------------

	function validate_settings($data)
	{
		ee()->form_validation->set_rules(
			'file_allowed_directories',
			'lang:allowed_dirs_file',
			'required|callback__validate_file_settings'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Grid settings validation callback; makes sure there are file upload
	 * directories available before allowing a new file field to be saved
	 *
	 * @param	array	Grid settings
	 * @return	mixed	Validation error or TRUE if passed
	 */
	function grid_validate_settings($data)
	{
		if ( ! $this->_check_directories())
		{
			ee()->lang->loadfile('filemanager');
			return sprintf(
				lang('no_upload_directories'),
				BASE.AMP.'C=content_files'.AMP.'M=file_upload_preferences'
			);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	function save_settings($data)
	{
		return array(
			'field_content_type'	=> ee()->input->post('file_field_content_type'),
			'allowed_directories'	=> ee()->input->post('file_allowed_directories'),
			'show_existing'			=> (ee()->input->post('file_show_existing') == 'y') ? 'y': 'n',
			'num_existing'			=> ee()->input->post('file_num_existing'),
			'field_fmt' 			=> 'none'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Form Validation callback; makes sure there are file upload
	 * directories available before allowing a new file field to be saved
	 *
	 * @param	string	Selected file dir
	 * @return	boolean	Whether or not to pass validation
	 */
	public function _validate_file_settings($file_dir)
	{
		// count upload dirs
		if ( ! $this->_check_directories())
		{
			ee()->lang->loadfile('filemanager');
			ee()->form_validation->set_message(
				'_validate_file_settings',
				sprintf(
					lang('no_upload_directories'),
					BASE.AMP.'C=content_files'.AMP.'M=file_upload_preferences'
				)
			);
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Tells us whether or not upload destinations exist
	 *
	 * This is public to allow for access from Form_validation, which
	 * triggers the callbacks.
	 *
	 * @return	boolean	Whether or not upload destinations exist
	 */
	public function _check_directories()
	{
		ee()->load->model('file_upload_preferences_model');
		$upload_dir_prefs = ee()->file_upload_preferences_model->get_file_upload_preferences();

		// count upload dirs
		return (count($upload_dir_prefs) !== 0);
	}

	// --------------------------------------------------------------------

	function grid_save_settings($data)
	{
		if ( ! isset($data['show_existing']))
		{
			$data['show_existing'] = 'n';
		}

		return $data;
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
}

// END File_ft class

/* End of file ft.file.php */
/* Location: ./system/expressionengine/fieldtypes/ft.file.php */
