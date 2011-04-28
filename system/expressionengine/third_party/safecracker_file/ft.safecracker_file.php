<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_FT.'file/ft.file.php';

class Safecracker_file_ft extends File_ft
{
	public $info = array(
		'name' => 'SafeCracker File',
		'version' => '2.1'
	);
	
	public $has_array_data = TRUE;
	
	public $upload_dir = FALSE;
	
	/**
	 * Safecracker_file_ft
	 * 
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		if (method_exists('File_ft', 'File_ft'))
		{
			parent::File_ft();
		}
		else
		{
			parent::__construct();
		}
		
		$this->EE->lang->loadfile('safecracker_file');
	}
	
	/**
	 * display_field
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function display_field($data = '')
	{
		if ( ! $this->settings['safecracker_upload_dir'])
		{
			return $this->EE->lang->line('no_upload_dir');
		}
		
		$this->add_js();
		
		$data = preg_replace('/{filedir_([0-9]+)}/', '', $data);
		
		if ($data == 'NULL')
		{
			$data = '';
			
			if (isset($this->EE->session->cache['safecracker_file']['field_data'][$this->field_id]))
			{
				$data = $this->EE->session->cache['safecracker_file']['field_data'][$this->field_id];
				//unset($this->EE->session->cache['safecracker_file']['field_data'][$this->field_name]);
			}
		}
		
		$hidden_data = set_value($this->field_name.'_hidden', '');
		
		$placeholder_data = set_value($this->field_name, '');
		
		$upload_dir = $this->EE->tools_model->get_upload_preferences($this->EE->session->userdata('group_id'), $this->settings['safecracker_upload_dir']);
		
		$thumb_src = PATH_CP_GBL_IMG.'default.png';
		
		$server_path = $upload_dir->row('server_path');
		
		if ( ! preg_match('#^(/|\w+:[/\\\])#', $server_path))
		{
			$server_path = realpath(APPPATH.'../'.$server_path).'/';
		}
		
		if ($data && file_exists($server_path.'_thumbs/thumb_'.$data))
		{
			$thumb_src = $upload_dir->row('url').'_thumbs/thumb_'.$data;
		}
		
		$this->add_css();
		
		$form_upload = array('name' => $this->field_name);
		
		if ($data)
		{
			$form_upload['disabled'] = 'disabled';
		}

		$vars = array(
			'data' => $data,
			'hidden' => form_hidden($this->field_name.'_hidden', $data),
			'upload' => form_upload($form_upload),
			'placeholder_input' => form_hidden($this->field_name, 'NULL'),
			'remove' => form_label(form_checkbox($this->field_name.'_remove', 1).' '.$this->EE->lang->line('remove_file')),
			'existing_input_name' => $this->field_name.'_existing',
			'existing_files' => $this->existing_files($server_path),
			'settings' => $this->settings,
			'default' => FALSE,
			'thumb_src' => $thumb_src
		);
		
		$old_view_path = $this->EE->load->_ci_view_path;
		
		$this->EE->load->_ci_view_path = PATH_THIRD.'safecracker_file/views/';

		$display_field = $this->EE->load->view('display_field', $vars, TRUE);
		
		$this->EE->load->_ci_view_path = $old_view_path;
		
		return $display_field;
	}
	
	/**
	 * validate
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function validate($data)
	{
		$field_name = 'field_id_'.$this->field_id;
		
		$valid = TRUE;
		
		if (isset($this->settings['field_settings']) && $field_settings = @unserialize(base64_decode($this->settings['field_settings'])))
		{
			$this->settings = array_merge($this->settings, $field_settings);
		}
		
		$this->EE->session->cache['safecracker']['field_settings'][$this->field_id] = $this->settings;
		
		//get rid of placeholder
		unset($_POST[$field_name]);
		
		if ($this->EE->input->post($field_name.'_remove') || $this->EE->input->post('field_id_'.$this->field_id.'_remove'))
		{
			unset($_POST[$field_name.'_remove'], $_POST['field_id_'.$this->field_id.'_remove']);
			
			//clear our file name if we're told to remove
			$_POST[$field_name.'_hidden'] = '';
			
			$_POST['field_id_'.$this->field_id.'_hidden'] = '';
		}
		
		if (isset($_FILES[$field_name]) && $_FILES[$field_name]['size'] > 0)
		{
			$this->EE->load->add_package_path(PATH_THIRD.'safecracker_file/');
			
			unset($this->EE->upload, $this->EE->load->_ci_loaded_files[array_search(BASEPATH.'libraries/Upload'.EXT, $this->EE->load->_ci_loaded_files)]);
			
			$this->EE->load->library('SC_Filemanager', array(), 'sc_filemanager');
		
			$this->EE->sc_filemanager->_initialize(array(
				//'overwrite' => $this->settings['safecracker_overwrite'],
				'field_id' => $this->field_id
			));
			
			$data = $this->EE->sc_filemanager->upload_file($this->settings['safecracker_upload_dir'], $field_name);
			
			if (array_key_exists('error', $data))
			{
				$this->EE->lang->loadfile('safecracker_file');
				
				$valid = $this->EE->lang->line(trim(strip_tags($data['error'])));
				
				if (REQ != 'CP')
				{
					$valid = $this->settings['field_label'].' - '.$valid;
				}
			}
			else
			{
				$this->EE->session->cache['safecracker_file']['field_data'][$this->field_id] = $this->data = $_POST[$field_name] = $data['name'];
			}
		}
		elseif ($this->EE->input->post($field_name.'_existing'))
		{
			//use existing file name
			$this->EE->session->cache['safecracker_file']['field_data'][$this->field_id] = $this->data = $_POST[$field_name] = $this->EE->input->post($field_name.'_existing', TRUE);
		}
		elseif ($this->EE->input->post($field_name.'_hidden'))
		{
			//use existing file name
			$this->EE->session->cache['safecracker_file']['field_data'][$this->field_id] = $this->data = $_POST[$field_name] = $this->EE->input->post($field_name.'_hidden', TRUE);
		}
		else
		{
			//blank
			$this->EE->session->cache['safecracker_file']['field_data'][$this->field_id] = $this->data = $_POST[$field_name] = '';
		}
		
		//clear hidden file name
		unset($_POST[$field_name.'_hidden'], $_POST[$field_name.'_existing']);
		
		if ($valid === TRUE && @$this->settings['field_required'] == 'y' && ! $this->EE->input->post($field_name))
		{
			$valid = (REQ == 'CP') ? $this->EE->lang->line('required') : array('value' => '', 'error' => $this->EE->lang->line('required'));
		}
		
		return $valid;
	}
	
	/**
	 * save
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function save($data)
	{
		if (isset($this->EE->session->cache['safecracker']['field_settings'][$this->field_id]))
		{
			$this->settings = $this->EE->session->cache['safecracker']['field_settings'][$this->field_id];
		}
		
		if (isset($this->settings['field_settings']) && $field_settings = @unserialize(base64_decode($this->settings['field_settings'])))
		{
			$this->settings = array_merge($this->settings, $field_settings);
		}
		
		if (isset($this->EE->session->cache['safecracker_file']['field_data'][$this->field_id]))
		{
			$data = $this->EE->session->cache['safecracker_file']['field_data'][$this->field_id];
		}
		
		$output = ($data && isset($this->settings['safecracker_upload_dir']) && $data !== 'NULL') ? '{filedir_'.$this->settings['safecracker_upload_dir'].'}'.$data : '';
		
		return $output;
	}
	
	/**
	 * display_settings
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function display_settings($data)
	{
		foreach ($this->_display_settings($data) as $row)
		{
			$this->EE->table->add_row($row[0], $row[1]);
		}
	}
	
	public function _display_settings($data)
	{
		$this->EE->lang->loadfile('safecracker_file');
		
		$this->EE->load->model(array('tools_model', 'field_model'));
		
		$upload_paths = array();
		
		$query = $this->EE->tools_model->get_upload_preferences($this->EE->session->userdata('group_id'));
		
		foreach ($query->result() as $row)
		{
			$upload_paths[$row->id] = $row->name;
		}
		
		$defaults = array(
			'field_content_options_file' => array(),
			'file_field_content_type' => '',
			'safecracker_upload_dir' => '',
			'safecracker_show_existing' => 0,
			'safecracker_num_existing' => '50',
			'safecracker_overwrite' => 0
		);
		
		$data = array_merge($defaults, $data);
		
		if ( ! $data['field_content_options_file'])
		{
			$content_types = $this->EE->field_model->get_field_content_types('file');
			
			$data['field_content_options_file']['any'] = $this->EE->lang->line('any');
			
			foreach($content_types as $content_type)
			{
				$vars['field_content_options_file'][$content_type] = $this->EE->lang->line('type_'.$content_type);
			}
		}
		
		return array(
			array(lang('file_type', 'field_content_file'), form_dropdown('safecracker_file_field_content_type', $data['field_content_options_file'], (isset($data['file_field_content_type'])) ? $data['file_field_content_type'] : '', 'id="safecracker_file_field_content_type"')),
			array(( ! empty($this->cell_name))?lang('choose_upload_dir'):form_label(lang('choose_upload_dir'),'safecracker_upload_dir'), form_dropdown('safecracker_upload_dir', $upload_paths, (isset($data['safecracker_upload_dir'])) ? $data['safecracker_upload_dir'] : '')),
			array(form_label(lang('show_existing')), form_checkbox('safecracker_show_existing', '1', $data['safecracker_show_existing'])),
			array(form_label(lang('num_existing')), form_input(array('name' => 'safecracker_num_existing', 'value' => $data['safecracker_num_existing'], 'style' => 'width:30px;'))),
			//array(form_label(lang('overwrite')), form_checkbox('safecracker_overwrite', '1', $data['safecracker_show_existing'])),
		);
	}
	
	/**
	 * settings
	 * 
	 * @access	public
	 * @param	mixed $key
	 * @return	void
	 */
	public function settings($key)
	{
		return (isset($this->settings[$key])) ? $this->settings[$key] : FALSE;
	}
	
