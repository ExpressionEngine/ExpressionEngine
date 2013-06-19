<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
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
			$this->field_name,
			$this->settings['field_required']
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
			$filebrowser
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
				$('.file_wrapper').each(function() {
					var container = $(this),
						last_value = [],
						fileselector = container.find('.no_file');

					container.find(".remove_file").click(function() {
						container.find("input[type=hidden]").val(function(i, current_value) {
							last_value[i] = current_value;
							return '';
						});
						container.find(".file_set").hide();
						container.find('.undo_remove').show();
						fileselector.show();

						return false;
					});

					container.find('.undo_remove').click(function() {
						container.find("input[type=hidden]").val(function(i) {
							return last_value.length ? last_value[i] : '';
						});
						container.find(".file_set").show();
						container.find('.undo_remove').hide();
						fileselector.hide();

						return false;
					});
				});
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

			// -----------------------------
			// Any date variables to format?
			// -----------------------------
			$upload_date		= array();
			$modified_date		= array();

			$date_vars = array('upload_date', 'modified_date');

			foreach ($date_vars as $val)
			{
				if (preg_match_all("/".LD.$val."\s+format=[\"'](.*?)[\"']".RD."/s", ee()->TMPL->tagdata, $matches))
				{
					for ($j = 0; $j < count($matches['0']); $j++)
					{
						$matches['0'][$j] = str_replace(LD, '', $matches['0'][$j]);
						$matches['0'][$j] = str_replace(RD, '', $matches['0'][$j]);

						switch ($val)
						{
							case 'upload_date':
								$upload_date[$matches['0'][$j]] = $matches['1'][$j];
								break;
							case 'modified_date':
								$modified_date[$matches['0'][$j]] = $matches['1'][$j];
								break;
						}
					}
				}
			}

			foreach (ee()->TMPL->var_single as $key => $val)
			{
				// Format {upload_date}
				if (isset($upload_date[$key]))
				{
					$tagdata = ee()->TMPL->swap_var_single(
						$key,
						ee()->localize->format_date(
							$upload_date[$key], 
							$file_info['upload_date']
						),
						$tagdata
					);
				}

				// Format {modified_date}
				if (isset($modified_date[$key]))
				{
					$tagdata = ee()->TMPL->swap_var_single(
						$key,
						ee()->localize->format_date(
							$modified_date[$key], 
							$file_info['modified_date']
						),
						$tagdata
					);
				}
			}

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
		
		// Allowed upload file type
		$field_content_options = array('all' => lang('all'), 'image' => lang('type_image'));
		
		// And now the directory
		$directory_options['all'] = lang('all');
		
		$dirs = ee()->file_upload_preferences_model->get_file_upload_preferences(1);

		foreach($dirs as $dir)
		{
			$directory_options[$dir['id']] = $dir['name'];
		}
		
		$allowed_directories = ( ! isset($data['allowed_directories'])) ? 'all' : $data['allowed_directories'];

		// Show existing files? checkbox, default to yes
		$show_existing = ( ! isset($data['show_existing'])) ? 'y' :$data['show_existing'];

		// Number of existing files to show? 0 means all
		$num_existing = ( ! isset($data['num_existing'])) ? 0 : $data['num_existing'];


		ee()->table->set_heading(array(
			'data' => lang('file_ft_options'),
			'colspan' => 2
		));

	//	$this->_row(
	//		'<strong>'.lang('file_ft_configure').'</strong><br><i class="instruction_text">'.lang('file_ft_configure_subtext').'</i>'
	//	);

		$this->_row(
			lang('file_ft_content_type', $prefix.'field_content_type'),
			form_dropdown('file_field_content_type', $field_content_options, $data['field_content_type'], 'id="'.$prefix.'field_content_type"')
		);

		$this->_row(
			lang('file_ft_allowed_dirs', $prefix.'field_allowed_dirs'),
			form_dropdown('file_allowed_directories', $directory_options, $allowed_directories, 'id="'.$prefix.'field_allowed_dirs"')
		);

		$this->_row(
			'<strong>'.lang('file_ft_configure_frontend').'</strong><br><i class="instruction_text">'.lang('file_ft_configure_frontend_subtext').'</i>'
		);

		$this->_row(
			lang('file_ft_show_files'),
			'<label>'.form_checkbox('file_show_existing', 'y', $show_existing).' '.lang('yes').' </label> <i class="instruction_text">('.lang('file_ft_show_files_subtext').')</i>'
		);

		$this->_row(
			lang('file_ft_limit_left'),
			form_input('file_num_existing', $num_existing, 'class="center" id="'.$prefix.'num_existing" style="width: 55px;"').
			NBS.' <strong>'.lang('file_ft_limit_right').'</strong> <i class="instruction_text">('.lang('file_ft_limit_files_subtext').')</i>'
		);

		return ee()->table->generate();

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
}

// END File_ft class

/* End of file ft.file.php */
/* Location: ./system/expressionengine/fieldtypes/ft.file.php */
