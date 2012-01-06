<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
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
		$this->EE->load->library('file_field');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Save the correct value {fieldir_\d}filename.ext
	 *
	 * @access	public
	 */
	function save($data)
	{
		$directory = $this->EE->input->post('field_id_'.$this->field_id.'_directory');
		return $this->EE->file_field->format_data($data, $directory);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Validate the upload
	 *
	 * @access	public
	 */
	function validate($data)
	{
		return $this->EE->file_field->validate(
			$data, 
			$this->field_name,
			$this->settings['field_required']
		);
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
		
		return $this->EE->file_field->field(
			$this->field_name,
			$data,
			$allowed_file_dirs,
			$content_type
		);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Prep the publish data
	 *
	 * @access	public
	 */
	function pre_process($data)
	{
		return $this->EE->file_field->parse_field($data);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Replace frontend tag
	 *
	 * @access	public
	 */
	function replace_tag($file_info, $params = array(), $tagdata = FALSE)
	{
		if ($tagdata !== FALSE)
		{
			$tagdata = $this->EE->functions->prep_conditionals($tagdata, $file_info);

			// -----------------------------
			// Any date variables to format?
			// -----------------------------
			$upload_date		= array();
			$modified_date		= array();

			$date_vars = array('upload_date', 'modified_date');

			foreach ($date_vars as $val)
			{
				if (preg_match_all("/".LD.$val."\s+format=[\"'](.*?)[\"']".RD."/s", $this->EE->TMPL->tagdata, $matches))
				{
					for ($j = 0; $j < count($matches['0']); $j++)
					{
						$matches['0'][$j] = str_replace(LD, '', $matches['0'][$j]);
						$matches['0'][$j] = str_replace(RD, '', $matches['0'][$j]);

						switch ($val)
						{
							case 'upload_date' 	: $upload_date[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
								break;
							case 'modified_date' : $modified_date[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
								break;
						}
					}
				}
			}

			foreach ($this->EE->TMPL->var_single as $key => $val)
			{
				// Format {upload_date}
				if (isset($upload_date[$key]))
				{
					foreach ($upload_date[$key] as $dvar)
						$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $file_info['upload_date'], TRUE), $val);					

					$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
				}

				// Format {modified_date}
				if (isset($modified_date[$key]))
				{
					foreach ($modified_date[$key] as $dvar)
						$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $file_info['modified_date'], TRUE), $val);					

					$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
				}
			}

			// ---------------
			// Parse the rest!
			// ---------------
			$tagdata = $this->EE->functions->var_swap($tagdata, $file_info);
			
			// More an example than anything else - not particularly useful in this context
			if (isset($params['backspace']))
			{
				$tagdata = substr($tagdata, 0, - $params['backspace']);
			}

			return $tagdata;
		}
		else if ($file_info['path'] != '' AND $file_info['filename'] != '' AND $file_info['extension'] !== FALSE)
		{
			$full_path = $file_info['path'].$file_info['filename'].'.'.$file_info['extension'];

			if (isset($params['wrap']))
			{
				if ($params['wrap'] == 'link')
				{
					return '<a href="'.$full_path.'">'.$file_info['filename'].'</a>';
				}
				elseif ($params['wrap'] == 'image')
				{
					return '<img src="'.$full_path.'" alt="'.$file_info['filename'].'" />';
				}
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
		if ($modifier)
		{
			$file_info['path'] .= '_'.$modifier.'/';	
		}

		return $this->replace_tag($file_info, $params, $tagdata);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Display settings screen
	 *
	 * @access	public
	 */
	function display_settings($data)
	{
		$this->EE->load->model('file_upload_preferences_model');
		
		$field_content_options = array('all' => lang('all'), 'image' => lang('type_image'));

		$this->EE->table->add_row(
			lang('field_content_file', 'field_content_file'),
			form_dropdown('file_field_content_type', $field_content_options, $data['field_content_type'], 'id="file_field_content_type"')
		);
		
		$directory_options['all'] = lang('all');
		
		$dirs = $this->EE->file_upload_preferences_model->get_file_upload_preferences(1);

		foreach($dirs as $dir)
		{
			$directory_options[$dir['id']] = $dir['name'];
		}
		
		$allowed_directories = ( ! isset($data['allowed_directories'])) ? 'all' : $data['allowed_directories'];

		$this->EE->table->add_row(
			lang('allowed_dirs_file', 'allowed_dirs_file'),
			form_dropdown('file_allowed_directories', $directory_options, $allowed_directories, 'id="file_allowed_directories"')
		);		
		
	}
	
	
	
	// --------------------------------------------------------------------

	function save_settings($data)
	{		
		return array(
			'field_content_type'	=> $this->EE->input->post('file_field_content_type'),
			'allowed_directories'	=> $this->EE->input->post('file_allowed_directories'),
			'field_fmt' 			=> 'none'
		);
	}	
}

// END File_ft class

/* End of file ft.file.php */
/* Location: ./system/expressionengine/fieldtypes/ft.file.php */