	/**
	 * save_settings
	 * 
	 * @access	public
	 * @return	void
	 */
	public function save_settings()
	{
		return array(
			'file_field_content_type' => $this->EE->input->post('safecracker_file_field_content_type'),
			'safecracker_upload_dir' => $this->EE->input->post('safecracker_upload_dir'),
			'safecracker_show_existing' => $this->EE->input->post('safecracker_show_existing'),
			'safecracker_num_existing' => $this->EE->input->post('safecracker_num_existing'),
		);
	}
	
	/**
	 * display_cell_settings
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function display_cell_settings($data)
	{
		return $this->_display_settings($data);
	}
	
	/**
	 * save_cell_settings
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function save_cell_settings($data)
	{
		return array(
			'file_field_content_type' => $data['safecracker_file_field_content_type'],
			'safecracker_upload_dir' => $data['safecracker_upload_dir'],
			'safecracker_show_existing' => (int) isset($data['safecracker_show_existing']), //it's a checkbox!
			'safecracker_num_existing' => $data['safecracker_num_existing']
		);
	}
	
	/**
	 * add_js
	 * 
	 * @access	public
	 * @return	void
	 */
	public function add_js($cell = FALSE)
	{	
		$this->EE->load->library('javascript');
		
		if (empty($this->EE->session->cache['safecracker']['add_js']))
		{
			$this->EE->session->cache['safecracker']['add_js'] = TRUE;
			
			$this->EE->javascript->output('$(".safecracker_file_remove_button").live("click",function(){fs=$(this).parents(".safecracker_file_set");fs.find(".safecracker_file_thumb, .safecracker_file_input, .safecracker_file_existing").toggle();fs.find(".safecracker_file_input input").attr("disabled","");fs.find(".safecracker_file_remove input").click();h=fs.find(".safecracker_file_hidden input").val();p=(h&&$(this).is(":checked"))?h:"NULL";fs.find(".safecracker_file_placeholder_input input").val(p);return false;});');
			$this->EE->javascript->output('$(".safecracker_file_undo_button").live("click",function(){fs=$(this).parents(".safecracker_file_set");fs.find(".safecracker_file_thumb, .safecracker_file_input, .safecracker_file_existing").toggle();fs.find(".safecracker_file_input input").attr("disabled","disabled");fs.find(".safecracker_file_remove input").click();return false;});');
			/*
				$(".safecracker_file_remove_button").click(function(){
					var file_set = $(this).parents(".safecracker_file_set")[0];
					$(file_set).find(".safecracker_file_thumb, .safecracker_file_input").toggle();
					$(file_set).find(".safecracker_file_input input").attr("disabled","");
					$(file_set).find(".safecracker_file_remove input").click();
					var hidden = $(file_set).find(".safecracker_file_hidden input").val();
					var placeholder = (hidden && $(this).is(":checked")) ? hidden : "NULL";
					$(file_set).find(".safecracker_file_placeholder_input input").val(placeholder);
					return false;
				});
				$(".safecracker_file_undo_button").click(function(){
					var file_set = $(this).parents(".safecracker_file_set")[0];
					$(file_set).find(".safecracker_file_thumb, .safecracker_file_input").toggle();
					$(file_set).find(".safecracker_file_input input").attr("disabled","disabled");
					$(file_set).find(".safecracker_file_remove input").click();
					// var hidden = $(file_set).find(".safecracker_file_hidden input").val();
					// var placeholder = (hidden && $(this).is(":checked")) ? hidden : "NULL";
					// $(file_set).find(".safecracker_file_placeholder_input input").val(placeholder);
					return false;
				});
			*/
		}
		
		if (0 && $cell && empty($this->EE->session->cache['safecracker']['cell_js'][$this->field_name]))
		{
			$this->EE->session->cache['safecracker']['cell_js'][$this->field_name] = TRUE;
			
			$this->EE->javascript->output('$("#'.$this->field_name.' .matrix-btn").live("click",function(){h=$("#'.$this->field_name.' .matrix-last .safecracker_file_hidden input").attr("name").replace(/\]$/, "_hidden]");$("#'.$this->field_name.' .matrix-last .safecracker_file_hidden input").attr("name", h);});');
			/*
			$this->EE->javascript->output('
				$("#'.$this->field_name.' .matrix-btn").click(function(){
					var hidden_name = $("#'.$this->field_name.' .matrix-last .safecracker_file_hidden input").attr("name").replace(/\]$/, "_hidden]");
					$("#'.$this->field_name.' .matrix-last .safecracker_file_hidden input").attr("name", hidden_name);
				});
			');
			*/
		}
	}
	
