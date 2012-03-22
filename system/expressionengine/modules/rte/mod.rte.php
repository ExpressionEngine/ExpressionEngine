<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Rich Text Editor Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		Aaron Gustafson
 * @link		http://easy-designs.net
 */
class Rte {

	public $return_data	= '';
	
	/**
	  * Constructor
	  */
	public function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}
	

	/**
	 * Get RTE JS for the front end
	 *
	 * Called via an ACT
	 * 
	 * @access	public
	 * @return	mixed 	The rendered JS or the ACT URL to it 
	 */
	public function get_js()
	{
		$selector 		= $this->EE->input->get('selector');
		$toolset_id 	= (int)$this->EE->input->get('toolset_id');
		$include_jquery = $this->EE->input->get('include_jquery') == 'n' ? FALSE : TRUE;

		if ( ! $this->EE->input->get('ACT') OR ! $selector)
		{
			exit();
		}

		// @todo Normalize quotes in $selector

		$this->EE->output->enable_profiler(FALSE);
		$this->EE->output->out_type = 'js';
		$this->EE->output->set_header("Content-Type: text/javascript");
		$this->EE->output->set_output($this->_build_js(urldecode($selector), $toolset_id, $include_jquery));
	}


	/**
	 * Returns the action URL for the RTE JavaScript
	 *
	 * @access	public
	 * @return	mixed 	The rendered JS or the ACT URL to it 
	 */
	public function script_url()
	{
		$selector 		= $this->EE->TMPL->fetch_param('selector', '.rte');
		$toolset_id 	= (int)$this->EE->TMPL->fetch_param('toolset_id', 0);
		$include_jquery = $this->EE->TMPL->fetch_param('include_jquery') == 'no' ? 'n' : 'y';

		return $this->EE->functions->fetch_site_index().QUERY_MARKER
			.'ACT='.$this->EE->functions->fetch_action_id('Rte', 'get_js')
			.'&selector='.$selector
			.'&toolset_id='.$toolset_id
			.'&include_jquery='.$include_jquery;
	}


	/**
	 * Build RTE JS
	 * 
	 * @access	private
	 * @param	string 	The selector that will match the elements to turn into an RTE
	 * @param	int 	The ID of the toolset you want to load
	 * @param	bool 	Whether to attempt to load jQuery
	 * @return	string 	The JS needed to embed the RTE
	 */
	private function _build_js($selector, $toolset_id, $include_jquery)
	{
		$this->EE->load->model(array('rte_toolset_model','rte_tool_model'));
		
		// get the tools
		if ( ! $toolset_id)
		{
			$toolset_id = $this->EE->rte_toolset_model->get_member_toolset();
		}

		$tools = $this->EE->rte_tool_model->get_tools($toolset_id);

		if ( ! $tools OR $this->EE->config->item('rte_enabled') != 'y')
		{
			return;
		}

		// load the tools
		$bits	= array(
			'globals'		=> array(
				'rte'	=> array(
					'update_event' => 'WysiHat-editor:change'
				)
			),
			'libraries'		=> array(
				'ui'		=> array(
					'core', 'widget'
				),
				'plugin'	=> array(
					'wysihat'
				)
			),
			'styles'		=> '',
			'definitions'	=> ''
		);

		foreach ($tools as $tool)
		{
			// skip tools that are not available to the front-end
			if ($tool['info']['cp_only'] == 'y')
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
			if (count($tool['libraries']))
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
		}
		
		// required assets
		$jquery = $this->EE->config->item('theme_folder_url') . 'javascript/' .
				  ($this->EE->config->item('use_compressed_js') == 'n' ? 'src' : 'compressed') .
				  '/jquery/jquery.js';
		$uicss	= $this->EE->config->item('theme_folder_url') . 'javascript/' .
				  ($this->EE->config->item('use_compressed_js') == 'n' ? 'src' : 'compressed') .
				  '/jquery/themes/default/ui.all.css';
		$rtecss	= $this->EE->config->item('theme_folder_url') . 'cp_themes/default/css/rte.css';
		
		// kick off the JS
		$js = '
		(function(){
			// make sure we have jQuery
			var interval = null;
			if (typeof jQuery === "undefined") {';
			
		if ($include_jquery)
		{
			$js .= 'var j = document.createElement("script");
				j.setAttribute("src","' . $jquery . '");
				document.getElementsByTagName("head")[0].appendChild(j);';
		}

		// Even if we don't load jQuery above, we still need to wait for it
		$js .= '
				interval = setInterval( loadRTE, 10);
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
				
				// library code
				' . $this->_load_js_files($bits['libraries']) . '

				// load in the styles (including base jQuery UI and RTE)
				$("<link rel=\"stylesheet\" href=\"' . $uicss . '\"/>")
					.add( $("<link rel=\"stylesheet\" href=\"' . $rtecss . '\"/>") )
					.add( $("<style>' . preg_replace( '/\\s+/', ' ', $bits['styles'] ) . '</style>") )
					.appendTo("head");

				// globals
				' . $this->_set_globals($bits['globals']) . '

				$("' . $selector . '").each(function(index)
				{
					var $field = $(this);
					
					// Add ID attributes to textareas missing them
					if ($field.attr("id") == undefined)
					{
						$field.attr("id", "rte-"+index);
					}
					
					var
					$parent	= $field.parent(),

					// set up the editor
					$editor	= WysiHat.Editor.attach($field),

					// establish the toolbar
					toolbar	= new WysiHat.Toolbar();

					toolbar.initialize($editor); 

					// tools
					' . $bits['definitions'] . '

				});
			}
		})();';

		return $js;
	}
	
	/**
	 * Loads JS library files
	 * 
	 * Note: This is partially borrowed from the combo loader
	 * 
	 * @access	private
	 * @param	array $load A collection of JS libraries to load
	 * @return	string The libraries
	 */
	private function _load_js_files( $load = array() )
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

	/**
	 * Prep global variables for JS
	 * 
	 * @access	private
	 * @param	array $globals The globals to load into JS
	 * @return	array the revised $globals array
	 */
	private function _prep_globals( $globals = array() )
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
// END CLASS

/* End of file mod.rte.php */
/* Location: ./system/expressionengine/modules/rte/mod.rte.php */