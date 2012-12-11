<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine RTE Module Library 
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Rte_lib {
	
	// We consider the editor empty in these cases
	public $_empty = array(
		'',
		'<br>',
		'<br/>',
		'<br />',
		'<p></p>',
		'<p>​</p>' // Zero-width character
	);
	
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->lang->loadfile('rte');
	}

	// -------------------------------------------------------------------------

	/**
	 * Provides Edit Toolset Screen HTML
	 *
	 * @access	public
	 * @param	int $toolset_id The Toolset ID to be edited (optional)
	 * @return	string The page
	 */
	public function edit_toolset($toolset_id = FALSE)
	{
		if ($toolset_id === FALSE)
		{
			$toolset_id = $this->EE->input->get_post('toolset_id');
		}

		if ( ! is_numeric($toolset_id))
		{
			exit();
		}
		
		$this->EE->output->enable_profiler(FALSE);

		$this->EE->load->library(array('table','javascript'));
		$this->EE->load->model(array('rte_toolset_model','rte_tool_model'));

		// new toolset?
		if ($toolset_id == 0)
		{
			$toolset['tools'] = array();
			$toolset['name'] = '';
			$is_private = ($this->EE->input->get_post('private') == 'true');
		}
		else
		{
			// make sure user can access the existing toolset
			if ( ! $this->EE->rte_toolset_model->member_can_access($toolset_id))
			{
				$this->EE->output->send_ajax_response(array(
					'error' => lang('toolset_edit_failed')
				));
			}

			// grab the toolset
			$toolset	= $this->EE->rte_toolset_model->get($toolset_id);
			$is_private	= ($toolset['member_id'] != 0);
		}


		// get list of enabled tools
		$enabled_tools = $this->EE->rte_tool_model->get_tool_list(TRUE);

		$unused_tools = $used_tools = array();

		foreach ($enabled_tools as $tool)
		{
			$tool_index = array_search($tool['tool_id'], $toolset['tools']);

			// is the tool in this toolset?
			if ($tool_index !== FALSE)
			{
				$used_tools[$tool_index] = $tool;
			}
			else
			{
				$unused_tools[] = $tool;
			}
		}

		// sort used tools by custom order
		ksort($used_tools, SORT_NUMERIC);
		
		// set up the form
		$vars = array(
			'action'			=> $this->form_url.AMP.'method=save_toolset'.( !! $toolset_id ? AMP.'toolset_id='.$toolset_id : ''),
			'is_private'		=> $is_private,
			'toolset_name'		=> ( ! $toolset || $is_private ? '' : $toolset['name']),
			'available_tools'	=> $enabled_tools,
			'unused_tools'		=> $unused_tools,
			'used_tools'		=> $used_tools
		);
		
		// JS
		$this->EE->cp->add_js_script(array(
			'ui' 	=> 'sortable',
			'file'	=> 'cp/rte'
		));
		
		// CSS
		$this->EE->cp->add_to_head($this->EE->view->head_link('css/rte.css'));
		
		// return the form
		$this->EE->output->send_ajax_response(array(
			'success' => $this->EE->load->view('edit_toolset', $vars, TRUE)
		));
	}

	// --------------------------------------------------------------------
	
	/**
	 * Saves a toolset
	 *
	 * @access	public
	 * @return	void
	 */
	public function save_toolset()
	{
		$this->EE->output->enable_profiler(FALSE);
		
		$this->EE->load->model('rte_toolset_model');

		// get the toolset
		$toolset_id = $this->EE->input->get_post('toolset_id');

		$toolset = array(
			'name'		=> $this->EE->input->get_post('toolset_name'),
			'tools' 	=> $this->EE->input->get_post('selected_tools'),
			'member_id'	=> ($this->EE->input->get_post('private') == 'true' ? $this->EE->session->userdata('member_id') : 0)
		);

		// is this an individual’s private toolset?
		$is_members = ($this->EE->input->get_post('private') == 'true');

		// did an empty name sneak through?
		if (empty($toolset['name']))
		{
			$this->EE->output->send_ajax_response(array(
				'error' => lang('name_required')
			));
		}

		// is the name unique?
		if ( ! $is_members && ! $this->EE->rte_toolset_model->unique_name($toolset['name'], $toolset_id))
		{
			$this->EE->output->send_ajax_response(array(
				'error' => lang('unique_name_required')
			));
		}

		// Updating? Make sure the toolset exists and they aren't trying any
		// funny business...
		if ($toolset_id)
		{
			$orig = $this->EE->rte_toolset_model->get($toolset_id);
			
			if ( ! $orig || $is_members && $orig['member_id'] != $this->EE->session->userdata('member_id'))
			{
				$this->EE->output->send_ajax_response(array(
					'error' => lang('toolset_update_failed')
				));
			}
		}
		
		// save it
		if ($this->EE->rte_toolset_model->save_toolset($toolset, $toolset_id) === FALSE)
		{
			$this->EE->output->send_ajax_response(array(
				'error' => lang('toolset_update_failed')
			));
		}

		// if it’s new, get the ID
		if ( ! $toolset_id)
		{
			$toolset_id = $this->EE->db->insert_id();
		}
		
		// If the default toolset was deleted
		if ($this->EE->config->item('rte_default_toolset_id') == 0)
		{
			$this->EE->config->update_site_prefs(array(
				'rte_default_toolset_id' => $toolset_id
			));
		}

		// update the member profile
		if ($is_members && $toolset_id)
		{
			$this->EE->db
				->where('member_id', $this->EE->session->userdata('member_id'))
				->update('members', array('rte_toolset_id' => $toolset_id));
		}

		$this->EE->output->send_ajax_response(array(
			'success' 		=> lang('toolset_updated'),
			'force_refresh' => TRUE
		));
	}

	// ------------------------------------------------------------------------

	/**
	 * Build RTE JS
	 * 
	 * @access	private
	 * @param	int 	The ID of the toolset you want to load
	 * @param	string 	The selector that will match the elements to turn into an RTE
	 * @param	array   Include or skip certain JS libs: array('jquery' => FALSE //skip)
	 * @param	bool	If TRUE, includes tools that are for the control panel only
	 * @return	string 	The JS needed to embed the RTE
	 */
	public function build_js($toolset_id, $selector, $include = array(), $cp_only = FALSE)
	{
		$this->EE->load->model(array('rte_toolset_model','rte_tool_model'));
		
		// no toolset specified?
		if ( ! $toolset_id)
		{
			$toolset_id = $this->EE->rte_toolset_model->get_member_toolset();
		}

		// get the toolset
		$toolset = $this->EE->rte_toolset_model->get($toolset_id);

		if ( ! $toolset OR ! $toolset['tools'])
		{
			return;
		}

		// get the tools
		if ( ! $tools = $this->EE->rte_tool_model->get_tools($toolset['tools']))
		{
			return;
		}

		// bare minimum required
		$bits	= array(
			'globals'		=> array(
				'rte' => array()
			),
			'styles' 		=> '',
			'definitions' 	=> '',
			'buttons' 		=> array(),
			'libraries' 	=> array(
				'plugin' => array('wysihat')
			)
		);

		if ($include['jquery_ui'])
		{
			$bits['libraries']['ui'] = array('core', 'widget');
		}	

		foreach ($tools as $tool)
		{
			// skip tools that are not available to the front-end
			if ($tool['info']['cp_only'] == 'y' && ! $cp_only)
			{
				continue;
			}
			
			// load the globals
			if (count($tool['globals']))
			{
				$tool['globals'] = $this->_prep_globals($tool['globals']);
				$bits['globals'] = array_merge_recursive($bits['globals'], $tool['globals']);
			}
			
			// load any libraries we need
			if ($tool['libraries'] && count($tool['libraries']))
			{
				$bits['libraries'] = array_merge_recursive($bits['libraries'], $tool['libraries']);
			}
			
			// add any styles we need
			if ( ! empty($tool['styles']))
			{
				$bits['styles'] .= $tool['styles'];
			}

			// load the definition
			if ( ! empty($tool['definition']))
			{
				$bits['definitions'] .= $tool['definition'];
			}

			// add to toolbar
			$bits['buttons'][] = strtolower(str_replace(' ', '_', $tool['info']['name']));
		}

		// potentially required assets
		$jquery = $this->EE->config->item('theme_folder_url') . 'javascript/' .
				  ($this->EE->config->item('use_compressed_js') == 'n' ? 'src' : 'compressed') .
				  '/jquery/jquery.js';
		$rtecss	= $this->EE->config->item('theme_folder_url') . 'cp_themes/default/css/rte.css';
		
		$this->EE->load->library('javascript');

		// kick off the JS
		$js = '
		(function(){
			var EE = ' . $this->EE->javascript->generate_json($this->EE->javascript->global_vars) . ';' .
			'
			// make sure we have jQuery
			var interval = null;
			if (typeof jQuery === "undefined") {';
			
		if ($include['jquery'])
		{
			$js .= '
				var j = document.createElement("script");
				j.setAttribute("src","' . $jquery . '");
				document.getElementsByTagName("head")[0].appendChild(j);';
		}

		// Even if we don't load jQuery above, we still need to wait for it
		$js .= '
				interval = setInterval(loadRTE, 100);
			}
			else
			{
				loadRTE();
			}
			
			function loadRTE()
			{
				// make sure jQuery is loaded
				if ( typeof jQuery === "undefined" ){ return; }
				clearInterval( interval );
				
				var $ = jQuery;
				
				// RTE library
				' . $this->_load_js_files($bits['libraries']) . '

				// RTE styles
				$("<link rel=\"stylesheet\" href=\"' . $rtecss . '\"/>")
					.add( $("<style>' . preg_replace( '/\\s+/', ' ', $bits['styles'] ) . '</style>"))
					.appendTo("head");

				// RTE globals
				' . $this->_set_globals($bits['globals']) . '

				// RTE button class definitions
				' . $bits['definitions'] . '

				// RTE editor setup for this page
				$("' . $selector . '")
					.addClass("WysiHat-field")
					.wysihat({
						buttons: '.$this->EE->javascript->generate_json($bits['buttons'], TRUE).'
					});
			}
		})();';

		return $js;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Save RTE field
	 *
	 * Use to clean up RTE content prior to DB insertion
	 * 
	 * @param string $data the RTE html content
	 * 
	 * @return string   the cleaned up RTE html content
	 */
	public function save_field($data)
	{
		if ($this->EE->session->userdata('rte_enabled') != 'y' 
			OR $this->EE->config->item('rte_enabled') != 'y')
		{
			return $data;
		}
		
		// If the editor was saved empty, save nothing to database
		// so it behaves as expected with conditional tags
		if ($this->is_empty($data))
		{
			return NULL;
		}

		$data = str_replace('<br>', "\n", $data); // must happen before the decode or we won't know which are ours
		$data = htmlspecialchars_decode($data, ENT_QUOTES);

		// decode double encoded code chunks
		if (preg_match_all("/\[code\](.+?)\[\/code\]/si", $data, $matches))
		{
			foreach ($matches[1] as $chunk)
			{
				$chunk = trim($chunk);
				$chunk = html_entity_decode($chunk, ENT_QUOTES, 'UTF-8');
				$data = str_replace($matches[0][$i], '[code]'.$chunk.'[/code]', $data);
			}
		}

		return $data;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Display an RTE field
	 * 
	 * @param string $data       the RTE html content
	 * @param string $field_name the field name for the RTE field
	 * @param array $settings   field settings:
	 * 					field_ta_rows - the number of textarea rows
	 * 					field_text_direction - ltr or rtl
	 * 					field_fmt - xhtml, br or none
	 * 
	 * @return string	
	 */
	public function display_field($data, $field_name, $settings)
	{
		$this->EE->load->helper('form');
		
		$field = array(
			'name'	=> $field_name,
			'id'	=> $field_name,
			'rows'	=> $settings['field_ta_rows'],
			'dir'	=> $settings['field_text_direction']
		);


		// form prepped nonsense
		$data = htmlspecialchars_decode($data, ENT_QUOTES);
		$code_marker = unique_marker('code');
		$code_chunks = array();

		$field_ft = isset($settings['field_fmt']) ? $settings['field_fmt'] : '';

		if ($field_ft == 'xhtml')
		{
			$data = trim($data);

			// Undo any existing newline formatting. Typography will change
			// it anyways and the rtf will add its own. Having this here
			// prevents growing-newline syndrome in the rtf and lets us switch
			// between rtf and non-rtf.

			$data = preg_replace("/<\/p>\n*<p>/is", "\n\n", $data);
			$data = preg_replace("/<br( \/)?>\n/is", "\n", $data);
		}

		// remove code chunks
		if (preg_match_all("/\[code\](.+?)\[\/code\]/si", $data, $matches))
		{
			foreach ($matches[1] as $i => $chunk)
			{
				$code_chunks[] = trim($chunk);
				$data = str_replace($matches[0][$i], $code_marker.$i, $data);
			}
		}

		// Check the RTE module and user's preferences
		if ($this->EE->session->userdata('rte_enabled') == 'y' 
			AND $this->EE->config->item('rte_enabled') == 'y')
		{
			$field['class']	= 'WysiHat-field';

			foreach ($code_chunks as &$chunk)
			{
				$chunk = htmlentities($chunk, ENT_QUOTES, 'UTF-8');
				$chunk = str_replace("\n", '<br>', $chunk);
			}

			// xhtml vs br
			if ($settings['field_fmt'] == 'xhtml')
			{
				$this->EE->load->library('typography');

				$data = $this->EE->typography->_format_newlines($data);

				// Remove double paragraph tags
				$data = preg_replace("/(<\/?p>)\\1/is", "\\1", $data);
			}
		}

		// put code chunks back
		foreach ($code_chunks as $i => $chunk)
		{
			$data = str_replace($code_marker.$i, '[code]'.$chunk.'[/code]', $data);
		}
		
		// Swap {filedir_x} with the real URL. It will be converted back
		// upon submit by the RTE Image tool.
		$this->EE->load->model('file_upload_preferences_model');
		$dirs = $this->EE->file_upload_preferences_model->get_file_upload_preferences($this->EE->session->userdata('group_id'));
		
		foreach($dirs as $d)
		{
			// tag to replace
			$filedir = "{filedir_{$d['id']}}";

			$data = str_replace($filedir, $d['url'], $data);
		}
	
		$data = htmlspecialchars($data, ENT_QUOTES);

		$field['value'] = $data;
		
		return form_textarea($field);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Check whether the specified data is empty html
	 * 
	 * @param string $data the RTE html content
	 * 
	 * @return bool
	 */
	public function is_empty($data)
	{
		return in_array($data, $this->_empty);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Loads JS library files
	 * 
	 * Note: This is partially borrowed from the combo loader
	 * 
	 * @access	private
	 * @param	array $load A collection of JS libraries to load
	 * @return	string The libraries
	 */
	private function _load_js_files($load = array())
	{
		$folder = $this->EE->config->item('use_compressed_js') == 'n' ? 'src' : 'compressed';

		if ( ! defined('PATH_JQUERY'))
		{
			define('PATH_JQUERY', PATH_THEMES.'javascript/'.$folder.'/jquery/');
		}

		$types	= array(
			'effect'	=> PATH_JQUERY.'ui/jquery.effects.',
			'ui'		=> PATH_JQUERY.'ui/jquery.ui.',
			'plugin'	=> PATH_JQUERY.'plugins/',
			'file'		=> PATH_THEMES.'javascript/'.$folder.'/',
			'package'	=> PATH_THIRD,
			'fp_module'	=> PATH_MOD
		);
		
		$contents = '';
		
		foreach ($types as $type => $path)
		{			
			if (isset($load[$type]))
			{
				// Don't load the same library twice
				$load[$type] = array_unique((array)$load[$type]);

				$files = $load[$type];

				if ( ! is_array($files))
				{
					$files = array( $files );
				}
				
				foreach ($files as $file)
				{
					if ($type == 'package' OR $type == 'fp_module')
					{
						$file = $file.'/javascript/'.$file;
					}
					elseif ($type == 'file')
					{
						$parts = explode('/', $file);
						$file = array();

						foreach ($parts as $part)
						{
							if ($part != '..')
							{
								$file[] = $this->EE->security->sanitize_filename($part);
							}
						}

						$file = implode('/', $file);
					}
					else
					{
						$file = $this->EE->security->sanitize_filename($file);
					}

					$file = $path.$file.'.js';

					if (file_exists($file))
					{
						$contents .= file_get_contents($file)."\n\n";
					}
				}
			}
		}

		return $contents;
	}

	// ------------------------------------------------------------------------

	/**
	 * Prep global variables for JS
	 * 
	 * @access	private
	 * @param	array $globals The globals to load into JS
	 * @return	array the revised $globals array
	 */
	private function _prep_globals($globals = array())
	{
		$temp = array();

		foreach ($globals as $key => $val)
		{
			if (strpos($key,'.') !== FALSE)
			{
				$parts	= explode('.', $key);
				$parent	= array_shift($parts);
				$key	= implode('.', $parts);
				$temp[$parent] = $this->_prep_globals(array(
					$key	=> $val
				));
			}
			else
			{
				$temp[$key] = $val;
			}
		}

		return $temp;
	}

	// ------------------------------------------------------------------------

	/**
	 * Manage the assignment of global JS
	 * 
	 * @access	private
	 * @param	array $globals The globals to load into JS
	 * @return	string The JavaScript
	 */
	private function _set_globals($globals = array())
	{
		$this->EE->load->library('javascript');
		
		$js = '';
		
		if (count($globals))
		{
			$js .= 'var EE = ' . $this->EE->javascript->generate_json($globals) . ';';
		}
		
		return $js;
	}
}

/* End of file rte_lib.php */
/* Location: ./system/expressionengine/modules/rte/libraries/rte_lib.php */