	/**
	 * add_css
	 * 
	 * @access	public
	 * @return	void
	 */
	public function add_css()
	{
		if (empty($this->EE->session->cache['safecracker']['add_css']))
		{
			$this->EE->session->cache['safecracker']['add_css'] = TRUE;
			
			$this->EE->cp->add_to_head('<style type="text/css">.safecracker_file_set{color:#5F6C74;font-family:Helvetica, Arial, sans-serif;font-size:12px} .safecracker_file_thumb{border:1px solid #B6C0C2;position:relative;text-align:center;float:left;margin:6px 0 5px 6px;padding:5px} .safecracker_file_undo_button{color:#5F6C74;font-family:Helvetica, Arial, sans-serif;font-size:12px;text-decoration:underline;display:block;margin:0 0 8px;padding:0} .safecracker_file_thumb img{display:block} .safecracker_file_thumb p{margin:4px 0 0;padding:0} .safecracker_file_remove_button{position:absolute;top:-6px;left:-6px} .safecracker_file_existing{margin:4px 0 0;} .clear{clear:both}</style>');
			/*
			<style type="text/css">
			.safecracker_file_set {
				color: #5F6C74;
				font-family: Helvetica, Arial, sans-serif;
				font-size: 12px;
			}
			.safecracker_file_thumb {
				border: 1px solid #B6C0C2;
				position: relative;
				padding: 5px;
				text-align: center;
				float: left;
				margin: 0 0 5px;
			}
			.safecracker_file_undo_button {
				color: #5F6C74;
				font-family: Helvetica, Arial, sans-serif;
				font-size: 12px;
				text-decoration: underline;
				display: block;
				padding: 0;
				margin: 0 0 8px;
			}
			.safecracker_file_thumb img {
				display: block;
			}
			.safecracker_file_thumb p {
				padding: 0;
				margin: 4px 0 0;
			}
			.safecracker_file_remove_button {
				position: absolute;
				top: -6px;
				left: -6px;
			}
			.clear {
				clear: both;
			}
			</style>
			*/
		}
	}
	
