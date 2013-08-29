<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
		ee()->lang->loadfile('rte');
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
			$toolset_id = ee()->input->get_post('toolset_id');
		}

		if ( ! is_numeric($toolset_id))
		{
			exit();
		}

		ee()->output->enable_profiler(FALSE);

		ee()->load->library(array('table','javascript'));
		ee()->load->model(array('rte_toolset_model','rte_tool_model'));

		// new toolset?
		if ($toolset_id == 0)
		{
			$toolset['tools'] = array();
			$toolset['name'] = '';
			$is_private = (ee()->input->get_post('private') == 'true');
		}
		else
		{
			// make sure user can access the existing toolset
			if ( ! ee()->rte_toolset_model->member_can_access($toolset_id))
			{
				ee()->output->send_ajax_response(array(
					'error' => lang('toolset_edit_failed')
				));
			}

			// grab the toolset
			$toolset	= ee()->rte_toolset_model->get($toolset_id);
			$is_private	= ($toolset['member_id'] != 0);
		}


		// get list of enabled tools
		$enabled_tools = ee()->rte_tool_model->get_tool_list(TRUE);

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
		ee()->cp->add_js_script(array(
			'ui' 	=> 'sortable',
			'file'	=> 'cp/rte'
		));

		// CSS
		ee()->cp->add_to_head(ee()->view->head_link('css/rte.css'));

		// return the form
		ee()->output->send_ajax_response(array(
			'success' => ee()->load->view('edit_toolset', $vars, TRUE)
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
		ee()->output->enable_profiler(FALSE);

		ee()->load->model('rte_toolset_model');

		// get the toolset
		$toolset_id = ee()->input->get_post('toolset_id');

		$toolset = array(
			'name'		=> ee()->input->get_post('toolset_name'),
			'tools' 	=> ee()->input->get_post('selected_tools'),
			'member_id'	=> (ee()->input->get_post('private') == 'true' ? ee()->session->userdata('member_id') : 0)
		);

		// is this an individual’s private toolset?
		$is_members = (ee()->input->get_post('private') == 'true');

		// did an empty name sneak through?
		if (empty($toolset['name']))
		{
			ee()->output->send_ajax_response(array(
				'error' => lang('name_required')
			));
		}

		// is the name unique?
		if ( ! $is_members && ! ee()->rte_toolset_model->unique_name($toolset['name'], $toolset_id))
		{
			ee()->output->send_ajax_response(array(
				'error' => lang('unique_name_required')
			));
		}

		// Updating? Make sure the toolset exists and they aren't trying any
		// funny business...
		if ($toolset_id)
		{
			$orig = ee()->rte_toolset_model->get($toolset_id);

			if ( ! $orig || $is_members && $orig['member_id'] != ee()->session->userdata('member_id'))
			{
				ee()->output->send_ajax_response(array(
					'error' => lang('toolset_update_failed')
				));
			}
		}

		// save it
		if (ee()->rte_toolset_model->save_toolset($toolset, $toolset_id) === FALSE)
		{
			ee()->output->send_ajax_response(array(
				'error' => lang('toolset_update_failed')
			));
		}

		// if it’s new, get the ID
		if ( ! $toolset_id)
		{
			$toolset_id = ee()->db->insert_id();
		}

		// If the default toolset was deleted
		if (ee()->config->item('rte_default_toolset_id') == 0)
		{
			ee()->config->update_site_prefs(array(
				'rte_default_toolset_id' => $toolset_id
			));
		}

		// update the member profile
		if ($is_members && $toolset_id)
		{
			ee()->db
				->where('member_id', ee()->session->userdata('member_id'))
				->update('members', array('rte_toolset_id' => $toolset_id));
		}

		ee()->output->send_ajax_response(array(
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
		ee()->load->model(array('rte_toolset_model','rte_tool_model'));

		// no toolset specified?
		if ( ! $toolset_id)
		{
			$toolset_id = ee()->rte_toolset_model->get_member_toolset();
		}

		// get the toolset
		$toolset = ee()->rte_toolset_model->get($toolset_id);

		if ( ! $toolset OR ! $toolset['tools'])
		{
			return;
		}

		// get the tools
		if ( ! $tools = ee()->rte_tool_model->get_tools($toolset['tools']))
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
		$jquery = ee()->config->item('theme_folder_url') . 'javascript/' .
				  (ee()->config->item('use_compressed_js') == 'n' ? 'src' : 'compressed') .
				  '/jquery/jquery.js';
		$rtecss	= ee()->config->item('theme_folder_url') . 'cp_themes/default/css/rte.css';

		ee()->load->library('javascript');

		$js = '(function() {'."\n";
		$js .= 	'// make sure we have jQuery
				var interval = null;'."\n";

		if ($cp_only === FALSE)
		{
			// kick off the JS
			$js .= '
				var EE = ' . json_encode(ee()->javascript->global_vars) . ';';

			if ($include['jquery'])
			{
				$js .= '
					var j = document.createElement("script");
					j.setAttribute("src","' . $jquery . '");
					document.getElementsByTagName("head")[0].appendChild(j);';
			}
		}


		// Even if we don't load jQuery above, we still need to wait for it
		$js .= '
			if (typeof jQuery === "undefined")
			{
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
					.not(".grid_field ' . $selector . '")
					.addClass("WysiHat-field")
					.wysihat({
						buttons: '.json_encode($bits['buttons']).'
					});

				Grid.bind("rte", "display", function(cell)
				{
					$("' . $selector . '", cell)
						.addClass("WysiHat-field")
						.wysihat({
							buttons: '.json_encode($bits['buttons']).'
						});
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
		if (ee()->session->userdata('rte_enabled') != 'y'
			OR ee()->config->item('rte_enabled') != 'y')
		{
			return $data;
		}

		// If the editor was saved empty, save nothing to database
		// so it behaves as expected with conditional tags
		if ($this->is_empty(trim($data)))
		{
			return NULL;
		}

		// The rte tries to create pretty html for its source view, but we want
		// to store our data with minimal html, allowing EE's auto typography to
		// do the bulk work and letting us switch back and forth. So first up, we
		// remove a bunch of newline formatting.

		// Strip newlines around <br>s
		$data = preg_replace("#\n?(<br>|<br />)\n?#i", "\n", $data);

		// Strip <br>s
		$data = preg_replace("#<br>|<br />#i", "\n", $data);

		// Strip paragraph tags
		$data = preg_replace("#<(/)?pre[^>]*?>#i", "<$1pre>", $data);
		$data = preg_replace("#<p>|<p(?!re)[^>]*?".">|</p>#i", "",  preg_replace("#<\/p><p(?!re)[^>]*?".">#i", "\n", $data));

		// Reduce newlines
		$data = preg_replace('/\n\n+/', "\n\n", $data);

		// decode double encoded code chunks
		if (preg_match_all("#\[code\](.+?)\[/code\]#si", $data, $matches))
		{
			foreach ($matches[1] as $i => $chunk)
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
	public function display_field($data, $field_name, $settings, $container = NULL)
	{
		if ( ! ee()->session->cache('rte', 'loaded'))
		{
			ee()->javascript->output(
				ee()->rte_lib->build_js(0, '.WysiHat-field', NULL, (REQ == 'CP'))
			);

			ee()->session->set_cache('rte', 'loaded', TRUE);
		}

		ee()->load->helper('form');

		$field = array(
			'name'	=> $field_name,
			'id'	=> $field_name,
			'rows'	=> $settings['field_ta_rows'],
			'dir'	=> $settings['field_text_direction']
		);


		// form prepped nonsense
		$code_marker = unique_marker('code');
		$code_chunks = array();

		$data = trim($data);
		$data = htmlspecialchars_decode($data, ENT_QUOTES);

		// Collapse tags and undo any existing newline formatting. Typography
		// will change it anyways and the rte will add its own. Having this here
		// prevents growing-newline syndrome in the rte and lets us switch
		// between rte and non-rte.
		$data = preg_replace('/<br( *\/)?>\n*/is', "<br>\n", $data);

		$data = preg_replace("/<\/p>\n*<p>/is", "\n\n", $data);
		$data = preg_replace("/<br>\n/is", "\n", $data);

		// most newlines we should ever have is 2
		$data = preg_replace('/\n\n+/', "\n\n", $data);

		// remove code chunks
		if (preg_match_all("/\[code\](.+?)\[\/code\]/si", $data, $matches))
		{
			
			foreach ($matches[1] as $i => $chunk)
			{
				$code_chunks[$i] = trim($chunk);
				$data = str_replace($matches[0][$i], $code_marker.$i, $data);
			}
		}

		// Check the RTE module and user's preferences
		if (ee()->session->userdata('rte_enabled') == 'y'
			AND ee()->config->item('rte_enabled') == 'y')
		{
			$field['class']	= 'WysiHat-field';

			foreach ($code_chunks as $i => $chunk)
			{
				$chunk = htmlentities($chunk, ENT_QUOTES, 'UTF-8');
				$chunk = str_replace("\n", '<br>', $chunk);
				$code_chunks[$i] = $chunk;
			}

			// xhtml vs br
			ee()->load->library('typography');

			$data = ee()->typography->auto_typography($data, TRUE);

			// remove non breaking spaces. typography likes to throw those
			// in when a list is indented.
			$data = str_replace('&nbsp;', ' ', $data);
		}

		// put code chunks back
		foreach ($code_chunks as $i => $chunk)
		{
			$data = str_replace($code_marker.$i, '[code]'.$chunk.'[/code]', $data);
		}

		// Swap {filedir_x} with the real URL. It will be converted back
		// upon submit by the RTE Image tool.
		ee()->load->model('file_upload_preferences_model');
		$dirs = ee()->file_upload_preferences_model->get_file_upload_preferences(ee()->session->userdata('group_id'));

		foreach($dirs as $d)
		{
			// tag to replace
			$filedir = "{filedir_{$d['id']}}";

			$data = str_replace($filedir, $d['url'], $data);
		}

		$field['value'] = $data;

		$return_data = form_textarea($field);

		if ($container = 'grid')
		{
			$return_data = '<div class="grid_full_cell_container">'.$return_data.'</div>';
		}

		return $return_data;
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
		$folder = ee()->config->item('use_compressed_js') == 'n' ? 'src' : 'compressed';

		if ( ! defined('PATH_JQUERY'))
		{
			define('PATH_JQUERY', PATH_THEMES.'javascript/'.$folder.'/jquery/');
		}

		$types	= array(
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
								$file[] = ee()->security->sanitize_filename($part);
							}
						}

						$file = implode('/', $file);
					}
					else
					{
						$file = ee()->security->sanitize_filename($file);
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
		ee()->load->library('javascript');

		$js = '';

		if (count($globals))
		{
			$js .= 'var EE = ' . json_encode($globals) . ';';
		}

		return $js;
	}
}

/* End of file rte_lib.php */
/* Location: ./system/expressionengine/modules/rte/libraries/rte_lib.php */