	public function replace_tag($file_info, $params = array(), $tagdata = FALSE)
	{
		if ($tagdata == '')
		{
			$tagdata = FALSE;
		}
		
		if ($file_info)
		{
			if ( ! is_array($file_info))
			{
				$file_info = parent::pre_process($file_info);
			}
		
			return parent::replace_tag($file_info, $params, $tagdata);
		}
		
		return '';
	}
	
	/**
	 * display_cell
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function display_cell($data)
	{
		$this->parse_cell_name();
		
		if ($data)
		{
			unset($this->EE->session->cache['safecracker_file']['saved_cell'][$data]);
		}
		
		if (isset($_POST[$this->field_name]))
		{
			foreach (array_keys($_POST[$this->field_name]) as $key)
			{
				if ($key !== 'row_order')
				{
					foreach ($_POST[$this->field_name][$key] as $k => $v)
					{
						if ($v === 'NULL')
						{
							$_POST[$this->field_name][$key][$k] = sprintf('%s[%s][%s]', $this->field_name, $key, $k);
						}
					}
				}
			}
		}
	
		//preserve fields if you encounter a submit error
		if (preg_match('/^'.preg_quote($this->field_name).'\[row_new_\d+\]\[col_id_\d+\]$/', $data))
		{
			if (isset($this->EE->session->cache['safecracker_file']['cells'][$this->field_id][$this->cell_name]))
			{
				$data = $this->EE->session->cache['safecracker_file']['cells'][$this->field_id][$this->cell_name];
			}
			else
			{
				$data = $this->EE->session->cache['safecracker_file']['saved_cell'][$data] = $this->save_cell($data);
			}
		}
		else if (preg_match('/^'.preg_quote($this->field_name).'\[(row_id_\d+)\]\[(col_id_\d+)\]$/', $data, $match))
		{
			if ($field_data = $this->EE->input->post($this->field_name, TRUE))
			{
				if (isset($field_data[$match[1]][$match[2].'_hidden']))
				{
					$data = $field_data[$match[1]][$match[2].'_hidden'];
				}
				else
				{
					$data = $this->EE->session->cache['safecracker']['saved_cell'][$data] = $this->save_cell($data);
				}
			}
		}
		else if ($data == 'NULL')
		{
			if ($field_data = $this->EE->input->post($this->field_name, TRUE))
			{
				$data = $this->EE->session->cache['safecracker']['saved_cell'][$data] = $this->save_cell($data);
			}
		}
		
		$temp_view_path = $this->EE->load->_ci_view_path;
		
		$this->EE->load->_ci_view_path = PATH_THIRD.'safecracker_file/views/';
		
		$this->add_js($this->cell_name == '{DEFAULT}');
		
		$data = preg_replace('/{filedir_[0-9]+}/', '', $data);
		
		if ($data == 'NULL' || $data === $this->cell_name)
		{
			$data = '';
		}
		
		$upload_dir = $this->EE->tools_model->get_upload_preferences($this->EE->session->userdata('group_id'), $this->settings['safecracker_upload_dir']);
		
		$server_path = $upload_dir->row('server_path');
		
		if ( ! preg_match('#^(/|\w+:[/\\\])#', $server_path))
		{
			$server_path = realpath(APPPATH.'../'.$server_path).'/';
		}
		
		$thumb_src = PATH_CP_GBL_IMG.'default.png';

		if ($data && file_exists($server_path.'_thumbs/thumb_'.$data))
		{
			$thumb_src = $upload_dir->row('url').'_thumbs/thumb_'.$data;
		}
		
		$form_upload = array('name' => $this->cell_name);
		
		if ($data)
		{
			$form_upload['disabled'] = 'disabled';
		}
		
		$this->EE->load->library('javascript');
		
		$this->EE->javascript->output('
			Matrix.bind("safecracker_file", "display", function(cell){
				if (cell.row.isNew) {
					$.each(["hidden", "remove", "existing"], function (i, value) {
						var input = $(cell.dom.$td).find(".safecracker_file_"+value+" :input");
						if (input.length > 0 && ! input.attr("name").match(new RegExp("_"+value+"]$"))) {
							input.attr("name", input.attr("name").replace(/\]$/, "_"+value+"]"));
						}
					});
				}
			});
		');
		
		$vars = array(
			'data' => $data,
			'hidden' => ($data) ? form_hidden(preg_replace('/\]$/', '_hidden]', $this->cell_name), $data) : '',
			'upload' => form_upload($form_upload),//form_upload(str_replace(array('][', '[', ']'), array('_', '_', ''), $this->cell_name)),
			'placeholder_input' => form_hidden($this->cell_name, $this->cell_name),
			'remove' => form_label(form_checkbox(preg_replace('/\]$/', '_remove]', $this->cell_name), 1).' '.$this->EE->lang->line('remove_file')),
			'existing_input_name' => preg_replace('/\]$/', '_existing]', $this->cell_name),
			'existing_files' => $this->existing_files($server_path),
			'settings' => $this->settings,
			'thumb_src' => $thumb_src,
			'default' => ($this->cell_name == '{DEFAULT}'),
			'field_name' => $this->field_name,
			'field_id' => $this->field_id
		);
		
		$this->add_css();

		$view = $this->EE->load->view('display_field', $vars, TRUE);
		
		$this->EE->load->_ci_view_path = $temp_view_path;
		
		return $view;
	}
	
	public function existing_files($path)
	{
		if ( ! $this->settings('safecracker_show_existing'))
		{
			return array();
		}
		
		$files = array(
			'' => $this->EE->lang->line('choose_existing'),
		);
		
		if ( ! is_numeric($this->settings('safecracker_num_existing')))
		{
			$this->settings['safecracker_num_existing'] = '50';
		}
		
		if (is_dir($path) && FALSE !== ($opendir = opendir($path)))
		{
			$count = 0;
			
			while (FALSE !== ($filename = readdir($opendir)) && $count < $this->settings('safecracker_num_existing'))
			{
				if ($filename[0] == '.' || is_dir($path.$filename))
				{
					continue;
				}
				
				$files[$filename] = $filename;
				
				$count++;
			}
		
			closedir($opendir);
		}
		
		return $files;
	}
	
	/**
	 * save_cell
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function save_cell($data)
	{
		//echo '<pre>';
		$this->parse_cell_name();
		
		$cell_name = sprintf('%s[%s][%s]', $this->settings['field_name'], $this->settings['row_name'], $this->settings['col_name']);
		
		//if we encountered an error, the file was already saved
		//and we don't need to run this a second time
		//let's just pass the cached value and be done
		if ( ! empty($this->EE->session->cache['safecracker_file']['saved_cell'][$cell_name]))
		{
			$cell = $this->EE->session->cache['safecracker_file']['saved_cell'][$cell_name];
			unset($this->EE->session->cache['safecracker_file']['saved_cell'][$cell_name]);
			return $cell;
		}
		
		$field_data = $this->EE->input->post($this->settings['field_name'], TRUE);
		
		if ( ! empty($field_data[$this->settings['row_name']][$this->settings['col_name'].'_existing']))
		{
			return '{filedir_'.$this->settings['safecracker_upload_dir'].'}'.$field_data[$this->settings['row_name']][$this->settings['col_name'].'_existing'];
		}
		
		if ( ! empty($field_data[$this->settings['row_name']][$this->settings['col_name'].'_hidden']) && empty($field_data[$this->settings['row_name']][$this->settings['col_name'].'_remove']))
		{
			return '{filedir_'.$this->settings['safecracker_upload_dir'].'}'.$field_data[$this->settings['row_name']][$this->settings['col_name'].'_hidden'];
		}
		elseif ( ! empty($_FILES[$this->settings['field_name']]['size'][$this->settings['row_name']][$this->settings['col_name']]))
		{
			//save for later
			$_files = $_FILES;
			
			foreach ($_FILES[$this->settings['field_name']] as $key => $value)
			{
				$_FILES[$this->settings['field_name']][$key] = $value[$this->settings['row_name']][$this->settings['col_name']];
			}
			
			$this->EE->load->add_package_path(PATH_THIRD.'safecracker_file/');
			
			//do file upload
			$this->EE->load->library('SC_Filemanager', array(), 'sc_filemanager');
		
			$this->EE->sc_filemanager->_initialize(array(
				//'overwrite' => $this->settings['safecracker_overwrite'],
				'field_id' => $this->field_id
			));
			
			$data = $this->EE->sc_filemanager->upload_file($this->settings['safecracker_upload_dir'], $this->settings['field_name']);
			
			//restore
			$_FILES = $_files;
			
			if (array_key_exists('error', $data))
			{
				return '';//$data['error'];
			}
			else
			{
				$this->EE->session->cache['safecracker_file']['cells'][$this->field_id][$cell_name] = '{filedir_'.$this->settings['safecracker_upload_dir'].'}'.$data['name'];
				
				return '{filedir_'.$this->settings['safecracker_upload_dir'].'}'.$data['name'];
			}
		}
		else
		{
			return '';
		}
	}

	private function parse_cell_name()
	{
		if ( ! isset($this->settings['row_name']))
		{
			if (preg_match('/^.*\[(.*?)\]\[(.*?)\]$/', $this->cell_name, $match))
			{
				$this->settings['row_name'] = $match[1];
				$this->settings['col_name'] = $match[2];
			}
		}
	}

	/*
	function display_var_tag($file_info, $params = array(), $tagdata = FALSE)
	{
		if (is_string($file_info) && preg_match('/^{filedir_[\d]+}(.+)/', $file_info, $match))
		{
			$file_info = '/images/upload/'.$match[1];
		}
		
		return $this->replace_tag($file_info, $params, $tagdata);
	}
	
	function save_var_settings()
	{
		return $this->save_settings();
	}
	
	function display_var_settings($data)
	{
		return $this->_display_settings($data);
	}
	
	function save_var_field($data)
	{
		$this->_fix_field_name();
		
		$this->validate($data);
		
		return $this->save($data);
	}
	
	function display_var_field($data)
	{
		$this->_fix_field_name();
		
		return $this->display_field($data);
	}
	
	function _fix_field_name()
	{
		if (preg_match('/^var\[(\d+)\]$/', $this->field_name, $match))
		{
			$this->field_name = 'var_'.$match[1];
		}
	}
	*/
}

/* End of file ft.safecracker_file.php */
/* Location: ./system/expressionengine/third_party/safecracker_file/ft.safecracker_file.php */